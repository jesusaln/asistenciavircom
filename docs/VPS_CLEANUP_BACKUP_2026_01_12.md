# Registro de Limpieza y Respaldos del VPS (12 Enero 2026)

Se realizó una limpieza de contenedores detenidos y duplicados en el VPS `191.101.233.82`.

## Contenedores Eliminados
Se eliminaron los siguientes contenedores que estaban en estado `Exited` (detenidos desde hace 13 días) y que parecían ser versiones antiguas o duplicados de la aplicación CDD Climas:

1. `cdd_climas_queue`
2. `cdd_climas_web`
3. `cdd_climas_php`
4. `dgc4wsk44gs0kcoowsogggcw-cdd_queue-1`
5. `dgc4wsk44gs0kcoowsogggcw-cdd_web-1`
6. `dgc4wsk44gs0kcoowsogggcw-cdd_php-1`

## Respaldos Creados
Antes de la eliminación, se creó una imagen de respaldo de cada contenedor. Estas imágenes se pueden usar para restaurar el estado exacto del contenedor si es necesario.

**Nombres de las imágenes de respaldo en el VPS:**
- `backup_cdd_climas_queue_2026_01_12`
- `backup_cdd_climas_web_2026_01_12`
- `backup_cdd_climas_php_2026_01_12`
- `backup_dgc4wsk44gs0kcoowsogggcw-cdd_queue-1_2026_01_12`
- `backup_dgc4wsk44gs0kcoowsogggcw-cdd_web-1_2026_01_12`
- `backup_dgc4wsk44gs0kcoowsogggcw-cdd_php-1_2026_01_12`

## Fecha de Expiración recomendada
**19 de Enero de 2026**: Si no se presentan problemas en el funcionamiento de la aplicación actual (gestionada por Coolify) después de una semana, estos respaldos pueden ser eliminados con el comando:
`docker rmi [nombre_de_la_imagen]`

---
*Nota: Este archivo fue generado automáticamente para seguimiento del mantenimiento del servidor.*
