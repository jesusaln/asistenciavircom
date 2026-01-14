#!/bin/bash

# Script para migrar datos de SQLite a PostgreSQL
# Uso: ./docker/migrate_to_postgresql.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🔄 Iniciando migración de SQLite a PostgreSQL..."
echo "📂 Directorio del proyecto: $PROJECT_ROOT"

# Verificar que existe la base de datos SQLite
SQLITE_DB="$PROJECT_ROOT/database/database.sqlite"
if [ ! -f "$SQLITE_DB" ]; then
    echo "❌ No se encontró la base de datos SQLite en: $SQLITE_DB"
    exit 1
fi

# Verificar que PostgreSQL esté corriendo
if ! docker-compose ps | grep -q "cdd-pg.*Up"; then
    echo "❌ PostgreSQL no está corriendo. Inicia el stack primero."
    exit 1
fi

cd "$PROJECT_ROOT"

echo "📊 Información de la migración:"
echo "   • Base de datos origen: SQLite ($SQLITE_DB)"
echo "   • Base de datos destino: PostgreSQL (cdd_production)"

# Crear respaldo de SQLite antes de migrar
echo "💾 Creando respaldo de SQLite..."
BACKUP_DIR="$PROJECT_ROOT/backups/sqlite"
mkdir -p "$BACKUP_DIR"
SQLITE_BACKUP="$BACKUP_DIR/database_$(date +%Y%m%d_%H%M%S).sqlite"
cp "$SQLITE_DB" "$SQLITE_BACKUP"
echo "✅ Respaldo creado: $SQLITE_BACKUP"

# Instalar sqlite3 si no está disponible
echo "🔧 Verificando herramientas necesarias..."
if ! command -v sqlite3 &> /dev/null; then
    echo "❌ sqlite3 no está instalado. Instálalo primero:"
    echo "   sudo apt-get install sqlite3  # En Linux"
    echo "   O usa WSL en Windows"
    exit 1
fi

# Extraer esquema y datos de SQLite
echo "📋 Extrayendo esquema de SQLite..."
SCHEMA_FILE="$PROJECT_ROOT/temp_schema.sql"
DATA_FILE="$PROJECT_ROOT/temp_data.sql"

# Crear archivos temporales
sqlite3 "$SQLITE_DB" ".schema" > "$SCHEMA_FILE" 2>/dev/null || {
    echo "❌ Error extrayendo esquema de SQLite"
    exit 1
}

echo "📦 Extrayendo datos de SQLite..."
sqlite3 "$SQLITE_DB" ".dump" > "$DATA_FILE" 2>/dev/null || {
    echo "❌ Error extrayendo datos de SQLite"
    exit 1
}

# Crear archivo de migración específico para PostgreSQL
MIGRATION_FILE="$PROJECT_ROOT/database/migrations/$(date +%Y_%m_%d_%H%M%S)_migrate_from_sqlite.php"
cat > "$MIGRATION_FILE" << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Esta migración se ejecuta después de importar datos desde SQLite
        // Puedes agregar aquí cualquier transformación específica necesaria

        DB::statement("ALTER DATABASE cdd_production SET timezone = 'America/Hermosillo';");

        // Actualizar secuencias de PostgreSQL para que coincidan con los IDs existentes
        $tables = ['users', 'clientes', 'productos', 'ventas', 'compras'];

        foreach ($tables as $table) {
            DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), (SELECT COALESCE(MAX(id), 1) FROM {$table}));");
        }

        // Crear índices adicionales si es necesario
        DB::statement('CREATE INDEX IF NOT EXISTS idx_clientes_email ON clientes(email);');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_productos_codigo ON productos(codigo);');
    }

    public function down()
    {
        // No necesitamos deshacer esta migración específica
    }
};
EOF

echo "🔄 Aplicando esquema a PostgreSQL..."
# Aplicar esquema (sin datos por ahora)
docker-compose exec -T pg psql -U ${DB_USER:-cdd_user} -d ${DB_NAME:-cdd_production} -f /temp_schema.sql || {
    echo "⚠️ Algunos elementos del esquema pueden haber fallado (índices, triggers, etc.)"
}

echo "📥 Importando datos a PostgreSQL..."
# Importar datos
docker-compose exec -T pg psql -U ${DB_USER:-cdd_user} -d ${DB_NAME:-cdd_production} < "$DATA_FILE" || {
    echo "❌ Error importando datos. Verifica la compatibilidad."
    exit 1
}

echo "⚡ Ejecutando migración específica..."
docker-compose exec -T app php artisan migrate --force

echo "🧹 Limpiando archivos temporales..."
rm -f "$SCHEMA_FILE" "$DATA_FILE"

echo "✅ ¡Migración completada exitosamente!"
echo ""
echo "📋 Resumen de la migración:"
echo "   • Respaldo SQLite: $SQLITE_BACKUP"
echo "   • Datos migrados correctamente"
echo "   • Migración específica ejecutada"
echo ""
echo "🔍 Verificaciones recomendadas:"
echo "   • Verificar que los datos se hayan migrado correctamente"
echo "   • Probar el funcionamiento de la aplicación"
echo "   • Crear un respaldo de PostgreSQL: ./docker/backup.sh"
echo ""
echo "⚠️ Notas importantes:"
echo "   • Los usuarios pueden necesitar restablecer sus contraseñas"
echo "   • Algunas funciones específicas de SQLite pueden necesitar ajustes"
echo "   • Revisa los logs de Laravel para cualquier error"