FROM php:8.2-apache

# Set environment variables
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    intl \
    zip \
    opcache \
    mbstring \
    xml \
    ctype \
    iconv

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Configure Apache DocumentRoot to public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Remove Windows-generated lock file
RUN rm -f composer.lock

# Install dependencies
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --optimize-autoloader --no-scripts --prefer-dist --no-progress

# Create var directory
RUN mkdir -p var/cache/prod var/log

# Set permissions - make var writable by everyone
RUN chmod -R 777 var

# Create entrypoint script
RUN echo '#!/bin/bash' > /docker-entrypoint.sh \
    && echo 'chmod -R 777 /var/www/html/var' >> /docker-entrypoint.sh \
    && echo 'php bin/console cache:clear || true' >> /docker-entrypoint.sh \
    && echo 'php bin/console cache:warmup || true' >> /docker-entrypoint.sh \
    && echo 'php bin/console assets:install public || true' >> /docker-entrypoint.sh \
    && echo 'php bin/console doctrine:migrations:migrate --no-interaction || true' >> /docker-entrypoint.sh \
    && echo 'exec apache2-foreground' >> /docker-entrypoint.sh \
    && chmod +x /docker-entrypoint.sh

EXPOSE 80

CMD ["/docker-entrypoint.sh"]
