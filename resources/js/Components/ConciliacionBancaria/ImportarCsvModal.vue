<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-bold text-white flex items-center">
            <FontAwesomeIcon :icon="['fas', 'file-upload']" class="mr-3" />
            Importar Estado de Cuenta
          </h3>
          <button @click="$emit('close')" class="text-white/80 hover:text-white">
            <FontAwesomeIcon :icon="['fas', 'times']" class="h-6 w-6" />
          </button>
        </div>
      </div>

      <!-- Content -->
      <form @submit.prevent="importar" class="p-6">
        <!-- Selección de banco -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Banco (opcional)</label>
          <select v-model="form.banco" class="w-full rounded-lg border-gray-300">
            <option value="">Detectar automáticamente</option>
            <option v-for="banco in bancosSoportados" :key="banco" :value="banco">{{ banco }}</option>
          </select>
          <p class="text-xs text-gray-500 mt-1">El sistema intentará detectar el formato automáticamente</p>
        </div>

        <!-- Cuenta bancaria -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Cuenta Bancaria Destino</label>
          <select
            v-model="form.cuenta_bancaria_id"
            class="w-full rounded-lg border-gray-300"
          >
            <option value="">Sin vincular a cuenta</option>
            <option v-for="cuenta in cuentasDisponibles" :key="cuenta.id" :value="cuenta.id">
              {{ cuenta.nombre }} - {{ cuenta.banco }} (${{ formatMonto(cuenta.saldo_actual) }})
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">
            Vincula los movimientos a una cuenta para actualizar su saldo automáticamente
          </p>
        </div>

        <!-- Área de drop -->
        <div
          class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
          :class="dragover ? 'border-blue-500 bg-blue-50' : archivo ? 'border-green-500 bg-green-50' : 'border-gray-300'"
          @dragover.prevent="dragover = true"
          @dragleave.prevent="dragover = false"
          @drop.prevent="onDrop"
        >
          <div v-if="!archivo">
            <FontAwesomeIcon :icon="['fas', 'cloud-upload-alt']" class="h-12 w-12 text-gray-400 mb-4" />
            <p class="text-gray-600 mb-2">Arrastra tu archivo Excel o CSV aquí</p>
            <p class="text-gray-400 text-sm mb-4">o</p>
            <label class="px-4 py-2 bg-blue-600 text-white rounded-lg cursor-pointer hover:bg-blue-700">
              Seleccionar archivo
              <input type="file" accept=".csv,.txt,.xls,.xlsx" @change="onFileSelect" class="hidden" />
            </label>
          </div>
          <div v-else class="text-green-700">
            <FontAwesomeIcon :icon="['fas', 'file-excel']" class="h-12 w-12 mb-2" />
            <p class="font-medium">{{ archivo.name }}</p>
            <p class="text-sm text-gray-500">{{ formatSize(archivo.size) }}</p>
            <button type="button" @click="archivo = null" class="mt-2 text-red-600 text-sm hover:underline">
              Cambiar archivo
            </button>
          </div>
        </div>

        <!-- Formato aceptado -->
        <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
          <p class="font-medium mb-1">Formatos aceptados:</p>
          <ul class="list-disc list-inside space-y-1">
            <li><strong>Excel (.xls, .xlsx)</strong> - Recomendado para BBVA</li>
            <li>CSV (.csv) - Todos los bancos</li>
            <li>TXT (.txt) - Archivos de texto</li>
          </ul>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="$emit('close')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="!archivo || form.processing"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
          >
            <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
            Importar
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  bancosSoportados: { type: Array, default: () => ['BBVA', 'BANORTE', 'SANTANDER'] },
  cuentas: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const archivo = ref(null)
const dragover = ref(false)
const cuentasDisponibles = ref([])

const form = useForm({
  archivo: null,
  banco: '',
  cuenta_bancaria_id: '',
})

// Cargar cuentas bancarias
const cargarCuentas = async () => {
  if (props.cuentas && props.cuentas.length > 0) {
    cuentasDisponibles.value = props.cuentas
  } else {
    try {
      const response = await fetch(route('api.cuentas-bancarias.activas'))
      cuentasDisponibles.value = await response.json()
    } catch (error) {
      console.error('Error cargando cuentas:', error)
    }
  }
}

const onFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) {
    archivo.value = file
  }
}

const onDrop = (e) => {
  dragover.value = false
  const file = e.dataTransfer.files[0]
  const extensionesValidas = ['.csv', '.txt', '.xls', '.xlsx']
  const esValido = extensionesValidas.some(ext => file.name.toLowerCase().endsWith(ext))
  if (file && esValido) {
    archivo.value = file
  }
}

const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const importar = () => {
  if (!archivo.value) return

  form.archivo = archivo.value
  
  form.post(route('conciliacion.importar'), {
    forceFormData: true,
    onSuccess: () => {
      emit('close')
    },
  })
}

onMounted(() => {
  cargarCuentas()
})
</script>

