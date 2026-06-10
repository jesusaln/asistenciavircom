<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ModalAsignarTecnico from '@/Components/ModalAsignarTecnico.vue';
import { Notyf } from 'notyf';
import Swal from '@/Utils/Swal';

const notyf = new Notyf({ position: { x: 'right', y: 'top' } });
const page = usePage();

onMounted(() => {
    const flash = page.props.flash;
    if (flash?.success) notyf.success(flash.success);
    if (flash?.error) notyf.error(flash.error);
});

const props = defineProps({
    tecnicos: { type: Array, required: true },
    citas: { type: Array, required: true },
    citasPendientes: { type: Array, required: true },
    mes: { type: Number, required: true },
    año: { type: Number, required: true },
    horarios: { type: Object, required: true },
    tiendas: { type: Object, required: true },
});

// Estado del calendario
const currentMonth = ref(new Date(props.año, props.mes - 1, 1));
const viewMode = ref('month'); // 'month' o 'week'
const filtroTecnico = ref('all');
const selectedDate = ref(null);
const selectedCita = ref(null);
const showModalAsignar = ref(false);
const showCitaDetails = ref(false);
const searchPendiente = ref('');
const filtroEstado = ref('all');

// Colores de estado
const estadoColores = {
    pendiente: { bg: 'bg-brand-50 dark:bg-amber-900/60', border: 'border-brand-400 dark:border-amber-500', text: 'text-brand-800 dark:text-amber-100' },
    pendiente_asignacion: { bg: 'bg-brand-100 dark:bg-orange-900/60', border: 'border-orange-400 dark:border-orange-500', text: 'text-orange-800 dark:text-orange-100' },
    programado: { bg: 'bg-blue-50 dark:bg-sky-800/60', border: 'border-blue-400 dark:border-sky-500', text: 'text-sky-800 dark:text-sky-100' },
    en_proceso: { bg: 'bg-sky-100 dark:bg-sky-700/60', border: 'border-sky-400 dark:border-sky-400', text: 'text-sky-800 dark:text-white' },
    completado: { bg: 'bg-emerald-100 dark:bg-emerald-900/40', border: 'border-emerald-400 dark:border-emerald-500', text: 'text-emerald-800 dark:text-emerald-100' },
    cancelado: { bg: 'bg-rose-50 dark:bg-rose-900/60', border: 'border-rose-400 dark:border-rose-500', text: 'text-rose-800 dark:text-rose-100' },
    reprogramado: { bg: 'bg-purple-100 dark:bg-purple-900/60', border: 'border-purple-400 dark:border-purple-500', text: 'text-purple-800 dark:text-purple-100' },
};

const estadoLabels = {
    pendiente: 'Pendiente',
    pendiente_asignacion: 'Sin asignar',
    programado: 'Programado',
    en_proceso: 'En proceso',
    completado: 'Completado',
    cancelado: 'Cancelado',
    reprogramado: 'Reprogramado',
};

// Computed: Días del mes
const daysInMonth = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const days = [];
    
    // Días del mes anterior para completar la primera semana
    const startDayOfWeek = firstDay.getDay();
    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const date = new Date(year, month, -i);
        days.push({ date, day: date.getDate(), isCurrentMonth: false, isPast: true });
    }
    
    // Días del mes actual
    for (let i = 1; i <= lastDay.getDate(); i++) {
        const date = new Date(year, month, i);
        days.push({
            date,
            day: i,
            isCurrentMonth: true,
            isToday: isSameDay(date, new Date()),
            isPast: date < new Date() && !isSameDay(date, new Date()),
        });
    }
    
    // Días del próximo mes para completar la última semana
    const remaining = 42 - days.length; // 6 filas * 7 días
    for (let i = 1; i <= remaining; i++) {
        const date = new Date(year, month + 1, i);
        days.push({ date, day: i, isCurrentMonth: false, isPast: false });
    }
    
    return days;
});

const monthYearLabel = computed(() => {
    return currentMonth.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
});

// Citas filtradas por técnico y estado
const citasFiltradas = computed(() => {
    let filtered = props.citas;
    if (filtroTecnico.value !== 'all') {
        filtered = filtered.filter(c => c.tecnico_id == filtroTecnico.value);
    }
    if (filtroEstado.value !== 'all') {
        filtered = filtered.filter(c => c.estado === filtroEstado.value);
    }
    return filtered;
});

