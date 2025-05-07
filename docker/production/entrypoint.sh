#!/bin/bash

set -e

WORK_DIR="/var/www/html"
cd $WORK_DIR
chmod -R 777 ${WORK_DIR}/*

# .envのセット
ENV_FILE_PATH=${WORK_DIR}/.env
echo "[Production] Install library"
cp /var/tmp/env_data ${ENV_FILE_PATH}

# APP_ENV が production のときのみ Vite ビルド実行
# 追加リソースが必要となる
# echo "APP_ENV is production. Building frontend with npm..."
# cd $WORK_DIR
# npm ci
# npm run build

# Composer
composer install --optimize-autoloader --no-dev --working-dir=${WORK_DIR}

# Laravel のパーミッション
chown -R www-data:www-data ${WORK_DIR}

# DB マイグレーション
echo "Running migrations..."
php artisan migrate --force

# supervisord 起動
exec "$@"
