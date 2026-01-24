# 🛡️ Guía de Seguridad y Entregabilidad: Newsletter Vircom

Para evitar que tu VPS sea bloqueado por Gmail, Outlook o Hostinger, hemos implementado las mejores prácticas de la industria.

## 1. 🚦 Throttling (Envío Programado)
**El problema:** Hostinger bloquea cuentas que envían más de 10 correos por minuto o 100 por día si se usa `sendmail`.
**Nuestra solución:** El comando `newsletter:send` ahora programa los correos de forma espaciada (8 cada minuto). 
*   Para enviar a los 600 clientes, el sistema se tomará unos **75 minutos**.
*   Esto hace que el envío parezca "humano" y no un ataque de spam, manteniendo tu servidor seguro.

## 2. 🔐 Autenticación DNS (CRÍTICO)
Debes configurar estos tres registros en tu panel de Hostinger/DNS para que tus correos no lleguen a SPAM:

1.  **SPF:** Debe incluir la IP de tu VPS (`191.101.233.82`). 
    *   *Ejemplo:* `v=spf1 ip4:191.101.233.82 include:_spf.mailcow.email ~all`
2.  **DKIM:** Es una firma digital. Mailcow la genera automáticamente. Debes copiar la clave pública que te da Mailcow y pegarla en un registro TXT de tu DNS.
3.  **DMARC:** Indica qué hacer si falla el SPF o DKIM (ponlo en modo `quarantine` o `none` al principio).

## 3. 🧹 Higiene de la Lista
*   **Darse de baja:** He incluido un enlace de "Darse de baja" al final de cada correo. Si un cliente hace clic, el sistema marca `recibe_newsletter = false` automáticamente. **Nunca quites este link**, si no el cliente marcará el correo como "Spam" y eso dañará tu reputación.
*   **Correos Inválidos:** Si un correo rebota (Bounce), Mailcow te avisará. Debes darlos de baja en el sistema para no seguir intentando enviar a direcciones que no existen.

## 4. 📧 El nuevo remitente: `blog@asistenciavircom.com`
Usar un correo específico para el blog protege tu cuenta personal (`jlopez`). Si por alguna razón el correo del blog es marcado como spam, tu correo personal de trabajo seguirá funcionando normalmente.

---
### 🚀 Comando para enviar el boletín
Cuando tengas un nuevo artículo en el blog, solo ejecuta:
```bash
php artisan newsletter:send
```
El sistema buscará el artículo más reciente y se encargará de enviarlo a todos tus contactos suscritos de forma segura.
