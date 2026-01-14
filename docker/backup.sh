#!/bin/bash

# Script de respaldo para Climas del Desierto
# Uso: ./docker/backup.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "💾 Creando respaldo de Climas del Desierto..."
echo "📂 Directorio del proyecto: $PROJECT_ROOT"

# Crear directorio de respaldos si no existe
mkdir -p "$PROJECT_ROOT/backups/database"

# Verificar que los contenedores estén corriendo
if ! docker-compose ps | grep -q "cdd-pg.*Up"; then
    echo "❌ El contenedor de PostgreSQL no está corriendo."
    exit 1
fi

cd "$PROJECT_ROOT"

# Nombre del archivo de respaldo con timestamp
BACKUP_FILE="backups/database/$(date +%Y%m%d_%H%M%S)_cdd_backup.sql"

echo "📦 Creando respaldo en: $BACKUP_FILE"

# Crear respaldo de la base de datos
docker-compose exec -T pg pg_dump \
    -U ${DB_USER:-cdd_user} \
    -d ${DB_NAME:-cdd_production} \
    -h localhost \
    -p 5432 \
    --no-password \
    --clean \
    --if-exists \
    > "$BACKUP_FILE"

# Comprimir el respaldo
echo "🗜️ Comprimiendo respaldo..."
gzip "$BACKUP_FILE"

BACKUP_FILE_GZ="$BACKUP_FILE.gz"
echo "✅ Respaldo creado exitosamente: $BACKUP_FILE_GZ"

# Calcular tamaño del archivo
BACKUP_SIZE=$(du -h "$BACKUP_FILE_GZ" | cut -f1)
echo "📏 Tamaño del respaldo: $BACKUP_SIZE"

# Limpiar respaldos antiguos (mantener solo los últimos 7 días)
echo "🧹 Limpiando respaldos antiguos..."
find "$PROJECT_ROOT/backups/database" -name "*.gz" -type f -mtime +7 -delete

REMAINING_BACKUPS=$(find "$PROJECT_ROOT/backups/database" -name "*.gz" -type f | wc -l)
echo "📋 Respaldos restantes: $REMAINING_BACKUPS"

echo ""
echo "💡 Comandos útiles:"
echo "   • Restaurar respaldo: gunzip < $BACKUP_FILE_GZ | docker-compose exec -T pg psql -U ${DB_USER:-cdd_user} -d ${DB_NAME:-cdd_production}"
echo "   • Listar respaldos: ls -la backups/database/"
echo "   • Ver logs: docker-compose logs pg"