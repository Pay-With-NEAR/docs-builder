FROM php:8.1-alpine

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV BUILD_DIR=dist

COPY . /app
WORKDIR /app

RUN composer install

ENTRYPOINT ./bin/console.php app:build -t $BUILD_DIR