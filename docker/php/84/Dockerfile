FROM php:8.4.0RC1-apache

#=== Install gd PHP dependencie ===
RUN set -x \
 && buildDeps="libpng-dev libjpeg-dev libfreetype6-dev" \
 && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
 \
 && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
 && docker-php-ext-install gd \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== Install intl, opcache and zip PHP dependencies ===
RUN set -x \
 && buildDeps="libicu-dev zlib1g-dev libxml2-dev libzip-dev"  \
 && apt-get update && apt-get install -y ${buildDeps} --no-install-recommends \
 \
 && docker-php-ext-configure intl \
 && docker-php-ext-install intl \
 && docker-php-ext-install zip \
 && docker-php-ext-install opcache \
 \
 && apt-get purge -y ${buildDeps} \
 && rm -rf /var/lib/apt/lists/*

#=== Install mysqli PHP dependencies ===
RUN set -x \
 && docker-php-ext-install pdo pdo_mysql mysqli

#=== Install xdebug PHP dependencies ===
#RUN pecl install xdebug-3.4.0alpha1  \
#    && docker-php-ext-enable xdebug

#=== Install xdebug via PIE (PHP Extension Installer) ===
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions xdebug/xdebug@master \
    && docker-php-ext-enable xdebug

#=== php default ===
ENV PHP_LOG_ERRORS=On \
    PHP_ERROR_REPORTING=E_ALL \
    PHP_POST_MAX_SIZE=64M \
    PHP_UPLOAD_MAX_FILESIZE=64M

#=== Set SSL support
RUN a2enmod ssl && a2enmod rewrite
RUN mkdir -p /etc/apache2/ssl

COPY ./*.pem /etc/apache2/ssl/
COPY ./000-default.conf /etc/apache2/sites-available/000-default.conf

#=== Set custom entrypoint ===
COPY docker-entrypoint.sh /entrypoint
RUN chmod +x /entrypoint
ENTRYPOINT [ "/entrypoint" ]

#=== Re-Set CMD as we changed the default entrypoint ===
CMD [ "apache2-foreground" ]