// Citas pendientes filtradas por búsqueda
const citasPendientesFiltradas = computed(() => {
    if (!searchPendiente.value) return props.citasPendientes;
    const search = searchPendiente.value.toLowerCase();
    return props.citasPendientes.filter(c => 
        (c.cliente?.nombre_razon_social?.toLowerCase().includes(search)) ||
        (c.folio?.toLowerCase().includes(search))
    );
});

// Helpers
function isSameDay(date1, date2) {
    return date1.getFullYear() === date2.getFullYear() &&
           date1.getMonth() === date2.getMonth() &&
           date1.getDate() === date2.getDate();
}

function getCitasForDay(date) {
    // Crear string de fecha local (YYYY-MM-DD) para comparación
    const targetYear = date.getFullYear();
    const targetMonth = String(date.getMonth() + 1).padStart(2, '0');
    const targetDay = String(date.getDate()).padStart(2, '0');
    const targetDateStr = `${targetYear}-${targetMonth}-${targetDay}`;
    
    return citasFiltradas.value.filter(cita => {
        // Obtener la fecha de la cita (preferir fecha_confirmada si existe)
        let citaDateStr = '';
        
        if (cita.fecha_confirmada) {
            // fecha_confirmada viene como "2026-01-08T07:00:00.000000Z" o "2026-01-08"
            citaDateStr = cita.fecha_confirmada.substring(0, 10);
        } else if (cita.fecha_hora) {
            // fecha_hora viene como "2026-01-08T16:30:00.000000Z"
            citaDateStr = cita.fecha_hora.substring(0, 10);
        }
        
        return citaDateStr === targetDateStr;
    });
}


function getTecnicoColor(tecnicoId) {
    const tecnico = props.tecnicos.find(t => t.id === tecnicoId);
    return tecnico?.color || '#9CA3AF';
}

