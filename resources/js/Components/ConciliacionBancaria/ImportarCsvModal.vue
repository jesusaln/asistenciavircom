<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-[var(--ui-surface)] text-[var(--ui-text)] rounded-3xl shadow-[var(--ui-shadow)] w-full max-w-lg mx-4 overflow-hidden border border-[var(--ui-border)]">
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
          <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">Banco (opcional)</label>
          <select v-model="form.banco" class="w-full rounded-xl border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-text)]">
            <option value="">Detectar automáticamente</option>
            <option v-for="banco in bancosSoportados" :key="banco" :value="banco">{{ banco }}</option>
          </select>
          <p class="text-xs text-[var(--ui-text-soft)] mt-1">El sistema intentará detectar el formato automáticamente</p>
        </div>

        <!-- Cuenta bancaria -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-2">Cuenta Bancaria Destino</label>
          <select
            v-model="form.cuenta_bancaria_id"
            class="w-full rounded-xl border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-text)]"
          >
            <option value="">Sin vincular a cuenta</option>
            <option v-for="cuenta in cuentasDisponibles" :key="cuenta.id" :value="cuenta.id">
              {{ cuenta.nombre }} - {{ cuenta.banco }} (${{ formatMonto(cuenta.saldo_actual) }})
            </option>
          </select>
          <p class="text-xs text-[var(--ui-text-soft)] mt-1">
            Vincula los movimientos a una cuenta para actualizar su saldo automáticamente
          </p>
        </div>

        <!-- Área de drop -->
        <div
          class="border-2 border-dashed rounded-2xl p-8 text-center transition-colors"
          :class="dragover ? 'border-blue-500 bg-blue-50/80 dark:bg-sky-900/20' : archivo ? 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-900/20' : 'border-[var(--ui-border)] bg-[var(--ui-surface-alt)]'"
          @dragover.prevent="dragover = true"
          @dragleave.prevent="dragover = false"
          @drop.prevent="handleDrop"
        >
          <div v-if="form.archivo.length === 0">
            <FontAwesomeIcon :icon="['fas', 'cloud-upload-alt']" class="h-12 w-12 text-[var(--ui-text-soft)] mb-4" />
            <p class="text-[var(--ui-text-muted)] mb-2">Arrastra tus archivos Excel, CSV o PDF aquí</p>
            <p class="text-[var(--ui-text-soft)] text-sm mb-4">o</p>
            <label class="px-4 py-2 bg-blue-600 text-white rounded-xl cursor-pointer hover:bg-blue-700">
              Seleccionar archivos
              <input
                ref="fileInput"
                type="file"
                class="hidden"
                multiple
                accept=".csv,.txt,.xls,.xlsx,.pdf"
                @change="handleFileUpload"
              />
            </label>
          </div>
          <div v-else class="text-emerald-700 dark:text-emerald-300">
            <FontAwesomeIcon :icon="['fas', 'file-excel']" class="h-12 w-12 mb-2" />
            <p class="font-medium">Archivos seleccionados:</p>
            <p v-if="selectedFileNames" class="mt-2 text-sm text-orange-400 font-medium animate-pulse">
              {{ selectedFileNames }}
            </p>
            <button type="button" @click="form.archivo = []; selectedFileNames = ''" class="mt-2 text-rose-600 text-sm hover:underline">
              Limpiar selección
            </button>
          </div>
        </div>

        <!-- Formato aceptado -->
        <div class="mt-4 p-3 bg-[var(--ui-surface-alt)] rounded-xl text-xs text-[var(--ui-text-muted)] border border-[var(--ui-border)]">
          <p class="font-medium mb-1">Formatos aceptados:</p>
          <ul class="list-disc list-inside space-y-1">
            <li><strong>Excel (.xls, .xlsx)</strong> - BBVA, Banorte</li>
            <li>CSV (.csv) - Todos los bancos</li>
            <li><strong>PDF (.pdf)</strong> - Citibanamex (Digital)</li>
            <li>TXT (.txt) - Archivos de texto</li>
          </ul>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" @click="$emit('close')" class="px-4 py-2 border border-[var(--ui-border)] rounded-xl hover:bg-black/5 dark:hover:bg-white/5">
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="form.archivo.length === 0 || form.processing"
            class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
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
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  bancosSoportados: { type: Array, default: () => ['BBVA', 'BANORTE', 'SANTANDER'] },
  cuentas: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const selectedFileNames = ref('')
const dragover = ref(false)
const cuentasDisponibles = ref([])

const form = useForm({
  archivo: [],
  banco: '',
  cuenta_bancaria_id: '',
})

// Cargar cuentas bancarias
const cargarCuentas = async () => {
  if (props.cuentas && props.cuentas.length > 0) {
    cuentasDisponibles.value = props.cuentas
  } else {
    try {
      const { data } = await axios.get(route('cuentas-bancarias.activas'))
      cuentasDisponibles.value = data
    } catch (error) {
      console.error('Error cargando cuentas:', error)
    }
  }
}

const handleFileUpload = (e) => {
  const files = Array.from(e.target.files);
  if (files.length > 0) {
    form.archivo = files;
    selectedFileNames.value = files.map(f => f.name).join(', ');
  }
};

const handleDrop = (e) => {
  dragover.value = false
  const files = Array.from(e.dataTransfer.files);
  if (files.length > 0) {
    form.archivo = files;
    selectedFileNames.value = files.map(f => f.name).join(', ');
  }
}

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const importar = () => {
  if (form.archivo.length === 0) return

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
