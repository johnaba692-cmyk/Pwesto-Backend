#!/bin/sh
set -eu

if [ "${PWESTO_DB_DRIVER:-sqlite}" = "sqlite" ]; then
    db_path="${PWESTO_DB_PATH:-/var/www/html/runtime/pwesto.sqlite}"
    case "$db_path" in
        /var/data/*)
            mkdir -p /var/data
            chown www-data:www-data /var/data
            chmod 775 /var/data
            if [ -f "$db_path" ]; then
                chown www-data:www-data "$db_path"
                chmod 664 "$db_path"
            fi
            for suffix in -wal -shm; do
                if [ -f "${db_path}${suffix}" ]; then
                    chown www-data:www-data "${db_path}${suffix}"
                    chmod 664 "${db_path}${suffix}"
                fi
            done
            ;;
    esac
fi

if [ "${PWESTO_RESTORE_APPROVED_STALLS:-}" = "1" ]; then
    su -s /bin/sh www-data -c 'php /var/www/html/console/restore_approved_stalls.php'
fi

exec docker-php-entrypoint apache2-foreground
