<template>
  <AppLayout>
    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Editar Artículo KB</h1>

            <form @submit.prevent="submit" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Título</label>
                <input v-model="form.titulo" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <div class="flex gap-2 mt-1">
                    <select v-model="form.categoria_id" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                       <option value="">Sin categoría</option>
                       <option v-for="cat in listaCategorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                    <button 
                        type="button" 
                        @click="showCategoryModal = true"
                        class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 text-gray-600 dark:text-gray-300 border border-gray-300 transition-colors"
                        title="Nueva Categoría"
                    >
                        ❤️
                    </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Contenido</label>
                <textarea v-model="form.contenido" rows="10" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Soporta HTML básico o Texto.</p>
              </div>

              <div class="flex items-center">
                 <input type="checkbox" v-model="form.es_publico" id="publico" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                 <label for="publico" class="ml-2 block text-sm text-gray-900 dark:text-white">Es público (visible para todos)</label>
              </div>

              <div class="flex justify-end gap-2">
                <button type="button" @click="$inertia.visit(route('soporte.kb.show', { articulo: articulo.id }))" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">Cancelar</button>
                <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Actualizar Artículo</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
        <SimpleCategoryForm 
            @close="showCategoryModal = false" 
            @created="agregarCategoriaNueva"
        />
    </Modal>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
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
