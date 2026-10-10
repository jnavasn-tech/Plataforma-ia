# ---------------------------------------------------------------------------
# Imágenes de la plataforma. Etapas: 1) dependencias PHP con Composer, 2) recursos del frontend con Vite,
# 3) aplicacion (PHP-FPM, backend), 4) web (Nginx, frontend) y 5) pruebas (PHPUnit).
# Para usar otra versión de PHP compatible (8.2 u 8.3): --build-arg PHP_VERSION=8.3
# ---------------------------------------------------------------------------
ARG PHP_VERSION=8.1

FROM composer:2 AS dependencias
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund || npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM php:${PHP_VERSION}-fpm-alpine AS aplicacion
WORKDIR /var/www/html

RUN apk add --no-cache icu-libs libzip libpng libjpeg-turbo freetype libxml2 \
    && apk add --no-cache --virtual .compilacion $PHPIZE_DEPS icu-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl zip gd bcmath opcache \
    && apk del .compilacion

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-plataforma.ini
COPY --from=dependencias --chown=www-data:www-data /app /var/www/html
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]

# Contenedor "web" (frontend): Nginx con los archivos públicos y los recursos compilados por Vite.
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=dependencias /app/public /var/www/html/public
COPY --from=frontend /app/public/build /var/www/html/public/build

# Imagen de pruebas automatizadas: agrega dependencias de desarrollo (PHPUnit) sobre la imagen de la aplicación.
FROM aplicacion AS pruebas
USER root
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --prefer-dist --no-progress \
    && chown -R www-data:www-data /var/www/html
USER www-data
CMD ["php", "vendor/bin/phpunit", "--log-junit", "storage/logs/junit.xml", "--testdox-text", "storage/logs/testdox.txt"]
