<script setup>
import { ref, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';

const props = defineProps({
    poliza: Object,
});

const showModal = ref(false);
const editingMantenimiento = ref(null);
const guiasTecnicas = ref([]);
const loadingGuias = ref(false);

const form = useForm({
    nombre: '',
    descripcion: '',
    frecuencia: 'mensual',
    dia_preferido: 1,
    requiere_visita: false,
    activo: true,
    checklist: [], // Array de strings
    guia_tecnica_id: null,
});

const newItem = ref('');

const fetchGuias = async () => {
    loadingGuias.value = true;
    try {
        const response = await axios.get(route('api.guias-tecnicas.index'));
        guiasTecnicas.value = response.data;
    } catch (error) {
        console.error('Error cargando guías:', error);
    } finally {
        loadingGuias.value = false;
    }
};

onMounted(() => {
    fetchGuias();
});

const addChecklistItem = () => {
    if (newItem.value.trim()) {
        form.checklist.push(newItem.value.trim());
        newItem.value = '';
    }
};

const removeChecklistItem = (index) => {
    form.checklist.splice(index, 1);
};

const openCreateModal = () => {
    editingMantenimiento.value = null;
    form.reset();
    form.checklist = [];
    showModal.value = true;
};

const openEditModal = (mantenimiento) => {
    editingMantenimiento.value = mantenimiento;
    form.nombre = mantenimiento.nombre;
    form.descripcion = mantenimiento.descripcion;
    form.frecuencia = mantenimiento.frecuencia;
    form.dia_preferido = mantenimiento.dia_preferido;
    form.requiere_visita = !!mantenimiento.requiere_visita;
    form.activo = !!mantenimiento.activo;
    form.checklist = mantenimiento.checklist ? JSON.parse(JSON.stringify(mantenimiento.checklist)) : [];
    form.guia_tecnica_id = mantenimiento.guia_tecnica_id;
    showModal.value = true;
};

const onGuiaSelected = () => {
    if (form.guia_tecnica_id) {
        const guia = guiasTecnicas.value.find(g => g.id === form.guia_tecnica_id);
        if (guia) {
            // Auto complete fields if needed, or just let them stay custom
            // If they are empty, fill them
            if (!form.nombre) form.nombre = guia.nombre;
            if (!form.descripcion) form.descripcion = guia.descripcion;
            
            // Merge checklist
            if (guia.checklist_default && Array.isArray(guia.checklist_default)) {
                // Avoid duplicates
                const current = new Set(form.checklist);
                guia.checklist_default.forEach(item => current.add(item));
                form.checklist = Array.from(current);
            }
        }
    }
};

const submit = () => {
    if (editingMantenimiento.value) {
        form.put(route('polizas-servicio.mantenimientos.update', editingMantenimiento.value.id), {
            onSuccess: () => showModal.value = false
        });
    } else {
        form.post(route('polizas-servicio.mantenimientos.store', props.poliza.id), {
            onSuccess: () => showModal.value = false
        });
    }
};

const deleteMantenimiento = (id) => {
    if (confirm('¿Estás seguro de eliminar este plan de mantenimiento?')) {
        router.delete(route('polizas-servicio.mantenimientos.destroy', id));
    }
};
</script>

<template>
    <div class="bg-slate-800/40 backdrop-blur border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-700/50 flex justify-between items-center bg-slate-800/60">
            <h3 class="font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-orange-500/20 rounded-lg text-orange-400">
                    <span class="text-xl">🛠️</span>
                </div>
                Planes de Mantenimiento Definidos
            </h3>
            <button @click="openCreateModal" class="text-xs bg-orange-500 hover:bg-orange-600 text-white px-3 py-1.5 rounded-lg font-bold transition-all shadow-lg shadow-orange-900/20 border border-orange-400/20">
                + Agregar Plan
            </button>
        </div>

        <div class="divide-y divide-slate-700/50">
            <div v-for="mant in poliza.mantenimientos" :key="mant.id" class="p-6 hover:bg-slate-700/30 transition-colors">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="font-bold text-white flex items-center gap-2 text-lg">
                            {{ mant.nombre }}
                            <span v-if="!mant.activo" class="px-2 py-0.5 rounded text-[10px] bg-slate-700 text-slate-400 uppercase border border-slate-600">Inactivo</span>
                            <span v-else class="px-2 py-0.5 rounded text-[10px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase font-bold">Activo</span>
                        </h4>
                        <p class="text-sm text-slate-400 mt-1">{{ mant.descripcion || 'Sin descripción' }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="openEditModal(mant)" class="p-2 hover:bg-slate-700 rounded-lg text-blue-400 hover:text-blue-300 transition-colors">
                            ✏️
                        </button>
                        <button @click="deleteMantenimiento(mant.id)" class="p-2 hover:bg-slate-700 rounded-lg text-red-400 hover:text-red-300 transition-colors">
                            🗑️
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 text-xs text-slate-400 mt-4 mb-5">
                    <span class="bg-blue-900/20 text-blue-300 border border-blue-500/20 px-2.5 py-1 rounded-md font-medium">
                        📅 Frecuencia: {{ mant.frecuencia }}
                    </span>
                    <span class="bg-purple-900/20 text-purple-300 border border-purple-500/20 px-2.5 py-1 rounded-md font-medium">
                        🗓️ Día preferido: {{ mant.dia_preferido || 'N/A' }}
                    </span>
                    <span class="bg-orange-900/20 text-orange-300 border border-orange-500/20 px-2.5 py-1 rounded-md font-medium">
                        🏠 Requiere Visita: {{ mant.requiere_visita ? 'SÍ' : 'NO' }}
                    </span>
                    <span v-if="mant.guia_tecnica_id" class="bg-amber-900/20 text-amber-300 border border-amber-500/20 px-2.5 py-1 rounded-md font-medium flex items-center gap-1">
                        📘 Guía Vinculada
                    </span>
                </div>

                <!-- Checklist Preview -->
                <div v-if="mant.checklist && mant.checklist.length" class="mt-3 bg-slate-900/50 border border-slate-700/50 rounded-xl p-4">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Checklist de Tareas ({{ mant.checklist.length }})</p>
                    <ul class="space-y-2">
                        <li v-for="(item, idx) in mant.checklist.slice(0, 3)" :key="idx" class="text-sm text-slate-300 flex items-start gap-3">
                            <span class="text-emerald-500 mt-0.5">✔</span>
                            {{ typeof item === 'string' ? item : item.label }}
                        </li>
                        <li v-if="mant.checklist.length > 3" class="text-xs text-blue-400 italic pl-6 mt-2">
                            ... y {{ mant.checklist.length - 3 }} tareas más
                        </li>
                    </ul>
                </div>
            </div>
            
            <div v-if="!poliza.mantenimientos || poliza.mantenimientos.length === 0" class="p-12 text-center text-slate-500 text-sm italic">
                No hay planes de mantenimiento definidos para esta póliza.
                <br>
                <button @click="openCreateModal" class="text-blue-400 hover:text-blue-300 hover:underline mt-2 font-medium">Crear el primer plan</button>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6 bg-slate-900 text-slate-300">
                <h2 class="text-xl font-bold text-white mb-6">
                    {{ editingMantenimiento ? 'Editar Plan de Mantenimiento' : 'Nuevo Plan de Mantenimiento' }}
                </h2>

                <div class="space-y-5">
                    <!-- Selector de Plantilla/Guía -->
                    <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                        <label class="block text-sm font-bold text-amber-400 mb-2 uppercase tracking-wide">
                             📘 Cargar desde Plantilla / Guía
                        </label>
                        <select v-model="form.guia_tecnica_id" @change="onGuiaSelected" class="w-full rounded-lg bg-slate-900 border-slate-700 text-white focus:border-amber-500 focus:ring-amber-500">
                            <option :value="null">-- Personalizado (Sin Guía) --</option>
                            <option v-for="guia in guiasTecnicas" :key="guia.id" :value="guia.id">
                                {{ guia.nombre }}
                            </option>
                        </select>
                        <p class="text-xs text-slate-500 mt-2">
                            Seleccionar una guía precargará el nombre, descripción y la checklist recomendada.
                            <a :href="route('guias.index')" target="_blank" class="text-indigo-400 hover:underline ml-1">Gestionar Guías</a>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Nombre del Mantenimiento</label>
                        <input v-model="form.nombre" type="text" class="w-full rounded-lg bg-slate-800 border-slate-700 text-white placeholder-slate-500 focus:border-blue-500 focus:ring-blue-500" placeholder="Ej: Mantenimiento Preventivo Mensual">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1">Descripción</label>
                        <textarea v-model="form.descripcion" class="w-full rounded-lg bg-slate-800 border-slate-700 text-white placeholder-slate-500 focus:border-blue-500 focus:ring-blue-500" rows="2"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-1">Frecuencia</label>
                            <select v-model="form.frecuencia" class="w-full rounded-lg bg-slate-800 border-slate-700 text-white focus:border-blue-500 focus:ring-blue-500">
                                <option value="mensual">Mensual</option>
                                <option value="bimestral">Bimestral</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-1">Día Preferido (del mes)</label>
                            <input v-model="form.dia_preferido" type="number" min="1" max="31" class="w-full rounded-lg bg-slate-800 border-slate-700 text-white focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-6 py-2">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" v-model="form.requiere_visita" class="rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-slate-300 group-hover:text-white transition-colors">Requiere Visita en Sitio</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" v-model="form.activo" class="rounded border-slate-700 bg-slate-800 text-green-500 focus:ring-green-500">
                            <span class="text-sm text-slate-300 group-hover:text-white transition-colors">Activo (Generar tickets)</span>
                        </label>
                    </div>

                    <!-- Checklist Editor -->
                    <div class="border-t border-slate-700 pt-5 mt-2">
                        <label class="block text-sm font-bold text-slate-300 mb-3">Checklist de Tareas</label>
                        
                        <div class="flex gap-2 mb-4">
                            <input 
                                v-model="newItem" 
                                @keyup.enter="addChecklistItem"
                                type="text" 
                                class="flex-1 rounded-lg bg-slate-800 border-slate-700 text-white placeholder-slate-500 text-sm focus:border-blue-500 focus:ring-blue-500" 
                                placeholder="Escriba una tarea y presione Enter..."
                            >
                            <button @click="addChecklistItem" type="button" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-blue-500 shadow-lg shadow-blue-900/30 border border-blue-500/30 transition-all">
                                Agregar
                            </button>
                        </div>

                        <ul class="space-y-2 max-h-60 overflow-y-auto bg-slate-950/50 rounded-xl p-3 border border-slate-800 custom-scrollbar">
                            <li v-for="(item, index) in form.checklist" :key="index" class="flex justify-between items-center bg-slate-800 p-3 rounded-lg border border-slate-700 group hover:border-slate-600 transition-colors">
                                <span class="text-sm text-slate-200">{{ typeof item === 'string' ? item : item.label }}</span>
                                <button @click="removeChecklistItem(index)" type="button" class="text-slate-500 hover:text-red-400 transition-colors">
                                    ✕
                                </button>
                            </li>
                            <li v-if="form.checklist.length === 0" class="text-center text-sm text-slate-600 py-6 italic">
                                No hay tareas agregadas a la checklist.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 border-t border-slate-800 pt-6">
                    <button @click="showModal = false" type="button" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-lg hover:bg-slate-700 transition-colors border border-slate-700">
                        Cancelar
                    </button>
                    <button @click="submit" type="button" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-all font-bold shadow-lg shadow-blue-900/30 border border-blue-500/30" :disabled="form.processing">
                        {{ editingMantenimiento ? 'Guardar Cambios' : 'Crear Plan' }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>>
