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

# Create Apache virtual host config for Symfony
RUN echo '<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride None\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

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
