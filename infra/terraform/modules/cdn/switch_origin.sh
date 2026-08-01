#!/usr/bin/env bash
# switch_origin.sh
#
# CloudFrontディストリビューション(E11UZMEXBMM184)の「API系オリジン」1個だけを
# ALB ⇔ Lambda Function URL の間で安全に切り替える。
# 対象オリジン以外(2つのS3オリジン、10個のordered_cache_behavior、証明書設定等)は一切変更しない。
#
# 使い方:
#   ./switch_origin.sh ecs                                    # ALBに戻す(ロールバック)
#   ./switch_origin.sh lambda <function-url-domain>            # Lambda Function URLへ切替
#
# 前提: aws cli, jq がインストール済み。対象アカウントの認証情報が設定済み。
set -euo pipefail

DIST_ID="E11UZMEXBMM184"
ORIGIN_ID="house-hold-app-alb-1263701531.ap-northeast-1.elb.amazonaws.com"
ALB_DOMAIN="house-hold-app-alb-1263701531.ap-northeast-1.elb.amazonaws.com"

mode="${1:-}"
if [[ "$mode" != "ecs" && "$mode" != "lambda" ]]; then
  echo "Usage: $0 ecs | lambda <function-url-domain>" >&2
  exit 1
fi

work_dir="$(mktemp -d)"
trap 'rm -rf "$work_dir"' EXIT

echo "==> Fetching current distribution config"
aws cloudfront get-distribution-config --id "$DIST_ID" > "$work_dir/current.json"
etag="$(jq -r '.ETag' "$work_dir/current.json")"

if [[ "$mode" == "ecs" ]]; then
  echo "==> Switching origin back to ALB ($ALB_DOMAIN:8080, HTTP)"
  jq --arg id "$ORIGIN_ID" --arg domain "$ALB_DOMAIN" '
    .DistributionConfig.Origins.Items |= map(
      if .Id == $id then
        .DomainName = $domain
        | .CustomOriginConfig.OriginProtocolPolicy = "http-only"
        | .CustomOriginConfig.HTTPPort = 8080
        | del(.OriginAccessControlId)
      else . end
    )
  ' "$work_dir/current.json" | jq '.DistributionConfig' > "$work_dir/new-config.json"
else
  fn_url_domain="${2:?function-url-domain is required for lambda mode}"
  echo "==> Switching origin to Lambda Function URL ($fn_url_domain, HTTPS)"
  echo "    NOTE: Origin Access Control(type=lambda)は事前に作成し、そのIDを"
  echo "    OAC_ID環境変数で渡すこと（未設定の場合はAWS_IAM認証なしのURLを想定）"
  oac_id="${OAC_ID:-}"
  jq --arg id "$ORIGIN_ID" --arg domain "$fn_url_domain" --arg oac "$oac_id" '
    .DistributionConfig.Origins.Items |= map(
      if .Id == $id then
        .DomainName = $domain
        | .CustomOriginConfig.OriginProtocolPolicy = "https-only"
        | .CustomOriginConfig.HTTPSPort = 443
        | (if $oac != "" then .OriginAccessControlId = $oac else . end)
      else . end
    )
  ' "$work_dir/current.json" | jq '.DistributionConfig' > "$work_dir/new-config.json"
fi

echo "==> Diff preview (origins only)"
jq '.DistributionConfig.Origins' "$work_dir/current.json" > "$work_dir/before-origins.json"
jq '.Origins' "$work_dir/new-config.json" > "$work_dir/after-origins.json"
diff "$work_dir/before-origins.json" "$work_dir/after-origins.json" || true

read -r -p "上記の差分でCloudFrontを更新します。よろしいですか？ [y/N] " confirm
if [[ "$confirm" != "y" ]]; then
  echo "中止しました"
  exit 1
fi

echo "==> Updating distribution"
aws cloudfront update-distribution \
  --id "$DIST_ID" \
  --if-match "$etag" \
  --distribution-config "file://$work_dir/new-config.json"

echo "==> Waiting for deployment to complete (this can take several minutes)"
aws cloudfront wait distribution-deployed --id "$DIST_ID"

echo "==> Done. Verify with:"
echo "    curl -I https://kake-pon.com/api/health"
