FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    zip \
    libzip-dev \
    postgresql \
    postgresql-client

# Install and enable PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        zip

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files and env.prod
COPY composer.json composer.lock .env.prod ./
RUN mv .env.prod .env

# Install dependencies with autoloader and scripts
RUN composer install --no-dev --optimize-autoloader

# Copy rest of the application
COPY . .

# JWT copy
COPY config/jwt/ /app/config/jwt/

# Create var directory and set permissions
RUN mkdir -p var && \
    chown -R www-data:www-data var/ && \
    chmod 777 -R var/


# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Set recommended PHP.ini settings
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini