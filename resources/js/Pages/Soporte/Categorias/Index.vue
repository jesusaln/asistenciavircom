<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';

const props = defineProps({
  categorias: Array,
});

const showModal = ref(false);
const form = useForm({
    id: null,
    nombre: '',
    descripcion: '',
    sla_horas: 24,
    orden: 0,
    icono: 'tag',
    color: 'blue',
    activo: true,
    consume_poliza: true,
});

const openModal = (cat = null) => {
    if (cat) {
        form.id = cat.id;
        form.nombre = cat.nombre;
        form.descripcion = cat.descripcion;
        form.sla_horas = cat.sla_horas;
        form.orden = cat.orden;
        form.icono = cat.icono;
        form.color = cat.color;
        form.activo = !!cat.activo;
        form.consume_poliza = !!cat.consume_poliza;
    } else {
        form.reset();
        form.id = null;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (form.id) {
        form.put(route('soporte.categorias.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('soporte.categorias.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm('¿Estás seguro de eliminar esta categoría?')) {
        form.delete(route('soporte.categorias.destroy', cat.id));
    }
};

const getColorBadge = (color) => {
    const colors = {
        blue: 'bg-brand-500/10 text-blue-400 border-blue-500/20',
        green: 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
        red: 'bg-brand-500/10 text-rose-400 border-rose-500/20',
        yellow: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        indigo: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        gray: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    };
    return colors[color] || 'bg-slate-500/10 text-slate-400 border-white/5';
};
</script>

<template>
    <AppLayout title="Categorías de Soporte">
        <Head title="Gestión de Categorías" />

        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 px-4 sm:px-6 lg:px-8 transition-colors">
            <div class="max-w-[1400px] mx-auto">
                
                <!-- Header -->
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-2xl backdrop-blur-xl">
                                <svg class="w-10 h-10 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter mb-1 uppercase">Taxonomía de <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 to-purple-500 dark:from-indigo-400 dark:to-purple-400">Servicio</span></h1>
                            <p class="text-slate-400 dark:text-slate-500 text-sm font-bold uppercase tracking-[0.2em] italic">Estructura categórica de soporte técnico</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <Link :href="route('soporte.dashboard')" class="px-6 py-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 hover:border-brand-500 dark:hover:border-white/20 text-slate-500 dark:text-slate-200 text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-md dark:shadow-xl">
                            Dashboard
                        </Link>
                        <button @click="openModal()" class="px-6 py-3 bg-sky-600 hover:bg-sky-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-indigo-600/20 flex items-center gap-2 group">
                            <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                            Nueva Categoría
                        </button>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] shadow-md dark:shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-8 duration-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-white/5">
                                    <th class="px-8 py-6 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Categoría & Descripción</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">SLA Operativo</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Visual Identity</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Volumetría</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Consumo Póliza</th>
                                    <th class="px-8 py-6 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Estado Solar</th>
                                    <th class="px-8 py-6 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Gestión</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr 
                                    v-for="(cat, idx) in categorias" 
                                    :key="cat.id" 
                                    class="group hover:bg-slate-50 dark:hover:bg-white/5 transition-all border-l-[6px] border-transparent hover:border-brand-500 animate-in fade-in slide-in-from-left-4"
                                    :style="{ 'animation-delay': (idx * 50) + 'ms' }"
                                >
                                    <td class="px-8 py-8">
                                        <div class="text-lg font-black text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors uppercase tracking-wider">{{ cat.nombre }}</div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-1 italic">{{ cat.descripcion || 'Sin descripción orbital' }}</div>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span class="text-xl font-black text-slate-800 dark:text-white tracking-tighter">{{ cat.sla_horas }}</span>
                                        <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 ml-1">HRS</span>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <div :class="['inline-flex items-center gap-2 px-4 py-2 rounded-xl border font-black text-[9px] uppercase tracking-wide transition-all group-hover:scale-105', getColorBadge(cat.color)]">
                                            <font-awesome-icon :icon="cat.icono" />
                                            {{ cat.color }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <div class="text-sm font-black text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-950/50 w-10 h-10 rounded-xl flex items-center justify-center mx-auto border border-slate-200 dark:border-white/5">{{ cat.tickets_count }}</div>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <span :class="[
                                            'px-4 py-1.5 text-[9px] font-black rounded-full uppercase tracking-wide border',
                                            cat.consume_poliza 
                                                ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-500/20' 
                                                : 'bg-slate-100 dark:bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/10'
                                        ]">
                                            {{ cat.consume_poliza ? 'Descuenta' : 'Sin Cargo' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-8 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <div :class="['w-2 h-2 rounded-full', cat.activo ? 'bg-brand-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]' : 'bg-brand-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]']"></div>
                                            <span :class="['text-[9px] font-black uppercase tracking-wide', cat.activo ? 'text-emerald-600 dark:text-emerald-500' : 'text-rose-600 dark:text-rose-500']">
                                                {{ cat.activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-8 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button @click="openModal(cat)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-white/5 hover:border-brand-500/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md dark:shadow-xl">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button @click="deleteCategory(cat)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-white/5 hover:border-brand-500/50 text-rose-600 dark:text-rose-500 flex items-center justify-center transition-all hover:scale-105 active:scale-95 shadow-md dark:shadow-xl">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[3rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="p-10 border-b border-slate-200 dark:border-white/5 flex items-center justify-between bg-gradient-to-r from-indigo-500/5 to-transparent uppercase">
                    <div class="flex items-center gap-5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-500 border border-indigo-100 dark:border-indigo-500/20">
                             <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <div>
                             <h2 class="text-xl font-black text-slate-800 dark:text-white tracking-tighter">{{ form.id ? 'Reconfigurar' : 'Nueva' }} Categoría</h2>
                             <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-wide italic tracking-[0.2em] mt-1">Sintonización de parámetros de servicio</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="w-10 h-10 rounded-xl hover:bg-slate-100 dark:hover:bg-white/5 flex items-center justify-center transition-colors text-slate-400 dark:text-slate-500">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-10 space-y-6">
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">Nombre de Categoría *</label>
                                <input v-model="form.nombre" type="text" required class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-sm font-black uppercase text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all focus:border-brand-500/50 shadow-inner" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">Icono (FontAwesome) *</label>
                                <div class="relative">
                                    <input v-model="form.icono" type="text" placeholder="ej. users" required class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-sm font-black text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all focus:border-brand-500/50 shadow-inner" />
                                    <div class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/5 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        <font-awesome-icon :icon="form.icono || 'tag'" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">Descripción / Propósito</label>
                            <input v-model="form.descripcion" type="text" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-sm font-black text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all focus:border-brand-500/50 shadow-inner" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">SLA (Horas) *</label>
                                <input v-model="form.sla_horas" type="number" required class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-sm font-black text-slate-800 dark:text-white transition-all focus:border-brand-500/50 shadow-inner" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">Ordenación</label>
                                <input v-model="form.orden" type="number" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-sm font-black text-slate-800 dark:text-white transition-all focus:border-brand-500/50 shadow-inner" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide px-1 italic">Color Visual Identity</label>
                                <select v-model="form.color" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase text-slate-800 dark:text-white appearance-none cursor-pointer focus:border-brand-500/50 shadow-inner">
                                    <option value="blue">Azul</option>
                                    <option value="green">Verde</option>
                                    <option value="red">Rojo</option>
                                    <option value="yellow">Amarillo</option>
                                    <option value="indigo">Índigo</option>
                                    <option value="gray">Gris</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-6 pt-6">
                            <label class="flex items-center gap-4 p-5 bg-[var(--ui-surface)] dark:bg-slate-950/40 border border-slate-200 dark:border-white/5 rounded-2xl cursor-pointer hover:border-brand-500/30 transition-all flex-1">
                                <input type="checkbox" v-model="form.activo" class="w-4 h-4 rounded-xl border-slate-200 dark:border-white/10 text-emerald-500 focus:ring-brand-500/20" />
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-wide">Activo</span>
                                    <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Visibilidad Global</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-4 p-5 bg-[var(--ui-surface)] dark:bg-slate-950/40 border border-slate-200 dark:border-white/5 rounded-2xl cursor-pointer hover:border-brand-500/30 transition-all flex-1">
                                <input type="checkbox" v-model="form.consume_poliza" class="w-4 h-4 rounded-xl border-slate-200 dark:border-white/10 text-indigo-500 focus:ring-brand-500/20" />
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-wide">Descontar de Póliza</span>
                                    <span class="text-[8px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Afectación de Inventario</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-8 border-t border-slate-200 dark:border-white/5 uppercase">
                        <button type="button" @click="closeModal" class="px-8 py-4 text-[10px] font-black text-slate-400 dark:text-slate-500 hover:text-slate-800 dark:hover:text-white transition-colors">Cancelar</button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-10 py-4 bg-sky-600 hover:bg-sky-700 text-white text-[10px] font-black tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-indigo-600/20 disabled:opacity-30 active:scale-95"
                        >
                            {{ form.processing ? 'Sincronizando...' : 'Guardar Configuración' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
