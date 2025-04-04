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

# Install Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash && \
    apt-get install symfony-cli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set environment variable to allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer files first
COPY composer.json composer.lock ./

# Install dependencies without scripts
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy rest of the application
COPY . .

# Run scripts and generate autoloader
RUN set -e; \
    composer dump-autoload --optimize --no-dev; \
    composer run-script post-install-cmd --no-dev; \
    chown -R www-data:www-data var/

# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf