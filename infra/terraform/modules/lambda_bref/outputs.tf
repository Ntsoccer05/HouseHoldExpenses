output "function_name" {
  value = aws_lambda_function.app.function_name
}

output "console_function_name" {
  value = aws_lambda_function.console.function_name
}

output "api_gateway_domain" {
  description = "CloudFrontのオリジンドメインに設定する値(API Gateway HTTP APIのデフォルトエンドポイント)"
  value       = replace(replace(aws_apigatewayv2_api.app.api_endpoint, "https://", ""), "/", "")
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

