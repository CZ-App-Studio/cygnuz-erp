# Multi-stage build for Cygnuz ERP
# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json ./
COPY yarn.lock* ./

# Install dependencies
RUN npm ci || yarn install --frozen-lockfile

# Copy frontend source files
COPY resources ./resources
COPY vite.config.js ./
COPY vite-module-loader.js ./
COPY postcss.config.js* ./
COPY tailwind.config.js* ./

# Copy modules for frontend build
COPY Modules ./Modules

# Build frontend assets
RUN npm run build

# Stage 2: PHP application
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    oniguruma-dev \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        soap \
        xml \
        sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built assets from frontend stage
COPY --from=frontend-builder /app/public/build ./public/build

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create startup script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port
EXPOSE 80

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]