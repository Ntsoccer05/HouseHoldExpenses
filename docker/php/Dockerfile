FROM php:8.2-fpm

# vite用のポート番号
EXPOSE 5173

# COPY php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# Composer install
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update \
  # zlibはデータの圧縮に関するアルゴリズムをライブラリ化したもの
  # vimをインストール
  # MariaDBデータベースのクライアントアプリケーションである「mysql」を使えるように「mariadb-client」をインストール
  # libzip-devはzip圧縮時に必要となるライブラリ(libzip は、zlib を使用する)
  && apt-get install -y zlib1g-dev mariadb-client vim libzip-dev libgmp-dev libgd-dev \
  # docker-php-ext-installはPHPに標準で備わっている拡張パッケージのインストール＆有効化ができるコマンド
  # PDO_MYSQL は、PHP から MySQL データベースへのアクセスを可能にするための PHP Data Objects (PDO) インターフェイス を実装したドライバ。
  && docker-php-ext-install zip pdo_mysql gmp gd

# プロキシ環境下でビルドする場合に指定する(LinuxじゃなくWindows下)
ARG http_proxy
ARG https_proxy
# nodeインストール(aptだとNodejsは古いため、パッケージマネージャの n を使用してインストール)
RUN apt-get install -y npm \
  && npm install n -g \
  && n stable

#docker-compose exec app bashというコマンドでappコンテナの中に入った場合、/var/www/htmlがカレントディレクトリになる
WORKDIR /var/www/html