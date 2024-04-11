FROM php:8.2-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

EXPOSE 80

WORKDIR /app

RUN apt-get update && \
    apt-get install -y \
        openssl \
        git \
        gnupg \
        unzip \
        zip \
        libssl-dev \
    && apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
    
RUN pecl install mongodb && docker-php-ext-enable mongodb

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install -j$(nproc) opcache

COPY conf/php.ini /usr/local/etc/php/conf.d/app.ini

COPY conf/vhost.conf /etc/apache2/sites-available/000-default.conf

COPY conf/apache.conf /etc/apache2/conf-available/z-app.conf

COPY index.php /app/index.php
COPY src /app/src 

RUN composer require mongodb/mongodb lcobucci/jwt

RUN a2enmod rewrite remoteip && a2enconf z-app