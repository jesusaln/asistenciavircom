<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';
import { Notyf } from 'notyf';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    credenciales: Object,
    clientes: Array,
    polizas: Array,
    filters: Object
});

const notyf = new Notyf();
const search = ref(props.filters.search || '');
const clienteId = ref(props.filters.cliente_id || '');
const showingCreateModal = ref(false);
const revealedPasswords = ref({});
const loadingReveal = ref({});

const form = useForm({
    nombre: '',
    usuario: '',
    password: '',
    host: '',
    puerto: '',
    notas: '',
    credentialable_id: '',
    credentialable_type: 'App\\Models\\Cliente', // Default
});

const openCreateModal = () => {
    form.reset();
    showingCreateModal.value = true;
};

const submit = () => {
    form.post(route('credenciales.store'), {
        onSuccess: () => {
            showingCreateModal.value = false;
            notyf.success('Credencial guardada en la bóveda');
        }
    });
};

const updateFilters = () => {
    router.get(route('credenciales.index'), { 
        search: search.value,
        cliente_id: clienteId.value 
    }, {
        preserveState: true,
        replace: true
    });
};

watch(search, (value) => {
    updateFilters();
});
watch(clienteId, (value) => {
    updateFilters();
});

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

const getOwnerLink = (item) => {
    if (item.credentialable_type === 'App\\Models\\Cliente') {
        return route('clientes.show', item.credentialable_id);
    } else if (item.credentialable_type === 'App\\Models\\PolizaServicio') {
        return route('polizas-servicio.edit', item.credentialable_id);
    }
    return '#';
};

const getOwnerName = (item) => {
    return item.credentialable?.nombre_razon_social || item.credentialable?.nombre || 'Desconocido';
};

</script>

