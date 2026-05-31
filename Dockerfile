FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy ALL code first — artisan must exist before composer install
COPY . .

# Now run composer install — artisan is available for package:discover
RUN composer install --optimize-autoloader --no-interaction --no-progress

# Create a fallback .env if not present
RUN cp -n .env.example .env 2>/dev/null || true

# Create output and log directories
RUN mkdir -p /output storage/logs \
    && chmod -R 777 /output storage/logs

CMD ["sh", "-c", "php artisan migrate --force && php artisan write:products"]