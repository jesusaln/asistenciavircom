# Documentación de APIs de Pago para Desarrollo

Esta guía detalla cómo utilizar las APIs de pago implementadas para el módulo de contratación de pólizas. Estas APIs están configuradas para funcionar en modo **Sandbox/Test**, permitiendo simulaciones completas sin realizar cobros reales.

## Configuración Inicial

Asegúrate de configurar las credenciales de prueba en tu archivo `.env`. Puedes obtener estas credenciales registrándote en los portales de desarrolladores correspondientes:

- **PayPal**: [Developer Dashboard](https://developer.paypal.com/)
- **MercadoPago**: [Developers Panel](https://www.mercadopago.com.mx/developers/panel)
- **Stripe**: [Dashboard](https://dashboard.stripe.com/test/apikeys)

Consulta el archivo `.env.example` para ver las variables requeridas.

---

## Endpoints Disponibles

Todos los endpoints responden con JSON y requieren que la póliza haya sido creada previamente (estado `pendiente_pago`).

### 1. Obtener Pasarelas Disponibles

Verifica qué métodos de pago están activos y configurados.

**GET** `/pago/poliza/pasarelas`

**Respuesta:**
```json
{
    "gateways": {
        "paypal": true,
        "mercadopago": true,
        "stripe": true
    },
    "currency": "MXN"
}
```

---

### 2. PayPal

#### Crear Orden
Inicia el proceso de pago. Devuelve el link para aprobar el pago en PayPal Sandbox.

**POST** `/pago/poliza/paypal/crear`

**Body:**
```json
{
    "poliza_id": 123
}
```

**Respuesta:**
```json
{
    "success": true,
    "order_id": "5X4329...",
    "approve_url": "https://www.sandbox.paypal.com/checkoutnow?token=5X4329...",
    "mode": "sandbox"
}
```

#### Capturar Pago
Una vez aprobado el pago por el usuario (tras visitar `approve_url`), llama a este endpoint con el `order_id`.

**POST** `/pago/poliza/paypal/capturar`

**Body:**
```json
{
    "order_id": "5X4329...",
    "poliza_id": 123
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Pago completado exitosamente",
    "redirect": "http://localhost:8000/contratacion/exito?slug=plan-x"
}
```

---

### 3. MercadoPago

#### Crear Preferencia
Genera una preferencia de pago. Usa `init_point` para redireccionar al usuario a MercadoPago.

**POST** `/pago/poliza/mercadopago/crear`

**Body:**
```json
{
    "poliza_id": 123
}
```

**Respuesta:**
```json
{
    "success": true,
    "preference_id": "123456-...",
    "init_point": "https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=...",
    "sandbox_init_point": "https://sandbox.mercadopago.com.mx/checkout/v1/redirect?pref_id=..."
}
```
*Nota: Para desarrollo, usa `sandbox_init_point`.*

---

### 4. Stripe (Tarjeta de Crédito)

#### Método A: Payment Intent (Para elementos personalizados)
Si estás construyendo tu propio formulario de tarjeta con Stripe Elements.

**POST** `/pago/poliza/stripe/intent`

**Body:**
```json
{
    "poliza_id": 123
}
```

**Respuesta:**
```json
{
    "success": true,
    "client_secret": "pi_123..._secret_...",
    "payment_intent_id": "pi_123...",
    "public_key": "pk_test_..."
}
```

#### Método B: Checkout Session (Página alojada por Stripe)
Más fácil de integrar. Redirige al usuario a la URL devuelta.

**POST** `/pago/poliza/stripe/checkout`

**Body:**
```json
{
    "poliza_id": 123
}
```

**Respuesta:**
```json
{
    "success": true,
    "session_id": "cs_test_...",
    "checkout_url": "https://checkout.stripe.com/c/pay/..."
}
```

---

## Webhooks

Para pruebas locales, necesitarás usar herramientas como **Ngrok** para exponer tu localhost.

- PayPal: `/pago/poliza/paypal/webhook`
- MercadoPago: `/pago/poliza/mercadopago/webhook`
- Stripe: `/pago/poliza/stripe/webhook`

Asegúrate de configurar estas URLs en los paneles de desarrollador de cada pasarela apuntando a tu URL ngrok (ej. `https://mi-ngrok.app/pago/poliza/paypal/webhook`).
