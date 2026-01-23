<!-- /resources/js/Pages/Categorias/Edit.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  categoria: {
    type: Object,
    required: true
  }
})

// Form data
const form = ref({
  nombre: props.categoria.nombre || '',
  descripcion: props.categoria.descripcion || '',
  estado: props.categoria.estado || 'activo'
})

// Estados
const loading = ref(false)

// Métodos
const submit = () => {
  loading.value = true

  router.put(route('categorias.update', props.categoria.id), form.value, {
    onSuccess: () => {
      notyf.success('Categoría actualizada correctamente')
      router.visit(route('categorias.index'))
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors)
      notyf.error('Error al actualizar la categoría')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}

const cancel = () => {
  router.visit(route('categorias.index'))
}
</script>

<template>
  <Head title="Editar Categoría" />

  <div class="min-h-screen bg-white dark:bg-slate-900 dark:bg-gray-900">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-gray-100">Editar Categoría</h1>
            <p class="text-gray-600 dark:text-gray-300 dark:text-gray-300 mt-1">Modifica la información de la categoría</p>
          </div>
          <button
            @click="cancel"
            class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Cancelar
          </button>
        </div>
      </div>

      <!-- Formulario -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:border-gray-700 p-8">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Nombre -->
          <div>
            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nombre de la Categoría *
            </label>
            <input
              id="nombre"
              v-model="form.nombre"
              type="text"
              required
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              placeholder="Ingresa el nombre de la categoría"
            />
          </div>

          <!-- Descripción -->
          <div>
            <label for="descripcion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Descripción
            </label>
            <textarea
              id="descripcion"
              v-model="form.descripcion"
              rows="4"
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
              placeholder="Ingresa una descripción opcional para la categoría"
            ></textarea>
          </div>

          <!-- Estado -->
          <div>
            <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Estado *
            </label>
            <select
              id="estado"
              v-model="form.estado"
              required
              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
            >
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
              Las categorías activas estarán disponibles para ser usadas en productos
            </p>
          </div>

          <!-- Información adicional -->
          <div class="bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-2">Información adicional</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Fecha de creación:</span>
                <span class="ml-2 text-gray-900 dark:text-white dark:text-gray-100">{{ new Date(props.categoria.created_at).toLocaleDateString('es-MX') }}</span>
              </div>
              <div>
                <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400">Última actualización:</span>
                <span class="ml-2 text-gray-900 dark:text-white dark:text-gray-100">{{ new Date(props.categoria.updated_at).toLocaleDateString('es-MX') }}</span>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex justify-end gap-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700">
            <button
              type="button"
              @click="cancel"
              :disabled="loading"
              class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Cancelar
            </button>
            <button
              type="submit"
              :disabled="loading"
              class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              {{ loading ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animaciones para el loading */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

