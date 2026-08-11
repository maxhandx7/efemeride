# ---- 1. Dependencias PHP ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .

# package:discover arranca Laravel durante el build. Si algo intenta tocar la base,
# necesita encontrar el archivo donde DB_DATABASE dice que esta. Cinturon y tirantes.
RUN mkdir -p /var/www/html/storage/app/database \
    && touch /var/www/html/storage/app/database/database.sqlite \
    && composer dump-autoload --optimize --no-dev

# ---- 2. Imagen final: nginx + php-fpm listos para Coolify ----
FROM serversideup/php:8.3-fpm-nginx

ENV PHP_OPCACHE_ENABLE=1
ENV AUTORUN_ENABLED=true
ENV AUTORUN_LARAVEL_MIGRATION=true
ENV AUTORUN_LARAVEL_CONFIG_CACHE=true
ENV AUTORUN_LARAVEL_ROUTE_CACHE=true
ENV AUTORUN_LARAVEL_VIEW_CACHE=true
ENV AUTORUN_LARAVEL_STORAGE_LINK=true

USER root

RUN install-php-extensions intl pcntl \
    && apt-get update \
    && apt-get install -y --no-install-recommends curl \
    && rm -rf /var/lib/apt/lists/*

# El volumen se monta ENCIMA de storage/, asi que el archivo que creamos durante el
# build desaparece al arrancar. Este script lo recrea antes de que corran las migraciones.
# Va escrito aqui dentro a proposito: un COPY dependeria de que el archivo llegue al
# repo, y ya sabemos como termina eso.
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'DB_FILE="${DB_DATABASE:-/var/www/html/storage/app/database/database.sqlite}"' \
    'case "$DB_FILE" in' \
    '  *.sqlite)' \
    '    mkdir -p "$(dirname "$DB_FILE")"' \
    '    [ -f "$DB_FILE" ] || { touch "$DB_FILE"; echo "[efemeride] Base SQLite creada en $DB_FILE"; }' \
    '    chown www-data:www-data "$DB_FILE" "$(dirname "$DB_FILE")" 2>/dev/null || true' \
    '    ;;' \
    'esac' \
    'mkdir -p /var/www/html/storage/framework/cache/data \' \
    '         /var/www/html/storage/framework/sessions \' \
    '         /var/www/html/storage/framework/views \' \
    '         /var/www/html/storage/logs \' \
    '         /var/www/html/storage/app/public' \
    'chown -R www-data:www-data /var/www/html/storage 2>/dev/null || true' \
    > /etc/entrypoint.d/10-sqlite.sh \
    && chmod +x /etc/entrypoint.d/10-sqlite.sh \
    && sh -n /etc/entrypoint.d/10-sqlite.sh

USER www-data

COPY --chown=www-data:www-data --from=vendor /app /var/www/html

EXPOSE 8080
