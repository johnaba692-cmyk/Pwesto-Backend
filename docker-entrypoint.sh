#!/bin/sh
set -eu

if [ "${PWESTO_DB_DRIVER:-sqlite}" = "sqlite" ]; then
    if [ "${RENDER:-}" = "true" ]; then
        default_db_path=/var/data/pwesto.sqlite
    else
        default_db_path=/var/www/html/runtime/pwesto.sqlite
    fi
    db_path="${PWESTO_DB_PATH:-$default_db_path}"

    if [ "${RENDER:-}" = "true" ]; then
        case "$db_path" in
            /var/data/*) ;;
            *)
                echo "Render SQLite requires PWESTO_DB_PATH under the persistent /var/data mount." >&2
                exit 1
                ;;
        esac
        if ! mountpoint -q /var/data; then
            echo "Render SQLite requires a persistent disk mounted at /var/data." >&2
            exit 1
        fi
    fi

    export PWESTO_DB_PATH="$db_path"
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

exec docker-php-entrypoint apache2-foreground
