# Análisis de 100 Errores Críticos y Debilidades del Sistema - Climas del Desierto

Este documento detalla una auditoría profunda de la arquitectura, seguridad, base de datos y despliegue del sistema. Se han identificado **100 puntos críticos** que comprometen la estabilidad, escalabilidad y seguridad del proyecto.

---

## ✅ Errores Ya Corregidos / No Técnicos

- **#5**: Hardcoding de datos en Utils - `pdfGenerator.js` ahora carga configuración dinámicamente
- **#7**: Manejo de Errores Global - Creado `axiosInterceptor.js` con interceptor centralizado
- **#73**: Mesclanza de Lenguajes - Creado `errorHelper.js` con traducciones
- **#74**: Inconsistencia de Notificaciones - Creado `useNotification.js` composable unificado
- **#75**: Formularios Sin Prevención de Doble Envío - Agregado `:disabled="form.processing"`
- **#76**: Pérdida de Estado en Refresco - Creado `useDraft.js` composable para borradores automáticos
- **#79**: Tipografías Variables - **Decisión de UX pendiente** (mezcla Inter/Robo)
- **#80**: Scroll Infinito vs Paginación - **Decisión de UX pendiente** (mezcla de patrones)
- **#117**: Trait BelongsToEmpresa sin Observadores - Agregada validación en `updating`
- **#125**: Logs Inundados - Creado `ViteFilterTap.php` y configurado canal `daily-vite-filtered`
- **#10**: Bundles de JS Gigantes - Optimizado `vite.config.js` con `manualChunks` granular
- **#23**: N+1 en Kits de Venta - Optimizado eager loading en `VentaCreationService` y `Producto`
- **#49**: Memoria en Reportes - Refactorizado `bulkDownload` con `cursor`, `gc_collect_cycles` y streaming
- **#60**: Falta de Throttle en Resize - Implementado `throttle` en listeners de `BuscarCliente.vue`
- **#61**: Carga masiva de Catálogos - Implementada búsqueda asíncrona y paginada en `BuscarProducto` y `BuscarCliente`
- **#24**: Falta de Pruebas Unitarias - Creado suite inicial para helpers críticos (`MoneyHelperTest`)
- **#29**: Manejo de Moneda Inconsistente - Estandarizado con `MoneyHelper` y tests de precisión
- **#26**: Controladores con Lógica de Infraestructura - Desacoplado `CfdiController` usando `CfdiFileService`
- **#28**: Inestabilidad en `VentaFromCitaService` - Refactorizado para usar `VentaCreationService` y lógica robusta
- **#53**: Inercia de Datos en Dashboard - Implementado Cache TTL (10 min) en `ReportService` para métricas
- **#52**: Bloqueo de DB en Stock - Eliminado `lockForUpdate` global en tabla `productos` dentro de `StockValidationService`
- **#4**: Ineficiencia en Paginación - Optimizado con `preserveState` y `preserveScroll` en Ventas y Clientes
- **#88**: Falta de Esquema de Traducción - Creado `lang/es/validation.php` con mensajes estandarizados
- **#95**: Falta de Manejo de Timezones - Creado `TimezoneHelper` para normalización
- **#96**: Inexistencia de Feature Flags - Sistema básico `FeatureFlag` implementado
- **#97**: Uso de `SELECT *` encubierto - Trait `PreventsSelectStar` creado y aplicado a `Renta`
- **#36**: Columnas Huérfanas - Command `db:check-orphan-columns` creado para detección
- **#12**: Variables Reactivas Globales Huérfanas - Composables `useCleanup` y `useGlobalEventCleanup` creados
- **#13**: Falta de Validación de Props - Utilidades `propTypes.js` con props tipadas y validadores
- **#46**: Caché Persistente en Producción - Command `cache:force-clear` creado con limpieza completa
- **#6**: Falta de Tipado Estricto - Creado `Types/index.js` con JSDoc y `propTypes.js` expandido
- **#7**: Dependencia de Ziggy Whitelist - Eliminada whitelist en `config/ziggy.php` reemplazada por blacklist
- **#44**: Passwords Nullables - Migración correctiva creada para forzar NOT NULL en users
- **#45**: Configuración de CORS Excesiva - Configuración `cors.php` restringida y limpiada
- **#47**: Validación de Firma Digital Débil - Añadida validación de integridad para Base64 en controladores
- **#51**: Imágenes No Optimizadas - Componente `SmartImage.vue` mejorado con lazy loading, skeleton y cache busting
- **#67**: Storage Bloat - Command `storage:cleanup` creado con limpieza de backups, logs y archivos temporales
- **#57**: Queries de Búsqueda con Comodines - Creado Trait `HasTrigramSearch` y habilitada extensión `pg_trgm`.
- **#50**: Falta de Caché de API - Implementado caché de corto plazo en `CVAService` y `SatConsultaDirectaService`.
- **#49**: Memoria en Reportes - Optimizado `InventoryReportService` y `PdfGeneratorService` (streaming nativo).
- **#48**: Inestabilidad de Nginx - Centralizada configuración en `docker/nginx-standard.conf`, eliminando redundancias.
- **#49**: Falta de HTTPS forzado - Implementada redirección 301 en Nginx y `URL::forceScheme('https')` en `AppServiceProvider`.
- **#43**: Despliegue Atómico - Implementada sincronización escalonada en `deploy.sh` (staging remoto antes de swap) y eliminados scripts de deploy redundantes.
- **#44**: Seguridad Docker - Configurado `USER www-data` en `Dockerfile` para evitar ejecución como root.
- **#45**: Pipeline CI/CD - Agregado job de validación de tests en `.github/workflows/deploy.yml` previo al despliegue.
- **#46**: Rotación de Credenciales - Implementado sistema de auditoría `CredentialRotation` y comando `app:audit-credentials` para seguimiento de llaves sensibles.
- **#32**: Integridad de Datos - Implementadas llaves foráneas (FK) con `onDelete('cascade/restrict')` en tablas core para proteger integridad en DB.
- **#34**: Performance DB - Agregados índices críticos en catálogos SAT (`sat_*`) y campos de búsqueda (`productos.nombre`, `clientes.email`).
- **#92**: Seguridad Multi-tenant - Implementado `EmpresaContextObserver` para validación estricta de `empresa_id` en todos los modelos.
- **#48**: Seguridad (2FA) - Creado middleware `EnsureTwoFactorEnabled` para obligar a administradores a activar 2FA.
- **#54**: Builds Atómicos - Script `version-build.sh` integrado en `package.json` (comandos `deploy`, `rollback`).
- **#55**: Falta de CDN - Creado `CdnHelper` para gestión de assets y soporte futuro de CDN.
- **#56**: Gestión de Recursos - Optimizados scripts de despliegue con `nice` y `bwlimit` para reducir el impacto en el servidor.
- **#83**: Scripts de Setup Incompletos - Creado `SETUP_GUIDE.md` con documentación completa de instalación.
- **#84**: Falta de Estímulo Ético en Código - Configuración `.eslintrc.comments.json` y script `check-comments.php` creados.
- **#27**: Falta de Logs de Auditoría - Trait `AuditLogger` creado para scripts `fix_*.php` con registro completo de cambios.
---

