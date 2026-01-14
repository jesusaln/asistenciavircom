#!/bin/bash

# Script de despliegue para Climas del Desierto
# Uso: ./docker/deploy.sh [entorno]

set -e

ENTORNO=${1:-production}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🚀 Iniciando despliegue para Climas del Desierto..."
echo "📍 Entorno: $ENTORNO"
echo "📂 Directorio del proyecto: $PROJECT_ROOT"

# Verificar si existe archivo .env para el entorno
ENV_FILE="$PROJECT_ROOT/.env.$ENTORNO"
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ No se encontró el archivo de configuración: $ENV_FILE"
    echo "💡 Copia .env.production a .env.$ENTORNO y configura las variables"
    exit 1
fi

# Copiar archivo de entorno si no existe .env
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo "📋 Copiando configuración de entorno..."
    cp "$ENV_FILE" "$PROJECT_ROOT/.env"
fi

# Crear directorios necesarios
echo "📁 Creando directorios necesarios..."
mkdir -p "$PROJECT_ROOT/docker/pgdata"
mkdir -p "$PROJECT_ROOT/docker/redis"
mkdir -p "$PROJECT_ROOT/docker/pgadmin"
mkdir -p "$PROJECT_ROOT/storage/app"
mkdir -p "$PROJECT_ROOT/storage/framework/cache"
mkdir -p "$PROJECT_ROOT/storage/framework/sessions"
mkdir -p "$PROJECT_ROOT/storage/framework/views"
mkdir -p "$PROJECT_ROOT/storage/logs"

# Establecer permisos
echo "🔐 Configurando permisos..."
chmod -R 755 "$PROJECT_ROOT/storage"
chmod -R 755 "$PROJECT_ROOT/bootstrap/cache"

# Construir y levantar contenedores
echo "🐳 Construyendo y levantando contenedores..."
cd "$PROJECT_ROOT"

# Detener contenedores existentes
docker-compose down || true

# Construir imágenes
docker-compose build --no-cache

# Levantar servicios
docker-compose up -d

# Esperar a que la base de datos esté lista
echo "⏳ Esperando a que la base de datos esté disponible..."
sleep 30

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
docker-compose exec -T app php artisan migrate --force

# Ejecutar seeders si es necesario
echo "🌱 Ejecutando seeders..."
docker-compose exec -T app php artisan db:seed --force || echo "⚠️ No se encontraron seeders o hubo un error"

# Optimizar aplicación
echo "⚡ Optimizando aplicación..."
docker-compose exec -T app php artisan config:cache
docker-compose exec -T app php artisan route:cache
docker-compose exec -T app php artisan view:cache

# Crear enlace simbólico para storage
echo "🔗 Creando enlace simbólico para storage..."
docker-compose exec -T app php artisan storage:link || echo "⚠️ El enlace ya existe o hubo un error"

echo "✅ ¡Despliegue completado exitosamente!"
echo ""
echo "🌐 Servicios disponibles:"
echo "   • Aplicación: http://localhost (o el dominio configurado)"
echo "   • pgAdmin: http://localhost:8081"
echo ""
echo "🔧 Comandos útiles:"
echo "   • Ver logs: docker-compose logs -f app"
echo "   • Reiniciar servicios: docker-compose restart"
echo "   • Detener servicios: docker-compose down"
echo ""
echo "📝 Recuerda actualizar las siguientes variables en .env.$ENTORNO:"
echo "   • DB_PASSWORD"
echo "   • REDIS_PASSWORD"
echo "   • PGADMIN_PASSWORD"
echo "   • APP_KEY (si no está configurada)"