# Análisis de Errores Críticos (HTTP 500 y Otros)

Este documento detalla los fallos críticos identificados en los registros del sistema, categorizados por su impacto y origen.

## 1. Conectividad y Base de Datos (Crítico)
**Error:** `SQLSTATE[08006] [7] (PDOException)`
- **Descripción:** El sistema no pudo establecer una conexión con el servidor PostgreSQL.
- **Impacto:** Bloqueo total de la aplicación. Cualquier operación que requiera persistencia o consulta fallará con un error 500.
- **Causa probable:** Tiempo de espera de conexión agotado, servicio de base de datos caído o credenciales incorrectas en el archivo `.env`.
- **Estado actual:** Parece haber sido un evento transitorio seguido de recuperaciones exitosas, pero requiere monitoreo de estabilidad.

## 2. Compilación de Assets (Alta Prioridad)
**Error:** `Vite manifest not found at: .../public/build/manifest.json`
- **Descripción:** Laravel no encuentra el manifiesto de Vite para inyectar scripts y estilos.
- **Impacto:** La interfaz de usuario no carga o se muestra sin estilos. Al ser una aplicación Inertia/Vue, esto resulta habitualmente en una pantalla blanca o un error 500 al intentar resolver la ruta.
- **Acción:** Ejecutar `npm run build` o asegurarse de que `npm run dev` esté activo y accesible.

## 3. Pasarelas de Pago (Bloqueo Comercial)
Se han detectado errores concurrentes en los 3 métodos de pago integrados:

### A. PayPal
- **Error:** `invalid_client` (Authentication failed).
- **Causa:** Credenciales (Client ID o Secret) inválidas, expiradas o correspondientes a un entorno (Sandbox/Live) incorrecto.

### B. MercadoPago
- **Error:** `PA_UNAUTHORIZED_RESULT_FROM_POLICIES`.
- **Causa:** Bloqueo por políticas de seguridad o falta de permisos en el Access Token utilizado.

### C. Stripe
- **Error:** `invalid_request_error`.
- **Causa:** Parámetros inválidos en la creación de sesiones de checkout.

## 4. Gestión de Archivos y Proxy de Imágenes
**Error:** `Image Proxy Exception: HTTP request returned status code 404`
- **Descripción:** Fallo al intentar cargar imágenes de perfil o recursos externos a través del proxy interno.
- **Causa:** Referencias a archivos que ya no existen en el almacenamiento local o remoto.

## 5. Errores de Sintaxis y Tiempo de Ejecución
**Error:** `PHP Parse error: Syntax error, unexpected T_NAME_FULLY_QUALIFIED`
- **Descripción:** Errores detectados en `PsyShell` (tinker).
- **Impacto:** Bajo (afecta solo a herramientas de depuración), pero indica fragmentos de código mal formados durante el desarrollo manual.

---

## Recomendaciones Inmediatas
1. **Verificar Conexión DB:** Asegurar que el host de la base de datos sea alcanzable y no tenga límites de conexión saturados.
2. **Ciclo de Build:** Ejecutar un build completo de frontend para regenerar el manifiesto de Vite.
3. **Auditoría de Credenciales:** Revisar y actualizar tokens de API para PayPal y MercadoPago en el entorno de producción/staging.
4. **Limpieza de Logs:** Rotar el archivo `laravel.log` para facilitar el monitoreo de nuevos incidentes después de aplicar parches.