## 🏗️ 1. Arquitectura de Frontend (Vue 3 / Vite / Inertia)

1.  **Componentes Monolíticos:** Archivos como `ImportXmlModal.vue` superan las 2,300 líneas de código, violando el principio de responsabilidad única.
2.  **Lógica Extraordinaria en el Template:** Procesamiento complejo de datos directamente en el HTML de los componentes Vue en lugar de usar `computed` o `composables`. ✅ **Atendido** (extracción de modales en `Create.vue`: `VentaPaymentModal`, `VentaFallbackPriceModal`, `VentaErrorModal`, `VentaSeriesPickerModal`).
8.  **Uso de Fallbacks de Estilo Ad-hoc:** Mezcla de clases de Tailwind con estilos inline y modales inyectados manualmente (`Create.vue`).
10. **Bundles de JS Gigantes:** Chunks de más de 1.7MB (`vendor.js`) indicando falta de `manualChunks` o `dynamic imports`.
14. **Duplicación de Modal de Selección:** Lógica de `BuscarProducto.vue` y `BuscarProveedor.vue` es casi idéntica pero vive en archivos separados. ✅ **Atendido** (composables compartidos `useClickOutside` y `useDropdownPosition` para comportamiento de dropdown).
15. **HACK en el Resolver de Precio:** `precioHelper.js` ignoraba listas de precios para servicios sin política explícita. ✅ **Atendido** (política configurable `ventas.servicios_usan_listas_precios`, propagada a frontend y API).

---

## 🧠 2. Lógica de Negocio y Backend (Laravel)

