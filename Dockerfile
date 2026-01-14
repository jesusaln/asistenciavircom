FROM php:8.2-fpm

# Instalar dependencias del sistema incluyendo PostgreSQL
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    postgresql-client \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www/cdd_app

# Copiar archivos de la aplicación
COPY . .

# Eliminar caché de bootstrap que pudo haberse copiado del host (CRUCIAL para evitar error de PailServiceProvider)
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Instalar dependencias PHP (--no-scripts evita conexión a BD durante build)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Instalar dependencias Node.js (borramos package-lock.json para evitar errores de plataforma Windows/Linux)
RUN rm -f package-lock.json && npm install && rm -rf node_modules && npm install --omit=dev

# Establecer permisos
RUN chown -R www-data:www-data /var/www/cdd_app \
    && chmod -R 755 /var/www/cdd_app \
    && chmod -R 775 /var/www/cdd_app/storage \
    && chmod -R 775 /var/www/cdd_app/bootstrap/cache

# Exponer puerto 9000 para PHP-FPM
EXPOSE 9000

# Comando por defecto (php-fpm es el default de la imagen base)
CMD ["php-fpm"]
