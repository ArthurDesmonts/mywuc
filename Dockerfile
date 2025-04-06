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
ENV APP_ENV=prod
ENV DATABASE_URL="postgresql://dbmywuc_vquu_user:skRzqkwSquNaF0CA6fyFrN3784NdW4qj@dpg-cvp83jqdbo4c73bc65l0-a/dbmywuc_vquu"

# Copy composer files and env.prod
COPY composer.json composer.lock .env.prod ./
RUN mv .env.prod .env

# Install dependencies without scripts
RUN composer install --no-dev --no-scripts --no-autoloader

# Copy rest of the application
COPY . .

# Create var directory and set permissions
RUN mkdir -p var && \
    chown -R www-data:www-data var/ && \
    chmod 777 -R var/

# Run scripts and generate autoloader
RUN set -e; \
    composer dump-autoload --optimize --no-dev; \
    composer run-script post-install-cmd --no-dev

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