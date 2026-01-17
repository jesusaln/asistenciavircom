---
description: Plan de despliegue por fases para asistenciavircom (VPS 191.101.233.82)
---

# 🚀 Plan de Despliegue por Fases - Asistencia Vircom

**Fecha:** 16 de Enero de 2026  
**Servidor:** 191.101.233.82 (Coolify)  
**Contenedores:** asistenciavircom-app-1, asistenciavircom-web-1

---

## 📋 Resumen de Cambios

| Área | Cambios |
|------|---------|
| **Rutas** | Nuevas rutas de backup (create-full, delete, restore, etc.) |
| **Frontend** | Fix Dashboard.vue (imports duplicados), nuevos íconos FontAwesome |
| **Permisos** | Permisos de soporte para admin/super-admin |
| **Migraciones** | tickets, cita_items, polizas |
| **Componentes** | DeudaModal.vue para portal cliente |

---

## Fase 1: Preparación (Sin Downtime)

### 1.1 Backup de seguridad
```bash
# Crear backup antes de cualquier cambio
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan db:backup"
```

### 1.2 Verificar estado actual
```bash
# Verificar que todo funciona antes de empezar
ssh root@191.101.233.82 "curl -s -o /dev/null -w '%{http_code}' https://asistenciavircom.com"
# Debería devolver 200
```

---

## Fase 2: Actualizar Rutas (Sin Rebuild)

### 2.1 Copiar archivo de rutas
```bash
scp routes/admin.php root@191.101.233.82:/tmp/admin.php

ssh root@191.101.233.82 "docker cp /tmp/admin.php asistenciavircom-app-1:/var/www/cdd_app/routes/admin.php"
```

### 2.2 Limpiar caché de rutas
```bash
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan route:clear && docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan route:cache"
```

### 2.3 Verificar
```bash
# Verificar que la ruta de backup existe
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan route:list --name=backup | head"
```

**Rollback Fase 2:**
```bash
# Si falla, restaurar rutas anteriores desde git
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 bash -c 'cd /var/www/cdd_app && git checkout routes/admin.php && php artisan route:cache'"
```

---

## Fase 3: Ejecutar Migraciones

### 3.1 Copiar migraciones
```bash
scp database/migrations/2026_01_16_*.php root@191.101.233.82:/tmp/

ssh root@191.101.233.82 "for f in /tmp/2026_01_16_*.php; do docker cp \$f asistenciavircom-app-1:/var/www/cdd_app/database/migrations/; done"
```

### 3.2 Ejecutar migraciones (una por una para control)
```bash
# Ver migraciones pendientes
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan migrate:status"

# Ejecutar migraciones
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan migrate --force"
```

**Rollback Fase 3:**
```bash
# Revertir última migración
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan migrate:rollback --step=1"
```

---

## Fase 4: Actualizar Permisos (Base de Datos)

### 4.1 Crear permisos de soporte
```bash
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan tinker --execute=\"
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

\\\$permisos = ['view soporte', 'create soporte', 'edit soporte', 'delete soporte'];
foreach(\\\$permisos as \\\$p) { Permission::firstOrCreate(['name' => \\\$p, 'guard_name' => 'web']); }

\\\$roles = Role::whereIn('name', ['admin', 'super-admin'])->get();
foreach(\\\$roles as \\\$r) { \\\$r->givePermissionTo(\\\$permisos); }
echo 'Permisos creados';
\""
```

### 4.2 Limpiar caché de permisos
```bash
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan permission:cache-reset"
```

---

## Fase 5: Actualizar Frontend (Requiere Build)

