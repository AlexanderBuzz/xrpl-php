FROM php:8.1.4-fpm-alpine

RUN apk update  \
    && apk upgrade \
    && apk add gmp-dev \
    && apk add --no-cache bash $PHPIZE_DEPS \
    && docker-php-ext-install gmp \
    && docker-php-ext-install sockets \
    && pecl install xdebug-3.1.3 \
    && pecl install ds

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /app