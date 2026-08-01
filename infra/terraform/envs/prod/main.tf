##############################################################################
# envs/prod
#
# compute_backend変数でECS運用とサーバーレス(Bref Lambda)運用を切り替える単一スタック。
# 設計の理由は ../../README.md を参照。
#
# 初回apply前に必ず import が必要な既存リソース一覧（README手順3参照）:
#   module.network.aws_route_table.private          -> rtb-005e62414043ad5a5
#   module.ecs_service[0].aws_ecs_cluster.this        -> house-hold-app-cluster
#   module.ecs_service[0].aws_ecs_service.api          -> house-hold-app-cluster/house-hold-app-api-service-1a7svcgy
#   module.ecs_service[0].aws_ecs_task_definition.api  -> house-hold-app-api:97 (revision込み)
#   module.ecs_service[0].aws_lb.this                  -> house-hold-app-alb の ARN
#   module.ecs_service[0].aws_lb_target_group.api      -> house-hold-app-ip-tg の ARN
#   module.ecs_service[0].aws_lb_listener.http_8080    -> HTTP:8080 リスナーARN
#   module.ecs_service[0].aws_lb_listener.https        -> HTTPS:443 リスナーARN
#   module.ecs_service[0].aws_ecr_repository.api       -> house-hold-api
#   module.ecs_service[0].aws_cloudwatch_log_group.api -> /ecs/house-hold-app-api
##############################################################################

module "network" {
  source = "../../modules/network"
}

module "ecs_service" {
  count  = var.destroy_idle_ecs ? 0 : 1
  source = "../../modules/ecs_service"

  vpc_id            = module.network.vpc_id
  public_subnet_ids = module.network.public_subnet_ids
  desired_count     = var.compute_backend == "ecs" ? 1 : 0
}

module "lambda_bref" {
  count  = var.compute_backend == "lambda" ? 1 : 0
  source = "../../modules/lambda_bref"

  vpc_id                      = module.network.vpc_id
  private_subnet_ids          = module.network.private_subnet_ids
  cloudfront_distribution_arn = var.cloudfront_distribution_arn
}

module "scheduler" {
  count  = var.compute_backend == "lambda" ? 1 : 0
  source = "../../modules/scheduler"
}

output "compute_backend" {
  value = var.compute_backend
}

output "api_origin_domain" {
  description = "CloudFrontの switch_origin.sh に渡すオリジンドメイン（lambdaの場合のみ意味を持つ）"
  value       = var.compute_backend == "lambda" && length(module.lambda_bref) > 0 ? module.lambda_bref[0].function_url_domain : (length(module.ecs_service) > 0 ? module.ecs_service[0].alb_dns_name : null)
}

output "lambda_oac_id" {
  description = "switch_origin.sh 実行時に OAC_ID として渡す（compute_backend=lambdaの場合のみ値を持つ）"
  value       = length(module.lambda_bref) > 0 ? module.lambda_bref[0].lambda_oac_id : null
}
