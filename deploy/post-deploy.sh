#!/usr/bin/env bash
set -euo pipefail

cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.."
PHP_BIN="${PHP_BIN:-php}"

mkdir -p storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [[ ! -f .env ]]; then
    echo 'Deployment stopped: create and configure .env in the application root before running post-deploy.' >&2
    exit 1
fi

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
chmod -R ug+rwX storage bootstrap/cache
