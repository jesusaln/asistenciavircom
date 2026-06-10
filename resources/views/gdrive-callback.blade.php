<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Drive - Autorización</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 400px;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success {
            color: #10b981;
        }

        .error {
            color: #ef4444;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }

        p {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .btn {
            background: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn:hover {
            background: #3367d6;
        }
    </style>
</head>

<body>
    <div class="card">
        @if($success)
            <div class="icon success">✓</div>
            <h1>¡Autorización Exitosa!</h1>
            <p>{{ $message }}</p>
            <button class="btn" onclick="window.close()">Cerrar Ventana</button>
            <script>
                // Notificar a la ventana padre
                if (window.opener) {
                    window.opener.postMessage({ type: 'gdrive_auth_success' }, '*');
                }
            </script>
        @else
            <div class="icon error">✗</div>
            <h1>Error de Autorización</h1>
            <p>{{ $message }}</p>
            <button class="btn" onclick="window.close()">Cerrar</button>
        @endif
    </div>
</body>

</html>