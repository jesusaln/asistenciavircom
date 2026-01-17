<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    misTareas: Array,
    tareasDisponibles: Array,
    stats: Object,
});

const formTomar = useForm({});

const tomarTarea = (id) => {
    if (confirm('¿Deseas asignarte esta tarea?')) {
        formTomar.post(route('admin.mantenimientos.tecnico.tomar', id));
    }
};

// --- Modal Completar ---
const tareaACompletar = ref(null);
const modalCompletarAbierto = ref(false);
const formCompletar = useForm({
    resultado: 'exitoso',
    notas_tecnico: '',
    // evidencia: [], // TODO: Implementar upload
});

const abrirCompletar = (tarea) => {
    tareaACompletar.value = tarea;
    formCompletar.reset();
    modalCompletarAbierto.value = true;
};

const cerrarCompletar = () => {
    modalCompletarAbierto.value = false;
    tareaACompletar.value = null;
    formCompletar.reset();
}

const enviarCompletar = () => {
    formCompletar.post(route('admin.mantenimientos.tecnico.completar', tareaACompletar.value.id), {
        onSuccess: () => {
             cerrarCompletar();
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const getPrioridadColor = (prioridad) => {
    return prioridad === 'alta' ? 'text-red-600 bg-red-50 border-red-200' : 
           prioridad === 'media' ? 'text-amber-600 bg-amber-50 border-amber-200' : 
           'text-blue-600 bg-blue-50 border-blue-200';
};
</script>

<template>
    <AppLayout title="Dashboard Técnico">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🔧 Mi Centro de Trabajo
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-indigo-500">
                        <div class="text-sm font-bold text-gray-500 uppercase">Tareas Pendientes</div>
                        <div class="text-3xl font-black text-indigo-700">{{ stats.pendientes }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-green-500">
                        <div class="text-sm font-bold text-gray-500 uppercase">Completadas Hoy</div>
                        <div class="text-3xl font-black text-green-700">{{ stats.completadas_hoy }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Columna Izquierda: Mis Tareas (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        <h3 class="text-lg font-bold text-gray-700 flex items-center">
                            <span class="mr-2">📋</span> Mis Tareas Asignadas
                        </h3>

                        <div v-if="misTareas.length === 0" class="bg-white rounded-lg p-8 text-center text-gray-400">
                            No tienes tareas pendientes. ¡Toma alguna de la lista disponible!
                        </div>

                        <div v-for="tarea in misTareas" :key="tarea.id" 
                            class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow overflow-hidden border border-gray-100">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span :class="['px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider', getPrioridadColor(tarea.mantenimiento.prioridad)]">
                                                {{ tarea.mantenimiento.prioridad || 'Normal' }}
                                            </span>
                                            <span class="text-xs text-gray-400 font-mono">#{{ tarea.id }}</span>
                                        </div>
                                        <h4 class="text-xl font-bold text-gray-800">{{ tarea.mantenimiento.nombre }}</h4>
                                        <p class="text-indigo-600 font-medium">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">Programada para:</div>
                                        <div :class="['font-bold', new Date(tarea.fecha_programada) < new Date() ? 'text-red-600' : 'text-gray-700']">
                                            {{ formatDate(tarea.fecha_programada) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm text-gray-600">
                                    <p v-if="tarea.mantenimiento.descripcion" class="mb-2">
                                        <strong>Instrucciones:</strong> {{ tarea.mantenimiento.descripcion }}
                                    </p>
                                    <p v-if="tarea.notas_reprogramacion" class="italic text-amber-600">
                                        ⚠️ {{ tarea.notas_reprogramacion }}
                                    </p>
                                    <p v-if="tarea.notas_tecnico" class="mt-2 text-gray-500 border-t pt-2">
                                        📝 {{ tarea.notas_tecnico }}
                                    </p>
                                </div>

                                <div class="flex justify-end gap-3">
                                    <!-- Botones de Acción -->
                                    <PrimaryButton @click="abrirCompletar(tarea)">
                                        ✅ Completar Tarea
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Disponibles (1/3) -->
                    <div class="lg:col-span-1 space-y-6">
                        <h3 class="text-lg font-bold text-gray-700 flex items-center">
                            <span class="mr-2">🤲</span> Disponibles
                        </h3>

                        <div v-if="tareasDisponibles.length === 0" class="bg-white rounded-lg p-6 text-center text-sm text-gray-400">
                            No hay tareas nuevas disponibles.
                        </div>

                        <div v-for="tarea in tareasDisponibles" :key="tarea.id" class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-300 hover:border-indigo-400 transition-colors">
                            <h5 class="font-bold text-gray-800">{{ tarea.mantenimiento.nombre }}</h5>
                            <p class="text-sm text-gray-600 mb-2">{{ tarea.mantenimiento.poliza?.cliente?.nombre_razon_social }}</p>
                            
                            <div class="flex justify-between items-center text-xs text-gray-500 mt-2">
                                <span>📅 {{ formatDate(tarea.fecha_programada) }}</span>
                                <button  @click="tomarTarea(tarea.id)" 
                                    class="text-indigo-600 font-bold hover:text-indigo-800 hover:underline">
                                    Tomar Tarea →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Completar -->
        <DialogModal :show="modalCompletarAbierto" @close="cerrarCompletar">
            <template #title>
                Completar Tarea
            </template>
            <template #content>
                <div class="mb-4">
                    <h3 class="font-bold text-lg mb-1">{{ tareaACompletar?.mantenimiento.nombre }}</h3>
                    <p class="text-sm text-gray-500">{{ tareaACompletar?.mantenimiento.poliza?.cliente?.nombre_razon_social }}</p>
                </div>

                <div class="mt-4">
                    <InputLabel value="Resultado del Mantenimiento" />
                    <select v-model="formCompletar.resultado" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1">
                        <option value="exitoso">✅ Exitoso</option>
                        <option value="con_observaciones">⚠️ Con Observaciones</option>
                        <option value="fallido">🔴 Fallido / No se pudo realizar</option>
                    </select>
                </div>

                <div class="mt-4">
                    <InputLabel value="Notas del Técnico / Bitácora" />
                    <textarea v-model="formCompletar.notas_tecnico" rows="4" 
                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1"
                        placeholder="Describe el trabajo realizado, anomalías encontradas, refacciones usadas..."></textarea>
                    <InputError :message="formCompletar.errors.notas_tecnico" />
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="cerrarCompletar" class="mr-3">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton @click="enviarCompletar" :disabled="formCompletar.processing">
                    Confirmar Terminación
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
