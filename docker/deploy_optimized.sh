#!/usr/bin/env bash
# Despliegue optimizado para Climas del Desierto
# Uso:
#   ./docker/deploy_optimized.sh            # build normal con caché
#   NO_CACHE=1 ./docker/deploy_optimized.sh # build sin caché
#   PULL=1 ./docker/deploy_optimized.sh     # fuerza re-pull de bases (imagenes)
set -Eeuo pipefail

### --- Utilidades --- ###
color() { printf "\033[%sm%s\033[0m\n" "$1" "${2:-}"; }
info()  { color "1;34" "ℹ️  $*"; }
ok()    { color "1;32" "✅ $*"; }
warn()  { color "1;33" "⚠️  $*"; }
err()   { color "1;31" "❌ $*"; }

trap 'err "Fallo en línea $LINENO. Revisa la salida arriba."' ERR

# Detectar comando compose (v2 o v1)
if command -v docker &>/dev/null && docker compose version &>/dev/null; then
  COMPOSE="docker compose"
elif command -v docker-compose &>/dev/null; then
  COMPOSE="docker-compose"
else
  err "No encuentro docker compose ni docker-compose"
  exit 1
fi

# Activar BuildKit si es posible
export DOCKER_BUILDKIT="${DOCKER_BUILDKIT:-1}"
export COMPOSE_DOCKER_CLI_BUILD=1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

info "Proyecto: $PROJECT_ROOT"
info "Compose:  $COMPOSE"
info "BuildKit: ${DOCKER_BUILDKIT}"

### --- Prechequeos --- ###
[ -f composer.lock ] || { err "No existe composer.lock (ejecuta 'composer install' en local y commitea)."; exit 1; }
[ -f .env ] || { err "No existe .env (copia/crea uno válido)."; exit 1; }

# Variables mínimas requeridas en .env
REQUIRED_VARS=( DB_DATABASE DB_USERNAME DB_PASSWORD POSTGRES_DB POSTGRES_USER POSTGRES_PASSWORD )
for v in "${REQUIRED_VARS[@]}"; do
  if ! grep -qE "^${v}=" .env; then
    err "Falta ${v}= en .env"
    exit 1
  fi
done

### --- Build --- ###
BUILD_ARGS=( --build-arg APP_ENV=production )
[ "${PULL:-0}" = "1" ] && BUILD_ARGS+=( --pull )
[ "${NO_CACHE:-0}" = "1" ] && BUILD_ARGS+=( --no-cache )

info "Construyendo imágenes (${BUILD_ARGS[*]})…"
$COMPOSE build "${BUILD_ARGS[@]}"

### --- Up --- ###
info "Levantando servicios…"
$COMPOSE up -d

### --- Esperar Postgres (mejor que sleep fijo) --- ###
info "Esperando a que Postgres esté listo…"
# Usamos exec en el contenedor db para pg_isready
for i in {1..40}; do
  if $COMPOSE exec -T db bash -lc 'pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB" -h 127.0.0.1' >/dev/null 2>&1; then
    ok "Postgres listo."
    break
  fi
  sleep 2
  [ "$i" -eq 40 ] && { err "Postgres no respondió a tiempo."; $COMPOSE logs --no-color db | tail -n 120; exit 1; }
done

### --- APP_KEY (si falta o placeholder) --- ###
APP_KEY_LINE="$(grep -E '^APP_KEY=' .env || true)"
if [[ -z "$APP_KEY_LINE" || "$APP_KEY_LINE" =~ REEMPLAZA_CON_TU_APP_KEY || "$APP_KEY_LINE" =~ base64:base64: ]]; then
  info "Generando APP_KEY…"
  KEY="$($COMPOSE exec -T app php artisan key:generate --show | tr -d '\r')"
  if [[ "$KEY" =~ ^base64: ]]; then
    sed -i.bak -E "s/^APP_KEY=.*/APP_KEY=${KEY}/" .env || true
    ok "APP_KEY escrita en .env"
    $COMPOSE restart app queue
  else
    warn "No se pudo leer APP_KEY, continúa manualmente si es necesario."
  fi
fi

### --- Limpieza, migraciones y storage --- ###
info "Limpiando cachés/config y ejecutando migraciones…"
$COMPOSE exec -T app php artisan optimize:clear
$COMPOSE exec -T app php artisan config:clear
$COMPOSE exec -T app php artisan migrate --force
$COMPOSE exec -T app php artisan storage:link || true

### --- Reinicio worker y estado --- ###
info "Reiniciando worker de colas…"
$COMPOSE restart queue

info "Estado de los servicios:"
$COMPOSE ps

ok "¡Despliegue completado!"
echo
info "Servicios:"
echo "  • App:        http://localhost:8080"
echo "  • PostgreSQL: localhost:5433"
echo "  • Redis:      en ejecución"
echo
info "Comandos útiles:"
echo "  • Logs app:    $COMPOSE logs -f app"
echo "  • Logs queue:  $COMPOSE logs -f queue"
echo "  • Reiniciar:   $COMPOSE restart"
echo "  • Detener:     $COMPOSE down"
echo "  • Migrar DB:   $COMPOSE exec app php artisan migrate --force"
