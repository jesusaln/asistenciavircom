---
description: Proceso de mantenimiento automático de pólizas
---

# 📅 Guía de Automatización de Mantenimientos

Esta guía explica cómo funciona el proceso automático de generación de tickets y citas para pólizas de servicio y cómo realizar pruebas.

## ⚙️ El Proceso Automático

1.  **Configuración de la Póliza**:
    *   **Frecuencia (Meses)**: Cada cuánto tiempo se debe realizar el mantenimiento (ej: 6 meses).
    *   **Próxima Visita**: La fecha programada para el siguiente servicio.
    *   **Autogenerar Ticket/Cita**: Debe estar marcado para que el sistema actúe.

2.  **Ejecución Diaria**:
    *   El sistema tiene programado un comando que se ejecuta todos los días a las **07:15 AM**.
    *   Comando técnico: `php artisan app:process-poliza-maintenance`.

3.  **Acciones del Sistema**:
    *   Busca pólizas activas con fecha de mantenimiento vencida (hoy o antes).
    *   Crea un **Ticket** de soporte con la categoría "Mantenimiento".
    *   Crea una **Cita** técnica vinculada al ticket para la fecha programada.
    *   Calcula la **nueva fecha de mantenimiento** sumando la frecuencia (meses) a la fecha actual.
    *   Envía una **notificación por correo** al cliente con todos los detalles.

## 🧪 Cómo Realizar Pruebas

Para verificar que todo funcione correctamente sin esperar a la ejecución programada:

### 1. Preparar los datos
*   Ve al módulo de **Pólizas de Servicio**.
*   Edita una póliza existente (o crea una nueva).
*   En la sección de **Mantenimiento Preventivo**:
    *   Establece una **Frecuencia** (ej: 3 meses).
    *   Pon la **Próxima Visita** con la fecha de **hoy** (o ayer).
    *   Marca el check **Autogenerar Ticket/Cita**.
*   Guarda los cambios.

### 2. Ejecutar el comando de prueba
// turbo
```bash
php artisan app:process-poliza-maintenance
```

### 3. Verificar resultados
*   **En la Póliza**: Verás que la "Próxima Visita" se ha movido automáticamente al futuro (ej: 3 meses después).
*   **En Tickets**: Debería aparecer un nuevo ticket de mantenimiento preventivo.
*   **En Citas**: Debería haber una nueva cita programada vinculada a ese ticket.
*   **En Logs**: Puedes revisar `storage/logs/laravel.log` para ver el detalle de la ejecución.
*   **Notificación**: Si el cliente tiene un correo válido, se habrá enviado la notificación (puedes verificarlo en Mailtrap o tu servicio de correo).

---
*Nota: Si la frecuencia es 0, el sistema generará el servicio una sola vez y luego desactivará la autogeneración para esa póliza.*
