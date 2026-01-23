<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    empresa: Object,
});

const step = ref(1);
const totalSteps = 5;

const form = ref({
    habitacion: 'sala', // recamara, sala, cocina, oficina
    area: 20,
    altura: 2.6,
    personas: 2,
    aparatos: 1, 
    zona: 'centro', 
    techo_directo: false, 
    ventanales: false, 
    sol: 'moderado', 
    aislamiento: 'normal', 
    voltaje: '220', 
    funcion: 'dual', 
    uso_horas: '8', // 8 (solo noche), 24 (todo el día)
    tecnologia: 'inverter', // inverter, convencional
});

onMounted(() => {
    // Geocalización básica por IP
    fetch('https://ipapi.co/json/')
        .then(res => res.json())
        .then(data => {
            const city = data.city?.toLowerCase() || '';
            const region = data.region?.toLowerCase() || '';
            
            // Lógica simple de zonas por ciudad/región
            if (city.includes('mexicali') || city.includes('hermosillo') || region.includes('sonora') || region.includes('baja california')) {
                form.value.zona = 'desierto';
            } else if (city.includes('cancun') || city.includes('veracruz') || region.includes('quintana') || region.includes('yucatan')) {
                form.value.zona = 'costa';
            } else {
                form.value.zona = 'centro';
            }
        })
        .catch(err => console.log('Error en geolocalización:', err));
});

const isCalculating = ref(false);
const requiresLeadCapture = ref(false); // Flag para saber si estamos en flujo de captura

const nextStep = () => {
    if (step.value === 4) {
        // En lugar de ir directo al resultado, mostrar modal de captura
        requiresLeadCapture.value = true;
        showLeadModal.value = true;
    } else if (step.value < totalSteps) {
        step.value++;
    }
};

const proceedToResults = () => {
    isCalculating.value = true;
    setTimeout(() => {
        isCalculating.value = false;
        step.value = 5;
    }, 2200);
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

// Lógica de cálculo Pro (Basada en m³ y factores de carga térmica real)
const calculoBTU = computed(() => {
    // 1. Carga por m³ (Volumen) según Zona Climática
    let factorZona = 600; // Centro / Templado
    if (form.value.zona === 'costa') factorZona = 800;
    if (form.value.zona === 'desierto') factorZona = 1000;

    // Volumen de aire
    let btuBase = form.value.area * factorZona;

    // 2. Ajuste por Altura (proporcional a 2.6m estándar)
    if (form.value.altura > 2.6) {
        btuBase *= (form.value.altura / 2.6);
    }

    // 3. Carga por Personas (600 BTU por persona extra después de la 1ra)
    let btuPersonas = (form.value.personas - 1) * 600;
    if (btuPersonas < 0) btuPersonas = 0;

    // 4. Carga por Aparatos Eléctricos (500 BTU por unidad: TV, PC, Refri)
    let btuAparatos = form.value.aparatos * 500;

    // 5. Cargas por Ganancia de Calor (Construcción)
    let btuConstruccion = 0;
    
    // Si el sol pega directo al techo (último piso o casa sola) +15%
    if (form.value.techo_directo) btuConstruccion += (btuBase * 0.15);
    
    // Si hay ventanales grandes (pérdida/ganancia por cristal) +20%
    if (form.value.ventanales) btuConstruccion += (btuBase * 0.20);

    // 6. Factor de Aislamiento
    let multiplierAislamiento = 1.0;
    if (form.value.aislamiento === 'bueno') multiplierAislamiento = 0.85;
    if (form.value.aislamiento === 'pobre') multiplierAislamiento = 1.25;

    // 7. Factor de Sol/Orientación
    let multiplierSol = 1.0;
    if (form.value.sol === 'mucho') multiplierSol = 1.15;
    if (form.value.sol === 'poco') multiplierSol = 0.90;

    // 8. Factor Tipo de Habitación
    let btuHabitacion = 0;
    if (form.value.habitacion === 'cocina') btuHabitacion = 2500; // Calor de estufa/horno
    if (form.value.habitacion === 'recamara') multiplierSol *= 0.95; // Menos actividad
    if (form.value.habitacion === 'oficina') btuHabitacion = 1000; // Carga constante de equipos

    const totalBTU = ((btuBase + btuPersonas + btuAparatos + btuConstruccion) * multiplierAislamiento * multiplierSol) + btuHabitacion;

    return Math.round(totalBTU);
});

const ahorroEstimado = computed(() => {
    const horas = parseInt(form.value.uso_horas);
    const kwConsumoConv = 1.2; // Consumo promedio 1 Ton Convencional
    const kwConsumoInv = 0.5;  // Consumo promedio 1 Ton Inverter (modulado)
    const costoKwh = 3.5;      // Costo promedio MXN
    
    const ahorroMensual = (kwConsumoConv - kwConsumoInv) * horas * 30 * costoKwh;
    return Math.round(ahorroMensual);
});

const recomendacion = computed(() => {
    const btu = calculoBTU.value;
    
    if (btu <= 13000) return { capacidad: '1 Tonelada (12,000 BTU)', nota: 'Ideal para recámaras o áreas pequeñas.' };
    if (btu <= 19000) return { capacidad: '1.5 Toneladas (18,000 BTU)', nota: 'Perfecto para salas medianas o recámaras amplias.' };
    if (btu <= 26000) return { capacidad: '2 Toneladas (24,000 BTU)', nota: 'Recomendado para espacios abiertos o áreas sociales.' };
    if (btu <= 38000) return { capacidad: '3 Toneladas (36,000 BTU)', nota: 'Necesario para áreas comerciales o techos altos.' };
    
    return { capacidad: 'Sistema Central o Multi-Split', nota: 'Tu requerimiento supera la capacidad de una unidad estándar. Te recomendamos una visita técnica personalizada.' };
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3B82F6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3B82F6') + '15',
    '--color-secondary': props.empresa?.color_secundario || '#64748B',
}));

