# 🕒 Pendientes Próxima Sesión: Activación Clawbot (Vircom AI)

He dejado todo listo en el servidor para que **Clawbot (Vircom AI)** comience a operar, pero falta el "puente" de comunicación (WhatsApp). Aquí tienes lo que quedó pendiente para cuando regreses:

## 1. 🔑 Datos de Meta (WhatsApp API)
Necesitamos los siguientes dos valores del portal [developers.facebook.com](https://developers.facebook.com/):
- **Phone Number ID:** Identificador único de tu número en Meta.
- **Permanent Access Token:** El token de larga duración (empieza con `EA...`).
    - *Asegúrate de marcar estos permisos al generarlo:* `whatsapp_business_messaging`, `whatsapp_business_management`, `business_management`, y `whatsapp_business_manage_events`.

> **💡 Vía Rápida (Explorador de la API Graph):** Lo que viste en Meta sobre el "Explorador" es una herramienta que podemos usar para generar un token temporal y probar si los mensajes de Clawbot llegan rápido. Si estás en esa pantalla, el "Explorador" te permite sacar una muestra del código que necesito para terminar la conexión.

## 2. 🤖 Tareas de Clawbot una vez conectado
En cuanto me pases esos datos, activaré las siguientes funciones proactivas:
- [ ] **Reporte de Bienvenida:** Prueba de conexión exitosa a tu WhatsApp.
- [ ] **Monitoreo de Blog:** Alerta inmediata cuando alguien lea tus artículos (especialmente el de No-Breaks).
- [ ] **Resumen de Cobranza:** Reporte matutino mensual de deudas pendientes.
- [ ] **Alertas de Stock:** Aviso cuando un producto estrella esté por agotarse.

## 3. 🌐 Integración Front-End (Opcional)
Me mandaste el código del **Facebook SDK for Javascript**. Esto sirve para:
- Poner un botón de **Chat de WhatsApp** directamente en tu página web.
- **Login con Facebook:** Permitir que tus clientes entren a ver sus facturas o pólizas usando su cuenta de FB. (Ya tenemos el código del botón `<fb:login-button>` y la función `checkLoginState`).
- Rastrear eventos de conversión (Píxel).
*Lo integraremos en la web una vez que el servidor (back-end) esté conectado.*

## 4. 📝 Blog pendiente
- El artículo sobre **No-Breaks** (ID 7) ya está en modo **Borrador** con diseño Dark Premium y botones de WhatsApp. Solo falta tu revisión final para publicarlo.

---
**Instrucciones para retomar:** 
Solo dime: *"Ya tengo los datos de WhatsApp"* y continuaremos desde el punto 1. 

¡Que tengas un excelente día, Jesús! 🌑⚡
