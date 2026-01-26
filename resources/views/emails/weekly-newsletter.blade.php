<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->titulo }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #0f172a;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            color: #fbbf24;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .featured-image {
            width: 100%;
            height: auto;
            display: block;
        }

        .content {
            padding: 40px 30px;
            line-height: 1.6;
            color: #334155;
        }

        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .post-title {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .summary {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }

        .btn-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .btn {
            background-color: #6366f1;
            color: #ffffff !important;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            font-size: 14px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }

        .footer b {
            color: #475569;
        }

        .unsubscribe {
            margin-top: 15px;
            font-size: 12px;
        }

        .unsubscribe a {
            color: #94a3b8;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>ASISTENCIA VIRCOM</h1>
        </div>

        <!-- Image -->
        @if($post->imagen_portada)
            <img src="{!! $post->imagen_portada_url !!}" alt="{{ $post->titulo }}" class="featured-image"
                style="width: 100%; max-width: 600px; height: auto; display: block; border: 0;">
        @endif

        <!-- Content -->
        <div class="content">
            <div class="greeting">Hola, {{ $cliente->nombre_razon_social }}</div>

            <p>Esta semana tenemos un artículo que te interesará especialmente para mantener tu infraestructura
                funcionando al 100%:</p>

            <div class="post-title">{{ $post->titulo }}</div>

            <div class="summary">
                {{ $post->resumen }}
            </div>

            <div class="btn-container">
                <a href="{{ $trackToken ? route('newsletter.track.click', $trackToken) : route('public.blog.show', $post->slug) }}"
                    class="btn">
                    Leer artículo completo
                </a>
            </div>

            <p style="margin-top: 40px; font-size: 14px; color: #64748b;">
                Esperamos que esta información te sea de gran utilidad. Si tienes dudas o necesitas soporte técnico,
                nuestro equipo de ingenieros está listo para ayudarte.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><b>Asistencia Vircom</b><br>Expertos en Soluciones Tecnológicas</p>
            <div class="unsubscribe">
                Recibes este correo porque eres cliente de Vircom.<br>
                <a href="{{ config('app.url') }}/newsletter/unsubscribe?email={{ urlencode($cliente->email) }}">Darse de
                    baja</a>
            </div>
            <p style="margin-top: 20px; font-size: 11px;">&copy; {{ date('Y') }} Asistencia Vircom. Todos los derechos
                reservados.</p>
        </div>
    </div>

    @if($trackToken)
        <img src="{{ route('newsletter.track.open', $trackToken) }}" width="1" height="1"
            style="display:none !important;" />
    @endif
</body>

</html>