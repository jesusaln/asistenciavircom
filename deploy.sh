#!/bin/bash
# ==============================================================================
# 🚀 DESPLIEGUE — CLIMAS DEL DESIERTO / ASISTENCIA VIRCOM
#
# ARQUITECTURA (recomendada y válida):
#   • App Ionic / PWA  → contenedor o nginx aparte (ej. ionic_climas-web-1 :3001).
#                        NO forma parte de este repositorio; despliegalo por su
#                        propio pipeline o compose.
#   • Laravel (este repo) → API Sanctum, panel admin Inertia, colas, Reverb.
#                        Este script solo actualiza el backend en $CONTAINER_APP.
#
# El nginx del HOST (ej. proxy_pass a 127.0.0.1:8083) debe apuntar al servicio
# HTTP que sirva Laravel; si solo tienes PHP-FPM :9000, publica un puerto o
# añade nginx delante. Eso no lo hace este script.
#
# Variables opcionales:
#   SKIP_NPM=1   — omite npm run build (más rápido; solo si no tocaste Vite/admin).
# ==============================================================================

# Colores y Estética
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color
BOLD='\033[1m'

set -euo pipefail

# Helpers
function log_step() { echo -e "${YELLOW}${BOLD}$1${NC}"; }
function log_info() { echo -e "${BLUE}$1${NC}"; }
function log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
function log_err() { echo -e "${RED}❌ $1${NC}"; }

# --- Sugerir Hard Refresh ---
function suggest_refresh() {
    echo -e "${YELLOW}${BOLD}⚠️  CONSEJO:${NC} Tras el deploy: ${BOLD}Ctrl + Shift + R${NC} en el navegador (PWA Ionic en otro contenedor si aplica)."
}

# --- Manejo de Errores ---
trap 'echo -e "${RED}❌ El despliegue falló en el último paso. Revisando logs...${NC}"; exit 1' ERR

# Configuración de Conexión
VPS_IP="191.101.233.82"
USER="root"
SSH_OPTS="-o ConnectTimeout=5 -o BatchMode=yes -i ~/.ssh/id_rsa"

# Instancia (climas por defecto)
SITE=${1:-climas}
SKIP_NPM="${SKIP_NPM:-0}"

if [[ "$SITE" == "climas" ]]; then
    REMOTE_PATH="/root/climasdeldesierto"
    CONTAINER_APP="climasdeldesierto-app-1"
    CONTAINER_DB="dgc4wsk44gs0kcoowsogggcw-cdd_db-1"
    CONTAINER_WORKER="climasdeldesierto-worker-1"
    # Stack actual: sin nginx en Docker; Ionic va aparte. No reiniciar contenedor inexistente.
    CONTAINER_WEB=""
    CONTAINER_REVERB="climasdeldesierto-reverb-1"
    WEB_CONFIG_PATH="/etc/nginx/conf.d/default.conf"
    APP_NAME="CLIMAS DEL DESIERTO"
    DB_NAME="cdd_climas"
    SITE_URL="https://admin.climasdeldesierto.com (Laravel) · API https://climasdeldesierto.com · Ionic aparte"
elif [[ "$SITE" == "vircom" ]]; then
    REMOTE_PATH="/root/cdd_app_vps"
    CONTAINER_APP="asistenciavircom-web-v3"
    CONTAINER_DB="asistenciavircom-db-v3"
    CONTAINER_WORKER="asistenciavircom-queue-v3"
    CONTAINER_WEB="asistenciavircom-web-v3"
    CONTAINER_REVERB="asistenciavircom-reverb-v3"
    WEB_CONFIG_PATH="/etc/nginx/conf.d/default.conf"
    APP_NAME="ASISTENCIA VIRCOM"
    DB_NAME="asistencia_vircom"
    SITE_URL="https://admin.asistenciavircom.com"
else
    echo -e "${RED}❌ Error: Sitio '$SITE' no reconocido.${NC} Usa 'climas' o 'vircom'."
    echo "Uso: ./deploy.sh [climas|vircom]"
    echo "Opcional: SKIP_NPM=1 ./deploy.sh climas   (omite compilación Vite)"
    exit 1
fi

echo -e "${BLUE}${BOLD}========================================================${NC}"
echo -e "${BLUE}${BOLD}   🚀 Despliegue backend Laravel ($SITE)                  ${NC}"
echo -e "${BLUE}${BOLD}   Proyecto: $APP_NAME                                  ${NC}"
echo -e "${BLUE}${BOLD}========================================================${NC}"

