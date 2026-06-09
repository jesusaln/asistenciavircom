<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    empresa: Object,
});

const step = ref(1);
const totalSteps = 4;

const form = ref({
    tipo_asesor: 'pos', // Important for backend distinction
    giro: 'abarrotes',
    sucursales: 1,
    volumen_ventas: 'medio',
    necesita_computadora_completa: true,
    necesita_cpu: false,
    necesita_monitor: false,
    necesita_monitor_touch: false,
    necesita_cajon_dinero: true,
    necesita_impresora_tickets: true,
    necesita_bascula: false,
    necesita_lector_codigos: true,
    necesita_etiquetadora: false,
    software: 'eleventa',
});

const isCalculating = ref(false);
const showLeadModal = ref(false);
const leadSent = ref(false);
const isSubmitting = ref(false);

const leadForm = ref({
    nombre: '',
    telefono: '',
    email: '',
});

const nextStep = () => {
    if (step.value < totalSteps) {
        step.value++;
    } else {
        proceedToResults();
    }
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const proceedToResults = () => {
    isCalculating.value = true;
    setTimeout(() => {
        isCalculating.value = false;
        step.value = 5; // Result step
    }, 2000);
};

// Logic for Recommendation
const recommendation = computed(() => {
    let kitName = 'Kit Básico';
    let kitDescription = 'Ideal para pequeños negocios que inician.';
    let price = 4500; // Base price example

    if (form.value.giro === 'restaurante') {
        kitName = 'Kit Restaurante Touch';
        kitDescription = 'Agilidad máxima con pantalla táctil y comanderas.';
        price += 5000;
    } else if (form.value.giro === 'abarrotes') {
         if (form.value.necesita_bascula) {
            kitName = 'Kit Punto de Venta Completo';
            kitDescription = 'Incluye báscula para venta a granel y lector de alto rendimiento.';
            price += 3000;
         } else {
            kitName = 'Kit Abarrotes Estándar';
            kitDescription = 'Todo lo necesario para cobrar rápido y sin errores.';
            price += 1500;
         }
    } else if (form.value.giro === 'farmacia') {
        kitName = 'Kit Farmacia';
        kitDescription = 'Control de inventario preciso y lectura rápida de códigos.';
        price += 2000;
    } else if (form.value.giro === 'ropa') {
        kitName = 'Kit Boutique';
        kitDescription = 'Elegancia y control de tallas y colores.';
        price += 2500;
    } else if (form.value.giro === 'ferreteria') {
        kitName = 'Kit Ferretero Profesional';
        kitDescription = 'Gestión de miles de SKUs, inventario a granel y uso rudo.';
        price += 3500;
    }

    if (form.value.sucursales > 1) {
        kitName += ' Multi-Sucursal';
        price *= 1.2; // Increase for cloud sync setup
    }

    return {
        name: kitName,
        description: kitDescription,
        price: price
    };
});

const openLeadModal = () => {
    showLeadModal.value = true;
};

const submitLead = async () => {
    leadForm.value.nombre = leadForm.value.nombre.toUpperCase();
    if (!leadForm.value.nombre || !leadForm.value.telefono) {
        alert('Por favor completa tu nombre y teléfono.');
        return;
    }
    const telefonoLimpio = leadForm.value.telefono.replace(/\D/g, '');
    if (telefonoLimpio.length !== 10) {
        alert('Por favor valida tu número a 10 dígitos.');
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await axios.post(route('public.asesor.store'), {
            nombre: leadForm.value.nombre,
            telefono: leadForm.value.telefono,
            email: leadForm.value.email,
            recomendacion: recommendation.value.name,
            form: {
                ...form.value,
                estimated_price: recommendation.value.price
            }
        });

        if (response.data.success) {
            leadSent.value = true;
            setTimeout(() => {
                showLeadModal.value = false;
                leadSent.value = false;
                leadForm.value = { nombre: '', telefono: '', email: '' };
            }, 3000);
        }
    } catch (e) {
        alert('Error al enviar solicitud. Intente de nuevo.');
        console.error(e);
    } finally {
        isSubmitting.value = false;
    }
};

const scrollToSimulator = () => {
    document.getElementById('simulator').scrollIntoView({ behavior: 'smooth' });
};
</script>

<template>
    <div class="w-full">
        <!-- Hero Section (Light variant for integration) -->
        <div class="relative overflow-hidden bg-slate-900 text-white pt-20 pb-24 lg:pt-32 lg:pb-40 rounded-[3rem] mx-4 my-8 shadow-2xl">
            <div class="absolute inset-0 z-0">
                <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/95 to-slate-900/50 z-10"></div>
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')] bg-cover bg-center opacity-30"></div>
            </div>

            <div class="container mx-auto px-6 relative z-10">
                <div class="max-w-4xl mx-auto text-center">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-widest mb-6 border border-blue-500/30">
                        <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                        Venta Definitiva
                    </div>
                    <h2 class="text-4xl md:text-5xl font-black tracking-tight mb-6 leading-tight">
                        ¿Prefieres comprar tu equipo?
                    </h2>
                    <p class="text-lg md:text-xl text-slate-300 mb-10 max-w-2xl mx-auto leading-relaxed">
                        Arma tu paquete de Punto de Venta a medida. Propiedad total del hardware y software sin rentas mensuales.
                    </p>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <button @click="scrollToSimulator" class="px-8 py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold transition-all transform hover:-translate-y-1 shadow-lg shadow-blue-600/30 w-full sm:w-auto">
                            Cotizar Compra Ahora
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Benefits Section -->
        <section class="py-20 relative">
            <div class="container mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white mb-4">Beneficios de Comprar</h2>
                    <p class="text-slate-500 dark:text-slate-400 max-w-2xl mx-auto text-lg">Inversión única, activo para tu empresa.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                     <div class="group p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 hover:border-blue-500/30 transition-all hover:shadow-[0_20px_40px_-15px_rgba(59,130,246,0.1)]">
                        <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 transition-transform">
                            🏷️
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Sin Mensualidades</h3>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Paga una sola vez y el equipo es 100% tuyo. Sin contratos forzosos ni rentas infinitas.
                        </p>
                     </div>

                     <div class="group p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 hover:border-indigo-500/30 transition-all hover:shadow-[0_20px_40px_-15px_rgba(99,102,241,0.1)]">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-500/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 transition-transform">
                            ⚡
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Software Perpetuo</h3>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            Incluimos licencias vitalicias (Eleventa / MyBusiness) para que no dependas de suscripciones.
                        </p>
                     </div>

                     <div class="group p-8 rounded-3xl bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 hover:border-emerald-500/30 transition-all hover:shadow-[0_20px_40px_-15px_rgba(16,185,129,0.1)]">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl mb-6 group-hover:scale-110 transition-transform">
                            🛠️
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">Garantía Directa</h3>
                        <p class="text-slate-500 dark:text-slate-400 leading-relaxed">
                            1 año de garantía en hardware contra defectos de fábrica, gestionada directamente con nosotros.
                        </p>
                     </div>
                </div>
            </div>
        </section>

        <!-- Simulator Section -->
        <section id="simulator" class="py-20 relative border-t border-slate-200 dark:border-slate-700/50">
             <div class="container mx-auto px-4 max-w-5xl">

                <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden min-h-[600px] flex flex-col relative">

                    <div class="px-8 pt-12 pb-6 text-center">
                        <span class="text-xs font-black uppercase tracking-widest text-blue-500 mb-2 block">Simulador de Compra</span>
                        <h2 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white">Arma tu Kit de Venta</h2>
                        <div class="max-w-md mx-auto mt-6 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 transition-all duration-500" :style="{ width: ((step / totalSteps) * 100) + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex-1 p-8 md:p-12 relative overflow-y-auto">

                        <div v-if="isCalculating" class="absolute inset-0 z-50 bg-white/90 dark:bg-slate-900/90 backdrop-blur-sm flex flex-col items-center justify-center p-8">
                             <div class="w-16 h-16 border-4 border-blue-500/30 border-t-blue-500 rounded-full animate-spin mb-4"></div>
                             <h3 class="text-xl font-bold text-slate-900 dark:text-white">Analizando requerimientos...</h3>
                        </div>

                        <!-- Step 1: GIRO -->
                        <div v-if="step === 1" class="space-y-8 animate-fade-in-up">
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">¿Cuál es el giro de tu negocio?</h3>
                                <p class="text-slate-500">Esto nos ayuda a sugerir el hardware específico.</p>
                            </div>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <button @click="form.giro = 'abarrotes'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3',
                                    form.giro === 'abarrotes' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">🛒</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Abarrotes</span>
                                </button>
                                <button @click="form.giro = 'restaurante'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3',
                                    form.giro === 'restaurante' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">🍔</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Restaurante / Comida</span>
                                </button>
                                <button @click="form.giro = 'farmacia'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3',
                                    form.giro === 'farmacia' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">💊</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Farmacia</span>
                                </button>
                                <button @click="form.giro = 'ropa'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3',
                                    form.giro === 'ropa' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">👗</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Boutique / Ropa</span>
                                </button>
                                <button @click="form.giro = 'ferreteria'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3',
                                    form.giro === 'ferreteria' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">🛠️</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Ferretería</span>
                                </button>
                                <button @click="form.giro = 'otro'"
                                    :class="['p-6 rounded-2xl border-2 transition-all flex flex-col items-center gap-3 col-span-2 lg:col-span-3 max-w-xs mx-auto w-full',
                                    form.giro === 'otro' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200']">
                                    <span class="text-4xl">🏭</span>
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">Otro / General</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: VOLUMEN -->
                        <div v-if="step === 2" class="space-y-8 animate-fade-in-up">
                             <div class="text-center">
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Tamaño del Negocio</h3>
                            </div>

                            <div class="space-y-6 max-w-xl mx-auto">
                                <div class="bg-slate-50 dark:bg-slate-800 p-6 rounded-2xl">
                                    <label class="block text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Número de Sucursales</label>
                                    <div class="flex items-center gap-4">
                                        <button @click="form.sucursales > 1 ? form.sucursales-- : null" class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 shadow flex items-center justify-center font-bold text-xl hover:text-blue-500">-</button>
                                        <span class="text-3xl font-black text-slate-900 dark:text-white w-12 text-center">{{ form.sucursales }}</span>
                                        <button @click="form.sucursales++" class="w-12 h-12 rounded-xl bg-white dark:bg-slate-700 shadow flex items-center justify-center font-bold text-xl hover:text-blue-500">+</button>
                                    </div>
                                    <p v-if="form.sucursales > 1" class="mt-3 text-xs text-blue-500 font-bold">✨ Recomendaremos un sistema en la nube para sincronizar inventarios.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: HARDWARE -->
                        <div v-if="step === 3" class="space-y-8 animate-fade-in-up">
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Selecciona Periféricos</h3>
                                <p class="text-slate-500">Marca lo que necesites</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_computadora_completa" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Computadora Completa (CPU + Monitor)</span>
                                </label>

                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_monitor_touch" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Monitor Touch (Táctil)</span>
                                </label>

                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_impresora_tickets" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Impresora de Tickets (Térmica)</span>
                                </label>

                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_cajon_dinero" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Cajón de Dinero Automático</span>
                                </label>

                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_lector_codigos" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Lector de Códigos de Barra</span>
                                </label>

                                <label class="flex items-center p-4 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                                    <input type="checkbox" v-model="form.necesita_bascula" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500 border-gray-300">
                                    <span class="ml-4 font-medium text-slate-900 dark:text-white">Báscula Digital (Kilos)</span>
                                </label>
                            </div>
                        </div>

                         <!-- Step 4: SOFTWARE -->
                         <div v-if="step === 4" class="space-y-8 animate-fade-in-up">
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Software de Punto de Venta</h3>
                                <p class="text-slate-500">¿Tienes alguna preferencia?</p>
                            </div>

                            <div class="space-y-4">
                                <button @click="form.software = 'eleventa'"
                                    :class="['w-full p-6 text-left rounded-2xl border-2 transition-all flex items-center justify-between',
                                    form.software === 'eleventa' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800']">
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-lg">Eleventa</h4>
                                        <p class="text-sm text-slate-500 mt-1">Líder en México. Sencillo, rápido y confiable. Ideal para abarrotes.</p>
                                    </div>
                                    <div v-if="form.software === 'eleventa'" class="text-blue-500 text-2xl">✅</div>
                                </button>
                                <button @click="form.software = 'mybusiness'"
                                    :class="['w-full p-6 text-left rounded-2xl border-2 transition-all flex items-center justify-between',
                                    form.software === 'mybusiness' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800']">
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-lg">MyBusiness POS</h4>
                                        <p class="text-sm text-slate-500 mt-1">Potente y robusto. Ideal para farmacias, ferreterías y refaccionarias.</p>
                                    </div>
                                    <div v-if="form.software === 'mybusiness'" class="text-blue-500 text-2xl">✅</div>
                                </button>
                                <button @click="form.software = 'otro'"
                                    :class="['w-full p-6 text-left rounded-2xl border-2 transition-all flex items-center justify-between',
                                    form.software === 'otro' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-slate-800']">
                                    <div>
                                        <h4 class="font-bold text-slate-900 dark:text-white text-lg">Otro / Asesoría</h4>
                                        <p class="text-sm text-slate-500 mt-1">No estoy seguro o necesito algo a medida.</p>
                                    </div>
                                    <div v-if="form.software === 'otro'" class="text-blue-500 text-2xl">✅</div>
                                </button>
                            </div>
                        </div>

                        <!-- Step 5: RESULTS -->
                        <div v-if="step === 5" class="space-y-10 animate-fade-in-up text-center">
                            <div class="inline-block p-4 rounded-full bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 text-4xl mb-4">
                                🎉
                            </div>
                            <h2 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white">¡Tenemos tu Kit Ideal!</h2>
                            <p class="text-lg text-slate-500 dark:text-slate-400">Basado en tu giro <strong>{{ form.giro.toUpperCase() }}</strong> y necesidades.</p>

                            <div class="bg-slate-900 rounded-3xl p-8 md:p-12 text-white relative overflow-hidden max-w-2xl mx-auto shadow-2xl">
                                <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500 rounded-full blur-[80px] opacity-40"></div>
                                <div class="relative z-10">
                                    <h3 class="text-3xl font-black mb-2">{{ recommendation.name }}</h3>
                                    <p class="text-slate-300 text-lg mb-8">{{ recommendation.description }}</p>
                                    <div class="h-px bg-white/10 w-full mb-8"></div>
                                    
                                    <ul class="text-left space-y-3 mb-8 text-sm md:text-base">
                                        <li v-if="form.necesita_computadora_completa" class="flex items-center gap-2"><span class="text-green-400">✓</span> CPU Intel Core / 8GB RAM / SSD</li>
                                        <li v-if="form.necesita_monitor_touch" class="flex items-center gap-2"><span class="text-green-400">✓</span> Monitor Touch Industrial</li>
                                        <li v-else class="flex items-center gap-2"><span class="text-green-400">✓</span> Monitor LED 20"</li>
                                        <li class="flex items-center gap-2"><span class="text-green-400">✓</span> Licencia perpetua {{ form.software.toUpperCase() }}</li>
                                        <li v-if="form.necesita_impresora_tickets" class="flex items-center gap-2"><span class="text-green-400">✓</span> Impresora Térmica 58mm/80mm</li>
                                        <li v-if="form.necesita_cajon_dinero" class="flex items-center gap-2"><span class="text-green-400">✓</span> Cajón de Dinero Metálico</li>
                                        <li v-if="form.necesita_lector_codigos" class="flex items-center gap-2"><span class="text-green-400">✓</span> Lector Código de Barras Láser</li>
                                    </ul>

                                    <button @click="openLeadModal" class="w-full py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold uppercase tracking-widest text-sm shadow-xl shadow-blue-600/20 transform hover:-translate-y-1 transition-all">
                                        Obtener Oferta y Contratar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Footer Controls -->
                    <div v-if="step < 5" class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-between bg-white dark:bg-slate-900 z-10">
                         <button @click="prevStep" v-if="step > 1" class="px-6 py-3 font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors uppercase text-xs tracking-widest">
                            ← Regresar
                         </button>
                         <div v-else></div>

                         <button @click="nextStep" class="px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold uppercase text-xs tracking-widest transition-all shadow-lg hover:shadow-blue-500/30">
                            {{ step === totalSteps ? 'Ver Resultado' : 'Continuar →' }}
                         </button>
                    </div>

                </div>
            </div>
        </section>

        <!-- Lead Form Modal -->
        <div v-if="showLeadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showLeadModal = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-md w-full p-8 md:p-10 transform transition-all scale-100">
                
                <button @click="showLeadModal = false" class="absolute top-6 right-6 text-slate-400 hover:text-slate-900 dark:hover:text-white">✕</button>

                <div v-if="!leadSent">
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">¡Excelente elección!</h3>
                    <p class="text-slate-500 dark:text-slate-400 mb-8">Déjanos tus datos para enviarte la cotización formal del <strong>{{ recommendation.name }}</strong> e iniciar tu contratación.</p>

                    <form @submit.prevent="submitLead" class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tu Nombre</label>
                            <input v-model="leadForm.nombre" type="text" required placeholder="Nombre Completo" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border-none font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Teléfono (WhatsApp)</label>
                            <input v-model="leadForm.telefono" type="tel" maxlength="10" required placeholder="10 Dígitos" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border-none font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Email (Opcional)</label>
                            <input v-model="leadForm.email" type="email" placeholder="correo@ejemplo.com" class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-800 border-none font-medium text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>

                        <button type="submit" :disabled="isSubmitting" class="w-full py-4 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-bold uppercase tracking-widest text-sm shadow-xl transition-all flex items-center justify-center gap-2">
                            <span v-if="isSubmitting" class="animate-spin">🌀</span>
                            {{ isSubmitting ? 'Enviando...' : 'Solicitar Contratación' }}
                        </button>
                    </form>
                </div>
                
                <div v-else class="text-center py-8">
                     <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">✅</div>
                     <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">¡Recibido!</h3>
                     <p class="text-slate-500 mb-6">Un asesor experto te contactará en breve para formalizar tu pedido.</p>
                     <button @click="showLeadModal = false" class="text-blue-500 font-bold underline">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
