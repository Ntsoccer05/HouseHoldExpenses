output "function_name" {
  value = aws_lambda_function.app.function_name
}

output "console_function_name" {
  value = aws_lambda_function.console.function_name
}

output "function_url" {
  value = aws_lambda_function_url.app.function_url
}

output "function_url_domain" {
  description = "CloudFrontのオリジンドメインに設定する値（https:// と末尾の / を除いたホスト名）"
  value       = replace(replace(aws_lambda_function_url.app.function_url, "https://", ""), "/", "")
}

output "function_arn" {
  value = aws_lambda_function.app.arn
}

output "console_function_arn" {
  value = aws_lambda_function.console.arn
}

output "artifact_bucket" {
  value = aws_s3_bucket.artifacts.id
}

output "deploy_object_key" {
  value = var.deploy_object_key
}

output "lambda_role_arn" {
  value = aws_iam_role.lambda_exec.arn
}

output "lambda_oac_id" {
  description = "switch_origin.sh 実行時に OAC_ID として渡す"
  value       = aws_cloudfront_origin_access_control.lambda.id
}
