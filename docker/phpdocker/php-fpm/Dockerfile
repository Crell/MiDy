FROM phpdockerio/php:8.5-fpm
WORKDIR "/app"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
        php8.5-xdebug \
        php8.5-sqlite \
        php8.5-pdo-sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
