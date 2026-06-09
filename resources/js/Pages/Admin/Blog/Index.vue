<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  posts: Array
})

const notyf = new Notyf()

const deletePost = (post) => {
  if (confirm('¿Estás seguro de eliminar este artículo?')) {
    router.delete(route('admin.blog.destroy', post.id), {
      onSuccess: () => notyf.success('Artículo eliminado correctamente')
    })
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}
</script>

<template>
  <Head title="Gestión de Blog" />

  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-100 dark:border-gray-700">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 flex justify-between items-center">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 dark:bg-blue-800 flex items-center justify-center shadow-lg text-white text-xl">
              <FontAwesomeIcon icon="blog" />
            </div>
            <div>
              <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Blog Corporativo</h2>
              <p class="text-sm text-gray-600 dark:text-gray-300">Administra tus artículos y noticias</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Link :href="route('admin.blog.create')" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                <FontAwesomeIcon icon="plus" />
                Nueva Entrada
            </Link>
          </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-white dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
              <tr>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Artículo</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Categoría</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Estado</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Fecha</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Visitas</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
              <tr v-for="post in posts" :key="post.id" class="hover:bg-white dark:hover:bg-gray-700 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img v-if="post.imagen_portada" :src="post.imagen_portada" class="w-10 h-10 rounded object-cover border dark:border-gray-600 bg-gray-100 dark:bg-gray-700">
                    <div v-else class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500">
                        <FontAwesomeIcon icon="image" />
                    </div>
                    <div>
                      <div class="font-semibold text-gray-900 dark:text-gray-100">{{ post.titulo }}</div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 truncate w-48">{{ post.resumen }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-xs font-medium">
                    {{ post.categoria || 'Sin categoría' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span :class="post.status === 'published' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300'" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ post.status === 'published' ? 'Publicado' : 'Borrador' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                  {{ formatDate(post.publicado_at || post.created_at) }}
                </td>
                <td class="px-6 py-4 font-medium">
                  <div class="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                    <FontAwesomeIcon icon="eye" class="text-gray-400 dark:text-gray-500" />
                    {{ post.visitas }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <Link :href="route('admin.blog.edit', post.id)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/40 rounded-lg transition-colors" title="Editar">
                      <FontAwesomeIcon icon="edit" />
                    </Link>
                    <button @click="deletePost(post)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/40 rounded-lg transition-colors" title="Eliminar">
                      <FontAwesomeIcon icon="trash" />
                    </button>
                    <a v-if="post.status === 'published'" :href="route('public.blog.show', post.slug)" target="_blank" class="p-2 text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 rounded-lg transition-colors" title="Ver">
                      <FontAwesomeIcon icon="external-link-alt" />
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="posts.length === 0">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <FontAwesomeIcon icon="inbox" class="text-5xl text-gray-200 dark:text-gray-700 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No hay artículos creados aún</p>
                    <Link :href="route('admin.blog.create')" class="mt-4 text-blue-600 dark:text-blue-400 font-semibold hover:underline">
                        Crear el primer artículo
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
