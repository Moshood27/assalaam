FROM php:8.4-fpm-alpine

# 1. Install system dependencies
RUN apk add --no-cache \
    bash \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev \
    unzip \
    icu-dev \
    icu-libs \
    mysql-client \
    curl \
    libxml2-dev

# 2. Configure and install PHP extensions
# Cleaned up redundancy and added pcntl for Laravel Reverb
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        pcntl \
        posix \
    && docker-php-ext-enable opcache

# 3. Install Composer
# Note: No backslash on the line above this. COPY must be its own command.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html