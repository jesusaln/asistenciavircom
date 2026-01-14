<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    empresa: Object,
});

const emit = defineEmits(['leadCaptured']);

const step = ref(1);
const totalSteps = 4;

const form = ref({
    habitacion: 'sala',
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
    uso_horas: '8',
    tecnologia: 'inverter',
});

const isCalculating = ref(false);
const showLeadModal = ref(false);
const requiresLeadCapture = ref(false);
const showResults = ref(false);

const leadForm = ref({
    nombre: '',
    telefono: '',
    email: '',
});
const isSubmitting = ref(false);
const leadSent = ref(false);

onMounted(async () => {
    // Intentar detectar zona climática automáticamente (silencioso si falla)
    try {
        // Usar ip-api.com que tiene mejor soporte CORS
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000); // 3s timeout
        
        const res = await fetch('/api/geoip', {
            signal: controller.signal
        });
        clearTimeout(timeoutId);
        
        if (res.ok) {
            const data = await res.json();
            const city = (data.city || '').toLowerCase();
            const region = (data.regionName || '').toLowerCase();
            
            // Clasificación por zona climática de México
            if (city.includes('mexicali') || city.includes('hermosillo') || city.includes('san luis') || 
                region.includes('sonora') || region.includes('baja california') || region.includes('chihuahua')) {
                form.value.zona = 'desierto';
            } else if (city.includes('cancun') || city.includes('veracruz') || city.includes('merida') ||
                region.includes('quintana') || region.includes('yucatan') || region.includes('tabasco')) {
                form.value.zona = 'costa';
            }
            // Si no coincide, mantiene 'centro' por defecto
        }
    } catch {
        // Silencioso: mantiene zona 'centro' por defecto
    }
});

const nextStep = () => {
    if (step.value === totalSteps) {
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
        showResults.value = true;
    }, 2200);
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const calculoBTU = computed(() => {
    // FACTORES BASADOS EN RECOMENDACIONES FIDE (México)
    
    // 1. Carga Base por Zona Térmica (BTU/m2)
    let factorZona = 600; // Zona Templada (Base)
    if (form.value.zona === 'costa') factorZona = 800;    // Cálida Húmeda
    if (form.value.zona === 'desierto') factorZona = 950; // Cálida Seca (Mayor carga sensible)

    let btuBase = form.value.area * factorZona;

    // 2. Carga por Altura (Volumen extra)
    // FIDE considera altura estándar 2.5m. Por cada metro extra, +10%
    if (form.value.altura > 2.5) {
        const alturaExtra = form.value.altura - 2.5;
        btuBase += (btuBase * (alturaExtra * 0.10)); 
    }

    // 3. Carga por Ocupantes (Metabolismo Latente/Sensible)
    // FIDE/ASHRAE promedio hogar: 600 BTU/persona
    const btuPersonas = (form.value.personas - 1) * 600; 

    // 4. Carga por Equipos (Ganancia Interna)
    // Promedio TV/PC: 400-800 BTU. Usamos 600 promedio.
    const btuAparatos = form.value.aparatos * 600;

    // 5. Factores Envolvente (Ganancia Externa)
    let factorEnvolvente = 1.0;
    
    // Techo sin aislamiento
    if (form.value.techo_directo) factorEnvolvente += 0.25; 
    
    // Ventanales (Ganancia solar directa)
    if (form.value.ventanales) factorEnvolvente += 0.20;

    // Aislamiento Muros/Techo
    if (form.value.aislamiento === 'bueno') factorEnvolvente -= 0.10;
    if (form.value.aislamiento === 'pobre') factorEnvolvente += 0.15;

    // Orientación/Insolación
    if (form.value.sol === 'mucho') factorEnvolvente += 0.15;
    if (form.value.sol === 'poco') factorEnvolvente -= 0.05;

    // 6. Factor Uso Específico
    let btuExtraUso = 0;
    if (form.value.habitacion === 'cocina') btuExtraUso = 3000; // Calor latente cocinar

    const totalBTU = (btuBase * factorEnvolvente) + btuPersonas + btuAparatos + btuExtraUso;

    return Math.round(totalBTU);
});

