---
description: Plan de Integración de RustDesk Management en Asistencia Vircom
---

# Plan de Integración: RustDesk + Asistencia Vircom

Este plan detalla la integración de capacidades de soporte remoto profesional dentro de la plataforma de asistencia, permitiendo gestionar clientes y conexiones de forma centralizada.

## Fase 1: Cimientos e Identidad (Actual)
- [ ] **Esquema de Base de Datos**: Agregar campo `rustdesk_id` y `rustdesk_alias` a la tabla `clientes` (o `users`).
- [ ] **Configuración de Conectividad**: Registrar la IP del servidor ID/Relay (`191.101.233.82`) y la Key Pública (`nWZn0wE7Gq6meimntlv0G8usBkxDjoR0+OTgUh76WEU=`) en la configuración de la empresa.
- [ ] **Perfil de Cliente**: Habilitar en la vista de cliente el campo para guardar su ID de RustDesk.

## Fase 2: Puente de Comunicación (API)
- [ ] **RustDesk Service**: Crear un servicio en Laravel (`App\Services\RustDeskService`) para comunicarse con la API de RustDesk (puerto 21114).
- [ ] **Sincronización de Equipos**: Capacidad de listar qué equipos de mis clientes están "Online" directamente desde Asistencia Vircom.
- [ ] **Login Unificado**: Permitir que los técnicos usen su cuenta de Asistencia Vircom para loguearse en la app de RustDesk.

## Fase 3: Soporte "Un Clic" (UI/UX)
- [ ] **Botón de Soporte Rápido**: En cada cliente con ID registrado, añadir un botón "Iniciar Soporte Remoto".
- [ ] **Protocolo RustDesk**: Al dar clic, abrir automáticamente la aplicación local de RustDesk conectándose al ID del cliente mediante el esquema `rustdesk://`.
- [ ] **Portal del Cliente**: Añadir un botón para que el cliente descargue el ejecutable pre-configurado de Vircom.

## Fase 4: Auditoría y Facturación
- [ ] **Registro de Sesiones**: Guardar historial de cuándo y quién se conectó a qué equipo.
- [ ] **Cómputo de Horas**: Si el soporte es bajo contrato, sumar el tiempo de conexión remota a la bitácora de servicios automáticamente.

---
**Nota Técnica**: La API actual de RustDesk está operativa en el puerto 21114 del VPS. El acceso se realizará de forma segura mediante comunicación interna entre contenedores.
