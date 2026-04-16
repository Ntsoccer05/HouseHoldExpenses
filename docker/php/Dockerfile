FROM php:8.2-fpm

# COPY php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# Composer install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update \
  # zlibはデータの圧縮に関するアルゴリズムをライブラリ化したもの
  # vimをインストール
  # MariaDBデータベースのクライアントアプリケーションである「mysql」を使えるように「mariadb-client」をインストール
  # libzip-devはzip圧縮時に必要となるライブラリ(libzip は、zlib を使用する)
  && apt-get install -y zlib1g-dev mariadb-client vim libzip-dev libgmp-dev libgd-dev libicu-dev \
  # docker-php-ext-installはPHPに標準で備わっている拡張パッケージのインストール＆有効化ができるコマンド
  # PDO_MYSQL は、PHP から MySQL データベースへのアクセスを可能にするための PHP Data Objects (PDO) インターフェイス を実装したドライバ。
  && docker-php-ext-install zip pdo_mysql gmp gd \
  # requires the Intl extension対応 libicu-devもinstall
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl

# プロキシ環境下でビルドする場合に指定する(LinuxじゃなくWindows下)
ARG http_proxy
ARG https_proxy
# nodeインストール(aptだとNodejsは古いため、パッケージマネージャの n を使用してインストール)
RUN apt-get install -y npm \
  && npm install n -g \
  && n stable

# XDebugをビルド時にインストール（起動時のpecl installを不要にする）
# ローカル開発時のみ: docker-compose build 時に INSTALL_XDEBUG=true を渡す
# 本番ビルド時はデフォルト false のままにすることでイメージに含めない
ARG INSTALL_XDEBUG=false
RUN if [ "${INSTALL_XDEBUG}" = "true" ]; then \
    pecl install xdebug-3.3.2 \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.mode=off" > /usr/local/etc/php/conf.d/xdebug.ini; \
  fi

#docker-compose exec app bashというコマンドでappコンテナの中に入った場合、/var/www/htmlがカレントディレクトリになる
WORKDIR /var/www/html

# Composerの依存関係をビルド時にインストール
# vendor/ は named volume (docker-compose.yml) にマウントされるため、ソースの bind mount で上書きされない
COPY ./src/composer.json ./src/composer.lock ./
RUN composer install --optimize-autoloader --no-scripts --no-interaction

# Entrypoint
COPY ./docker/php/entrypoint.sh /usr/local/bin/docker-php-entrypoint
RUN chmod +x /usr/local/bin/docker-php-entrypoint
