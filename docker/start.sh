#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html
export PORT="${PORT:-10000}"
if [[ ! "$PORT" =~ ^[0-9]+$ ]] || (( PORT < 1024 || PORT > 65535 )); then
    echo 'PORT must be an unprivileged port between 1024 and 65535.' >&2
    exit 1
fi
export APP_URL="${APP_URL:-${RENDER_EXTERNAL_URL:-http://localhost:$PORT}}"
if [[ -z "${APP_KEY:-}" ]]; then
    echo 'APP_KEY is required. Set it in the Render environment before starting the demo.' >&2
    exit 1
fi
if [[ "$APP_KEY" != base64:* ]]; then
    # Render generateValue is plain text: SHA-256 yields exactly 32 binary bytes.
    export APP_KEY="$(php -r 'echo "base64:".base64_encode(substr(hash("sha256", getenv("APP_KEY"), true), 0, 32));')"
fi

# Always reset demo data, even when the same container is restarted.
export DB_CONNECTION=sqlite DB_DATABASE=/tmp/demo/database.sqlite
unset DB_URL
umask 077
mkdir -p /tmp/demo
rm -f "$DB_DATABASE" "$DB_DATABASE-wal" "$DB_DATABASE-shm"
touch "$DB_DATABASE"
php artisan config:clear
php artisan migrate --force --seed
if [[ -n "${DEMO_ADMIN_EMAIL:-}" || -n "${DEMO_ADMIN_PASSWORD:-}" ]]; then
    php artisan app:demo-admin
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# No public storage link and no scheduler: this disposable demo resets at start.
printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
exec apache2-foreground
