FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    intl \
    zip \
    opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Create var directory (since it is ignored in .dockerignore)
RUN mkdir -p var

# Install PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Set permissions
RUN chown -R www-data:www-data var

# Configure Apache DocumentRoot to public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf.conf

# Create entrypoint script to run migrations and cache clear on startup
RUN echo '#!/bin/bash' > /docker-entrypoint.sh \
    && echo 'php bin/console cache:clear' >> /docker-entrypoint.sh \
    && echo 'php bin/console assets:install public' >> /docker-entrypoint.sh \
    && echo 'php bin/console doctrine:migrations:migrate --no-interaction' >> /docker-entrypoint.sh \
    && echo 'exec apache2-foreground' >> /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.sh

# Port
EXPOSE 80

CMD ["/docker-entrypoint.sh"]
