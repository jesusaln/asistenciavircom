# Performance y N+1

## P-01 Validacion de firma WhatsApp O(N) por request
- Archivo/ruta: `app/Http/Controllers/WhatsAppWebhookController.php`
- Descripción: `validateSignature()` carga todas las empresas con WhatsApp habilitado y desencripta secretos en cada webhook.
- Severidad: Media
- Impacto: Escala lineal con el numero de empresas, aumentando latencia en picos.
- Sugerencia: Resolver empresa por `business_account_id` o token y validar una sola firma.

## P-02 Consulta por cada status en webhook
- Archivo/ruta: `app/Http/Controllers/WhatsAppWebhookController.php`
- Descripción: Cada `status` hace un `WhatsAppMessage::where(...)->first()`.
- Severidad: Media
- Impacto: Multiples queries por payload (N+1). En picos de mensajes puede saturar DB.
- Sugerencia: Hacer lookup en batch por `message_id` y mapear en memoria.

## P-03 Carga completa de cuentas por cobrar en memoria
- Archivo/ruta: `app/Http/Controllers/Api/CobranzaApiController.php`
- Descripción: `proximas()` obtiene todas las cuentas del mes y calcula estadisticas en memoria.
- Severidad: Media
- Impacto: Respuesta pesada y alto consumo de memoria si hay muchos registros.
- Sugerencia: Agregar paginacion y usar agregaciones SQL para stats.

## P-04 Endpoints de catalogos sin paginacion
- Archivo/ruta: `app/Http/Controllers/Api/PriceListController.php`
- Descripción: `index()` y `all()` devuelven todas las listas sin paginar.
- Severidad: Baja
- Impacto: Respuestas grandes en crecimiento de datos.
- Sugerencia: Paginar o cachear si el catalogo es estatico.
