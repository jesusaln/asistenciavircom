---
description: Desplegar cambios a producción para Asistencia Vircom y Climas del Desierto
---

Este workflow describe cómo desplegar cambios a los servidores de producción.

## Método Rápido (Recomendado para Climas del Desierto)

// turbo
1. Ejecuta el script de deploy automatizado:
```bash
./deploy.sh
```

Este script hace todo automáticamente:
- Build de assets con npm
- Fix del manifest.json para Vite 5
- Sincronización de archivos al VPS
- Limpieza de caches en Docker
- Opcionalmente ejecuta migraciones

---

## Método Manual (Paso a Paso)

### Paso 1: Realizar Build localmente
// turbo
1. Ejecuta el build de assets:
```bash
npm run build && cp public/build/.vite/manifest.json public/build/manifest.json
```

### Paso 2: Sincronizar archivos al VPS

// turbo
1. Sincroniza las carpetas principales a **Climas del Desierto** (admin.climasdeldesierto.com):
```bash
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./app/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/app/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./config/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/config/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./database/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/database/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./public/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/public/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./resources/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/resources/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./routes/ root@admin.climasdeldesierto.com:/root/climasdeldesierto/routes/
```

// turbo
2. Sincroniza las carpetas principales a **Asistencia Vircom** (admin.asistenciavircom.com):
```bash
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./app/ root@admin.asistenciavircom.com:/root/cdd_app_vps/app/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./config/ root@admin.asistenciavircom.com:/root/cdd_app_vps/config/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./database/ root@admin.asistenciavircom.com:/root/cdd_app_vps/database/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./public/ root@admin.asistenciavircom.com:/root/cdd_app_vps/public/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./resources/ root@admin.asistenciavircom.com:/root/cdd_app_vps/resources/ && \
rsync -avz --exclude 'node_modules' --exclude 'vendor' --exclude '.git' --exclude 'storage' --exclude '.env' -e "ssh -i ~/.ssh/id_rsa" ./routes/ root@admin.asistenciavircom.com:/root/cdd_app_vps/routes/
```

### Paso 3: Fix Vite Manifest y Clear Caches

// turbo
1. Fix manifest y clear caches en **Climas**:
```bash
ssh -i ~/.ssh/id_rsa root@admin.climasdeldesierto.com 'docker exec climasdeldesierto-app-1 cp /var/www/cdd_app/public/build/.vite/manifest.json /var/www/cdd_app/public/build/manifest.json && docker exec climasdeldesierto-app-1 php artisan optimize:clear'
```

// turbo
2. Fix manifest y clear caches en **Vircom**:
```bash
ssh -i ~/.ssh/id_rsa root@admin.asistenciavircom.com 'docker exec cdd_app_vps-app-1 cp /var/www/cdd_app/public/build/.vite/manifest.json /var/www/cdd_app/public/build/manifest.json 2>/dev/null || true && docker exec cdd_app_vps-app-1 php artisan optimize:clear'
```

### Paso 4 (Opcional): Ejecutar Migraciones

// turbo
1. Migraciones en **Climas**:
```bash
ssh -i ~/.ssh/id_rsa root@admin.climasdeldesierto.com 'docker exec climasdeldesierto-app-1 php artisan migrate --force'
```

// turbo
2. Migraciones en **Vircom**:
```bash
ssh -i ~/.ssh/id_rsa root@admin.asistenciavircom.com 'docker exec cdd_app_vps-app-1 php artisan migrate --force'
```

---

## Resolución de Problemas Comunes

### Error: Vite manifest not found
El manifest.json de Vite 5 está en `.vite/manifest.json` pero Laravel lo busca en `build/manifest.json`. Solución:
```bash
docker exec CONTAINER_NAME cp /var/www/cdd_app/public/build/.vite/manifest.json /var/www/cdd_app/public/build/manifest.json
```

### Conflicto de Migración (Tabla existente)
Si una migración falla porque la tabla ya existe, marca la migración como ejecutada:
```bash
docker exec app php artisan tinker --execute="DB::table('migrations')->insert(['migration' => 'NOMBRE_DE_LA_MIGRACION', 'batch' => 99]);"
```

### Cache del navegador
Si los cambios no se ven, el usuario debe hacer hard refresh: **Ctrl+Shift+R**
