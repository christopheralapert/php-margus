# syntax=docker/dockerfile:1
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
COPY app ./app
COPY database ./database
# Extensions (gd, intl, ...) are compiled in the runtime stage, so skip the platform check here;
# `composer check-platform-reqs` runs against the real runtime PHP further down.
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts --ignore-platform-reqs

FROM php:8.4-apache-bookworm AS runtime
WORKDIR /var/www/html
RUN apt-get update \
    && apt-get install -y --no-install-recommends libfreetype6 libjpeg62-turbo libpng16-16 libicu72 libzip4 libsqlite3-0 libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev libzip-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite gd intl zip bcmath opcache \
    && php -r 'foreach (["pdo_sqlite", "gd", "intl", "zip", "bcmath", "Zend OPcache", "mbstring", "fileinfo", "iconv", "ctype", "tokenizer", "dom", "xml"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Missing extension: $ext\n"); exit(1); } }' \
    && a2enmod rewrite headers \
    && apt-get purge -y --auto-remove libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev libzip-dev libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' 'memory_limit=256M' 'max_execution_time=60' 'expose_php=Off' 'opcache.enable=1' 'display_errors=Off' > "$PHP_INI_DIR/conf.d/demo.ini"

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=vendor /usr/bin/composer /usr/local/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN composer check-platform-reqs --no-dev \
    && rm /usr/local/bin/composer \
    && rm -f bootstrap/cache/*.php \
    && mkdir -p storage/framework/cache/data storage/framework/cache/mpdf storage/framework/sessions storage/framework/views storage/logs \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache /etc/apache2 /var/run/apache2 /var/lock/apache2 \
    && chmod +x docker/start.sh

ENV PORT=10000 APACHE_RUN_DIR=/var/run/apache2 APACHE_LOCK_DIR=/var/lock/apache2 APACHE_PID_FILE=/var/run/apache2/apache2.pid
EXPOSE 10000
USER www-data
CMD ["bash", "docker/start.sh"]