// Lógica de Lead / Modal
const showLeadModal = ref(false);
const leadForm = ref({
    nombre: '',
    telefono: '',
    email: '',
});
const isSubmitting = ref(false);
const leadSent = ref(false);

const openLeadModal = (e) => {
    e.preventDefault();
    showLeadModal.value = true;
};

const submitLead = async () => {
    // Convertir nombre a mayúsculas
    leadForm.value.nombre = leadForm.value.nombre.toUpperCase();

    if (!leadForm.value.nombre || !leadForm.value.telefono) {
        alert('Por favor completa tu nombre y teléfono.');
        return;
    }

    // Validación de 10 dígitos
    const telefonoLimpio = leadForm.value.telefono.replace(/\D/g, '');
    if (telefonoLimpio.length !== 10) {
        alert('Por favor ingresa un número de teléfono válido de 10 dígitos.');
        return;
    }

    isSubmitting.value = true;
    try {
        const response = await axios.post(route('public.asesor.store'), {
            nombre: leadForm.value.nombre,
            telefono: leadForm.value.telefono,
            email: leadForm.value.email,
            btu: calculoBTU.value,
            recomendacion: recomendacion.value.capacidad,
            form: {
                ...form.value,
                ahorro_estimado: ahorroEstimado.value
            }
        });

        if (response.data.success) {
            leadSent.value = true;
            
            // Si estamos en el flujo de captura pre-resultado
            if (requiresLeadCapture.value) {
                setTimeout(() => {
                    showLeadModal.value = false;
                    leadSent.value = false;
                    requiresLeadCapture.value = false;
                    proceedToResults(); // Mostrar resultados después de capturar
                }, 1500);
            } else {
                // Flujo normal (desde botón de resultados)
                setTimeout(() => {
                    showLeadModal.value = false;
                    leadSent.value = false;
                    leadForm.value = { nombre: '', telefono: '', email: '' };
                }, 3000);
            }
        }
    } catch (error) {
        console.error('Error enviando lead:', error);
        alert('Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Head title="Asesor de Climatización Expert" />

    <div class="min-h-screen bg-white dark:bg-slate-900 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="" />

        <main class="flex-1 py-12 px-4">
            <div class="w-full">
                <!-- Header -->
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[var(--color-primary-soft)] rounded-full mb-6">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[var(--color-primary)] opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-[var(--color-primary)]"></span>
                        </span>
                        <span class="text-[var(--color-primary)] text-[10px] font-black uppercase tracking-[0.2em]">Cálculo en Tiempo Real</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black text-gray-900 dark:text-white tracking-tight leading-tight">
                        Tu Confort, <br class="md:hidden"> <span class="text-[var(--color-primary)]">Calculado</span>
                    </h1>
                    <p class="mt-6 text-gray-500 dark:text-gray-400 font-medium w-full text-lg">Responde estas breves preguntas y deja que nuestro algoritmo encuentre el clima perfecto para ti.</p>
                </div>

                <!-- Progress Tracker (Custom) -->
                <div class="mb-12 w-full">
                    <div class="flex justify-between mb-4">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Paso {{ step }} de {{ totalSteps }}</span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)]">{{ Math.round((step/totalSteps)*100) }}% Completado</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden border border-gray-50">
                        <div 
                            class="h-full bg-[var(--color-primary)] transition-all duration-700 ease-out shadow-[0_0_15px_rgba(59,130,246,0.5)]"
                            :style="`width: ${(step / totalSteps) * 100}%`"
                        ></div>
                    </div>
                </div>

                <!-- Main Interaction Container -->
                <div class="relative">
                    <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-[0_30px_100px_rgba(0,0,0,0.04)] border border-gray-50 overflow-hidden min-h-[550px] transition-all duration-500">
                        
                        <div class="p-8 md:p-16 h-full flex flex-col relative">
                            
                            <!-- Professional Analyzing Overlay -->
                            <div v-if="isCalculating" class="absolute inset-0 z-50 bg-white dark:bg-slate-900/90 backdrop-blur-md flex flex-col items-center justify-center p-12 text-center animate-content-in">
                                <div class="relative w-24 h-24 mb-10">
                                    <div class="absolute inset-0 border-4 border-[var(--color-primary-soft)] rounded-full"></div>
                                    <div class="absolute inset-0 border-4 border-t-[var(--color-primary)] rounded-full animate-spin"></div>
                                    <div class="absolute inset-0 flex items-center justify-center text-3xl">🧮</div>
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">Analizando Carga Térmica</h3>
                                <div class="space-y-3 w-full max-w-xs">
                                    <p class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] animate-pulse">PROCESANDO FACTORES...</p>
                                    <div class="flex justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                        <span>Ganancia Solar</span>
                                        <span class="text-green-500">OK</span>
                                    </div>
                                    <div class="flex justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                        <span>Volumen de Aire</span>
                                        <span class="text-green-500">OK</span>
                                    </div>
                                    <div class="flex justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                        <span>Eficiencia Inverter</span>
                                        <span class="text-green-500">OK</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Step 1: Espacio y Ubicación -->
                            <div v-if="step === 1" class="animate-content-in space-y-12">
                                <div>
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">¿Qué espacio vamos a climatizar?</h2>
                                    <p class="text-gray-400 font-medium italic">Selecciona el tipo de habitación para ajustar la carga térmica.</p>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                                        <button v-for="h in [
                                            {id: 'sala', label: 'Sala', icon: '🛋️'},
                                            {id: 'recamara', label: 'Recámara', icon: '🛏️'},
                                            {id: 'cocina', label: 'Cocina', icon: '🍲'},
                                            {id: 'oficina', label: 'Oficina', icon: '💻'}
                                        ]" :key="h.id" 
                                        @click="form.habitacion = h.id"
                                        :class="['p-6 rounded-[2rem] border-2 transition-all duration-300 text-center flex flex-col items-center', 
                                                form.habitacion === h.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800 group']">
                                            <span class="text-4xl mb-4 transform group-hover:scale-110 transition-transform">{{ h.icon }}</span>
                                            <span class="font-black text-[10px] uppercase tracking-widest text-gray-900 dark:text-white">{{ h.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="pt-8 border-t border-gray-100">
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Tu Ubicación</h2>
                                    <p class="text-gray-400 font-medium italic px-1">Detectamos tu zona climática automáticamente.</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                                        <button @click="form.zona = 'centro'" 
                                                class="group relative p-8 rounded-[2rem] border-2 transition-all duration-300 text-left overflow-hidden" 
                                                :class="form.zona === 'centro' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="text-4xl mb-6 transform group-hover:scale-110 transition-transform">🏢</div>
                                            <h3 class="font-black text-gray-900 dark:text-white text-lg mb-2">Zona Centro</h3>
                                            <p class="text-[10px] leading-relaxed text-gray-500 dark:text-gray-400">Templado (CDMX, Bajío, Puebla).</p>
                                        </button>

                                        <button @click="form.zona = 'costa'" 
                                                class="group relative p-8 rounded-[2rem] border-2 transition-all duration-300 text-left overflow-hidden" 
                                                :class="form.zona === 'costa' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="text-4xl mb-6 transform group-hover:scale-110 transition-transform">🏖️</div>
                                            <h3 class="font-black text-gray-900 dark:text-white text-lg mb-2">Costas</h3>
                                            <p class="text-[10px] leading-relaxed text-gray-500 dark:text-gray-400">Calor húmedo (Cancún, Vallarta, Veracruz).</p>
                                        </button>

                                        <button @click="form.zona = 'desierto'" 
                                                class="group relative p-8 rounded-[2rem] border-2 transition-all duration-300 text-left overflow-hidden" 
                                                :class="form.zona === 'desierto' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="text-4xl mb-6 transform group-hover:scale-110 transition-transform">🏜️</div>
                                            <h3 class="font-black text-gray-900 dark:text-white text-lg mb-2">Norte / Desierto</h3>
                                            <p class="text-[10px] leading-relaxed text-gray-500 dark:text-gray-400">Extremo (Mexicali, Hermosillo, Juárez).</p>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Dimensiones -->
                            <div v-if="step === 2" class="animate-content-in space-y-12">
                                <div class="text-center md:text-left">
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Cuéntanos del Espacio</h2>
                                    <p class="text-gray-400 font-medium italic">Entre más volumen tenga el cuarto, más potencia necesitaremos.</p>
                                </div>

                                <div class="space-y-12 max-w-2xl">
                                    <div class="relative p-8 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100">
                                        <div class="flex justify-between items-center mb-6">
                                            <label class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Tamaño del Área</label>
                                            <span class="px-4 py-1 bg-[var(--color-primary)] text-white font-black rounded-lg text-lg shadow-lg">{{ form.area }} m²</span>
                                        </div>
                                        <input type="range" v-model="form.area" min="5" max="150" class="w-full h-3 bg-gray-200 rounded-full appearance-none cursor-pointer accent-[var(--color-primary)]" />
                                        <div class="flex justify-between mt-4 text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                            <span>Pequeño (Recámara)</span>
                                            <span>Grande (Sala/Local)</span>
                                        </div>
                                    </div>

                                    <div class="grid md:grid-cols-2 gap-8">
                                        <div class="relative p-8 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100">
                                            <label class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Altura del techo</label>
                                            <div class="flex items-center gap-4">
                                                <input type="number" v-model="form.altura" step="0.1" class="w-full p-4 bg-white dark:bg-slate-900 rounded-2xl border-none font-black text-2xl focus:ring-2 focus:ring-[var(--color-primary)]" />
                                                <span class="font-black text-gray-400 text-xl text-nowrap">METROS</span>
                                            </div>
                                            <p class="mt-4 text-[10px] text-gray-400 italic">Estándar: 2.4m - 2.8m</p>
                                        </div>
                                        <div class="relative p-8 bg-white dark:bg-slate-900 rounded-3xl border border-gray-100 flex flex-col justify-center">
                                            <label class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Número de Personas</label>
                                            <div class="flex items-center justify-between gap-6">
                                                <button @click="form.personas > 1 && form.personas--" class="w-16 h-16 rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-gray-100 flex items-center justify-center text-3xl font-black text-gray-400 hover:text-[var(--color-primary)] transition-colors">-</button>
                                                <span class="text-4xl font-black text-gray-900 dark:text-white">{{ form.personas }}</span>
                                                <button @click="form.personas < 20 && form.personas++" class="w-16 h-16 rounded-2xl bg-white dark:bg-slate-900 shadow-sm border border-gray-100 flex items-center justify-center text-3xl font-black text-gray-400 hover:text-[var(--color-primary)] transition-colors">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Construcción -->
                            <div v-if="step === 3" class="animate-content-in space-y-12">
                                <div class="text-center md:text-left">
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Detalles de Construcción</h2>
                                    <p class="text-gray-400 font-medium italic">Factores que aumentan radicalmente el calor interno.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-6">
                                        <button @click="form.techo_directo = !form.techo_directo" 
                                                class="w-full p-8 rounded-[2.5rem] border-2 transition-all duration-300 text-left flex items-center gap-6"
                                                :class="form.techo_directo ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="text-5xl">🏠</div>
                                            <div>
                                                <h4 class="font-black text-gray-900 dark:text-white text-lg">Techo Directo</h4>
                                                <p class="text-[10px] text-gray-400">¿Es el último piso o el sol pega directo al techo?</p>
                                            </div>
                                            <div v-if="form.techo_directo" class="ml-auto text-[var(--color-primary)]">
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </button>

                                        <button @click="form.ventanales = !form.ventanales" 
                                                class="w-full p-8 rounded-[2.5rem] border-2 transition-all duration-300 text-left flex items-center gap-6"
                                                :class="form.ventanales ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="text-5xl">🪟</div>
                                            <div>
                                                <h4 class="font-black text-gray-900 dark:text-white text-lg">Grandes Ventanales</h4>
                                                <p class="text-[10px] text-gray-400">¿Tiene áreas acristaladas que dejen pasar el sol?</p>
                                            </div>
                                            <div v-if="form.ventanales" class="ml-auto text-[var(--color-primary)]">
                                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </button>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="p-8 bg-white dark:bg-slate-900 rounded-[2.5rem] border border-gray-100">
                                            <label class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Calidad del Aislamiento</label>
                                            <div class="grid grid-cols-1 gap-3">
                                                <button v-for="ai in [{v:'bueno', t:'Excelente', d:'Muro térmico / Aislante'}, {v:'normal', t:'Estándar', d:'Ladrillo o Block'}, {v:'pobre', t:'Mínimo', d:'Lámina o madera'}]" 
                                                        :key="ai.v" @click="form.aislamiento = ai.v"
                                                        class="p-4 rounded-2xl border-2 transition-all text-left flex items-center justify-between"
                                                        :class="form.aislamiento === ai.v ? 'border-[var(--color-primary)] bg-white dark:bg-slate-900 shadow-md' : 'border-transparent text-gray-400'">
                                                    <div>
                                                        <p class="font-black text-gray-900 dark:text-white text-xs">{{ ai.t }}</p>
                                                        <p class="text-[9px]">{{ ai.d }}</p>
                                                    </div>
                                                    <div v-if="form.aislamiento === ai.v" class="w-3 h-3 bg-[var(--color-primary)] rounded-full"></div>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Tecnología y Uso -->
                            <div v-if="step === 4" class="animate-content-in space-y-12">
                                <div>
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Hábitos de Uso</h2>
                                    <p class="text-gray-400 font-medium italic">¿Cuánto tiempo estará encendido el equipo al día?</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                                        <button @click="form.uso_horas = '8'" :class="['p-8 rounded-[2.5rem] border-2 transition-all duration-300 flex items-center gap-6 text-left', form.uso_horas === '8' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-100']">
                                            <span class="text-4xl">🌙</span>
                                            <div>
                                                <span class="block font-black text-gray-900 dark:text-white uppercase tracking-widest text-xs mb-1">8 Horas Diario</span>
                                                <span class="text-[10px] text-gray-400 font-bold uppercase">Uso moderado (Solo Noche)</span>
                                            </div>
                                        </button>
                                        <button @click="form.uso_horas = '24'" :class="['p-8 rounded-[2.5rem] border-2 transition-all duration-300 flex items-center gap-6 text-left relative overflow-hidden', form.uso_horas === '24' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)]' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-100']">
                                            <span class="text-4xl">☀️</span>
                                            <div>
                                                <span class="block font-black text-gray-900 dark:text-white uppercase tracking-widest text-xs mb-1">Todo el Día</span>
                                                <span class="text-[10px] text-[var(--color-primary)] font-black uppercase tracking-widest">Uso Intensivo</span>
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <div class="pt-10 border-t border-gray-100">
                                    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">Preferencia Tecnológica</h2>
                                    <p class="text-gray-400 font-medium italic">Te recomendamos la mejor opción para maximizar tu ahorro.</p>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                                        <button @click="form.tecnologia = 'convencional'" 
                                                class="group relative p-8 rounded-[2.5rem] border-2 transition-all duration-300 text-left overflow-hidden min-h-[160px] flex flex-col justify-between" 
                                                :class="form.tecnologia === 'convencional' ? 'border-gray-900 bg-gray-900 text-white shadow-2xl' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <h3 class="font-black text-sm uppercase tracking-[0.2em] mb-4">Convencional</h3>
                                            <p class="text-[11px] leading-relaxed font-medium opacity-60">Precio inicial bajo. Ideal para usos de corta duración (menos de 4h al día).</p>
                                        </button>

                                        <button @click="form.tecnologia = 'inverter'" 
                                                class="group relative p-8 rounded-[2.5rem] border-2 transition-all duration-300 text-left overflow-hidden min-h-[160px] flex flex-col justify-between" 
                                                :class="form.tecnologia === 'inverter' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] ring-4 ring-[var(--color-primary-soft)] shadow-2xl' : 'border-gray-50 bg-white dark:bg-slate-900 hover:border-gray-200 dark:border-slate-800'">
                                            <div class="flex justify-between items-start mb-4">
                                                <h3 class="font-black text-gray-900 dark:text-white text-sm uppercase tracking-[0.2em]">Inverter</h3>
                                                <span class="px-3 py-1 bg-green-100 text-green-700 text-[9px] font-black rounded-full uppercase tracking-widest">Recomendado</span>
                                            </div>
                                            <p class="text-[11px] leading-relaxed text-gray-500 dark:text-gray-400 font-medium">Ahorra hasta 70% en luz. Extremadamente silencioso y temperatura constante.</p>
                                        </button>
                                    </div>
                                </div>

                                <div class="pt-10 border-t border-gray-100 grid md:grid-cols-2 gap-10">
                                    <div class="space-y-6">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Suministro Eléctrico</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <button @click="form.voltaje = '110'" :class="['py-4 rounded-2xl border-2 font-black transition-all', form.voltaje === '110' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-[var(--color-primary)]' : 'border-gray-50 bg-white dark:bg-slate-900 text-gray-400']">110V</button>
                                            <button @click="form.voltaje = '220'" :class="['py-4 rounded-2xl border-2 font-black transition-all', form.voltaje === '220' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-[var(--color-primary)]' : 'border-gray-50 bg-white dark:bg-slate-900 text-gray-400']">220V</button>
                                        </div>
                                    </div>
                                    <div class="space-y-6">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Modo de Operación</label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <button @click="form.funcion = 'frio'" :class="['py-4 rounded-2xl border-2 font-black transition-all', form.funcion === 'frio' ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-50 bg-white dark:bg-slate-900 text-gray-400']">Solo Frío</button>
                                            <button @click="form.funcion = 'dual'" :class="['py-4 rounded-2xl border-2 font-black transition-all', form.funcion === 'dual' ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-gray-50 bg-white dark:bg-slate-900 text-gray-400']">Frío/Calor</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Resultados Finales -->
                            <div v-if="step === 5" class="animate-content-in">
                                <div class="text-center md:w-full py-6">
                                    <div class="w-32 h-32 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-full flex items-center justify-center text-5xl mx-auto mb-10 shadow-[0_20px_60px_-15px_rgba(59,130,246,0.3)] animate-bounce">
                                        ✨
                                    </div>
                                    <h2 class="text-4xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">¡Resultado Listo!</h2>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium text-lg leading-relaxed mb-12">Nuestro algoritmo ha procesado todos los factores térmicos. Tu mejor opción es:</p>

                                    <div class="group relative bg-gray-900 rounded-[3.5rem] p-12 text-white overflow-hidden shadow-2xl mb-12 transition-transform hover:scale-[1.02]">
                                        <div class="absolute -top-12 -right-12 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[100px] opacity-20 group-hover:opacity-40 transition-opacity"></div>
                                        
                                        <div class="relative z-10">
                                            <p class="text-[10px] font-black uppercase tracking-[0.5em] text-[var(--color-primary)] mb-8">Capacidad Óptima del Sistema</p>
                                            <h3 class="text-4xl md:text-6xl font-black mb-6 tracking-tighter">{{ recomendacion.capacidad }}</h3>
                                            
                                            <div class="h-px bg-white dark:bg-slate-900/10 w-full mb-8"></div>
                                            
                                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                                <div class="text-left">
                                                    <p class="text-[10px] uppercase text-gray-400 mb-1">Carga Estimada</p>
                                                    <p class="text-2xl font-black text-white">{{ calculoBTU.toLocaleString() }} <span class="text-xs font-medium text-gray-500 dark:text-gray-400">BTU/h</span></p>
                                                </div>
                                                <div class="px-6 py-4 bg-white dark:bg-slate-900/5 rounded-[1.5rem] border border-white/10 text-left max-w-xs backdrop-blur-md">
                                                    <p class="text-xs font-medium text-gray-300 italic">"{{ recomendacion.nota }}"</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Savings Badge -->
                                    <div v-if="form.tecnologia === 'inverter'" class="bg-green-50 border border-green-100 rounded-[2rem] p-8 mb-10 group">
                                        <div class="flex items-center gap-6">
                                            <div class="text-4xl group-hover:scale-110 transition-transform">💰</div>
                                            <div class="text-left">
                                                <p class="text-[10px] font-black text-green-600 uppercase tracking-widest mb-1">Impacto Financiero Inverter</p>
                                                <p class="text-2xl font-black text-gray-900 dark:text-white">Ahorro Estimado: <span class="text-green-600">${{ ahorroEstimado.toLocaleString() }} MXN/mes</span></p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Basado en un uso de {{ form.uso_horas }}h al día comparado con tecnología tradicional.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else class="bg-blue-50 border border-blue-100 rounded-[2rem] p-8 mb-10 flex items-center gap-6">
                                        <div class="text-4xl text-blue-500">💡</div>
                                        <div class="text-left">
                                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-1">Tip de Ahorro</p>
                                            <p class="text-gray-900 dark:text-white font-bold leading-tight">Si cambias a <span class="text-blue-600">Tecnología Inverter</span>, podrías ahorrar hasta <span class="font-black">${{ ahorroEstimado.toLocaleString() }}</span> al mes.</p>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-6 w-full">
                                        <button @click="openLeadModal" class="flex-1 py-6 bg-[var(--color-primary)] text-white rounded-[1.5rem] font-black uppercase tracking-widest text-sm hover:shadow-[0_20px_50px_-15px_rgba(59,130,246,0.5)] transform hover:-translate-y-1 transition-all">
                                            Agendar Especialista
                                        </button>
                                        <a :href="route('public.asesor.pdf', { btu: calculoBTU, rec: recomendacion.capacidad, 'form[area]': form.area, 'form[altura]': form.altura, 'form[personas]': form.personas, 'form[zona]': form.zona, 'form[habitacion]': form.habitacion, 'form[techo_directo]': form.techo_directo, 'form[ventanales]': form.ventanales, 'form[voltaje]': form.voltaje, 'form[funcion]': form.funcion, 'form[uso_horas]': form.uso_horas, 'form[tecnologia]': form.tecnologia, ahorro: ahorroEstimado })" target="_blank" class="flex-1 py-6 bg-white dark:bg-slate-900 text-gray-900 dark:text-white border-2 border-gray-100 rounded-[1.5rem] font-black uppercase tracking-widest text-sm hover:bg-white dark:bg-slate-900 transform hover:-translate-y-1 transition-all">
                                            Descargar Reporte PDF
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Navigation Drawer -->
                            <div class="mt-auto pt-12 flex items-center justify-between border-t border-gray-50">
                                <button 
                                    v-if="step > 1 && step < 5"
                                    @click="prevStep" 
                                    class="group flex items-center gap-3 px-6 py-3 font-black text-gray-400 hover:text-gray-900 dark:text-white transition-all uppercase tracking-widest text-xs"
                                >
                                    <span class="transform group-hover:-translate-x-2 transition-transform">←</span> Volver
                                </button>
                                <span v-else class="w-1"></span>

                                <button 
                                    v-if="step < 5"
                                    @click="nextStep"
                                    class="relative group"
                                >
                                    <div class="absolute -inset-1 bg-[var(--color-primary)] rounded-[1.5rem] blur opacity-25 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                    <div class="relative px-12 py-5 bg-[var(--color-primary)] text-white rounded-[1.5rem] font-black uppercase tracking-widest text-sm flex items-center gap-4 transition-all transform hover:scale-[1.05] hover:shadow-xl">
                                        {{ step === 4 ? 'Hacer Cálculo' : 'Continuar' }}
                                        <span class="transform group-hover:translate-x-2 transition-transform">→</span>
                                    </div>
                                </button>

                                <button 
                                    v-if="step === 5"
                                    @click="step = 1"
                                    class="mx-auto px-8 py-4 bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-gray-100 transition-all"
                                >
                                    Reiniciar Asistente
                                </button>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Floating Tech Badge -->
                    <div class="hidden lg:block absolute -right-20 top-20 transform -rotate-12">
                        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl shadow-2xl border border-gray-50 max-w-[180px]">
                            <p class="text-[10px] font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">HVAC Norm</p>
                            <p class="text-[9px] text-gray-400 font-medium">Cumplimos con la norma de ASHRAE para carga térmica residencial.</p>
                            <div class="mt-4 flex gap-1">
                                <div class="w-1 h-1 rounded-full bg-blue-500"></div>
                                <div class="w-1 h-1 rounded-full bg-blue-500"></div>
                                <div class="w-1 h-1 rounded-full bg-blue-500"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <PublicFooter :empresa="empresa" />
            </div>
        </main>

        <!-- Lead Modal -->
        <div v-if="showLeadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showLeadModal = false"></div>
            
            <div class="relative bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl max-w-md w-full p-10 overflow-hidden transform transition-all scale-100 animate-content-in">
                <button @click="showLeadModal = false" class="absolute top-6 right-6 text-gray-400 hover:text-gray-900 dark:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div v-if="!leadSent">
                    <div class="w-20 h-20 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-2xl flex items-center justify-center text-3xl mb-8">
                        {{ requiresLeadCapture ? '�' : '�👨‍🔧' }}
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-2">{{ requiresLeadCapture ? '¡Un paso más!' : 'Visita Técnica' }}</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium mb-8 leading-relaxed">
                        <template v-if="requiresLeadCapture">
                            Para ver tu <span class="font-black text-[var(--color-primary)]">Reporte de Climatización Personalizado</span>, déjanos tus datos. También te lo enviaremos por correo.
                        </template>
                        <template v-else>
                            Déjanos tus datos y un especialista se contactará contigo para validar tu cálculo de {{ recomendacion.capacidad }}.
                        </template>
                    </p>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Nombre Completo</label>
                            <input v-model="leadForm.nombre" @input="leadForm.nombre = leadForm.nombre.toUpperCase()" type="text" placeholder="TU NOMBRE" class="w-full px-6 py-4 bg-white dark:bg-slate-900 border-none rounded-2xl font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Teléfono (10 dígitos)</label>
                            <input v-model="leadForm.telefono" type="tel" maxlength="10" placeholder="Ej. 6861234567" class="w-full px-6 py-4 bg-white dark:bg-slate-900 border-none rounded-2xl font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Email <span class="text-gray-300 font-medium">(Opcional)</span></label>
                            <input v-model="leadForm.email" type="email" placeholder="Ej. juan@ejemplo.com" class="w-full px-6 py-4 bg-white dark:bg-slate-900 border-none rounded-2xl font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)] transition-all">
                        </div>
                        
                        <button 
                            @click="submitLead"
                            :disabled="isSubmitting"
                            class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase tracking-widest text-xs hover:shadow-xl transition-all flex items-center justify-center gap-3"
                            :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:-translate-y-1'"
                        >
                            <span v-if="isSubmitting" class="animate-spin">🌀</span>
                            {{ isSubmitting ? 'Procesando...' : (requiresLeadCapture ? 'Ver Mi Resultado' : 'Confirmar Agenda') }}
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-10">
                    <div class="w-20 h-20 bg-green-50 text-green-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                        ✅
                    </div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-2">¡Todo Listo!</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Hemos recibido tu solicitud. Un asesor se pondrá en contacto pronto.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-content-in {
    animation: contentIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes contentIn {
    from { 
        opacity: 0; 
        transform: translateY(30px) scale(0.98);
        filter: blur(10px);
    }
    to { 
        opacity: 1; 
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 32px;
    width: 32px;
    border-radius: 50%;
    background: white;
    border: 4px solid var(--color-primary);
    cursor: pointer;
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    transition: all 0.2s;
}

input[type="range"]::-webkit-slider-thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 10px 30px rgba(59,130,246,0.3);
}

/* Hide navigation buttons during final step for focus */
.step-5-active nav {
    opacity: 0.2;
    pointer-events: none;
}
</style>
