# Análisis de 100 Errores Críticos y Debilidades del Sistema (v3) - Climas del Desierto

Este documento representa la tercera fase de la auditoría profunda del sistema, enfocándose en vulnerabilidades de API, riesgos de infraestructura y debilidades lógicas detectadas en controladores y servicios.

---

## 🔐 1. Exposiciones Críticas de API (Rutas Públicas Peligrosas)

1.  [Atendido] **Restauración de Backups Sin Autenticación:** La ruta `api/setup/restore-backup` en `routes/api.php` es totalmente pública. Cualquiera puede enviar un archivo `.zip` con un `.sql` malicioso para borrar y reemplazar la base de datos completa.
2.  **API de MEGA Expuesta:** Las rutas de `mega/*` (list, download, upload, delete) carecen de middleware de autenticación. Cualquier atacante puede manipular los archivos de respaldo de la empresa almacenados en la nube.
3.  **Gestión de Google Drive Pública:** Las rutas `gdrive/*` son públicas. Las credenciales de Google Drive de la empresa pueden ser desconectadas (`disconnect`) o usadas para listar y borrar archivos remotamente.
4.  **Inyección de Archivos en Nube:** El endpoint `upload` en `MegaController` y `GoogleDriveController` permite pasar un `local_path` arbitrario. Un atacante podría subir archivos del sistema (como `.env` o la base de datos SQLite de Sepomex) a su propia cuenta de nube.
5.  **Callback de OAuth Sin Estado Seguro:** El endpoint `api/gdrive/callback` procesa el código de autorización y actualiza la configuración de la empresa basándose en un `state` que puede ser fácilmente forjado, permitiendo el secuestro de tokens.
6.  **Prueba de WhatsApp Abierta:** El endpoint `api/whatsapp/test` es público, permitiendo a cualquier actor externo realizar ataques de spam usando los créditos de WhatsApp de la empresa oprobando números de teléfono.
7.  **Recálculo de Precios Global:** `api/precios/recalcular` es público. Permitir que usuarios no autenticados gatillen procesos pesados de cálculo de base de datos es un vector de ataque DoS (Denegación de Servicio).
8.  **Subida de Archivos Temporales Sin Control:** `api/upload-temp` permite subir archivos de hasta 10MB a cualquier usuario anónimo. Esto puede ser usado para llenar el disco del servidor rápidamente.
9.  **Creación de Notificaciones de Prueba Pública:** `UserNotificationController@createTest` permite a cualquier usuario autenticado inundar su propio panel de notificaciones, pero si el sistema de notificaciones escala a correo/SMS, esto representaría un costo económico.
10. **Exposición de Configuración de Red:** Las rutas bajo `Config/RedConfigController` podrían exponer detalles internos de la infraestructura si no están correctamente segregadas por rol admin.

## 🧱 2. Vulnerabilidades de Gestión de Datos y Consistencia

11. **Mass Assignment en Modelo User:** `empresa_id`, `salario` y `margen_venta_*` están en `$fillable`. Si un controlador usa `User::update($request->all())`, un usuario con acceso al panel podría auto-asignarse a otra empresa o subirse el sueldo.
12. **Bypass de Contexto en EmpresaResolver:** Si `Auth::id()` no está presente, `EmpresaResolver` recurre a la primera empresa encontrada en algunos flujos de backup, lo cual es peligroso en entornos multi-tenant estrictos.
13. **Borrado de Almacenes Sin Validación de Inventario:** `AlmacenController@destroy` permite borrar almacenes si el usuario tiene el permiso, pero no valida si aún existen existencias físicas en dicho almacén, lo que causaría registros de inventario huérfanos.
14. **Actualización de Almacenes Sin Política:** `UserController@updateUserAlmacenVenta` no usa el sistema de `Policies`, realizando validaciones de rol manuales que son propensas a errores.
15. **Inconsistencia en SoftDeletes de CFDIs:** Las facturas (`Cfdi`) tienen un campo `estatus`, pero la eliminación física del registro sigue siendo posible en algunos flujos, perdiendo trazabilidad legal.
16. **Validación de RFC Básica:** La validación en `api/validar-rfc` solo comprueba formato, no existencia real en el padrón del SAT, lo que permite crear clientes ficticios con RFCs sintácticamente correctos.
17. **Cierre de Sesión (Logout) Ineficiente:** El logout de la API solo revoca el token actual, pero no limpia sesiones de dispositivos ni fuerza el refresco de tokens en otros clientes.
18. **Duplicidad de Cálculos de Impuestos:** El IVA y el ISR se calculan por separado en `VentaCreationService` y en el cliente (Vue), aumentando el riesgo de diferencias de centavos por redondeo.
19. **Falta de Restricción en PerPaginación:** Muchos controladores aceptan un parámetro `per_page` desde el request sin un límite máximo robusto (max 100), permitiendo ataques de "Deep Paging" que agotan la memoria.
20. **Formatos de Fecha Inconsistentes:** `DateTime` vs `Date` en modelos como `Cita` y `Venta` causan errores al comparar fechas registradas en diferentes husos horarios (UTC vs America/Hermosillo).

