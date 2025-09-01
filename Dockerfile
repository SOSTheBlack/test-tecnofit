# Use oficial Hyperf image with PHP 8.2 and Swoole
FROM hyperf/hyperf:8.2-alpine-v3.18-swoole

# Set working directory
WORKDIR /opt/www

# Set timezone to Brazil/São Paulo (UTC-3)
ENV TZ=America/Sao_Paulo
RUN apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime && \
    echo "America/Sao_Paulo" > /etc/timezone

# Install system dependencies and ensure PHP extensions are available
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    mysql-client \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev

# The Hyperf image already includes most PHP extensions we need
# Just ensure Redis extension is available
RUN if ! php -m | grep -q redis; then \
        pecl install redis && docker-php-ext-enable redis; \
    fi

# Install Composer (already included in Hyperf image, but ensure it's available)
RUN which composer || { curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer; }

# Copy composer files
COPY composer.json ./

# Copy application code
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Create runtime directory with proper permissions
RUN mkdir -p runtime/logs runtime/cache && \
    chmod -R 755 runtime

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Clean up build deps
RUN apk del .build-deps

# Expose port 9501
EXPOSE 9501

# Start Hyperf server
CMD ["php", "bin/hyperf.php", "start"]
