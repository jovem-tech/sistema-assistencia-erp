FROM php:8.2-apache

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod expires headers remoteip rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --prefer-dist

COPY . .

COPY docker/runtime/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/runtime/php.ini /usr/local/etc/php/conf.d/zz-sistema.ini
COPY docker/runtime/entrypoint.sh /usr/local/bin/sistema-entrypoint

RUN chmod +x /usr/local/bin/sistema-entrypoint \
    && mkdir -p \
        public/uploads \
        writable/cache \
        writable/debugbar \
        writable/logs \
        writable/session \
        writable/uploads \
    && chown -R www-data:www-data public/uploads writable

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -fsS http://127.0.0.1/login || exit 1

ENTRYPOINT ["sistema-entrypoint"]
CMD ["apache2-foreground"]

