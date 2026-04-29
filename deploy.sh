#!/bin/bash
# Script de deploy para o servidor Jelastic
# Rodar na raiz do projeto: bash deploy.sh

set -e

echo "==> Atualizando dependências..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Limpando todos os caches..."
php artisan optimize:clear

echo "==> Rodando migrations pendentes..."
php artisan migrate --force

echo "==> Reconstruindo caches de produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Gerando documentação Swagger..."
php artisan l5-swagger:generate

echo "==> Reiniciando workers de fila..."
php artisan queue:restart

echo ""
echo "Deploy concluído com sucesso!"
