<x-mail::message>
    # 🧪 Prueba de Conexión Vircom

    Este es un correo de prueba enviado desde tu servidor **Mailcow** configurado en el VPS.

    Si estás leyendo esto, significa que:
    1. La conexión SMTP es correcta.
    2. Tu servidor está listo para enviar el boletín a los 600 clientes.

    <x-mail::button :url="config('app.url')">
        Ir al Panel Administrador
    </x-mail::button>

    Gracias,<br>
    {{ config('app.name') }}
</x-mail::message>