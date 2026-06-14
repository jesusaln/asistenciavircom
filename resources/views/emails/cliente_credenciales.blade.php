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

        .credentials-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
            margin: 24px 0;
        }

        .credential-item {
            margin: 8px 0;
            font-size: 15px;
        }

        code {
            font-family: monospace;
            background-color: #e2e8f0;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 14px;
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
            <h1>¡Tus accesos al Portal!</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $cliente->nombre_razon_social }}</strong>,</p>
            <p>Te damos la bienvenida al <strong>Portal de Clientes</strong> de <span
                    class="highlight">{{ $empresa->nombre_empresa ?? 'nuestra plataforma' }}</span>.</p>
            <p>A partir de este momento, puedes ingresar y gestionar tus servicios utilizando las siguientes credenciales:</p>
            
            <div class="credentials-box">
                <div class="credential-item"><strong>Usuario (Correo):</strong> <code>{{ $cliente->email }}</code></div>
                <div class="credential-item"><strong>Contraseña Temporal:</strong> <code>{{ $password }}</code></div>
            </div>

            <p>Desde el portal podrás realizar y dar seguimiento a solicitudes, revisar cotizaciones, ver tus estados de cuenta de crédito, descargar tus facturas y firmar contratos digitales.</p>
            
            <div style="text-align: center;">
                <a href="{{ route('portal.login') }}" class="btn">Entrar al Portal ahora</a>
            </div>
            
            <p style="margin-top: 30px; font-size: 13px; color: #64748b;">
                Por motivos de seguridad, te recomendamos cambiar esta contraseña temporal desde la sección de tu perfil una vez que hayas ingresado.
                Si tienes alguna duda o problema al ingresar, puedes contactarnos respondiendo directamente a este correo o vía WhatsApp al {{ $empresa->telefono }}.
            </p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $empresa->nombre_empresa }}. Todos los derechos reservados.
        </div>
    </div>
</body>

</html>
