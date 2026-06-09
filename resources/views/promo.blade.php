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

    <!-- Widget Flotante OpenClaw (Blade Version) -->
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end font-sans">
        <div id="botMenu"
            class="hidden mb-4 w-72 bg-white dark:bg-slate-900 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-100 dark:border-slate-800 overflow-hidden transform transition-all duration-300 scale-95 opacity-0 origin-bottom-right">
            <div class="bg-slate-950 p-6 text-white relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-purple-600/20 opacity-50"></div>
                <div class="relative z-10 flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-tr from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-2xl shadow-lg border border-white/20">
                        🤖</div>
                    <div class="text-left">
                        <h3 class="text-lg font-bold leading-tight">OpenClaw IA</h3>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            <p class="text-[10px] uppercase tracking-wider font-semibold opacity-70">En línea ahora</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50/30 dark:bg-slate-900/50 space-y-2">
                <a href="https://wa.me/5216622036840?text=Hola%20OpenClaw!%20Me%20interesa%20la%20promo"
                    class="flex items-center gap-3 p-3 rounded-2xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 hover:border-blue-500/50 transition-all group">
                    <span class="text-xl">🏷️</span>
                    <div class="text-left">
                        <p class="font-bold text-sm">Ver Promociones</p>
                    </div>
                </a>
                <a href="https://wa.me/5216622036840?text=Hola!%20Quiero%20agendar%20una%20cita"
                    class="flex items-center gap-3 p-3 rounded-2xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-slate-700 hover:border-blue-500/50 transition-all group">
                    <span class="text-xl">📅</span>
                    <div class="text-left">
                        <p class="font-bold text-sm">Agendar Cita</p>
                    </div>
                </a>
            </div>
            <div class="p-2 text-center border-t border-gray-50 dark:border-slate-800">
                <p class="text-[9px] text-gray-400">Powered by OpenClaw</p>
            </div>
        </div>

        <button onclick="toggleBotMenu()"
            class="w-16 h-16 bg-gradient-to-tr from-[#25D366] to-[#128C7E] rounded-full shadow-2xl flex items-center justify-center text-3xl hover:scale-110 active:scale-95 transition-all">
            <span id="botIcon">🤖</span>
        </button>
    </div>

    <!-- Widget Flotante Vircom Bot (Chat Real) -->
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col items-end font-sans">
        <div id="botChat" class="hidden mb-4 w-80 md:w-96 bg-white rounded-[2rem] shadow-[0_20px_60px_rgba(0,0,0,0.5)] border border-gray-100 overflow-hidden flex flex-col h-[500px] transform transition-all duration-300 scale-95 opacity-0 origin-bottom-right">
            <!-- Header -->
            <div class="bg-slate-900 p-5 text-white relative overflow-hidden flex-shrink-0">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/30 to-purple-600/30 opacity-50"></div>
                <div class="relative z-10 flex items-center gap-3 text-left">
                    <div class="w-10 h-10 bg-gradient-to-tr from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-xl shadow-lg border border-white/20">🤖</div>
                    <div>
                        <h3 class="text-base font-bold">Vircom Bot</h3>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span class="text-[9px] uppercase tracking-widest font-bold opacity-70">Soporte Inteligente</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages Container -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50/50 shadow-inner">
                <div class="flex w-full justify-start">
                    <div class="max-w-[85%] p-3 rounded-2xl text-sm leading-relaxed shadow-sm bg-white text-slate-800 rounded-bl-none border border-gray-100">
                        ¡Hola! Soy Vircom Bot. 🤖 Estoy listo para ayudarte. ¿En qué puedo apoyarte hoy?
                    </div>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-4 bg-white border-t border-gray-100 flex-shrink-0">
                <form id="botForm" class="flex gap-2 relative">
                    <input id="botInput" type="text" placeholder="Escribe un mensaje..." class="flex-1 bg-gray-50 border border-gray-100 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-900">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white w-10 h-10 rounded-full flex items-center justify-center transition-all active:scale-90 shadow-lg">
                        <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <button onclick="toggleBotChat()" class="w-16 h-16 bg-gradient-to-tr from-[#25D366] to-[#128C7E] rounded-full shadow-2xl flex items-center justify-center text-3xl hover:scale-110 active:scale-95 transition-all">
            <span id="botIcon">🤖</span>
        </button>
    </div>

    <script>
        const chatSessionId = 'promo-' + Math.random().toString(36).substring(2, 9);

        function toggleBotChat() {
            const chat = document.getElementById('botChat');
            const icon = document.getElementById('botIcon');
            if (chat.classList.contains('hidden')) {
                chat.classList.remove('hidden');
                setTimeout(() => {
                    chat.classList.remove('scale-95', 'opacity-0');
                    chat.classList.add('scale-100', 'opacity-100');
                    scrollToChatBottom();
                }, 10);
                icon.innerText = '✕';
            } else {
                chat.classList.add('scale-95', 'opacity-0');
                chat.classList.remove('scale-100', 'opacity-100');
                setTimeout(() => chat.classList.add('hidden'), 300);
                icon.innerText = '🤖';
            }
        }

        function scrollToChatBottom() {
            const container = document.getElementById('chatMessages');
            container.scrollTop = container.scrollHeight;
        }

        function appendMessage(text, isBot) {
            const container = document.getElementById('chatMessages');
            const wrapper = document.createElement('div');
            wrapper.className = `flex w-full ${isBot ? 'justify-start' : 'justify-end'}`;
            
            const bubble = document.createElement('div');
            bubble.className = `max-w-[85%] p-3 rounded-2xl text-sm leading-relaxed shadow-sm ${
                isBot 
                    ? 'bg-white text-slate-800 rounded-bl-none border border-gray-100' 
                    : 'bg-blue-600 text-white rounded-br-none font-medium'
            }`;
            bubble.innerText = text;
            
            wrapper.appendChild(bubble);
            container.appendChild(wrapper);
            scrollToChatBottom();
        }

        document.getElementById('botForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('botInput');
            const text = input.value.trim();
            if (!text) return;

            appendMessage(text, false);
            input.value = '';
            
            // Loading bubble
            const loadingId = 'loading-' + Date.now();
            const container = document.getElementById('chatMessages');
            const loadingWrapper = document.createElement('div');
            loadingWrapper.id = loadingId;
            loadingWrapper.className = 'flex justify-start';
            loadingWrapper.innerHTML = `
                <div class="bg-white p-3 rounded-2xl rounded-bl-none shadow-sm flex gap-1">
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                </div>
            `;
            container.appendChild(loadingWrapper);
            scrollToChatBottom();

            try {
                const response = await fetch('/chat/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: text,
                        session_id: chatSessionId
                    })
                });

                const data = await response.json();
                document.getElementById(loadingId).remove();

                if (data.success) {
                    appendMessage(data.message, true);
                } else {
                    appendMessage('Lo siento, tuve un problema. ¿Puedes intentar de nuevo?', true);
                }
            } catch (error) {
                document.getElementById(loadingId).remove();
                appendMessage('Error de conexión.', true);
            }
        });

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
            const botMenu = document.getElementById('botMenu');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

</body>

</html>