# Roadmap: Sistema Híbrido (Offline-First)

Este documento describe la arquitectura necesaria para que el sistema CDD App pueda funcionar sin conexión a internet y sincronizar los datos una vez que se restablezca la conectividad.

## 1. Tecnologías Propuestas

### PWA (Progressive Web App)
- **Service Workers**: Para cachear los assets de la aplicación (CSS, JS, Imágenes) y permitir que cargue instantáneamente incluso offline.
- **Manifiesto de App**: Para permitir la instalación en dispositivos móviles y de escritorio.

### Almacenamiento Local (IndexedDB)
- Utilizar una base de datos local en el navegador (vía librerías como `Dexie.js` o `LocalForage`) para guardar transacciones pendientes (ventas, citas, inventarios) mientras no hay red.

## 2. Estrategia de Sincronización

1. **Detección de Conectividad**: Escuchar los eventos `online` y `offline` del navegador.
2. **Cola de Sincronización**: Al recuperar la conexión, el sistema debe procesar la cola de datos locales y enviarlos al servidor de Laravel.
3. **Resolución de Conflictos**: Implementar lógica en el backend para manejar casos donde un registro fue editado por dos usuarios distintos durante el periodo offline (ej. usar timestamps de "última edición").

## 3. Módulos Críticos para Modo Offline
- **Punto de Venta (Ventas)**: Vital para no detener la operación comercial.
- **Citas y Servicios**: Para que los técnicos puedan registrar avances en campo sin internet.
- **Inventario**: Consulta de existencias cacheadas.

## 4. Retos Técnicos
- **Seguridad**: Los datos sensibles en el navegador deben estar protegidos.
- **Volumen de Datos**: No se puede guardar toda la base de datos en el cliente, solo lo esencial para el día a día.
