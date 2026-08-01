variable "static_assets_bucket_name" {
  description = "Filament/Livewireの静的アセット(css/js/vendor)を配信するS3バケット名"
  type        = string
  default     = "house-hold-api-static-assets"
}

variable "aws_account_id" {
  type = string
}

variable "cloudfront_distribution_id" {
  description = "既存の本番CloudFrontディストリビューションID(E11UZMEXBMM184)。バケットポリシーのSourceArn条件にのみ使用する"
  type        = string
}
