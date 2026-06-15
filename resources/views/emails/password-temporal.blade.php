@component('mail::message')
# Acceso a tu cuenta

Hemos generado una contraseña temporal para tu cuenta en **Asistencia Vircom**.

**Tu correo:** {{ $email }}
**Contraseña temporal:** `{{ $password }}`

@component('mail::button', ['url' => url('/portal/login')])
Iniciar Sesión
@endcomponent

Por seguridad, te recomendamos cambiar tu contraseña después de iniciar sesión.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
