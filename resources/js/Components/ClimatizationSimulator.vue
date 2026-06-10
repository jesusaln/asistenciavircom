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
    // Solo ejecutar si no estamos en una página de autenticación
    const currentPath = window.location.pathname;
    if (currentPath.includes('/login') || currentPath.includes('/register')) {
        return; // No hacer llamadas de geoip durante autenticación
    }

    try {
        // Usar axios para evitar conflictos con Inertia
        const res = await axios.get('/api/geoip', {
            timeout: 3000,
            skipGlobalErrorHandler: true, // Evitar toast si falla el geoip
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = res.data;
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

const desgloseBTU = computed(() => {
    // FACTORES BASADOS EN RECOMENDACIONES FIDE (México) - Valores ajustados para evitar sobrecálculo

    // 1. Carga Base por Zona Térmica (BTU/m2) - Valores realistas FIDE
    let factorZona = 500; // Zona Templada (Base)
    if (form.value.zona === 'costa') factorZona = 650;    // Cálida Húmeda
    if (form.value.zona === 'desierto') factorZona = 750; // Cálida Seca (ajustado de 950)

    let btuBase = form.value.area * factorZona;

    // 2. Carga por Altura (Volumen extra) - Solo si supera 3m (no 2.5m)
    let btuAltura = 0;
    if (form.value.altura > 3.0) {
        const alturaExtra = form.value.altura - 3.0;
        btuAltura = btuBase * (alturaExtra * 0.08);
    }

    // 3. Carga por Ocupantes (Metabolismo Latente/Sensible)
    const btuPersonas = Math.max(0, (form.value.personas - 1) * 400);

    // 4. Carga por Equipos (Ganancia Interna)
    const btuAparatos = form.value.aparatos * 400;

    // 5. Factores Envolvente (Ganancia Externa)
    let factorEnvolvente = 1.0;
    let btuTecho = 0;
    let btuVentanas = 0;
    let btuAislamiento = 0;
    let btuSol = 0;

    if (form.value.techo_directo) {
        factorEnvolvente += 0.15; // Reducido de 0.25
        btuTecho = btuBase * 0.15;
    }

    if (form.value.ventanales) {
        factorEnvolvente += 0.12; // Reducido de 0.20
        btuVentanas = btuBase * 0.12;
    }

    if (form.value.aislamiento === 'bueno') {
        factorEnvolvente -= 0.08;
        btuAislamiento = -(btuBase * 0.08);
    }
    if (form.value.aislamiento === 'pobre') {
        factorEnvolvente += 0.10; // Reducido de 0.15
        btuAislamiento = btuBase * 0.10;
    }

    if (form.value.sol === 'mucho') {
        factorEnvolvente += 0.10; // Reducido de 0.15
        btuSol = btuBase * 0.10;
    }
    if (form.value.sol === 'poco') {
        factorEnvolvente -= 0.05;
        btuSol = -(btuBase * 0.05);
    }

    // 6. Factor Uso Específico
    let btuExtraUso = 0;
    if (form.value.habitacion === 'cocina') btuExtraUso = 2000; // Reducido de 3000

    const btuBaseConEnvolvente = btuBase * factorEnvolvente;
    const totalBTU = btuBaseConEnvolvente + btuPersonas + btuAparatos + btuExtraUso;

    return {
        zona: Math.round(btuBase),
        altura: Math.round(btuAltura),
        personas: Math.round(btuPersonas),
        aparatos: Math.round(btuAparatos),
        techo: Math.round(btuTecho),
        ventanas: Math.round(btuVentanas),
        aislamiento: Math.round(btuAislamiento),
        sol: Math.round(btuSol),
        habitacion: Math.round(btuExtraUso),
        total: Math.round(totalBTU),
        factorZona: factorZona
    };
});

const calculoBTU = computed(() => {
    // Retornamos el total del desglose para mantener compatibilidad
    return desgloseBTU.value.total;
});

const ahorroEstimado = computed(() => {
    const horas = parseInt(form.value.uso_horas);
    const costoKwh = 3.5; // Tarifa promedio CFE
    
    // Obtener el producto recomendado para calcular consumo real
    const productoRec = productosRecomendados.value[0];
    const kwConv = productoRec?.consumoPromedio ? productoRec.consumoPromedio * 1.5 : 1.5;
    const kwInv = productoRec?.consumoPromedio ? productoRec.consumoPromedio * 0.6 : 0.6;
    
    const ahorroMensual = (kwConv - kwInv) * horas * 30 * costoKwh;
    return Math.round(ahorroMensual);
});

const recomendacion = computed(() => {
    const btu = calculoBTU.value;

    if (btu <= 13000) return { 
        capacidad: '1 Tonelada (12,000 BTU)', 
        nota: 'Ideal para recámaras o áreas pequeñas.',
        btuMin: 9000,
        btuMax: 13000
    };
    if (btu <= 19000) return { 
        capacidad: '1.5 Toneladas (18,000 BTU)', 
        nota: 'Perfecto para salas medianas o recámaras amplias.',
        btuMin: 13001,
        btuMax: 19000
    };
    if (btu <= 26000) return { 
        capacidad: '2 Toneladas (24,000 BTU)', 
        nota: 'Recomendado para espacios abiertos o áreas sociales.',
        btuMin: 19001,
        btuMax: 26000
    };
    if (btu <= 38000) return { 
        capacidad: '3 Toneladas (36,000 BTU)', 
        nota: 'Necesario para áreas comerciales o techos altos.',
        btuMin: 26001,
        btuMax: 38000
    };

    return { 
        capacidad: 'Sistema Central o Multi-Split', 
        nota: 'Tu requerimiento supera la capacidad de una unidad estándar.',
        btuMin: 38001,
        btuMax: null
    };
});

const productosRecomendados = computed(() => {
    const rec = recomendacion.value;
    if (!rec.btuMin) return [];

    // Enlace directo al catálogo filtrado por BTU y marca Mirage
    const catalogoLink = route('catalogo.index');

    const productos = [];
    
    if (rec.btuMin <= 13000) {
        productos.push({
            nombre: 'Mirage Inverter 1 Tonelada',
            btu: 12000,
            eficiencia: 'Hasta 65% ahorro energético',
            consumoPromedio: 0.8,
            imagen: null,
            link: `${catalogoLink}?q=mirage+inverter+12000+btu&marca=mirage`
        });
    }
    
    if (rec.btuMin <= 19000 && rec.btuMax >= 13001) {
        productos.push({
            nombre: 'Mirage Inverter 1.5 Toneladas',
            btu: 18000,
            eficiencia: 'Hasta 65% ahorro energético',
            consumoPromedio: 1.2,
            imagen: null,
            link: `${catalogoLink}?q=mirage+inverter+18000+btu&marca=mirage`
        });
    }
    
    if (rec.btuMin <= 26000 && rec.btuMax >= 19001) {
        productos.push({
            nombre: 'Mirage Inverter 2 Toneladas',
            btu: 24000,
            eficiencia: 'Hasta 65% ahorro energético',
            consumoPromedio: 1.6,
            imagen: null,
            link: `${catalogoLink}?q=mirage+inverter+24000+btu&marca=mirage`
        });
    }
    
    if (rec.btuMin <= 38000 && rec.btuMax >= 26001) {
        productos.push({
            nombre: 'Mirage Inverter 3 Toneladas',
            btu: 36000,
            eficiencia: 'Hasta 65% ahorro energético',
            consumoPromedio: 2.4,
            imagen: null,
            link: `${catalogoLink}?q=mirage+inverter+36000+btu&marca=mirage`
        });
    }

    if (rec.btuMin >= 38001) {
        productos.push({
            nombre: 'Sistema Multi-Split Mirage',
            btu: null,
            eficiencia: 'Solución personalizada para grandes espacios',
            consumoPromedio: null,
            imagen: null,
            link: `${catalogoLink}?q=mirage+multi+split&marca=mirage`
        });
    }

    return productos;
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
    <section class="py-24 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 overflow-hidden transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-12 gap-8 items-start">
                
                <!-- Panel Explicativo (1/3 aprox) -->
                <div class="lg:col-span-4 lg:sticky lg:top-24">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2.5rem] p-8 lg:p-10 text-white relative overflow-hidden">
                        <!-- Decoración -->
                        <div class="absolute top-0 right-0 w-48 h-48 bg-[var(--color-primary)] rounded-full blur-[80px] opacity-20"></div>
                        <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-[var(--color-secondary)] rounded-full blur-[60px] opacity-20"></div>
                        
                        <div class="relative z-10">
                            <!-- Icono Principal -->
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-3xl mb-6">
                                <font-awesome-icon icon="snowflake" />
                            </div>
                            
                            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-3">Herramienta Inteligente</h2>
                            <h3 class="text-2xl lg:text-3xl font-black mb-4 leading-tight">Simulador de Climatización</h3>
                            
                            <p class="text-slate-300 text-sm leading-relaxed mb-6">
                                Calcula el <span class="text-white font-bold">equipo de aire acondicionado ideal</span> para tu espacio. Nuestro simulador analiza múltiples factores para darte una recomendación precisa.
                            </p>
                            
                            <!-- Beneficios -->
                            <div class="space-y-3 mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm">
                                        <font-awesome-icon icon="check" />
                                    </div>
                                    <span class="text-sm text-slate-300">Cálculo basado en <span class="text-white font-bold">normas FIDE</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm">
                                        <font-awesome-icon icon="check" />
                                    </div>
                                    <span class="text-sm text-slate-300">Considera tu <span class="text-white font-bold">zona climática</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm">
                                        <font-awesome-icon icon="check" />
                                    </div>
                                    <span class="text-sm text-slate-300">Estima <span class="text-white font-bold">ahorro mensual</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm">
                                        <font-awesome-icon icon="check" />
                                    </div>
                                    <span class="text-sm text-slate-300">Reporte <span class="text-white font-bold">PDF descargable</span></span>
                                </div>
                            </div>
                            
                            <!-- CTA -->
                            <div class="p-4 bg-[var(--color-primary-soft)] rounded-xl">
                                <p class="text-[10px] font-black uppercase tracking-wide text-[var(--color-primary)] mb-1">
                                    <font-awesome-icon icon="bolt" class="mr-1" /> Solo 4 pasos
                                </p>
                                <p class="text-xs text-slate-300">Completa el formulario y recibe tu recomendación personalizada en segundos.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Simulador (2/3 aprox) -->
                <div class="lg:col-span-8">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-[0_30px_100px_rgba(0,0,0,0.08)] dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-500">
            
            <!-- Progress Bar -->
            <div class="px-8 pt-8">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[10px] font-black uppercase tracking-wide text-slate-400 dark:text-slate-500">Paso {{ step }} de {{ totalSteps }}</span>
                    <span class="text-[10px] font-black uppercase tracking-wide text-[var(--color-primary)]">{{ Math.round((step/totalSteps)*100) }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-[var(--color-primary)] transition-all duration-500 ease-out" :style="`width: ${(step / totalSteps) * 100}%`"></div>
                </div>
            </div>

            <div class="p-8 relative min-h-[400px]">
                
                <!-- Analyzing Overlay -->
                <div v-if="isCalculating" class="absolute inset-0 z-50 bg-white/80 dark:bg-slate-800/95 backdrop-blur-sm flex flex-col items-center justify-center p-8 text-center animate-fade-in">
                    <div class="relative w-20 h-20 mb-8">
                        <div class="absolute inset-0 border-4 border-[var(--color-primary-soft)] rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-[var(--color-primary)] rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-2xl">
                            <font-awesome-icon icon="calculator" />
                        </div>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-3">Analizando tu espacio...</h3>
                    <p class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] animate-pulse">PROCESANDO FACTORES</p>
                </div>

                <!-- Results View -->
                <div v-if="showResults && !isCalculating" class="animate-fade-in">
                    <div class="text-center mb-8">
                        <div class="w-20 h-20 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                            <font-awesome-icon icon="wand-magic-sparkles" />
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">¡Tu Resultado!</h3>
                    </div>

                    <!-- Recomendación Principal -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 text-white mb-8 border border-white/10">
                        <p class="text-[9px] font-black uppercase tracking-[0.4em] text-[var(--color-primary)] mb-3">Capacidad Recomendada</p>
                        <h4 class="text-3xl md:text-4xl font-black mb-3">{{ recomendacion.capacidad }}</h4>
                        <p class="text-sm text-slate-400">{{ calculoBTU.toLocaleString() }} BTU/h requeridos</p>
                        <p class="text-xs text-slate-500 mt-3">{{ recomendacion.nota }}</p>
                    </div>

                    <!-- Ahorro Estimado -->
                    <div v-if="form.tecnologia === 'inverter'" class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-emerald-200 dark:border-emerald-800/30 rounded-2xl p-6 mb-8 text-left transition-colors">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 bg-emerald-500 dark:bg-emerald-600 rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                                <font-awesome-icon icon="piggy-bank" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">Ahorro Mensual con Inverter</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white">${{ ahorroEstimado.toLocaleString() }} <span class="text-sm font-bold text-slate-500">MXN/mes</span></p>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Ahorro anual: <strong class="text-emerald-600 dark:text-emerald-400">${{ (ahorroEstimado * 12).toLocaleString() }} MXN</strong></p>
                            </div>
                        </div>
                    </div>

                    <!-- Desglose Visual de BTUs -->
                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-2xl p-6 mb-8 border border-slate-100 dark:border-slate-700 transition-colors">
                        <h4 class="text-lg font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-xl flex items-center justify-center text-lg">
                                <font-awesome-icon icon="chart-bar" />
                            </span>
                            Desglose de Carga Térmica
                        </h4>

                        <!-- Barra de Progreso Visual -->
                        <div class="mb-6">
                            <div class="flex justify-between text-xs font-bold text-slate-600 dark:text-slate-400 mb-2">
                                <span>Distribución de Carga Térmica</span>
                                <span class="text-[var(--color-primary)]">{{ desgloseBTU.total.toLocaleString() }} BTU/h</span>
                            </div>
                            <div class="h-6 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden flex text-xs">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-400 transition-all duration-500 flex items-center justify-center text-white font-bold" :style="`width: ${Math.max((desgloseBTU.zona / desgloseBTU.total) * 100, 8)}%`" v-if="(desgloseBTU.zona / desgloseBTU.total) * 100 > 8">
                                    <span v-if="(desgloseBTU.zona / desgloseBTU.total) * 100 > 15">Zona {{ Math.round((desgloseBTU.zona / desgloseBTU.total) * 100) }}%</span>
                                </div>
                                <div v-if="desgloseBTU.altura > 0" class="bg-gradient-to-r from-purple-500 to-purple-400 transition-all duration-500" :style="`width: ${(desgloseBTU.altura / desgloseBTU.total) * 100}%`"></div>
                                <div class="bg-gradient-to-r from-brand-500 to-orange-400 transition-all duration-500" :style="`width: ${(desgloseBTU.personas / desgloseBTU.total) * 100}%`"></div>
                                <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 transition-all duration-500" :style="`width: ${(desgloseBTU.aparatos / desgloseBTU.total) * 100}%`"></div>
                                <div v-if="desgloseBTU.techo > 0" class="bg-gradient-to-r from-red-500 to-red-400 transition-all duration-500" :style="`width: ${(desgloseBTU.techo / desgloseBTU.total) * 100}%`"></div>
                                <div v-if="desgloseBTU.ventanas > 0" class="bg-gradient-to-r from-pink-500 to-pink-400 transition-all duration-500" :style="`width: ${(desgloseBTU.ventanas / desgloseBTU.total) * 100}%`"></div>
                                <div v-if="desgloseBTU.habitacion > 0" class="bg-gradient-to-r from-brand-500 to-brand-400 transition-all duration-500" :style="`width: ${(desgloseBTU.habitacion / desgloseBTU.total) * 100}%`"></div>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-2 mt-4 text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Zona: <strong class="text-slate-900 dark:text-white">{{ desgloseBTU.zona.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div v-if="desgloseBTU.altura > 0" class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Altura: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.altura.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-brand-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Personas: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.personas.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Equipos: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.aparatos.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div v-if="desgloseBTU.techo > 0" class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-rose-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Techo: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.techo.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div v-if="desgloseBTU.ventanas > 0" class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-pink-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Ventanas: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.ventanas.toLocaleString() }} BTU</strong></span>
                                </div>
                                <div v-if="desgloseBTU.habitacion > 0" class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-brand-500 rounded-full"></div>
                                    <span class="text-slate-600 dark:text-slate-400">Cocina: <strong class="text-slate-900 dark:text-white">+{{ desgloseBTU.habitacion.toLocaleString() }} BTU</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Desglose -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="globe-americas" class="text-blue-500" />
                                    Zona climática ({{ form.zona === 'desierto' ? 'Norte' : form.zona === 'costa' ? 'Costa' : 'Centro' }})
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ desgloseBTU.zona.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.altura !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="arrows-alt-v" class="text-purple-500" />
                                    Altura ({{ form.altura }}m)
                                </span>
                                <span :class="desgloseBTU.altura > 0 ? 'text-orange-600' : 'text-emerald-600'" class="font-bold">{{ desgloseBTU.altura > 0 ? '+' : '' }}{{ desgloseBTU.altura.toLocaleString() }} BTU</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="users" class="text-orange-500" />
                                    {{ form.personas }} persona(s)
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white">+{{ desgloseBTU.personas.toLocaleString() }} BTU</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="tv" class="text-yellow-500" />
                                    {{ form.aparatos }} aparato(s)
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white">+{{ desgloseBTU.aparatos.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.techo !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="sun" class="text-rose-500" />
                                    Sol directo en techo
                                </span>
                                <span :class="desgloseBTU.techo > 0 ? 'text-rose-600' : 'text-emerald-600'" class="font-bold">{{ desgloseBTU.techo > 0 ? '+' : '' }}{{ desgloseBTU.techo.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.ventanas !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="columns" class="text-pink-500" />
                                    Ventanales
                                </span>
                                <span :class="desgloseBTU.ventanas > 0 ? 'text-rose-600' : 'text-emerald-600'" class="font-bold">{{ desgloseBTU.ventanas > 0 ? '+' : '' }}{{ desgloseBTU.ventanas.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.aislamiento !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="layer-group" class="text-slate-500" />
                                    Aislamiento ({{ form.aislamiento }})
                                </span>
                                <span :class="desgloseBTU.aislamiento > 0 ? 'text-rose-600' : 'text-emerald-600'" class="font-bold">{{ desgloseBTU.aislamiento > 0 ? '+' : '' }}{{ desgloseBTU.aislamiento.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.sol !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="sun" class="text-brand-500" />
                                    Insolación ({{ form.sol }})
                                </span>
                                <span :class="desgloseBTU.sol > 0 ? 'text-rose-600' : 'text-emerald-600'" class="font-bold">{{ desgloseBTU.sol > 0 ? '+' : '' }}{{ desgloseBTU.sol.toLocaleString() }} BTU</span>
                            </div>
                            <div v-if="desgloseBTU.habitacion !== 0" class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-700">
                                <span class="text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <font-awesome-icon icon="utensils" class="text-orange-500" />
                                    {{ form.habitacion === 'cocina' ? 'Cocina (calor extra)' : 'Tipo de habitación' }}
                                </span>
                                <span class="font-bold text-slate-900 dark:text-white">+{{ desgloseBTU.habitacion.toLocaleString() }} BTU</span>
                            </div>
                            <div class="flex justify-between items-center py-3 bg-slate-900 dark:bg-slate-800 rounded-xl px-4 mt-4">
                                <span class="text-sm font-black text-white">TOTAL REQUERIDO</span>
                                <span class="text-xl font-black text-[var(--color-primary)]">{{ desgloseBTU.total.toLocaleString() }} BTU/h</span>
                            </div>
                        </div>
                    </div>

                    <!-- Productos Recomendados -->
                    <div v-if="productosRecomendados.length > 0" class="mb-8">
                        <h4 class="text-lg font-black text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-blue-50 dark:bg-sky-900/20/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-lg">
                                <font-awesome-icon icon="snowflake" />
                            </span>
                            Equipos Mirage Recomendados
                        </h4>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div v-for="(producto, idx) in productosRecomendados" :key="idx"
                                 class="bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-700 rounded-2xl p-6 hover:border-[var(--color-primary)] dark:hover:border-[var(--color-primary)] transition-all hover:shadow-xl group">
                                <div class="aspect-square bg-gradient-to-br from-slate-50 to-slate-100 dark:from-gray-900 dark:to-gray-800 rounded-xl mb-4 flex items-center justify-center overflow-hidden">
                                    <font-awesome-icon icon="fan" class="text-6xl text-slate-300 dark:text-slate-600 group-hover:scale-110 transition-transform" />
                                </div>
                                <h5 class="font-black text-slate-900 dark:text-white text-base mb-2">{{ producto.nombre }}</h5>
                                <div v-if="producto.btu" class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-1 bg-blue-50 dark:bg-sky-900/20/30 text-blue-600 dark:text-blue-400 rounded-xl text-xs font-bold">{{ producto.btu.toLocaleString() }} BTU</span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">{{ producto.eficiencia }}</p>
                                <a :href="producto.link"
                                   class="block w-full py-3 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm text-center hover:bg-[var(--color-primary)]/90 transition-all hover:shadow-lg">
                                    Ver en Tienda →
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- PDF Download -->
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
                        class="block w-full py-4 mb-4 bg-slate-900 text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <font-awesome-icon icon="file-pdf" /> Descargar Reporte PDF
                    </a>

                    <button @click="resetSimulator" class="w-full py-4 border-2 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 rounded-xl font-bold text-sm hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] transition-all flex items-center justify-center gap-2">
                        <font-awesome-icon icon="redo" /> Calcular para otro espacio
                    </button>
                </div>

                <!-- Step 1: Espacio -->
                <div v-if="step === 1 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 transition-colors">¿Qué espacio climatizamos?</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6 transition-colors">Selecciona el tipo de habitación.</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <button v-for="h in [
                            {id: 'sala', label: 'Sala', icon: 'home'},
                            {id: 'recamara', label: 'Recámara', icon: 'user-circle'},
                            {id: 'cocina', label: 'Cocina', icon: 'toolbox'},
                            {id: 'oficina', label: 'Oficina', icon: 'laptop'}
                        ]" :key="h.id" 
                        @click="form.habitacion = h.id"
                        :class="['p-4 rounded-xl border-2 transition-all text-center', 
                                form.habitacion === h.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600']">
                            <span class="text-2xl block mb-2"><font-awesome-icon :icon="h.icon" /></span>
                            <span class="text-[10px] font-black uppercase tracking-wide text-slate-900 dark:text-white">{{ h.label }}</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="z in [
                            {id: 'centro', label: 'Centro', icon: 'building'},
                            {id: 'costa', label: 'Costa', icon: 'globe-americas'},
                            {id: 'desierto', label: 'Norte', icon: 'map-marker-alt'}
                        ]" :key="z.id" 
                        @click="form.zona = z.id"
                        :class="['p-3 rounded-xl border-2 transition-all text-center', 
                                form.zona === z.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 dark:hover:border-slate-600']">
                            <span class="text-xl block"><font-awesome-icon :icon="z.icon" /></span>
                            <span class="text-[9px] font-bold text-slate-500 dark:text-slate-400">{{ z.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Dimensiones -->
                <div v-if="step === 2 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 transition-colors">Dimensiones del espacio</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6 transition-colors">Indica el tamaño aproximado.</p>
                    
                    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 mb-4 transition-colors">
                        <div class="flex justify-between items-center mb-3">
                            <label for="simulator-area" class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">Área</label>
                            <span class="px-3 py-1 bg-[var(--color-primary)] text-white font-black rounded-xl text-sm transition-colors">{{ form.area }} m²</span>
                        </div>
                        <input id="simulator-area" type="range" v-model="form.area" min="5" max="100" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full appearance-none cursor-pointer accent-[var(--color-primary)]" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 transition-colors">
                            <label for="simulator-altura" class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase block mb-2">Altura (m)</label>
                            <input id="simulator-altura" type="number" v-model="form.altura" step="0.1" class="w-full p-2 bg-white dark:bg-slate-800 rounded-xl border-none font-bold text-lg text-center dark:text-white transition-colors" />
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-900/50 rounded-xl p-4 transition-colors">
                            <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase block mb-2">Personas</label>
                            <div class="flex items-center justify-center gap-4">
                                <button @click="form.personas > 1 && form.personas--" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold dark:text-white transition-colors">-</button>
                                <span class="text-xl font-black dark:text-white transition-colors">{{ form.personas }}</span>
                                <button @click="form.personas < 20 && form.personas++" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-bold dark:text-white transition-colors">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Construcción -->
                <div v-if="step === 3 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 transition-colors">Factores de calor</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6 transition-colors">Selecciona los que apliquen.</p>
                    
                    <div class="space-y-3 mb-4">
                        <button @click="form.techo_directo = !form.techo_directo" 
                                :class="['w-full p-4 rounded-xl border-2 transition-all text-left flex items-center gap-4', form.techo_directo ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600']">
                            <span class="text-2xl"><font-awesome-icon icon="home" /></span>
                            <div class="flex-1">
                                <p class="font-bold text-slate-900 dark:text-white text-sm transition-colors">Techo Directo</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 transition-colors">Sol directo al techo</p>
                            </div>
                            <div v-if="form.techo_directo" class="w-5 h-5 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-xs transition-colors">
                                <font-awesome-icon icon="check" />
                            </div>
                        </button>

                        <button @click="form.ventanales = !form.ventanales" 
                                :class="['w-full p-4 rounded-xl border-2 transition-all text-left flex items-center gap-4', form.ventanales ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600']">
                            <span class="text-2xl"><font-awesome-icon icon="columns" /></span>
                            <div class="flex-1">
                                <p class="font-bold text-slate-900 dark:text-white text-sm transition-colors">Ventanales Grandes</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 transition-colors">Áreas acristaladas</p>
                            </div>
                            <div v-if="form.ventanales" class="w-5 h-5 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-xs transition-colors">
                                <font-awesome-icon icon="check" />
                            </div>
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button v-for="ai in [{v:'bueno', t:'Buen Aislamiento'}, {v:'normal', t:'Normal'}, {v:'pobre', t:'Malo'}]" 
                                :key="ai.v" @click="form.aislamiento = ai.v"
                                :class="['flex-1 py-2 rounded-xl border-2 text-[10px] font-bold uppercase transition-all', form.aislamiento === ai.v ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-[var(--color-primary)]' : 'border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400']">
                            {{ ai.t }}
                        </button>
                    </div>
                </div>

                <!-- Step 4: Tecnología -->
                <div v-if="step === 4 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2 transition-colors">Preferencias técnicas</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6 transition-colors">Últimos detalles para tu cálculo.</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button @click="form.uso_horas = '8'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.uso_horas === '8' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 dark:hover:border-slate-600']">
                            <span class="text-xl block mb-1"><font-awesome-icon icon="clock" /></span>
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 transition-colors">8 Horas/día</span>
                        </button>
                        <button @click="form.uso_horas = '24'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.uso_horas === '24' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 dark:hover:border-slate-600']">
                            <span class="text-xl block mb-1"><font-awesome-icon icon="calendar-day" /></span>
                            <span class="text-[10px] font-bold text-slate-600 dark:text-slate-400 transition-colors">Todo el día</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <button @click="form.tecnologia = 'convencional'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center', form.tecnologia === 'convencional' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-100 dark:border-slate-700 dark:hover:border-slate-600']">
                            <span class="text-[10px] font-bold uppercase">Convencional</span>
                        </button>
                        <button @click="form.tecnologia = 'inverter'" 
                                :class="['p-4 rounded-xl border-2 transition-all text-center relative', form.tecnologia === 'inverter' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 dark:hover:border-slate-600']">
                            <span class="absolute -top-2 -right-2 px-2 py-0.5 bg-emerald-500 text-white text-[8px] font-bold rounded-full">AHORRA</span>
                            <span class="text-[10px] font-bold uppercase text-slate-900 dark:text-white transition-colors">Inverter</span>
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <button @click="form.voltaje = '110'" :class="['flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all', form.voltaje === '110' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400']">110V</button>
                        <button @click="form.voltaje = '220'" :class="['flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all', form.voltaje === '220' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]' : 'border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400']">220V</button>
                        <button @click="form.funcion = 'frio'" :class="['flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all', form.funcion === 'frio' ? 'border-blue-500 bg-blue-50 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400' : 'border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400']">Frío</button>
                        <button @click="form.funcion = 'dual'" :class="['flex-1 py-2 rounded-xl border-2 text-xs font-bold transition-all', form.funcion === 'dual' ? 'border-brand-500 bg-orange-50 dark:bg-brand-900/20 text-brand-600 dark:text-orange-400' : 'border-slate-100 dark:border-slate-700 text-slate-500 dark:text-slate-400']">Dual</button>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div v-if="!showResults && !isCalculating" class="px-8 pb-8 flex items-center justify-between border-t border-slate-50 dark:border-slate-700 pt-6 transition-colors">
                <button v-if="step > 1" @click="prevStep" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">
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
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showLeadModal = false"></div>
            
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-sm w-full p-8 animate-fade-in border border-slate-100 dark:border-slate-700 transition-colors">
                <button @click="showLeadModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                    <font-awesome-icon icon="times" />
                </button>

                <div v-if="!leadSent">
                    <div class="text-center mb-6">
                        <span class="text-4xl block mb-4"><font-awesome-icon icon="chart-pie" /></span>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">¡Un paso más!</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-2 transition-colors">Para ver tu <span class="font-bold text-[var(--color-primary)]">Reporte Personalizado</span>, déjanos tus datos.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="lead-nombre" class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1 transition-colors">Nombre Completo</label>
                            <input id="lead-nombre" v-model="leadForm.nombre" @input="leadForm.nombre = leadForm.nombre.toUpperCase()" type="text" placeholder="TU NOMBRE" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl font-medium dark:text-white transition-colors" />
                        </div>
                        <div>
                            <label for="lead-telefono" class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1 transition-colors">Teléfono (10 dígitos)</label>
                            <input id="lead-telefono" v-model="leadForm.telefono" type="tel" maxlength="10" placeholder="Ej: 6861234567" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl font-medium dark:text-white transition-colors" />
                        </div>
                        <div>
                            <label for="lead-email" class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1 transition-colors">Email <span class="text-slate-300 dark:text-slate-600">(opcional)</span></label>
                            <input id="lead-email" v-model="leadForm.email" type="email" placeholder="correo@ejemplo.com" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900 border-none rounded-xl font-medium dark:text-white transition-colors" />
                        </div>
                        
                        <button @click="submitLead" :disabled="isSubmitting" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all" :class="isSubmitting ? 'opacity-70' : ''">
                            {{ isSubmitting ? 'Procesando...' : 'Ver Mi Resultado' }}
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-6">
                    <span class="text-5xl block mb-4"><font-awesome-icon icon="check-circle" /></span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">¡Listo!</h3>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 transition-colors">Calculando tu reporte...</p>
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
