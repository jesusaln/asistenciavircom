<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Oferta Exclusiva | Asistencia Vircom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0f172a;
            color: white;
            font-family: sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-enter {
            opacity: 0;
            transform: scale(0.95);
        }

        .modal-enter-active {
            opacity: 1;
            transform: scale(1);
            transition: all 0.3s ease-out;
        }
    </style>
</head>

<body class="flex flex-col items-center min-h-screen p-4">

    <!-- Header -->
    <header class="w-full max-w-4xl flex justify-between items-center py-6">
        <h1 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-300">VIRCOM
            SEGURIDAD</h1>
        <span
            class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-sm font-semibold border border-red-500/30">OFERTA
            ENERO</span>
    </header>

    <!-- Hero Section -->
    <main class="w-full max-w-2xl mt-8 text-center">
        <h2 class="text-4xl md:text-6xl font-extrabold tracking-tight mb-4 text-white">
            HiLook 1080p <br>
            <span class="text-blue-500">Seguridad Profesional</span>
        </h2>
        <p class="text-slate-400 text-lg mb-8">
            Cámara Bala TurboHD 2 Megapixeles. Visión Nocturna Inteligente y Resistencia Total a Intemperie (IP67).
        </p>

        <!-- Product Card -->
        <div
            class="glass rounded-3xl p-8 mb-8 shadow-2xl shadow-blue-500/10 hover:shadow-blue-500/20 transition-all duration-300 transform hover:-translate-y-1">
            <div class="mb-6 flex justify-center">
                <img src="/img/promo-hilook.png" alt="Kit HiLook TurboHD"
                    class="w-full max-w-md rounded-2xl shadow-2xl">
            </div>

            <h3 class="text-xl font-bold bg-white text-slate-900 rounded-lg py-1 px-4 inline-block mb-4">Modelo
                HL-1080-CV-A</h3>

            <ul class="text-left space-y-3 mb-8 text-slate-300 max-w-sm mx-auto">
                <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Alta Definición 1080p (2MP)
                </li>
                <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Visión Nocturna Smart IR (30
                    metros)</li>
                <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Lente Gran Angular 2.8mm</li>
                <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> Cuerpo Metálico Antivandálico y
                    Agua (IP67)</li>
                <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> <strong>Garantía y Soporte
                        Vircom</strong></li>
            </ul>

            <div class="flex flex-col items-center">
                <span class="text-slate-500 line-through text-lg">$4,500 MXN</span>
                <span class="text-5xl font-bold text-white mb-2">$2,950<span
                        class="text-xl text-slate-400">.00</span></span>
                <span class="text-sm text-blue-300 font-medium mb-6">Precio Final de Enero</span>

                <button onclick="openModal()"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 px-8 rounded-xl transition-all shadow-lg hover:shadow-blue-500/50 flex items-center justify-center gap-2 group cursor-pointer">
                    <span>QUIERO APARTAR UNA</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
                <p class="mt-4 text-xs text-slate-500">Solo 5 unidades disponibles a este precio.</p>
            </div>
        </div>
    </main>

    <!-- Modal de Registro -->
    <div id="leadModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="glass w-full max-w-md rounded-3xl p-8 relative overflow-hidden">
            <button onclick="closeModal()"
                class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
            </button>

            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold mb-2">¡Casi lista!</h3>
                <p class="text-slate-400">Déjanos tus datos para asegurar tu equipo en el CRM y contactarte.</p>
            </div>

            <form id="leadForm" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Nombre Completo</label>
                    <input type="text" name="nombre" required placeholder="Tu nombre..."
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Teléfono (WhatsApp)</label>
                    <input type="tel" name="telefono" required placeholder="Ej: 6624590092"
                        class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>

                <button type="submit" id="submitBtn"
                    class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg hover:shadow-blue-500/50 flex items-center justify-center gap-2">
                    <span id="btnText">REGISTRAR Y CONTINUAR</span>
                    <svg id="loadingSpinner" class="animate-spin h-5 w-5 text-white hidden"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <footer class="text-slate-600 text-sm mt-8">
        &copy; 2026 Asistencia Vircom. Tecnología con Confianza.
    </footer>

    <script>
        function openModal() {
            const modal = document.getElementById('leadModal');
            modal.classList.remove('hidden');
            modal.querySelector('div').classList.add('modal-enter-active');
        }

        function closeModal() {
            const modal = document.getElementById('leadModal');
            modal.classList.add('hidden');
        }

        document.getElementById('leadForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');

            // UI Loading state
            btn.disabled = true;
            btnText.classList.add('hidden');
            spinner.classList.remove('hidden');

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('{{ route("promo.lead") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Redirect to WhatsApp
                    window.location.href = result.redirect;
                } else {
                    alert('Algo salió mal. Por favor intenta de nuevo.');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión.');
            } finally {
                btn.disabled = false;
                btnText.classList.remove('hidden');
                spinner.classList.add('hidden');
            }
        });

        // Close modal on click outside
        window.onclick = function (event) {
            const modal = document.getElementById('leadModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>