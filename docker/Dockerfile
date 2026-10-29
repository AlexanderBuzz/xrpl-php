FROM php:8.2.35-fpm-alpine

RUN apk update  \
    && apk upgrade \
    && apk add gmp-dev \
    && apk add --no-cache bash $PHPIZE_DEPS linux-headers \
    && docker-php-ext-install gmp \
    && docker-php-ext-install sockets \
    && pecl install xdebug \
    && pecl install ds

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app