### 5.1 Copiar archivos modificados
```bash
# App.js con nuevos íconos
scp resources/js/app.js root@191.101.233.82:/tmp/app.js
ssh root@191.101.233.82 "docker cp /tmp/app.js asistenciavircom-app-1:/var/www/cdd_app/resources/js/app.js"

# Dashboard.vue corregido
scp resources/js/Pages/Portal/Dashboard.vue root@191.101.233.82:/tmp/Dashboard.vue
ssh root@191.101.233.82 "docker cp /tmp/Dashboard.vue asistenciavircom-app-1:/var/www/cdd_app/resources/js/Pages/Portal/Dashboard.vue"

# DeudaModal.vue nuevo componente
scp resources/js/Pages/Portal/Components/DeudaModal.vue root@191.101.233.82:/tmp/DeudaModal.vue
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 mkdir -p /var/www/cdd_app/resources/js/Pages/Portal/Components"
ssh root@191.101.233.82 "docker cp /tmp/DeudaModal.vue asistenciavircom-app-1:/var/www/cdd_app/resources/js/Pages/Portal/Components/DeudaModal.vue"
```

### 5.2 Reconstruir assets (⚠️ Esto toma ~2-3 minutos)
```bash
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 npm run build --prefix /var/www/cdd_app"
```

### 5.3 Verificar build exitoso
```bash
# Verificar que se generaron los assets
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 ls -la /var/www/cdd_app/public/build/assets/ | tail -5"
```

**Rollback Fase 5:**
```bash
# Si el build falla, restaurar assets desde backup (si tienes)
# O revertir los archivos JS y reconstruir
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 bash -c 'cd /var/www/cdd_app && git checkout resources/js/app.js resources/js/Pages/Portal/Dashboard.vue && npm run build'"
```

---

## Fase 6: Actualizar Controladores

### 6.1 Copiar controladores modificados
```bash
# Lista de controladores a actualizar
CONTROLLERS=(
    "app/Http/Controllers/Api/TicketApiController.php"
    "app/Http/Controllers/CitaController.php"
    "app/Http/Controllers/CompraController.php"
    "app/Http/Controllers/PolizaServicioController.php"
    "app/Http/Controllers/ClientPortal/PortalController.php"
)

for ctrl in "${CONTROLLERS[@]}"; do
    scp "$ctrl" root@191.101.233.82:/tmp/
    ssh root@191.101.233.82 "docker cp /tmp/$(basename $ctrl) asistenciavircom-app-1:/var/www/cdd_app/$ctrl"
done
```

### 6.2 Limpiar caché general
```bash
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan optimize:clear"
```

---

## Fase 7: Verificación Final

### 7.1 Smoke tests
```bash
# Login
curl -s -o /dev/null -w "%{http_code}" https://asistenciavircom.com/login
# Debería ser 200

# Panel
curl -s -o /dev/null -w "%{http_code}" https://asistenciavircom.com/panel
# Debería ser 302 (redirect a login) o 200 si logeado

# Módulo backup
curl -s -o /dev/null -w "%{http_code}" https://asistenciavircom.com/admin/backup
# Debería ser 200 o 302
```

### 7.2 Revisar logs
```bash
ssh root@191.101.233.82 "docker logs asistenciavircom-app-1 --tail 20"
ssh root@191.101.233.82 "docker logs asistenciavircom-web-1 --tail 20"
```

---

## 🚨 Rollback Completo

Si algo sale muy mal, restaurar desde el backup:

```bash
# 1. Restaurar base de datos
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan db:restore [NOMBRE_BACKUP]"

# 2. Restaurar código desde git (última versión estable)
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 bash -c 'cd /var/www/cdd_app && git fetch origin && git reset --hard origin/main~1'"

# 3. Reconstruir assets
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 npm run build --prefix /var/www/cdd_app"

# 4. Limpiar cachés
ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php /var/www/cdd_app/artisan optimize:clear"
```

---

## 📞 Contactos de Emergencia

- **VPS Provider:** Hostinger
- **Gestor:** Coolify (https://191.101.233.82:8000)
- **Repositorio:** https://github.com/jesusaln/asistenciavircom

---

*Última actualización: 16 de Enero 2026*
