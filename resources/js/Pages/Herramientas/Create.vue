<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import CategoriaHerramientaModal from '@/Components/Modals/CategoriaHerramientaModal.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  categorias: { type: Array, default: () => [] },
})

const form = useForm({
  nombre: '',
  numero_serie: '',
  estado: 'disponible',
  descripcion: '',
  foto: null,
  categoria_id: '',
  vida_util_meses: '',
  costo_reemplazo: '',
  dias_para_mantenimiento: '',
  requiere_mantenimiento: false,
})

const fotoPreview = ref(null)
const categoriasList = ref([...(props.categorias || [])])
const showCategoriaModal = ref(false)

const handleFile = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
      Swal.fire({
        icon: 'error',
        title: 'Archivo inválido',
        text: 'Por favor selecciona un archivo de imagen válido'
      })
      return
    }

    // Validar tamaño (máximo 5MB)
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        icon: 'error',
        title: 'Imagen demasiado grande',
        text: 'La imagen es demasiado grande. El tamaño máximo es 5MB'
      })
      return
    }

    form.foto = file
    const reader = new FileReader()
    reader.onload = (ev) => { fotoPreview.value = ev.target.result }
    reader.readAsDataURL(file)
  }
}

const removeImage = () => {
  form.foto = null
  fotoPreview.value = null
}

const submit = () => {
  form.post(route('herramientas.store'), { forceFormData: true })
}

const openCategoriaModal = () => {
  showCategoriaModal.value = true
}

const closeCategoriaModal = () => {
  showCategoriaModal.value = false
}

const handleCategoriaCreated = (categoria) => {
  categoriasList.value.push(categoria)
  form.categoria_id = categoria.id
}

const handleCategoriaUpdated = (categoria) => {
  const index = categoriasList.value.findIndex(c => c.id === categoria.id)
  if (index !== -1) {
    categoriasList.value[index] = categoria
  }
  // Si la categoría editada es la seleccionada actualmente, mantener la selección
  if (form.categoria_id === categoria.id) {
    form.categoria_id = categoria.id
  }
}
</script>

<template>
  <Head title="Nueva Herramienta" />

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold text-slate-900">Nueva Herramienta</h1>
    <Link class="text-blue-600 hover:underline" :href="route('herramientas.index')">Volver</Link>
  </div>

  <form @submit.prevent="submit" class="bg-white shadow rounded p-6 space-y-4 max-w-2xl">
    <div>
      <label class="block text-sm font-medium text-gray-700">Categoría</label>
      <div class="flex gap-2 mt-1">
        <select v-model="form.categoria_id" class="w-full border rounded px-3 py-2">
          <option value="">Sin categoría</option>
          <option v-for="cat in categoriasList" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
        </select>
        <button type="button" @click="openCategoriaModal" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700">Gestionar</button>
      </div>
      <p v-if="!categoriasList.length" class="text-xs text-gray-500 mt-1">No hay categorías. Usa "Gestionar" para crear una nueva.</p>
      <div v-if="form.errors.categoria_id" class="text-red-600 text-sm">{{ form.errors.categoria_id }}</div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Nombre</label>
      <input v-model="form.nombre" type="text" class="mt-1 w-full border rounded px-3 py-2" required />
      <div v-if="form.errors.nombre" class="text-red-600 text-sm">{{ form.errors.nombre }}</div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Número de serie</label>
      <input v-model="form.numero_serie" type="text" class="mt-1 w-full border rounded px-3 py-2" />
      <div v-if="form.errors.numero_serie" class="text-red-600 text-sm">{{ form.errors.numero_serie }}</div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Estado</label>
      <select v-model="form.estado" class="mt-1 w-full border rounded px-3 py-2">
        <option value="disponible">Disponible</option>
        <option value="asignada">Asignada</option>
        <option value="mantenimiento">En mantenimiento</option>
        <option value="baja">De baja</option>
        <option value="perdida">Perdida</option>
      </select>
      <div v-if="form.errors.estado" class="text-red-600 text-sm">{{ form.errors.estado }}</div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Descripción</label>
      <textarea v-model="form.descripcion" rows="3" class="mt-1 w-full border rounded px-3 py-2"></textarea>
      <div v-if="form.errors.descripcion" class="text-red-600 text-sm">{{ form.errors.descripcion }}</div>
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Foto de la herramienta</label>
      <div class="flex flex-col items-center space-y-4">
        <!-- Vista previa de imagen -->
        <div v-if="fotoPreview" class="relative">
          <img :src="fotoPreview" class="w-48 h-48 object-cover rounded-lg border-2 border-gray-200" />
          <button
            @click="removeImage"
            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
            title="Eliminar imagen"
          >
            ×
          </button>
        </div>

        <!-- Área de carga de imagen -->
        <div v-else class="w-48 h-48 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-500 hover:border-gray-400 transition-colors">
          <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
          </svg>
          <span class="text-sm">Foto de la herramienta</span>
        </div>

        <!-- Botones de acción -->
        <div class="flex space-x-2">
          <label class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer">
            <span>Seleccionar imagen</span>
            <input @change="handleFile" accept="image/*" type="file" class="hidden" />
          </label>

          <label class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 cursor-pointer">
            <span>Capturar foto</span>
            <input @change="handleFile" accept="image/*" capture="environment" type="file" class="hidden" />
          </label>
        </div>

        <div v-if="form.errors.foto" class="text-red-600 text-sm text-center">{{ form.errors.foto }}</div>
      </div>
    </div>
    <div class="pt-2">
      <button :disabled="form.processing" type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">Guardar</button>
    </div>
  </form>

  <!-- Modal de gestión de categorías -->
  <CategoriaHerramientaModal
    :show="showCategoriaModal"
    :categorias="categoriasList"
    @close="closeCategoriaModal"
    @categoria-created="handleCategoriaCreated"
    @categoria-updated="handleCategoriaUpdated"
  />
</template>

