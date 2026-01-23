<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    cita: { type: Object, required: true },
    tecnicos: { type: Array, required: true },
    horarios: { type: Object, required: true },
    tiendas: { type: Object, required: true },
    citasExistentes: { type: Array, default: () => [] }, // Citas existentes para verificar conflictos
});

const emit = defineEmits(['close']);

// Formulario con Inertia
const form = useForm({
    tecnico_id: '',
    fecha_confirmada: '',
    hora_confirmada: '',
});

// Horario seleccionado (para calcular el rango de hora válido)
const selectedHorario = computed(() => {
    if (!props.cita?.horario_preferido) return null;
    return props.horarios[props.cita.horario_preferido];
});

// Citas del técnico seleccionado en la fecha seleccionada
const citasTecnicoFecha = computed(() => {
    if (!form.tecnico_id || !form.fecha_confirmada) return [];
    
    return props.citasExistentes.filter(c => {
        const citaFecha = c.fecha_confirmada || (c.fecha_hora ? c.fecha_hora.split('T')[0] : null);
        return c.tecnico_id === form.tecnico_id && 
               citaFecha === form.fecha_confirmada &&
               c.estado !== 'cancelado';
    }).sort((a, b) => {
        const horaA = a.hora_confirmada || (a.fecha_hora ? a.fecha_hora.split('T')[1]?.substring(0, 5) : '00:00');
        const horaB = b.hora_confirmada || (b.fecha_hora ? b.fecha_hora.split('T')[1]?.substring(0, 5) : '00:00');
        return horaA.localeCompare(horaB);
    });
});

// Verificar si hay conflicto de horario
const tieneConflicto = computed(() => {
    if (!form.tecnico_id || !form.fecha_confirmada || !form.hora_confirmada) return false;
    
    return citasTecnicoFecha.value.some(c => {
        const citaHora = c.hora_confirmada || (c.fecha_hora ? c.fecha_hora.split('T')[1]?.substring(0, 5) : null);
        return citaHora === form.hora_confirmada;
    });
});

// Generar opciones de hora según el horario preferido
const horasDisponibles = computed(() => {
    if (!selectedHorario.value) {
        // Si no hay horario preferido, mostrar todo el día
        const horas = [];
        for (let h = 8; h <= 20; h++) {
            horas.push({ value: `${String(h).padStart(2, '0')}:00`, label: formatHora(h, 0) });
            if (h < 20) {
                horas.push({ value: `${String(h).padStart(2, '0')}:30`, label: formatHora(h, 30) });
            }
        }
        return horas;
    }
    
    // Generar horas dentro del rango del horario preferido
    const [horaInicio] = selectedHorario.value.inicio.split(':').map(Number);
    const [horaFin] = selectedHorario.value.fin.split(':').map(Number);
    
    const horas = [];
    for (let h = horaInicio; h < horaFin; h++) {
        horas.push({ value: `${String(h).padStart(2, '0')}:00`, label: formatHora(h, 0) });
        horas.push({ value: `${String(h).padStart(2, '0')}:30`, label: formatHora(h, 30) });
    }
    
    return horas;
});

function formatHora(hora, minutos) {
    const ampm = hora >= 12 ? 'PM' : 'AM';
    const hora12 = hora > 12 ? hora - 12 : (hora === 0 ? 12 : hora);
    return `${hora12}:${String(minutos).padStart(2, '0')} ${ampm}`;
}

function formatHoraDisplay(horaStr) {
    if (!horaStr) return '';
    const [h, m] = horaStr.split(':').map(Number);
    return formatHora(h, m);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr + 'T12:00:00').toLocaleDateString('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    });
}

// Pre-seleccionar el primer día preferido
watch(() => props.cita, (cita) => {
    if (cita?.dias_preferidos?.length > 0) {
        form.fecha_confirmada = cita.dias_preferidos[0];
    }
}, { immediate: true });

