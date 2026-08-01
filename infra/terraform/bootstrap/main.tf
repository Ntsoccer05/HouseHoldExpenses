##############################################################################
# bootstrap
#
# 初回のみ・手動で1回だけ apply する層。ローカルstateで運用する
# （tfstate用S3バケット自体がまだ存在しない「卵が先か鶏が先か」問題のため）。
#
# 作成するもの:
#   - Terraform state用 S3バケット（バージョニング + 暗号化 + パブリックアクセスブロック）
#   - state ロック用 DynamoDB テーブル
#   - GitHub Actions OIDC プロバイダ
#   - GitHub Actions から assume する IAM ロール（Terraform apply 用 / Lambdaデプロイ用）
#
# 参考: serverless-migration-lessons-learned.md 3-17
#   「ローカルstateのみで運用する層は複数マシン/セッションをまたぐとドリフトしやすい」
#   → apply は必ず `aws sts get-caller-identity` で対象アカウントを確認してから、1人が実行すること。
##############################################################################

terraform {
  required_version = ">= 1.7.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

data "aws_caller_identity" "current" {}

# --- Terraform state用バックエンド ---------------------------------------

resource "aws_s3_bucket" "tfstate" {
  bucket = var.tfstate_bucket_name

  # 誤操作での削除防止。本当に削除する場合は先にこのブロックを外してapply
  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_s3_bucket_versioning" "tfstate" {
  bucket = aws_s3_bucket.tfstate.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "tfstate" {
  bucket = aws_s3_bucket.tfstate.id
  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "tfstate" {
  bucket                  = aws_s3_bucket.tfstate.id
  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_dynamodb_table" "tfstate_lock" {
  name         = var.tfstate_lock_table_name
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "LockID"

  attribute {
    name = "LockID"
    type = "S"
  }
}

# --- GitHub Actions OIDC ----------------------------------------------------

resource "aws_iam_openid_connect_provider" "github" {
  # NOTE: このOIDCプロバイダはAWSアカウントに1つしか存在できず、実際に既に他プロジェクト用に
  # 作成済みだった(import済み)。thumbprintは実際の値に合わせてあり、他プロジェクトの
  # IAMロールへの影響を避けるため、このTerraformから値を変更しない。
  url             = "https://token.actions.githubusercontent.com"
  client_id_list  = ["sts.amazonaws.com"]
  thumbprint_list = ["d89e3bd43d5d909b47a18977aa9d5ce36cee184c"]
}

data "aws_iam_policy_document" "github_oidc_assume" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = [aws_iam_openid_connect_provider.github.arn]
    }

    condition {
      test     = "StringEquals"
      variable = "token.actions.githubusercontent.com:aud"
      values   = ["sts.amazonaws.com"]
    }

    condition {
      test     = "StringLike"
      variable = "token.actions.githubusercontent.com:sub"
      values   = ["repo:${var.github_owner}/${var.github_repo}:*"]
    }
  }
}

resource "aws_iam_role" "github_actions_deploy" {
  name               = "household-github-actions-deploy"
  assume_role_policy = data.aws_iam_policy_document.github_oidc_assume.json
}

# CI用ロールの権限は「実際にplan/applyを一度フルで通してから過不足を調整する」前提
# (serverless-migration-lessons-learned.md 3-6, 4章チェックリスト) で、
# 初回は広めのAWS管理ポリシーを付与し、安定後に最小権限へ絞り込むことを推奨する。
resource "aws_iam_role_policy_attachment" "deploy_admin_scoped" {
  # NOTE: 本番運用に入れる前に、必ずこのAdministratorAccessは
  # 必要なサービス（Lambda, ECS, ECR, S3, CloudFront, RDS(Describe系のみ), IAM PassRole, EventBridge Scheduler等）
  # に絞ったカスタムポリシーへ置き換えること。初回セットアップの詰まりを避けるため一旦Admin相当にしている。
  role       = aws_iam_role.github_actions_deploy.name
  policy_arn = "arn:aws:iam::aws:policy/AdministratorAccess"
}
