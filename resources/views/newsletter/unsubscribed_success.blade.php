<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción Cancelada - Vircom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 flex items-center justify-center min-h-screen p-6 text-center">
    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-xl p-12 border border-gray-100">
        <div class="text-6xl mb-6">🔕</div>
        <h1 class="text-3xl font-black text-gray-900 mb-4 tracking-tight">Suscripción Cancelada</h1>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Hemos procesado tu solicitud. El correo <span class="font-bold text-orange-600">{{ $email }}</span> ya no
            recibirá más boletines semanales de Vircom.
        </p>
        <div class="space-y-4">
            <a href="/"
                class="block w-full py-4 bg-gray-900 text-white rounded-xl font-bold hover:bg-black transition-all">
                Ir al inicio
            </a>
            <p class="text-xs text-gray-400">
                Si fue un error, por favor contacta a soporte para reactivarte.
            </p>
        </div>
    </div>
</body>

</html>