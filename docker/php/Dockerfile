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
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash appuser || true

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 10M" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY --chown=appuser:appuser composer.json composer.lock ./

USER appuser
RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

USER root
COPY --chown=appuser:appuser . /var/www/html

RUN rm -f /var/www/html/.env.local \
    && rm -f /var/www/html/.env.*.local \
    && rm -f /var/www/html/.env.test.local

USER appuser
RUN composer dump-autoload --optimize --classmap-authoritative

USER root
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R appuser:appuser /var/www/html/var \
    && chmod -R 775 /var/www/html/var

USER appuser

EXPOSE 9000

CMD ["php-fpm"]