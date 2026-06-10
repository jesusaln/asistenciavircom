# --- Stage 1: Composer Dependencies ---
FROM composer:latest AS composer_deps
WORKDIR /app
COPY composer.json composer.lock ./
# Install dependencies without scripts to avoid database connection issues during build
RUN composer install --no-interaction --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-scripts

# --- Stage 2: Frontend Build ---
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps || npm install
COPY . .
# Copy vendor from composer stage - needed for Ziggy (Laravel routes in JS)
COPY --from=composer_deps /app/vendor /app/vendor
RUN npm run build

# --- Stage 3: Production PHP Image ---
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    postgresql-dev \
    postgresql-client \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    bash \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd zip intl opcache bcmath exif mbstring pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www

# Copy application code
COPY . /var/www

# Copy pre-built vendor and frontend assets
COPY --from=composer_deps /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# PWA Fix: Move manifest and sw.js from build to public (as expected by deploy.sh logic)
RUN cp -f /var/www/public/build/sw.js /var/www/public/sw.js || true \
    && cp -f /var/www/public/build/manifest.webmanifest /var/www/public/manifest.json || true \
    && cp -f /var/www/public/build/manifest.webmanifest /var/www/public/manifest.webmanifest || true

# Set permissions
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Production Opcache config
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Increase upload limits
RUN echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/uploads.ini

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
