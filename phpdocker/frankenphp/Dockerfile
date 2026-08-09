FROM dunglas/frankenphp:php8.5
WORKDIR "/app"

RUN apt-get update \
    && apt-get -y --no-install-recommends install \
        git \
    && apt-get clean \
    && install-php-extensions dom pdo \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* \
