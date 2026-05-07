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

# Remove ALL MPM symlinks, then enable only mpm_prefork (required for mod_php)
RUN rm -f /etc/apache2/mods-enabled/mpm_*.conf \
          /etc/apache2/mods-enabled/mpm_*.load \
    && ln -s /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf \
    && ln -s /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html \
    && chmod 755 /var/www/html/uploads /var/www/html/backups

COPY init-db.sh /usr/local/bin/init-db.sh
RUN chmod +x /usr/local/bin/init-db.sh

CMD ["/bin/bash", "-c", \
    "PORT=${PORT:-80} && \
    sed -i \"s/Listen 80/Listen $PORT/\" /etc/apache2/ports.conf && \
    sed -i \"s/*:80>/*:$PORT>/\" /etc/apache2/sites-enabled/000-default.conf && \
    /usr/local/bin/init-db.sh & \
    apache2-foreground"]

EXPOSE 80
