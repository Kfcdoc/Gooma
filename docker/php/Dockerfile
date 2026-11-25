# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Imagen del backend: PHP 8.4 + extensiones requeridas por Laravel y MongoDB
# ---------------------------------------------------------------------------
FROM php:8.4-cli-bookworm

# Instalador que resuelve dependencias del sistema para extensiones PHP.
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        mongodb \
        bcmath \
        zip \
        gd \
        intl \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuración de PHP pensada para desarrollo y reportes grandes
RUN printf '%s\n' \
        'memory_limit=512M' \
        'upload_max_filesize=20M' \
        'post_max_size=20M' \
        'date.timezone=America/Mexico_City' \
        > /usr/local/etc/php/conf.d/zz-goomaps.ini

WORKDIR /var/www/html

# Cachea dependencias: solo se reinstalan si cambian composer.json/lock
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-interaction \
        --prefer-dist \
        --no-scripts \
        --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-scripts

EXPOSE 8000

# El entrypoint (montado por docker compose) prepara .env, claves y arranca
# el servidor de desarrollo.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
