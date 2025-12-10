FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    cron \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY ./docker/cron/crontab /etc/cron.d/gorun-cron

RUN chmod 0644 /etc/cron.d/gorun-cron && \
    crontab /etc/cron.d/gorun-cron && \
    touch /var/log/cron.log

RUN echo '#!/bin/bash\n\
echo "Starting cron service..."\n\
printenv | grep -v "no_proxy" >> /etc/environment\n\
cron\n\
echo "Cron started. Watching logs..."\n\
tail -f /var/log/cron.log' > /start-cron.sh && \
    chmod +x /start-cron.sh

CMD ["/start-cron.sh"]