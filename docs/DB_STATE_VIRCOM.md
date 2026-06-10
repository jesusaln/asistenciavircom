| Table Scheme | Last Migration | Description |
|---|---|---|
| asistenciavircom_db_data_v2 | 2026_01_16_192656 | Base de datos principal de Vircom (Restaurada desde backup) |
| asistenciavircom-app-v3 | 2026_01_17_213610 | Código de la aplicación en producción (tiene migraciones más nuevas) |

### Estado Actual:
- **Base de Datos:** Contiene datos hasta el 16 de Enero (aprox 19:26).
- **Código:** Tiene migraciones creadas el 17 de Enero (ej: `add_destacado_to_productos`).
- **Conflicto:** Al correr `migrate`, falla porque cree que algunas tablas ya existen (ej: `ticket_comments`), impidiendo que corran las NUEVAS columnas (ej: `destacado`).

### Migraciones Faltantes (Pendientes de aplicar en DB):
1. `2026_01_17_202818_add_destacado_to_productos_table` (Falta columna `destacado` en tabla `productos`). **SOLUCIONADO MANUALMENTE**.
2. Posibles otras columnas de configuraciones o clientes que falten.

### Solución Aplicada:
- Se agregó manualmente la columna `destacado` a `productos`.
- Se insertó el registro de migración para evitar duplicados futuros.
