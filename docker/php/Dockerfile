FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        msmtp \
        libzip-dev \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/log/php \
    && chown www-data:www-data /var/log/php

WORKDIR /var/www/authserver
