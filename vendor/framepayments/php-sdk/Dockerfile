FROM php:8.3-cli

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug && docker-php-ext-enable xdebug

WORKDIR /app

COPY . .

RUN composer update --no-dev --optimize-autoloader
RUN composer install --dev

CMD ["./vendor/bin/phpunit"]
