<template>
  <AppLayout>
    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
            <div class="flex justify-between items-center mb-6">
              <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Base de Conocimiento</h1>
              <Link
                v-if="$can('create kb') || $can('admin') || $can('super-admin')"
                :href="route('soporte.kb.create')"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
              >
                Nuevo Artículo
              </Link>
            </div>

            <!-- Buscador -->
            <div class="mb-6">
              <input
                type="text"
                v-model="search"
                placeholder="Buscar artículos..."
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>

            <!-- Listado de Artículos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="articulo in articulos.data" :key="articulo.id" class="border rounded-lg p-4 hover:shadow-lg transition">
                    <h3 class="font-bold text-lg mb-2 text-indigo-600">
                        <Link :href="route('soporte.kb.show', { articulo: articulo.id })">{{ articulo.titulo }}</Link>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-3">{{ articulo.resumen || 'Sin resumen' }}</p>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ articulo.categoria?.nombre || 'General' }}</span>
                        <span>{{ new Date(articulo.created_at).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
            
            <div v-if="articulos.data.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
                No se encontraron artículos.
            </div>

            <!-- Paginación -->
            <!-- (Simplificada) -->
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  articulos: Object,
  filtros: Object,
});

const search = ref(props.filtros.buscar || '');

// Debounce search
let timeout;
watch(search, (value) => {
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    router.get(route('soporte.kb.index'), { buscar: value }, { preserveState: true, replace: true });
  }, 300);
});
</script>
