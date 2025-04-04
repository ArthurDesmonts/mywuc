FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libpq-dev \
    zip

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    intl \
    opcache

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set environment variable to allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files and env.prod
COPY composer.json composer.lock .env.prod ./
RUN mv .env.prod .env

# Install dependencies without scripts
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy rest of the application
COPY . .

# Create var directory and set permissions
RUN mkdir -p var && \
    chown -R www-data:www-data var/

# Run scripts and generate autoloader
RUN set -e; \
    composer dump-autoload --optimize --no-dev; \
    composer run-script post-install-cmd --no-dev

# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf