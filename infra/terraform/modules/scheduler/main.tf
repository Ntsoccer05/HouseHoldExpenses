##############################################################################
# scheduler (compute_backend = "lambda" かつ enable_rds_night_stop = true の時のみ作成)
#
# 【2026-08-01】RDSを24/7常時稼働させる方針になったため、envs/prod側で
# enable_rds_night_stop のデフォルトを false にし、このモジュール自体を作成しないよう変更した。
# 夜間停止でコストを下げたい場合のみ enable_rds_night_stop=true にすれば復活する
# （コスト差は COST_ESTIMATE.md 5章参照）。
#
# 既存の household-scheduler(CloudFormation管理, infra/scheduler/) も同じ理由(ECS desiredCount操作 +
# RDS start/stop の重複、かつLambda移行後もECSを毎朝再起動し続けるバグ状態だった)により2026-08-01に
# スタックごと削除済み。infra/scheduler/ 配下のファイルは削除された旧スタックのソースとして参考用に残す。
##############################################################################

resource "aws_iam_role" "scheduler_lambda" {
  name = "house-hold-app-rds-scheduler-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "lambda.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy_attachment" "scheduler_basic" {
  role       = aws_iam_role.scheduler_lambda.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AWSLambdaBasicExecutionRole"
}

resource "aws_iam_role_policy" "scheduler_rds" {
  name = "rds-start-stop"
  role = aws_iam_role.scheduler_lambda.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "rds:StopDBInstance",
        "rds:StartDBInstance",
        "rds:DescribeDBInstances",
      ]
      Resource = "*"
    }]
  })
}

resource "aws_lambda_function" "scheduler" {
  function_name = "house-hold-app-rds-scheduler"
  role          = aws_iam_role.scheduler_lambda.arn
  runtime       = "python3.12"
  handler       = "index.handler"
  timeout       = 30

  filename         = data.archive_file.scheduler.output_path
  source_code_hash = data.archive_file.scheduler.output_base64sha256

  environment {
    variables = {
      RDS_INSTANCE_ID = var.rds_instance_id
    }
  }
}

data "archive_file" "scheduler" {
  type        = "zip"
  output_path = "${path.module}/build/scheduler.zip"

  source {
    content  = <<-PY
      import boto3, os
      from botocore.exceptions import ClientError

      rds = boto3.client('rds')
      DB_INSTANCE = os.environ['RDS_INSTANCE_ID']

      def handler(event, context):
          action = event.get('action')
          try:
              if action == 'stop':
                  rds.stop_db_instance(DBInstanceIdentifier=DB_INSTANCE)
              elif action == 'start':
                  rds.start_db_instance(DBInstanceIdentifier=DB_INSTANCE)
              else:
                  raise ValueError(f'Unknown action: {action}')
          except ClientError as e:
              if e.response['Error']['Code'] != 'InvalidDBInstanceState':
                  raise
              print(f'RDS already in target state, skipping {action}')
    PY
    filename = "index.py"
  }
}

resource "aws_lambda_permission" "scheduler_invoke" {
  statement_id  = "AllowEventBridgeScheduler"
  action        = "lambda:InvokeFunction"
  function_name = aws_lambda_function.scheduler.function_name
  principal     = "scheduler.amazonaws.com"
}

resource "aws_iam_role" "scheduler_invoke" {
  name = "house-hold-app-rds-scheduler-invoke-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "scheduler.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })
}

resource "aws_iam_role_policy" "scheduler_invoke" {
  name = "invoke-lambda"
  role = aws_iam_role.scheduler_invoke.id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect   = "Allow"
      Action   = "lambda:InvokeFunction"
      Resource = aws_lambda_function.scheduler.arn
    }]
  })
}

resource "aws_scheduler_schedule" "stop" {
  name                         = "house-hold-app-rds-stop"
  schedule_expression          = var.stop_cron
  schedule_expression_timezone = "Asia/Tokyo"

  flexible_time_window {
    mode = "OFF"
  }

  target {
    arn      = aws_lambda_function.scheduler.arn
    role_arn = aws_iam_role.scheduler_invoke.arn
    input    = jsonencode({ action = "stop" })
  }
}

resource "aws_scheduler_schedule" "start" {
  name                         = "house-hold-app-rds-start"
  schedule_expression          = var.start_cron
  schedule_expression_timezone = "Asia/Tokyo"

  flexible_time_window {
    mode = "OFF"
  }

  target {
    arn      = aws_lambda_function.scheduler.arn
    role_arn = aws_iam_role.scheduler_invoke.arn
    input    = jsonencode({ action = "start" })
  }
}