# 1. Comprobar Conexión
log_step "🔍 1/9 Comprobando conexión al VPS..."
if ! ssh $SSH_OPTS $USER@$VPS_IP "echo 'Conexión OK'" > /dev/null 2>&1; then
    log_err "No se pudo conectar al VPS. Verifica tu llave SSH o conexión VPN."
    exit 1
fi

# 2. Backup de Seguridad
log_step "💾 2/9 Realizando respaldo preventivo de la base de datos..."
ssh $SSH_OPTS $USER@$VPS_IP "set -euo pipefail; mkdir -p \"$REMOTE_PATH/storage/backups\"; \
  TS=\$(date +%Y%m%d_%H%M%S); \
  OUT=\"$REMOTE_PATH/storage/backups/pre_deploy_\${TS}.sql.gz\"; \
  docker exec \"$CONTAINER_DB\" pg_dump -U postgres \"$DB_NAME\" | gzip -9 > \"\$OUT\"; \
  test -s \"\$OUT\"; \
  echo \"Backup OK: \$OUT\"" || log_warn "Backup falló (continuando con precaución)."

# 3. Guardado automático en Git (seguro, sin force-push)
log_step "📝 3/9 Guardando estado en git (si aplica)..."
BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo '')"
CURRENT_REF="$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')"
NEXT_VERSION="$(date +%Y.%m.%d-%H%M%S)"

if [[ -z "$BRANCH" || "$BRANCH" == "HEAD" ]]; then
  log_warn "No pude detectar un branch (detached HEAD). No se hará commit/push automático."
else
  if ! git diff --quiet || ! git diff --cached --quiet; then
    log_info "Cambios detectados. Creando commit de deploy en '$BRANCH'..."
    git add -A
    git restore --staged --worktree=false public/build 2>/dev/null || true
    git restore --staged --worktree=false public/sw.js 2>/dev/null || true
    git restore --staged --worktree=false public/manifest.json 2>/dev/null || true
    git restore --staged --worktree=false public/hot 2>/dev/null || true
    git commit -m "Deploy: ${SITE} ${NEXT_VERSION} (${CURRENT_REF})" || true
    if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
      git push origin "$BRANCH" || log_warn "Falló el push a origin/$BRANCH. El deploy continuará (rsync usa tu workspace local)."
    fi
  else
    log_info "Sin cambios locales: no se crea commit."
  fi
fi

CURRENT_REF="$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')"
log_info "Deploy ref: ${CURRENT_REF} | versión: ${NEXT_VERSION} | sitio: ${SITE}"

# 4–5. Assets Vite (panel Inertia en este repo). Ionic/PWA = otro proyecto.
if [[ "$SKIP_NPM" == "1" ]]; then
    log_step "📦 4–5/9 Omitiendo npm (SKIP_NPM=1) — asume public/build ya generado."
    log_warn "Si cambiaste Vue/CSS del admin, quita SKIP_NPM y vuelve a desplegar."
else
    log_step "📦 4/9 Compilando assets del panel Laravel (Vite)..."
    rm -f public/hot
    npm run build

    log_step "🔧 5/9 Parches Vite 5 / PWA (manifest)..."
    cp public/build/sw.js public/sw.js 2>/dev/null || true
    cp public/build/manifest.webmanifest public/manifest.json 2>/dev/null || true
    cp public/build/.vite/manifest.json public/build/manifest.json 2>/dev/null || true
fi

# 6. Modo Mantenimiento
log_step "🚧 6/9 Modo mantenimiento breve..."
ssh $SSH_OPTS $USER@$VPS_IP "cd $REMOTE_PATH && docker exec $CONTAINER_APP php artisan down --refresh=15 --retry=60 --render=maintenance || true"

# 7. Sincronización
log_step "📡 7/9 Sincronizando código al VPS (rsync)..."
rsync -avz --delete --no-perms --no-owner --no-group \
    --exclude='.env' \
    --exclude='storage/**' \
    --exclude='public/storage' \
    --exclude='public/storage/**' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='.git' \
    ./ $USER@$VPS_IP:$REMOTE_PATH/

