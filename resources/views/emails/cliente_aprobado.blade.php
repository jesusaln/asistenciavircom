<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color:
                {{ $empresa->color_principal ?? '#3b82f6' }}
            ;
            padding: 40px;
            text-align: center;
            color: white;
        }

        .content {
            padding: 40px;
        }

        .footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }

        .btn {
            display: inline-block;
            padding: 16px 32px;
            background-color:
                {{ $empresa->color_principal ?? '#3b82f6' }}
            ;
            color: white !important;
            text-decoration: none;
            border-radius: 12px;
            font-weight: bold;
            margin-top: 20px;
            box-shadow: 0 4px 12px
                {{ ($empresa->color_principal ?? '#3b82f6') }}
                40;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
        }

        p {
            margin: 16px 0;
        }

        .highlight {
            color:
                {{ $empresa->color_principal ?? '#3b82f6' }}
            ;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if($empresa && $empresa->logo_url)
                <img src="{{ $empresa->logo_url }}" alt="{{ $empresa->nombre_empresa }}"
                    style="max-height: 60px; margin-bottom: 20px;">
            @endif
            <h1>¡Excelente noticia!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $cliente->nombre_razon_social }}</strong>,</p>
            <p>Nos complace informarte que tu acceso al <strong>Portal de Clientes</strong> de <span
                    class="highlight">{{ $empresa->nombre_empresa ?? 'nuestra plataforma' }}</span> ha sido aprobado.
            </p>
            <p>A partir de este momento, ya puedes acceder a todas nuestras herramientas exclusivas:</p>
            <ul style="padding-left: 20px;">
                <li>Levantamiento y seguimiento de tickets de soporte.</li>
                <li>Consulta de historial de pedidos y facturación.</li>
                <li>Acceso prioritario a nuestro catálogo de productos y pólizas.</li>
            </ul>
            <div style="text-align: center;">
                <a href="{{ route('portal.login') }}" class="btn">Entrar al Portal ahora</a>
            </div>
            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
                Si tienes alguna duda o problema al ingresar, recuerda que puedes contactarnos directamente respondiendo
                a este correo o vía WhatsApp al {{ $empresa->telefono }}.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $empresa->nombre_empresa }}. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>