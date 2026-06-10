# Lista de tareas - Division de controladores (mayor a menor)

## Estado actual
- Iniciado con `CompraController`: se movieron endpoints de CFDI a `CompraCfdiController`.
- Se movieron series a `CompraSeriesController`.
- Se movieron cancelaciones y force delete a `CompraEstadoController`.
- Se inició `PedidoController`: estados, venta, acciones y documentos separados.
- Se inició `CotizacionController`: conversion, documentos, acciones y borrador separados.
- Se inició `VentaController`: documentos y estado separados.
- Se inició `EmpresaConfiguracionController`: apariencia, email y certificados/PAC separados.
- Se separaron updates por tab de configuracion (general, documentos, impuestos, bancarios, sistema, seguridad, red, tienda, cobros y pagos).

## Plan por controlador (ordenado)

1) CompraController (115KB)
- Mover CFDI/parse/import a `CompraCfdiController` (hecho).
- Mover series a `CompraSeriesController` (hecho).
- Mover cancelaciones y force a `CompraEstadoController` (hecho).
- Extraer stats/filters/query a `CompraQueryService`.

2) PedidoController (87KB)
- Separar Index/Query/Transform a `PedidoQueryService`.
- Crear `PedidoEstadoController` para confirmar/cancelar (hecho).
- Crear `PedidoVentaController` para enviar a venta (hecho).
- Crear `PedidoAccionController` para duplicar (hecho).
- Crear `PedidoDocumentoController` (PDF/ticket/email) (hecho).

3) CotizacionController (84KB)
- Crear `CotizacionConversionController` (hecho).
- Crear `CotizacionDocumentoController` (hecho).
- Crear `CotizacionAccionController` (hecho).
- Crear `CotizacionBorradorController` (hecho).
- Extraer query/filters a service.

4) VentaController (73KB)
- Separar pagos, CFDI y documentos.
- Crear `VentaDocumentoController` (hecho).
- Crear `VentaEstadoController` (hecho).
- Extraer query/transform a service.

5) EmpresaConfiguracionController (10KB)
- Separar por tabs: perfil, comunicaciones, oauth, pagos, backups, seguridad.
- Crear `AparienciaConfigController` (hecho).
- Crear `EmailConfigController` (hecho).
- Crear `CertificadosConfigController` (hecho).
- Crear `GeneralConfigController` (hecho).
- Crear `DocumentosConfigController` (hecho).
- Crear `ImpuestosConfigController` (hecho).
- Crear `BancariosConfigController` (hecho).
- Crear `SistemaConfigController` (hecho).
- Crear `SeguridadConfigController` (hecho).
- Crear `RedConfigController` (hecho).
- Crear `TiendaConfigController` (hecho).
- Crear `CobrosConfigController` (hecho).
- Crear `PagosConfigController` (hecho).
- Centralizar secretos en `EmpresaSecretsService`.

6) ReporteController (72KB)
- Dividir por dominio: ventas, inventario, finanzas.
- Crear `ReporteExportController`.

7) CfdiController (63KB)
- Separar emision/cancelacion/descargas.
- Mover notificaciones a controller dedicado.

8) OrdenCompraController (55KB)
- Separar estado/aprobaciones/documentos.
- Extraer query/filters a service.

9) ClienteController (49KB)
- Separar import/export y catalogos.
- Extraer catalogos SAT a service.

10) ProductoController (48KB)
- Separar series, stock, export.

## Criterio de avance
- Cada split debe actualizar rutas y mantener comportamiento.
- Commits pequenos por controlador.
- Actualizar docs/README_CONTROLADORES.md al cerrar cada split.
