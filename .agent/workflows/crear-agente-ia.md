---
description: Integrar Agente IA (Llama 3.1) con Laravel para Atención al Cliente y Citas
---

Este workflow describe cómo crear un servicio en Laravel que conecte con Ollama (Llama 3.1) en el VPS para responder mensajes de clientes y agendar citas automáticamente.

### 1. Requisitos Previos (Ya completados)
- VPS con Ollama instalado.
- Modelo `llama3.1` descargado.
- Laravel 11+ funcionando.

### 2. Configurar Cliente Ollama en Laravel
Instalar una librería o crear un servicio HTTP simple para hablar con la API local de Ollama.

**Archivo: `app/Services/AI/OllamaService.php`**
(Este servicio manejará la comunicación con http://localhost:11434)

```php
// Ejemplo de estructura
public function chat(string $prompt, array $history = []) {
    // Petición HTTP a localhost:11434/api/chat
    // Definir System Prompt: "Eres un asistente de Asistencia Vircom..."
    // Definir Herramientas: "agendar_cita", "consultar_disponibilidad"
}
```

### 3. Definir Herramientas (Function Calling)
Llama 3.1 soporta el uso de herramientas. Debemos enseñarle qué puede hacer.

**Herramientas Clave:**
- `check_availability(date)`: Revisa si hay huecos en la agenda.
- `book_appointment(client_data, date, issue)`: Crea el registro en la tabla `citas`.
- `get_services()`: Lista los servicios disponibles.

### 4. Crear Endpoint de Webhook (WhatsApp/Chat Web)
Necesitamos una ruta para recibir los mensajes del cliente.

**Ruta:** `POST /api/webhook/chat`
**Controlador:** `ChatbotController`

Flujo:
1. Recibe mensaje del usuario.
2. Envía historial + mensaje a `OllamaService`.
3. Si Llama responde texto -> Enviar al usuario.
4. Si Llama pide ejecutar herramienta (ej. `book_appointment`) -> Laravel ejecuta la acción en BD y le devuelve el resultado a Llama.
5. Llama genera la respuesta final ("¡Listo! Tu cita quedó agendada...").

### 5. Integración con Frontend (Ionic/Vue)
- **Ionic App:** Añadir una pantalla de "Asistente Virtual".
- **Web:** Widget de chat flotante.

### 6. Integración con WhatsApp (Opcional pero recomendado)
Usar un proveedor como Twilio o Meta Cloud API para conectar un número de WhatsApp a nuestro webhook.

### 7. Prompt del Sistema (La "Personalidad")
```text
Eres VircomBot, el asistente experto de Asistencia Vircom.
Tu objetivo es ayudar a clientes a agendar reparaciones y mantenimientos.
- Sé amable y profesional.
- Antes de agendar, pide: Nombre, Dirección y Problema.
- Si no sabes algo, no inventes. Deriva con un humano.
- Usa la herramienta 'check_availability' antes de confirmar una hora.
```

### Próximos Pasos para Implementar:
1. Crear `App\Services\AI\OllamaService`.
2. Crear `App\Http\Controllers\Api\ChatbotController`.
3. Definir la ruta API.
4. Probar con `curl`.
