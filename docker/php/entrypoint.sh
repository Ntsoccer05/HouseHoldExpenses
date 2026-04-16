#!/bin/bash
WORK_DIR="/var/www/html"
cd $WORK_DIR
echo "Setting permissions..."
# Laravelが書き込みを必要とするディレクトリのみ対象（全体への-Rは不要かつ遅い）
chmod -R 777 ${WORK_DIR}/storage ${WORK_DIR}/bootstrap/cache

# Environment variables
ENV_FILE_PATH=${WORK_DIR}/.env

if [ "${APP_ENV}" = "local" ]; then
  echo "[Local] environment"
else
  echo "[Production] Install library"
  cp /var/tmp/env_data ${ENV_FILE_PATH}
  # 本番環境ではビルド時にvendorがないためcomposer installを実行
  composer install --optimize-autoloader --working-dir=${WORK_DIR}
fi

#===== XDebug 設定（インストール済み、モードのみ切り替え）=====#
if [ "${APP_DEBUG}" = "true" ]; then
    echo "XDebug: debug mode enabled (trigger mode - no overhead on normal requests)"
    echo "xdebug.mode=debug
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
xdebug.idekey=VSCODE
xdebug.start_with_request=trigger
xdebug.log=/tmp/xdebug.log
xdebug.discover_client_host=1" > /usr/local/etc/php/conf.d/xdebug.ini
    touch /tmp/xdebug.log && chmod 777 /tmp/xdebug.log
else
    echo "XDebug: off"
    echo "xdebug.mode=off" > /usr/local/etc/php/conf.d/xdebug.ini
fi

# set permission for laravel folder
chown -R www-data:www-data ${WORK_DIR}/storage ${WORK_DIR}/bootstrap/cache

# DB Migration
if [ "${APP_ENV}" = "local" ]; then
  echo "run migration"
  php artisan migrate --force
fi

exec "$@"
