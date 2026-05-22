FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_mysql mysqli gd mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
COPY docker-php-upload.ini /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/uploads /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/uploads /var/www/html/backups

COPY init-db.sh /usr/local/bin/init-db.sh
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/init-db.sh /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
