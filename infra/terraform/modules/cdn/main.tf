## CloudFrontディストリビューション本体は README.md に記載の設計判断により
## Terraform管理外としているが、そのオリジンとして使うS3バケットは
## 新規かつスコープが明確なリソースのため、ここでTerraform管理する。

resource "aws_s3_bucket" "static_assets" {
  bucket = var.static_assets_bucket_name
}

resource "aws_s3_bucket_public_access_block" "static_assets" {
  bucket = aws_s3_bucket.static_assets.id

  block_public_acls       = true
  ignore_public_acls      = true
  block_public_policy     = true
  restrict_public_buckets = true
}

resource "aws_cloudfront_origin_access_control" "static_assets" {
  name                              = "${var.static_assets_bucket_name}-oac"
  description                       = "OAC for backend static assets (filament css/js, vendor, livewire.js)"
  signing_protocol                  = "sigv4"
  signing_behavior                  = "always"
  origin_access_control_origin_type = "s3"
}

data "aws_iam_policy_document" "static_assets" {
  statement {
    sid    = "AllowCloudFrontServicePrincipal"
    effect = "Allow"

    principals {
      type        = "Service"
      identifiers = ["cloudfront.amazonaws.com"]
    }

    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.static_assets.arn}/*"]

    condition {
      test     = "StringEquals"
      variable = "AWS:SourceArn"
      values   = ["arn:aws:cloudfront::${var.aws_account_id}:distribution/${var.cloudfront_distribution_id}"]
    }
  }
}

resource "aws_s3_bucket_policy" "static_assets" {
  bucket = aws_s3_bucket.static_assets.id
  policy = data.aws_iam_policy_document.static_assets.json
}
