





---


  
56. **`get()` en sincronización de ventas sin límites.** `app/Console/Commands/SincronizarEstadoVentas.php`.  
57. **`get()` en comando de alertas de stock.** `app/Console/Commands/RevisarAlertasStock.php`.  
58. **`get()` en comandos de recordatorios WhatsApp.** `app/Console/Commands/EnviarRecordatoriosWhatsApp.php`.  
59. **`get()` en reportes mensuales con joins.** `app/Services/Panel/PanelChartsService.php`.  
60. **`->get()` en controladores de admin con listados completos.** `app/Http/Controllers/ProyectoController.php`, `LandingContentController.php`.

---

## 🧩 4) Arquitectura y Mantenibilidad

63. **Controladores muy largos con múltiples responsabilidades.** ✅ **Parcial** (query+stats extraídos a servicio). `app/Http/Controllers/HerramientaController.php`, `app/Services/HerramientaQueryService.php`.  
64. **Controladores con demasiadas rutas en un solo archivo.** ✅ **Parcial** (rutas separadas en módulos). `routes/admin.php`, `routes/admin/crm.php`, `routes/admin/soporte.php`, `routes/admin/empresa.php`.  
65. **Uso de `DB::raw` disperso complica testing.** ✅ **Parcial** (helper centralizado y usos migrados). `app/Support/DbExpression.php`, `app/Http/Controllers/MovimientoManualController.php`, `TraspasoController.php`, `AjusteInventarioController.php`, `CompraCfdiController.php`, `CuentasPorPagarController.php`, `CfdiController.php`, `CompraController.php`, `app/Services/PaymentProcessingService.php`.  
66. **Lógica de negocios en controladores.** ✅ **Parcial** (create/update/recibir movidos a service). `app/Http/Controllers/EntregaDineroController.php`, `app/Services/EntregaDineroService.php`.  
67. **Servicios muy acoplados a modelos Eloquent.** ✅ **Parcial** (dashboard extraído a servicio dedicado). `app/Services/Reports/DashboardReportService.php`, `app/Services/ReportService.php`, `app/Http/Controllers/ReportesDashboardController.php`.  
  
70. **Reglas de validación duplicadas en múltiples controladores.** ✅ **Parcial** (rules centralizadas en soporte). `app/Support/ConfigValidationRules.php`, `app/Http/Controllers/Config/AparienciaConfigController.php`, `EmailConfigController.php`.

---

## 🛡️ 5) Observabilidad y Auditoría

80. **Procesos batch sin métricas de duración/resultado.** ✅ **Parcial** (métricas en comandos críticos). `SyncSequences.php`, `UpdateFolioPrefixes.php`, `ReconciliarInventario.php`.

---

## 🎨 6) Frontend/UX y Consistencia

  
82. **Campos sensibles sin máscara/format.** ✅ **Parcial** (RFC con patrón/ayuda). `resources/js/Pages/Contratacion/Show.vue`, `Contratacion/Renta.vue`.  
83. **Uso de `bg-white` sin tema premium en formularios legacy.** ✅ **Parcial** (inputs RFC con dark premium). `resources/js/Pages/Contratacion/Show.vue`, `Contratacion/Renta.vue`.  
85. **Múltiples patrones de toast/notificación coexistiendo.** ✅ **Parcial** (Mantenimientos usan `notyf` compartido). `resources/js/Utils/notyf.js`, `resources/js/Pages/Mantenimientos/*`.


