variable "vpc_id" {
  type = string
}

variable "public_subnet_ids" {
  type = list(string)
}

variable "security_group_id" {
  description = <<-EOT
    既存の house-hold-api-sg (sg-00da69b8788f033d3)。ALB・ECSタスク・RDSで共用中のためそのまま参照する。
    Terraform管理外(aws_security_groupリソースとしてimportしていない、CloudFrontと同様の設計判断)。

    【2026-08-01修正・AWS CLIで直接適用】インバウンド3306(MySQL)が0.0.0.0/0に開放されていたため、
    自己参照(ECS用)とLambda SG(sg-0dfb9e6ed6ce92fb8)からのみ許可するルールに置き換えた。
    RDSはpublicly_accessible=falseのため実際のインターネット直接到達性はなかったが、
    同SGを共用するALBの公開ENI経由でポートスキャン上は開いて見える状態だったため是正した。
  EOT
  type        = string
  default     = "sg-00da69b8788f033d3"
}

variable "desired_count" {
  description = "compute_backend=ecs の時は1、lambdaの時は0にしてすぐ戻せる状態で待機させる"
  type        = number
  default     = 0
}

variable "container_image" {
  description = "ECRのイメージURI（タグ込み）。CIが `aws ecs update-service --force-new-deployment` 相当で更新するため、Terraformでは初期値のみ管理しignore_changesする"
  type        = string
  default     = "985196657378.dkr.ecr.ap-northeast-1.amazonaws.com/house-hold-api:latest"
}

variable "acm_certificate_domain" {
  type    = string
  default = "*.kake-pon.com"
}
