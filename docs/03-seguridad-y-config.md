# Errores y fallos

## E-01 Duplicidad de ruta en API de productos
- Archivo/ruta: `routes/api.php`
- Descripción: Se declara dos veces el endpoint `GET /api/productos/next-codigo`, lo que puede provocar conflictos de resolución y ambigüedad en la intención del código.
- Severidad: Media
- Impacto: Comportamiento inesperado en el routing y confusión al mantener la ruta.
- Sugerencia: Eliminar la definición duplicada y mantener una sola ruta documentada.

## E-02 Selección de empresa por `first()` en contexto multi-empresa
- Archivo/ruta: `app/Http/Controllers/EmpresaWhatsAppController.php`
- Descripción: Se usa `Empresa::first()` para cargar y actualizar configuración, ignorando el contexto del usuario/empresa actual (el propio comentario lo indica).
- Severidad: Alta
- Impacto: Modificación de configuración de la empresa equivocada y exposición de datos cruzados entre tenants.
- Sugerencia: Resolver empresa por el usuario autenticado o por el dominio/tenant actual.

## E-03 Endpoint público de prueba WhatsApp devuelve configuración sensible
- Archivo/ruta: `routes/api.php`
- Descripción: El endpoint `POST /api/whatsapp/test` está expuesto sin autenticación y retorna información de configuración (y registra detalles) que deberían estar protegidos.
- Severidad: Alta
- Impacto: Uso indebido del endpoint por terceros, posible abuso de infraestructura y filtración de datos operativos.
- Sugerencia: Proteger el endpoint con `auth:sanctum` y autorización de rol; si debe ser público, limitar con rate limit y validaciones estrictas.

# Seguridad y configuracion

## S-01 CORS permisivo con credenciales
- Archivo/ruta: `app/Http/Middleware/CorsMiddleware.php`
- Descripción: Se permite cualquier origen (`*`) y credenciales (`Access-Control-Allow-Credentials: true`). Esto es una combinación insegura y en algunos navegadores inválida.
- Severidad: Alta
- Impacto: Riesgo de exposición de recursos a orígenes no autorizados y comportamiento inconsistente en navegadores.
- Sugerencia: Restringir orígenes por entorno y deshabilitar credenciales cuando el origen sea `*`.

## S-02 API publica sin autenticacion en recursos sensibles
- Archivo/ruta: `routes/api.php`
- Descripción: Muchas rutas API (clientes, productos, ventas, etc.) están expuestas sin `auth:sanctum` ni autorización de rol.
- Severidad: Alta
- Impacto: Lectura/escritura de datos por usuarios no autenticados.
- Sugerencia: Proteger con `auth:sanctum` y middleware de roles/policies; definir rutas públicas explícitas.

## S-03 Webhook WhatsApp acepta payloads sin firma
- Archivo/ruta: `app/Http/Controllers/WhatsAppWebhookController.php`
- Descripción: Si no viene `X-Hub-Signature-256`, el webhook procesa el payload sin validación.
- Severidad: Alta
- Impacto: Posibilidad de inyección de eventos y estados falsos.
- Sugerencia: Requerir firma válida para procesar requests y responder 403 si falta.

## S-04 Endpoint publico de prueba WhatsApp con logs sensibles
- Archivo/ruta: `routes/api.php`, `app/Http/Controllers/EmpresaWhatsAppController.php`
- Descripción: `POST /api/whatsapp/test` no requiere auth y registra headers y body completos.
- Severidad: Alta
- Impacto: Filtracion de tokens/credenciales en logs y abuso del endpoint.
- Sugerencia: Proteger con auth + rate limit y reducir logs a metadatos no sensibles.

## S-05 Password por defecto en configuracion de DB
- Archivo/ruta: `config/database.php`
- Descripción: Para PostgreSQL se define `DB_PASSWORD` con valor por defecto `Contpaqi1.`.
- Severidad: Alta
- Impacto: Si no se configura `.env`, se corre con credenciales débiles conocidas.
- Sugerencia: Eliminar defaults sensibles y exigir variables en `.env`.

## S-06 Exposicion de secretos en frontend de configuracion
- Archivo/ruta: `app/Http/Controllers/EmpresaWhatsAppController.php`
- Descripción: La vista devuelve `whatsapp_access_token` y `whatsapp_app_secret` al frontend.
- Severidad: Media
- Impacto: Mayor superficie de exposición de credenciales si el panel es comprometido.
- Sugerencia: Enmascarar/ocultar secretos y usar endpoints de update dedicados.
