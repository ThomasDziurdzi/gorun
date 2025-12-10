FROM php:8.4-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

# Installer les dépendances système
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

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer Symfony CLI et créer l'utilisateur
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony \
    && groupadd -g ${GROUP_ID} appuser || true \
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash appuser || true

# Configuration PHP
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "memory_limit = 512M" >> "$PHP_INI_DIR/php.ini" \
    && echo "upload_max_filesize = 10M" >> "$PHP_INI_DIR/php.ini" \
    && echo "post_max_size = 10M" >> "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# ✅ ÉTAPE 1 : Copier SEULEMENT composer.json et composer.lock d'abord
COPY --chown=appuser:appuser composer.json composer.lock ./

# ✅ ÉTAPE 2 : Installer les dépendances (cache Docker optimisé)
USER appuser
RUN composer install \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# ✅ ÉTAPE 3 : Copier le reste des fichiers
USER root
COPY --chown=appuser:appuser . /var/www/html

# ✅ ÉTAPE 4 : Supprimer les .env.local (garder .env et .env.docker)
RUN rm -f /var/www/html/.env.local \
    && rm -f /var/www/html/.env.*.local \
    && rm -f /var/www/html/.env.test.local

# ✅ ÉTAPE 5 : Générer l'autoloader optimisé
USER appuser
RUN composer dump-autoload --optimize --classmap-authoritative

# ✅ ÉTAPE 6 : Créer les dossiers var/ avec bonnes permissions
USER root
RUN mkdir -p /var/www/html/var/cache /var/www/html/var/log \
    && chown -R appuser:appuser /var/www/html/var \
    && chmod -R 775 /var/www/html/var

USER appuser

EXPOSE 9000

CMD ["php-fpm"]