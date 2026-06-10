<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="facebook-domain-verification" content="xmfjgripw60chso5uz1m9xejiccmtc" />

    {{-- Previene flash de tema incorrecto — aplica clase antes de pintar --}}
    <script>
        (function(){var k='cdd_theme';var t=localStorage.getItem(k);var d;if(t==='dark'){d=true}else if(t==='light'){d=false}else{var p=localStorage.getItem('darkModePreference');if(p==='manual'){d=localStorage.getItem('theme')==='dark'||localStorage.getItem('darkMode')==='true'}else{d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches}};if(d){document.documentElement.classList.remove('light');document.documentElement.classList.add('dark')}})()
    </script>
    <link rel="manifest" href="/manifest.webmanifest">

    {{-- Configuración de empresa --}}
    @php
        $empresaConfig = \App\Models\EmpresaConfiguracion::getConfig();
    @endphp

    {{-- Información de la empresa --}}
    <meta name="description" content="{{ $empresaConfig->descripcion_empresa ?? 'Expertos en venta, instalación y mantenimiento de aires acondicionados en Hermosillo y Sonora. Distribuidor autorizado Mirage.' }}">
    <meta name="keywords"
        content="aire acondicionado, minisplit, mirage, hermosillo, sonora, instalacion aircon, mantenimiento aires, inverter, {{ strtolower($empresaConfig->nombre_empresa ?? 'Climas del Desierto') }}">
    <meta name="author" content="{{ $empresaConfig->nombre_empresa ?? 'Climas del Desierto' }}">

    {{-- Título con nombre de empresa --}}
    <title inertia>
        @if($empresaConfig->nombre_empresa)
            {{ $empresaConfig->nombre_empresa }}
        @else
            {{ config('app.name', 'Climas del Desierto') }}
        @endif
    </title>

    <!-- Fonts -->
    <link rel="preload" href="https://fonts.bunny.net/css?family=figtree:400&display=swap" as="style">
    <link href="https://fonts.bunny.net/css?family=figtree:400&display=swap" rel="stylesheet" />
    <!-- Fallback font for better Spanish character support -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    {{-- Favicon dinámico --}}
    @if($empresaConfig->favicon_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresaConfig->favicon_path))
        <link rel="icon" href="{{ \App\Helpers\UrlHelper::storageUrl($empresaConfig->favicon_path) }}" type="image/x-icon">
    @else
        <link rel="icon" href="{{ asset('images/logo.webp') }}" type="image/x-icon">
    @endif

    {{-- Logo para meta tags --}}
    @if($empresaConfig->logo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresaConfig->logo_path))
        <meta property="og:image" content="{{ \App\Helpers\UrlHelper::storageUrl($empresaConfig->logo_path) }}">
        <meta property="og:title" content="{{ $empresaConfig->nombre_empresa ?? config('app.name') }}">
        <meta property="og:description"
            content="{{ $empresaConfig->descripcion_empresa ?? 'Sistema de gestión empresarial' }}">
    @endif

    {{-- Estilos dinámicos con colores de empresa --}}
    <style>
        :root {
            --color-primary:
                {{ $empresaConfig->color_principal ?? '#F59E0B' }}
            ;
            --color-primary-soft:
                {{ ($empresaConfig->color_principal ?? '#F59E0B') . '15' }}
            ;
            --color-secondary:
                {{ $empresaConfig->color_secundario ?? '#D97706' }}
            ;
            --empresa-nombre: "{{ addslashes($empresaConfig->nombre_empresa ?? 'CLIMAS DEL DESIERTO') }}";
        }

        /* Ensure proper font rendering for Spanish characters */
        body {
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
    @php
        $hasViteBuild = file_exists(public_path('build/manifest.json'));
        $hasViteHot = file_exists(public_path('hot'));
    @endphp
    @if($hasViteBuild || $hasViteHot)
        @vite(['resources/js/app.js', 'resources/css/app.css'])
    @endif
    @if(config('services.meta.enabled'))
    <!-- Meta Pixel Code -->
    <script>
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () {
                n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0';
            n.queue = []; t = b.createElement(e); t.async = !0;
            t.src = v; s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window, document, 'script',
            'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ config('services.meta.pixel_id') }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ config('services.meta.pixel_id') }}&ev=PageView&noscript=1" /></noscript>
    <!-- End Meta Pixel Code -->
    @endif

    @if(config('services.google_maps.key'))
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places"
            defer></script>
    @endif

    {{-- SEO Canónico: Asegura que todas las páginas tengan una versión sin parámetros visible para Google --}}
    <link rel="canonical" href="{{ url()->current() }}">

    @inertiaHead
</head>

<body class="font-sans antialiased">
    {{-- Modal de mantenimiento --}}
    @if($empresaConfig->mantenimiento ?? false)
        <div class="mantenimiento-modal">
            <div class="mb-4">
                @if(($empresaConfig->logo_path ?? false) && \Illuminate\Support\Facades\Storage::disk('public')->exists($empresaConfig->logo_path))
                    <img src="{{ \App\Helpers\UrlHelper::storageUrl($empresaConfig->logo_path) }}" alt="Logo"
                        class="w-16 h-16 mx-auto mb-4 object-contain" />
                @endif
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Sistema en Mantenimiento</h2>
            <p class="text-gray-600 mb-4">
                {{ $empresaConfig->mensaje_mantenimiento ?? 'El sistema se encuentra temporalmente fuera de servicio por mantenimiento. Por favor, inténtalo más tarde.' }}
            </p>
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
