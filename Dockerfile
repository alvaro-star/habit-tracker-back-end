# syntax=docker/dockerfile:1

FROM php:8.4-fpm-alpine

# Runtime libs + build deps para las extensiones PHP que necesita la app.
RUN apk add --no-cache \
        libpq \
        oniguruma \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        postgresql-dev \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        mbstring \
        zip \
        gd \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && apk add --no-cache git unzip nginx

# Nginx sirve public/ y reenvía los .php al php-fpm de este mismo container.
COPY nginx.conf /etc/nginx/http.d/default.conf

# Composer (de la imagen oficial).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Primero solo lo necesario para instalar dependencias → mejor cache.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction --prefer-dist

# Ahora el resto del código.
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

# php-fpm queda en segundo plano y nginx en primer plano como proceso principal.
CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]
