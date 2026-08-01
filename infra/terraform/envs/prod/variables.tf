variable "aws_region" {
  type    = string
  default = "ap-northeast-1"
}

variable "compute_backend" {
  description = <<-EOT
    "ecs"    : 現行運用のまま(ECS Fargate + ALB)。切り戻し用。
    "lambda" : サーバーレス(Bref Lambda)。ECS/ALBはdesired_count=0で待機させたまま残す。
  EOT
  type        = string
  default     = "ecs"

  validation {
    condition     = contains(["ecs", "lambda"], var.compute_backend)
    error_message = "compute_backend は ecs か lambda"
  }
}

variable "destroy_idle_ecs" {
  description = "compute_backend=lambda が安定稼働した後、ECS/ALB一式を完全に削除する場合のみtrueにする"
  type        = bool
  default     = false
}

variable "enable_rds_night_stop" {
  description = <<-EOT
    trueの場合、夜間(23:30 JST)にRDSを停止し朝(07:30 JST)に起動するEventBridge Schedulerを作成する。
    2026-08-01時点、DBを常時稼働させる方針のためfalseがデフォルト。
    夜間停止でコストを下げたい場合のみtrueにする（コスト差はCOST_ESTIMATE.md参照）。
  EOT
  type        = bool
  default     = false
}

variable "cloudfront_distribution_id" {
  type    = string
  default = "E11UZMEXBMM184"
}

variable "aws_account_id" {
  type    = string
  default = "985196657378"
}
