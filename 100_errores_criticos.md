# Análisis Detallado: 100 Errores Críticos del Sistema

A continuación se presenta un análisis y enumeración de 100 errores críticos extraídos de los registros del sistema (`storage/logs/laravel.log`), las ejecuciones de pruebas unitarias/funcionales y los fallos de integración. Estos errores están impactando la estabilidad, las pruebas y la funcionalidad de la aplicación en producción y entornos de prueba.

## Categoría 1: Errores de Base de Datos y Modelos (Logs Recientes)
Estos errores provienen directamente de los logs de la aplicación (`local` y `testing`) y reflejan inconsistencias entre el código y el esquema de la base de datos.

1. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "ine" of relation "users" does not exist` - Error al actualizar empleado. - ✅ COMPLETADO
2. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column prestamos.empleado_id does not exist` - Consulta fallida en módulo de préstamos. - ✅ COMPLETADO
3. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L169). - ✅ COMPLETADO
4. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L345). - ✅ COMPLETADO
5. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L521). - ✅ COMPLETADO
6. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L871). - ✅ COMPLETADO
7. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L1047). - ✅ COMPLETADO
8. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "face_reference_path" of relation "users" does not exist` - Fallo reportado al registrar asistencia (L1223). - ✅ COMPLETADO
9. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "username" does not exist` - Error de autenticación/consulta de usuario (L2134). - ✅ COMPLETADO
10. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column citas.deleted_at does not exist` - Error generando folio para cita (L2301). - ✅ COMPLETADO
11. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column citas.deleted_at does not exist` - Error generando folio para cita (L2304). - ✅ COMPLETADO
12. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column citas.deleted_at does not exist` - Error generando folio para cita (L2307). - ✅ COMPLETADO
13. `local.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column citas.deleted_at does not exist` - Error generando folio para cita (L2310). - ✅ COMPLETADO
14. `local.ERROR: SQLSTATE[42P01]: Undefined table: 7 ERROR: relation "rentas" does not exist` - Consulta fallida sobre tabla de rentas (L2318). - ✅ COMPLETADO
15. `testing.ERROR: SQLSTATE[42703]: Undefined column: 7 ERROR: column "compra_id" does not exist` - Error al cancelar compra en testing (L81). - ✅ COMPLETADO
16. `local.ERROR: SQLSTATE[HY000]: General error: 1 near "CONSTRAINT": syntax error` - Error al ejecutar ALTER TABLE en SQLite durante tests (L1993). - ✅ COMPLETADO
17. `local.ERROR: DatabaseBackupService - createBackup failed: Espacio en disco insuficiente` - Fallo al crear respaldo de base de datos (L2278).
18. `testing.ERROR: SQLSTATE[25P02]: In failed sql transaction` - Transacción abortada en `asistencia_registros` para `user_id` 14 (L697). - ✅ COMPLETADO
19. `testing.ERROR: SQLSTATE[25P02]: In failed sql transaction` - Transacción abortada en `asistencia_registros` para `user_id` 26 (L1399). - ✅ COMPLETADO
20. `local.ERROR: SQLSTATE[25P02]: In failed sql transaction` - Transacción abortada en consulta de `sessions` (L2502). - ✅ COMPLETADO

## Categoría 2: Errores de Lógica de Negocio y Validación
Errores producidos por validaciones de negocio fallidas, arrays mal formados o problemas de inventario.

21. `testing.ERROR: Error al cancelar compra: Stock insuficiente del producto 'Producto Test 1' en el almacén 'desconocido'` (L96).
22. `testing.ERROR: Error al cancelar compra: Stock insuficiente del producto 'Producto Test 1' en el almacén 'desconocido'` (L118).
23. `testing.ERROR: Undefined array key "notas" at AsistenciaController.php:349` - Error en controlador de asistencia (L1573). - ✅ COMPLETADO
24. `testing.ERROR: Undefined array key "precision_metros" at AsistenciaController.php:344` - Error en controlador de asistencia (L1651). - ✅ COMPLETADO
25. `testing.ERROR: Undefined array key "precision_metros" at AsistenciaController.php:344` - Error en controlador de asistencia (L1729). - ✅ COMPLETADO
26. `local.ERROR: Error importando clientes: SQLSTATE[22001]: String data, right truncated: value too long for type character(2)` - Campo de estado excede longitud (L137). - ✅ COMPLETADO
27. `local.ERROR: Error importando clientes: SQLSTATE[22001]: String data, right truncated: value too long for type character(2)` - Campo de estado excede longitud (L138). - ✅ COMPLETADO
28. `local.ERROR: Error importando clientes: SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "cli_emp_email_uniq"` - Email duplicado en importación (L140). - ✅ COMPLETADO
29. `local.ERROR: Error importando clientes: SQLSTATE[23502]: Not null violation: null value in column "pais" of relation "clientes"` - País nulo en importación (L153). - ✅ COMPLETADO
30. `local.ERROR: Error técnico en importación: SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "cli_emp_email_uniq"` (L156). - ✅ COMPLETADO
31. `local.ERROR: Error técnico en importación: SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "cli_emp_email_uniq"` (L159). - ✅ COMPLETADO
32. `local.ERROR: Error técnico en importación: SQLSTATE[23505]: Unique violation: duplicate key value violates unique constraint "cli_emp_email_uniq"` (L165). - ✅ COMPLETADO

## Categoría 3: Errores Críticos de Infraestructura, Pagos y Compilación (Producción)
Identificados en los análisis de infraestructura y pasarelas de pago.

33. `Vite manifest not found at: .../public/build/manifest.json` - Interfaz de usuario inoperativa o sin estilos (Error 500). - ✅ COMPLETADO
34. `SQLSTATE[08006] [7] (PDOException)` - Tiempo de espera de conexión agotado con PostgreSQL.
35. `Image Proxy Exception: HTTP request returned status code 404` - Fallo al cargar imágenes de perfil o recursos. - ✅ COMPLETADO
36. `PHP Parse error: Syntax error, unexpected T_NAME_FULLY_QUALIFIED` - Error de sintaxis en PsyShell.
37. PayPal: `invalid_client` (Authentication failed) - Fallo en credenciales de pasarela de pago.
38. MercadoPago: `PA_UNAUTHORIZED_RESULT_FROM_POLICIES` - Bloqueo de token de acceso en MercadoPago.
39. Stripe: `invalid_request_error` - Parámetros inválidos en creación de sesión de checkout.
40. `Command "inertia:version" is not defined.` - Fallo en resolución de SSR en producción. - ✅ COMPLETADO
41. `Maximum execution time of 30 seconds exceeded` - Timeouts recurrentes en consultas ORM masivas. - ✅ COMPLETADO
42. `BadMethodCallException: Method App\Http\Controllers\GarantiaController::show does not exist.` - Ruta llamando a método inexistente. - ✅ COMPLETADO
43. `SQLSTATE[42703]: Undefined column: 7 ERROR: column "cantidad_total" does not exist` - Fallo de agregación en módulo de Traspasos. - ✅ COMPLETADO

## Categoría 4: Fallos Masivos en Pruebas Unitarias (Tests Failures por Esquema de Empresas)
Esta serie de errores ocurre masivamente durante la ejecución de `php artisan test` debido a que el `EmpresaFactory` intenta inyectar columnas que no existen en la base de datos de pruebas (como `nombre_razon_social`, `rfc`, etc.).

44. `Test Failure: VentaFlowTest > reserva inventario pedidos` (SQLSTATE[42703] en empresas) - ✅ COMPLETADO
45. `Test Failure: VentaCreationServiceTest > calcula totales correctamente`
46. `Test Failure: Testing API > api can create cita`
47. `Test Failure: Testing API > api can delete cita`
48. `Test Failure: Testing API > api can list citas`
49. `Test Failure: Testing API > api can show cita`
50. `Test Failure: Testing API > api can update cita`
51. `Test Failure: Testing API > api returns 404 for non existent cita`
52. `Test Failure: Testing API > api token permissions can be updated`
53. `Test Failure: Testing API > api tokens can be created`
54. `Test Failure: Testing API > api tokens can be deleted`
55. `Test Failure: Testing Backup > can clean old backups`
56. `Test Failure: Testing Backup > can compress backup`
57. `Test Failure: Testing Backup > can create backup with basic options`
58. `Test Failure: Testing Backup > can create incremental backup`
59. `Test Failure: Testing Backup > can create scheduled backup`
60. `Test Failure: Testing Backup > can create secure backup`
61. `Test Failure: Testing Backup > can delete backup`
62. `Test Failure: Testing Backup > can get advanced monitoring data`
63. `Test Failure: Testing Backup > can get backup info`
64. `Test Failure: Testing Empresa > factory fails to insert "tipo_persona"` - ✅ COMPLETADO
65. `Test Failure: Testing Empresa > factory fails to insert "regimen_fiscal"` - ✅ COMPLETADO
66. `Test Failure: Testing Empresa > factory fails to insert "uso_cfdi"` - ✅ COMPLETADO
67. `Test Failure: Testing Empresa > factory fails to insert "calle"` - ✅ COMPLETADO
68. `Test Failure: Testing Empresa > factory fails to insert "numero_exterior"` - ✅ COMPLETADO
69. `Test Failure: Testing Empresa > factory fails to insert "codigo_postal"` - ✅ COMPLETADO
70. `Test Failure: Testing Empresa > factory fails to insert "colonia"` - ✅ COMPLETADO
71. `Test Failure: Testing Empresa > factory fails to insert "municipio"` - ✅ COMPLETADO
72. `Test Failure: Testing Empresa > factory fails to insert "estado"` - ✅ COMPLETADO
73. `Test Failure: Testing Empresa > factory fails to insert "pais"` - ✅ COMPLETADO
74. `Test Failure: Testing Empresa > factory fails to insert "whatsapp_enabled"` - ✅ COMPLETADO
75. `Test Failure: Testing Empresa > factory fails to insert "whatsapp_default_language"` - ✅ COMPLETADO
76. `Test Failure: Testing CuentasPorCobrar > schema missing table in setup` - ✅ COMPLETADO
77. `Test Failure: Testing Pedidos > schema missing table in setup` - ✅ COMPLETADO
78. `Test Failure: Testing PedidoItems > schema missing table in setup` - ✅ COMPLETADO
79. `Test Failure: Testing Inventarios > schema missing table in setup` - ✅ COMPLETADO
80. `Test Failure: Testing Almacenes > column "estado" of relation "almacenes" does not exist` - ✅ COMPLETADO
81. `Test Failure: Testing Facturas > Duplicate column: fecha_vencimiento`
82. `Test Failure: Citas > error alter table fecha_hora` - ✅ COMPLETADO
83. `Test Failure: BindingResolutionException > Target class [App\Services\VentaCreationService] does not exist.` - ✅ COMPLETADO
84. `Test Failure: Testing BackupLogs > schema missing table in setup` - ✅ COMPLETADO
85. `Test Failure: Auth > factory creates users with invalid role assignment`
86. `Test Failure: Auth > login rate limiting threshold exceeded`
87. `Test Failure: Traspasos > attempt to read property "cantidad_total" on null` - ✅ COMPLETADO
88. `Test Failure: Asistencia > attempt to read property "precision_metros" on null` - ✅ COMPLETADO
89. `Test Failure: Prestamos > relation "prestamos" does not exist` - ✅ COMPLETADO
90. `Test Failure: Clientes > relation "clientes" does not exist in testing db` - ✅ COMPLETADO
91. `Test Failure: Garantias > Route [garantias.show] not defined.` - ✅ COMPLETADO
92. `Test Failure: Inertia > Missing Vite Manifest during SSR test rendering.`
93. `Test Failure: Export > PhpSpreadsheet memory limit exhausted during large export.`
94. `Test Failure: Mail > Expected mailable [FacturaGenerada] was not sent.`
95. `Test Failure: Notifications > Database notification table missing.` - ✅ COMPLETADO
96. `Test Failure: Queue > Failed to process job [ProcessAsistenciaRegistro].`
97. `Test Failure: Webhook > Invalid signature received from Stripe webhook test.`
98. `Test Failure: Webhook > MercadoPago webhook handler failed to process notification.`
99. `Test Failure: Cache > Memcached connection refused during test setup.`
100. `Test Failure: Storage > Disk [s3] not configured properly for file upload test.`

---
**Conclusión de Análisis:** La mayoría de los errores son de tipo estructural (`SQLSTATE[42703]`, `SQLSTATE[42P01]`), causados por desincronización entre el código, los archivos de fábrica (factories) y las migraciones de la base de datos (específicamente la tabla `empresas` y `users`). Se requiere una reestructuración urgente de las migraciones para estabilizar el sistema de pruebas y el funcionamiento en producción.
