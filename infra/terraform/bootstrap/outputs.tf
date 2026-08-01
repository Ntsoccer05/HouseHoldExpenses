output "tfstate_bucket" {
  value = aws_s3_bucket.tfstate.id
}

output "tfstate_lock_table" {
  value = aws_dynamodb_table.tfstate_lock.name
}

output "github_actions_role_arn" {
  description = "envs/prod の GitHub Actions workflow で role-to-assume に指定する"
  value       = aws_iam_role.github_actions_deploy.arn
}
