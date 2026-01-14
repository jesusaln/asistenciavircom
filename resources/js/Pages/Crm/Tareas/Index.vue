<template>
    <Head title="CRM - Mis Tareas" />

    <div class="container mx-auto px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shadow-lg">
                            <FontAwesomeIcon :icon="['fas', 'tasks']" class="h-6 w-6" />
                        </span>
                        Mis Tareas
                    </h1>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <button @click="showModalNueva = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600">
                        <FontAwesomeIcon :icon="['fas', 'plus']" />
                        Nueva Tarea
                    </button>
                    <Link href="/crm" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        <FontAwesomeIcon :icon="['fas', 'chart-line']" />
                        Pipeline
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div @click="filtrar('pendientes')" :class="filtros.filtro === 'pendientes' ? 'ring-2 ring-amber-500' : ''" class="bg-white p-4 rounded-xl shadow-sm border cursor-pointer hover:border-amber-300 transition-colors">
                <p class="text-sm text-gray-500">Pendientes</p>
                <p class="text-2xl font-bold text-gray-900">{{ stats.pendientes }}</p>
            </div>
            <div @click="filtrar('hoy')" :class="filtros.filtro === 'hoy' ? 'ring-2 ring-amber-500' : ''" class="bg-white p-4 rounded-xl shadow-sm border cursor-pointer hover:border-amber-300 transition-colors">
                <p class="text-sm text-gray-500">Para Hoy</p>
                <p class="text-2xl font-bold text-blue-600">{{ stats.hoy }}</p>
            </div>
            <div @click="filtrar('vencidas')" :class="filtros.filtro === 'vencidas' ? 'ring-2 ring-amber-500' : ''" class="bg-white p-4 rounded-xl shadow-sm border cursor-pointer hover:border-amber-300 transition-colors">
                <p class="text-sm text-gray-500">Vencidas</p>
                <p class="text-2xl font-bold text-red-600">{{ stats.vencidas }}</p>
            </div>
            <div @click="filtrar('completadas')" :class="filtros.filtro === 'completadas' ? 'ring-2 ring-amber-500' : ''" class="bg-white p-4 rounded-xl shadow-sm border cursor-pointer hover:border-amber-300 transition-colors">
                <p class="text-sm text-gray-500">Completadas (semana)</p>
                <p class="text-2xl font-bold text-green-600">{{ stats.completadas_semana }}</p>
            </div>
        </div>

        <!-- Lista de tareas -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="divide-y divide-gray-100">
                <div v-if="!tareas.data.length" class="px-6 py-12 text-center text-gray-400">
                    <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-12 w-12 mb-4" />
                    <p>No hay tareas {{ filtros.filtro === 'completadas' ? 'completadas' : 'pendientes' }}</p>
                </div>
                <div 
                    v-for="tarea in tareas.data" 
                    :key="tarea.id"
                    class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 transition-colors"
                    :class="tarea.completada_at ? 'opacity-60' : ''"
                >
                    <!-- Checkbox / Completar -->
                    <button 
                        v-if="!tarea.completada_at"
                        @click="completarTarea(tarea)"
                        class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-gray-300 hover:border-green-500 hover:bg-green-50 transition-colors"
                    ></button>
                    <div v-else class="flex-shrink-0 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                        <FontAwesomeIcon :icon="['fas', 'check']" class="text-white text-xs" />
                    </div>

                    <!-- Icono tipo -->
                    <div :class="getTareaIconColor(tarea.tipo)" class="flex-shrink-0 p-2 rounded-lg">
                        <FontAwesomeIcon :icon="['fas', getTareaIcon(tarea.tipo)]" class="w-4 h-4" />
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900" :class="tarea.completada_at ? 'line-through' : ''">{{ tarea.titulo }}</p>
                        <div class="flex items-center gap-3 mt-1">
                            <Link v-if="tarea.prospecto" :href="`/crm/prospectos/${tarea.prospecto.id}`" class="text-sm text-blue-600 hover:underline">
                                {{ tarea.prospecto.nombre }}
                            </Link>
                            <span v-if="tarea.descripcion" class="text-sm text-gray-500 truncate">{{ tarea.descripcion }}</span>
                        </div>
                    </div>

                    <!-- Prioridad -->
                    <span :class="getPrioridadColor(tarea.prioridad)" class="px-2 py-1 rounded-full text-xs font-medium">
                        {{ tarea.prioridad }}
                    </span>

                    <!-- Fecha -->
                    <div class="text-right">
                        <p :class="esFechaVencida(tarea.fecha_limite) && !tarea.completada_at ? 'text-red-500 font-medium' : 'text-gray-500'" class="text-sm">
                            {{ formatFecha(tarea.fecha_limite) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="tareas.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2">
                <Link 
                    v-for="link in tareas.links" 
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="link.active ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-3 py-1 rounded-lg text-sm"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- Modal Nueva Tarea -->
        <div v-if="showModalNueva" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalNueva = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Nueva Tarea</h3>
                        <button @click="showModalNueva = false" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="crearTarea">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                                <input v-model="formTarea.titulo" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                    <select v-model="formTarea.tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option v-for="(label, key) in tiposTarea" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                                    <select v-model="formTarea.prioridad" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="alta">Alta</option>
                                        <option value="media">Media</option>
                                        <option value="baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div v-if="isAdmin && vendedores.length">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a *</label>
                                <select v-model="formTarea.user_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha límite *</label>
                                <input v-model="formTarea.fecha_limite" type="date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                <textarea v-model="formTarea.descripcion" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="showModalNueva = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                            <button type="submit" :disabled="procesando" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                Crear Tarea
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tareas: Object,
    stats: Object,
    filtros: Object,
    tiposTarea: Object,
    vendedores: Array,
    isAdmin: Boolean,
});