# 8. Contenedor: copiar, limpiar caché (robusto si Redis falla), migrar, cachés
log_step "🛠️ 8/9 Actualizando contenedor Laravel..."
ssh $SSH_OPTS $USER@$VPS_IP "cd $REMOTE_PATH && \
    echo '  - Permisos storage / bootstrap/cache...' && \
    docker exec -u 0 $CONTAINER_APP chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    docker exec -u 0 $CONTAINER_APP chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    \
    echo '  - public/storage...' && \
    docker exec -u 0 $CONTAINER_APP rm -rf /var/www/public/storage || true && \
    docker exec -u 0 $CONTAINER_APP mkdir -p /var/www/public && \
    \
    echo '  - docker cp → app...' && \
    docker cp app/. $CONTAINER_APP:/var/www/app/ && \
    docker cp database/. $CONTAINER_APP:/var/www/database/ && \
    docker cp routes/. $CONTAINER_APP:/var/www/routes/ && \
    docker cp config/. $CONTAINER_APP:/var/www/config/ && \
    docker cp resources/. $CONTAINER_APP:/var/www/resources/ && \
    docker cp public/. $CONTAINER_APP:/var/www/public/ && \
    docker cp bootstrap/app.php $CONTAINER_APP:/var/www/bootstrap/app.php && \
    \
    echo '  - Manifest Vite...' && \
    docker exec $CONTAINER_APP mkdir -p /var/www/public/build && \
    docker exec $CONTAINER_APP cp /var/www/public/build/.vite/manifest.json /var/www/public/build/manifest.json 2>/dev/null || true && \
    \
    echo '  - Quitar cachés bootstrap obsoletas (Collision / packages.php)...' && \
    docker exec $CONTAINER_APP sh -c 'rm -f /var/www/bootstrap/cache/packages.php /var/www/bootstrap/cache/services.php /var/www/bootstrap/cache/config.php /var/www/bootstrap/cache/routes*.php /var/www/bootstrap/cache/events.php' || true && \
    \
    echo '  - optimize:clear (si Redis falla, limpieza por partes)...' && \
    (docker exec $CONTAINER_APP php artisan optimize:clear || ( \
      echo '  ⚠️  optimize:clear falló (p. ej. Redis); limpiando por componentes...' && \
      docker exec $CONTAINER_APP php artisan config:clear 2>/dev/null || true && \
      docker exec $CONTAINER_APP php artisan route:clear 2>/dev/null || true && \
      docker exec $CONTAINER_APP php artisan view:clear 2>/dev/null || true && \
      docker exec $CONTAINER_APP php artisan cache:clear 2>/dev/null || true \
    )) && \
    \
    echo '  - Migraciones...' && \
    docker exec $CONTAINER_APP php artisan migrate --force && \
    \
    echo '  - Cachés Laravel...' && \
    docker exec $CONTAINER_APP php artisan config:cache || true && \
    docker exec $CONTAINER_APP php artisan route:cache || true && \
    docker exec $CONTAINER_APP php artisan view:cache || true && \
    docker exec $CONTAINER_APP php artisan event:cache || true && \
    \
    echo '  - storage:link...' && \
    docker exec -u 0 $CONTAINER_APP mkdir -p /var/www/storage/app/public && \
    docker exec -u 0 $CONTAINER_APP ln -sfn /var/www/storage/app/public /var/www/public/storage || true && \
    docker exec -u 0 $CONTAINER_APP chown -R www-data:www-data /var/www/public/storage /var/www/storage || true && \
    docker exec -u 0 $CONTAINER_APP chmod -R 775 /var/www/public/storage /var/www/storage || true && \
    \
    echo '  - Reinicio worker / reverb...' && \
    docker restart $CONTAINER_WORKER 2>/dev/null || true && \
    docker restart $CONTAINER_REVERB 2>/dev/null || true && \
    if [ -n \"${CONTAINER_WEB}\" ]; then \
      docker inspect \"${CONTAINER_WEB}\" >/dev/null 2>&1 && docker restart \"${CONTAINER_WEB}\" && echo \"  - Web: ${CONTAINER_WEB} reiniciado\" || echo \"  (omitido: ${CONTAINER_WEB} no existe)\"; \
    else \
      echo '  (sin CONTAINER_WEB: nginx/Ionic en otro stack — no se reinicia contenedor web aquí)'; \
    fi"

# 9. Subir aplicación
log_step "✅ 9/9 Quitando mantenimiento..."
ssh $SSH_OPTS $USER@$VPS_IP "cd $REMOTE_PATH && docker exec $CONTAINER_APP php artisan up"
echo -e "${GREEN}${BOLD}¡DESPLIEGUE BACKEND COMPLETADO!${NC}"
echo -e "${BLUE}🔗 $SITE_URL${NC}"
echo -e "${BLUE}📦 Versión: ${NEXT_VERSION} (${CURRENT_REF})${NC}"
echo "--------------------------------------------------------"
suggest_refresh
echo "--------------------------------------------------------"
