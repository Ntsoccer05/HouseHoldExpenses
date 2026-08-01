output "static_assets_bucket_domain" {
  value = "${aws_s3_bucket.static_assets.bucket}.s3.ap-northeast-1.amazonaws.com"
}

output "static_assets_oac_id" {
  value = aws_cloudfront_origin_access_control.static_assets.id
}
