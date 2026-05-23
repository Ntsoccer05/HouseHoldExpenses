#!/bin/bash
set -e

# .env.scheduler から値を読み込む
ENV_FILE="$(dirname "$0")/../../.env.scheduler"

if [ ! -f "$ENV_FILE" ]; then
  echo "Error: .env.scheduler が見つかりません。"
  echo ".env.scheduler.example をコピーして作成してください:"
  echo "  cp .env.scheduler.example .env.scheduler"
  exit 1
fi

source "$ENV_FILE"

# Windows環境でAWS CLIがUTF-8ファイルを読めるよう設定
export PYTHONUTF8=1
export PYTHONIOENCODING=utf-8

# 必須変数チェック
: "${ECS_CLUSTER:?ECS_CLUSTER が未設定です}"
: "${ECS_SERVICE:?ECS_SERVICE が未設定です}"
: "${RDS_INSTANCE_ID:?RDS_INSTANCE_ID が未設定です}"
: "${AWS_REGION:?AWS_REGION が未設定です}"

echo "デプロイ設定:"
echo "  ECS Cluster:    $ECS_CLUSTER"
echo "  ECS Service:    $ECS_SERVICE"
echo "  RDS Instance:   $RDS_INSTANCE_ID"
echo "  Region:         $AWS_REGION"
echo ""

aws cloudformation deploy \
  --template-file "$(dirname "$0")/cloudformation.yml" \
  --stack-name household-scheduler \
  --capabilities CAPABILITY_NAMED_IAM \
  --parameter-overrides \
    EcsCluster="$ECS_CLUSTER" \
    EcsService="$ECS_SERVICE" \
    RdsInstanceId="$RDS_INSTANCE_ID" \
  --region "$AWS_REGION"

echo ""
echo "デプロイ完了しました。"
