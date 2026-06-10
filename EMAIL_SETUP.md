# Configuración de Email para Mailcow

## 📧 Configurar el Email

El archivo `.env` ya está configurado para usar **Mailcow** como servidor de correo.

### 🔧 Configuración Actual:
```env
MAIL_MAILER=smtp
MAIL_HOST=192.168.1.55
MAIL_PORT=587
MAIL_USERNAME=soporte@climasdeldesierto.com
MAIL_PASSWORD=TU_PASSWORD_AQUI
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=soporte@climasdeldesierto.com
MAIL_FROM_NAME=Climas del Desierto
```

### ⚠️ IMPORTANTE - Pasos para activar:

1. **Reemplaza el password** en `.env`:
   ```bash
   # Cambia esta línea con el password real de Mailcow
   MAIL_PASSWORD="tu_password_real_de_mailcow"
   ```

2. **Verifica el email** en Mailcow:
   - Usuario: `soporte@climasdeldesierto.com`
   - Debe existir en Mailcow
   - Debe tener permisos de envío SMTP

3. **Limpia la configuración cache**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **Prueba el envío de email**:
   ```bash
   php artisan tinker
   >>> Mail::raw('Test', fn($m) => $m->to('tu@email.com')->subject('Test'))
   ```

### 🔄 URLs de recuperación de contraseña:

- **Forgot Password**: http://192.168.1.55.nip.io:8001/forgot-password
- **Reset Password**: Se genera automáticamente con token único

### 📝 Flujo de Recuperación:

1. Usuario va a `/forgot-password`
2. Ingresa su email
3. Sistema envía email con enlace de recuperación
4. Usuario recibe email con link
5. Usuario hace clic en link y va a `/reset-password/{token}`
6. Usuario ingresa nueva contraseña
7. Contraseña actualizada exitosamente

### 🔐 Security:

- Rate limiting: 5 intentos por minuto
- Tokens expiran en 60 minutos
- CSRF protection habilitado
- Validación de email requerida

### 🎨 Diseño:

La página de forgot password ahora tiene:
- ✅ Mismo diseño que el login (dark mode)
- ✅ Validación visual de email
- ✅ Spinner de carga al enviar
- ✅ Banner de errores visible
- ✅ Ícono de sobre en el campo email
- ✅ Botón para volver al login
- ✅ Instrucciones claras para el usuario
