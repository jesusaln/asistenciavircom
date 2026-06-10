# Módulo de WhatsApp Business API

Este documento describe la implementación completa del módulo de WhatsApp Business API integrado en la aplicación.

## 📋 Resumen

El módulo permite:
- ✅ Envío automático de recordatorios de pago por WhatsApp
- ✅ Gestión de configuración por empresa (multitenant)
- ✅ Procesamiento de webhooks para estados de mensajes
- ✅ Sistema de colas para envío asíncrono
- ✅ UI administrativa completa
- ✅ Logging detallado de todos los mensajes

## 🗄️ Base de Datos

### Migraciones

#### 1. Campos WhatsApp en empresas (`2025_10_11_152927_add_whatsapp_to_empresas_table.php`)

```php
Schema::table('empresas', function (Blueprint $table) {
    $table->boolean('whatsapp_enabled')->default(false);
    $table->string('whatsapp_business_account_id')->nullable();
    $table->string('whatsapp_phone_number_id')->nullable();
    $table->string('whatsapp_sender_phone')->nullable(); // E.164
    $table->text('whatsapp_access_token')->nullable();
    $table->string('whatsapp_app_secret')->nullable();
    $table->string('whatsapp_webhook_verify_token')->nullable();
    $table->string('whatsapp_default_language')->default('es_MX');
    $table->string('whatsapp_template_payment_reminder')->nullable();
});
```

#### 2. Tabla de logs de mensajes (`2025_10_11_152959_create_whatsapp_messages_table.php`)

```php
Schema::create('whatsapp_messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
    $table->string('to')->index(); // número destino E.164
    $table->string('template_name')->nullable();
    $table->json('template_params')->nullable();
    $table->string('message_id')->nullable();
    $table->string('status')->nullable(); // queued/sent/delivered/failed/read
    $table->text('response')->nullable(); // raw API response
    $table->string('error_code')->nullable();
    $table->timestamps();

    // Índices adicionales
    $table->index(['empresa_id', 'status']);
    $table->index(['to', 'created_at']);
});
```

## 🏗️ Arquitectura

### Modelos

#### Empresa
- **Ubicación**: `app/Models/Empresa.php`
- **Campos agregados**: Todos los campos de configuración de WhatsApp
- **Casts**: `whatsapp_access_token` y `whatsapp_app_secret` encriptados

#### WhatsAppMessage
- **Ubicación**: `app/Models/WhatsAppMessage.php`
- **Funciones**: Logging completo de mensajes y estados
- **Estados**: `queued`, `sent`, `delivered`, `failed`, `read`

### Servicios

#### WhatsAppService
- **Ubicación**: `app/Services/WhatsAppService.php`
- **Funciones**:
  - Envío de plantillas vía Meta Graph API
  - Validación de números E.164
  - Manejo de errores y respuestas

### Jobs

#### SendWhatsAppTemplate
- **Ubicación**: `app/Jobs/SendWhatsAppTemplate.php`
- **Características**:
  - Procesamiento en cola con reintentos
  - Backoff exponencial (10s, 30s, 60s)
  - Logging completo de estados

### Controladores

#### WhatsAppWebhookController
- **Ubicación**: `app/Http/Controllers/WhatsAppWebhookController.php`
- **Funciones**:
  - Verificación de webhooks (GET)
  - Procesamiento de notificaciones (POST)
  - Validación de firma HMAC

#### EmpresaWhatsAppController
- **Ubicación**: `app/Http/Controllers/EmpresaWhatsAppController.php`
- **Funciones**:
  - Configuración administrativa
  - Endpoint de prueba

## 🚀 Instalación y Configuración

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

### 2. Configurar Empresa

1. Ir a `/admin/whatsapp/configuracion`
2. Habilitar WhatsApp Business
3. Completar todos los campos requeridos:
   - Business Account ID
   - Phone Number ID
   - Número de teléfono (E.164)
   - Access Token
   - App Secret
   - Token de verificación del webhook
   - Plantilla de recordatorio

### 3. Configurar Webhook en Meta

1. En Facebook Business Manager, configurar webhook
2. URL: `https://sudominio.com/api/webhooks/whatsapp`
3. Token de verificación: El mismo configurado en la empresa
4. Eventos a suscribir: `messages`, `message_template_status_update`

## 📡 API Endpoints

### Webhooks

#### Verificación (GET)
```
GET /api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=TOKEN&hub.challenge=CHALLENGE
```

