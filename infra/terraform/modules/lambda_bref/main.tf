##############################################################################
# lambda_bref
#
# Laravel を Bref(https://bref.sh) で Lambda 化する。TrainingMemoAppの実績構成を踏襲。
#   - house-hold-app-api  : fpm-runtime（HTTPリクエスト処理、CloudFront経由で公開）
#   - house-hold-app-console : console-runtime（artisanコマンド専用、EventBridge Schedulerから起動）
#
# serverless-migration-lessons-learned.md 1章の教訓を反映し、RUNTIME_CLASS を両方とも
# 明示的に設定する（Brefのデフォルトフォールバック挙動に頼らない）。
##############################################################################

data "aws_caller_identity" "current" {}

resource "aws_s3_bucket" "artifacts" {
  bucket = var.artifact_bucket_name
}

resource "aws_s3_bucket_versioning" "artifacts" {
  bucket = aws_s3_bucket.artifacts.id
  versioning_configuration {
    status = "Enabled"
  }
}

# 初回applyのプレースホルダー。以降はCIが同じキーへ実コードを上書きし続ける
resource "aws_s3_object" "placeholder" {
  bucket  = aws_s3_bucket.artifacts.id
  key     = var.deploy_object_key
  content = "placeholder for initial terraform apply. CI overwrites this key on every deploy."

  lifecycle {
    ignore_changes = [content, etag]
  }
}

# --- IAM ---------------------------------------------------------------------

resource "aws_iam_role" "lambda_exec" {
  name = "house-hold-app-lambda-exec-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "lambda.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy_attachment" "vpc_access" {
  role       = aws_iam_role.lambda_exec.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaVPCAccessExecutionRole"
}

resource "aws_iam_role_policy_attachment" "basic_exec" {
  role       = aws_iam_role.lambda_exec.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

# --- Security Group -----------------------------------------------------------

resource "aws_security_group" "lambda" {
  name        = "house-hold-app-lambda-sg"
  description = "Bref Lambda (fpm/console) - private subnet"
  vpc_id      = var.vpc_id

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "house-hold-app-lambda-sg"
  }
}

# --- Lambda: HTTP (FunctionRuntime + Bref\LaravelBridge\Http\HttpHandler) ------
#
# 【重要・実機検証で判明】Bref 3.x + bref/laravel-bridge には "FpmRuntime\Main" という
# クラスは存在しない(FpmRuntimeネームスペースにあるのは FpmHandler のみ)。
# 旧版(Bref 2.x)の "RUNTIME_CLASS=Bref\FpmRuntime\Main" 明示指定はもはや正しくなく、
# 存在しないクラスを指定した結果、デフォルトの FunctionRuntime\Main にフォールバックし、
# handler="public/index.php" がファイルとして直接requireされてクラッシュすることを実機で確認した。
#
# 正しい構成(bref/laravel-bridgeのvendor/.../stubs/serverless.ymlで確認済み):
#   handler: Bref\LaravelBridge\Http\HttpHandler というクラス名を指定する
#   (Bref\Event\Http\HttpHandler の handle() が Bref標準の Handler インターフェースを実装しており、
#    追加のRUNTIME_CLASS指定は一切不要 = デフォルトのFunctionRuntime\Mainのままでよい)
resource "aws_lambda_function" "app" {
  function_name = "house-hold-app-api"
  role          = aws_iam_role.lambda_exec.arn
  runtime       = "provided.al2"
  handler       = "Bref\\LaravelBridge\\Http\\HttpHandler"
  memory_size   = var.lambda_memory_mb
  timeout       = var.lambda_timeout_seconds
  layers        = [var.bref_layer_arn]

  s3_bucket = aws_s3_bucket.artifacts.id
  s3_key    = var.deploy_object_key

  vpc_config {
    subnet_ids         = var.private_subnet_ids
    security_group_ids = [aws_security_group.lambda.id]
  }

  environment {
    variables = var.environment
  }

  lifecycle {
    ignore_changes = [s3_key, environment] # CIが直接 update-function-code / update-function-configuration で更新する
  }
}

# --- API Gateway (HTTP API) ---------------------------------------------------
#
# 【重要・実機検証で判明】Lambda Function URL(OAC署名 / 認証なしパブリックいずれも)は、
# このアカウント/リージョンで原因不明のAccessDeniedExceptionが常に発生し、
# 自分自身のIAM認証情報で署名したリクエストしか通らないという状態だった
# (Function URLの削除・再作成、複数の権限パターンを試したが解消せず)。
# 実績のあるAPI Gateway(HTTP API)経由のLambda Proxy統合に切り替えた。
# 現行ALBと同じくエンドポイント自体は認証なしで公開する(Sanctum等アプリ層の認証はそのまま有効)。
resource "aws_apigatewayv2_api" "app" {
  name          = "house-hold-app-api"
  protocol_type = "HTTP"
}

