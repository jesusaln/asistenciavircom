# VircomBot - Integración de IA con Groq

## Resumen
Integración de un chatbot inteligente (VircomBot) en el portal de clientes usando la API de Groq con el modelo llama-3.3-70b.

## Configuración Implementada

### Archivos Modificados/Creados:
1. **`app/Services/AI/GroqService.php`** - Servicio para conectar con la API de Groq
2. **`app/Services/AI/OllamaService.php`** - Servicio backup para Ollama local
3. **`app/Http/Controllers/Api/ChatbotController.php`** - Controlador con soporte multi-proveedor
4. **`config/services.php`** - Configuración de Groq y Ollama
5. **`resources/js/Components/Chatbot/ChatbotWidget.vue`** - Widget de chat en el frontend
6. **`resources/js/Pages/Portal/Layout/ClientLayout.vue`** - Layout del portal con el widget integrado

### Variables de Entorno (.env):
```env
# Groq API (Producción)
GROQ_API_KEY=tu_api_key_de_groq
GROQ_MODEL=llama-3.3-70b-versatile
GROQ_TEMPERATURE=0.7
AI_PROVIDER=groq
```

## Herramientas del Bot (Function Calling)

VircomBot puede ejecutar las siguientes acciones automáticamente:

| Herramienta | Descripción |
|-------------|-------------|
| `consultar_disponibilidad` | Verifica horarios disponibles para una fecha |
| `agendar_cita` | Crea una nueva cita en el sistema |
| `consultar_precios` | Busca precios de servicios/productos |
| `consultar_estado_reparacion` | Consulta estado de un ticket por folio |

## Endpoint API

```
POST /api/webhooks/chat
Content-Type: application/json

{
    "message": "Texto del usuario",
    "session_id": "identificador_sesion"
}
```

**Respuesta:**
```json
{
    "message": "Respuesta del bot",
    "action_taken": "nombre_herramienta" // Si ejecutó alguna
}
```

## Cambiar Proveedor de IA

En `.env`, modificar `AI_PROVIDER`:
- `groq` - API de Groq (rápido, requiere internet)
- `ollama` - Ollama local (sin internet, requiere modelo instalado)

## Fecha de Implementación
17 de Enero de 2026

## Notas
- Groq tiene un tier gratuito con límites generosos
- El modelo llama-3.3-70b es excelente para function calling
- Tiempo de respuesta promedio: ~1 segundo
