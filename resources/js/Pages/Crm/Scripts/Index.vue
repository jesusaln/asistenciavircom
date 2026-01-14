<template>
    <Head title="CRM - Scripts de Venta" />

    <div class="container mx-auto px-6 py-8 animate-fade-in">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white shadow-lg">
                            <FontAwesomeIcon :icon="['fas', 'scroll']" class="h-6 w-6" />
                        </span>
                        Scripts de Venta
                    </h1>
                    <p class="mt-2 text-gray-500">
                        Guiones y respuestas para tu equipo de ventas
                    </p>
                </div>
                
                <button @click="nuevoScript" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 text-white font-semibold rounded-lg hover:bg-purple-600">
                    <FontAwesomeIcon :icon="['fas', 'plus']" />
                    Nuevo Script
                </button>
            </div>
        </div>

        <!-- Scripts por tipo -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div v-for="(label, tipoKey) in tipos" :key="tipoKey" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div :class="getTipoHeaderColor(tipoKey)" class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', getTipoIcon(tipoKey)]" />
                        {{ label }}
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div 
                        v-for="script in scriptsPorTipo(tipoKey)" 
                        :key="script.id"
                        class="p-4 rounded-lg border border-gray-200 hover:border-purple-300 hover:bg-purple-50/30 cursor-pointer transition-colors"
                        @click="editarScript(script)"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900">{{ script.nombre }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ etapas[script.etapa] }}</span>
                                <span v-if="!script.activo" class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-600">Inactivo</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2">{{ script.contenido }}</p>
                    </div>
                    <div v-if="!scriptsPorTipo(tipoKey).length" class="text-center py-8 text-gray-400">
                        <p class="text-sm">Sin scripts de {{ label.toLowerCase() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Editar/Crear Script -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModal = false">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black bg-opacity-50"></div>
                <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full p-6 animate-scale-in">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ formScript.id ? 'Editar' : 'Nuevo' }} Script</h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                            <FontAwesomeIcon :icon="['fas', 'times']" />
                        </button>
                    </div>

                    <form @submit.prevent="guardarScript">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input v-model="formScript.nombre" type="text" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                                    <select v-model="formScript.tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                        <option v-for="(label, key) in tipos" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Etapa</label>
                                    <select v-model="formScript.etapa" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                                        <option v-for="(label, key) in etapas" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contenido del Script *</label>
                                <textarea v-model="formScript.contenido" rows="8" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-mono" placeholder="Buenos días, ¿hablo con [NOMBRE]?&#10;&#10;Mi nombre es [VENDEDOR] de [EMPRESA]..."></textarea>
                                <p class="text-xs text-gray-500 mt-1">Usa [NOMBRE], [VENDEDOR], [EMPRESA] como placeholders</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tips (opcional)</label>
                                <textarea v-model="formScript.tips" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" placeholder="Consejos para el vendedor..."></textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <input v-model="formScript.activo" type="checkbox" id="activo" class="rounded text-purple-500 focus:ring-purple-500">
                                <label for="activo" class="text-sm text-gray-700">Script activo</label>
                            </div>
                        </div>

                        <div class="flex justify-between mt-6">
                            <button v-if="formScript.id" type="button" @click="eliminarScript" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg">
                                <FontAwesomeIcon :icon="['fas', 'trash']" class="mr-2" />
                                Eliminar
                            </button>
                            <div class="flex gap-3 ml-auto">
                                <button type="button" @click="showModal = false" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">Cancelar</button>
                                <button type="submit" :disabled="procesando" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 disabled:opacity-50">
                                    <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineOptions({ layout: AppLayout });

const props = defineProps({
    scripts: Array,
    tipos: Object,
    etapas: Object,
});

const showModal = ref(false);
const procesando = ref(false);

const formScript = ref({
    id: null,
    nombre: '',
    tipo: 'apertura',
    etapa: 'general',
    contenido: '',
    tips: '',
    activo: true,
});

const scriptsPorTipo = (tipo) => props.scripts.filter(s => s.tipo === tipo);

const getTipoIcon = (tipo) => {
    const icons = { apertura: 'door-open', seguimiento: 'redo', cierre: 'handshake', objecion: 'shield-alt', presentacion: 'presentation' };
    return icons[tipo] || 'file-alt';
};

const getTipoHeaderColor = (tipo) => {
    const colors = { apertura: 'bg-gradient-to-r from-blue-500 to-blue-600', seguimiento: 'bg-gradient-to-r from-yellow-500 to-orange-500', cierre: 'bg-gradient-to-r from-green-500 to-green-600', objecion: 'bg-gradient-to-r from-red-500 to-red-600', presentacion: 'bg-gradient-to-r from-purple-500 to-purple-600' };
    return colors[tipo] || 'bg-gray-500';
};

const nuevoScript = () => {
    formScript.value = { id: null, nombre: '', tipo: 'apertura', etapa: 'general', contenido: '', tips: '', activo: true };
    showModal.value = true;
};

const editarScript = (script) => {
    formScript.value = { ...script };
    showModal.value = true;
};

const guardarScript = () => {
    procesando.value = true;
    router.post('/crm/scripts', formScript.value, {
        onSuccess: () => {
            showModal.value = false;
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const eliminarScript = () => {
    if (confirm('¿Eliminar este script?')) {
        router.delete(`/crm/scripts/${formScript.value.id}`, {
            onSuccess: () => { showModal.value = false; },
        });
    }
};
</script>
