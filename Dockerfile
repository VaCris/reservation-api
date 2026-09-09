FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && apt-get clean

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

RUN mkdir -p var/cache var/log && chmod -R 777 var

RUN APP_ENV=prod php bin/console cache:clear --no-warmup || true

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]