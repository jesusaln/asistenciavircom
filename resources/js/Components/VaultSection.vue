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
    categoria: 'Wifi',
    credentialable_id: props.credentialableId,
    credentialable_type: props.credentialableType
});

const openCreateModal = () => {
    editingItem.value = null;
    form.reset();
    form.credentialable_id = props.credentialableId;
    form.credentialable_type = props.credentialableType;
    form.categoria = 'Wifi';
    showingModal.value = true;
};

const openEditModal = (item) => {
    editingItem.value = item;
    form.nombre = item.nombre;
    form.usuario = item.usuario;
    form.categoria = item.categoria || 'Wifi';
    form.password = ''; // Leave empty if not changing
    form.host = item.host;
    form.puerto = item.puerto;
    form.notas = item.notas;
    showingModal.value = true;
};

const generatePassword = () => {
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
    let retVal = "";
    for (let i = 0, n = charset.length; i < 16; ++i) {
        retVal += charset.charAt(Math.floor(Math.random() * n));
    }
    form.password = retVal;
    notyf.success('Contraseña segura generada');
};

const categorias = ['Wifi', 'Router', 'DVR/NVR', 'Servidor', 'App', 'Panel de Alarma', 'Sitio Web', 'Otro'];

const getCategoryIcon = (cat) => {
    const icons = {
        'Wifi': 'wifi',
        'Router': 'network-wired',
        'DVR/NVR': 'video',
        'Servidor': 'server',
        'App': 'mobile-alt',
        'Panel de Alarma': 'bell',
        'Sitio Web': 'globe',
        'Otro': 'key'
    };
    return icons[cat] || 'key';
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
    <div class="mt-6 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 bg-indigo-50/50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                    <font-awesome-icon icon="shield-alt" />
                </div>
                <div>
                    <h3 class="text-sm font-black text-gray-800 dark:text-gray-100 uppercase tracking-tight">Bóveda de Credenciales</h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Almacenamiento blindado AES-256</p>
                </div>
            </div>
            <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase transition-all flex items-center gap-2 shadow-sm">
                <font-awesome-icon icon="plus" />
                Nueva Acceso
            </button>
        </div>

        <div class="p-6">
            <div v-if="items.length === 0" class="text-center py-12">
                <div class="w-20 h-20 bg-gray-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-200 dark:text-slate-700">
                    <font-awesome-icon icon="key" size="3x" />
                </div>
                <p class="text-xs text-gray-400 font-bold uppercase tracking-wide">No hay accesos registrados aún</p>
                <p class="text-[10px] text-gray-300 mt-1">Registre contraseñas de DVRs, Routers o Servidores aquí.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="item in items" :key="item.id" class="p-4 rounded-2xl border border-gray-100 dark:border-slate-800 hover:border-indigo-200 dark:hover:border-indigo-500/50 transition-all group bg-white dark:bg-slate-900 relative">
                    <!-- Category Badge -->
                    <div class="absolute top-4 right-4 flex gap-1 items-center">
                        <span v-if="item.categoria" class="px-2 py-0.5 bg-gray-100 dark:bg-slate-800 text-[8px] font-black text-gray-400 dark:text-gray-500 rounded uppercase tracking-tighter border border-gray-100 dark:border-slate-700">
                            {{ item.categoria }}
                        </span>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all">
                            <button @click="openEditModal(item)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition-colors"><font-awesome-icon icon="edit" size="xs" /></button>
                            <button @click="deleteItem(item.id)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors"><font-awesome-icon icon="trash" size="xs" /></button>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gray-50 dark:bg-slate-800 flex items-center justify-center text-indigo-500 dark:text-indigo-400 border border-gray-100 dark:border-slate-700">
                            <font-awesome-icon :icon="getCategoryIcon(item.categoria)" size="lg" />
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="text-xs font-black text-gray-800 dark:text-gray-100 uppercase tracking-wide truncate w-32" :title="item.nombre">{{ item.nombre }}</h4>
                            <p class="text-[9px] text-gray-400 font-bold font-mono tracking-tight truncate w-32">{{ item.host || 'Local' }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between p-2.5 bg-gray-50/50 dark:bg-slate-800/50 rounded-xl border border-gray-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Usuario</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ item.usuario }}</span>
                                <button @click="copyToClipboard(item.usuario)" class="text-gray-400 hover:text-indigo-600 transition-colors"><font-awesome-icon icon="copy" size="xs" /></button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-2.5 bg-gray-50/50 dark:bg-slate-800/50 rounded-xl border border-gray-100 dark:border-slate-800">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Password</span>
                            <div class="flex items-center gap-2">
                                <span v-if="revealedPasswords[item.id]" class="text-xs font-mono font-black text-indigo-600 dark:text-indigo-400">{{ revealedPasswords[item.id] }}</span>
                                <span v-else class="text-xs font-bold text-gray-200 dark:text-slate-700 tracking-widest">••••••••</span>
                                
                                <button @click="revealPassword(item)" :disabled="loadingReveal[item.id]" class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-indigo-600 transition-colors">
                                    <font-awesome-icon :icon="loadingReveal[item.id] ? 'spinner' : (revealedPasswords[item.id] ? 'eye-slash' : 'eye')" :spin="loadingReveal[item.id]" />
                                </button>
                                <button v-if="revealedPasswords[item.id]" @click="copyToClipboard(revealedPasswords[item.id])" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                    <font-awesome-icon icon="copy" size="xs" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="item.notas" class="mt-4 p-2.5 bg-yellow-50 dark:bg-yellow-900/10 rounded-xl border border-yellow-100 dark:border-yellow-900/30">
                         <p class="text-[9px] text-yellow-700 dark:text-yellow-500 leading-tight font-medium italic">{{ item.notas }}</p>
                    </div>

                    <!-- Audit Footer -->
                    <div v-if="item.last_revealed_at" class="mt-3 pt-2 border-t border-gray-50 dark:border-slate-800 flex items-center gap-1">
                        <font-awesome-icon icon="history" class="text-[8px] text-amber-500" />
                        <span class="text-[8px] font-bold text-gray-400 uppercase tracking-tighter">Último acceso: {{ new Date(item.last_revealed_at).toLocaleString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showingModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-gray-100 dark:border-slate-800">
                <div class="p-6 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
                             <font-awesome-icon icon="shield-alt" />
                        </div>
                        <h3 class="text-sm font-black text-gray-800 dark:text-gray-100 uppercase tracking-widest">
                            {{ editingItem ? 'Actualizar Acceso' : 'Nuevo Acceso Seguro' }}
                        </h3>
                    </div>
                    <button @click="showingModal = false" class="w-8 h-8 rounded-full bg-gray-50 dark:bg-slate-800 text-gray-400 hover:bg-gray-100 transition-all">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Concepto / Nombre</label>
                            <input v-model="form.nombre" type="text" placeholder="Ej: DVR Principal - Bodega" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-sm font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" required />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Usuario</label>
                                <input v-model="form.usuario" type="text" placeholder="admin" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-sm font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Contraseña</label>
                                <div class="flex gap-2">
                                    <input v-model="form.password" type="text" :placeholder="editingItem ? '••••••••' : 'Password'" class="flex-1 bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-sm font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" :required="!editingItem" />
                                    <button type="button" @click="generatePassword" class="w-12 h-12 bg-gray-50 dark:bg-slate-800 rounded-2xl text-gray-400 hover:text-indigo-600 transition-all border border-gray-100 dark:border-slate-700 flex items-center justify-center shadow-sm">
                                        <font-awesome-icon icon="sync-alt" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Categoría</label>
                                <select v-model="form.categoria" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-xs font-black focus:ring-indigo-500 focus:bg-white transition-all shadow-sm uppercase">
                                    <option v-for="cat in categorias" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Puerto (Opcional)</label>
                                <input v-model="form.puerto" type="text" placeholder="80" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-sm font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Host / Dirección IP</label>
                            <input v-model="form.host" type="text" placeholder="mibodega.ddns.net" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl h-12 text-sm font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 px-1">Notas / Guía de acceso</label>
                            <textarea v-model="form.notas" rows="2" class="w-full bg-gray-50 dark:bg-slate-800/50 border-gray-100 dark:border-slate-700 rounded-2xl p-4 text-xs font-bold focus:ring-indigo-500 focus:bg-white transition-all shadow-sm" placeholder="Detalles de configuración..."></textarea>
                        </div>
                    </div>

                    <div class="mt-10 flex gap-4">
                        <button type="button" @click="showingModal = false" class="flex-1 h-14 bg-gray-50 hover:bg-gray-100 text-gray-500 rounded-2xl text-xs font-black uppercase transition-all">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black uppercase transition-all shadow-xl shadow-indigo-200 flex items-center justify-center gap-2">
                            <font-awesome-icon :icon="editingItem ? 'check' : 'shield-alt'" />
                            {{ editingItem ? 'Actualizar' : 'Guardar Seguro' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
