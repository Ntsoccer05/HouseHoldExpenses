FROM php:8.2-fpm

# 作業ディレクトリ
WORKDIR /var/www/html

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    unzip \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libpq-dev \
    libjpeg-dev \
    libfreetype6-dev \
    mariadb-client \
    zlib1g-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_mysql zip intl gd opcache

# Composer のインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定ファイルコピー
COPY ./docker/production/default.conf /etc/nginx/conf.d/default.conf
COPY ./docker/production/php.ini /usr/local/etc/php/php.ini
COPY ./docker/production/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Laravel アプリケーションと一緒にコピー docker-compose.ymlのvolumesと一緒
COPY ./src /var/www/html

# ビルド時に渡される .env を保存
# 後に entrypoint.sh がこのファイルを /var/www/html/.env にコピー
RUN mkdir -p /var/tmp
COPY ./docker/production/.env.production /var/tmp/env_data

# Entrypoint スクリプトのコピーと実行権限付与
COPY ./docker/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Laravel パーミッション設定
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# Entrypoint → entrypoint.sh → 最終的に supervisord 起動
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
