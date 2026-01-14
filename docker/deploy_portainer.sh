#!/bin/bash

# Script de despliegue específico para Portainer
# Uso: ./docker/deploy_portainer.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🚀 Iniciando despliegue en Portainer para Climas del Desierto..."
echo "📂 Proyecto: $PROJECT_ROOT"

# Verificar archivos necesarios
echo "🔍 Verificando archivos necesarios..."

if [ ! -f "$PROJECT_ROOT/Dockerfile" ]; then
    echo "❌ No se encontró Dockerfile"
    exit 1
fi

if [ ! -f "$PROJECT_ROOT/docker-compose.yml" ]; then
    echo "❌ No se encontró docker-compose.yml"
    exit 1
fi

# Crear directorios necesarios
echo "📁 Creando estructura de directorios..."
mkdir -p "$PROJECT_ROOT/docker/ssl"
mkdir -p "$PROJECT_ROOT/backups/database"
mkdir -p "$PROJECT_ROOT/storage/app"
mkdir -p "$PROJECT_ROOT/storage/framework/cache"
mkdir -p "$PROJECT_ROOT/storage/framework/sessions"
mkdir -p "$PROJECT_ROOT/storage/framework/views"
mkdir -p "$PROJECT_ROOT/storage/logs"

# Generar certificados SSL si no existen
if [ ! -f "$PROJECT_ROOT/docker/ssl/cert.pem" ] || [ ! -f "$PROJECT_ROOT/docker/ssl/key.pem" ]; then
    echo "🔐 Generando certificados SSL..."
    $SCRIPT_DIR/generate_ssl.sh portainer.asistenciavircom.com
else
    echo "✅ Certificados SSL ya existen"
fi

# Crear archivo .env si no existe
if [ ! -f "$PROJECT_ROOT/.env" ]; then
    echo "📋 Creando archivo .env..."
    cp "$PROJECT_ROOT/.env.production" "$PROJECT_ROOT/.env"
    echo "⚠️ IMPORTANTE: Edita el archivo .env con tus valores reales antes de continuar"
    echo "   Variables críticas:"
    echo "   - DB_PASSWORD"
    echo "   - REDIS_PASSWORD"
    echo "   - PGADMIN_PASSWORD"
    echo "   - APP_KEY"
    read -p "Presiona Enter después de configurar .env..."
fi

# Construir imágenes
echo "🐳 Construyendo imágenes de Docker..."
cd "$PROJECT_ROOT"
docker-compose build --no-cache

echo "📋 Resumen del despliegue:"
echo ""
echo "🌐 Servicios que se crearán:"
echo "   • cdd-app: Aplicación Laravel"
echo "   • cdd-nginx: Proxy reverso (puertos 80 y 443)"
echo "   • cdd-pg: Base de datos PostgreSQL (puerto 5432)"
echo "   • cdd-redis: Servidor Redis (puerto 6379)"
echo "   • cdd-pgadmin: Interfaz de administración (puerto 8081)"
echo ""
echo "📁 Volúmenes persistentes:"
echo "   • pgdata: Datos de PostgreSQL"
echo "   • redisdata: Datos de Redis"
echo "   • pgadmin: Configuración de pgAdmin"
echo ""
echo "🔧 Pasos siguientes en Portainer:"
echo ""
echo "1. 📤 Subir archivos:"
echo "   Copia todos los archivos del proyecto al servidor donde tienes Portainer"
echo ""
echo "2. 🌐 Crear stack en Portainer:"
echo "   • Ve a: https://portainer.asistenciavircom.com/"
echo "   • Stacks → Add stack"
echo "   • Nombre: cdd-app"
echo "   • Web editor: Copia el contenido de docker-compose.yml"
echo "   • Environment variables: Agrega todas las variables de .env"
echo ""
echo "3. 🚀 Desplegar:"
echo "   Click en 'Deploy the stack'"
echo ""
echo "4. 🗄️ Ejecutar migraciones:"
echo "   Desde el contenedor cdd-app:"
echo "   php artisan migrate --force"
echo "   php artisan db:seed --force"
echo "   php artisan storage:link"
echo ""
echo "5. 🔄 Migrar datos de SQLite (si es necesario):"
echo "   $SCRIPT_DIR/migrate_to_postgresql.sh"
echo ""
echo "🌐 URLs después del despliegue:"
echo "   • Aplicación: https://portainer.asistenciavircom.com/"
echo "   • pgAdmin: https://portainer.asistenciavircom.com:8081/"
echo ""
echo "💡 Comandos útiles para mantenimiento:"
echo "   • Ver logs: docker-compose logs -f [servicio]"
echo "   • Respaldos: $SCRIPT_DIR/backup.sh"
echo "   • Actualizaciones: $SCRIPT_DIR/update.sh"
echo ""
echo "✅ ¡Preparación completada! El proyecto está listo para Portainer."
echo ""
echo "📝 Checklist antes de desplegar:"
echo "   [ ] Archivo .env configurado con valores reales"
echo "   [ ] Archivos subidos al servidor"
echo "   [ ] Cuenta de Portainer con permisos"
echo "   [ ] Datos de SQLite respaldados (si aplica)"