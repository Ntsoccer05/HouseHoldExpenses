##############################################################################
# ecs_service
#
# 現行ECS Fargate運用をそのままコード化したもの（ロールバック用）。
# コンソール実査(2026-08-01)で確認した実際の設定値を反映している:
#   - house-hold-app-cluster / house-hold-app-api-service-1a7svcgy
#   - タスク定義 house-hold-app-api:97 (CPU 256 / Mem 512, X86_64/Linux, awsvpc)
#   - ALB house-hold-app-alb (Internet-facing) → TG house-hold-app-ip-tg (HTTP:8080, IP type)
#   - ECR house-hold-api
#
# 初回は `terraform import` で既存リソースを取り込むこと（README参照）。
##############################################################################

data "aws_iam_role" "task_execution" {
  name = "ecsTaskExecutionRole"
}

data "aws_iam_role" "task_role" {
  name = "ecsTaskExecuteCommandRole"
}

data "aws_acm_certificate" "cert" {
  domain      = var.acm_certificate_domain
  statuses    = ["ISSUED"]
  most_recent = true
}

# --- ECR --------------------------------------------------------------------

resource "aws_ecr_repository" "api" {
  name                 = "house-hold-api"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }
}

resource "aws_ecr_lifecycle_policy" "api" {
  repository = aws_ecr_repository.api.name
  policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "直近20世代のみ保持"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 20
      }
      action = { type = "expire" }
    }]
  })
}

# --- CloudWatch Logs ----------------------------------------------------------

resource "aws_cloudwatch_log_group" "api" {
  name              = "/ecs/house-hold-app-api"
  retention_in_days = 30
}

# --- ECS Cluster / Task Definition / Service ---------------------------------

resource "aws_ecs_cluster" "this" {
  name = "house-hold-app-cluster"
}

resource "aws_ecs_task_definition" "api" {
  family                   = "house-hold-app-api"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = data.aws_iam_role.task_execution.arn
  task_role_arn            = data.aws_iam_role.task_role.arn
  runtime_platform {
    operating_system_family = "LINUX"
    cpu_architecture        = "X86_64"
  }

  container_definitions = jsonencode([
    {
      name      = "api"
      image     = var.container_image
      cpu       = 0
      essential = true
      portMappings = [{
        containerPort = 8080
        hostPort      = 8080
        protocol      = "tcp"
        name          = "api-8080-tcp"
        appProtocol   = "http"
      }]
      environment = [
        { name = "APP_DEBUG", value = "false" },
        { name = "APP_ENV", value = "production" },
      ]
      mountPoints = []
      volumesFrom = []
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.api.name
          "mode"                  = "non-blocking"
          "awslogs-create-group"  = "true"
          "max-buffer-size"       = "25m"
          "awslogs-region"        = "ap-northeast-1"
          "awslogs-stream-prefix" = "ecs"
        }
      }
    }
  ])

  lifecycle {
    # CIが `docker/production` のビルド成果物イメージで新しいタスク定義リビジョンを都度登録するため、
    # Terraformはcontainer_imageの初期値だけを持ち、以降の差分検知はしない
    # (lessons-learned 3-18と同種の事故を避けるため、image自体はTerraformで能動更新しない方針)
    ignore_changes = [container_definitions]
  }
}

resource "aws_ecs_service" "api" {
  name                              = "house-hold-app-api-service-1a7svcgy"
  cluster                           = aws_ecs_cluster.this.id
  task_definition                   = aws_ecs_task_definition.api.arn
  desired_count                     = var.desired_count
  launch_type                       = "FARGATE"
  scheduling_strategy               = "REPLICA"
  health_check_grace_period_seconds = 0
  enable_execute_command            = true

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [var.security_group_id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.api.arn
    container_name   = "api"
    container_port   = 8080
  }

  lifecycle {
    # desired_count はスケジューラLambda/compute_backend切替が能動的に変更するため、
    # Terraform planのたびに差分として出て邪魔にならないよう無視する
    ignore_changes = [desired_count, task_definition]
  }

  depends_on = [aws_lb_listener.https, aws_lb_listener.http_8080]
}

# --- ALB / Target Group / Listeners ------------------------------------------

resource "aws_lb" "this" {
  name               = "house-hold-app-alb"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [var.security_group_id]
  subnets            = var.public_subnet_ids
}

resource "aws_lb_target_group" "api" {
  name        = "house-hold-app-ip-tg"
  port        = 8080
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "ip"

  health_check {
    # NOTE: パスは /api/health を推測で設定。import前に実際のターゲットグループ設定と
    # 突き合わせて修正すること（コンソールで未確認）
    path                = "/api/health"
    port                = "traffic-port"
    healthy_threshold   = 3
    unhealthy_threshold = 3
    interval            = 30
    timeout             = 5
    matcher             = "200"
  }
}

resource "aws_lb_listener" "http_8080" {
  load_balancer_arn = aws_lb.this.arn
  port              = 8080
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.api.arn
  }
}

resource "aws_lb_listener" "https" {
  load_balancer_arn = aws_lb.this.arn
  port              = 443
  protocol          = "HTTPS"
  ssl_policy        = "ELBSecurityPolicy-TLS13-1-2-2021-06"
  certificate_arn   = data.aws_acm_certificate.cert.arn

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.api.arn
  }
}
