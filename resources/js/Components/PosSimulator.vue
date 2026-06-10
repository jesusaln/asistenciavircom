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
    giro: 'abarrotes',
    volumen_ventas: 'medio',
    sucursales: 1,
    necesita_computadora_completa: true,
    necesita_cpu: false,
    necesita_monitor: false,
    necesita_cajon_dinero: true,
    necesita_impresora_tickets: true,
    necesita_bascula: false,
    necesita_lector_codigos: true,
    necesita_etiquetadora: false,
    necesita_monitor_touch: false,
    tipo_conexion: 'wifi',
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

const calculoComplejidad = computed(() => {
    let score = 10; // Base
    
    if (form.value.giro === 'restaurante') score += 20;
    if (form.value.giro === 'farmacia') score += 15;
    if (form.value.volumen_ventas === 'alto') score += 30;
    if (form.value.sucursales > 1) score += 25;
    if (form.value.necesita_bascula) score += 10;
    if (form.value.necesita_monitor_touch) score += 15;
    
    return score;
});

const softwareRecomendado = computed(() => {
    if (form.value.giro === 'restaurante') return 'SoftRestaurant (Especializado)';
    return 'Eleventa (Versátil y fácil de usar)';
});

const recomendacion = computed(() => {
    const score = calculoComplejidad.value;
    const software = softwareRecomendado.value;
    
    if (score <= 35) return { 
        kit: 'Kit Emprendedor POS', 
        descripcion: `Ideal para pequeños negocios. Recomendamos el software ${software}.`,
        incluye: 'CPU Kit, Monitor 19", Impresora de Tickets, Cajón de dinero.'
    };
    if (score <= 65) return { 
        kit: 'Kit Profesional POS', 
        descripcion: `Configuración robusta para alto flujo. Incluye ${software}.`,
        incluye: 'Computadora Completa Pro, Impresora Térmica 80mm, Lector de Códigos, Cajón Reforzado.'
    };
    
    return { 
        kit: 'Kit Empresarial / Restaurante Premium', 
        descripcion: `Máxima capacidad operativa. Equipado con ${software}.`,
        incluye: 'Monitor Touch, Impresora de Tickets Industrial, Cajón de dinero, Lector de códigos, (Báscula y Etiquetadora Opcionales).'
    };
});

const submitLead = async () => {
    leadForm.value.nombre = leadForm.value.nombre.toUpperCase();

    if (!leadForm.value.nombre || !leadForm.value.telefono) {
        alert('Por favor completa tu nombre y teléfono.');
        return;
    }

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
            btu: calculoComplejidad.value, 
            recomendacion: recomendacion.value.kit,
            form: {
                ...form.value,
                software: softwareRecomendado.value,
                tipo_asesor: 'pos'
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
        alert('Ocurrió un error al procesar tu solicitud.');
    } finally {
        isSubmitting.value = false;
    }
};

const resetSimulator = () => {
    step.value = 1;
    showResults.value = false;
    leadForm.value = { nombre: '', telefono: '', email: '' };
};

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (props.empresa?.color_principal || '#FF6B35') + 'dd',
}));
</script>

