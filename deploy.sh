#!/bin/bash
# ==============================================================================
# SCRIPT DE DESPLIEGUE ROBUSTO - ASISTENCIA VIRCOM
# Previene errores y asegura consistencia en producción
# Adaptado de Climas del Desierto
# ==============================================================================

set -e

# Configuración del Servidor
# ⚠️ IMPORTANTE: Modifica estas variables antes de usar
VPS_IP="191.101.233.82" # IP predeterminada o cambia según tu VPS
USER="root"
REMOTE_PATH="/root/asistenciavircom" # Asumiendo ruta estándar

# Nombres de Contenedores (Ajusta según tu docker-compose en prod)
# Por defecto Docker Compose usa carpeta_servicio_numero
CONTAINER_APP="asistenciavircom-app-1"
CONTAINER_QUEUE="asistenciavircom-queue-1"
CONTAINER_WEB="asistenciavircom-web-1"

echo "--------------------------------------------------------"
echo "🚀 Iniciando Despliegue Robusto - ASISTENCIA VIRCOM..."
echo "--------------------------------------------------------"

# 1. Incremento de Versión, Commit y Push
echo "📝 1/8 Control de Versiones y Sincronización..."
# Intento de autodetectar versión (Fallback a timestamp si falla grep)
LAST_VERSION=$(git log --grep="Version [0-9]*" -n 1 --format=%s 2>/dev/null | grep -o "Version [0-9]*" | grep -o "[0-9]*" | head -n 1 || echo 0)
NEXT_VERSION=$((LAST_VERSION + 1))
echo "📌 Nueva Versión Detectada: $NEXT_VERSION"

git add .
# Commit condicional (solo si hay cambios)
if git diff-index --quiet HEAD --; then
    echo "ℹ️ No hay cambios para commitear, procediendo con push de seguridad."
else
    git commit -m "Version $NEXT_VERSION - Auto Deploy"
    echo "📤 Cambios guardados localmente."
fi

echo "📤 Sincronizando con Repositorio Remoto..."
git push origin main || echo "⚠️ Advertencia: 'git push' falló (¿conexión?). Continuando con el despliegue directo..."

# 2. Construcción de Assets Local
echo "📦 2/8 Construyendo Assets (Vite Production Build)..."
rm -f public/hot # Elimina flag de dev server
npm run build 

# 3. Fix Manifiesto (Previene error 500 en carga de assets)
if [ -f "public/build/.vite/manifest.json" ]; then
    echo "🔧 3/8 Normalizando manifest.json..."
    cp public/build/.vite/manifest.json public/build/manifest.json
fi

# 4. Modo Mantenimiento
echo "🚧 4/8 Activando Modo Mantenimiento en VPS..."
# Usamos '|| true' para no abortar si el contenedor no responde (ej. primer despliegue)
ssh $USER@$VPS_IP "docker exec $CONTAINER_APP php artisan down --message=\"Actualizando Asistencia Vircom (v$NEXT_VERSION). Volvemos enseguida...\" --retry=60 || echo '⚠️ No se pudo activar modo mantenimiento (¿Contenedor caído?)'"

# 5. Sincronización de Archivos (Rsync)
echo "📡 5/8 Enviando archivos vía Rsync..."
# Excluimos archivos pesados o sensibles que no deben sobreescribirse
rsync -avz --no-perms --no-owner --no-group \
    --exclude='.env' \
    --exclude='storage/*.key' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.idea' \
    --exclude='.vscode' \
    --exclude='tests' \
    --exclude='deploy.sh' \
    ./ $USER@$VPS_IP:$REMOTE_PATH/

# 6. Permisos y Dependencias Remotas
echo "🔐 6/8 Configurando entorno remoto..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    # 1. Asegurar permisos de carpetas críticas
    chmod -R 775 storage bootstrap/cache && \
    chown -R root:www-data storage bootstrap/cache || true && \
    
    # 2. Instalar dependencias PHP (súper rápido si no hay cambios en composer.json)
    docker exec $CONTAINER_APP composer install --optimize-autoloader --no-dev --no-interaction && \
    
    # 3. Limpiar flag de hot reload si se sincronizó por error
    docker exec $CONTAINER_APP rm -f public/hot"

# 7. Ejecución de Tareas de Laravel
echo "⚙️ 7/8 Optimizando y Migrando..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    # Limpieza profunda de cachés
    docker exec $CONTAINER_APP php artisan optimize:clear && \
    
    # Regeneración de cachés de producción
    docker exec $CONTAINER_APP php artisan config:cache && \
    docker exec $CONTAINER_APP php artisan route:cache && \
    docker exec $CONTAINER_APP php artisan view:cache && \
    docker exec $CONTAINER_APP php artisan event:cache && \
    
    # Ejecutar Migraciones (Force required in prod)
    docker exec $CONTAINER_APP php artisan migrate --force && \
    
    # Reiniciar colas y servicios
    echo '🔄 Reiniciando Queue Workers...' && \
    (docker restart $CONTAINER_QUEUE || (echo '⚠️ Queue container not found, restarting app only' && docker restart $CONTAINER_APP))"

# 8. Reactivar Sitio
echo "✅ 8/8 Desactivando mantenimiento..."
ssh $USER@$VPS_IP "docker exec $CONTAINER_APP php artisan up"

echo "--------------------------------------------------------"
echo "✨ ¡DESPLIEGUE ASISTENCIA VIRCOM COMPLETADO! (v$NEXT_VERSION) ✨"
echo "--------------------------------------------------------"
