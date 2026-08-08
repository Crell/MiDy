FROM php:8.5-fpm

WORKDIR "/app"

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
    && apt-get clean \
    && install-php-extensions dom pdo pdo_sqlite xdebug \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
