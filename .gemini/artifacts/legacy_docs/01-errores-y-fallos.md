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
