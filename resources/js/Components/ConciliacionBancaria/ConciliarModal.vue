<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm" @click.self="$emit('close')">
    <div class="bg-[var(--ui-surface)] text-[var(--ui-text)] rounded-3xl shadow-[var(--ui-shadow)] w-full max-w-2xl mx-4 overflow-hidden border border-[var(--ui-border)]">
      <!-- Header -->
      <div class="p-6 border-b border-[var(--ui-border)]">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold">Conciliar Movimiento</h3>
            <p class="text-sm text-[var(--ui-text-soft)] mt-1">{{ formatFecha(movimiento.fecha) }} • {{ movimiento.concepto }}</p>
          </div>
          <button @click="$emit('close')" class="text-[var(--ui-text-soft)] hover:text-[var(--ui-text)]">
            <FontAwesomeIcon :icon="['fas', 'times']" class="h-6 w-6" />
          </button>
        </div>
      </div>

      <!-- Movimiento Info -->
      <div class="p-6 bg-[var(--ui-surface-alt)]">
        <div class="flex items-center justify-between">
          <div>
            <span :class="movimiento.tipo === 'deposito' ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
              {{ movimiento.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(Math.abs(movimiento.monto)) }}
            </span>
            <span class="ml-2 text-sm text-[var(--ui-text-soft)]">
              {{ movimiento.tipo === 'deposito' ? 'Depósito (buscar en CXC)' : 'Retiro (buscar en CXP)' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Sugerencias -->
      <div class="p-6">
        <div v-if="loading" class="text-center py-8">
          <FontAwesomeIcon :icon="['fas', 'spinner']" class="h-8 w-8 text-blue-600 animate-spin" />
          <p class="text-[var(--ui-text-soft)] mt-2">Buscando sugerencias...</p>
        </div>

        <div v-else-if="sugerencias.length > 0">
          <h4 class="font-medium mb-4">Sugerencias de conciliación</h4>
          <div class="space-y-3">
            <div
              v-for="sug in sugerencias"
              :key="`${sug.tipo}-${sug.cuenta_id}`"
              @click="seleccionarSugerencia(sug)"
              class="p-4 border rounded-lg cursor-pointer transition-all"
              :class="sugerenciaSeleccionada?.cuenta_id === sug.cuenta_id ? 'border-blue-500 bg-blue-50/80 dark:bg-blue-900/20' : 'border-[var(--ui-border)] hover:border-blue-300'"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <span class="font-medium">{{ sug.numero }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full" :class="sug.tipo === 'CXC' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ sug.tipo }}
                    </span>
                  </div>
                  <p class="text-sm text-[var(--ui-text-muted)] mt-1">{{ sug.entidad }}</p>
                  <p v-if="sug.fecha_vencimiento" class="text-xs text-[var(--ui-text-soft)]">Vence: {{ sug.fecha_vencimiento }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold">${{ formatMonto(sug.monto_pendiente) }}</p>
                  <div class="flex items-center gap-1 mt-1">
                    <div class="w-16 h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                      <div class="h-full bg-green-500" :style="`width: ${sug.score}%`"></div>
                    </div>
                    <span class="text-xs text-[var(--ui-text-soft)]">{{ sug.score }}%</span>
                  </div>
                  <p class="text-xs text-[var(--ui-text-soft)] mt-1">{{ sug.razon }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <FontAwesomeIcon :icon="['fas', 'search']" class="h-12 w-12 text-gray-300 mb-3" />
          <p class="text-[var(--ui-text-muted)] font-medium">No se encontraron coincidencias</p>
          <p class="text-sm text-[var(--ui-text-soft)]">No hay cuentas pendientes que coincidan con este monto</p>
        </div>
      </div>

      <!-- Botones -->
      <div class="p-6 border-t border-[var(--ui-border)] bg-[var(--ui-surface-alt)] flex justify-end gap-3">
        <button @click="$emit('close')" class="px-4 py-2 border border-[var(--ui-border)] rounded-lg hover:bg-black/5 dark:hover:bg-white/5">
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
