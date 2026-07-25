FROM php:8.2-cli

# ==============================
# System dependencies
# ==============================
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ==============================
# PHP extensions needed by Laravel + MySQL
# ==============================
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    xml \
    exif \
    pcntl

# ==============================
# Increase PHP upload limits (default 2MB is too small for multi-photo car listings)
# ==============================
RUN { \
    echo 'upload_max_filesize=8M'; \
    echo 'post_max_size=40M'; \
    echo 'max_file_uploads=10'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# ==============================
# Composer
# ==============================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ==============================
# App files
# ==============================
WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

# ==============================
# Permissions + storage link (for public car images)
# ==============================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# ==============================
# Run migrations + storage link, start a lightweight scheduler loop in the
# background (checks every 60s — this is what actually fires `cars:expire` daily),
# then start Laravel's built-in server
# ==============================
CMD php artisan config:cache \
    && php artisan storage:link \
    && php artisan migrate --force \
    && (while true; do php artisan schedule:run >> /dev/null 2>&1; sleep 60; done &) \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