<template>
    <section class="py-24 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 overflow-hidden transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid lg:grid-cols-12 gap-8 items-start">
                
                <!-- Panel Explicativo -->
                <div class="lg:col-span-4 lg:sticky lg:top-24">
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2.5rem] p-8 lg:p-10 text-white relative overflow-hidden" :style="cssVars">
                        <div class="absolute top-0 right-0 w-48 h-48 bg-[var(--color-primary)] rounded-full blur-[80px] opacity-20"></div>
                        
                        <div class="relative z-10">
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-3xl mb-6">
                                <font-awesome-icon icon="laptop" />
                            </div>
                            
                            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-[var(--color-primary)] mb-3 opacity-90">Asesor Inteligente</h2>
                            <h3 class="text-2xl lg:text-3xl font-black mb-4 leading-tight">Configurador de Punto de Venta</h3>
                            
                            <p class="text-slate-300 text-sm leading-relaxed mb-6">
                                Encuentra el <span class="text-white font-bold">sistema POS ideal</span> para tu negocio. Nuestro algoritmo analiza tus necesidades de operación para recomendarte el hardware y software perfecto.
                            </p>
                            
                            <div class="space-y-3 mb-8">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm"><font-awesome-icon icon="check" /></div>
                                    <span class="text-sm text-slate-300">Respaldo técnico <span class="text-white font-bold">24/7 incluido</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm"><font-awesome-icon icon="check" /></div>
                                    <span class="text-sm text-slate-300">Software especializado <span class="text-white font-bold">SoftRestaurant / Eleventa</span></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-sm"><font-awesome-icon icon="check" /></div>
                                    <span class="text-sm text-slate-300">Equipamiento de <span class="text-white font-bold">última generación</span></span>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-[var(--color-primary-soft)] rounded-xl">
                                <p class="text-[10px] font-black uppercase tracking-wide text-[var(--color-primary)] mb-1 opacity-90"><font-awesome-icon icon="bolt" class="mr-1" /> Solo 4 pasos</p>
                                <p class="text-xs text-slate-300">Personaliza tu hardware y software en segundos.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Simulador -->
                <div class="lg:col-span-8">
                    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-[0_30px_100px_rgba(0,0,0,0.08)] border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-500">
            
            <!-- Progress Bar -->
            <div class="px-8 pt-8" :style="cssVars">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-[10px] font-black uppercase tracking-wide text-slate-400">Paso {{ step }} de {{ totalSteps }}</span>
                    <span class="text-[10px] font-black uppercase tracking-wide text-blue-600">{{ Math.round((step/totalSteps)*100) }}%</span>
                </div>
                <div class="h-1.5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 transition-all duration-500 ease-out" :style="`width: ${(step / totalSteps) * 100}%`"></div>
                </div>
            </div>

            <div class="p-8 relative min-h-[420px]">
                
                <!-- Analyzing -->
                <div v-if="isCalculating" class="absolute inset-0 z-50 bg-white/80 dark:bg-slate-900/95 backdrop-blur-sm flex flex-col items-center justify-center p-8 text-center animate-fade-in" :style="cssVars">
                    <div class="relative w-20 h-20 mb-8">
                        <div class="absolute inset-0 border-4 border-slate-50 dark:border-slate-700 rounded-full"></div>
                        <div class="absolute inset-0 border-4 border-t-[var(--color-primary)] rounded-full animate-spin"></div>
                        <div class="absolute inset-0 flex items-center justify-center text-2xl"><font-awesome-icon icon="cogs" /></div>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-3">Configurando tu solución POS...</h3>
                    <p class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.3em] animate-pulse">ANALIZANDO REQUERIMIENTOS</p>
                </div>

                <!-- Results View -->
                <div v-if="showResults && !isCalculating" class="text-center animate-fade-in" :style="cssVars">
                    <div class="w-20 h-20 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                        <font-awesome-icon icon="bullseye" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2">¡Tu Sistema Ideal!</h3>
                    
                    <div class="bg-slate-900 rounded-2xl p-6 text-white my-6">
                        <p class="text-[9px] font-black uppercase tracking-[0.4em] text-[var(--color-primary)] mb-2 text-center opacity-90">Configuración Recomendada</p>
                        <h4 class="text-2xl md:text-3xl font-black mb-2">{{ recomendacion.kit }}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">{{ recomendacion.descripcion }}</p>
                    </div>

                    <div class="bg-[var(--color-primary-soft)] border border-[var(--color-primary)]/10 rounded-xl p-4 mb-6 text-left">
                        <p class="text-[9px] font-black text-[var(--color-primary)] uppercase tracking-wide mb-2 opacity-90">Resumen de Equipamiento:</p>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ recomendacion.incluye }}</p>
                    </div>

                    <button @click="resetSimulator" class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">
                        ← Regresar al asesor
                    </button>
                </div>

                <!-- Step 1: Giro -->
                <div v-if="step === 1 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">¿Cuál es tu giro comercial?</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6">El software y equipo se adaptarán a tu modelo de negocio.</p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button v-for="g in [
                            {id: 'abarrotes', label: 'Tienda / Abarrotes', icon: 'building'},
                            {id: 'restaurante', label: 'Comida / Café', icon: 'shopping-bag'},
                            {id: 'retail', label: 'Ropa / Calzado', icon: 'shopping-bag'},
                            {id: 'farmacia', label: 'Farmacia', icon: 'shield-halved'},
                            {id: 'ferreteria', label: 'Ferretería', icon: 'tools'},
                            {id: 'otro', label: 'Otro Servicio', icon: 'shopping-cart'}
                        ]" :key="g.id" 
                        @click="form.giro = g.id"
                        :style="form.giro === g.id ? cssVars : {}"
                        :class="['p-4 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center', 
                                form.giro === g.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] dark:bg-[var(--color-primary)]/20' : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600']">
                            <span class="text-2xl mb-2"><font-awesome-icon :icon="g.icon" /></span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-900 dark:text-slate-300 leading-tight transition-colors">{{ g.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Operación -->
                <div v-if="step === 2 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Flujo de operación</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6">Ayúdanos a entender el volumen de tu negocio.</p>
                    
                    <div class="space-y-4 mb-8">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-3 block">Volumen de ventas diarias</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="v in [{id:'bajo', l:'Bajo'}, {id:'medio', l:'Medio'}, {id:'alto', l:'Alto'}]" :key="v.id"
                                    @click="form.volumen_ventas = v.id"
                                    :style="form.volumen_ventas === v.id ? cssVars : {}"
                                    :class="['py-3 rounded-xl border-2 font-bold text-xs uppercase', 
                                            form.volumen_ventas === v.id ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] dark:bg-[var(--color-primary)]/20 text-[var(--color-primary)]' : 'border-slate-100 dark:border-slate-700 text-slate-400 dark:text-slate-500']">
                                    {{ v.l }}
                                </button>
                            </div>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-700/50 rounded-2xl p-6 transition-colors" :style="cssVars">
                            <div class="flex justify-between items-center mb-4">
                                <label class="text-xs font-black text-slate-600 dark:text-slate-300 uppercase">Cajas / Estaciones de Cobro</label>
                                <span class="bg-[var(--color-primary)] text-white px-3 py-1 rounded-xl font-black text-sm">{{ form.sucursales }}</span>
                            </div>
                            <input type="range" v-model="form.sucursales" min="1" max="10" class="w-full h-2 bg-slate-200 dark:bg-slate-600 rounded-full appearance-none cursor-pointer accent-[var(--color-primary)]" />
                        </div>
                    </div>
                </div>

                <!-- Step 3: Hardware -->
                <div v-if="step === 3 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Accesorios y Periféricos</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6">Selecciona el equipamiento necesario para tu punto de venta.</p>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button v-for="h in [
                            {id: 'necesita_computadora_completa', label: 'Computadora Completa', icon: 'desktop'},
                            {id: 'necesita_cpu', label: 'Solo CPU', icon: 'server'},
                            {id: 'necesita_monitor', label: 'Monitor', icon: 'desktop'},
                            {id: 'necesita_cajon_dinero', label: 'Cajón de Dinero', icon: 'dollar-sign'},
                            {id: 'necesita_impresora_tickets', label: 'Impresora Tickets', icon: 'print'},
                            {id: 'necesita_bascula', label: 'Báscula', icon: 'balance-scale'},
                            {id: 'necesita_lector_codigos', label: 'Lector de Códigos', icon: 'barcode'},
                            {id: 'necesita_etiquetadora', label: 'Etiquetadora', icon: 'tag'},
                            {id: 'necesita_monitor_touch', label: 'Monitor Touch', icon: 'mobile-alt'}
                        ]" :key="h.id" 
                        @click="form[h.id] = !form[h.id]"
                        :style="form[h.id] ? cssVars : {}"
                        :class="['p-3 rounded-xl border-2 transition-all text-center flex flex-col items-center justify-center relative', 
                                form[h.id] ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] dark:bg-[var(--color-primary)]/20' : 'border-slate-100 dark:border-slate-700']">
                            <span class="text-2xl mb-1"><font-awesome-icon :icon="h.icon" /></span>
                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-900 dark:text-slate-300 leading-tight transition-colors">{{ h.label }}</span>
                            <div v-if="form[h.id]" class="absolute top-1 right-1 w-4 h-4 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white text-[8px]"><font-awesome-icon icon="check" /></div>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Resumen Final -->
                <div v-if="step === 4 && !showResults && !isCalculating" class="animate-fade-in">
                    <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">Propuesta Técnica</h3>
                    <p class="text-slate-400 dark:text-slate-500 text-sm mb-6">Resumen de tu configuración personalizada.</p>
                    
                    <div class="bg-slate-50 dark:bg-slate-700/50 rounded-[1.5rem] p-6 space-y-4 transition-colors">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500">Software Sugerido:</span>
                            <span class="font-black text-[var(--color-primary)] uppercase opacity-90">{{ softwareRecomendado }}</span>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-500 dark:text-slate-400">Giro Comercial:</span>
                            <span class="font-black text-slate-900 dark:text-white uppercase transition-colors">{{ form.giro }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                             <span v-if="form.necesita_computadora_completa" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl text-[8px] font-black dark:text-slate-300">PC COMPLETA</span>
                             <span v-if="form.necesita_cajon_dinero" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl text-[8px] font-black dark:text-slate-300">CAJÓN</span>
                             <span v-if="form.necesita_impresora_tickets" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl text-[8px] font-black dark:text-slate-300">IMPRESORA TICKETS</span>
                             <span v-if="form.necesita_bascula" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl text-[8px] font-black dark:text-slate-300">BÁSCULA</span>
                             <span v-if="form.necesita_monitor_touch" class="px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl text-[8px] font-black dark:text-slate-300">TOUCH</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div v-if="!showResults && !isCalculating" class="px-8 pb-8 flex items-center justify-between border-t border-slate-50 dark:border-slate-700 pt-6 transition-colors" :style="cssVars">
                <button v-if="step > 1" @click="prevStep" class="text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
                    ← Anterior
                </button>
                <div v-else></div>

                <button @click="nextStep" class="px-8 py-3 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all">
                    {{ step === totalSteps ? '¡Obtener Propuesta!' : 'Siguiente →' }}
                </button>
            </div>
        </div>
    </div>
</div>

        <!-- Lead Modal -->
        <div v-if="showLeadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showLeadModal = false"></div>
            
            <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-sm w-full p-8 animate-fade-in transition-colors">
                <button @click="showLeadModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><font-awesome-icon icon="times" /></button>

                <div v-if="!leadSent">
                    <div class="text-center mb-6">
                        <span class="text-4xl block mb-4"><font-awesome-icon icon="wand-magic-sparkles" /></span>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">¡Ya casi terminamos!</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-2 transition-colors">Introduce tus datos para recibir tu <span class="font-bold text-[var(--color-primary)]">Propuesta Técnica</span> preparada por un experto.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nombre Completo</label>
                            <input v-model="leadForm.nombre" type="text" placeholder="TU NOMBRE" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 dark:text-white border-none rounded-xl font-medium focus:ring-2 focus:ring-[var(--color-primary)] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Teléfono (WhatsApp)</label>
                            <input v-model="leadForm.telefono" type="tel" maxlength="10" placeholder="686XXXXXXX" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 dark:text-white border-none rounded-xl font-medium focus:ring-2 focus:ring-[var(--color-primary)] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Email <span class="text-slate-300 dark:text-slate-500">(opcional)</span></label>
                            <input v-model="leadForm.email" type="email" placeholder="hola@empresa.com" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-700 dark:text-white border-none rounded-xl font-medium focus:ring-2 focus:ring-[var(--color-primary)] transition-colors" />
                        </div>
                        
                        <button @click="submitLead" :disabled="isSubmitting" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm hover:shadow-lg transition-all" :class="isSubmitting ? 'opacity-70' : ''">
                            {{ isSubmitting ? 'Procesando...' : 'Ver Mi Propuesta POS' }}
                        </button>
                    </div>
                </div>

                <div v-else class="text-center py-6">
                    <span class="text-5xl block mb-4"><font-awesome-icon icon="paper-plane" /></span>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">¡Excelente!</h3>
                    <p class="text-slate-500 dark:text-slate-400 mt-2 transition-colors">Generando tu configuración ideal...</p>
                </div>
            </div>
        </div>

        </div>
    
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
    height: 18px;
    width: 18px;
    border-radius: 50%;
    background: white;
    border: 3px solid #2563eb;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>
