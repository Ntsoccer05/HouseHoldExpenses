##############################################################################
# scheduler (compute_backend = "lambda" 専用)
#
# 既存の household-scheduler(CloudFormation管理, infra/scheduler/) は
# ECS desiredCount操作 + RDS start/stop の両方を行うが、Lambda移行後はECS操作が
# 不要になる(Lambdaは呼ばれた分だけの課金でdesiredCountという概念が無い)。
#
# 既存のCloudFormationスタックとLambda関数名が競合しないよう、
# この Terraform 版は別名 (house-hold-app-rds-scheduler) で新規作成し、
# 既存の household-scheduler 側は「envs/prod」から compute_backend=ecs の時だけ
# 有効化される運用のまま touch しない（2つのIaCで同じ実リソースを取り合わない設計。README参照）。
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
