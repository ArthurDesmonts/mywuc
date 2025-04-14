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
    postgresql-client \
    libonig-dev \
    zlib1g-dev

# Install and enable PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        zip \
        mbstring

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0

# Copy application files
COPY . .

# Move env.prod to .env (important pour que Symfony ait ses variables à temps)
RUN mv .env.prod .env

# Install PHP dependencies after copying all files
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Generate JWT keys from env vars (important : après composer install)
RUN mkdir -p config/jwt && \
    echo "$JWT_PRIVATE_KEY" > config/jwt/private.pem && \
    echo "$JWT_PUBLIC_KEY" > config/jwt/public.pem

# Run Symfony scripts manually (composer scripts nécessitent .env et les clés JWT)
RUN php bin/console cache:clear --env=prod && \
    php bin/console cache:warmup --env=prod && \
    php bin/console doctrine:migrations:migrate --no-interaction

# Set permissions
RUN mkdir -p var && \
    chown -R www-data:www-data var/ && \
    chmod -R 777 var/

# Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# PHP recommendations
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini


RUN php bin/console doctrine:migrations:migrate --no-interaction