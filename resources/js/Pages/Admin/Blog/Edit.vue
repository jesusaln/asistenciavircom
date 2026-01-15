<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  post: Object
})

const notyf = new Notyf()

const form = useForm({
  titulo: props.post.titulo,
  resumen: props.post.resumen,
  contenido: props.post.contenido,
  categoria: props.post.categoria,
  status: props.post.status,
  imagen_portada: props.post.imagen_portada,
})

const submit = () => {
  form.put(route('admin.blog.update', props.post.id), {
    onSuccess: () => notyf.success('Artículo actualizado con éxito')
  })
}
</script>

<template>
  <Head title="Editar Artículo" />

  <div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg border border-gray-100">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 flex justify-between items-center">
          <div class="flex items-center gap-4">
            <Link :href="route('admin.blog.index')" class="text-gray-400 hover:text-gray-600 transition-colors">
                <FontAwesomeIcon icon="arrow-left" />
            </Link>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Editar Artículo</h2>
              <p class="text-xs text-gray-600">ID: {{ post.id }} - Modificando contenido existente</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span :class="post.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'" class="px-3 py-1 rounded-full text-xs font-bold uppercase">
                {{ post.status === 'published' ? 'Publicado' : 'Borrador' }}
            </span>
          </div>
        </div>

        <form @submit.prevent="submit" class="p-8 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Título del Artículo</label>
              <input v-model="form.titulo" type="text" placeholder="Ej: Las mejores cámaras de seguridad para 2024" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">Categoría</label>
              <select v-model="form.categoria" class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                <option value="">Seleccionar...</option>
                <option value="Seguridad">Seguridad</option>
                <option value="Tecnología">Tecnología</option>
                <option value="Redes">Redes</option>
                <option value="Soporte">Soporte</option>
                <option value="Noticias">Noticias</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">URL Imagen Portada</label>
              <input v-model="form.imagen_portada" type="text" placeholder="https://..." class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Resumen / Intro</label>
              <textarea v-model="form.resumen" rows="2" placeholder="Un breve resumen que aparecerá en los listados..." class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-semibold text-gray-700 mb-1">Contenido (HTML Soportado)</label>
              <textarea v-model="form.contenido" rows="12" placeholder="Escribe aquí el cuerpo del artículo..." class="w-full border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 font-sans" required></textarea>
            </div>

            <div class="flex items-center gap-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Estado:</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="form.status" value="draft" class="text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="text-sm text-gray-600">Borrador</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" v-model="form.status" value="published" class="text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="text-sm text-gray-600">Publicado</span>
                    </label>
                </div>
            </div>
          </div>

          <div class="flex justify-end pt-6 border-t border-gray-100 gap-4">
            <a v-if="post.status === 'published'" :href="route('public.blog.show', post.slug)" target="_blank" class="px-6 py-3 rounded-xl font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 transition-all flex items-center gap-2">
                <FontAwesomeIcon icon="eye" />
                Ver Publicación
            </a>
            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center gap-2 shadow-lg shadow-blue-200 disabled:opacity-50">
                <FontAwesomeIcon icon="save" />
                {{ form.processing ? 'Actualizando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
