FROM php:8.4-fpm-alpine AS base

# Dependências do sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    ffmpeg \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    curl

# Extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql pcntl bcmath gd intl mbstring zip

# PHP config otimizado pra produção
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize=25M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "post_max_size=30M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/custom.ini" \
    && echo "max_execution_time=60" >> "$PHP_INI_DIR/conf.d/custom.ini"

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências PHP primeiro (cache de layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copia o resto do projeto
COPY . .

# Finaliza instalação do Composer (scripts, auto-discover)
RUN composer dump-autoload --optimize

# Permissões do storage
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Cria symlink do storage
RUN php artisan storage:link 2>/dev/null || true

# Configs do Nginx e Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
