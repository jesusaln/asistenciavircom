<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ModalAsignarTecnico from '@/Components/ModalAsignarTecnico.vue';
import { Notyf } from 'notyf';

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

// Colores de estado
const estadoColores = {
    pendiente: { bg: 'bg-yellow-100 dark:bg-yellow-900/30', border: 'border-yellow-400 dark:border-yellow-600', text: 'text-yellow-800 dark:text-yellow-400' },
    pendiente_asignacion: { bg: 'bg-orange-100 dark:bg-orange-900/30', border: 'border-orange-400 dark:border-orange-600', text: 'text-orange-800 dark:text-orange-400' },
    programado: { bg: 'bg-blue-100 dark:bg-blue-900/30', border: 'border-blue-400 dark:border-blue-600', text: 'text-blue-800 dark:text-blue-400' },
    en_proceso: { bg: 'bg-indigo-100 dark:bg-indigo-900/30', border: 'border-indigo-400 dark:border-indigo-600', text: 'text-indigo-800 dark:text-indigo-400' },
    completado: { bg: 'bg-green-100 dark:bg-green-900/30', border: 'border-green-400 dark:border-green-600', text: 'text-green-800 dark:text-green-400' },
    cancelado: { bg: 'bg-red-100 dark:bg-red-900/30', border: 'border-red-400 dark:border-red-600', text: 'text-red-800 dark:text-red-400' },
    reprogramado: { bg: 'bg-purple-100 dark:bg-purple-900/30', border: 'border-purple-400 dark:border-purple-600', text: 'text-purple-800 dark:text-purple-400' },
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

// Citas filtradas por técnico
const citasFiltradas = computed(() => {
    if (filtroTecnico.value === 'all') return props.citas;
    return props.citas.filter(c => c.tecnico_id == filtroTecnico.value);
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
        const ampm = hour >= 12 ? 'p.m.' : 'a.m.';
        const hour12 = hour % 12 || 12;
        return `${hour12}:${minutes} ${ampm}`;
    }
    
    // Si es un datetime completo
    const date = new Date(datetime);
    if (isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true });
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors">Calendario de Citas</h1>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 transition-colors">
                            Gestiona las citas de los técnicos y asigna nuevas solicitudes
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Filtro por técnico -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 dark:text-gray-400 transition-colors">Técnico:</label>
                            <select 
                                v-model="filtroTecnico"
                                class="text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                            >
                                <option value="all">Todos</option>
                                <option v-for="tec in tecnicos" :key="tec.id" :value="tec.id">
                                    {{ tec.name }}
                                </option>
                            </select>
                        </div>
                        
                        <!-- Botón Hoy -->
                        <button
                            @click="goToToday"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            Hoy
                        </button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
                    
                    <!-- Sidebar: Citas Pendientes de Asignación -->
                    <div class="xl:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-6 transition-colors">
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-3">
                                <h2 class="text-white font-semibold flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Sin Asignar
                                    <span class="ml-auto bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">
                                        {{ citasPendientes.length }}
                                    </span>
                                </h2>
                            </div>
                            
                            <div class="max-h-[calc(100vh-300px)] overflow-y-auto">
                                <div v-if="citasPendientes.length === 0" class="p-6 text-center">
                                    <div class="text-5xl mb-3">🎉</div>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm transition-colors">¡Sin citas pendientes!</p>
                                </div>
                                
                                <div 
                                    v-else
                                    v-for="cita in citasPendientes" 
                                    :key="cita.id"
                                    class="p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer"
                                    @click="openAsignarModal(cita)"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate transition-colors">
                                                {{ cita.cliente?.nombre_razon_social || 'Cliente' }}
                                            </p>
                                            <div class="mt-1 space-y-1">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 transition-colors">
                                                    <span>📱</span>
                                                    {{ cita.cliente?.telefono }}
                                                </p>
                                                <p v-if="cita.origen_tienda" class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 transition-colors">
                                                    <span>🏪</span>
                                                    {{ tiendas[cita.origen_tienda] }}
                                                </p>
                                            </div>
                                            
                                            <!-- Días preferidos -->
                                            <div v-if="cita.dias_preferidos?.length" class="mt-2 flex flex-wrap gap-1">
                                                <span 
                                                    v-for="dia in cita.dias_preferidos.slice(0, 2)" 
                                                    :key="dia"
                                                    class="text-xs px-2 py-0.5 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded transition-colors"
                                                >
                                                    {{ new Date(dia + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric' }) }}
                                                </span>
                                                <span v-if="cita.dias_preferidos.length > 2" class="text-xs text-gray-400 dark:text-gray-500 transition-colors">
                                                    +{{ cita.dias_preferidos.length - 2 }}
                                                </span>
                                            </div>
                                            
                                            <!-- Horario preferido -->
                                            <div v-if="cita.horario_preferido && horarios[cita.horario_preferido]" class="mt-1">
                                                <span class="text-xs text-gray-500 dark:text-gray-400 transition-colors">
                                                    {{ horarios[cita.horario_preferido].emoji }} 
                                                    {{ horarios[cita.horario_preferido].nombre }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <svg class="w-5 h-5 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calendario Principal -->
                    <div class="xl:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
                            
                            <!-- Header del Calendario -->
                            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex items-center justify-between">
                                <button
                                    @click="changeMonth(-1)"
                                    class="p-2 hover:bg-white/10 rounded-lg transition-colors text-white"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                
                                <h2 class="text-xl font-bold text-white capitalize">
                                    {{ monthYearLabel }}
                                </h2>
                                
                                <button
                                    @click="changeMonth(1)"
                                    class="p-2 hover:bg-white/10 rounded-lg transition-colors text-white"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Leyenda de técnicos -->
                            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-3 transition-colors">
                                <span class="text-sm text-gray-500 dark:text-gray-400 transition-colors">Técnicos:</span>
                                <div 
                                    v-for="tec in tecnicos" 
                                    :key="tec.id"
                                    class="flex items-center gap-1.5"
                                >
                                    <span 
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: tec.color }"
                                    ></span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 transition-colors">{{ tec.name }}</span>
                                </div>
                            </div>
                            
                            <!-- Días de la semana -->
                            <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700">
                                <div 
                                    v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" 
                                    :key="day"
                                    class="px-2 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/30 transition-colors"
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
                                        'min-h-[120px] p-2 border-b border-r border-gray-100 dark:border-gray-700 transition-colors',
                                        !dayObj.isCurrentMonth ? 'bg-gray-50 dark:bg-gray-900/30' : 'bg-white dark:bg-gray-800',
                                        dayObj.isToday ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-inset ring-indigo-500' : '',
                                        dayObj.isPast && dayObj.isCurrentMonth ? 'bg-gray-50/50 dark:bg-gray-900/20' : '',
                                    ]"
                                >
                                    <!-- Número del día -->
                                    <div class="flex items-center justify-between mb-1">
                                        <span 
                                            :class="[
                                                'text-sm font-medium transition-colors',
                                                !dayObj.isCurrentMonth ? 'text-gray-400 dark:text-gray-600' :
                                                dayObj.isToday ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-gray-700 dark:text-gray-300'
                                            ]"
                                        >
                                            {{ dayObj.day }}
                                        </span>
                                        
                                        <!-- Contador de citas -->
                                        <span 
                                            v-if="getCitasForDay(dayObj.date).length > 0"
                                            class="text-xs px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-full transition-colors"
                                        >
                                            {{ getCitasForDay(dayObj.date).length }}
                                        </span>
                                    </div>
                                    
                                    <!-- Citas del día -->
                                    <div class="space-y-1 overflow-y-auto max-h-20">
                                        <div
                                            v-for="cita in getCitasForDay(dayObj.date).slice(0, 3)"
                                            :key="cita.id"
                                            @click.stop="openCitaDetails(cita)"
                                            :class="[
                                                'text-xs px-2 py-1 rounded cursor-pointer truncate border-l-2 transition-all hover:shadow-md',
                                                estadoColores[cita.estado]?.bg || 'bg-gray-100 dark:bg-gray-700',
                                                estadoColores[cita.estado]?.text || 'text-gray-700 dark:text-gray-300',
                                            ]"
                                            :style="{ borderLeftColor: getTecnicoColor(cita.tecnico_id) }"
                                            :title="`${cita.cliente?.nombre_razon_social} - ${formatTime(cita.fecha_hora)}`"
                                        >
                                            <span class="font-medium">{{ formatTime(cita.hora_confirmada || cita.fecha_hora) }}</span>
                                            <span class="ml-1">{{ cita.cliente?.nombre_razon_social?.split(' ')[0] }}</span>
                                        </div>
                                        
                                        <div 
                                            v-if="getCitasForDay(dayObj.date).length > 3"
                                            class="text-xs text-gray-500 dark:text-gray-400 pl-2 transition-colors"
                                        >
                                            +{{ getCitasForDay(dayObj.date).length - 3 }} más
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
            <div v-if="showCitaDetails" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="closeCitaDetails"></div>
                    
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md transform transition-all">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-bold text-white">Detalles de Cita</h3>
                                <button @click="closeCitaDetails" class="text-white/80 hover:text-white">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Contenido -->
                        <div v-if="selectedCita" class="p-6 space-y-4">
                            <!-- Estado -->
                            <div class="flex items-center gap-3">
                                <span 
                                    :class="[
                                        'px-3 py-1 rounded-full text-sm font-medium',
                                        estadoColores[selectedCita.estado]?.bg,
                                        estadoColores[selectedCita.estado]?.text,
                                    ]"
                                >
                                    {{ estadoLabels[selectedCita.estado] }}
                                </span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm transition-colors">
                                    Folio: {{ selectedCita.folio || '-' }}
                                </span>
                            </div>
                            
                            <!-- Cliente -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Cliente</p>
                                <p class="font-semibold text-gray-900 dark:text-white transition-colors">
                                    {{ selectedCita.cliente?.nombre_razon_social }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors">
                                    📱 {{ selectedCita.cliente?.telefono }}
                                </p>
                            </div>
                            
                            <!-- Fecha y Hora -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Fecha y Hora</p>
                                <p class="font-semibold text-gray-900 dark:text-white capitalize transition-colors">
                                    {{ formatDate(selectedCita.fecha_confirmada || selectedCita.fecha_hora?.split('T')[0]) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors">
                                    ⏰ {{ selectedCita.hora_confirmada || formatTime(selectedCita.fecha_hora) }}
                                </p>
                            </div>
                            
                            <!-- Técnico -->
                            <div v-if="selectedCita.tecnico" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Técnico asignado</p>
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: getTecnicoColor(selectedCita.tecnico_id) }"
                                    ></span>
                                    <span class="font-semibold text-gray-900 dark:text-white transition-colors">{{ selectedCita.tecnico.name }}</span>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div v-if="selectedCita.direccion_calle" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Dirección</p>
                                <p class="text-gray-900 dark:text-white transition-colors">{{ selectedCita.direccion_calle }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors">
                                    {{ selectedCita.direccion_colonia }}
                                    {{ selectedCita.direccion_cp ? `, C.P. ${selectedCita.direccion_cp}` : '' }}
                                </p>
                                <p v-if="selectedCita.direccion_referencias" class="text-xs text-gray-500 dark:text-gray-500 mt-1 italic transition-colors">
                                    "{{ selectedCita.direccion_referencias }}"
                                </p>
                            </div>
                            
                            <!-- Descripción -->
                            <div v-if="selectedCita.descripcion" class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 transition-colors">
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1 transition-colors">Descripción inicial</p>
                                <p class="text-gray-700 dark:text-gray-300 text-sm transition-colors">{{ selectedCita.descripcion }}</p>
                            </div>

                            <!-- Reporte de Cierre -->
                            <div v-if="selectedCita.trabajo_realizado || selectedCita.fotos_finales" class="space-y-4">
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest transition-colors">Reporte de Cierre</span>
                                    <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                                </div>

                                <div v-if="selectedCita.trabajo_realizado" class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800 transition-colors">
                                    <p class="text-xs font-bold text-green-700 dark:text-green-400 mb-1 uppercase transition-colors">Trabajo Realizado</p>
                                    <p class="text-gray-800 dark:text-gray-300 text-sm italic transition-colors">"{{ selectedCita.trabajo_realizado }}"</p>
                                </div>

                                <div v-if="selectedCita.fotos_finales?.length > 0">
                                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase transition-colors">Evidencias ({{ selectedCita.fotos_finales.length }})</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div v-for="(foto, idx) in selectedCita.fotos_finales" :key="idx" class="aspect-square rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm transition-transform hover:scale-105">
                                            <a :href="'/storage/' + foto" target="_blank">
                                                <img :src="'/storage/' + foto" class="w-full h-full object-cover">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 rounded-b-2xl flex items-center justify-between transition-colors">
                            <button
                                @click="closeCitaDetails"
                                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 font-medium transition-colors"
                            >
                                Cerrar
                            </button>
                            
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="selectedCita?.estado === 'pendiente_asignacion'"
                                    @click="closeCitaDetails(); openAsignarModal(selectedCita)"
                                    class="px-4 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 transition-colors"
                                >
                                    Asignar Técnico
                                </button>
                                <button
                                    @click="editarCita(selectedCita.id)"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors"
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
