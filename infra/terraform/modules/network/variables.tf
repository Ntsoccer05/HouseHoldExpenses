variable "vpc_id" {
  description = "既存VPC（コンソール実査で確認済み）"
  type        = string
  default     = "vpc-0c3c823b2089c4354"
}

variable "public_subnet_ids" {
  description = "既存の2つのパブリックサブネット（ECS/ALBが現在使用中）"
  type        = list(string)
  default     = ["subnet-02daa7740d3042752", "subnet-088425f30aa6ec9f9"]
}

variable "private_subnet_ids" {
  description = <<-EOT
    既存の2つのプライベートサブネット（house-hold-app-subnet-private1-a/c）。
    コンソール実査の結果、当初の設計書には未記載だったが既に作成済みで未使用だったため、
    Lambda用に新規サブネットを作らずこれをそのまま再利用する。
  EOT
  type        = list(string)
  default     = ["subnet-076b6e1f767f30710", "subnet-0f91a683cfea82b3f"]
}

variable "nat_type" {
  description = <<-EOT
    Lambda(VPCアタッチ)が外向き通信(Google OAuth, SMTP等)をするためのNAT方式。
      "instance" : 自前NATインスタンス(t4g.nano)。安価だが運用の落とし穴がある(README参照)
      "gateway"  : AWS管理NAT Gateway。高価だが信頼性が高い
      "none"     : NATを作らない(Lambdaはprivate subnetに置かず、外向き通信はできない前提)
    詳細な月額差は COST_ESTIMATE.md 参照。
  EOT
  type        = string
  default     = "instance"

  validation {
    condition     = contains(["instance", "gateway", "none"], var.nat_type)
    error_message = "nat_type は instance / gateway / none のいずれか"
  }
}

variable "nat_instance_type" {
  type    = string
  default = "t4g.nano"
}
