#!/usr/bin/env bash
set -euo pipefail

is_true() {
    local value="${1:-}"
    case "${value,,}" in
        "1"|"true"|"yes"|"on") return 0 ;;
        *) return 1 ;;
    esac
}

sql_escape_literal() {
    local raw="${1:-}"
    printf "%s" "$raw" | sed "s/'/''/g"
}

if is_true "${USE_EMBEDDED_DB:-1}"; then
    export DB_HOST="127.0.0.1"
    export DB_PORT="${DB_PORT:-3306}"
    export DB_NAME="${DB_NAME:-ridex_db}"
    export DB_USER="${DB_USER:-ridex_app}"
    export DB_PASS="${DB_PASS:-ridex_app_pass}"

    if ! [[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]]; then
        echo "Invalid DB_NAME. Use only letters, numbers, and underscores." >&2
        exit 1
    fi

    if ! [[ "$DB_USER" =~ ^[A-Za-z0-9_]+$ ]]; then
        echo "Invalid DB_USER. Use only letters, numbers, and underscores." >&2
        exit 1
    fi

    mkdir -p /run/mysqld /var/lib/mysql
    chown -R mysql:mysql /run/mysqld /var/lib/mysql

    if [[ ! -d "/var/lib/mysql/mysql" ]]; then
        mariadb-install-db --user=mysql --datadir=/var/lib/mysql >/dev/null
    fi

    mysqld_safe \
        --datadir=/var/lib/mysql \
        --socket=/run/mysqld/mysqld.sock \
        --bind-address=127.0.0.1 \
        --port="$DB_PORT" \
        >/var/log/mysqld.log 2>&1 &

    for i in $(seq 1 60); do
        if mysqladmin --socket=/run/mysqld/mysqld.sock ping --silent >/dev/null 2>&1; then
            break
        fi

        if [[ "$i" -eq 60 ]]; then
            echo "Embedded MariaDB failed to start." >&2
            exit 1
        fi

        sleep 1
    done

    escaped_pass="$(sql_escape_literal "$DB_PASS")"
    mysql --socket=/run/mysqld/mysqld.sock -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$escaped_pass';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

    php /var/www/html/bin/migrate.php

    if is_true "${APPLY_DB_SNAPSHOT_ON_BOOT:-1}"; then
        php /var/www/html/bin/import_db_snapshot.php --if-missing-ok
    fi

    existing_vehicle_count="$(mysql --socket=/run/mysqld/mysqld.sock -N -s "$DB_NAME" -e "SELECT COUNT(*) FROM vehicles;" 2>/dev/null || echo 0)"
    if [[ "${existing_vehicle_count:-0}" -eq 0 ]]; then
        php /var/www/html/bin/seed.php
    else
        echo "Skipping seed: vehicles table already contains ${existing_vehicle_count} row(s)."
    fi
fi

exec apache2-foreground
