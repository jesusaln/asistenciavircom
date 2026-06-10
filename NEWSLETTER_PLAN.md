# 📈 Plan de Implementación: Sistema de Newsletter Vircom

Este plan detalla las fases para implementar un sistema de envío masivo de boletines semanales utilizando el servidor **Mailcow** propio, integrado con el blog de Laravel.

## Fase 1: Base de Datos y Configuración (Hoy)
- [ ] **Verificación de Clientes:** Asegurar que la tabla `clientes` tenga campo de `email` y un nuevo campo `recibe_newsletter` (boolean).
- [ ] **Configuración SMTP:** Validar conexión con Mailcow en `.env` mediante un comando de prueba.
- [ ] **Mantenimiento de Lista:** Crear el comando para importar/sincronizar los 600 clientes actuales.

## Fase 2: El Mensaje y la Plantilla (Diseño)
- [ ] **Mailable Pro:** Crear la clase `WeeklyNewsletter` en Laravel.
- [ ] **Plantilla Premium:** Diseñar una plantilla HTML responsiva con los colores corporativos de Vircom (Naranja/Gris) que extraiga automáticamente la imagen y resumen del último post del blog.
- [ ] **Link de Desuscripción:** Implementar la lógica legal para que los clientes puedan dejar de recibir correos si lo desean.

## Fase 3: Automatización y Envíos Masivos
- [ ] **Job & Queue:** Crear el Job `SendNewsletterBatch` para procesar los 600 correos en segundo plano (uso de Laravel Queues).
- [ ] **Batching:** Implementar `Bus::batch` para que el sistema pueda reanudar el envío si hay algún fallo del servidor.
- [ ] **Scheduler:** Configurar una tarea programada (`App\Console\Kernel`) para que el boletín se dispare automáticamente cada viernes a las 9:00 AM.

## Fase 4: Panel de Control (Admin)
- [ ] **Dashboard de Envío:** Crear una vista sencilla para ver:
    - ¿Cuántos correos se enviaron exitosamente?
    - ¿Cuántos fallaron?
    - ¿Quién se dessuscribió?
- [ ] **Prueba de Spam:** Realizar pruebas con Mail-Tester para asegurar que lleguemos 10/10 a la bandeja de entrada.

---
*Este sistema ahorrará costos de Mailchimp/Brevo y profesionalizará la comunicación con los clientes de Póliza.*
