<template>
  <AppLayout>
    <div class="min-h-screen bg-slate-950 text-slate-200">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
          <div class="flex items-center gap-6">
            <div class="w-14 h-14 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-600/20">
              <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            </div>
            <div>
              <h1 class="text-3xl font-black text-white uppercase tracking-tighter leading-none mb-1">Nuevo <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-purple-400">Artículo</span></h1>
              <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest italic">Expande la base de conocimiento técnica</p>
            </div>
          </div>
          <Link :href="route('soporte.kb.index')" class="text-xs font-black uppercase tracking-widest text-slate-500 hover:text-white transition-colors">Cancelar</Link>
        </div>

        <!-- Form Card -->
        <div class="bg-slate-900/40 border border-white/5 rounded-[2.5rem] p-8 md:p-12 backdrop-blur-xl shadow-2xl animate-in fade-in slide-in-from-bottom-4 duration-700">
          <form @submit.prevent="submit" class="space-y-8">
            <!-- Título -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Título del Artículo</label>
              <input 
                v-model="form.titulo" 
                type="text" 
                placeholder="Ej: Cómo configurar una cuenta de correo en Outlook..."
                class="w-full px-6 py-4 bg-slate-800/50 border border-white/5 rounded-2xl text-white placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium" 
                required 
              />
            </div>

            <!-- Categoría -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Clasificación / Categoría</label>
              <div class="flex gap-3">
                <select 
                  v-model="form.categoria_id" 
                  class="flex-1 px-6 py-4 bg-slate-800/50 border border-white/5 rounded-2xl text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium appearance-none"
                >
                  <option value="">Sin categoría específica</option>
                  <option v-for="cat in listaCategorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                </select>
                <button 
                  type="button" 
                  @click="showCategoryModal = true"
                  class="w-14 h-14 bg-slate-800 border border-white/5 rounded-2xl flex items-center justify-center text-slate-400 hover:text-white hover:border-indigo-500/50 transition-all group"
                  title="Nueva Categoría"
                >
                  <svg class="w-6 h-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                </button>
              </div>
            </div>

            <!-- Contenido -->
            <div class="space-y-3">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Contenido Técnico (Soporta HTML)</label>
              <textarea 
                v-model="form.contenido" 
                rows="12" 
                placeholder="Escribe aquí las instrucciones detalladas, pasos a seguir o explicaciones necesarias..."
                class="w-full px-6 py-6 bg-slate-800/50 border border-white/5 rounded-[2rem] text-white placeholder-slate-600 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium leading-relaxed" 
                required
              ></textarea>
              <div class="flex items-center gap-2 text-[10px] text-slate-600 font-bold uppercase tracking-widest ml-2">
                <svg class="w-3 h-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                Puedes usar etiquetas HTML para formatear el texto.
              </div>
            </div>

            <!-- Switches row -->
            <div class="flex items-center justify-between p-6 bg-slate-950/50 rounded-3xl border border-white/5">
              <div class="flex items-center gap-4">
                 <div class="relative inline-flex h-6 w-11 items-center rounded-full cursor-pointer transition-colors"
                      :class="form.publicado ? 'bg-indigo-600' : 'bg-slate-700'"
                      @click="form.publicado = !form.publicado">
                   <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                         :class="form.publicado ? 'translate-x-6' : 'translate-x-1'"></span>
                 </div>
                 <div>
                   <p class="text-xs font-black text-white uppercase tracking-widest">Estado Público</p>
                   <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Visible para todos los usuarios</p>
                 </div>
              </div>

              <button 
                type="submit" 
                :disabled="form.processing" 
                class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all shadow-lg shadow-indigo-600/20 flex items-center gap-3 disabled:opacity-50"
              >
                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                {{ form.processing ? 'Procesando...' : 'Publicar Artículo' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
      <div class="bg-slate-900 border border-white/10 rounded-[2.5rem] overflow-hidden">
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
  titulo: '',
  contenido: '',
  categoria_id: '',
  publicado: true,
});

const submit = () => {
    form.post(route('soporte.kb.store'));
};
</script>
