FROM php:8-alpine3.14

LABEL author="Fernando Piovezan"

# Install system dependencies
RUN apk add --update --virtual \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    libzip-dev \
    unzip \
    git \
    openssh-client \
    libintl \
    icu-dev \
    gettext-dev \
    $PHPIZE_DEPS

# Clear cache
RUN rm -rf /tmp/*

# Install PHP extensions
RUN docker-php-ext-install gettext exif pcntl bcmath gd opcache calendar intl mysqli pdo_mysql shmop soap sockets sysvmsg sysvsem sysvshm zip

RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

RUN pecl install -o -f xlswriter \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable xlswriter

# Get latest Composer
RUN curl -s https://getcomposer.org/installer | php
RUN alias composer='php composer.phar'

# Create system user to run Composer and Artisan Commands

RUN adduser -G www-data -G root -u 1000 -h /home/baseuser baseuser -D
    
RUN mkdir -p /home/baseuser/.composer && \
    chown -R baseuser /home/baseuser

# Set working directory
WORKDIR /var/www

USER baseuser