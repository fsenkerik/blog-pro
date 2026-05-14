#!/bin/bash
set -e

# Fix Apache MPM conflict at runtime - remove ALL MPM modules, enable only prefork
rm -f /etc/apache2/mods-enabled/mpm_event.conf \
      /etc/apache2/mods-enabled/mpm_event.load \
      /etc/apache2/mods-enabled/mpm_worker.conf \
      /etc/apache2/mods-enabled/mpm_worker.load \
      /etc/apache2/mods-enabled/mpm_threaded.conf \
      /etc/apache2/mods-enabled/mpm_threaded.load

if [ ! -f /etc/apache2/mods-enabled/mpm_prefork.load ]; then
    ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf
    ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
fi

# Configure Apache to listen on Railway's PORT (default 80)
PORT=${PORT:-80}
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# Start DB init in background
/usr/local/bin/init-db.sh &

# Publish scheduled posts even when nobody is actively loading the site.
(
  while true; do
    php /var/www/html/cron/publish.php >/proc/1/fd/1 2>/proc/1/fd/2 || true
    sleep "${PUBLISH_CRON_INTERVAL:-30}"
  done
) &

# Start Apache in foreground
exec apache2-foreground
