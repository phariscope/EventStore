FROM php:8.2.10-fpm

# Installation de l'extension pecl pcov
RUN pecl install pcov \
    && docker-php-ext-enable pcov

COPY ./php.ini $PHP_INI_DIR/php.ini

WORKDIR /usr/src