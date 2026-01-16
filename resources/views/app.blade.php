<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Configuración de empresa --}}
    @php
        $empresaConfig = \App\Models\EmpresaConfiguracion::getConfig();
    @endphp

    {{-- Información de la empresa --}}
    <meta name="description" content="{{ $empresaConfig->descripcion_empresa ?? 'Sistema de gestión empresarial' }}">
    <meta name="keywords" content="sistema, gestión, empresarial, {{ strtolower($empresaConfig->nombre_empresa ?? 'CDD') }}">
    <meta name="author" content="{{ $empresaConfig->nombre_empresa ?? 'CDD Sistema' }}">

    {{-- Título con nombre de empresa --}}
    <title inertia>
        @if($empresaConfig->nombre_empresa)
            {{ $empresaConfig->nombre_empresa }}
        @else
            {{ config('app.name', 'Asistencia Vircom') }}
        @endif
    </title>

    <!-- Fonts -->
    <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400&display=swap" as="style">
    <link href="https://fonts.bunny.net/css?family=figtree:400&display=swap" rel="stylesheet" />
    <!-- Fallback font for better Spanish character support -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    {{-- Favicon dinámico --}}
    @if($empresaConfig->favicon_path)
        <link rel="icon" href="{{ asset('storage/' . $empresaConfig->favicon_path) }}" type="image/x-icon">
    @else
        <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/x-icon">
    @endif

    {{-- Logo para meta tags --}}
    @if($empresaConfig->logo_path)
        <meta property="og:image" content="{{ asset('storage/' . $empresaConfig->logo_path) }}">
        <meta property="og:title" content="{{ $empresaConfig->nombre_empresa ?? config('app.name') }}">
        <meta property="og:description" content="{{ $empresaConfig->descripcion_empresa ?? 'Sistema de gestión empresarial' }}">
    @endif

    {{-- Estilos dinámicos con colores de empresa --}}
    <style>
        :root {
            --color-primary: {{ $empresaConfig->color_principal ?? '#3B82F6' }};
            --color-secondary: {{ $empresaConfig->color_secundario ?? '#1E40AF' }};
            --empresa-nombre: "{{ addslashes($empresaConfig->nombre_empresa ?? 'CDD Sistema') }}";
        }
        
        /* Ensure proper font rendering for Spanish characters */
        body {
            font-family: 'Figtree', 'Inter', 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
    </style>

    @if($empresaConfig->mantenimiento)
        <style>
            body {
                background: linear-gradient(45deg, #f3f4f6, #e5e7eb);
            }
            body::before {
                content: "";
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 9998;
            }
            .mantenimiento-modal {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
                text-align: center;
                z-index: 9999;
                max-width: 500px;
                width: 90%;
            }
        </style>
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    {{-- Modal de mantenimiento --}}
    @if($empresaConfig->mantenimiento ?? false)
        <div class="mantenimiento-modal">
            <div class="mb-4">
                @if($empresaConfig->logo_path ?? false)
                    <img src="{{ asset('storage/' . $empresaConfig->logo_path) }}" alt="Logo" class="w-16 h-16 mx-auto mb-4 object-contain" />
                @endif
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Sistema en Mantenimiento</h2>
            <p class="text-gray-600 mb-4">{{ $empresaConfig->mensaje_mantenimiento ?? 'El sistema se encuentra temporalmente fuera de servicio por mantenimiento. Por favor, inténtalo más tarde.' }}</p>
            <div class="text-sm text-gray-500">
                <p>{{ $empresaConfig->nombre_empresa ?? 'CDD Sistema' }}</p>
                @if($empresaConfig->email ?? false)
                    <p>Email: {{ $empresaConfig->email }}</p>
                @endif
                @if($empresaConfig->telefono ?? false)
                    <p>Tel: {{ $empresaConfig->telefono }}</p>
                @endif
            </div>
        </div>
    @endif

    @inertia
</body>

</html>

