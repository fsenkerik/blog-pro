#!/bin/bash
if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "DB env vars not set, skipping import."
    exit 0
fi

DB_PORT=${DB_PORT:-3306}

echo "Waiting for MySQL at $DB_HOST:$DB_PORT..."
for i in $(seq 1 60); do
    if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --connect-timeout=3 -e "SELECT 1;" "$DB_NAME" > /dev/null 2>&1; then
        echo "MySQL is ready after $i attempts."
        break
    fi
    echo "Attempt $i failed, retrying in 2s..."
    sleep 2
done

TABLE_COUNT=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --connect-timeout=5 "$DB_NAME" \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
    --skip-column-names 2>/dev/null || echo "0")

if [ "$TABLE_COUNT" = "0" ] || [ -z "$TABLE_COUNT" ]; then
    echo "Importing database.sql..."
    mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --connect-timeout=5 "$DB_NAME" < /var/www/html/database.sql 2>/dev/null \
        && echo "Database imported successfully." \
        || echo "Database import failed."
else
    echo "Database already has $TABLE_COUNT tables, skipping import."
fi
