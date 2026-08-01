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

# --- Lambda: fpm-runtime (HTTP) ------------------------------------------------

resource "aws_lambda_function" "app" {
  function_name = "house-hold-app-api"
  role          = aws_iam_role.lambda_exec.arn
  runtime       = "provided.al2"
  handler       = "public/index.php"
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
    variables = merge(var.environment, {
      # lessons-learned 1章: デフォルトのRUNTIME_CLASSフォールバックに頼らず明示指定
      RUNTIME_CLASS = "Bref\\FpmRuntime\\Main"
    })
  }

  lifecycle {
    ignore_changes = [s3_key, environment] # CIが直接 update-function-code / update-function-configuration で更新する
  }
}

resource "aws_lambda_function_url" "app" {
  function_name      = aws_lambda_function.app.function_name
  authorization_type = "AWS_IAM" # CloudFrontからのOAC相当の呼び出しのみ許可(下記permissionで限定)
}

# CloudFrontがLambda Function URLをSigV4署名付きで呼び出すためのOAC。
# switch_origin.sh 実行時に `terraform output lambda_oac_id` の値を OAC_ID として渡す。
resource "aws_cloudfront_origin_access_control" "lambda" {
  name                              = "house-hold-app-lambda-oac"
  origin_access_control_origin_type = "lambda"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

# lessons-learned 3-23と同種の問題(オリジンドメイン露出)を避けるため、CloudFront以外からの
#直接アクセスを拒否する。Function URLへの invoke 権限を対象CloudFrontディストリビューションに限定する。
resource "aws_lambda_permission" "allow_cloudfront" {
  statement_id           = "AllowCloudFrontServicePrincipal"
  action                 = "lambda:InvokeFunctionUrl"
  function_name          = aws_lambda_function.app.function_name
  principal              = "cloudfront.amazonaws.com"
  source_arn             = var.cloudfront_distribution_arn
  function_url_auth_type = "AWS_IAM"
}

# --- Lambda: console-runtime (artisan) ----------------------------------------

resource "aws_lambda_function" "console" {
  function_name = "house-hold-app-console"
  role          = aws_iam_role.lambda_exec.arn
  runtime       = "provided.al2"
  handler       = "artisan"
  memory_size   = var.lambda_memory_mb
  timeout       = 120 # artisanコマンドはHTTPより長めに許容
  layers        = [var.bref_layer_arn, var.bref_console_layer_arn]

  s3_bucket = aws_s3_bucket.artifacts.id
  s3_key    = var.deploy_object_key

  vpc_config {
    subnet_ids         = var.private_subnet_ids
    security_group_ids = [aws_security_group.lambda.id]
  }

  environment {
    variables = merge(var.environment, {
      RUNTIME_CLASS = "Bref\\ConsoleRuntime\\Main"
    })
  }

  lifecycle {
    ignore_changes = [s3_key, environment]
  }
}
