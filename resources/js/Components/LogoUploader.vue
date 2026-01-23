<!-- /resources/js/Components/LogoUploader.vue -->
<template>
  <div class="logo-uploader">
    <div class="mb-4">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ titulo }}</h3>
      <p class="text-sm text-gray-600 mb-4">{{ descripcion }}</p>
    </div>

    <!-- Logo actual -->
    <div v-if="logoActual" class="mb-6">
      <div class="flex items-center gap-4">
        <img :src="logoActual" :alt="altText" class="w-24 h-24 object-contain border border-gray-200 dark:border-slate-800 rounded-lg p-2" />
        <div>
          <p class="text-sm font-medium text-gray-700">Logo actual</p>
          <button @click="eliminarLogo" class="mt-1 px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700">
            Eliminar
          </button>
        </div>
      </div>
    </div>

    <!-- Área de subida -->
    <div
      class="border-2 border-dashed rounded-lg p-6 text-center transition-colors duration-200"
      :class="dragOver ? 'border-blue-400 bg-blue-50' : 'border-gray-300'"
      @dragover.prevent="dragOver = true"
      @dragleave.prevent="dragOver = false"
      @drop.prevent="handleDrop"
    >
      <input
        ref="fileInput"
        type="file"
        :accept="accept"
        @change="handleFileChange"
        class="hidden"
      />

      <div v-if="!archivoSeleccionado">
        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
          <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <div class="mt-4">
          <button @click="$refs.fileInput.click()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Seleccionar Archivo
          </button>
          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ textoAyuda }}</p>
        </div>
      </div>

      <!-- Archivo seleccionado -->
      <div v-else class="text-center">
        <svg class="mx-auto h-12 w-12 text-green-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
          <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ archivoSeleccionado.name }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatearTamanio(archivoSeleccionado.size) }}</p>

        <div class="mt-4 flex justify-center gap-2">
          <button @click="subirArchivo" :disabled="subiendo" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
            {{ subiendo ? 'Subiendo...' : 'Subir Archivo' }}
          </button>
          <button @click="cancelarSeleccion" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
            Cancelar
          </button>
        </div>
      </div>
    </div>

    <!-- Progreso de subida -->
    <div v-if="progreso > 0 && progreso < 100" class="mt-4">
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="{ width: progreso + '%' }"></div>
      </div>
      <p class="text-xs text-gray-600 mt-1">{{ progreso }}% completado</p>
    </div>

    <!-- Vista previa -->
    <div v-if="vistaPrevia" class="mt-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Vista previa:</p>
      <img :src="vistaPrevia" :alt="altText + ' preview'" class="w-24 h-24 object-contain border border-gray-200 dark:border-slate-800 rounded-lg p-2" />
    </div>

    <!-- Mensajes de error -->
    <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-sm text-red-600">{{ error }}</p>
    </div>

    <!-- Mensajes de éxito -->
    <div v-if="mensajeExito" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
      <p class="text-sm text-green-600">{{ mensajeExito }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps } from 'vue'
import { router } from '@inertiajs/vue3'

// Props
const props = defineProps({
  titulo: {
    type: String,
    default: 'Subir Logo'
  },
  descripcion: {
    type: String,
    default: 'Selecciona un archivo de imagen para subir como logo'
  },
  altText: {
    type: String,
    default: 'Logo'
  },
  accept: {
    type: String,
    default: 'image/*'
  },
  maxSize: {
    type: Number,
    default: 2 * 1024 * 1024 // 2MB
  },
  textoAyuda: {
    type: String,
    default: 'PNG, JPG, GIF hasta 2MB'
  },
  logoActual: {
    type: String,
    default: null
  },
  rutaSubida: {
    type: String,
    required: true
  },
  rutaEliminacion: {
    type: String,
    required: true
  }
})

// Estado
const archivoSeleccionado = ref(null)
const vistaPrevia = ref(null)
const dragOver = ref(false)
const subiendo = ref(false)
const progreso = ref(0)
const error = ref(null)
const mensajeExito = ref(null)

// Métodos
const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    validarArchivo(file)
  }
}

const handleDrop = (event) => {
  dragOver.value = false
  const files = event.dataTransfer.files
  if (files.length > 0) {
    validarArchivo(files[0])
  }
}

const validarArchivo = (file) => {
  error.value = null

  // Validar tamaño
  if (file.size > props.maxSize) {
    error.value = `El archivo es demasiado grande. Máximo ${formatearTamanio(props.maxSize)}`
    return
  }

  // Validar tipo
  if (!file.type.startsWith('image/')) {
    error.value = 'Solo se permiten archivos de imagen'
    return
  }

  archivoSeleccionado.value = file
  vistaPrevia.value = URL.createObjectURL(file)
}

const subirArchivo = () => {
  if (!archivoSeleccionado.value) return

  subiendo.value = true
  progreso.value = 0
  error.value = null
  mensajeExito.value = null

  const formData = new FormData()
  formData.append('logo', archivoSeleccionado.value)

  // Simular progreso
  const interval = setInterval(() => {
    progreso.value += 10
    if (progreso.value >= 90) {
      clearInterval(interval)
    }
  }, 100)

  fetch(props.rutaSubida, {
    method: 'POST',
    body: formData,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  })
  .then(response => {
    progreso.value = 100
    clearInterval(interval)

    if (response.ok) {
      return response.json()
    } else {
      throw new Error('Error al subir el archivo')
    }
  })
  .then(data => {
    subiendo.value = false
    mensajeExito.value = 'Archivo subido correctamente'

    // Emitir evento de éxito
    emit('subido', data)

    // Limpiar después de 3 segundos
    setTimeout(() => {
      archivoSeleccionado.value = null
      vistaPrevia.value = null
      mensajeExito.value = null
      progreso.value = 0
    }, 3000)
  })
  .catch(err => {
    subiendo.value = false
    progreso.value = 0
    error.value = err.message
    clearInterval(interval)
  })
}

const eliminarLogo = () => {
  if (confirm('¿Estás seguro de que deseas eliminar este logo?')) {
    router.delete(props.rutaEliminacion, {
      onSuccess: () => {
        mensajeExito.value = 'Logo eliminado correctamente'
        emit('eliminado')
        setTimeout(() => {
          mensajeExito.value = null
        }, 3000)
      },
      onError: () => {
        error.value = 'Error al eliminar el logo'
      }
    })
  }
}

const cancelarSeleccion = () => {
  archivoSeleccionado.value = null
  vistaPrevia.value = null
  progreso.value = 0
  error.value = null
  mensajeExito.value = null
}

const formatearTamanio = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Emits
const emit = defineEmits(['subido', 'eliminado'])
</script>

<style scoped>
.logo-uploader {
  /* Estilos adicionales si son necesarios */
}
</style>
