FROM composer:2.5.5

# Install necessary dependencies
RUN apk update \
    && apk add --no-cache \
       git \
       unzip \
       rabbitmq-c-dev \
       g++ \
       make \
       autoconf

# Install the PHP AMQP extension
RUN pecl install amqp \
    && docker-php-ext-enable amqp

WORKDIR /usr/src



