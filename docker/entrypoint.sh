#!/bin/sh
# TechHub container entrypoint: create writable runtime directories.
set -eu

for dir in \
    /var/www/html/storage/sessions \
    /var/www/html/storage/logs \
    /var/www/html/public/uploads \
    /var/www/html/data/team \
    /var/www/html/data/personal \
    /var/www/html/data/resources
do
    mkdir -p "$dir"
    chown -R www-data:www-data "$dir" 2>/dev/null || true
    chmod -R ug+rwX "$dir" 2>/dev/null || true

    if [ "$dir" = "/var/www/html/storage/sessions" ]; then
        # The bind-mounted directory may contain sessions created by a different
        # host user. PHP strict session mode rejects those files, so remove them.
        find "$dir" -maxdepth 1 -type f -name 'sess_*' ! -user www-data -delete 2>/dev/null || true
    fi
done

exec "$@"
