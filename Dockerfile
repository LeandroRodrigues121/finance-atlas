FROM php:8.4-cli

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev ca-certificates \
    && docker-php-ext-install pdo_mysql bcmath \
    && update-ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN php -v \
    && composer --version \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "if [ \"${RUN_MIGRATIONS_ON_STARTUP:-true}\" = \"true\" ]; then echo \"Running database migrations...\" && php artisan migrate --force; fi && echo \"Starting API server...\" && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
