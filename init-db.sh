#!/bin/bash
# Import database.sql on first startup if tables don't exist yet
if [ -n "$DB_HOST" ] && [ -n "$DB_NAME" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
    echo "Checking database..."
    TABLE_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" \
        --skip-column-names 2>/dev/null || echo "0")

    if [ "$TABLE_COUNT" = "0" ] || [ "$TABLE_COUNT" = "" ]; then
        echo "Importing database.sql..."
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/database.sql
        echo "Database imported."
    else
        echo "Database already initialized, skipping import."
    fi
fi
