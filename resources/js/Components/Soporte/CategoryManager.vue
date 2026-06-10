<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from '@/Utils/Swal';

const props = defineProps({
  categorias: {
    type: Array,
    default: () => [],
  },
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
      preserveScroll: true,
    });
  } else {
    form.post(route('soporte.categorias.store'), {
      onSuccess: () => closeModal(),
      preserveScroll: true,
    });
  }
};

const deleteCategory = async (cat) => {
  const result = await Swal.fire({
    title: 'Eliminar categoría',
    text: '¿Estás seguro de eliminar esta categoría?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  });

  if (result.isConfirmed) {
    form.delete(route('soporte.categorias.destroy', cat.id), {
        preserveScroll: true,
    });
  }
};

const getColorBadge = (color) => {
    const colors = {
        blue: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        green: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
        red: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
        yellow: 'bg-brand-500/10 text-brand-400 border-brand-500/20',
        indigo: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
        gray: 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        purple: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        pink: 'bg-pink-500/10 text-pink-400 border-pink-500/20',
        orange: 'bg-brand-500/10 text-orange-400 border-brand-500/20',
    };
    return colors[color] || 'bg-slate-500/10 text-slate-400 border-white/5';
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-end">
      <button @click="openModal()" class="px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-lg flex items-center gap-2 group active:scale-95">
        <svg class="w-4 h-4 group-hover:rotate-90 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
        Nueva Categoría
      </button>
    </div>

    <!-- Layout Matrix (Table alternative) -->
    <div class="bg-slate-950/40 border border-white/5 rounded-[2.5rem] overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-white/5">
          <tr>
            <th class="px-8 py-6 text-left text-[10px] font-black text-slate-500 uppercase tracking-wide italic">Categoría</th>
            <th class="px-8 py-6 text-center text-[10px] font-black text-slate-500 uppercase tracking-wide italic">SLA</th>
            <th class="px-8 py-6 text-center text-[10px] font-black text-slate-500 uppercase tracking-wide italic">Estilo</th>
            <th class="px-8 py-6 text-center text-[10px] font-black text-slate-500 uppercase tracking-wide italic">Estado</th>
            <th class="px-8 py-6 text-right text-[10px] font-black text-slate-500 uppercase tracking-wide italic">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
          <tr v-for="cat in categorias" :key="cat.id" class="group hover:bg-white/5 transition-colors">
            <td class="px-8 py-6">
              <div class="text-sm font-black text-white uppercase tracking-wider">{{ cat.nombre }}</div>
              <div class="text-[9px] text-slate-500 font-bold uppercase tracking-wide italic mt-1">{{ cat.descripcion }}</div>
            </td>
            <td class="px-8 py-6 text-center">
              <span class="text-sm font-black text-slate-300">{{ cat.sla_horas }}H</span>
            </td>
            <td class="px-8 py-6 text-center">
              <div :class="['inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-[9px] font-black uppercase tracking-wide', getColorBadge(cat.color)]">
                <font-awesome-icon :icon="cat.icono" />
                {{ cat.color }}
              </div>
            </td>
            <td class="px-8 py-6 text-center">
              <span :class="['px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-wide border', cat.activo ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20']">
                {{ cat.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-8 py-6 text-right">
                <div class="flex justify-end gap-3">
                    <button @click="openModal(cat)" class="w-8 h-8 rounded-xl bg-slate-900 border border-white/5 text-indigo-400 hover:text-white hover:bg-indigo-600 transition-all flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                    <button @click="deleteCategory(cat)" class="w-8 h-8 rounded-xl bg-slate-900 border border-white/5 text-rose-500 hover:text-white hover:bg-rose-600 transition-all flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </td>
          </tr>
          <tr v-if="categorias.length === 0">
              <td colspan="5" class="px-8 py-12 text-center text-[10px] font-black text-slate-600 uppercase tracking-wide italic">No se detectaron categorías activas</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Crear/Editar Premium -->
    <Modal :show="showModal" @close="closeModal" maxWidth="xl">
      <div class="bg-slate-900 border border-white/10 rounded-[3rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] overflow-hidden">
        <div class="p-8 border-b border-white/5 bg-gradient-to-r from-brand-500/10 to-transparent flex items-center justify-between">
            <h2 class="text-xl font-black text-white uppercase tracking-wide">{{ form.id ? 'Reconfigurar' : 'Nueva' }} Categoría</h2>
            <button @click="closeModal" class="text-slate-500 hover:text-white transition-colors">×</button>
        </div>
        
        <form @submit.prevent="submit" class="p-8 space-y-8">
          <div class="space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">Nombre</label>
                <input v-model="form.nombre" type="text" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-sm font-black uppercase text-white placeholder-slate-800 transition-all focus:border-brand-500/50" required />
            </div>
            
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">Descripción</label>
                <input v-model="form.descripcion" type="text" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase text-white placeholder-slate-800 transition-all focus:border-brand-500/50" />
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">SLA (Horas)</label>
                    <input v-model="form.sla_horas" type="number" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase text-white focus:border-brand-500/50" required />
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">Orden</label>
                    <input v-model="form.orden" type="number" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase text-white focus:border-brand-500/50" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">Icono (FA)</label>
                    <input v-model="form.icono" type="text" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black text-white focus:border-brand-500/50" required />
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide italic px-1">Gama Cromática</label>
                    <select v-model="form.color" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase text-white appearance-none cursor-pointer focus:border-brand-500/50">
                        <option v-for="c in ['blue','green','red','yellow','indigo','gray','purple','pink','orange']" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-4 p-5 bg-slate-950/40 border border-white/5 rounded-2xl cursor-pointer hover:border-emerald-500/30 transition-all">
                <input type="checkbox" v-model="form.activo" class="w-5 h-5 rounded-xl border-white/10 text-emerald-500 focus:ring-emerald-500/20 shadow-inner" />
                <span class="text-[10px] font-black text-white uppercase tracking-wide">Activo en Sistema</span>
            </label>
          </div>

          <div class="flex justify-end gap-4 pt-6 mt-6 border-t border-white/5">
            <button type="button" @click="closeModal" class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wide hover:text-white transition-colors">Abortar</button>
            <button type="submit" class="px-10 py-4 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl shadow-xl transition-all disabled:opacity-30 active:scale-95" :disabled="form.processing">
                {{ form.id ? 'Refactorizar' : 'Inicializar' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>
