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

# Remove conflicting MPM modules, keep only prefork (required by mod_php)
RUN find /etc/apache2/mods-enabled -name 'mpm_event*' -delete \
    && find /etc/apache2/mods-enabled -name 'mpm_worker*' -delete \
    && find /etc/apache2/mods-available -name 'mpm_event*' -delete \
    && find /etc/apache2/mods-available -name 'mpm_worker*' -delete \
    && ls /etc/apache2/mods-enabled/ | grep mpm

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/uploads /var/www/html/backups

COPY init-db.sh /usr/local/bin/init-db.sh
RUN chmod +x /usr/local/bin/init-db.sh

CMD ["/bin/bash", "-c", "/usr/local/bin/init-db.sh & apache2-foreground"]

EXPOSE 80
