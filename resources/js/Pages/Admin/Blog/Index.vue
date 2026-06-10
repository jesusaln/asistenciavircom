<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import Swal from '@/Utils/Swal';
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

const deletePost = async (post) => {
  const result = await Swal.fire({
    title: 'Eliminar artículo',
    text: '¿Estás seguro de eliminar este artículo?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  });

  if (result.isConfirmed) {
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
      <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-xl sm:rounded border border-slate-100 dark:border-slate-700">
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 flex justify-between items-center">
          <div class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl bg-blue-600 dark:bg-blue-800 flex items-center justify-center shadow-xl text-white text-xl">
              <FontAwesomeIcon icon="blog" />
            </div>
            <div>
              <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Blog Corporativo</h2>
              <p class="text-sm text-slate-500 dark:text-slate-200">Administra tus artículos y noticias</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Link :href="route('admin.blog.create')" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm">
                <FontAwesomeIcon icon="plus" />
                Nueva Entrada
            </Link>
          </div>
        </div>

        <!-- Tabla -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Artículo</th>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Categoría</th>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Estado</th>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Fecha</th>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Visitas</th>
                <th class="px-6 py-4 text-xs font-semibold text-slate-500 dark:text-slate-200 uppercase">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="post in posts" :key="post.id" class="hover:bg-white dark:hover:bg-slate-700 transition-colors">
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <img v-if="post.imagen_portada" :src="post.imagen_portada" class="w-10 h-10 rounded-xl object-cover border dark:border-slate-700 bg-slate-100 dark:bg-slate-700">
                    <div v-else class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-500">
                        <FontAwesomeIcon icon="image" />
                    </div>
                    <div>
                      <div class="font-semibold text-slate-900 dark:text-slate-100">{{ post.titulo }}</div>
                      <div class="text-xs text-slate-500 dark:text-slate-400 truncate w-48">{{ post.resumen }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 py-1 rounded-full bg-blue-50 dark:bg-sky-900/20/40 text-sky-800 dark:text-sky-200 dark:text-blue-300 text-xs font-medium">
                    {{ post.categoria || 'Sin categoría' }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span :class="post.status === 'published' ? 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' : 'bg-brand-50 dark:bg-brand-900/20/40 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300'" class="px-2 py-1 rounded-xl text-xs font-medium">
                    {{ post.status === 'published' ? 'Publicado' : 'Borrador' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-slate-500 dark:text-slate-200">
                  {{ formatDate(post.publicado_at || post.created_at) }}
                </td>
                <td class="px-6 py-4 font-medium">
                  <div class="flex items-center gap-1 text-slate-500 dark:text-slate-200">
                    <FontAwesomeIcon icon="eye" class="text-slate-400 dark:text-slate-500" />
                    {{ post.visitas }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-2">
                    <Link :href="route('admin.blog.edit', post.id)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-blue-900/40 rounded-xl transition-colors" title="Editar">
                      <FontAwesomeIcon icon="edit" />
                    </Link>
                    <button @click="deletePost(post)" class="p-2 text-rose-600 dark:text-rose-400 hover:bg-slate-50 dark:hover:bg-rose-900/40 rounded-xl transition-colors" title="Eliminar">
                      <FontAwesomeIcon icon="trash" />
                    </button>
                    <a v-if="post.status === 'published'" :href="route('public.blog.show', post.slug)" target="_blank" class="p-2 text-slate-500 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-colors" title="Ver">
                      <FontAwesomeIcon icon="external-link-alt" />
                    </a>
                  </div>
                </td>
              </tr>
              <tr v-if="posts.length === 0">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <FontAwesomeIcon icon="inbox" class="text-5xl text-slate-200 dark:text-slate-700 mb-4" />
                    <p class="text-slate-500 dark:text-slate-400 font-medium">No hay artículos creados aún</p>
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
