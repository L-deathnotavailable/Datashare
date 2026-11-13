FROM composer:2 AS composer

FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install intl pdo_pgsql zip opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY php.ini /usr/local/etc/php/conf.d/datashare.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
