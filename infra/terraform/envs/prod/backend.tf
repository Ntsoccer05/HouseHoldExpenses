##############################################################################
# Remote state backend.
# bootstrap/ を apply した後、その出力値(tfstate_bucket, tfstate_lock_table)を
# ここに反映してから `terraform init` すること。
##############################################################################

terraform {
  backend "s3" {
    bucket         = "household-expenses-terraform-state" # bootstrapの出力に合わせる
    key            = "envs/prod/terraform.tfstate"
    region         = "ap-northeast-1"
    dynamodb_table = "household-expenses-terraform-lock"
    encrypt        = true
  }
}