function formatTime(datetime) {
    if (!datetime) return '';
    
    // Si es solo hora (HH:MM:SS o HH:MM), formatearla directamente
    if (/^\d{2}:\d{2}(:\d{2})?$/.test(datetime)) {
        const [hours, minutes] = datetime.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    // Si es un datetime completo
    const date = new Date(datetime);
    if (isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function isAtrasada(c) {
  if (!c?.fecha_hora || !['pendiente', 'programado'].includes(c.estado)) return false
  const d = new Date(c.fecha_hora)
  const t = new Date(); t.setHours(0,0,0,0)
  return d < t
}


function formatDate(date) {
    if (!date) return '';
    return new Date(date + 'T12:00:00').toLocaleDateString('es-MX', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long' 
    });
}

// Navegación
function changeMonth(offset) {
    const newDate = new Date(currentMonth.value);
    newDate.setMonth(newDate.getMonth() + offset);
    
    router.get(route('citas.calendario'), {
        mes: newDate.getMonth() + 1,
        año: newDate.getFullYear(),
    }, { preserveState: true, only: ['citas', 'citasPendientes', 'mes', 'año'] });
}

function goToToday() {
    const today = new Date();
    router.get(route('citas.calendario'), {
        mes: today.getMonth() + 1,
        año: today.getFullYear(),
    }, { preserveState: true, only: ['citas', 'citasPendientes', 'mes', 'año'] });
}

// Acciones
function openAsignarModal(cita) {
    selectedCita.value = cita;
    showModalAsignar.value = true;
}

function openCitaDetails(cita) {
    selectedCita.value = cita;
    showCitaDetails.value = true;
}

function closeCitaDetails() {
    showCitaDetails.value = false;
    selectedCita.value = null;
}

function editarCita(citaId) {
    router.visit(route('citas.edit', citaId));
}

async function cancelarCita(citaId) {
    const result = await Swal.fire({
        title: '¿Cancelar Cita?',
        text: '¿Deseas cancelar esta cita? El horario asignado se liberará.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.post(route('citas.cancelar', citaId), {
            motivo: 'Cancelado desde el calendario.'
        }, {
            onSuccess: () => notyf.success('Cita cancelada exitosamente y horario liberado'),
            onError: (errors) => {
                const msg = errors.general || 'Error al intentar cancelar la cita';
                notyf.error(msg);
            }
        });
    }
}

// Watchers
watch(() => [props.mes, props.año], ([mes, año]) => {
    currentMonth.value = new Date(año, mes - 1, 1);
}, { immediate: true });
</script>

<template>
    <Head title="Calendario de Citas" />
    
    <AppLayout>
        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="mb-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white transition-colors">Calendario de Citas</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 transition-colors">
                            Gestiona las citas de los técnicos y asigna nuevas solicitudes
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Filtro por técnico -->
                        <div class="flex flex-wrap items-center gap-4">
                            <!-- Filtro por técnico -->
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-slate-500 dark:text-slate-400 transition-colors">Técnico:</label>
                                <select 
                                    v-model="filtroTecnico"
                                    class="text-sm border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                >
                                    <option value="all">Todos</option>
                                    <option v-for="tec in tecnicos" :key="tec.id" :value="tec.id">
                                        {{ tec.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Filtro por estado -->
                            <div class="flex items-center gap-2">
                                <label class="text-sm font-medium text-slate-500 dark:text-slate-400 transition-colors">Estado:</label>
                                <select 
                                    v-model="filtroEstado"
                                    class="text-sm border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                >
                                    <option value="all">Todos</option>
                                    <option v-for="(label, key) in estadoLabels" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Botón Hoy -->
                        <button
                            @click="goToToday"
                            class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            Hoy
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                    
                    <!-- Sidebar: Citas Pendientes de Asignación -->
                    <div class="xl:col-span-1">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden sticky top-6 transition-colors">
                            <div class="bg-gradient-to-r from-brand-500 to-brand-500 px-4 py-3">
                                <h2 class="text-white font-semibold flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Sin Asignar
                                    <span class="ml-auto bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">
                                        {{ citasPendientes.length }}
                                    </span>
                                </h2>
                            </div>
                            
                            <div class="p-3 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/20">
                                <div class="relative">
                                    <input 
                                        v-model="searchPendiente"
                                        type="text" 
                                        placeholder="Buscar cliente o folio..."
                                        class="w-full pl-9 pr-4 py-2 text-xs border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl focus:ring-brand-500 focus:border-brand-500 transition-colors"
                                    />
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <div class="max-h-[calc(100vh-350px)] overflow-y-auto custom-scrollbar">
                                <div v-if="citasPendientesFiltradas.length === 0" class="p-6 text-center">
                                    <div class="text-5xl mb-3">🔍</div>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm transition-colors">
                                        {{ searchPendiente ? 'No se encontraron resultados' : '¡Sin citas pendientes!' }}
                                    </p>
                                </div>
                                
                                <div 
                                    v-else
                                    v-for="cita in citasPendientesFiltradas" 
                                    :key="cita.id"
                                    class="p-4 border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer"
                                    @click="openAsignarModal(cita)"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-slate-900 dark:text-white truncate transition-colors">
                                                {{ cita.cliente?.nombre_razon_social || 'Cliente' }}
                                            </p>
                                            <div class="mt-1 space-y-1">
                                                <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 transition-colors">
                                                    <span>📱</span>
                                                    {{ cita.cliente?.telefono }}
                                                </p>
                                                <p v-if="cita.origen_tienda" class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 transition-colors">
                                                    <span>🏪</span>
                                                    {{ tiendas[cita.origen_tienda] }}
                                                </p>
                                            </div>
                                            
                                            <!-- Días preferidos -->
                                            <div v-if="cita.dias_preferidos?.length" class="mt-2 flex flex-wrap gap-1">
                                                <span 
                                                    v-for="dia in cita.dias_preferidos.slice(0, 2)" 
                                                    :key="dia"
                                                    class="text-xs px-2 py-0.5 bg-brand-100 dark:bg-brand-900/30 text-brand-700 dark:text-orange-400 rounded-xl transition-colors"
                                                >
                                                    {{ new Date(dia + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric' }) }}
                                                </span>
                                                <span v-if="cita.dias_preferidos.length > 2" class="text-xs text-slate-400 dark:text-slate-500 transition-colors">
                                                    +{{ cita.dias_preferidos.length - 2 }}
                                                </span>
                                            </div>
                                            
                                            <!-- Horario preferido -->
                                            <div v-if="cita.horario_preferido && horarios[cita.horario_preferido]" class="mt-1">
                                                <span class="text-xs text-slate-500 dark:text-slate-400 transition-colors">
                                                    {{ horarios[cita.horario_preferido].emoji }} 
                                                    {{ horarios[cita.horario_preferido].nombre }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                                            <button 
                                                v-if="!['completado', 'cancelado'].includes(cita.estado)"
                                                @click.stop="cancelarCita(cita.id)"
                                                class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors"
                                                title="Cancelar cita"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calendario Principal -->
                    <div class="xl:col-span-3">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
                            
                            <!-- Header del Calendario -->
                            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                                <button
                                    @click="changeMonth(-1)"
                                    class="p-2 hover:bg-white/10 rounded-xl transition-colors text-white"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                
                                <h2 class="text-xl font-bold text-white capitalize">
                                    {{ monthYearLabel }}
                                </h2>
                                
                                <button
                                    @click="changeMonth(1)"
                                    class="p-2 hover:bg-white/10 rounded-xl transition-colors text-white"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Leyenda de técnicos -->
                            <div class="px-6 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border-b border-slate-100 dark:border-slate-700 flex flex-wrap items-center gap-3 transition-colors">
                                <span class="text-sm text-slate-500 dark:text-slate-400 transition-colors">Técnicos:</span>
                                <div 
                                    v-for="tec in tecnicos" 
                                    :key="tec.id"
                                    class="flex items-center gap-1.5"
                                >
                                    <span 
                                        class="w-2 h-2 rounded-full"
                                        :style="{ backgroundColor: tec.color }"
                                    ></span>
                                    <span class="text-sm text-slate-700 dark:text-slate-200 transition-colors">{{ tec.name }}</span>
                                </div>
                            </div>
                            
                            <!-- Días de la semana -->
                            <div class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-700">
                                <div 
                                    v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" 
                                    :key="day"
                                    class="px-2 py-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400 bg-[var(--ui-surface)] dark:bg-slate-800/30 transition-colors"
                                >
                                    {{ day }}
                                </div>
                            </div>
                            
                            <!-- Grid del calendario -->
                            <div class="grid grid-cols-7">
                                <div
                                    v-for="(dayObj, index) in daysInMonth"
                                    :key="index"
                                    :class="[
                                        'min-h-[120px] p-2 border-b border-r border-slate-100 dark:border-slate-700 transition-colors',
                                        !dayObj.isCurrentMonth ? 'bg-slate-50 dark:bg-slate-800/30' : 'bg-white dark:bg-slate-800',
                                        dayObj.isToday ? 'bg-indigo-50 dark:bg-sky-900/20 ring-2 ring-inset ring-indigo-500' : '',
                                        dayObj.isPast && dayObj.isCurrentMonth ? 'bg-slate-50/50 dark:bg-slate-800/20' : '',
                                    ]"
                                >
                                    <!-- Número del día -->
                                    <div class="flex items-center justify-between mb-2 group/day">
                                        <div class="flex items-center gap-2">
                                            <span 
                                                :class="[
                                                    'text-base font-bold transition-colors',
                                                    !dayObj.isCurrentMonth ? 'text-slate-500 dark:text-slate-600' :
                                                    dayObj.isToday ? 'bg-indigo-600 text-white px-2 py-0.5 rounded-full' : 'text-slate-700 dark:text-slate-100'
                                                ]"
                                            >
                                                {{ dayObj.day }}
                                            </span>
                                            
                                            <!-- Botón de añadir rápida -->
                                            <button 
                                                v-if="dayObj.isCurrentMonth && !dayObj.isPast"
                                                @click.stop="router.visit(route('citas.create', { fecha: dayObj.date.toISOString().split('T')[0] }))"
                                                class="opacity-0 group-hover/day:opacity-100 p-1 text-slate-400 hover:text-brand-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-all"
                                                title="Programar cita para este día"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <!-- Contador de citas -->
                                        <span 
                                            v-if="getCitasForDay(dayObj.date).length > 0"
                                            class="text-sm font-bold px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-full transition-colors"
                                        >
                                            {{ getCitasForDay(dayObj.date).length }}
                                        </span>
                                    </div>
                                    
                                    <!-- Citas del día -->
                                    <div class="space-y-1.5 overflow-y-auto custom-scrollbar max-h-32">
                                        <div
                                            v-for="cita in getCitasForDay(dayObj.date).slice(0, 8)"
                                            :key="cita.id"
                                            @click.stop="openCitaDetails(cita)"
                                            :class="[
                                                'text-sm px-2 py-1.5 rounded-xl cursor-pointer truncate border-l-4 transition-all hover:brightness-110 shadow-sm hover:shadow-md mb-1.5',
                                                isAtrasada(cita) ? 'bg-rose-50 dark:bg-rose-950/40 border-rose-400 text-rose-800 dark:text-rose-200' : (estadoColores[cita.estado]?.bg || 'bg-slate-100 dark:bg-slate-700'),
                                                !isAtrasada(cita) && (estadoColores[cita.estado]?.text || 'text-slate-700 dark:text-slate-100'),
                                            ]"
                                            :style="{ borderLeftColor: isAtrasada(cita) ? '#e11d48' : getTecnicoColor(cita.tecnico_id) }"
                                            :title="`${cita.cliente?.nombre_razon_social} - ${formatTime(cita.fecha_hora)}`"
                                        >
                                            <div class="flex flex-col gap-0.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="font-black text-[9px] uppercase tracking-tighter opacity-80">
                                                        {{ formatTime(cita.hora_confirmada || cita.fecha_hora) }} - {{ formatTime(cita.fecha_hora_fin) }}
                                                    </span>
                                                    <span v-if="isAtrasada(cita)" class="text-[8px] font-black bg-rose-600 text-white px-1 rounded">ATRASADA</span>
                                                </div>
                                                <div class="font-bold truncate text-[10px] leading-tight mb-1 uppercase">
                                                    {{ cita.cliente?.nombre_razon_social }}
                                                </div>
                                                <!-- Técnico -->
                                                <div v-if="cita.tecnico" class="flex items-center gap-1 mt-0.5 py-0.5 px-1 bg-black/5 dark:bg-white/5 rounded border border-black/5 dark:border-white/5">
                                                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: getTecnicoColor(cita.tecnico_id) }"></span>
                                                    <span class="text-[8px] font-black uppercase truncate opacity-70">
                                                        {{ cita.tecnico?.name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div 
                                            v-if="getCitasForDay(dayObj.date).length > 5"
                                            class="text-xs font-bold text-brand-500 dark:text-brand-400 pl-2 transition-colors mt-1"
                                        >
                                            +{{ getCitasForDay(dayObj.date).length - 5 }} más...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </div>
        
        <!-- Modal: Asignar Técnico -->
        <ModalAsignarTecnico
            v-if="showModalAsignar"
            :cita="selectedCita"
            :tecnicos="tecnicos"
            :horarios="horarios"
            :tiendas="tiendas"
            :citasExistentes="citas"
            @close="showModalAsignar = false"
        />
        
        <!-- Modal: Detalles de Cita -->
        <Teleport to="body">
            <div v-if="showCitaDetails" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeCitaDetails"></div>
                    
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl w-full max-w-md transform transition-all">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-white">Detalles de Cita</h3>
                                <button @click="closeCitaDetails" class="text-white/80 hover:text-white">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Contenido -->
                        <div v-if="selectedCita" class="p-6 space-y-6">
                            <!-- Estado -->
                            <div class="flex items-center gap-2">
                                <span 
                                    :class="[
                                        'px-3 py-1 rounded-full text-sm font-medium',
                                        estadoColores[selectedCita.estado]?.bg,
                                        estadoColores[selectedCita.estado]?.text,
                                    ]"
                                >
                                    {{ estadoLabels[selectedCita.estado] }}
                                </span>
                                <span class="text-slate-500 dark:text-slate-400 text-sm transition-colors">
                                    Folio: {{ selectedCita.folio || '-' }}
                                </span>
                            </div>
                            
                            <!-- Cliente -->
                            <div class="bg-slate-50 dark:bg-black/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">Cliente</p>
                                <p class="font-semibold text-slate-900 dark:text-white transition-colors">
                                    {{ selectedCita.cliente?.nombre_razon_social }}
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 transition-colors">
                                    📱 {{ selectedCita.cliente?.telefono }}
                                </p>
                            </div>
                            
                            <!-- Fecha y Hora -->
                            <div class="bg-slate-50 dark:bg-black/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">Fecha y Hora</p>
                                <p class="font-semibold text-slate-900 dark:text-white capitalize transition-colors">
                                    {{ formatDate(selectedCita.fecha_confirmada || selectedCita.fecha_hora?.split('T')[0]) }}
                                </p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 transition-colors">
                                    ⏰ {{ selectedCita.hora_confirmada || formatTime(selectedCita.fecha_hora) }}
                                </p>
                            </div>
                            
                            <!-- Técnico -->
                            <div v-if="selectedCita.tecnico" class="bg-slate-50 dark:bg-black/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">Técnico asignado</p>
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="w-2 h-2 rounded-full"
                                        :style="{ backgroundColor: getTecnicoColor(selectedCita.tecnico_id) }"
                                    ></span>
                                    <span class="font-semibold text-slate-900 dark:text-white transition-colors">{{ selectedCita.tecnico.name }}</span>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div v-if="selectedCita.direccion_calle" class="bg-slate-50 dark:bg-black/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">Dirección</p>
                                <p class="text-slate-900 dark:text-white transition-colors">{{ selectedCita.direccion_calle }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400 transition-colors">
                                    {{ selectedCita.direccion_colonia }}
                                    {{ selectedCita.direccion_cp ? `, C.P. ${selectedCita.direccion_cp}` : '' }}
                                </p>
                                <p v-if="selectedCita.direccion_referencias" class="text-xs text-slate-500 dark:text-slate-500 mt-1 italic transition-colors">
                                    "{{ selectedCita.direccion_referencias }}"
                                </p>
                            </div>
                            
                            <!-- Descripción -->
                            <div v-if="selectedCita.descripcion" class="bg-slate-50 dark:bg-black/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">Descripción inicial</p>
                                <p class="text-slate-700 dark:text-slate-200 text-sm transition-colors">{{ selectedCita.descripcion }}</p>
                            </div>

                            <!-- Reporte de Cierre -->
                            <div v-if="selectedCita.trabajo_realizado || selectedCita.fotos_finales" class="space-y-6">
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide transition-colors">Reporte de Cierre</span>
                                    <div class="h-px bg-slate-200 dark:bg-slate-700 flex-1"></div>
                                </div>

                                <div v-if="selectedCita.trabajo_realizado" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800/30 transition-colors">
                                    <p class="text-xs font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 mb-1 uppercase transition-colors">Trabajo Realizado</p>
                                    <p class="text-slate-800 dark:text-slate-200 text-sm italic transition-colors">"{{ selectedCita.trabajo_realizado }}"</p>
                                </div>

                                <div v-if="selectedCita.fotos_finales?.length > 0">
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase transition-colors">Evidencias ({{ selectedCita.fotos_finales.length }})</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="(foto, idx) in selectedCita.fotos_finales" :key="idx" class="aspect-square rounded-xl overflow-hidden border border-slate-300 dark:border-slate-600 shadow-sm transition-transform hover:scale-105">
                                            <a :href="'/storage/' + foto" target="_blank">
                                                <img :src="'/storage/' + foto" class="w-full h-full object-cover">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-[var(--ui-surface)] dark:bg-black/50 rounded-b-2xl flex items-center justify-between transition-colors">
                            <button
                                @click="closeCitaDetails"
                                class="px-4 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 font-medium transition-colors"
                            >
                                Cerrar
                            </button>
                            
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="selectedCita?.estado === 'pendiente_asignacion'"
                                    @click="closeCitaDetails(); openAsignarModal(selectedCita)"
                                    class="px-4 py-2 bg-brand-500 text-white rounded-xl font-medium hover:bg-brand-600 transition-colors"
                                >
                                    Asignar Técnico
                                </button>
                                <button
                                    @click="editarCita(selectedCita.id)"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors"
                                >
                                    Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
        
    </AppLayout>
</template>
