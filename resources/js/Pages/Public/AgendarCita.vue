<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { useDarkMode } from '@/Utils/useDarkMode';

const props = defineProps({
    empresa: Object,
    tiendas: Object,
    horarios: Object,
    diasDisponibles: Array,
    tiposServicio: Object,
    tiposEquipo: Object,
});

const { isDarkMode, toggleDarkMode, enableDarkMode } = useDarkMode(props.empresa);

onMounted(() => {
    enableDarkMode();
});

const cssVars = computed(() => {
    const primary = props.empresa?.color_principal || '#FF6B35';
    return {
        '--color-primary': primary,
        '--color-primary-soft': primary + '15',
        '--color-primary-medium': primary + '40',
    };
});

// Formulario con Inertia - Campos mínimos con valores predeterminados seguros
const form = useForm({
    nombre: '',
    telefono: '',
    email: 'contacto@climasdeldesierto.com',
    direccion_calle: 'Por definir',
    direccion_colonia: 'Por definir',
    direccion_cp: '',
    direccion_referencias: '',
    dias_preferidos: [], // Contendrá únicamente 1 fecha seleccionada
    horario_preferido: 'manana',
    tipo_servicio: 'mantenimiento',
    tipo_equipo: 'minisplit',
    origen_tienda: 'otro',
    numero_ticket_tienda: '',
    descripcion: 'Solicitud de cita rápida en línea.',
    acepta_terminos: true,
});

const formErrors = ref({});
const isSubmitting = ref(false);

// Validar formulario
const validate = () => {
    formErrors.value = {};
    
    if (!form.nombre.trim()) {
        formErrors.value.nombre = 'Tu nombre completo es requerido';
    }
    
    if (!form.telefono.trim()) {
        formErrors.value.telefono = 'Tu teléfono o WhatsApp es requerido';
    } else if (!/^\d{10}$/.test(form.telefono.replace(/\D/g, ''))) {
        formErrors.value.telefono = 'Ingresa un número de 10 dígitos válido';
    }
    
    if (form.dias_preferidos.length === 0) {
        formErrors.value.dias_preferidos = 'Por favor selecciona un día del calendario';
    }
    
    return Object.keys(formErrors.value).length === 0;
};

// Formatear teléfono
const formatPhone = (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) value = value.slice(0, 10);
    form.telefono = value;
};

// Seleccionar día del calendario
const selectDia = (dia) => {
    if (dia.ocupado) return;
    form.dias_preferidos = [dia.fecha];
};

// Enviar formulario
const submitForm = () => {
    if (!validate()) return;
    
    isSubmitting.value = true;
    form.post(route('agendar.store'), {
        onError: (err) => {
            isSubmitting.value = false;
            // Mapear errores del servidor si existen
            if (err.nombre) formErrors.value.nombre = err.nombre;
            if (err.telefono) formErrors.value.telefono = err.telefono;
            if (err.dias_preferidos) formErrors.value.dias_preferidos = err.dias_preferidos;
        },
        onSuccess: () => {
            isSubmitting.value = false;
        }
    });
};

// Agrupar días disponibles por mes (de forma segura contra desfasamientos de zona horaria)
const diasPorMes = computed(() => {
    const grupos = {};
    props.diasDisponibles?.forEach(dia => {
        const parts = dia.fecha.split('-');
        const fecha = new Date(parts[0], parts[1] - 1, parts[2]);
        const mesKey = `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}`;
        const mesNombre = fecha.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
        
        if (!grupos[mesKey]) {
            grupos[mesKey] = { nombre: mesNombre, dias: [] };
        }
        grupos[mesKey].dias.push({
            ...dia,
            diaSemana: fecha.toLocaleDateString('es-MX', { weekday: 'short' }),
            diaMes: fecha.getDate(),
        });
    });
    return grupos;
});
</script>

