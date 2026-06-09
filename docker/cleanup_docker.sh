#!/bin/bash

# Script para limpiar configuración Docker anterior
# Uso: ./docker/cleanup_docker.sh

set -e

echo "🧹 Iniciando limpieza de configuración Docker anterior..."
echo "⚠️  Este script eliminará contenedores, imágenes y volúmenes antiguos"
read -p "¿Estás seguro de continuar? (yes/no): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]|[Yy]$ ]]
then
    echo "❌ Operación cancelada"
    exit 1
fi

# Función para limpiar recursos específicos del proyecto
cleanup_project_resources() {
    local project_name="cdd"

    echo "🔍 Buscando recursos relacionados con el proyecto..."

    # Detener y eliminar contenedores
    echo "🛑 Deteniendo y eliminando contenedores..."
    docker-compose down --volumes --remove-orphans 2>/dev/null || true

    # Eliminar contenedores específicos del proyecto
    local containers=$(docker ps -a --filter "name=${project_name}" -q 2>/dev/null)
    if [ ! -z "$containers" ]; then
        echo "📦 Eliminando contenedores antiguos..."
        docker rm -f $containers 2>/dev/null || true
    fi

    # Eliminar imágenes específicas del proyecto
    local images=$(docker images "*${project_name}*" -q 2>/dev/null)
    if [ ! -z "$images" ]; then
        echo "🖼️  Eliminando imágenes antiguas..."
        docker rmi -f $images 2>/dev/null || true
    fi

    # Eliminar volúmenes específicos del proyecto
    local volumes=$(docker volume ls --filter "name=${project_name}" -q 2>/dev/null)
    if [ ! -z "$volumes" ]; then
        echo "💾 Eliminando volúmenes antiguos..."
        docker volume rm $volumes 2>/dev/null || true
    fi

    # Eliminar redes específicas del proyecto
    local networks=$(docker network ls --filter "name=${project_name}" -q 2>/dev/null)
    if [ ! -z "$networks" ]; then
        echo "🌐 Eliminando redes antiguas..."
        docker network rm $networks 2>/dev/null || true
    fi
}

# Función para limpiar recursos huérfanos
cleanup_orphaned_resources() {
    echo "🧽 Limpiando recursos huérfanos..."

    # Limpiar contenedores detenidos
    local stopped_containers=$(docker ps -aq -f status=exited 2>/dev/null)
    if [ ! -z "$stopped_containers" ]; then
        echo "📦 Eliminando contenedores detenidos..."
        docker rm -f $stopped_containers 2>/dev/null || true
    fi

    # Limpiar imágenes dangling
    local dangling_images=$(docker images -f "dangling=true" -q 2>/dev/null)
    if [ ! -z "$dangling_images" ]; then
        echo "🖼️  Eliminando imágenes dangling..."
        docker rmi -f $dangling_images 2>/dev/null || true
    fi

    # Limpiar volúmenes dangling
    local dangling_volumes=$(docker volume ls -f "dangling=true" -q 2>/dev/null)
    if [ ! -z "$dangling_volumes" ]; then
        echo "💾 Eliminando volúmenes dangling..."
        docker volume rm $dangling_volumes 2>/dev/null || true
    fi
}

# Función para mostrar estado del sistema
show_system_status() {
    echo "📊 Estado actual del sistema Docker:"
    echo ""
    echo "🐳 Contenedores corriendo:"
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
    echo ""
    echo "💾 Volúmenes:"
    docker volume ls --format "table {{.Name}}\t{{.Driver}}"
    echo ""
    echo "🌐 Redes:"
    docker network ls --format "table {{.Name}}\t{{.Driver}}\t{{.Scope}}"
}

# Ejecutar limpieza
echo "🚀 Iniciando proceso de limpieza..."

# Limpiar recursos específicos del proyecto
cleanup_project_resources

# Limpiar recursos huérfanos
cleanup_orphaned_resources

# Mostrar estado final
echo ""
echo "✅ Limpieza completada!"
show_system_status

echo ""
echo "📋 Próximos pasos recomendados:"
echo "1. Verificar que no hay servicios críticos corriendo"
echo "2. Proceder con el despliegue en Portainer"
echo "3. Ejecutar las migraciones necesarias"
echo ""
echo "💡 Consejo: Siempre verifica el estado del sistema después de la limpieza"