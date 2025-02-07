## Cloned and modified from phpdocker.io's 8.3 base image.

### CLI ###

FROM ubuntu:noble AS cli

# Fixes some weird terminal issues such as broken clear / CTRL+L
ENV TERM=linux

# Ensure apt doesn't ask questions when installing stuff
ENV DEBIAN_FRONTEND=noninteractive

# Install Ondrej repos for Ubuntu jammy, PHP, composer and selected extensions - better selection than
# the distro's packages
RUN apt-get update \
    && apt-get install -y --no-install-recommends gnupg \
    && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ondrej-php.list \
    && apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C \
    && apt-get update \
    && apt-get -y --no-install-recommends install \
        ca-certificates \
        curl \
        unzip \
        php8.4-cli \
        php8.4-curl \
        php8.4-mbstring \
        php8.4-opcache \
        php8.4-readline \
        php8.4-xml \
        php8.4-pdo \
        php8.4-pdo-sqlite \
        php8.4-dev \
        php-pear \
        make \
        git \
        nano \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/* ~/.composer

#=== Install xdebug via PIE (PHP Extension Installer) ===
#ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
#RUN install-php-extensions xdebug/xdebug@master \
#    && docker-php-ext-enable xdebug

RUN pecl install xdebug-3.4.0

#RUN git clone https://github.com/xdebug/xdebug.git -b master xdebug \
#    && cd xdebug && git reset --hard 65f7f69f34 && rm -r .git

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php", "-a"]

# Git keeps whining if I don't do this.
RUN git config --global --add safe.directory /app

### FPM ###

FROM cli AS fpm

# Install FPM
RUN apt-get update \
    && apt-get -y --no-install-recommends install php8.4-fpm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

STOPSIGNAL SIGQUIT

# PHP-FPM packages need a nudge to make them docker-friendly
COPY overrides.conf /etc/php/8.4/fpm/pool.d/z-overrides.conf

CMD ["/usr/sbin/php-fpm8.4", "-O" ]

# Open up fcgi port
EXPOSE 9000