<template>
    <Head :title="`Agendar Cita - ${empresa?.nombre || 'Climas del Desierto'}`" />
    
    <div 
        class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-500 pb-12" 
        :style="cssVars"
    >
        <!-- Header -->
        <header class="sticky top-0 z-50 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors duration-300">
            <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img 
                        v-if="empresa?.logo"
                        :src="empresa.logo"
                        alt="Logo"
                        class="w-10 h-10 rounded-xl object-contain bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-1 shadow-md"
                    />
                    <div 
                        v-else
                        class="w-10 h-10 rounded-xl bg-[var(--color-primary)] flex items-center justify-center text-white font-extrabold text-lg shadow-lg shadow-[var(--color-primary-medium)]"
                    >
                        {{ empresa?.nombre?.charAt(0) || 'C' }}
                    </div>
                    <div>
                        <h1 class="font-extrabold text-lg tracking-tight text-slate-900 dark:text-white">{{ empresa?.nombre || 'Climas del Desierto' }}</h1>
                        <p class="text-xs font-semibold text-[var(--color-primary)]">Agendamiento Rápido en Línea</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        @click="toggleDarkMode" 
                        type="button" 
                        class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        title="Cambiar Tema"
                    >
                        <svg v-if="isDarkMode" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                    </button>
                    
                    <a 
                        v-if="empresa?.whatsapp"
                        :href="`https://wa.me/${empresa.whatsapp.replace(/\D/g, '')}?text=Hola,%20necesito%20ayuda%20para%20agendar%20una%20cita`"
                        target="_blank"
                        class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold shadow-lg shadow-emerald-500/20 transition-all duration-300"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        </svg>
                        <span>WhatsApp</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="max-w-6xl mx-auto px-4 mt-8">
            <!-- Glassmorphic Info Banner -->
            <div class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-[var(--color-primary-soft)] to-blue-500/10 border border-[var(--color-primary-medium)] flex items-start gap-4">
                <div class="text-3xl">📅</div>
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-white text-lg">Reserva de Cita Rápida</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
                        Elige el día de tu preferencia en el calendario, dinos tu nombre y teléfono, y nos comunicaremos contigo de inmediato para definir el horario de la visita y los detalles del servicio.
                    </p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Columna Izquierda: Formulario de Datos -->
                <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-850 p-6 rounded-3xl shadow-xl transition-all duration-300 hover:shadow-2xl">
                    <h3 class="font-bold text-xl mb-6 text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="text-lg">👤</span> Tus Datos de Contacto
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Input Nombre -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                                Nombre completo
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">👤</span>
                                <input 
                                    v-model="form.nombre"
                                    type="text"
                                    placeholder="Ej: Juan Pérez"
                                    class="w-full pl-10 pr-4 py-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:border-[var(--color-primary)] focus:ring-0 transition-all duration-300"
                                    :class="{ 'border-rose-500 focus:border-rose-500': formErrors.nombre }"
                                />
                            </div>
                            <p v-if="formErrors.nombre" class="text-rose-500 text-xs font-bold mt-1.5 flex items-center gap-1">
                                <span>⚠️</span> {{ formErrors.nombre }}
                            </p>
                        </div>
                        
                        <!-- Input Telefono -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                                Teléfono / WhatsApp
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">+52</span>
                                <input 
                                    :value="form.telefono"
                                    @input="formatPhone"
                                    type="tel"
                                    placeholder="Ej: 6622000000"
                                    maxlength="10"
                                    class="w-full pl-14 pr-4 py-3.5 rounded-2xl border-2 border-slate-200 dark:border-slate-800 dark:bg-slate-950 dark:text-white focus:border-[var(--color-primary)] focus:ring-0 transition-all duration-300"
                                    :class="{ 'border-rose-500 focus:border-rose-500': formErrors.telefono }"
                                />
                            </div>
                            <p v-if="formErrors.telefono" class="text-rose-500 text-xs font-bold mt-1.5 flex items-center gap-1">
                                <span>⚠️</span> {{ formErrors.telefono }}
                            </p>
                            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-2">
                                Te enviaremos el folio y confirmación de la cita a este número vía WhatsApp.
                            </p>
                        </div>

                        <!-- Horario Preferido -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2.5">
                                Horario de tu preferencia
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    v-for="(horario, key) in horarios"
                                    :key="key"
                                    @click="form.horario_preferido = key"
                                    type="button"
                                    :class="[
                                        'p-3.5 rounded-2xl border-2 text-left flex flex-col gap-1 transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]',
                                        form.horario_preferido === key 
                                            ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)] text-slate-900 dark:text-white' 
                                            : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-350'
                                    ]"
                                >
                                    <span class="text-2xl">{{ horario.emoji }}</span>
                                    <span class="text-sm font-black">{{ horario.nombre }}</span>
                                    <span class="text-[10px] opacity-75 font-semibold">{{ horario.inicio }} - {{ horario.fin }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Info de Resumen Opcional del Día Seleccionado -->
                        <div 
                            v-if="form.dias_preferidos.length > 0"
                            class="p-4 rounded-2xl bg-[var(--color-primary-soft)] border border-[var(--color-primary-medium)] transition-all duration-300 animate-pulse-slow"
                        >
                            <p class="text-xs font-bold text-[var(--color-primary)] uppercase tracking-wider">Fecha Seleccionada</p>
                            <p class="text-lg font-extrabold text-slate-800 dark:text-white mt-1 capitalize">
                                {{ new Date(form.dias_preferidos[0] + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                            </p>
                        </div>
                        
                        <!-- Botón de Envío -->
                        <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                            <button
                                @click="submitForm"
                                type="button"
                                :disabled="isSubmitting || form.processing"
                                class="w-full py-4 bg-[var(--color-primary)] hover:opacity-95 text-white font-extrabold rounded-2xl shadow-xl shadow-[var(--color-primary-medium)] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 hover:scale-[1.02]"
                            >
                                <svg v-if="isSubmitting || form.processing" class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ isSubmitting || form.processing ? 'Procesando Cita...' : '✓ Confirmar Mi Cita' }}</span>
                            </button>
                        </div>

                        <!-- Errores Generales del Servidor -->
                        <div v-if="form.errors.general" class="p-3 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs font-bold">
                            {{ form.errors.general }}
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Calendario -->
                <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-850 p-6 rounded-3xl shadow-xl transition-all duration-300">
                    <h3 class="font-bold text-xl mb-4 text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="text-lg">📅</span> Selecciona la Fecha
                    </h3>
                    
                    <p v-if="formErrors.dias_preferidos" class="text-rose-500 text-sm font-bold mb-4 flex items-center gap-1">
                        <span>⚠️</span> {{ formErrors.dias_preferidos }}
                    </p>

                    <!-- Renderizado de los meses -->
                    <div class="space-y-8">
                        <div v-for="(mes, key) in diasPorMes" :key="key" class="border border-slate-100 dark:border-slate-800/50 p-4 rounded-2xl">
                            <h4 class="text-sm font-black text-[var(--color-primary)] uppercase tracking-widest mb-4 border-b border-slate-100 dark:border-slate-800 pb-2 capitalize">
                                {{ mes.nombre }}
                            </h4>
                            
                            <!-- Grid del Calendario -->
                            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                                <button
                                    v-for="dia in mes.dias"
                                    :key="dia.fecha"
                                    @click="selectDia(dia)"
                                    type="button"
                                    :disabled="dia.ocupado"
                                    :class="[
                                        'relative p-4 rounded-2xl border-2 text-center transition-all duration-300 flex flex-col items-center justify-center gap-1 select-none',
                                        dia.ocupado
                                            ? 'bg-slate-50 dark:bg-slate-950 border-slate-200/50 dark:border-slate-850 opacity-40 cursor-not-allowed'
                                            : form.dias_preferidos.includes(dia.fecha)
                                                ? 'border-[var(--color-primary)] bg-[var(--color-primary)] text-white shadow-lg shadow-[var(--color-primary-medium)] scale-105 z-10'
                                                : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-[var(--color-primary)] hover:bg-[var(--color-primary-soft)] hover:scale-102'
                                    ]"
                                >
                                    <span class="text-2xl font-black">{{ dia.diaMes }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider opacity-75">{{ dia.diaSemana }}</span>
                                    
                                    <!-- Badge Ocupado o Checkmark -->
                                    <div class="mt-1">
                                        <span v-if="dia.ocupado" class="text-[9px] font-black uppercase text-red-600 bg-red-100 dark:bg-red-950/40 dark:text-red-400 px-2 py-0.5 rounded-full">
                                            Ocupado
                                        </span>
                                        <span v-else-if="form.dias_preferidos.includes(dia.fecha)" class="text-[10px] font-bold text-white">
                                            ✓ Elegido
                                        </span>
                                        <span v-else class="text-[9px] font-bold text-emerald-600 bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 px-2 py-0.5 rounded-full">
                                            Disponible
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center mt-12 text-slate-400 dark:text-slate-500 text-sm">
            <p>{{ empresa?.nombre || 'Climas del Desierto' }} © {{ new Date().getFullYear() }}</p>
            <p class="mt-1 flex items-center justify-center gap-2">
                <span>📞</span> <a :href="`tel:${empresa?.telefono}`" class="font-bold hover:text-[var(--color-primary)] transition-colors">{{ empresa?.telefono }}</a>
            </p>
        </footer>
    </div>
</template>

<style scoped>
.animate-pulse-slow {
    animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .85;
    }
}
</style>
