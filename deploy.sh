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
CONTAINER_APP="asistenciavircom-app-v3"
CONTAINER_QUEUE="asistenciavircom-queue-v3"
CONTAINER_WEB="asistenciavircom-web-v3"

echo "--------------------------------------------------------"
echo "🚀 Iniciando Despliegue Robusto - ASISTENCIA VIRCOM..."
echo "--------------------------------------------------------"

# Preflight: detectar contenedor real para comandos Artisan.
# En este VPS Laravel vive en queue-v3 (web-v3 es nginx).
echo "🔎 Verificando contenedor de aplicación..."
DETECTED_APP_CONTAINER=$(ssh $USER@$VPS_IP "for c in '$CONTAINER_APP' '$CONTAINER_QUEUE'; do docker ps --format '{{.Names}}' | grep -Fx \"\$c\" >/dev/null && { echo \"\$c\"; break; }; done")
if [ -z "$DETECTED_APP_CONTAINER" ]; then
    echo "❌ No se encontró contenedor Laravel activo (ni $CONTAINER_APP ni $CONTAINER_QUEUE)."
    exit 1
fi
CONTAINER_APP="$DETECTED_APP_CONTAINER"
echo "✅ Contenedor Laravel detectado: $CONTAINER_APP"

# 1. Incremento de Versión, Commit y Push
echo "📝 1/8 Control de Versiones y Sincronización..."
# Intento de autodetectar versión (Fallback a timestamp si falla grep)
LAST_VERSION=$(git log --grep="Version [0-9]*" -n 1 --format=%s 2>/dev/null | grep -o "Version [0-9]*" | grep -o "[0-9]*" | head -n 1 || echo 0)
NEXT_VERSION=$((LAST_VERSION + 1))

echo "📌 Nueva Versión Detectada: $NEXT_VERSION"

# Agregar, commit y push (Opcional, pero recomendado para trazabilidad)
git add .
git commit -m "Version $NEXT_VERSION - Auto Deploy" || echo "No hay cambios para commit."
echo "📤 Cambios guardados localmente."

echo "📤 Sincronizando con Repositorio Remoto..."
git push origin main || echo "⚠️ Falló el push, continuando con despliegue local..."

# 2. Construcción de Assets
echo "📦 2/8 Construyendo Assets (Vite Production Build)..."
npm run build

# 3. Preparar Modo Mantenimiento
echo "🚧 3/8 Activando modo mantenimiento..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && docker exec $CONTAINER_APP php artisan down || true"

# 4. Limpieza de Logs y Caché Local (Opcional)
# rm -rf storage/logs/*.log

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
    --exclude='storage/app/public/*' \
    --exclude='public/storage' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.idea' \
    --exclude='.vscode' \
    --exclude='tests' \
    --exclude='ia_sync' \
    --exclude='clawd' \
    --exclude='.clawdbot' \
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
    docker exec $CONTAINER_APP rm -f public/hot && \
    
    # 4. Asegurar que clawdbot esté instalado en el contenedor
    echo '🤖 Asegurando Clawdbot en el contenedor...' && \
    docker exec -u root $CONTAINER_APP npm install -g clawdbot"

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

    # Asegurar enlace simbólico de Storage (Critico para logos/imágenes)
    docker exec $CONTAINER_APP php artisan storage:link --force && \
    
    # Ejecutar Migraciones (Force required in prod)
    docker exec $CONTAINER_APP php artisan migrate --force && \
    
    # Reiniciar colas y servicios
    echo '🔄 Reiniciando Queue Workers...' && \
    (docker restart $CONTAINER_QUEUE || (echo '⚠️ Queue container not found, restarting app only' && docker restart $CONTAINER_APP))"

# 8. Sincronización de IA (Clawdbot)
echo "🤖 9/8 Sincronizando Cerebro de IA..."
mkdir -p ia_sync
cp -r ./.clawdbot ia_sync/
cp -r ./clawd ia_sync/
rsync -avz --exclude='*.log' ./ia_sync $USER@$VPS_IP:$REMOTE_PATH/
ssh $USER@$VPS_IP "docker cp $REMOTE_PATH/ia_sync/.clawdbot $CONTAINER_APP:/var/www/cdd_app/ && \
    docker cp $REMOTE_PATH/ia_sync/clawd $CONTAINER_APP:/var/www/cdd_app/ && \
    docker exec -u root $CONTAINER_APP chown -R www-data:www-data /var/www/cdd_app/.clawdbot /var/www/cdd_app/clawd"

ssh $USER@$VPS_IP "docker exec -d $CONTAINER_APP bash -c 'HOME=/var/www/cdd_app clawdbot gateway > /var/www/cdd_app/storage/logs/gateway.log 2>&1' || true"

# 10. Reactivar Sitio
echo "✅ 10/10 Desactivando mantenimiento..."
ssh $USER@$VPS_IP "docker exec $CONTAINER_APP php artisan up"

echo "--------------------------------------------------------"
echo "✨ ¡DESPLIEGUE ASISTENCIA VIRCOM COMPLETADO! (v$NEXT_VERSION) ✨"
echo "--------------------------------------------------------"
