<template>
  <AppLayout>
    <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 transition-colors">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
          <div class="flex items-center gap-6">
            <div class="w-14 h-14 rounded-2xl bg-brand-500 flex items-center justify-center shadow-xl shadow-brand-500/20">
              <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </div>
            <div>
              <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide leading-none mb-1">Editar <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-500 to-brand-500 dark:from-brand-400 dark:to-orange-400">Artículo</span></h1>
              <p class="text-slate-500 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wide italic">Actualizando documentación técnica existente</p>
            </div>
          </div>
          <Link :href="route('soporte.kb.show', { articulo: articulo.id })" class="text-xs font-black uppercase tracking-wide text-slate-400 hover:text-slate-800 dark:hover:text-white transition-colors">Cancelar</Link>
        </div>

        <!-- Form Card -->
        <div class="bg-white dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2.5rem] p-8 md:p-12 backdrop-blur-xl shadow-md dark:shadow-2xl animate-in fade-in slide-in-from-bottom-4 duration-700">
          <form @submit.prevent="submit" class="space-y-6">
            <!-- Título -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Título del Artículo</label>
              <input 
                v-model="form.titulo" 
                type="text" 
                placeholder="Ej: Cómo configurar una cuenta de correo..."
                class="w-full px-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-2xl text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium" 
                required 
              />
            </div>

            <!-- Categoría -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Clasificación / Categoría</label>
              <div class="flex gap-3">
                <select 
                  v-model="form.categoria_id" 
                  class="flex-1 px-6 py-4 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-2xl text-slate-800 dark:text-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium appearance-none"
                >
                  <option value="">Sin categoría específica</option>
                  <option v-for="cat in listaCategorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                </select>
                <button 
                  type="button" 
                  @click="showCategoryModal = true"
                  class="w-14 h-14 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:border-brand-500/50 transition-all group"
                  title="Nueva Categoría"
                >
                  <svg class="w-10 h-10 group-hover:scale-105 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                </button>
              </div>
            </div>

            <!-- Contenido -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Contenido Técnico (Soporta HTML)</label>
              <textarea 
                v-model="form.contenido" 
                rows="12" 
                placeholder="Escribe aquí las instrucciones detalladas..."
                class="w-full px-6 py-6 bg-[var(--ui-surface)] dark:bg-slate-800/50 border border-slate-200 dark:border-white/5 rounded-[2rem] text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all font-medium leading-relaxed" 
                required
              ></textarea>
              <div class="flex items-center gap-2 text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide ml-2">
                <svg class="w-3 h-3 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                El contenido actualizado se reflejará inmediatamente en la base de datos.
              </div>
            </div>

            <!-- Switches row -->
            <div class="flex items-center justify-between p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-3xl border border-slate-200 dark:border-white/5">
              <div class="flex items-center gap-4">
                 <div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors"
                      :class="form.es_publico ? 'bg-amber-600' : 'bg-slate-300 dark:bg-slate-700'"
                      @click="form.es_publico = !form.es_publico">
                   <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                         :class="form.es_publico ? 'translate-x-6' : 'translate-x-1'"></span>
                 </div>
                 <div>
                   <p class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wide">Estado Público</p>
                   <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wide mt-0.5">Visible para todos los usuarios</p>
                 </div>
              </div>

              <div class="flex gap-4">
                <Link
                  :href="route('soporte.kb.show', { articulo: articulo.id })"
                  class="px-8 py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-200 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all border border-slate-200 dark:border-white/5"
                >
                  Cancelar
                </Link>
                <button 
                  type="submit" 
                  :disabled="form.processing" 
                  class="px-8 py-4 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-brand-600/20 flex items-center gap-2 disabled:opacity-50"
                >
                  <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  {{ form.processing ? 'Actualizando...' : 'Guardar Cambios' }}
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
      <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <SimpleCategoryForm 
            @close="showCategoryModal = false" 
            @created="agregarCategoriaNueva"
        />
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SimpleCategoryForm from '@/Components/Soporte/SimpleCategoryForm.vue';

const props = defineProps({
    articulo: Object,
    categorias: Array
});

const showCategoryModal = ref(false);
const listaCategorias = ref([...props.categorias]);

const agregarCategoriaNueva = (nuevaCategoria) => {
    listaCategorias.value.push(nuevaCategoria);
    form.categoria_id = nuevaCategoria.id;
    showCategoryModal.value = false;
};

const form = useForm({
  titulo: props.articulo.titulo,
  contenido: props.articulo.contenido,
  categoria_id: props.articulo.categoria_id,
  es_publico: !!props.articulo.publicado,
});

const submit = () => {
    form.put(route('soporte.kb.update', { articulo: props.articulo.id }));
};
</script>
