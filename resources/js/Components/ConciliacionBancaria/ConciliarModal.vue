<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden">
      <!-- Header -->
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-gray-900">Conciliar Movimiento</h3>
            <p class="text-sm text-gray-500 mt-1">{{ formatFecha(movimiento.fecha) }} • {{ movimiento.concepto }}</p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <FontAwesomeIcon :icon="['fas', 'times']" class="h-6 w-6" />
          </button>
        </div>
      </div>

      <!-- Movimiento Info -->
      <div class="p-6 bg-gray-50">
        <div class="flex items-center justify-between">
          <div>
            <span :class="movimiento.tipo === 'deposito' ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
              {{ movimiento.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(Math.abs(movimiento.monto)) }}
            </span>
            <span class="ml-2 text-sm text-gray-500">
              {{ movimiento.tipo === 'deposito' ? 'Depósito (buscar en CXC)' : 'Retiro (buscar en CXP)' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Sugerencias -->
      <div class="p-6">
        <div v-if="loading" class="text-center py-8">
          <FontAwesomeIcon :icon="['fas', 'spinner']" class="h-8 w-8 text-blue-600 animate-spin" />
          <p class="text-gray-500 mt-2">Buscando sugerencias...</p>
        </div>

        <div v-else-if="sugerencias.length > 0">
          <h4 class="font-medium text-gray-900 mb-4">Sugerencias de conciliación</h4>
          <div class="space-y-3">
            <div
              v-for="sug in sugerencias"
              :key="`${sug.tipo}-${sug.cuenta_id}`"
              @click="seleccionarSugerencia(sug)"
              class="p-4 border rounded-lg cursor-pointer transition-all"
              :class="sugerenciaSeleccionada?.cuenta_id === sug.cuenta_id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-900">{{ sug.numero }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="sug.tipo === 'CXC' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ sug.tipo }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ sug.entidad }}</p>
                  <p v-if="sug.fecha_vencimiento" class="text-xs text-gray-400">Vence: {{ sug.fecha_vencimiento }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-gray-900">${{ formatMonto(sug.monto_pendiente) }}</p>
                  <div class="flex items-center gap-1 mt-1">
                    <div class="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                      <div class="h-full bg-green-500" :style="`width: ${sug.score}%`"></div>
                    </div>
                    <span class="text-xs text-gray-500">{{ sug.score }}%</span>
                  </div>
                  <p class="text-xs text-gray-400 mt-1">{{ sug.razon }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <FontAwesomeIcon :icon="['fas', 'search']" class="h-12 w-12 text-gray-300 mb-3" />
          <p class="text-gray-600 font-medium">No se encontraron coincidencias</p>
          <p class="text-sm text-gray-400">No hay cuentas pendientes que coincidan con este monto</p>
        </div>
      </div>

      <!-- Botones -->
      <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
          Cancelar
        </button>
        <button
          @click="conciliar"
          :disabled="!sugerenciaSeleccionada || conciliando"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          <FontAwesomeIcon v-if="conciliando" :icon="['fas', 'spinner']" class="animate-spin mr-2" />
          <FontAwesomeIcon v-else :icon="['fas', 'link']" class="mr-2" />
          Conciliar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  movimiento: { type: Object, required: true },
})

const emit = defineEmits(['close', 'conciliado'])

const loading = ref(true)
const sugerencias = ref([])
const sugerenciaSeleccionada = ref(null)
const conciliando = ref(false)

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (fecha) => {
  if (!fecha) return '-'
  return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' })
}

const cargarSugerencias = async () => {
  loading.value = true
  try {
    const response = await fetch(route('conciliacion.sugerencias', props.movimiento.id))
    const data = await response.json()
    sugerencias.value = data.sugerencias || []
    
    // Pre-seleccionar la mejor sugerencia si tiene score alto
    if (sugerencias.value.length > 0 && sugerencias.value[0].score >= 50) {
      sugerenciaSeleccionada.value = sugerencias.value[0]
    }
  } catch (error) {
    console.error('Error cargando sugerencias:', error)
  } finally {
    loading.value = false
  }
}

const seleccionarSugerencia = (sug) => {
  sugerenciaSeleccionada.value = sug
}

const conciliar = () => {
  if (!sugerenciaSeleccionada.value) return

  conciliando.value = true

  router.post(route('conciliacion.conciliar'), {
    movimiento_id: props.movimiento.id,
    tipo_cuenta: sugerenciaSeleccionada.value.tipo,
    cuenta_id: sugerenciaSeleccionada.value.cuenta_id,
  }, {
    onSuccess: () => {
      emit('conciliado')
    },
    onFinish: () => {
      conciliando.value = false
    },
  })
}

onMounted(() => {
  cargarSugerencias()
})
</script>

