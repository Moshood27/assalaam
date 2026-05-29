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
    curl-dev \
    libxml2-dev \
    libsodium-dev \
    oniguruma-dev

# 2. Configure and install PHP extensions
# Cleaned up redundancy and added pcntl/posix for Laravel Reverb & Horizon
# Added redis extension for better performance/reliability
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
        pcntl \
        posix \
        sodium \
        mbstring \
        xml \
        curl \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis opcache

# 3. Install Composer
# Note: No backslash on the line above this. COPY must be its own command.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html