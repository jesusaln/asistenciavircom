# Integración Contpaqi Bridge (CFDI)

Esta aplicación utiliza un Bridge intermedio para comunicarse con Contpaqi Comercial y timbrar documentos CFDI 4.0.

## Configuración (.env)

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `CONTPAQI_ENABLED` | Activa la integración (true/false) | `true` |
| `CONTPAQI_API_URL` | URL del Bridge API | `http://192.168.1.100:5000` |
| `CONTPAQI_RUTA_EMPRESA` | Ruta física de la empresa en el servidor Contpaqi | `C:\Compac\Empresas\adMI_EMPRESA` |
| `CONTPAQI_CSD_PASS` | Contraseña de los certificados CSD (debe ser la misma en Contpaqi) | `MiPassword123` |
| `CONTPAQI_CONCEPT_CODE` | Código del Concepto de Factura en Contpaqi | `4` |
| `CONTPAQI_CONCEPT_CODE_PAGO` | Código del Concepto de Pago (REP) en Contpaqi | `100` |
| `CONTPAQI_CONCEPT_CODE_ANTICIPO` | Código del Concepto para Anticipos | `4` |

## Flujo de Trabajo

### Facturación de Ventas
Cuando `CONTPAQI_ENABLED=true`, el método `CfdiService::facturarVenta` delega la operación a `ContpaqiService::procesarVenta`.
1. Sincroniza el cliente (`syncCliente`).
2. Sincroniza los productos/servicios (`syncItem`).
3. Crea el documento de factura en Contpaqi (`POST /api/Documentos/factura`).
4. Timbra el documento (`POST /api/Documentos/timbrar`).
5. Descarga el XML y crea el registro local en la tabla `cfdis`.

### Complementos de Pago (REP)
Se gestiona a través de `CfdiService::timbrarPago`.
1. Crea un documento de pago vinculado a la factura original (`POST /api/Documentos/pago`).
2. Timbra el pago.

### Cancelación
Se gestiona a través de `CfdiCancelService::cancelar`.
1. Envía la solicitud de cancelación al SAT vía Bridge (`POST /api/Documentos/cancelar`).
2. Actualiza el estatus local y revierte inventario/pagos.

## Dependencias
- `App\Services\ContpaqiService`: Lógica principal de comunicación.
- `App\Services\Cfdi\CfdiPdfService`: Generación de PDF local basada en el XML recibido del Bridge.
- `App\Services\SatConsultaDirectaService`: Consulta de estado SAT (usado como fallback ya que el Bridge no siempre expone este endpoint).
