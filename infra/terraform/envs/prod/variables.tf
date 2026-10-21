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
    trueの場合、平日 1:00-6:00 / 11:00-17:00、土日祝 1:00-7:00 (JST) にRDSを停止するEventBridge Schedulerを作成する。
    2026-08-01に24/7稼働へ切り替えたが、2026-10-04にコスト削減のため再びtrueをデフォルトにした。
  EOT
  type        = bool
  default     = true
}

variable "cloudfront_distribution_id" {
  type    = string
  default = "E11UZMEXBMM184"
}

variable "aws_account_id" {
  type    = string
  default = "985196657378"
}
