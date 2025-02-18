# Use a lightweight PHP-FPM image
FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www/html

# Install PHP extensions required by Laravel
RUN apk add --no-cache \
    curl \
    libpq \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    chromium \
    chromium-chromedriver \
    xvfb

# Install PHP extensions (INCLUDING POSTGRESQL)
RUN docker-php-ext-install pdo pdo_pgsql pgsql opcache gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy Laravel files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Install Puppeteer globally
RUN npm install -g puppeteer --unsafe-perm=true \
    && npm cache clean --force
    
RUN npm instal && npm run build

# Expose port for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["/bin/sh", "-c", "php artisan migrate --force && php-fpm"]

