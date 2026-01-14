<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b">
        <h3 class="text-xl font-semibold text-gray-900">Gestión de Categorías</h3>
        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Formulario de categoría -->
      <div class="p-6 border-b">
        <h4 class="text-lg font-medium text-gray-900 mb-4">
          {{ editingCategoria ? 'Editar Categoría' : 'Crear Nueva Categoría' }}
        </h4>
        <form @submit.prevent="submitCategoria" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nombre <span class="text-red-500">*</span>
              </label>
              <input
                v-model="categoriaForm.nombre"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="editingCategoria ? categoriaForm.nombre : 'Ej: Herramientas Eléctricas'"
                required
              />
              <div v-if="errors.nombre" class="mt-1 text-sm text-red-600">{{ errors.nombre }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
              <input
                v-model="categoriaForm.descripcion"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="editingCategoria ? categoriaForm.descripcion : 'Descripción opcional...'"
              />
              <div v-if="errors.descripcion" class="mt-1 text-sm text-red-600">{{ errors.descripcion }}</div>
            </div>
          </div>

          <div class="flex justify-between">
            <button
              v-if="editingCategoria"
              type="button"
              @click="cancelEdit"
              class="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancelar Edición
            </button>
            <div v-else></div>
            <button
              type="submit"
              :disabled="processing"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              <span v-if="processing" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ processing ? (editingCategoria ? 'Actualizando...' : 'Creando...') : (editingCategoria ? 'Actualizar' : 'Crear') }}
              </span>
              <span v-else>{{ editingCategoria ? 'Actualizar Categoría' : 'Crear Categoría' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Lista de categorías existentes -->
      <div class="p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Categorías Existentes</h4>
        <div v-if="categorias.length === 0" class="text-center py-8 text-gray-500">
          No hay categorías creadas aún
        </div>
        <div v-else class="space-y-3 max-h-96 overflow-y-auto">
          <div
            v-for="categoria in categorias"
            :key="categoria.id"
            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
          >
            <div class="flex-1">
              <div class="font-medium text-gray-900">{{ categoria.nombre }}</div>
              <div v-if="categoria.descripcion" class="text-sm text-gray-600">{{ categoria.descripcion }}</div>
            </div>
            <div class="flex items-center space-x-2">
              <span :class="categoria.activo ? 'text-green-600' : 'text-red-600'" class="text-sm">
                {{ categoria.activo ? 'Activa' : 'Inactiva' }}
              </span>
              <button
                @click="editCategoria(categoria)"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Editar
              </button>
              <button
                @click="toggleCategoria(categoria)"
                class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50"
              >
                {{ categoria.activo ? 'Desactivar' : 'Activar' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  categorias: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'categoria-created', 'categoria-updated'])

const processing = ref(false)
const errors = ref({})
const categoriasList = ref([])
const editingCategoria = ref(null)

const categoriaForm = reactive({
  nombre: '',
  descripcion: ''
})

// Cargar categorías cuando se abra el modal
onMounted(async () => {
  if (props.show) {
    await loadCategorias()
  }
})

// Observar cuando se muestra el modal
watch(() => props.show, async (newShow) => {
  if (newShow) {
    editingCategoria.value = null
    categoriaForm.nombre = ''
    categoriaForm.descripcion = ''
    await loadCategorias()
  }
})

const loadCategorias = async () => {
  try {
    const response = await axios.get('/herramientas/categorias')
    categoriasList.value = response.data
  } catch (error) {
    console.error('Error al cargar categorías:', error)
  }
}

const editCategoria = (categoria) => {
  editingCategoria.value = categoria
  categoriaForm.nombre = categoria.nombre
  categoriaForm.descripcion = categoria.descripcion || ''
}

const cancelEdit = () => {
  editingCategoria.value = null
  categoriaForm.nombre = ''
  categoriaForm.descripcion = ''
  errors.value = {}
}

const submitCategoria = async () => {
  processing.value = true
  errors.value = {}

  try {
    let response
    if (editingCategoria.value) {
      // Actualizar categoría existente
      response = await axios.put(`/herramientas/categorias/${editingCategoria.value.id}`, categoriaForm)
      emit('categoria-updated', response.data.categoria)

      // Actualizar la categoría en la lista local
      const index = categoriasList.value.findIndex(c => c.id === editingCategoria.value.id)
      if (index !== -1) {
        categoriasList.value[index] = response.data.categoria
      }

      cancelEdit()
    } else {
      // Crear nueva categoría
      response = await axios.post('/herramientas/categorias', categoriaForm)
      emit('categoria-created', response.data.categoria)

      // Limpiar formulario
      categoriaForm.nombre = ''
      categoriaForm.descripcion = ''

      // Recargar categorías
      await loadCategorias()
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al procesar categoría',
        text: error.response?.data?.message || 'Error al procesar la categoría'
      });
    }
  } finally {
    processing.value = false
  }
}

const toggleCategoria = async (categoria) => {
  try {
    const response = await axios.patch(`/herramientas/categorias/${categoria.id}/toggle`)
    emit('categoria-updated', response.data.categoria)

    // Actualizar la categoría en la lista local
    const index = categoriasList.value.findIndex(c => c.id === categoria.id)
    if (index !== -1) {
      categoriasList.value[index] = response.data.categoria
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error al cambiar estado',
      text: error.response?.data?.message || 'Error al cambiar el estado de la categoría'
    });
  }
}

const closeModal = () => {
  errors.value = {}
  editingCategoria.value = null
  categoriaForm.nombre = ''
  categoriaForm.descripcion = ''
  emit('close')
}
</script>

