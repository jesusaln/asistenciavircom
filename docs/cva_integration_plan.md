# Plan de Integración: API Grupo CVA

Este documento detalla la estrategia por fases para integrar el catálogo de productos de Grupo CVA en la plataforma, asegurando rendimiento, seguridad y datos actualizados en tiempo real.

## Fase 1: Infraestructura y Autenticación (Prioridad Alta) ✅
Establecer la comunicación base con el API y permitir la configuración de credenciales.
- [x] **Backend:** Crear `app/Services/CVAService.php` para manejar peticiones.
- [x] **Seguridad:** Implementar login y almacenamiento del `Bearer Token` en caché (12 horas).
- [x] **Base de Datos:** Añadir campos de configuración (`cva_user`, `cva_password`, `cva_active`) en `empresa_configuracion`.
- [x] **UI:** Actualizar `TiendaOnlineTab.vue` para permitir al administrador ingresar sus credenciales de CVA.

## Fase 2: Consumo de Catálogo y Normalización ✅
Permitir que el sistema busque y entienda los productos del proveedor.
- [x] **Proxy:** Crear un controlador en Laravel que sirva como puente entre Vue.js y CVA (para evitar errores de CORS y proteger el token).
- [x] **Mapeo:** Crear una clase que convierta el JSON de CVA (id, clave, precio, imagen) al formato interno de la aplicación.
- [x] **Filtros:** Implementar búsqueda por marca, grupo y descripción genérica.

## Fase 3: Integración en la Tienda (Frontend) ✅
Hacer visibles los productos externos para los clientes finales.
- [x] **Vista /tienda:** Modificar la interfaz para mostrar productos locales y externos (etiquetados).
- [x] **Validación en Tiempo Real:** Al ver un producto CVA, consultar stock y precio exacto antes de permitir añadir al carrito.
- [x] **Márgenes:** Aplicar dinámicamente el porcentaje de utilidad configurado por el usuario.

## Fase 4: Automatización y Pedidos (Opcional)
Cerrar el ciclo de venta.
- [x] **Sincronización:** Tarea programada (Cron/Job) para actualizar categorías y marcas locales.
- [x] **Infraestructura de Pedidos:** Implementación de métodos para crear, listar y consultar pedidos en `CVAService`.
- [ ] **Automatización de Compra:** (Pendiente) Integrar la creación automática de órdenes al concretar una venta (requiere definir reglas de negocio).

---

### Notas de Implementación
- **Caché:** Se usará `Cache::remember` para evitar llamadas redundantes al catálogo de 90MB.
- **Rendimiento:** Priorizar el uso de parámetros `images=1` y `completos=1` para mantener una estética premium en la tienda.
- **Seguridad:** Las credenciales nunca se exponen al navegador; todas las llamadas pasan por el servidor local.
