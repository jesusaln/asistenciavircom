# Deuda tecnica

## D-01 Controladores muy grandes y con demasiadas responsabilidades
- Archivo/ruta: `docs/INVENTARIO_MODULOS.md`
- Descripción: Hay controladores de gran tamaño (por ejemplo, `CompraController` ~115KB, `CotizacionController` ~84KB, `PedidoController` ~86KB), señal de lógica acoplada y difícil de probar.
- Severidad: Media
- Impacto: Mantenimiento lento, alto costo de cambios y más riesgo de regresiones.
- Sugerencia: Extraer servicios, actions o jobs y dividir por casos de uso.

## D-02 Discrepancia de version de Laravel en documentación
- Archivo/ruta: `README.md`
- Descripción: Se declara Laravel 11, pero en `docs/INVENTARIO_MODULOS.md` se menciona Laravel 10.
- Severidad: Baja
- Impacto: Confusión operativa y errores al seguir instrucciones de despliegue o upgrade.
- Sugerencia: Unificar la versión real en la documentación.

## D-03 Scripts operativos sueltos en la raiz
- Archivo/ruta: `repair_*.php`, `fill_payment_supplements.php`, `sync_stock.php`
- Descripción: Scripts operativos críticos están en la raíz sin encapsular como comandos Artisan ni documentación de uso consistente.
- Severidad: Media
- Impacto: Difícil trazabilidad, ejecución accidental, ausencia de validación y auditoría.
- Sugerencia: Convertir a comandos Artisan con logs y permisos controlados.

## D-04 Configuracion CORS hardcodeada en middleware custom
- Archivo/ruta: `app/Http/Middleware/CorsMiddleware.php`
- Descripción: CORS está implementado manualmente y permisivo, sin un control centralizado por entorno.
- Severidad: Media
- Impacto: Dificulta el cumplimiento de políticas por entorno y el hardening de seguridad.
- Sugerencia: Migrar a configuración central (config/cors.php) y parametrizar por entorno.
