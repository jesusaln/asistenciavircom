#!/bin/bash

# Script de actualización para Climas del Desierto
# Uso: ./docker/update.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🔄 Iniciando actualización de Climas del Desierto..."
echo "📂 Directorio del proyecto: $PROJECT_ROOT"

# Verificar que los contenedores estén corriendo
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Los contenedores no están corriendo. Ejecuta primero: ./docker/deploy.sh"
    exit 1
fi

cd "$PROJECT_ROOT"

# Hacer backup de la base de datos antes de actualizar
echo "💾 Creando respaldo de la base de datos..."
docker-compose exec -T pg pg_dump -U ${DB_USER:-cdd_user} ${DB_NAME:-cdd_production} > "backups/database/$(date +%Y%m%d_%H%M%S)_backup.sql" || echo "⚠️ No se pudo crear el respaldo"

# Instalar nuevas dependencias de PHP
echo "📦 Instalando dependencias de PHP..."
docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Instalar nuevas dependencias de Node.js y reconstruir assets
echo "🔧 Reconstruyendo assets..."
docker-compose exec -T app npm ci
docker-compose exec -T app npm run build

# Ejecutar migraciones pendientes
echo "🗄️ Ejecutando migraciones..."
docker-compose exec -T app php artisan migrate --force

# Limpiar y optimizar cachés
echo "🧹 Limpiando cachés..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Optimizar aplicación
echo "⚡ Optimizando aplicación..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Reiniciar servicios para aplicar cambios
echo "🔄 Reiniciando servicios..."
docker-compose restart app nginx

echo "✅ ¡Actualización completada exitosamente!"
echo ""
echo "🔍 Verificando estado de los servicios..."
docker-compose ps
echo ""
echo "📋 Últimas líneas del log de la aplicación:"
docker-compose logs --tail=10 app