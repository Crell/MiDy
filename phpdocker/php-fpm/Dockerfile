FROM phpdockerio/php:8.4-fpm
WORKDIR "/app"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
        php8.4-xdebug \
        php8.4-sqlite \
        php8.4-pdo-sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