**Respuesta**: Retorna el `hub.challenge` si el token es válido

#### Recepción de Notificaciones (POST)
```
POST /api/webhooks/whatsapp
Content-Type: application/json
X-Hub-Signature-256: sha256=SIGNATURE
```

### Administración

#### Configuración
```
GET /admin/whatsapp/configuracion
```

#### Prueba de Configuración
```
POST /admin/whatsapp/test
Content-Type: application/json

{
  "telefono": "+52551234567",
  "mensaje": "Mensaje de prueba"
}
```

## ⏰ Scheduler

### Comando para Recordatorios

```bash
# Recordatorios 3 días antes del vencimiento
php artisan whatsapp:enviar-recordatorios --dias=3

# Para empresa específica
php artisan whatsapp:enviar-recordatorios --dias=3 --empresa_id=1
```

### Configuración en `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule)
{
    // Recordatorios diarios a las 9:00 AM
    $schedule->command('whatsapp:enviar-recordatorios --dias=3')
             ->dailyAt('09:00')
             ->withoutOverlapping()
             ->runInBackground();
}
```

## 🧪 Pruebas Manuales

### 1. Probar Configuración

```bash
curl -X POST "http://127.0.0.1:8000/admin/whatsapp/test" \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(grep csrf-token resources/views/app.blade.php | grep -o 'content=\"[^\"]*\"' | sed 's/content=\"//;s/\"//')" \
  -d '{"telefono":"+52551234567","mensaje":"Prueba"}'
```

### 2. Simular Webhook

```bash
# Verificación
curl "http://127.0.0.1:8000/api/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=TU_TOKEN&hub.challenge=12345"

# Estado de mensaje
curl -X POST "http://127.0.0.1:8000/api/webhooks/whatsapp" \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=$(echo -n '{}' | openssl dgst -sha256 -hmac 'TU_APP_SECRET' | cut -d' ' -f2)" \
  -d '{}'
```

## 📊 Monitoreo

### Logs de Mensajes

```php
// Obtener mensajes recientes
$messages = WhatsAppMessage::recent(7)->get();

// Estadísticas por empresa
$stats = WhatsAppMessage::where('empresa_id', $empresaId)
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();
```

### Estados de Mensajes

- `queued`: En cola esperando procesamiento
- `sent`: Enviado correctamente
- `delivered`: Entregado al dispositivo
- `read`: Leído por el usuario
- `failed`: Falló el envío

## 🔧 Solución de Problemas

### Error: "CSRF token mismatch"
- Verificar que la ruta esté en `$except` del middleware
- Confirmar que el middleware personalizado esté registrado

### Error: "Invalid phone number"
- Verificar formato E.164: `+52551234567`
- Incluir código de país correcto

### Error: "Template not approved"
- Verificar que la plantilla esté aprobada en Meta Business
- Usar el nombre exacto de la plantilla

### Webhook no funciona
- Verificar URL del webhook en Meta
- Confirmar token de verificación
- Revisar logs de Laravel

## 🔒 Seguridad

### Encriptación
- `whatsapp_access_token` y `whatsapp_app_secret` se encriptan automáticamente
- Nunca se muestran en texto plano en la UI

### Validación de Firma
- Todos los webhooks validan firma HMAC SHA256
- Firma se calcula usando `whatsapp_app_secret`

### Límites de Rate
- Máximo 3 mensajes por día por cliente
- Jobs con reintentos y backoff para evitar bloqueos

## 📝 Notas de Desarrollo

### Plantilla Sugerida para Meta

**Nombre**: `payment_reminder`
**Idioma**: `es_MX`
**Contenido**:
```
Hola {{1}}, te recordamos que tu pago de {{2}} vence el {{3}}. Por favor realiza tu pago para evitar recargos.
```

### Variables de Plantilla
- `{{1}}`: Nombre del cliente
- `{{2}}`: Monto formateado
- `{{3}}`: Fecha de vencimiento (dd/mm/yyyy)

### Configuración de Meta Business

1. Crear aplicación en Facebook Developers
2. Configurar WhatsApp Business API
3. Crear y aprobar plantilla de mensaje
4. Configurar webhook con eventos necesarios

## 🚀 Próximas Mejoras

- [ ] Soporte para múltiples plantillas
- [ ] Respuestas automáticas a mensajes entrantes
- [ ] Dashboard de estadísticas en tiempo real
- [ ] Configuración de límites por empresa
- [ ] Soporte para medios (imágenes, documentos)