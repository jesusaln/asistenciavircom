# Plan de Organización y Optimización del Proyecto (CDD Climas)

Este plan divide las mejoras necesarias en fases para no comprometer la estabilidad de la aplicación actual.

## Fase 1: Limpieza de Código y Estructura (Inmediata)
- [x] **Estandarización de Controladores**: Mover lógica de negocio de los Controladores a **Services** o **Actions**. Actualmente algunos controladores tienen demasiadas responsabilidades. (Ventas refactorizado ✅)
- [ ] **Limpieza de Rutas**: Agrupar rutas en archivos separados (ej. `admin.php`, `api.php`, `public.php`) si `web.php` está muy saturado.
- [ ] **Eliminación de Vistas Huérfanas**: Identificar y borrar archivos `.vue` o `.blade.php` que ya no se importan en ninguna parte.

## Fase 2: Mejora de Base de Datos y Cache (Medio Plazo)
- [ ] **Optimización de Índices**: Revisar tablas con muchos datos (como `citas` o `clientes`) y asegurar que las columnas de búsqueda tengan índices.
- [ ] **Estrategia de Backup Automático**: Configurar un cronjob en el VPS para que el respaldo sea diario y no manual.
- [ ] **Monitoreo de Logs**: Configurar una herramienta (o un script simple) que me alerte si el archivo `laravel.log` crece demasiado rápido por errores repetitivos.

## Fase 3: Automatización y Despliegue (Avanzado)
- [ ] **Workflow de CI/CD**: Configurar GitHub Actions o herramientas en Coolify para que los tests se corran solos antes de cada despliegue.
- [ ] **Documentación de API**: Generar un Swagger o archivo centralizado de endpoints si la app crece mucho.
- [ ] **Poda de Imágenes**: Script para limpiar imágenes de `storage/` que ya no estén referenciadas en la base de datos.

---
*Creado el 12 de Enero de 2026. Este archivo servirá como nuestra hoja de ruta.*
