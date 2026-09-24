FROM dunglas/frankenphp:1-php8.5-bookworm AS php

RUN install-php-extensions pdo_pgsql pgsql intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY . .

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && chmod -R ug+rwX storage bootstrap/cache

FROM node:24-bookworm-slim AS assets

WORKDIR /app
COPY --from=php /app /app
RUN npm ci && npm run build

FROM php AS application

COPY --from=assets /app/public/build /app/public/build

EXPOSE 10000
CMD ["/bin/sh", "/app/render-start.sh"]