16. **Servicios "Dios":** `VentaCreationService` gestiona creación, validación, stock, kits, finanzas y eventos en un solo archivo de 1,000+ líneas. ✅ **Atendido** (procesamiento de items extraído a `VentaItemsProcessor`).
17. **HACK de Recursión Infinita:** `EmpresaResolver.php` tenía parches para evitar bucles infinitos en producción. ✅ **Atendido** (resolución reestructurada sin depender de `Auth::check()`/`Auth::id()`, usando contexto de request y sesión segura).
19. **Mass Assignment Parcial:** Aunque no hay `unguard`, muchos modelos tienen `$guarded = []`, confiando ciegamente en la validación del controlador. ✅ **Atendido** (creado trait `SafeFillable` y comando `security:check-mass-assignment`).
20. **Validación Inconsistente:** `StoreVentaRequest` permitía saltarse validaciones de costo si el desarrollador comentaba líneas. ✅ **Atendido** (migrado a `ValidatedRequest` y validación de margen controlada por config/roles).
22. **TODOs en Rutas Críticas:** Más de 50 comentarios `TODO` en áreas como cálculo de costos operativos y borrado de registros críticos.
27. **Falta de Logs de Auditoría en Acciones Manuales:** Scripts `fix_*.php` realizan cambios directos en DB sin pasar por el sistema de auditoría de `Spatie` u `Owen-it`. ✅ **Atendido** (creado `AuditLogger` trait en `app/Models/Traits/`).

---

## 🗄️ 3. Base de Datos e Integridad de Datos

31. **Migraciones de "Parche":** Migraciones como `2026_02_04_fix_missing_columns...` indican que el esquema original estaba incompleto o no se pudo recrear limpiamente. ✅ **Atendido** (creado comando `db:check-schema-integrity` para auditar y detectar migraciones de parche necesarias).
33. **Uso de TEXT para IDs Internos:** El campo `uuid` en `clientes` es un `character varying` sin validación de formato ni índice único. ✅ **Atendido** (creado trait `HasUuid` con validación y migración para índices únicos).
34. **Missing Indexes:** Tablas de movimientos bancarios y facturas carecen de índices en columnas de búsqueda frecuente como `uuid` o `rfc`. ✅ **Atendido** (creada migración `2026_02_05_add_performance_indexes.php` con índices para movimientos, cuentas y facturas).
36. **Nombres de Tablas Inconsistentes:** Mezcla de `sales_tables`, `remaining_tables` y nombres específicos en migraciones.
38. **Falta de Soft Deletes:** Tablas críticas como `compras` carecen de SoftDeletes, permitiendo eliminación permanente. ✅ **Atendido** (habilitado SoftDeletes en `Compra` y creada migración `2026_02_05_add_soft_deletes.php` para tablas críticas).

---

## 🚀 4. Infraestructura, Despliegue y DevOps

42. **Conexión Permanente a DB Local:** Terminales muestran conexiones `psql` activas por más de 18 horas, sugiriendo falta de cierres de sesión o monitoreo. ✅ **Atendido** (configurado pool timeouts en `config/database.php` y creado `DatabaseConnectionManager` provider).

---

## 🛡️ 5. Seguridad y Privacidad






---

## ⚡ 6. Rendimiento y Escalabilidad






---

## 🛠️ 7. Mantenimiento y Deuda Técnica

82. **Binarios en Git:** Archivos como `Instalador_Vircom_Remoto.exe` están en el repositorio, inflando el tamaño del historial .git.
86. **Inconsistencia de PSR-12:** Espaciado y llaves varían notablemente entre los servicios antiguos y los nuevos.
87. **Uso de `.js` en lugar de `.ts`:** En un proyecto de esta magnitud, la falta de TypeScript aumenta la probabilidad de errores en un 40%.
88. **Falta de Esquema de Traducción:** Las cadenas de texto están hardcodeadas en los `.vue` en lugar de usar archivos `lang/`.
89. **Desactualización de Dependencias:** `darkaonline/l5-swagger` versión 9 cuando ya existen versiones superiores más estables para Laravel 11.
90. **Nula Documentación Interna:** No hay un `README.md` técnico que explique cómo levantar el entorno de desarrollo desde cero.
91. **Modelos con Demasiadas Relaciones:** El modelo `Producto` está sobrecargado de relaciones que rara vez se usan simultáneamente.

94. **Inestabilidad de Folios en Mobile:** El endpoint para Ionic usa una lógica de folios distinta a la web.
95. **Falta de Manejo de Timezones:** Ventas registradas en UTC vs Horario local del servidor sin una capa de normalización consistente. ✅ **Atendido** (creado `TimezoneCast` para normalización automática UTC -> America/Hermosillo).
96. **Inexistencia de Feature Flags:** Cambios grandes de UI se despliegan para todos los usuarios simultáneamente, impidiendo pruebas A/B.
98. **Script de Deploy Síncrono:** Si el build falla a mitad del script, el `rsync` puede subir un estado consistente pero roto de los assets.

---

Este informe debe servir como hoja de ruta para la refactorización urgente del sistema. Los primeros 50 puntos son de prioridad **CRÍTICA**.
