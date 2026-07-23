FROM node:26-alpine AS node-deps
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY vite.config.js ./
COPY public public
RUN npm run build

FROM php:8.4-fpm-alpine
WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN install-php-extensions pdo_mysql redis pcntl opcache calendar gmp intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN apk add --no-cache git unzip \
    && composer install --no-dev --optimize-autoloader --no-scripts --no-interaction \
    && apk del git unzip

# Copy source. The subsequent copies from node-deps intentionally overwrite
# public/build and public/static because generated assets are gitignored and
# therefore not present in the build context from COPY . .
COPY . .
COPY --from=node-deps /var/www/html/public/build public/build
COPY --from=node-deps /var/www/html/public/static public/static

RUN mkdir -p bootstrap/cache storage/framework/sessions storage/framework/views storage/framework/cache storage/logs storage/app/public \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan package:discover --ansi \
    && rm .env

RUN chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www-data

EXPOSE 9000

ARG APP_VERSION=dev
ARG APP_PR_NUMBER=
ARG APP_BRANCH=

ENV APP_VERSION=$APP_VERSION
ENV APP_PR_NUMBER=$APP_PR_NUMBER
ENV APP_BRANCH=$APP_BRANCH

LABEL org.opencontainers.image.version=$APP_VERSION \
      org.opencontainers.image.revision=$APP_PR_NUMBER \
      org.opencontainers.image.ref.name=$APP_BRANCH

ENTRYPOINT ["/entrypoint.sh"]
