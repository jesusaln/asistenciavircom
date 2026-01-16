---
description: Desplegar Asistencia Vircom (Laravel/Vue) a Producción
---

Este workflow automatiza el despliegue de la aplicación Laravel `asistenciavircom` en el VPS.
Utiliza el repositorio git en `/root/asistenciavircom` y los contenedores Docker existentes.

### Pasos de Despliegue

// turbo
1. Actualizar código desde GitHub:
   ```bash
   ssh root@191.101.233.82 "cd /root/asistenciavircom && git stash && git pull origin main"
   ```

// turbo
2. Instalar dependencias de Frontend y Compilar (Vite/Tailwind):
   ```bash
   ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 npm install && docker exec asistenciavircom-app-1 npm run build"
   ```

// turbo
3. Ejecutar Migraciones y Limpiar Caché:
   ```bash
   ssh root@191.101.233.82 "docker exec asistenciavircom-app-1 php artisan migrate --force && docker exec asistenciavircom-app-1 php artisan optimize:clear"
   ```

// turbo
4. Reiniciar Servicios (Colas):
   ```bash
   ssh root@191.101.233.82 "docker restart asistenciavircom-queue-1"
   ```

### Verificación
- Visita: https://app.asistenciavircom.com/
- Verifica que los cambios visuales (ej. textos de Landing) se reflejen.
