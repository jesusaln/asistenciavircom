<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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

// Debounce search
let timeout;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        updateFilters();
    }, 300);
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

const handlePageChange = (page) => {
    router.get(route('credenciales.index'), {
        search: search.value,
        cliente_id: clienteId.value,
        page: page
    }, {
        preserveState: true,
        replace: true
    });
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
        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 px-4 sm:px-6 lg:px-8 transition-colors">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl text-emerald-500 dark:text-slate-400">
                                <svg class="w-10 h-10 group-hover:scale-105 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide leading-none mb-1">
                                Bóveda de <span class="bg-clip-text text-transparent bg-gradient-to-r from-emerald-500 to-teal-500 dark:from-emerald-400 dark:to-teal-400">Credenciales</span>
                            </h1>
                            <p class="text-slate-400 dark:text-slate-500 text-[10px] font-bold uppercase tracking-[0.2em] italic">Gestión centralizada de accesos seguros AES-256</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <button @click="openCreateModal" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-emerald-600/20 flex items-center gap-2 group">
                            <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                            Nueva Credencial
                        </button>

                        <div class="flex gap-2">
                             <div class="relative group min-w-[200px]">
                                 <select v-model="clienteId" class="w-full bg-white dark:bg-black/50 border border-slate-200 dark:border-white/5 rounded-2xl h-12 text-[10px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-200 focus:ring-brand-500 focus:border-emerald-500 transition-all appearance-none pl-6 pr-10 shadow-sm">
                                    <option value="">Todos los Clientes</option>
                                    <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                        {{ cliente.nombre_razon_social }}
                                    </option>
                                </select>
                                <svg class="absolute right-4 top-4 w-4 h-4 text-slate-400 dark:text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div class="relative group min-w-[240px]">
                                <input 
                                    v-model="search"
                                    type="text" 
                                    placeholder="BUSCAR ACCESOS..."
                                    class="w-full bg-white dark:bg-black/50 border border-slate-200 dark:border-white/5 rounded-2xl h-12 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-slate-200 focus:ring-brand-500 focus:border-emerald-500 transition-all pl-12 pr-6 placeholder-slate-400 dark:placeholder-slate-600 shadow-sm"
                                >
                                <svg class="absolute left-4 top-3.5 w-4 h-4 text-emerald-500/50 group-focus-within:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning Alert -->
                <div class="mb-12 p-6 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-brand-500/5 rounded-3xl border border-emerald-100 dark:border-emerald-500/10 flex items-start gap-6 animate-in fade-in slide-in-from-bottom-4 duration-700 delay-150">
                    <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-500 shrink-0">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide mb-1">Protocolo de Bóveda de Seguridad</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed max-w-4xl">
                            Todas las visualizaciones de contraseñas son registradas en el registro de auditoría con marca de tiempo y usuario. 
                            Las llaves de acceso están protegidas bajo encriptación industrial <span class="text-emerald-600 dark:text-slate-400/80">AES-256-CBC</span>.
                        </p>
                    </div>
                </div>

                <!-- Credentials Grid -->
                <div v-if="credenciales.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="(item, index) in credenciales.data" :key="item.id" 
                          class="group relative animate-in fade-in slide-in-from-bottom-8 duration-700"
                          :style="{ 'animation-delay': (index * 50) + 'ms' }">
                        
                        <div class="absolute -inset-0.5 bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-[2.5rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
                        
                        <div class="relative h-full bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] overflow-hidden backdrop-blur-xl group-hover:bg-slate-50 dark:group-hover:bg-slate-900/50 group-hover:border-brand-500/20 shadow-md dark:shadow-2xl transition-all duration-200">
                            <!-- Header Vinculación -->
                            <div class="px-8 py-4 bg-[var(--ui-surface)] dark:bg-white/5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center group-hover:bg-slate-50 dark:group-hover:bg-slate-500/5 transition-colors">
                                <Link :href="getOwnerLink(item)" class="flex items-center gap-2 group/link">
                                    <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">PROPIETARIO:</span>
                                    <span class="text-[9px] font-black text-emerald-600 dark:text-slate-400 group-hover/link:text-emerald-500 dark:group-hover/link:text-emerald-300 uppercase hover:underline decoration-emerald-500/20 truncate max-w-[150px]">
                                        {{ getOwnerName(item) }}
                                    </span>
                                </Link>
                                <div class="flex gap-1">
                                    <div class="w-1.5 h-1.5 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
                                </div>
                            </div>

                            <div class="p-8">
                                <div class="flex items-center gap-5 mb-8">
                                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 dark:bg-brand-500/5 border border-emerald-100 dark:border-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-slate-400 group-hover:bg-slate-500 group-hover:text-white transition-all duration-500">
                                        <svg v-if="item.host" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" /></svg>
                                        <svg v-else class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-wider truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ item.nombre }}</h3>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-0.5 truncate">{{ item.host || 'ACCESO LOCAL / DIRECTO' }}</p>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <!-- Usuario Row -->
                                    <div class="group/field p-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-brand-500/20 transition-all flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Identificador / Usuario</span>
                                            <span class="text-sm font-bold text-slate-800 dark:text-slate-200 tracking-tight">{{ item.usuario }}</span>
                                        </div>
                                        <button @click="copyToClipboard(item.usuario)" class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 hover:bg-slate-500 hover:text-white flex items-center justify-center transition-all opacity-0 group-hover/field:opacity-100 shadow-xl" title="Copiar Usuario">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                        </button>
                                    </div>

                                    <!-- Password Row -->
                                    <div class="group/field p-4 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-2xl border border-slate-200 dark:border-white/5 hover:border-brand-500/20 transition-all flex items-center justify-between relative overflow-hidden">
                                        <div v-if="revealedPasswords[item.id]" class="absolute inset-0 bg-brand-500/5 pointer-events-none"></div>
                                        <div class="flex flex-col relative z-10">
                                            <span class="text-[8px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Key / Contraseña</span>
                                            <span v-if="revealedPasswords[item.id]" class="text-sm font-mono font-black text-emerald-600 dark:text-slate-400 tracking-wider">
                                                {{ revealedPasswords[item.id] }}
                                            </span>
                                            <span v-else class="text-sm font-bold text-slate-300 dark:text-slate-700 tracking-[0.4em]">••••••••</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 relative z-10">
                                            <button 
                                                @click="revealPassword(item)" 
                                                :disabled="loadingReveal[item.id]"
                                                class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-500 hover:text-white flex items-center justify-center transition-all shadow-xl active:scale-90"
                                                :title="revealedPasswords[item.id] ? 'Ocultar' : 'Revelar'"
                                            >
                                                <svg v-if="loadingReveal[item.id]" class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <svg v-else-if="revealedPasswords[item.id]" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" /></svg>
                                                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            
                                            <button 
                                                v-if="revealedPasswords[item.id]" 
                                                @click="copyToClipboard(revealedPasswords[item.id])" 
                                                class="w-10 h-10 rounded-xl bg-brand-500 text-white flex items-center justify-center transition-all shadow-xl shadow-emerald-500/20 active:scale-90"
                                                title="Copiar Contraseña"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="item.notas" class="mt-6 p-4 bg-brand-500/5 rounded-2xl border border-brand-500/10 relative group/notes overflow-hidden">
                                     <div class="absolute inset-y-0 left-0 w-1 bg-brand-500/50"></div>
                                     <p class="text-[10px] text-brand-600 dark:text-brand-500 font-medium leading-relaxed italic line-clamp-2 transition-all group-hover/notes:line-clamp-none">"{{ item.notas }}"</p>
                                </div>
                            </div>

                            <div class="px-8 py-5 border-t border-slate-100 dark:border-white/5 bg-[var(--ui-surface)] dark:bg-white/5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                     <div class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-800"></div>
                                     <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-none">Act: {{ new Date(item.updated_at).toLocaleDateString() }}</span>
                                </div>
                                <Link :href="getOwnerLink(item)" class="text-[10px] font-black text-emerald-600 dark:text-slate-400 hover:text-emerald-500 dark:hover:text-emerald-300 uppercase flex items-center gap-2 transition-all">
                                    Gestionar
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-32 bg-white dark:bg-slate-800/30 rounded-[4rem] border border-dashed border-slate-200 dark:border-white/5 shadow-sm dark:shadow-none animate-in fade-in duration-700">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-3xl">
                        <svg class="w-10 h-10 text-slate-400 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white uppercase tracking-wider mb-2">No se encontraron credenciales</h3>
                    <p class="text-slate-400 dark:text-slate-500 font-medium max-w-sm mx-auto">Prueba con otro término o registra nuevos accesos en las fichas de Clientes o Pólizas.</p>
                </div>

                <!-- Pagination -->
                <div v-if="credenciales.links.length > 3" class="mt-16 flex justify-center">
                    <Pagination :pagination-data="credenciales" @page-change="handlePageChange" />
                </div>
            </div>
        </div>

        <!-- Create Modal (Premium Style) -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="showingCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xl">
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[3rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] w-full max-w-2xl overflow-hidden animate-in zoom-in-95 duration-200">
                    <div class="p-8 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-gradient-to-r from-emerald-500/5 to-transparent">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center text-emerald-600 dark:text-slate-400 border border-emerald-100 dark:border-emerald-500/20">
                                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wide">Nueva Credencial de Bóveda</h3>
                        </div>
                        <button @click="showingCreateModal = false" class="w-10 h-10 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 flex items-center justify-center transition-colors text-slate-400 dark:text-slate-500">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-10">
                        <div class="space-y-6">
                            <!-- Selección de Propietario -->
                            <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-3xl border border-slate-200 dark:border-white/5">
                                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 px-1 italic">Vinculación de Propietario</label>
                                
                                <div class="flex p-1 bg-slate-200 dark:bg-slate-800 rounded-2xl mb-6 border border-slate-200 dark:border-white/5">
                                    <button type="button" 
                                        @click="form.credentialable_type = 'App\\Models\\Cliente'"
                                        :class="form.credentialable_type === 'App\\Models\\Cliente' ? 'bg-emerald-600 text-white shadow-xl' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                        class="flex-1 py-3 rounded-xl text-[10px] font-black uppercase tracking-wide transition-all flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                        Cliente
                                    </button>
                                    <button type="button" 
                                        @click="form.credentialable_type = 'App\\Models\\PolizaServicio'"
                                        :class="form.credentialable_type === 'App\\Models\\PolizaServicio' ? 'bg-emerald-600 text-white shadow-xl' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                        class="flex-1 py-3 rounded-xl text-[10px] font-black uppercase tracking-wide transition-all flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        Póliza
                                    </button>
                                </div>

                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-emerald-500/50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                    <select v-model="form.credentialable_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl h-14 pl-14 text-xs font-black uppercase tracking-wide text-slate-800 dark:text-white focus:ring-brand-500 transition-all appearance-none cursor-pointer shadow-inner pr-10" required>
                                        <option value="" disabled>SELECCIONAR DESTINO...</option>
                                        <template v-if="form.credentialable_type === 'App\\Models\\Cliente'">
                                            <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_razon_social }}</option>
                                        </template>
                                        <template v-else>
                                            <option v-for="p in polizas" :key="p.id" :value="p.id">{{ p.folio }} - {{ p.nombre }}</option>
                                        </template>
                                    </select>
                                    <svg class="absolute right-4 top-5 w-4 h-4 text-slate-400 dark:text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>

                            <!-- Campos de Acceso -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Nombre Descriptivo</label>
                                    <input v-model="form.nombre" type="text" placeholder="Ej: PANEL CONTROL WEB" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-2xl h-14 px-6 text-xs font-bold text-slate-800 dark:text-white focus:ring-brand-500 appearance-none shadow-inner" required />
                                </div>
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Usuario / Login</label>
                                    <input v-model="form.usuario" type="text" placeholder="ADMIN_SYSTEM" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-2xl h-14 px-6 text-xs font-bold text-slate-800 dark:text-white focus:ring-brand-500 appearance-none shadow-inner" required />
                                </div>
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Contraseña de Acceso</label>
                                    <div class="relative group">
                                        <input v-model="form.password" type="text" placeholder="SECRETO_2025" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-2xl h-14 pl-6 pr-14 text-xs font-bold text-emerald-600 dark:text-slate-400 focus:ring-brand-500 appearance-none shadow-inner" required />
                                        <div class="absolute right-4 top-4 text-emerald-500/30 group-focus-within:text-emerald-500 transition-colors">
                                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-2 space-y-3">
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Host / IP</label>
                                        <input v-model="form.host" type="text" placeholder="192.168.1..." class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-2xl h-14 px-6 text-xs font-bold text-slate-800 dark:text-white focus:ring-brand-500 appearance-none shadow-inner" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Puerto</label>
                                        <input v-model="form.puerto" type="text" placeholder="8080" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-2xl h-14 px-4 text-center text-xs font-bold text-slate-800 dark:text-white focus:ring-brand-500 appearance-none shadow-inner" />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide ml-1">Notas de Procedimiento</label>
                                <textarea v-model="form.notas" rows="3" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-200 dark:border-white/5 rounded-3xl p-6 text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-brand-500 transition-all shadow-inner" placeholder="Escribe instrucciones adicionales para este acceso..."></textarea>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="mt-12 flex gap-4">
                            <button type="button" @click="showingCreateModal = false" class="flex-1 h-14 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing" class="flex-[2] h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-emerald-600/20 flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50">
                                 <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                 <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                 Sellar y Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>

<style scoped>
.shadow-3xl {
    shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.4);
}
</style>