// Submit
const submit = () => {
    form.post(route('citas.asignar-tecnico', props.cita.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

const close = () => {
    emit('close');
};
</script>


<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="close"></div>
                
                <!-- Modal -->
                <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg transform transition-all">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-4 rounded-t-2xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-white">Asignar Técnico</h3>
                                <p class="text-white/80 text-sm">{{ cita?.cliente?.nombre_razon_social }}</p>
                            </div>
                            <button @click="close" class="text-white/80 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <form @submit.prevent="submit">
                        <div class="p-6 space-y-5">
                            
                            <!-- Info de la cita -->
                            <div class="bg-orange-50 rounded-xl p-4">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-orange-600 font-medium">📱 Teléfono</p>
                                        <p class="text-gray-700">{{ cita?.cliente?.telefono }}</p>
                                    </div>
                                    <div v-if="cita?.origen_tienda">
                                        <p class="text-orange-600 font-medium">🏪 Tienda</p>
                                        <p class="text-gray-700">{{ tiendas[cita.origen_tienda] }}</p>
                                    </div>
                                    <div v-if="cita?.horario_preferido" class="col-span-2">
                                        <p class="text-orange-600 font-medium">⏰ Horario preferido</p>
                                        <p class="text-gray-700">
                                            {{ horarios[cita.horario_preferido]?.emoji }}
                                            {{ horarios[cita.horario_preferido]?.nombre }}
                                            ({{ horarios[cita.horario_preferido]?.inicio }} - {{ horarios[cita.horario_preferido]?.fin }})
                                        </p>
                                    </div>
                                    <div v-if="cita?.direccion_calle" class="col-span-2">
                                        <p class="text-orange-600 font-medium">📍 Dirección</p>
                                        <p class="text-gray-700">
                                            {{ cita.direccion_calle }}, {{ cita.direccion_colonia }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Días preferidos del cliente -->
                            <div v-if="cita?.dias_preferidos?.length">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Días preferidos del cliente
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="dia in cita.dias_preferidos"
                                        :key="dia"
                                        type="button"
                                        @click="form.fecha_confirmada = dia"
                                        :class="[
                                            'px-3 py-2 rounded-lg text-sm font-medium border-2 transition-all',
                                            form.fecha_confirmada === dia
                                                ? 'border-orange-500 bg-orange-100 text-orange-800'
                                                : 'border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-700 hover:border-orange-300'
                                        ]"
                                    >
                                        {{ formatDate(dia) }}
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Fecha confirmada (si no hay días preferidos o quiere otra) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Fecha confirmada *
                                </label>
                                <input
                                    v-model="form.fecha_confirmada"
                                    type="date"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-800 rounded-xl focus:border-orange-500 focus:ring-0 transition-colors"
                                    :class="{ 'border-red-400': form.errors.fecha_confirmada }"
                                    required
                                />
                                <p v-if="form.errors.fecha_confirmada" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.fecha_confirmada }}
                                </p>
                            </div>
                            
                            <!-- Hora confirmada -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Hora confirmada *
                                    <span v-if="selectedHorario" class="text-gray-400 font-normal">
                                        (Horario preferido: {{ selectedHorario.inicio }} - {{ selectedHorario.fin }})
                                    </span>
                                </label>
                                <select
                                    v-model="form.hora_confirmada"
                                    class="w-full px-4 py-3 border-2 border-gray-200 dark:border-slate-800 rounded-xl focus:border-orange-500 focus:ring-0 transition-colors appearance-none bg-white dark:bg-slate-900"
                                    :class="{ 'border-red-400': form.errors.hora_confirmada }"
                                    required
                                >
                                    <option value="">Seleccionar hora...</option>
                                    <option v-for="hora in horasDisponibles" :key="hora.value" :value="hora.value">
                                        {{ hora.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.hora_confirmada" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.hora_confirmada }}
                                </p>
                            </div>
                            
                            <!-- Técnico -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Técnico a asignar *
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="tec in tecnicos"
                                        :key="tec.id"
                                        type="button"
                                        @click="form.tecnico_id = tec.id"
                                        :class="[
                                            'p-3 rounded-xl border-2 text-sm font-medium transition-all text-left',
                                            form.tecnico_id === tec.id
                                                ? 'border-orange-500 bg-orange-50'
                                                : 'border-gray-200 dark:border-slate-800 hover:border-orange-300'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="w-3 h-3 rounded-full flex-shrink-0"
                                                :style="{ backgroundColor: tec.color }"
                                            ></span>
                                            <span class="truncate">{{ tec.name }}</span>
                                        </div>
                                        <p v-if="tec.telefono" class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-5">
                                            📱 {{ tec.telefono }}
                                        </p>
                                    </button>
                                </div>
                                <p v-if="form.errors.tecnico_id" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.tecnico_id }}
                                </p>
                            </div>
                            
                            <!-- Citas del técnico en la fecha seleccionada -->
                            <div v-if="citasTecnicoFecha.length > 0" class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
                                <p class="font-medium text-blue-800 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Citas existentes ese día
                                </p>
                                <div class="space-y-2">
                                    <div 
                                        v-for="citaExis in citasTecnicoFecha" 
                                        :key="citaExis.id"
                                        class="flex items-center justify-between text-sm bg-white dark:bg-slate-900 rounded-lg px-3 py-2"
                                    >
                                        <span class="font-medium text-gray-700">
                                            {{ formatHoraDisplay(citaExis.hora_confirmada || (citaExis.fecha_hora ? citaExis.fecha_hora.split('T')[1]?.substring(0, 5) : '')) }}
                                        </span>
                                        <span class="text-gray-600 truncate ml-2">
                                            {{ citaExis.cliente?.nombre_razon_social || 'Cliente' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Advertencia de conflicto -->
                            <div v-if="tieneConflicto" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>
                                    ⚠️ <strong>Conflicto:</strong> El técnico ya tiene una cita a esa hora. Selecciona otra hora.
                                </span>
                            </div>
                            
                            <!-- Error general -->
                            <div v-if="form.errors.general" class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
                                {{ form.errors.general }}
                            </div>
                            
                        </div>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="close"
                                class="px-5 py-2.5 text-gray-600 font-medium hover:text-gray-800 transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.tecnico_id || !form.fecha_confirmada || !form.hora_confirmada"
                                class="px-6 py-2.5 bg-orange-500 text-white font-bold rounded-xl hover:bg-orange-600 transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ form.processing ? 'Asignando...' : 'Asignar Técnico' }}</span>
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>