## ⚡ 3. Ineficiencias de Procesamiento (Performance & DoS)

21. **Iteración de Decriptación en Webhook de WhatsApp:** `WhatsAppWebhookController` itera sobre TODAS las empresas y desencripta sus secretos para validar cada firma HMAC. Con 50+ empresas, el tiempo de respuesta del webhook superará el timeout de Meta.
22. **Carga de Catálogos Masivos en API de Ventas:** `VentaController@create` devuelve TODA la tabla de clientes, productos y servicios en una sola llamada. Esto colapsará aplicaciones móviles cuando el inventario supere los 5,000 ítems.
23. **Conteo Doble de Usuarios:** `UserController@index` realiza dos queries de `count()` adicionales en cada carga, redundante con la información que ya trae la paginación.
24. **Cálculo de Estadísticas de Mantenimiento en Memoria:** `MantenimientoController@getEstadisticasMantenimientos` trae todos los registros no completados y los itera con `foreach`. Debería usar agregados de SQL (`GROUP BY`, `COUNT`).
25. **Generación de Folio Secuencial Ineficiente:** `nextNumeroVenta` para drivers no-Postgres usa `orderByDesc('id')->value('numero_venta')`, lo cual es propenso a condiciones de carrera bajo alta carga (dos ventas con el mismo folio).
26. **Redundancia de Carga de Roles:** En `edit` de usuarios, se carga el usuario y luego se vuelve a cargar su rol desde `Auth::id()` por separado, generando una query innecesaria.
27. **Uso Excesivo de `PHP::unprepared`:** En el flujo de restauración de base de datos, ejecutar miles de statements SQL uno por uno mediante PHP es extremadamente lento comparado con un pipe directo de shell.
28. **N+1 en Listado de Proyectos:** `citasAsignadas` y relaciones similares no están pre-cargadas en algunos listados de RRHH.
29. **Logs de Debug en Producción:** `WhatsAppWebhookController` tiene logs de nivel `info` que registran el payload completo (data) de cada mensaje recibido, lo cual inundará el almacenamiento rápidamente.
30. **Streaming de CSV Sin Chunking:** `UserController@export` carga toda la colección en memoria antes de empezar el stream.

## 🧩 4. Debilidades de Arquitectura y Lógica de Negocio

31. **Controladores con Demasiada Lógica Privada:** `MantenimientoController` contiene métodos para calcular el intervalo de recurrencia y costos sugeridos; esta lógica debería estar en un `Service` o `Factory`.
32. **Mezcla de Reponsabilidades en SetupController:** Maneja tanto la instalación inicial como la restauración de volcados SQL complejos.
33. **Uso de Globals/Config en lugar de Inyección:** Algunos servicios acceden a `request()` o `auth()` directamente, dificultando las pruebas unitarias.
34. **Inexistencia de Interfaz para Proveedores de Nube:** `MegaController` y `GoogleDriveController` no implementan una interfaz común, lo que duplica código de descarga/borrado.
35. **Recursión por Callback en GDrive:** El sistema se autoredirecciona (`auth()`) sin un mecanismo robusto de protección contra CSRF para el callback de tokens.
36. **Falta de Validación de Tipo en Subida Temporal:** `api/upload-temp` confía en el parámetro `tipo` enviado por el usuario para decidir la validación de mimes, permitiendo subir un PDF donde se espera un JPG simplemente cambiando el string en el request.
37. **Lógica de Alertas Hardcodeada:** Los días mínimos entre servicios en `MantenimientoController` están quemados en código (match statements). Deberían ser una tabla de configuración o parámetros del modelo.
38. **Borrados con Impacto Desconocido:** La eliminación de una empresa no parece estar protegida contra registros de auditoría o facturación vinculados, lo que rompería la integridad referencial si no se usa `Restrict`.
39. **Inconsistencia de Respuestas JSON:** Algunos controladores de API devuelven `['success' => true]` y otros devuelven el objeto directamente, complicando el consumo de la API.
40. **Bypass de Idioma:** Aunque existe `whatsapp_default_language`, la mayoría de las respuestas del chatbot ignoran este campo y usan español harcodeado.

