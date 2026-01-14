<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ articulo.titulo }}</h1>
            
            <div class="flex items-center text-sm text-gray-500 mb-6 space-x-4">
               <span>Categoría: {{ articulo.categoria?.nombre }}</span>
               <span>Autor: {{ articulo.autor?.name }}</span>
               <span>Actualizado: {{ new Date(articulo.updated_at).toLocaleDateString() }}</span>
            </div>

            <div class="prose max-w-none text-gray-800" v-html="articulo.contenido"></div>

            <div class="mt-8 pt-6 border-t border-gray-200">
               <h3 class="text-lg font-medium text-gray-900">¿Fue útil este artículo?</h3>
               <div class="mt-2 flex space-x-4">
                  <button @click="votar(true)" class="text-green-600 hover:text-green-800">👍 Sí ({{ articulo.votos_positivos }})</button>
                  <button @click="votar(false)" class="text-red-600 hover:text-red-800">👎 No ({{ articulo.votos_negativos }})</button>
               </div>
            </div>
            
            <div class="mt-6">
                <Link :href="route('soporte.kb.index')" class="text-indigo-600 hover:text-indigo-900">← Volver a la lista</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
  articulo: Object,
});

const votar = (esPositivo) => {
    router.post(route('soporte.kb.votar', { articulo: props.articulo.id }), { es_positivo: esPositivo });
};
</script>
