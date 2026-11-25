#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Preparación del contenedor de Laravel.
# Se ejecuta en cada `docker compose up`, por lo que debe ser idempotente.
# ---------------------------------------------------------------------------
set -euo pipefail

cd /var/www/html

# 1. Archivo .env: en un clon nuevo no existe (está en .gitignore).
if [ ! -f .env ]; then
    echo "→ Creando .env a partir de .env.example"
    cp .env.example .env
fi

# 2. APP_KEY: solo se genera si falta, para no invalidar tokens existentes.
if ! grep -q '^APP_KEY=base64:' .env; then
    echo "→ Generando APP_KEY"
    php artisan key:generate --ansi --force
fi

# 3. Dependencias: el volumen nombrado puede quedar vacío o desactualizado.
if [ ! -f vendor/autoload.php ]; then
    echo "→ Instalando dependencias de Composer"
    COMPOSER_ALLOW_SUPERUSER=1 composer install \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader
fi

# 4. Carpetas de escritura de Laravel.
mkdir -p storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# 5. Limpiar cachés de configuración/rutas para reflejar cambios del código.
php artisan config:clear >/dev/null 2>&1 || true
php artisan route:clear >/dev/null 2>&1 || true

echo "→ GooMaps API disponible en http://0.0.0.0:8000"

# --no-reload: evita que `artisan serve` filtre las variables de entorno de
# Docker (DB_HOST, DB_DATABASE, etc.) al proceso hijo del servidor PHP.
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
