FROM php:8.4-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apt-get update && apt-get install -y \
    curl \
    git \
    libicu-dev \
    libonig-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
    && groupadd -g ${GROUP_ID} appuser || true \
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash appuser || true \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 10M" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY --chown=appuser:appuser . /var/www/html

USER root
RUN rm -rf /var/www/html/.env* \
    && ls -la /var/www/html/ | grep -i env || echo "Aucun .env restant - OK"

USER appuser
RUN composer install --no-interaction --optimize-autoloader --no-scripts

USER root
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/public/uploads \
    && chown -R appuser:appuser /var/www/html/var /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/var /var/www/html/public/uploads

USER appuser

EXPOSE 9000

CMD ["php-fpm"]