const page = usePage();
const showModalNueva = ref(false);
const procesando = ref(false);

const formTarea = ref({
    titulo: '',
    tipo: 'llamar',
    prioridad: 'media',
    user_id: page.props.auth?.user?.id,
    fecha_limite: new Date().toISOString().split('T')[0],
    descripcion: '',
});

const formatFecha = (fecha) => fecha ? new Date(fecha).toLocaleDateString('es-MX', { day: 'numeric', month: 'short' }) : '';
const esFechaVencida = (fecha) => new Date(fecha) < new Date(new Date().setHours(0,0,0,0));

const getTareaIcon = (tipo) => {
    const icons = { llamar: 'phone', enviar_cotizacion: 'file-invoice-dollar', seguimiento: 'redo', visita: 'building', reunion: 'users', otro: 'tasks' };
    return icons[tipo] || 'tasks';
};

const getTareaIconColor = (tipo) => {
    const colors = { llamar: 'bg-blue-100 text-blue-600', enviar_cotizacion: 'bg-purple-100 text-purple-600', seguimiento: 'bg-yellow-100 text-yellow-600', visita: 'bg-green-100 text-green-600', reunion: 'bg-orange-100 text-orange-600', otro: 'bg-gray-100 text-gray-600' };
    return colors[tipo] || 'bg-gray-100 text-gray-600';
};

const getPrioridadColor = (prioridad) => {
    const colors = { alta: 'bg-red-100 text-red-700', media: 'bg-yellow-100 text-yellow-700', baja: 'bg-green-100 text-green-700' };
    return colors[prioridad] || 'bg-gray-100 text-gray-700';
};

const filtrar = (filtro) => {
    router.get('/crm/tareas', { filtro }, { preserveState: true });
};

const completarTarea = (tarea) => {
    router.patch(`/crm/tareas/${tarea.id}/completar`, {}, { preserveState: true });
};

const crearTarea = () => {
    procesando.value = true;
    router.post('/crm/tareas', formTarea.value, {
        onSuccess: () => {
            showModalNueva.value = false;
            formTarea.value = { titulo: '', tipo: 'llamar', prioridad: 'media', user_id: page.props.auth?.user?.id, fecha_limite: new Date().toISOString().split('T')[0], descripcion: '' };
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};
</script>
