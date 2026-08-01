variable "vpc_id" {
  type = string
}

variable "private_subnet_ids" {
  type = list(string)
}

variable "rds_security_group_id" {
  description = "既存 house-hold-api-sg。RDSがこのSGからの3306を許可済み(0.0.0.0/0)のため、LambdaのSGを追加で許可し直す必要はないが、将来的にはこのLambda専用SGに絞り込むことを推奨する"
  type        = string
  default     = "sg-00da69b8788f033d3"
}

variable "artifact_bucket_name" {
  type    = string
  default = "house-hold-api-lambda-artifacts"
}

variable "deploy_object_key" {
  description = <<-EOT
    デプロイ用zipのS3キー。CIが毎回このキーに最新のzipを上書きし続ける固定キーにすること。
    (lessons-learned 3-18: ignore_changesで追跡除外している属性は、
     他の属性変更のついでに古いプレースホルダー値が再送されるリスクがあるため、
     デフォルト値自体を「放置されても安全な値」にしておく)
  EOT
  type        = string
  default     = "backend/latest.zip"
}

variable "bref_layer_arn" {
  description = <<-EOT
    Bref PHP-FPMレイヤーのARN。バージョンは `vendor/bref/bref/layers.json` を参照して固定すること。
    最初はECSと同じx86_64で揃え、安定後にarm64へ切り替える(その際はレイヤーARNも `arm-php-82-fpm` に変更)。
  EOT
  type        = string
  default     = "arn:aws:lambda:ap-northeast-1:534081306603:layer:php-82-fpm:56"
}

variable "bref_console_layer_arn" {
  type    = string
  default = "arn:aws:lambda:ap-northeast-1:534081306603:layer:console:83"
}

variable "lambda_memory_mb" {
  type    = number
  default = 512
}

variable "lambda_timeout_seconds" {
  type    = number
  default = 28 # CloudFrontのオリジンタイムアウト(30秒)未満に収める
}

variable "environment" {
  description = <<-EOT
    Lambda環境変数。DB_PASSWORD/APP_KEY等の秘匿値はここに書かず、
    GitHub Secrets経由でCIが `aws lambda update-function-configuration` で都度設定する
    (lessons-learned 3-1)。Terraform側は lifecycle.ignore_changes で environment を追跡除外する。
  EOT
  type        = map(string)
  default = {
    APP_ENV          = "production"
    APP_DEBUG        = "false"
    LOG_CHANNEL      = "stderr"
    SESSION_DRIVER   = "database"
    CACHE_DRIVER     = "database"
    QUEUE_CONNECTION = "sync"
  }
}

variable "cloudfront_distribution_arn" {
  description = "Function URLの呼び出し元をこのCloudFrontディストリビューションに限定する"
  type        = string
}
