#!/bin/bash
set -e

if [ ! -f .env ]; then
  echo ".env não encontrado, copiando .env.example"
  cp .env.example .env
fi
# Install PHP dependencies if vendor directory is missing
# Restore vendor directory from cached copy or install if missing
if [ ! -d vendor ]; then
  if [ -d /vendor_cache ]; then
    echo "Recuperando dependências pré-instaladas..."
    cp -a /vendor_cache vendor
  else
    echo "Instalando dependências do Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
fi

# Ensure Laravel directories exist and have correct permissions
mkdir -p storage/logs bootstrap/cache
touch storage/logs/laravel.log
chown -R www-data:www-data storage bootstrap/cache
chmod -R 777 storage bootstrap/cache
echo "Iniciando aplicação..."
exec "$@"
