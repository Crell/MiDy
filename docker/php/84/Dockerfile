FROM php:8.4.0RC1-fpm-bookworm
WORKDIR /app
RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN apt update
RUN apt install git nano nginx bzip2 zip procps -y

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN git clone https://github.com/xdebug/xdebug.git -b master xdebug \
    && cd xdebug && git reset --hard 65f7f69f34 && rm -r .git

RUN IPE_ICU_EN_ONLY=1 install-php-extensions \
bcmath \
exif \
gd \
gmp \
igbinary \
#Imagick/imagick@ef495c0b8f \
intl \
#mysqli \
pcntl \
#pdo_mysql \
#pdo_pgsql \
sockets \
tidy \
$(realpath xdebug) \
xsl \
zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

#RUN curl -L https://getcomposer.org/download/latest-stable/composer.phar -o /usr/local/bin/composer
#RUN chmod 0755 /usr/local/bin/composer
