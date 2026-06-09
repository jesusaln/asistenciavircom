<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';

const props = defineProps({
    credentialableId: {
        type: Number,
        required: true
    },
    credentialableType: {
        type: String,
        required: true
    },
    items: {
        type: Array,
        default: () => []
    }
});

const notyf = new Notyf();
const showingModal = ref(false);
const editingItem = ref(null);
const revealedPasswords = ref({});
const loadingReveal = ref({});

const form = useForm({
    nombre: '',
    usuario: '',
    password: '',
    host: '',
    puerto: '',
    notas: '',
    credentialable_id: props.credentialableId,
    credentialable_type: props.credentialableType
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    form.credentialable_id = props.credentialableId;
    form.credentialable_type = props.credentialableType;
    showingModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.nombre = item.nombre;
    form.usuario = item.usuario;
    form.password = ''; // Leave empty if not changing
    form.host = item.host;
    form.puerto = item.puerto;
    form.notas = item.notas;
    showingModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(route('credenciales.update', editingItem.value.id), {
            onSuccess: () => {
                showingModal.value = false;
                notyf.success('Credencial actualizada');
            }
        });
    } else {
        form.post(route('credenciales.store'), {
            onSuccess: () => {
                showingModal.value = false;
                notyf.success('Credencial guardada de forma segura');
            }
        });
    }
};

const deleteItem = (id) => {
    if (confirm('¿Estás seguro de eliminar esta credencial?')) {
        form.delete(route('credenciales.destroy', id), {
            onSuccess: () => notyf.success('Credencial eliminada')
        });
    }
};

const revealPassword = async (item) => {
    if (revealedPasswords.value[item.id]) {
        delete revealedPasswords.value[item.id];
        return;
    }

    loadingReveal.value[item.id] = true;
    try {
        const response = await axios.get(route('credenciales.reveal', item.id));
        revealedPasswords.value[item.id] = response.data.password;
    } catch (error) {
        notyf.error('No se pudo revelar la contraseña');
    } finally {
        loadingReveal.value[item.id] = false;
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    notyf.success('Copiado al portapapeles');
};

</script>

<template>
    <div class="mt-6 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                    <font-awesome-icon icon="key" />
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-tight">Bóveda de Credenciales</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Acceso seguro y encriptado</p>
                </div>
            </div>
            <button @click="openCreateModal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[10px] font-bold uppercase transition-all flex items-center gap-1">
                <font-awesome-icon icon="plus" />
                Nueva Credencial
            </button>
        </div>

        <div class="p-4">
            <div v-if="items.length === 0" class="text-center py-8">
                <div class="text-gray-300 mb-2">
                    <font-awesome-icon icon="shield-alt" size="3x" />
                </div>
                <p class="text-xs text-gray-400 font-medium">No hay credenciales guardadas para este registro.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="item in items" :key="item.id" class="p-4 rounded-xl border border-gray-100 hover:border-indigo-200 transition-all group bg-white shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center text-indigo-500 border border-gray-100">
                                <font-awesome-icon :icon="item.host ? 'server' : 'lock'" />
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-gray-800 uppercase tracking-wide">{{ item.nombre }}</h4>
                                <p class="text-[10px] text-gray-400 font-bold">{{ item.host || 'Sin host' }}</p>
                            </div>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                            <button @click="openEditModal(item)" class="p-1.5 text-gray-400 hover:text-indigo-600"><font-awesome-icon icon="edit" /></button>
                            <button @click="deleteItem(item.id)" class="p-1.5 text-gray-400 hover:text-red-600"><font-awesome-icon icon="trash" /></button>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Usuario</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-700">{{ item.usuario }}</span>
                                <button @click="copyToClipboard(item.usuario)" class="text-[10px] text-gray-400 hover:text-indigo-600"><font-awesome-icon icon="copy" /></button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Contraseña</span>
                            <div class="flex items-center gap-2">
                                <span v-if="revealedPasswords[item.id]" class="text-xs font-mono font-bold text-indigo-600">{{ revealedPasswords[item.id] }}</span>
                                <span v-else class="text-xs font-bold text-gray-300">••••••••••••</span>
                                
                                <button @click="revealPassword(item)" :disabled="loadingReveal[item.id]" class="text-[10px] text-gray-400 hover:text-indigo-600">
                                    <font-awesome-icon :icon="loadingReveal[item.id] ? 'spinner' : (revealedPasswords[item.id] ? 'eye-slash' : 'eye')" :spin="loadingReveal[item.id]" />
                                </button>
                                <button v-if="revealedPasswords[item.id]" @click="copyToClipboard(revealedPasswords[item.id])" class="text-[10px] text-gray-400 hover:text-indigo-600">
                                    <font-awesome-icon icon="copy" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="item.notas" class="mt-3 p-2 bg-yellow-50 rounded-lg border border-yellow-100">
                         <p class="text-[9px] text-yellow-700 leading-tight">{{ item.notas }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">
                        {{ editingItem ? 'Editar Credencial' : 'Nueva Credencial Segura' }}
                    </h3>
                    <button @click="showingModal = false" class="text-gray-400 hover:text-gray-600">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Nombre / Título</label>
                            <input v-model="form.nombre" type="text" placeholder="Ej: SQL Admin Production" class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Usuario</label>
                                <input v-model="form.usuario" type="text" placeholder="admin" class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Contraseña</label>
                                <input v-model="form.password" type="password" :placeholder="editingItem ? '••••••••' : 'Password'" class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" :required="!editingItem" />
                                <p v-if="editingItem" class="text-[9px] text-gray-400 mt-1">Dejar vacío para no cambiar</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Host / URL</label>
                                <input v-model="form.host" type="text" placeholder="192.168.1.50" class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Puerto</label>
                                <input v-model="form.puerto" type="text" placeholder="1433" class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Notas Adicionales</label>
                            <textarea v-model="form.notas" rows="3" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500" placeholder="Instrucciones especiales de acceso..."></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button type="button" @click="showingModal = false" class="flex-1 h-12 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-black uppercase transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 h-12 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase transition-all shadow-lg shadow-indigo-200">
                             {{ editingItem ? 'Actualizar' : 'Guardar en Bóveda' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
