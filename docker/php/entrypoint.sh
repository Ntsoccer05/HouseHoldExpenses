#!/bin/bash
WORK_DIR="/var/www/html"
cd $WORK_DIR
chmod -R 777 ${WORK_DIR}/*

# Environment variables
ENV_FILE_PATH=${WORK_DIR}/.env

# php environment for stage
if [ "${APP_ENV}" = "local" ]; then 
  echo "[Local] Install library"
  # cp "${WORK_DIR}/.env.example" ${ENV_FILE_PATH}
else
  echo "[Production] Install library"
  cp /var/tmp/env_data ${ENV_FILE_PATH}
fi

# Run Composer
composer install --optimize-autoloader --working-dir=${WORK_DIR}

#===== Install XDebug =====#
if [ "${APP_DEBUG}" = "true"  ] &&
   [ ! -e "/usr/local/etc/php/conf.d/xdebug.ini" ] ; then
    echo "xdebug install";

    pecl install -f xdebug-3.3.2 && docker-php-ext-enable xdebug
    echo "
        xdebug.mode=debug
        xdebug.client_host=host.docker.internal
        xdebug.client_port=9003
        xdebug.idekey=VSCODE
        xdebug.start_with_request=yes
        xdebug.log=/tmp/xdebug.log
        xdebug.discover_client_host=1
        " > /usr/local/etc/php/conf.d/xdebug.ini;

        if [ ! -e "/tmp/xdebug.log" ] ; then
          touch /tmp/xdebug.log
        fi
        
        chmod 777 /tmp/xdebug.log
fi


# set permission for laravel folder
chown www-data:www-data -R ${WORK_DIR}

# DB Migration
if [ "${APP_ENV}" = "local" ]; then
  echo "run migration"
  php artisan migrate --force

  USER_EXISTS=$(php artisan tinker --execute "echo \App\Models\User::where('email', '${FILAMENT_ADMIN_EMAIL}')->exists();")
  if [ "$USER_EXISTS" = "false" ]; then
    echo "Creating Filament admin user..."
    php artisan make:filament-user \
      --name="${FILAMENT_ADMIN_NAME}" \
      --email="${FILAMENT_ADMIN_EMAIL}" \
      --password="${FILAMENT_ADMIN_PASSWORD}" \
      --no-interaction
  else
    echo "Filament admin user already exists."
  fi
fi

exec "$@"