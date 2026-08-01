#!/usr/bin/env bash
# switch_origin.sh
#
# CloudFrontディストリビューション(E11UZMEXBMM184)の「API系オリジン」1個だけを
# ALB ⇔ Lambda(API Gateway経由) の間で安全に切り替える。
# 対象オリジン以外(S3オリジン群、他のordered_cache_behavior、証明書設定等)は一切変更しない。
#
# 【重要】2026-08-01時点、本番は"lambda"モード稼働中で、実際のオリジンは
# Lambda Function URLではなくAPI Gateway HTTP APIのドメイン
# (例: xxxxxxxxxx.execute-api.ap-northeast-1.amazonaws.com)。
# Function URL + OACはAccessDeniedExceptionが解消できず断念した経緯がある
# （詳細はmodules/cdn/README.md参照）。lambdaモードの引数にはAPI Gatewayの
# ドメインを渡すこと。またAPI Gatewayへ切り替える場合、対象ビヘイビアの
# キャッシュポリシーがHostヘッダーをオリジン転送する設定のままだと
# API Gateway側のHostヘッダー検証で403になるため、README.mdの手順も参照。
#
# 使い方:
#   ./switch_origin.sh ecs                                    # ALBに戻す(ロールバック)
#   ./switch_origin.sh lambda <api-gateway-domain>             # API Gatewayへ切替
#
# 前提: aws cli, python3 がインストール済み。対象アカウントの認証情報が設定済み。
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

# Git Bash(MSYS)のパスをWindowsネイティブのaws.exeが解釈できる形式に変換する
if command -v cygpath >/dev/null 2>&1; then
  work_dir_win="$(cygpath -m "$work_dir")"
else
  work_dir_win="$work_dir"
fi

echo "==> Fetching current distribution config"
aws cloudfront get-distribution-config --id "$DIST_ID" > "$work_dir/current.json"

if [[ "$mode" == "ecs" ]]; then
  echo "==> Switching origin back to ALB ($ALB_DOMAIN:8080, HTTP)"
  MODE=ecs ORIGIN_ID="$ORIGIN_ID" ALB_DOMAIN="$ALB_DOMAIN" WORK_DIR="$work_dir" python3 - <<'PYEOF'
import json, os

work_dir = os.environ["WORK_DIR"]
origin_id = os.environ["ORIGIN_ID"]
alb_domain = os.environ["ALB_DOMAIN"]

with open(f"{work_dir}/current.json", encoding="utf-8") as f:
    current = json.load(f)

etag = current["ETag"]
config = current["DistributionConfig"]

for origin in config["Origins"]["Items"]:
    if origin["Id"] == origin_id:
        origin["DomainName"] = alb_domain
        origin["CustomOriginConfig"]["OriginProtocolPolicy"] = "http-only"
        origin["CustomOriginConfig"]["HTTPPort"] = 8080
        origin.pop("OriginAccessControlId", None)

with open(f"{work_dir}/new-config.json", "w", encoding="utf-8") as f:
    json.dump(config, f)

with open(f"{work_dir}/etag.txt", "w", encoding="utf-8") as f:
    f.write(etag)

with open(f"{work_dir}/before-origins.json", "w", encoding="utf-8") as f:
    json.dump(current["DistributionConfig"]["Origins"], f, indent=2, ensure_ascii=False)

with open(f"{work_dir}/after-origins.json", "w", encoding="utf-8") as f:
    json.dump(config["Origins"], f, indent=2, ensure_ascii=False)
PYEOF
else
  fn_url_domain="${2:?function-url-domain is required for lambda mode}"
  echo "==> Switching origin to Lambda Function URL ($fn_url_domain, HTTPS)"
  echo "    NOTE: Origin Access Control(type=lambda)は事前に作成し、そのIDを"
  echo "    OAC_ID環境変数で渡すこと（未設定の場合はAWS_IAM認証なしのURLを想定）"
  MODE=lambda ORIGIN_ID="$ORIGIN_ID" FN_URL_DOMAIN="$fn_url_domain" OAC_ID="${OAC_ID:-}" WORK_DIR="$work_dir" python3 - <<'PYEOF'
import json, os

work_dir = os.environ["WORK_DIR"]
origin_id = os.environ["ORIGIN_ID"]
fn_url_domain = os.environ["FN_URL_DOMAIN"]
oac_id = os.environ.get("OAC_ID", "")

with open(f"{work_dir}/current.json", encoding="utf-8") as f:
    current = json.load(f)

etag = current["ETag"]
config = current["DistributionConfig"]

for origin in config["Origins"]["Items"]:
    if origin["Id"] == origin_id:
        origin["DomainName"] = fn_url_domain
        origin["CustomOriginConfig"]["OriginProtocolPolicy"] = "https-only"
        origin["CustomOriginConfig"]["HTTPSPort"] = 443
        if oac_id:
            origin["OriginAccessControlId"] = oac_id

with open(f"{work_dir}/new-config.json", "w", encoding="utf-8") as f:
    json.dump(config, f)

with open(f"{work_dir}/etag.txt", "w", encoding="utf-8") as f:
    f.write(etag)

with open(f"{work_dir}/before-origins.json", "w", encoding="utf-8") as f:
    json.dump(current["DistributionConfig"]["Origins"], f, indent=2, ensure_ascii=False)

with open(f"{work_dir}/after-origins.json", "w", encoding="utf-8") as f:
    json.dump(config["Origins"], f, indent=2, ensure_ascii=False)
PYEOF
fi

etag="$(cat "$work_dir/etag.txt")"

echo "==> Diff preview (origins only)"
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
  --distribution-config "file://$work_dir_win/new-config.json"

echo "==> Waiting for deployment to complete (this can take several minutes)"
aws cloudfront wait distribution-deployed --id "$DIST_ID"

echo "==> Done. Verify with:"
echo "    curl -I https://kake-pon.com/api/health"
