#!/bin/bash

# Script de despliegue optimizado para Portainer
# Uso: ./docker/deploy_portainer_optimized.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "🚀 Iniciando despliegue optimizado en Portainer para Climas del Desierto..."
echo "📂 Proyecto: $PROJECT_ROOT"
echo "⏰ Fecha: $(date)"

# Función para verificar dependencias
check_dependencies() {
    echo "🔍 Verificando dependencias..."

    local dependencies=("docker" "git")
    for dep in "${dependencies[@]}"; do
        if ! command -v "$dep" &> /dev/null; then
            echo "❌ Dependencia faltante: $dep"
            exit 1
        fi
    done

    echo "✅ Todas las dependencias están instaladas"
}

# Función para validar archivos críticos
validate_files() {
    echo "📋 Validando archivos críticos..."

    local required_files=(
        "$PROJECT_ROOT/Dockerfile"
        "$PROJECT_ROOT/docker-compose.yml"
        "$PROJECT_ROOT/.env.production"
    )

    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            echo "❌ Archivo requerido no encontrado: $file"
            exit 1
        fi
    done

    echo "✅ Todos los archivos críticos están presentes"
}

# Función para preparar entorno
prepare_environment() {
    echo "🏗️ Preparando entorno de despliegue..."

    # Crear directorios necesarios
    mkdir -p "$PROJECT_ROOT/docker/ssl"
    mkdir -p "$PROJECT_ROOT/backups/database"
    mkdir -p "$PROJECT_ROOT/storage/app"
    mkdir -p "$PROJECT_ROOT/storage/framework/cache"
    mkdir -p "$PROJECT_ROOT/storage/framework/sessions"
    mkdir -p "$PROJECT_ROOT/storage/framework/views"
    mkdir -p "$PROJECT_ROOT/storage/logs"

    # Configurar permisos
    chmod -R 755 "$PROJECT_ROOT/storage"
    chmod -R 755 "$PROJECT_ROOT/bootstrap/cache"

    echo "✅ Entorno preparado correctamente"
}

# Función para configurar SSL
setup_ssl() {
    echo "🔐 Configurando certificados SSL..."

    if [ ! -f "$PROJECT_ROOT/docker/ssl/cert.pem" ] || [ ! -f "$PROJECT_ROOT/docker/ssl/key.pem" ]; then
        echo "🔧 Generando certificados SSL auto-firmados..."
        $SCRIPT_DIR/generate_ssl.sh portainer.asistenciavircom.com
    else
        echo "✅ Certificados SSL ya existen"
    fi
}

# Función para validar configuración de producción
validate_production_config() {
    echo "🔍 Validando configuración de producción..."

    # Verificar que se haya configurado .env
    if [ ! -f "$PROJECT_ROOT/.env" ]; then
        echo "⚠️ Archivo .env no encontrado. Creando desde plantilla..."
        cp "$PROJECT_ROOT/.env.production" "$PROJECT_ROOT/.env"
        echo "⚠️ IMPORTANTE: Edita el archivo .env con tus valores reales antes de continuar"
        echo "   Variables críticas:"
        echo "   - DB_PASSWORD"
        echo "   - REDIS_PASSWORD"
        echo "   - PGADMIN_PASSWORD"
        echo "   - APP_KEY"
        read -p "Presiona Enter después de configurar .env..."
    fi

    # Verificar que las contraseñas no sean las predeterminadas
    local env_file="$PROJECT_ROOT/.env"
    if grep -q "CAMBIA_ESTE_PASSWORD" "$env_file"; then
        echo "❌ Aún tienes contraseñas predeterminadas en .env"
        echo "   Edita el archivo .env antes de continuar"
        exit 1
    fi

    echo "✅ Configuración de producción validada"
}

# Función para mostrar resumen del despliegue
show_deployment_summary() {
    echo ""
    echo "📋 RESUMEN DEL DESPLIEGUE:"
    echo "========================================"
    echo ""
    echo "🌐 Servicios que se crearán:"
    echo "   • cdd-app: Aplicación Laravel (PHP 8.3)"
    echo "   • cdd-queue: Procesador de colas"
    echo "   • cdd-db: Base de datos PostgreSQL 16"
    echo "   • cdd-redis: Servidor Redis"
    echo ""
    echo "📁 Volúmenes persistentes:"
    echo "   • cdd_db_data: Datos de PostgreSQL"
    echo "   • cdd_redis_data: Datos de Redis"
    echo ""
    echo "🌐 URLs después del despliegue:"
    echo "   • Aplicación: https://portainer.asistenciavircom.com/"
    echo "   • pgAdmin: https://portainer.asistenciavircom.com:8081/"
    echo ""
    echo "🔧 Pasos siguientes en Portainer:"
    echo "   1. Crear stack con el nombre: 'cdd-app-production'"
    echo "   2. Copiar configuración de docker-compose.yml"
    echo "   3. Configurar TODAS las variables de entorno en el formulario"
    echo "   4. Desplegar el stack"
    echo "   5. Ejecutar migraciones"
    echo ""
}

# Función para mostrar instrucciones finales
show_final_instructions() {
    echo ""
    echo "🎯 INSTRUCCIONES FINALES:"
    echo "========================================"
    echo ""
    echo "1. 📤 Subir archivos al servidor:"
    echo "   Asegúrate de que todos los archivos estén en el servidor"
    echo ""
    echo "2. 🌐 Crear stack en Portainer:"
    echo "   • Ve a: https://portainer.asistenciavircom.com/"
    echo "   • Stacks → Add stack"
    echo "   • Nombre: 'cdd-app-production'"
    echo "   • Web editor: Copia el contenido de docker-compose.yml"
    echo "   • Environment variables: Agrega TODAS las variables del formulario"
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
    echo "5. 🔍 Verificar funcionamiento:"
    echo "   Abre https://portainer.asistenciavircom.com/"
    echo ""
    echo "💡 Comandos útiles para mantenimiento:"
    echo "   • Ver logs: docker-compose logs -f [servicio]"
    echo "   • Ejecutar comandos: docker-compose exec app php artisan [comando]"
    echo "   • Respaldos: $SCRIPT_DIR/backup.sh"
    echo "   • Actualizaciones: $SCRIPT_DIR/update.sh"
    echo ""
    echo "✅ ¡El proyecto está listo para desplegar en Portainer!"
}

# Ejecutar todas las funciones
main() {
    echo "🚀 Iniciando proceso de despliegue optimizado..."
    echo ""

    check_dependencies
    validate_files
    prepare_environment
    setup_ssl
    validate_production_config
    show_deployment_summary
    show_final_instructions

    echo ""
    echo "🎉 ¡Proceso completado exitosamente!"
    echo "📅 $(date)"
    echo ""
    echo "El proyecto está listo para desplegar en Portainer."
}

# Ejecutar función principal
main "$@"