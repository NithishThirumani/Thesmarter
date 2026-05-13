#!/bin/sh
cd /var/www || exit 1
# Laravel public disk: ensure symlink public/storage -> storage/app/public (avatars, uploads)
if [ -f artisan ] && [ ! -e public/storage ]; then
  php artisan storage:link || true
fi
# Optional: set RUN_MIGRATE_ON_START=1 in docker-compose for local one-command DB updates (not for untrusted prod).
if [ -f artisan ] && [ "${RUN_MIGRATE_ON_START:-}" = "1" ]; then
  php artisan migrate --force --no-interaction || true
fi
exec "$@"
