<template>
  <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-medium text-gray-900">Precios por Lista</h2>
      <button
        v-if="hasChanges"
        @click="savePrices"
        :disabled="saving"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="saving">Guardando...</span>
        <span v-else>Guardar Cambios</span>
      </button>
    </div>

    <div class="text-sm text-gray-600 mb-4">
      Define precios específicos para cada lista. Si un precio está vacío, se usará el precio de venta base ({{ formatCurrency(basePrice) }}).
    </div>

    <!-- Mensaje de éxito -->
    <div v-if="showSuccess" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
      <p class="text-sm font-medium text-green-800">Precios actualizados correctamente</p>
    </div>

    <!-- Mensaje de error -->
    <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
      <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
    </div>

    <div class="space-y-4">
      <div
        v-for="lista in priceLists"
        :key="lista.id"
        class="flex items-center gap-4 p-4 border border-gray-200 rounded-md hover:bg-gray-50"
      >
        <div class="flex-1">
          <label :for="`price-${lista.id}`" class="block text-sm font-medium text-gray-700">
            {{ lista.nombre }}
          </label>
          <p v-if="lista.descripcion" class="text-xs text-gray-500 mt-1">
            {{ lista.descripcion }}
          </p>
        </div>
        
        <div class="w-48">
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
              $
            </span>
            <input
              :id="`price-${lista.id}`"
              type="number"
              step="0.01"
              min="0"
              v-model.number="prices[lista.id]"
              @input="markAsChanged"
              placeholder="Usar precio base"
              class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>
          <p class="text-xs text-gray-500 mt-1">
            <span v-if="prices[lista.id]">
              {{ formatCurrency(prices[lista.id]) }}
            </span>
            <span v-else class="text-gray-400">
              Fallback: {{ formatCurrency(basePrice) }}
            </span>
          </p>
        </div>
      </div>
    </div>

    <!-- Botón guardar al final también -->
    <div v-if="hasChanges" class="mt-6 flex justify-end">
      <button
        @click="savePrices"
        :disabled="saving"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="saving">Guardando...</span>
        <span v-else>Guardar Cambios</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  productoId: {
    type: Number,
    default: null
  },
  basePrice: {
    type: Number,
    required: true
  },
  priceLists: {
    type: Array,
    required: true
  },
  mode: {
    type: String,
    default: 'direct', // 'direct' (API save) or 'local' (emit changes)
    validator: (value) => ['direct', 'local'].includes(value)
  },
  modelValue: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:modelValue'])

// Estado reactivo para los precios
const prices = reactive({})
const hasChanges = ref(false)
const saving = ref(false)
const showSuccess = ref(false)
const errorMessage = ref('')

// Inicializar precios
const initPrices = () => {
  if (props.mode === 'local') {
    // En modo local, inicializar desde modelValue si existe
    if (props.modelValue && props.modelValue.length > 0) {
      props.modelValue.forEach(item => {
        prices[item.price_list_id] = item.precio
      })
    }
  } else {
    // En modo directo, inicializar desde props.priceLists (que trae precios cargados del backend)
    props.priceLists.forEach(lista => {
      prices[lista.id] = lista.precio || null
    })
  }
}

onMounted(() => {
  initPrices()
})

// Watch para cambios en modelValue externo en modo local
watch(() => props.modelValue, (newValue) => {
  if (props.mode === 'local' && newValue) {
    // Solo actualizar si es diferente para evitar loops
    // (Implementación simplificada, asume que el padre controla el estado)
  }
}, { deep: true })

const markAsChanged = () => {
  hasChanges.value = true
  showSuccess.value = false
  errorMessage.value = ''

  if (props.mode === 'local') {
    emitChanges()
  }
}

const emitChanges = () => {
  // Convertir objeto prices a array para el backend
  const pricesArray = props.priceLists.map(lista => ({
    price_list_id: lista.id,
    precio: prices[lista.id] || null
  })).filter(item => item.precio !== null && item.precio !== '')
  
  emit('update:modelValue', pricesArray)
}

const formatCurrency = (value) => {
  if (value === null || value === undefined) return '$0.00'
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value)
}

const savePrices = async () => {
  if (props.mode === 'local') return

  saving.value = true
  errorMessage.value = ''
  
  try {
    // Preparar datos para enviar
    const pricesData = props.priceLists.map(lista => ({
      price_list_id: lista.id,
      precio: prices[lista.id] || null
    }))

    // Enviar petición
    const response = await fetch(`/productos/${props.productoId}/prices`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ prices: pricesData })
    })

    const data = await response.json()

    if (response.ok && data.success) {
      hasChanges.value = false
      showSuccess.value = true
      
      // Ocultar mensaje de éxito después de 3 segundos
      setTimeout(() => {
        showSuccess.value = false
      }, 3000)
    } else {
      errorMessage.value = data.message || 'Error al actualizar los precios'
    }
  } catch (error) {
    console.error('Error saving prices:', error)
    errorMessage.value = 'Error de conexión al guardar los precios'
  } finally {
    saving.value = false
  }
}
</script>