<template>
    <Head title="Bóveda de Credenciales" />

    <AppLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                        <font-awesome-icon icon="shield-alt" size="2x" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Bóveda de Credenciales</h1>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Gestión centralizada de accesos seguros</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="openCreateModal" class="px-5 h-12 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase transition-all flex items-center gap-2 shadow-lg shadow-indigo-200 hover:shadow-xl hover:translate-y-[-1px]">
                        <font-awesome-icon icon="plus" />
                        <span class="hidden sm:inline">Nueva</span>
                    </button>

                    <!-- Filtro Cliente -->
                     <div class="relative w-full md:w-64 group">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-hover:text-indigo-500 transition-colors">
                            <font-awesome-icon icon="users" />
                         </div>
                         <select v-model="clienteId" class="w-full bg-white border-gray-200 rounded-xl h-12 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 shadow-sm pl-10 pr-8 hover:border-indigo-300 transition-all appearance-none cursor-pointer">
                            <option value="">Todos los Clientes</option>
                            <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                {{ cliente.nombre_razon_social }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                            <font-awesome-icon icon="chevron-down" size="xs" />
                        </div>
                    </div>

                    <div class="relative w-full md:w-80 group">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                             <font-awesome-icon icon="search" />
                        </span>
                        <input 
                            v-model="search"
                            type="text" 
                            placeholder="Buscar credenciales..."
                            class="pl-10 w-full bg-white border-gray-200 rounded-xl h-12 text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 shadow-sm hover:border-indigo-300 transition-all placeholder-gray-300"
                        >
                    </div>
                </div>
            </div>

            <!-- Warning Card -->
            <div class="mb-8 p-4 bg-amber-50 rounded-2xl border border-amber-100 flex items-start gap-4">
                <div class="text-amber-500 mt-1">
                    <font-awesome-icon icon="exclamation-triangle" />
                </div>
                <div>
                    <h4 class="text-xs font-black text-amber-800 uppercase mb-1">Aviso de Seguridad</h4>
                    <p class="text-xs text-amber-700 leading-normal">
                        Todas las visualizaciones de contraseñas son registradas en el log de auditoría. Use esta bóveda solo para propósitos autorizados. 
                        Las contraseñas están encriptadas bajo el estándar <strong>AES-256-CBC</strong>.
                    </p>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="credenciales.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="item in credenciales.data" :key="item.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all overflow-hidden group">
                    <!-- Top Info/Owner -->
                    <div class="p-4 border-b border-gray-50 bg-gray-50/50 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Vinculado a:</span>
                            <Link :href="getOwnerLink(item)" class="text-[9px] font-black text-indigo-600 hover:text-indigo-800 uppercase underline decoration-indigo-200 transition-all">
                                {{ getOwnerName(item) }}
                            </Link>
                        </div>
                        <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
                    </div>

                    <div class="p-5">
                         <div class="flex items-center gap-4 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100">
                                <font-awesome-icon :icon="item.host ? 'server' : 'key'" size="lg" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black text-gray-800 uppercase tracking-tight">{{ item.nombre }}</h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">{{ item.host || 'Acceso Local' }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <span class="text-[10px] font-black text-gray-400 uppercase">Usuario</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-700">{{ item.usuario }}</span>
                                    <button @click="copyToClipboard(item.usuario)" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                        <font-awesome-icon icon="copy" size="sm" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <span class="text-[10px] font-black text-gray-400 uppercase">Password</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="revealedPasswords[item.id]" class="text-xs font-mono font-black text-indigo-600 tracking-wider">
                                        {{ revealedPasswords[item.id] }}
                                    </span>
                                    <span v-else class="text-xs font-bold text-gray-300 tracking-widest">••••••••••••</span>
                                    
                                    <button 
                                        @click="revealPassword(item)" 
                                        :disabled="loadingReveal[item.id]"
                                        class="ml-1 text-gray-400 hover:text-indigo-600 transition-colors h-6 w-6 flex items-center justify-center"
                                    >
                                        <font-awesome-icon :icon="loadingReveal[item.id] ? 'spinner' : (revealedPasswords[item.id] ? 'eye-slash' : 'eye')" :spin="loadingReveal[item.id]" />
                                    </button>
                                    
                                    <button v-if="revealedPasswords[item.id]" @click="copyToClipboard(revealedPasswords[item.id])" class="text-gray-400 hover:text-indigo-600 transition-colors">
                                        <font-awesome-icon icon="copy" size="sm" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="item.notas" class="mt-4 p-3 bg-yellow-50 rounded-xl border border-yellow-100 italic">
                             <p class="text-[10px] text-yellow-700 leading-tight font-medium">{{ item.notas }}</p>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <span class="text-[9px] font-black text-gray-400 uppercase">
                             Actualizado {{ new Date(item.updated_at).toLocaleDateString() }}
                        </span>
                        <Link :href="getOwnerLink(item)" class="text-[10px] font-black text-gray-400 hover:text-indigo-600 uppercase flex items-center gap-1 transition-colors">
                            Gestionar
                            <font-awesome-icon icon="arrow-right" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-3xl p-16 text-center shadow-sm border border-gray-100">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-200">
                    <font-awesome-icon icon="search" size="4x" />
                </div>
                <h3 class="text-xl font-black text-gray-800 uppercase mb-2">No se encontraron credenciales</h3>
                <p class="text-sm text-gray-400 font-medium">Prueba con otro término de búsqueda o registra nuevas credenciales en Clientes o Pólizas.</p>
            </div>

            <!-- Pagination -->
            <div v-if="credenciales.links.length > 3" class="mt-10 flex justify-center">
                <Pagination :links="credenciales.links" />
            </div>
        </div>

        <!-- Modal de Creación Global -->
        <div v-if="showingCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">
                        Nueva Credencial Global
                    </h3>
                    <button @click="showingCreateModal = false" class="text-gray-400 hover:text-gray-600">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6">
                    <div class="space-y-6">
                        <!-- Selección de Tipo Visual -->
                        <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-3 px-1">¿A quién pertenece esta credencial?</label>
                            
                            <!-- Toggle Tabs -->
                            <div class="flex p-1 bg-gray-200 rounded-xl mb-4">
                                <button type="button" 
                                    @click="form.credentialable_type = 'App\\Models\\Cliente'"
                                    :class="{'bg-white text-indigo-600 shadow-sm': form.credentialable_type === 'App\\Models\\Cliente', 'text-gray-500 hover:text-gray-700': form.credentialable_type !== 'App\\Models\\Cliente'}"
                                    class="flex-1 py-2 rounded-lg text-xs font-black uppercase transition-all flex items-center justify-center gap-2">
                                    <font-awesome-icon icon="users" /> Cliente
                                </button>
                                <button type="button" 
                                    @click="form.credentialable_type = 'App\\Models\\PolizaServicio'"
                                    :class="{'bg-white text-indigo-600 shadow-sm': form.credentialable_type === 'App\\Models\\PolizaServicio', 'text-gray-500 hover:text-gray-700': form.credentialable_type !== 'App\\Models\\PolizaServicio'}"
                                    class="flex-1 py-2 rounded-lg text-xs font-black uppercase transition-all flex items-center justify-center gap-2">
                                    <font-awesome-icon icon="file-contract" /> Póliza de Servicio
                                </button>
                            </div>

                            <!-- Selector Dinámico -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <font-awesome-icon :icon="form.credentialable_type === 'App\\Models\\Cliente' ? 'user-tie' : 'file-signature'" />
                                </div>
                                <select v-model="form.credentialable_id" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white" required>
                                    <option value="" disabled>Seleccione {{ form.credentialable_type === 'App\\Models\\Cliente' ? 'el Cliente' : 'la Póliza' }}...</option>
                                    <template v-if="form.credentialable_type === 'App\\Models\\Cliente'">
                                        <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_razon_social }}</option>
                                    </template>
                                    <template v-else>
                                        <option v-for="p in polizas" :key="p.id" :value="p.id">{{ p.folio }} - {{ p.nombre }}</option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <!-- Detalles de Credencial -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Nombre Identificador</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <font-awesome-icon icon="tag" />
                                    </div>
                                    <input v-model="form.nombre" type="text" placeholder="Ej: Acceso Servidor SQL Producción" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500" required />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Usuario</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <font-awesome-icon icon="user" />
                                        </div>
                                        <input v-model="form.usuario" type="text" placeholder="admin" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500" required />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Contraseña</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <font-awesome-icon icon="key" />
                                        </div>
                                        <input v-model="form.password" type="text" placeholder="••••••••" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500" required />
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Host / URL</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <font-awesome-icon icon="server" />
                                        </div>
                                        <input v-model="form.host" type="text" placeholder="192.168.1.50" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Puerto</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <font-awesome-icon icon="network-wired" />
                                        </div>
                                        <input v-model="form.puerto" type="text" placeholder="1433" class="w-full border-gray-200 rounded-xl h-11 pl-10 text-sm font-bold focus:ring-indigo-500" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Notas Adicionales</label>
                                <textarea v-model="form.notas" rows="3" class="w-full border-gray-200 rounded-xl text-sm font-bold focus:ring-indigo-500 p-3" placeholder="Instrucciones especiales de acceso..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-8 flex gap-3 pt-6 border-t border-gray-100">
                        <button type="button" @click="showingCreateModal = false" class="flex-1 h-12 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-xl text-xs font-black uppercase transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing" class="flex-1 h-12 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase transition-all shadow-lg shadow-indigo-200 flex items-center justify-center gap-2">
                             <font-awesome-icon icon="shield-alt" />
                             Guardar Segura
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.decoration-indigo-200 { text-decoration-color: #c7d2fe; }
</style>
