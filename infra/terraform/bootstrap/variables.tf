variable "aws_region" {
  type    = string
  default = "ap-northeast-1"
}

variable "tfstate_bucket_name" {
  description = "Terraform state用S3バケット名（グローバルで一意である必要がある）"
  type        = string
  default     = "household-expenses-terraform-state"
}

variable "tfstate_lock_table_name" {
  type    = string
  default = "household-expenses-terraform-lock"
}

variable "github_owner" {
  description = "GitHubのオーナー（ユーザー or Organization）名"
  type        = string
  default     = "Ntsoccer05"
}

variable "github_repo" {
  description = "このバックエンドリポジトリ名"
  type        = string
  default     = "HouseHoldExpenses"
}
