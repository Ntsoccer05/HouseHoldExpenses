variable "vpc_id" {
  type = string
}

variable "public_subnet_ids" {
  type = list(string)
}

variable "security_group_id" {
  description = "既存の house-hold-api-sg (sg-00da69b8788f033d3)。RDSと共用中のためそのまま参照する"
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
