FROM node:26-alpine AS node-deps
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY bin/icons bin/icons
COPY vite.config.js ./
COPY public public

ARG APP_ENV=production
ARG APP_NAME=Docket
ARG APP_VERSION=dev
ARG APP_PR_NUMBER=
ARG APP_BRANCH=
ARG VITE_OTEL_EXPORTER_OTLP_ENDPOINT=

RUN VITE_APP_NAME=$APP_NAME VITE_APP_VERSION=$APP_VERSION VITE_APP_ENV=$APP_ENV VITE_APP_PR_NUMBER=$APP_PR_NUMBER VITE_APP_BRANCH=$APP_BRANCH VITE_OTEL_EXPORTER_OTLP_ENDPOINT=$VITE_OTEL_EXPORTER_OTLP_ENDPOINT npm run build

FROM php:8.5-fpm-alpine
WORKDIR /var/www/html

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/bin/install-php-extensions

RUN install-php-extensions pdo_mysql redis pcntl opcache calendar gmp intl opentelemetry

COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/opcache.ini

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
ARG APP_ENV=production
ARG APP_NAME=Docket

ENV APP_VERSION=$APP_VERSION
ENV APP_PR_NUMBER=$APP_PR_NUMBER
ENV APP_BRANCH=$APP_BRANCH

ENV OTEL_RESOURCE_ATTRIBUTES="service.version=$APP_VERSION,deployment.environment=$APP_ENV,service.name=$APP_NAME,service.revision=$APP_PR_NUMBER,service.branch=$APP_BRANCH"
ENV OTEL_PHP_AUTOLOAD_ENABLED=true

LABEL org.opencontainers.image.version=$APP_VERSION \
      org.opencontainers.image.revision=$APP_PR_NUMBER \
      org.opencontainers.image.ref.name=$APP_BRANCH

ENTRYPOINT ["/entrypoint.sh"]
