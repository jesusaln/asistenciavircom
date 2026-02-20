#!/bin/bash
# ==============================================================================
# SCRIPT DE DESPLIEGUE ROBUSTO - ASISTENCIA VIRCOM
# Previene errores y asegura consistencia en producción
# ==============================================================================

set -e

# Configuración del Servidor
VPS_IP="191.101.233.82" 
USER="root"
REMOTE_PATH="/root/asistenciavircom"

# Nombres de Contenedores
CONTAINER_APP="asistenciavircom-app-v3"
CONTAINER_QUEUE="asistenciavircom-queue-v3"
CONTAINER_WEB="asistenciavircom-web-v3"

echo "--------------------------------------------------------"
echo "🚀 Iniciando Despliegue - ASISTENCIA VIRCOM..."
echo "--------------------------------------------------------"

# 1. Incremento de Versión, Commit y Push
echo "📝 1/8 Control de Versiones..."
LAST_VERSION=$(git log --grep="Version [0-9]*" -n 1 --format=%s 2>/dev/null | grep -o "Version [0-9]*" | grep -o "[0-9]*" | head -n 1 || echo 0)
NEXT_VERSION=$((LAST_VERSION + 1))

echo "📌 Nueva Versión: $NEXT_VERSION"

git add .
git commit -m "Version $NEXT_VERSION - Auto Deploy" || echo "No hay cambios para commit."

echo "📤 Sincronizando con Repositorio Remoto..."
git push origin main || echo "⚠️ Falló el push, continuando con despliegue local..."

# 2. Construcción de Assets
echo "📦 2/8 Construyendo Assets (Vite Production Build)..."
npm run build

# 3. Preparar Modo Mantenimiento
echo "🚧 3/8 Activando modo mantenimiento..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && docker exec $CONTAINER_APP php artisan down || true"

# 4. Sincronización de Archivos (Rsync)
echo "📡 4/8 Enviando archivos vía Rsync..."
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

# 5. Dependencias PHP
echo "🔐 5/8 Instalando dependencias..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    chmod -R 775 storage bootstrap/cache && \
    chown -R root:www-data storage bootstrap/cache || true && \
    docker exec $CONTAINER_APP composer install --optimize-autoloader --no-dev --no-interaction && \
    docker exec $CONTAINER_APP rm -f public/hot"

# 6. Tareas de Laravel (SIN reiniciar contenedores)
echo "⚙️ 6/8 Optimizando y Migrando..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    docker exec $CONTAINER_APP php artisan optimize:clear && \
    docker exec $CONTAINER_APP php artisan storage:link --force && \
    docker exec $CONTAINER_APP php artisan migrate --force && \
    docker exec $CONTAINER_APP php artisan queue:restart"

# 7. Sincronización de IA (Clawdbot)
echo "🤖 7/8 Sincronizando Cerebro de IA..."
mkdir -p ia_sync
cp -r ./.clawdbot ia_sync/ 2>/dev/null || true
cp -r ./clawd ia_sync/ 2>/dev/null || true
rsync -avz --exclude='*.log' ./ia_sync $USER@$VPS_IP:$REMOTE_PATH/ 2>/dev/null || true
ssh $USER@$VPS_IP "docker cp $REMOTE_PATH/ia_sync/.clawdbot $CONTAINER_APP:/var/www/cdd_app/ 2>/dev/null && \
    docker cp $REMOTE_PATH/ia_sync/clawd $CONTAINER_APP:/var/www/cdd_app/ 2>/dev/null && \
    docker exec -u root $CONTAINER_APP chown -R www-data:www-data /var/www/cdd_app/.clawdbot /var/www/cdd_app/clawd 2>/dev/null || true"

# 8. Reactivar Sitio
echo "✅ 8/8 Desactivando mantenimiento..."
ssh $USER@$VPS_IP "docker exec $CONTAINER_APP php artisan up"

echo "--------------------------------------------------------"
echo "✨ ¡DESPLIEGUE ASISTENCIA VIRCOM v$NEXT_VERSION COMPLETADO! ✨"
echo "--------------------------------------------------------"
