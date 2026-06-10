<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
        <div class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click="close"></div>
                
                <!-- Modal -->
                <div class="relative bg-[var(--ui-surface)] text-[var(--ui-text)] rounded-3xl shadow-[var(--ui-shadow)] w-full max-w-lg transform transition-all border border-[var(--ui-border)]">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-brand-500 to-brand-500 px-6 py-4 rounded-t-3xl">
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
                            <div class="bg-orange-50/80 dark:bg-brand-900/20 rounded-2xl p-4 border border-orange-100/70 dark:border-orange-900/40">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-brand-600 font-medium">📱 Teléfono</p>
                                        <p class="text-[var(--ui-text-muted)]">{{ cita?.cliente?.telefono }}</p>
                                    </div>
                                    <div v-if="cita?.origen_tienda">
                                        <p class="text-brand-600 font-medium">🏪 Tienda</p>
                                        <p class="text-[var(--ui-text-muted)]">{{ tiendas[cita.origen_tienda] }}</p>
                                    </div>
                                    <div v-if="cita?.horario_preferido" class="col-span-2">
                                        <p class="text-brand-600 font-medium">⏰ Horario preferido</p>
                                        <p class="text-[var(--ui-text-muted)]">
                                            {{ horarios[cita.horario_preferido]?.emoji }}
                                            {{ horarios[cita.horario_preferido]?.nombre }}
                                            ({{ horarios[cita.horario_preferido]?.inicio }} - {{ horarios[cita.horario_preferido]?.fin }})
                                        </p>
                                    </div>
                                    <div v-if="cita?.direccion_calle" class="col-span-2">
                                        <p class="text-brand-600 font-medium">📍 Dirección</p>
                                        <p class="text-[var(--ui-text-muted)]">
                                            {{ cita.direccion_calle }}, {{ cita.direccion_colonia }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Días preferidos del cliente -->
                            <div v-if="cita?.dias_preferidos?.length">
                                <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">
                                    Días preferidos del cliente
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="dia in cita.dias_preferidos"
                                        :key="dia"
                                        type="button"
                                        @click="form.fecha_confirmada = dia"
                                        :class="[
                                            'px-3 py-2 rounded-xl text-sm font-medium border-2 transition-all',
                                            form.fecha_confirmada === dia
                                                ? 'border-brand-500 bg-brand-100 text-amber-800'
                                                : 'border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-text-muted)] hover:border-orange-300'
                                        ]"
                                    >
                                        {{ formatDate(dia) }}
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Fecha confirmada (si no hay días preferidos o quiere otra) -->
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">
                                    Fecha confirmada *
                                </label>
                                <input
                                    v-model="form.fecha_confirmada"
                                    type="date"
                                    :min="new Date().toISOString().split('T')[0]"
                                    class="w-full px-4 py-3 border-2 border-[var(--ui-border)] rounded-xl focus:border-brand-500 focus:ring-0 transition-colors bg-[var(--ui-surface)]"
                                    :class="{ 'border-rose-400': form.errors.fecha_confirmada }"
                                    required
                                />
                                <p v-if="form.errors.fecha_confirmada" class="text-rose-500 text-xs mt-1">
                                    {{ form.errors.fecha_confirmada }}
                                </p>
                            </div>
                            
                            <!-- Hora confirmada -->
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">
                                    Hora confirmada *
                                    <span v-if="selectedHorario" class="text-[var(--ui-text-soft)] font-normal">
                                        (Horario preferido: {{ selectedHorario.inicio }} - {{ selectedHorario.fin }})
                                    </span>
                                </label>
                                <select
                                    v-model="form.hora_confirmada"
                                    class="w-full px-4 py-3 border-2 border-[var(--ui-border)] rounded-xl focus:border-brand-500 focus:ring-0 transition-colors appearance-none bg-[var(--ui-surface)]"
                                    :class="{ 'border-rose-400': form.errors.hora_confirmada }"
                                    required
                                >
                                    <option value="">Seleccionar hora...</option>
                                    <option v-for="hora in horasDisponibles" :key="hora.value" :value="hora.value">
                                        {{ hora.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.hora_confirmada" class="text-rose-500 text-xs mt-1">
                                    {{ form.errors.hora_confirmada }}
                                </p>
                            </div>
                            
                            <!-- Técnico -->
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">
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
                                                ? 'border-brand-500 bg-orange-50'
                                                : 'border-[var(--ui-border)] hover:border-orange-300'
                                        ]"
                                    >
                                        <div class="flex items-center gap-2">
                                            <span 
                                                class="w-3 h-3 rounded-full flex-shrink-0"
                                                :style="{ backgroundColor: tec.color }"
                                            ></span>
                                            <span class="truncate">{{ tec.name }}</span>
                                        </div>
                                        <p v-if="tec.telefono" class="text-xs text-[var(--ui-text-soft)] mt-1 ml-5">
                                            📱 {{ tec.telefono }}
                                        </p>
                                    </button>
                                </div>
                                <p v-if="form.errors.tecnico_id" class="text-rose-500 text-xs mt-1">
                                    {{ form.errors.tecnico_id }}
                                </p>
                            </div>
                            
                            <!-- Citas del técnico en la fecha seleccionada -->
                            <div v-if="citasTecnicoFecha.length > 0" class="p-4 bg-blue-50/80 dark:bg-sky-900/20 border border-blue-200 dark:border-blue-900/40 rounded-2xl">
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
                                        class="flex items-center justify-between text-sm bg-[var(--ui-surface)] rounded-xl px-3 py-2 border border-[var(--ui-border)]"
                                    >
                                        <span class="font-medium text-[var(--ui-text-muted)]">
                                            {{ formatHoraDisplay(citaExis.hora_confirmada || (citaExis.fecha_hora ? citaExis.fecha_hora.split('T')[1]?.substring(0, 5) : '')) }}
                                        </span>
                                        <span class="text-[var(--ui-text-soft)] truncate ml-2">
                                            {{ citaExis.cliente?.nombre_razon_social || 'Cliente' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Advertencia de conflicto -->
                            <div v-if="tieneConflicto" class="p-3 bg-rose-50/80 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-900/40 rounded-2xl text-rose-700 dark:text-rose-300 text-sm flex items-center gap-2">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span>
                                    ⚠️ <strong>Conflicto:</strong> El técnico ya tiene una cita a esa hora. Selecciona otra hora.
                                </span>
                            </div>
                            
                            <!-- Error general -->
                            <div v-if="form.errors.general" class="p-3 bg-rose-50/80 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-900/40 rounded-2xl text-rose-600 dark:text-rose-300 text-sm">
                                {{ form.errors.general }}
                            </div>
                            
                        </div>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-[var(--ui-border)] bg-[var(--ui-surface-alt)] rounded-b-3xl flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="close"
                                class="px-5 py-2.5 text-[var(--ui-text-muted)] font-medium hover:text-[var(--ui-text)] transition-colors"
                            >
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || !form.tecnico_id || !form.fecha_confirmada || !form.hora_confirmada"
                                class="px-6 py-2.5 bg-brand-500 text-white font-bold rounded-xl hover:bg-brand-600 transition-colors shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
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
