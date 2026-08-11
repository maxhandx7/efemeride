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

# Se ejecuta al arrancar el contenedor, antes de las migraciones
COPY --chmod=755 docker/entrypoint.d/10-sqlite.sh /etc/entrypoint.d/10-sqlite.sh

USER www-data

COPY --chown=www-data:www-data --from=vendor /app /var/www/html

EXPOSE 8080
