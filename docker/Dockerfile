FROM dunglas/frankenphp:php8.5

WORKDIR "/app"

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
        unzip \
    && apt-get clean \
    && install-php-extensions xdebug \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*