resource "aws_apigatewayv2_integration" "app" {
  api_id                 = aws_apigatewayv2_api.app.id
  integration_type       = "AWS_PROXY"
  integration_uri        = aws_lambda_function.app.invoke_arn
  payload_format_version = "2.0"
}

resource "aws_apigatewayv2_route" "app_default" {
  api_id    = aws_apigatewayv2_api.app.id
  route_key = "$default"
  target    = "integrations/${aws_apigatewayv2_integration.app.id}"
}

resource "aws_apigatewayv2_stage" "app_default" {
  api_id      = aws_apigatewayv2_api.app.id
  name        = "$default"
  auto_deploy = true
}

resource "aws_lambda_permission" "allow_apigateway" {
  statement_id  = "AllowAPIGatewayInvoke"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.app.function_name
  principal     = "apigateway.amazonaws.com"
  source_arn    = "${aws_apigatewayv2_api.app.execution_arn}/*/*"
}

# --- ウォームアップ(コールドスタート対策, lessons-learned 3-20) ----------------
#
# API Gateway/CloudFrontを経由せず、EventBridge SchedulerからLambda関数を直接invokeする。
# BrefのFpmRuntimeはAPI Gateway v2形式のHTTPイベントを期待するため、
# ペイロード自体を `GET /api/health` を模したJSONにする(DBに触れない軽量エンドポイント)。

resource "aws_iam_role" "warmup_invoke" {
  count = var.warmup_enabled ? 1 : 0
  name  = "house-hold-app-warmup-scheduler-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "scheduler.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "warmup_invoke" {
  count = var.warmup_enabled ? 1 : 0
  name  = "invoke-app-lambda"
  role  = aws_iam_role.warmup_invoke[0].id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = "lambda:InvokeFunction"
      Resource = aws_lambda_function.app.arn
    }]
  })
}

resource "aws_scheduler_schedule" "warmup" {
  count                        = var.warmup_enabled ? 1 : 0
  name                         = "house-hold-app-warmup-ping"
  schedule_expression          = var.warmup_schedule
  schedule_expression_timezone = "Asia/Tokyo"

  flexible_time_window {
    mode = "OFF"
  }

  target {
    arn      = aws_lambda_function.app.arn
    role_arn = aws_iam_role.warmup_invoke[0].arn
    input = jsonencode({
      version        = "2.0"
      routeKey       = "$default"
      rawPath        = "/api/health"
      rawQueryString = ""
      headers = {
        host       = "warmup.internal"
        user-agent = "eventbridge-warmup-ping"
      }
      requestContext = {
        http = {
          method    = "GET"
          path      = "/api/health"
          protocol  = "HTTP/1.1"
          sourceIp  = "127.0.0.1"
          userAgent = "eventbridge-warmup-ping"
        }
      }
      isBase64Encoded = false
    })
  }
}

# --- Lambda: console (FunctionRuntime + App\Lambda\ArtisanHandler) ------------
#
# 【重要・実機検証で判明】このBrefレイヤー(php-82:23)の /opt/bootstrap シェルスクリプトは
# RUNTIME_CLASS を "Bref\FunctionRuntime\Main" に無条件でexportしており、
# Lambda関数側で RUNTIME_CLASS=Bref\ConsoleRuntime\Main を設定しても無視される。
# handler="artisan"(ファイル)のままだと、artisanファイル末尾のexit()でランタイムごと
# 終了してしまいハングする(実機で再現・確認済み)ため、appと同様にBref標準のHandler
# インターフェースを実装した独自クラス(App\Lambda\ArtisanHandler)経由で実行する。
resource "aws_lambda_function" "console" {
  function_name = "house-hold-app-console"
  role          = aws_iam_role.lambda_exec.arn
  runtime       = "provided.al2"
  handler       = "App\\Lambda\\ArtisanHandler"
  memory_size   = var.lambda_memory_mb
  timeout       = 120 # artisanコマンドはHTTPより長めに許容
  layers        = [var.bref_layer_arn]

  s3_bucket = aws_s3_bucket.artifacts.id
  s3_key    = var.deploy_object_key

  vpc_config {
    subnet_ids         = var.private_subnet_ids
    security_group_ids = [aws_security_group.lambda.id]
  }

  environment {
    variables = var.environment
  }

  lifecycle {
    ignore_changes = [s3_key, environment]
  }
}