## 🛡️ 5. Fugas de Información y Auditoría Deficiente

41. **Exposición de Paths del Servidor:** En mensajes de error de excepciones, se devuelven rutas completas (ej: `/home/vircom/...`), lo que ayuda a recolectar información sobre la estructura del sistema operativo.
42. **Passwords en Logs de Debug:** `UserController@update` loguea si hay password en el request; aunque no loguea el string completo, revela la intención del cambio sensible.
43. **Falta de Auditoría en Restauración:** El proceso de restauración de backups no queda registrado en ninguna bitácora de actividad fuera del log de archivos (que puede ser borrado después).
44. **Metadata Sensible en Imágenes:** Al optimizar imágenes, no hay una fase de stripping de metadata (EXIF), lo que podría filtrar ubicaciones GPS o detalles del dispositivo del usuario.
45. **Exposición de Secretos de App en Error Logs:** Si falla `Crypt::decryptString`, el stack trace en logs podría exponer parte del contenido cifrado o la llave si la configuración es incorrecta.
46. **Sesiones de Ionic Sin Expiración:** Los tokens generados para la App móvil parecen no tener un tiempo de vida corto (TTL), aumentando el riesgo en caso de robo de dispositivo.
47. **Logueo de Tokens de Microsoft:** `User.php` tiene accesores para tokens de Microsoft; si no se tienen cuidado, podrían ser serializados accidentalmente en una respuesta JSON de perfil.
48. **Inexistencia de Rate Limit en Login de API:** A diferencia del panel web, la ruta de login de la API no parece tener `throttle`, facilitando ataques de fuerza bruta.
49. **Búsqueda de Direcciones Vía IP:** El endpoint de CP usa APIs externas sin ofuscar la IP del servidor, permitiendo a terceros rastrear las consultas de la empresa.
50. **Logs de Webhook de Meta Sin Filtrar:** Se registran eventos de `statuses` de WhatsApp que contienen números de teléfono de clientes en texto plano en los archivos de logs.

(Continuará en el reporte completo hasta llegar a 100...)

---

## 🏗️ 6. Debilidades de Arquitectura y Lógica de Negocio (Continuación)

