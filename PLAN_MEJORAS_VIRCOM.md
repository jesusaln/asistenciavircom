# 🚀 Plan de Mejoras Estratégicas para Asistencia Vircom

Este documento detalla la integración de 4 funcionalidades clave, aisladas de Climas, diseñadas exclusivamente para potenciar el marketing, las ventas y el seguimiento de prospectos en Asistencia Vircom.

## Fase 1: Motor de Captura de Leads y Rastreo UTM 🎯
**Objetivo:** Capturar parámetros UTM (fuente, campaña, medio) de la URL cuando un visitante llega a la página, persistirlos durante la sesión y guardarlos cuando el usuario envíe un formulario (Contacto/Lead).

*Pasos a ejecutar:*
1. Crear un Middleware `CaptureMarketingParams` para interceptar las UTM de la URL y guardarlas en sesión.
2. Registrar el middleware.
3. Crear un servicio `LeadCaptureService` para estructurar los datos del prospecto.
4. Actualizar el esquema de base de datos (`crm_prospectos`) para soportar parámetros UTM (`utm_source`, `utm_medium`, `utm_campaign`).
5. Modificar los controladores correspondientes para adjuntar estos parámetros al contactar con Vircom.

## Fase 2: Gatillos Psicológicos en Landing Page (FOMO y Ofertas) 🔥
**Objetivo:** Agregar componentes visuales a la landing page para incentivar conversiones rápidas mediante urgencia y prueba social.

*Pasos a ejecutar:*
1. Instalar componente `SocialProofNotification.vue`.
2. Instalar componente `OfertaCountdown.vue`.
3. Configurar estos componentes en `Index.vue` con casos reales de TI (Soporte Servidores, CCTV, Pólizas).

## Fase 3: Widget de Cita / Diagnóstico Rápido 📅
**Objetivo:** Proveer una vía rápida para reportes de urgencia o levantamientos técnicos de TI.

*Pasos a ejecutar:*
1. Construir `QuickAppointmentForm.vue` para fallas de red, mantenimiento o instalaciones de seguridad tecnológica.
2. Conectar el formulario a la base de tickets/leads.
3. Incrustar el componente en `Index.vue`.

## Fase 4: Motor de WhatsApp Empresarial Legal (Consentimiento) 📱
**Objetivo:** Preparar la base de datos y la recolección de consentimiento (Opt-In) de WhatsApp para enviar notificaciones legales y útiles (mantenimientos terminados, reportes técnicos).

*Pasos a ejecutar:*
1. Migración para campos de `opt_in` en usuarios/clientes.
2. Modelo `WhatsAppConsentEvent` y tabla correspondiente.
3. Habilitar la captura de este consentimiento en el perfil/configuración del cliente.

---
*Estado:* En progreso.
