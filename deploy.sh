#!/bin/bash
# ==============================================================================
# SCRIPT DE DESPLIEGUE ATÓMICO - ASISTENCIA VIRCOM
# Minimiza el downtime aplicando sincronización por etapas (Staging -> Live)
# ==============================================================================

set -e

# Configuración del Servidor
VPS_IP="191.101.233.82"
USER="root"
REMOTE_PATH="/root/asistenciavircom"
STAGING_PATH="/root/asistenciavircom_staging"

# Nombres de Contenedores
CONTAINER_APP="asistenciavircom-queue-v3"
CONTAINER_QUEUE="asistenciavircom-queue-v3"

echo "--------------------------------------------------------"
echo "🚀 Iniciando Despliegue Atómico - ASISTENCIA VIRCOM..."
echo "--------------------------------------------------------"

# 0. Preparar STAGING remoto e identificar contenedor
echo "🔎 Verificando servidor y preparando entorno..."
ssh $USER@$VPS_IP "mkdir -p $STAGING_PATH && mkdir -p $REMOTE_PATH"

DETECTED_APP_CONTAINER=$(ssh $USER@$VPS_IP "for c in '$CONTAINER_APP' '$CONTAINER_QUEUE'; do docker ps --format '{{.Names}}' | grep -Fx \"\$c\" >/dev/null && { echo \"\$c\"; break; }; done")
if [ -z "$DETECTED_APP_CONTAINER" ]; then
    echo "❌ No se encontró contenedor Laravel activo."
    exit 1
fi
CONTAINER_APP="$DETECTED_APP_CONTAINER"
echo "✅ Contenedor Laravel detectado: $CONTAINER_APP"

# 1. Control de Versiones
echo "📝 1/8 Sincronizando Git..."
LAST_VERSION=$(git log --grep="Version [0-9]*" -n 1 --format=%s 2>/dev/null | grep -o "Version [0-9]*" | grep -o "[0-9]*" | head -n 1 || echo 0)
NEXT_VERSION=$((LAST_VERSION + 1))
git add .
git commit -m "Version $NEXT_VERSION - Atomic Deploy" || echo "Sin cambios nuevos."
git push origin main || echo "⚠️ Falló el push, continuando localmente..."

# 2. Construcción de Assets Local
echo "📦 2/8 Construyendo Assets (Vite Production)..."
nice -n 19 npm run build
rm -f public/hot

# 3. Sincronización a STAGING (Página sigue ONLINE)
echo "📡 3/8 Enviando archivos a STAGING (Carga en segundo plano)..."
rsync -avz --no-perms --no-owner --no-group --delete \
    --exclude='.env' \
    --exclude='storage/*.key' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='public/storage' \
    --exclude='public/hot' \
    --exclude='vendor' \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='ia_sync' \
    --exclude='clawd' \
    --exclude='.clawdbot' \
    ./ $USER@$VPS_IP:$STAGING_PATH/

# 4. Preparación de Dependencias en Staging
echo "🔐 4/8 Instalando dependencias en STAGING..."
ssh $USER@$VPS_IP "cp $REMOTE_PATH/.env $STAGING_PATH/.env || true && \
    docker exec $CONTAINER_APP bash -c 'cd /var/www/cdd_app && composer install --optimize-autoloader --no-dev --no-interaction'"

# 5. EL MOMENTO DE LA VERDAD (Downtime mínimo inicia aquí)
echo "🚧 5/8 ACTIVANDO MODO MANTENIMIENTO E INTERCAMBIO ATÓMICO..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    docker exec $CONTAINER_APP php artisan down --retry=60 || true && \
    
    # Intercambio de archivos (Súper rápido porque es local en el VPS)
    # Excluimos storage del borrado para no matar archivos subidos por usuarios
    rsync -a --delete --exclude='storage' $STAGING_PATH/ $REMOTE_PATH/ && \
    # Sincronizamos los assets estáticos de storage (landing, logos) sin borrar lo demás
    rsync -a $STAGING_PATH/storage/ $REMOTE_PATH/storage/ && \
    
    # Asegurar permisos
    chmod -R 775 storage bootstrap/cache && \
    chown -R root:www-data storage bootstrap/cache || true"

# 6. Ejecución de Tareas de Laravel
echo "⚙️ 6/8 Optimizando y Migrando..."
ssh $USER@$VPS_IP "cd $REMOTE_PATH && \
    docker exec $CONTAINER_APP php artisan optimize:clear && \
    docker exec $CONTAINER_APP php artisan config:cache && \
    docker exec $CONTAINER_APP php artisan route:cache && \
    docker exec $CONTAINER_APP php artisan view:cache && \
    docker exec $CONTAINER_APP php artisan storage:link --force && \
    
    # Migraciones Críticas
    docker exec $CONTAINER_APP php artisan migrate --force"

# 7. Reiniciar colas y Reactivar
echo "🔄 7/8 Reiniciando servicios y Reactivando sitio..."
ssh $USER@$VPS_IP "docker restart $CONTAINER_QUEUE && \
    # Asegurar que PHP-FPM esté corriendo (Crítico en este VPS)
    docker exec $CONTAINER_APP sh -lc 'php-fpm -D || php-fpm8.2 -D || true' && \
    docker exec $CONTAINER_APP php artisan up"

# 8. Sincronización de IA (Segundo plano)
echo "🤖 8/8 Sincronizando componente IA..."
rsync -avz --delete --exclude='*.log' ./.clawdbot ./clawd $USER@$VPS_IP:$REMOTE_PATH/ia_staging/ || true
ssh $USER@$VPS_IP "docker cp $REMOTE_PATH/ia_staging/.clawdbot $CONTAINER_APP:/var/www/cdd_app/ && \
    docker cp $REMOTE_PATH/ia_staging/clawd $CONTAINER_APP:/var/www/cdd_app/ && \
    docker exec -u root $CONTAINER_APP chown -R www-data:www-data /var/www/cdd_app/.clawdbot /var/www/cdd_app/clawd"

echo "--------------------------------------------------------"
echo "✨ ¡DESPLIEGUE COMPLETADO EN SEGUNDOS! (v$NEXT_VERSION) ✨"
echo "--------------------------------------------------------"