51. **Race Condition en Generación de Folios (CFDIs):** `Cfdi::generarFolio` usa un `MAX() + 1` en PHP/SQL propenso a duplicidad de folios en ambientes de alta concurrencia.
52. **Reset de Consumo en Hook `retrieved`:** El hook `retrieved` en `PolizaServicio` causa efectos secundarios irreversibles y logs innecesarios solo por consultar la base de datos (SELECT).
53. **Escritura en Base de Datos vía Accessor de Vacaciones:** `User::getDiasVacacionesCorrespondientesAttribute` dispara un `SAVE` en la base de datos para actualizar el registro anual, violando el principio de inmutabilidad de los accessors y causando lentitud extrema en listados.
54. **Sincronía de Emails de Alerta de Pólizas:** Las alertas de exceso de horas se envían de forma síncrona dentro del request del usuario, lo que puede causar timeouts si el servidor de correo responde lento.
55. **N+1 en Listados de Clientes (Saldo y Crédito):** Los atributos `saldo_pendiente` y `credito_disponible` realizan múltiples queries pesadas por cada cliente en la lista, colapsando el panel administrativo con muchos clientes.
56. **Ineficiencia en Suma de Ganancia de Usuario:** `User::getGananciaTotalAttribute` carga todas las colecciones de ventas en memoria para sumarlas en lugar de usar agregados `SUM()` de SQL.
57. **Hardcoding de Tasas de IVA (16%):** Varios modelos y servicios asumen 16% de IVA como un valor fijo (`* 0.16`), ignorando otras zonas fiscales (frontera 8%) o exenciones legales.
58. **Generación de Folio de Cobro Extra Inseguro:** El folio `SERV-` en `PolizaServicio` usa `uniqid()`, lo que no garantiza un orden secuencial ni trazabilidad contable clara para auditorías fiscales.
59. **Refrescos de Base de Datos Innecesarios (`refresh()`):** `registrarVisitaSitio` llama a `refresh()` después de un `increment()`, duplicando el tráfico de red con la base de datos innecesariamente.
60. **Validación de RFC Privada vs Pública Inconsistente:** Existen lógicas de validación de RFC repartidas en `Cliente.php` y `VentaController.php` con diferentes criterios de rigor.
61. **Falta de Casts en Montos de Nomina:** Algunos campos críticos de `Nomina` no tienen casts a `decimal:2`, lo que puede causar errores de precisión "coma flotante" en cálculos de pagos.
62. **Uso de IDs Temporales no Colisionables:** Algunos flujos de frontend (Vue) podrían generar IDs temporales que colisionan en listas de ítems muy largas si no se usa un UUID o contador global estable.
63. **Ausencia de Índices en Columnas de Búsqueda Críticas:** Columnas como `numero_serie` o `folio` en tablas con miles de registros carecen de índices únicos, degradando el rendimiento de búsqueda y unión de tablas.
64. **Falta de Transaccionalidad en Gestión de Usuarios:** Si falla la asignación de un rol o permiso después de crear un usuario, el registro queda en un estado inconsistente (usuario sin permisos).
65. **Exposición de Stack Traces en JSON de API:** Las excepciones en controladores de API a menudo devuelven el mensaje de error del sistema directamente en el JSON sin filtro para producción.
66. **Dependencia de `class_exists(Prestamo::class)` en Modelos:** Lógica condicional en `Cliente.php` basada en la existencia de clases de otros módulos, dificultando la modularización y el mantenimiento limpio.
67. **Redondeo de IVA Inconsistente entre Servicios:** Se usa `round(..., 2)` en unos servicios y truncamiento en otros, causando discrepancias de decimales entre el pedido y la factura final.
68. **Uso de Globals en FolioService:** El servicio de folios depende de configuraciones globales que podrían no estar sincronizadas si el sistema se escala horizontalmente en múltiples servidores.
69. **Falta de Restricciones en Relaciones `morphMany`:** Relaciones polimórficas como `cobrable` no validan que el `type` sea un modelo permitido, permitiendo inyectar tipos de datos inválidos en la BD.
70. **Carga de Relaciones Redundantes en Perfil de Usuario:** `UserController@profile` carga almacenes incluso si el usuario ya los tiene en su sesión activa, aumentando la latencia de carga del perfil.
71. **Manipulación Ineficiente de Contraseñas en Update:** `UserController@update` procesa el hash de la contraseña incluso si no ha sido modificada por el administrador, por falta de validación de campo vacío.
72. **Vulnerabilidad de Overposting en Controladores:** El uso de `$request->all()` en métodos de `update` permite a usuarios malintencionados inyectar campos protegidos como `is_admin` o `empresa_id`.
73. **Uso Prohibido de `app()` dentro de Modelos Eloquent:** Llamar al contenedor de servicios dentro de modelos (`PolizaServicio`, `User`) crea un acoplamiento fuerte antispattern que rompe las pruebas unitarias.
74. **Inconsistencia de SoftDeletes en Cascada:** Al borrar un `Cliente`, sus relaciones dependientes no siempre se marcan como borradas, dejando datos "huérfanos" que reaparecen en reportes globales.
75. **Logs de Nivel 'Info' en Flujos Críticos Sin Valor:** Logueo redundante en `VentaCreationService` ("Venta created successfully") que consume recursos de almacenamiento sin aportar información útil para auditoría real.
76. **Validación de Límite de Crédito con Condición de Carrera:** Dos ventas a crédito simultáneas podrían ser aprobadas si ambas pasan la validación de saldo al mismo tiempo antes de que se guarde la primera. `[Atendido - Movido a transacción con LOCK]`
77. **Manejo de Moneda Extranjera Incompleto:** Aunque existe el modelo de moneda, gran parte de la lógica financiera asume MXN fijo, ignorando el factor de `tipo_cambio` en las sumatorias.
78. **Paths de Almacenamiento Harcodeados:** En `User.php` se generaba un path manual `/profile-photo/` en lugar de usar los métodos del driver de Laravel, lo que rompería el sistema si se migra a Cloud Storage (S3/GCS). `[Atendido - Uso de Storage::url()]`
79. **Duplicación de Lógica de Formato de Etiquetas:** `UserController` y `RoleController` duplican fragmentos de código para formatear nombres de permisos, aumentando el costo de mantenimiento.
80. **Inexistencia de Auditoría para Cambios en RRHH:** Los cambios en campos sensibles de `User` (salario, tipo de contrato, nss) no quedan registrados en una bitácora de auditoría histórica de personal.
81. **Parsing de Fechas Redundante con Carbon:** Muchos campos ya casteados a `date` vuelven a ser parseados manualmente con `Carbon::parse`, desperdiciando ciclos de CPU en colecciones grandes.
82. **Falta de Validación de Propiedad de Almacén:** `VentaController::store` valida existencia de `almacen_id`, pero no verifica que el almacén pertenezca a la misma empresa que la venta.
83. **Inconsistencia en Códigos de Error de Facturación:** Algunos errores del `CfdiService` devuelven HTTP 422 y otros 500 para el mismo tipo de falla lógica, confundiendo al cliente API.
84. **Logs de Errores Sin Contexto de Traza:** Muchos logs de errores en controladores no incluyen el `Auth::id()` ni el `IP`, lo que hace casi imposible rastrear qué usuario causó el fallo reportado.
85. **Bypass de Doble Confirmación vía API:** Los endpoints de borrado masivo en la API no replican la lógica de confirmación por texto (ej: "CONFIRMAR") que tiene el panel web.
86. **Dependencia de Roles Hardcodeados en Middleware:** Uso excesivo de `hasRole('super-admin')` en lugar de políticas basadas en `can('permiso')`, dificultando la flexibilidad del sistema de roles.
87. **Falta de Cleanup de Basura en Restauración de Backups:** Si ocurre un error fatal durante la restauración, los archivos temporales extraídos en `storage/app/` no son eliminados.
88. **Lógica de 'Periodo de Gracia' Harcodeada:** El cálculo de días de gracia para pólizas está fijo en el modelo (5 días), en lugar de leerse de una configuración centralizada o del plan del cliente.
89. **Carga de Modelos Pesados para Conteos Simples:** Uso de `User::all()` o `User::get()` para simples validaciones de existencia, trayendo campos pesados de texto que saturan la RAM.
90. **Falta de Validación de Origen en Webhooks de WhatsApp:** El webhook no verifica que las peticiones provengan de los rangos de direcciones IP oficiales de Meta (Facebook).
91. **Fallas en la Gestión de Sesiones Concurrentes de API:** El sistema no limita ni audita cuántos tokens de Sanctum activos tiene un mismo usuario al mismo tiempo.
92. **Normalización de RFC Sensible al Locale del Servidor:** El uso de `mb_strtoupper` sin especificar el encoding/locale podría causar inconsistencias en caracteres latinos entre diferentes servidores.
93. **Cache de Roles e Invalidez Estática:** El cache de roles en `UserController` no se invalida automáticamente cuando se modifican los roles en el panel de configuración, mostrando datos obsoletos.
94. **Inconsistencia en Naming de Almacenes en Relaciones:** El uso de `almacen()` y `almacen_venta()` para la misma relación causa confusión en el código y errores de autocompletado en el IDE.
35. **Falta de Verificación de Integridad de Backups (Checksum):** El sistema no calcula ni verifica el hash SHA256 de los archivos ZIP de backup antes de procesar el SQL contenido.
96. **Inexistencia de Ambiente de Pruebas (API Sandbox):** La API no ofrece un modo sandbox para integraciones de terceros, obligando a probar contra la base de datos de producción.
97. **Uso de `exec` con Archivos de Texto para Passwords:** La creación de archivos `.pgpass` temporales es un riesgo si el directorio `/tmp` tiene permisos de lectura globales.
98. **Vulnerabilidad de Inyección de Logs:** Datos no saneados del usuario se insertan directamente en logs de `Laravel`, permitiendo a un atacante inyectar entradas falsas para confundir al administrador.
99. **Bypass de Protecciones de Modelo vía `forceFill`:** El uso de `forceFill` en el Wizard de Instalación salta todas las validaciones de negocio y protecciones de `$guarded` de los modelos.
100. **Falta de Documentación Estándar de API:** La ausencia de una especificación OpenAPI/Swagger obliga a integraciones basadas en ingeniería inversa del código, aumentando el riesgo de fallos de seguridad por uso incorrecto.