const ahorroEstimado = computed(() => {
    const horas = parseInt(form.value.uso_horas);
    const kwConsumoConv = 1.2;
    const kwConsumoInv = 0.5;
    const costoKwh = 3.5;
    
    const ahorroMensual = (kwConsumoConv - kwConsumoInv) * horas * 30 * costoKwh;
    return Math.round(ahorroMensual);
});

const recomendacion = computed(() => {
    const btu = calculoBTU.value;
    
    if (btu <= 13000) return { capacidad: '1 Tonelada (12,000 BTU)', nota: 'Ideal para recámaras o áreas pequeñas.' };
    if (btu <= 19000) return { capacidad: '1.5 Toneladas (18,000 BTU)', nota: 'Perfecto para salas medianas o recámaras amplias.' };
    if (btu <= 26000) return { capacidad: '2 Toneladas (24,000 BTU)', nota: 'Recomendado para espacios abiertos o áreas sociales.' };
    if (btu <= 38000) return { capacidad: '3 Toneladas (36,000 BTU)', nota: 'Necesario para áreas comerciales o techos altos.' };
    
    return { capacidad: 'Sistema Central o Multi-Split', nota: 'Tu requerimiento supera la capacidad de una unidad estándar.' };
});

const submitLead = async () => {
    // Convertir nombre a mayúsculas antes de validar
    leadForm.value.nombre = leadForm.value.nombre.toUpperCase();

    if (!leadForm.value.nombre || !leadForm.value.telefono) {
        alert('Por favor completa tu nombre y teléfono.');
        return;
    }

    // Validación de 10 dígitos para el teléfono
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
            emit('leadCaptured', { nombre: leadForm.value.nombre });
            
            if (requiresLeadCapture.value) {
                setTimeout(() => {
                    showLeadModal.value = false;
                    leadSent.value = false;
                    requiresLeadCapture.value = false;
                    proceedToResults();
                }, 1500);
            } else {
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

const resetSimulator = () => {
    step.value = 1;
    showResults.value = false;
    leadForm.value = { nombre: '', telefono: '', email: '' };
};
</script>

<template>
    <section class="py-24 bg-gradient-to-b from-gray-50 to-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-12 gap-8 items-start">
                
                <!-- Panel Explicativo (1/3 aprox) -->
                <div class="lg:col-span-4 lg:sticky lg:top-24">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-[2.5rem] p-8 lg:p-10 text-white relative overflow-hidden">
                        <!-- Decoración -->
                        <div class="absolute top-0 right-0 w-48 h-48 bg-[var(--color-primary)] rounded-full blur-[80px] opacity-20"></div>
                        <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-[var(--color-secondary)] rounded-full blur-[60px] opacity-20"></div>
                        
                        <div class="relative z-10">
                            <!-- Icono Principal -->
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-3xl mb-6">
                                🌡️
                            </div>
                            
                            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-3">Herramienta Inteligente</h2>
                            <h3 class="text-2xl lg:text-3xl font-black mb-4 leading-tight">Simulador de Climatización</h3>
                            
                            <p class="text-gray-300 text-sm leading-relaxed mb-6">
                                Calcula el <span class="text-white font-bold">equipo de aire acondicionado ideal</span> para tu espacio. Nuestro simulador analiza múltiples factores para darte una recomendación precisa.
                            </p>
                            
                            <!-- Beneficios -->
                            <div class="space-y-3 mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">✓</div>
                                    <span class="text-sm text-gray-300">Cálculo basado en <span class="text-white font-bold">normas FIDE</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">✓</div>
                                    <span class="text-sm text-gray-300">Considera tu <span class="text-white font-bold">zona climática</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">✓</div>
                                    <span class="text-sm text-gray-300">Estima <span class="text-white font-bold">ahorro mensual</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">✓</div>
                                    <span class="text-sm text-gray-300">Reporte <span class="text-white font-bold">PDF descargable</span></span>
                                </div>
                            </div>
                            
                            <!-- CTA -->
                            <div class="p-4 bg-[var(--color-primary-soft)] rounded-xl">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)] mb-1">⚡ Solo 4 pasos</p>
                                <p class="text-xs text-gray-300">Completa el formulario y recibe tu recomendación personalizada en segundos.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Simulador (2/3 aprox) -->
                <div class="lg:col-span-8">
                    <div class="bg-white rounded-[2.5rem] shadow-[0_30px_100px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden transition-all duration-500">
            
            <!-- Progress Bar -->
            <div class="px-8 pt-8">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Paso {{ step }} de {{ totalSteps }}</span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)]">{{ Math.round((step/totalSteps)*100) }}%</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-[var(--color-primary)] transition-all duration-500 ease-out" :style="`width: ${(step / totalSteps) * 100}%`"></div>
                </div>
            </div>

            <div class="p-8 relative min-h-[400px]">
                
                <!-- Analyzing Overlay -->
                <div v-if="isCalculating" class="absolute inset-0 z-50 bg-white/95 backdrop-blur-sm flex flex-col items-center justify-center p-8 text-center animate-fade-in">
                    <div class="relative w-20 h-20 mb-8">
                        <div class="absolute inset-0 border-4 border-[var(--color-primary-soft)] rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-[var(--color-primary)] rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-2xl">🧮</div>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 mb-3">Analizando tu espacio...</h3>
                    <p class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] animate-pulse">PROCESANDO FACTORES</p>
                </div>

                <!-- Results View -->
                <div v-if="showResults && !isCalculating" class="text-center animate-fade-in">
                    <div class="w-20 h-20 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                        ✨
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">¡Tu Resultado!</h3>
                    
                    <div class="bg-gray-900 rounded-2xl p-6 text-white my-6">
                        <p class="text-[9px] font-black uppercase tracking-[0.4em] text-[var(--color-primary)] mb-2">Capacidad Recomendada</p>
                        <h4 class="text-2xl md:text-3xl font-black mb-2">{{ recomendacion.capacidad }}</h4>
                        <p class="text-xs text-gray-400">{{ calculoBTU.toLocaleString() }} BTU/h</p>
                    </div>

                    <div v-if="form.tecnologia === 'inverter'" class="bg-green-50 border border-green-100 rounded-xl p-4 mb-6 text-left">
                        <div class="flex items-center gap-4">
                            <span class="text-2xl">💰</span>
                            <div>
                                <p class="text-[9px] font-black text-green-600 uppercase tracking-widest">Ahorro Mensual Estimado</p>
                                <p class="text-lg font-black text-gray-900">${{ ahorroEstimado.toLocaleString() }} MXN</p>
                            </div>
                        </div>
                    </div>

                    <a :href="route('public.asesor.pdf', { 
                            btu: calculoBTU, 
                            rec: recomendacion.capacidad, 
                            ahorro: ahorroEstimado,
                            'form[area]': form.area,
                            'form[altura]': form.altura,
                            'form[zona]': form.zona,
                            'form[personas]': form.personas,
                            'form[aparatos]': form.aparatos,
                            'form[techo_directo]': form.techo_directo ? '1' : '0',
                            'form[ventanales]': form.ventanales ? '1' : '0',
                            'form[aislamiento]': form.aislamiento,
                            'form[sol]': form.sol,
                            'form[habitacion]': form.habitacion,
                            'form[voltaje]': form.voltaje,
                            'form[funcion]': form.funcion,
                            'form[uso_horas]': form.uso_horas,
                            'form[tecnologia]': form.tecnologia
                        })" 
                        target="_blank"
                        class="block w-full py-4 mb-4 bg-gray-900 text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <span>📄</span> Descargar Reporte PDF
                    </a>

                    <button @click="resetSimulator" class="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                        ← Calcular de nuevo
                    </button>
                </div>

                <!-- Step 1: Espacio -->
                <div v-if="step === 1 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-gray-900 mb-2">¿Qué espacio climatizamos?</h3>
                    <p class="text-gray-400 text-sm mb-6">Selecciona el tipo de habitación.</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button v-for="h in [
                            {id: 'sala', label: 'Sala', icon: '🛋️'},
                            {id: 'recamara', label: 'Recámara', icon: '🛏️'},
                            {id: 'cocina', label: 'Cocina', icon: '🍲'},
                            {id: 'oficina', label: 'Oficina', icon: '💻'}
                        ]" :key="h.id" 
                        @click="form.habitacion = h.id"
                        :class="['p-4 rounded-xl border-2 transition-all text-center', 
                                form.habitacion === h.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100 hover:border-gray-200']">
                            <span class="text-2xl block mb-2">{{ h.icon }}</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-900">{{ h.label }}</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="z in [
                            {id: 'centro', label: 'Centro', icon: '🏢'},
                            {id: 'costa', label: 'Costa', icon: '🏖️'},
                            {id: 'desierto', label: 'Norte', icon: '🏜️'}
                        ]" :key="z.id" 
                        @click="form.zona = z.id"
                        :class="['p-3 rounded-xl border-2 transition-all text-center', 
                                form.zona === z.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="text-xl block">{{ z.icon }}</span>
                            <span class="text-[9px] font-bold text-gray-500">{{ z.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Dimensiones -->
                <div v-if="step === 2 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-gray-900 mb-2">Dimensiones del espacio</h3>
                    <p class="text-gray-400 text-sm mb-6">Indica el tamaño aproximado.</p>
                    
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <label for="simulator-area" class="text-xs font-bold text-gray-600 uppercase">Área</label>
                            <span class="px-3 py-1 bg-[var(--color-primary)] text-white font-black rounded-lg text-sm">{{ form.area }} m²</span>
                        </div>
                        <input id="simulator-area" type="range" v-model="form.area" min="5" max="100" class="w-full h-2 bg-gray-200 rounded-full appearance-none cursor-pointer accent-[var(--color-primary)]" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <label for="simulator-altura" class="text-xs font-bold text-gray-600 uppercase block mb-2">Altura (m)</label>
                            <input id="simulator-altura" type="number" v-model="form.altura" step="0.1" class="w-full p-2 bg-white rounded-lg border-none font-bold text-lg text-center" />
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4">
                            <label class="text-xs font-bold text-gray-600 uppercase block mb-2">Personas</label>
                            <div class="flex items-center justify-center gap-4">
                                <button @click="form.personas > 1 && form.personas--" class="w-8 h-8 rounded-lg bg-white border border-gray-200 font-bold">-</button>
                                <span class="text-xl font-black">{{ form.personas }}</span>
                                <button @click="form.personas < 20 && form.personas++" class="w-8 h-8 rounded-lg bg-white border border-gray-200 font-bold">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Construcción -->
                <div v-if="step === 3 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-gray-900 mb-2">Factores de calor</h3>
                    <p class="text-gray-400 text-sm mb-6">Selecciona los que apliquen.</p>
                    
                    <div class="space-y-3 mb-4">
                        <button @click="form.techo_directo = !form.techo_directo" 
                                :class="['w-full p-4 rounded-xl border-2 transition-all text-left flex items-center gap-4', form.techo_directo ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="text-2xl">🏠</span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900 text-sm">Techo Directo</p>
                                <p class="text-[10px] text-gray-400">Sol directo al techo</p>
                            </div>
                            <div v-if="form.techo_directo" class="w-5 h-5 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-xs">✓</div>
                        </button>

                        <button @click="form.ventanales = !form.ventanales" 
                                :class="['w-full p-4 rounded-xl border-2 transition-all text-left flex items-center gap-4', form.ventanales ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="text-2xl">🪟</span>
                            <div class="flex-1">
                                <p class="font-bold text-gray-900 text-sm">Ventanales Grandes</p>
                                <p class="text-[10px] text-gray-400">Áreas acristaladas</p>
                            </div>
                            <div v-if="form.ventanales" class="w-5 h-5 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-xs">✓</div>
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button v-for="ai in [{v:'bueno', t:'Buen Aislamiento'}, {v:'normal', t:'Normal'}, {v:'pobre', t:'Malo'}]" 
                                :key="ai.v" @click="form.aislamiento = ai.v"
                                :class="['flex-1 py-2 rounded-lg border-2 text-[10px] font-bold uppercase transition-all', form.aislamiento === ai.v ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-[var(--color-primary)]' : 'border-gray-100 text-gray-500']">
                            {{ ai.t }}
                        </button>
                    </div>
                </div>

                <!-- Step 4: Tecnología -->
                <div v-if="step === 4 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-gray-900 mb-2">Preferencias técnicas</h3>
                    <p class="text-gray-400 text-sm mb-6">Últimos detalles para tu cálculo.</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button @click="form.uso_horas = '8'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.uso_horas === '8' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="text-xl block mb-1">🌙</span>
                            <span class="text-[10px] font-bold text-gray-600">8 Horas/día</span>
                        </button>
                        <button @click="form.uso_horas = '24'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.uso_horas === '24' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="text-xl block mb-1">☀️</span>
                            <span class="text-[10px] font-bold text-gray-600">Todo el día</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button @click="form.tecnologia = 'convencional'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.tecnologia === 'convencional' ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-100']">
                            <span class="text-[10px] font-bold uppercase">Convencional</span>
                        </button>
                        <button @click="form.tecnologia = 'inverter'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center relative', form.tecnologia === 'inverter' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100']">
                            <span class="absolute -top-2 -right-2 px-2 py-0.5 bg-green-500 text-white text-[8px] font-bold rounded-full">AHORRA</span>
                            <span class="text-[10px] font-bold uppercase text-gray-900">Inverter</span>
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button @click="form.voltaje = '110'" :class="['flex-1 py-2 rounded-lg border-2 text-xs font-bold transition-all', form.voltaje === '110' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100 text-gray-500']">110V</button>
                        <button @click="form.voltaje = '220'" :class="['flex-1 py-2 rounded-lg border-2 text-xs font-bold transition-all', form.voltaje === '220' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-gray-100 text-gray-500']">220V</button>
                        <button @click="form.funcion = 'frio'" :class="['flex-1 py-2 rounded-lg border-2 text-xs font-bold transition-all', form.funcion === 'frio' ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-gray-100 text-gray-500']">Frío</button>
                        <button @click="form.funcion = 'dual'" :class="['flex-1 py-2 rounded-lg border-2 text-xs font-bold transition-all', form.funcion === 'dual' ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-gray-100 text-gray-500']">Dual</button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div v-if="!showResults && !isCalculating" class="px-8 pb-8 flex items-center justify-between border-t border-gray-50 pt-6">
                <button v-if="step > 1" @click="prevStep" class="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                    ← Anterior
                </button>
                <span v-else></span>

                <button @click="nextStep" class="px-8 py-3 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all">
                    {{ step === totalSteps ? '¡Calcular!' : 'Siguiente →' }}
                </button>
            </div>
        </div>

        <!-- Lead Modal -->
        <div v-if="showLeadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showLeadModal = false"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 animate-fade-in">
                <button @click="showLeadModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">✕</button>

                <div v-if="!leadSent">
                    <div class="text-center mb-6">
                        <span class="text-4xl block mb-4">📊</span>
                        <h3 class="text-2xl font-black text-gray-900">¡Un paso más!</h3>
                        <p class="text-gray-500 text-sm mt-2">Para ver tu <span class="font-bold text-[var(--color-primary)]">Reporte Personalizado</span>, déjanos tus datos.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="lead-nombre" class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nombre Completo</label>
                            <input id="lead-nombre" v-model="leadForm.nombre" @input="leadForm.nombre = leadForm.nombre.toUpperCase()" type="text" placeholder="TU NOMBRE" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl font-medium" />
                        </div>
                        <div>
                            <label for="lead-telefono" class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Teléfono (10 dígitos)</label>
                            <input id="lead-telefono" v-model="leadForm.telefono" type="tel" maxlength="10" placeholder="Ej: 6861234567" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl font-medium" />
                        </div>
                        <div>
                            <label for="lead-email" class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email <span class="text-gray-300">(opcional)</span></label>
                            <input id="lead-email" v-model="leadForm.email" type="email" placeholder="correo@ejemplo.com" class="w-full px-4 py-3 bg-gray-50 border-none rounded-xl font-medium" />
                        </div>
                        
                        <button @click="submitLead" :disabled="isSubmitting" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all" :class="isSubmitting ? 'opacity-70' : ''">
                            {{ isSubmitting ? 'Procesando...' : 'Ver Mi Resultado' }}
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-6">
                    <span class="text-5xl block mb-4">✅</span>
                    <h3 class="text-2xl font-black text-gray-900">¡Listo!</h3>
                    <p class="text-gray-500 mt-2">Calculando tu reporte...</p>
                </div>
            </div>
            </div>
        </div>
                    </div> <!-- Cierre del simulador card -->
                </div> <!-- Cierre lg:col-span-8 -->
        
    </section>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--color-primary);
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
</style>
