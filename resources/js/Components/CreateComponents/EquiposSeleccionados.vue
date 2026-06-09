<template>
  <div class="equipos-seleccionados">
    <!-- Lista de equipos seleccionados -->
    <div v-if="selectedProducts.length > 0" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Equipos Seleccionados
        <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
          {{ selectedProducts.length }}
        </span>
      </h3>

      <div class="space-y-4">
        <div
          v-for="entry in selectedProducts"
          :key="`${entry.tipo}-${entry.id}`"
          class="bg-gradient-to-r from-gray-50 to-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-all duration-200"
        >
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Información del equipo -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-3 bg-blue-100 text-blue-800">
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      Equipo
                    </span>
                  </div>

                  <h4 class="text-lg font-semibold text-gray-900 mb-1">
                    {{ getItemInfo(entry).nombre }}
                  </h4>

                  <p v-if="getItemInfo(entry).descripcion" class="text-sm text-gray-600 mb-2">
                    {{ getItemInfo(entry).descripcion }}
                  </p>

                  <div class="space-y-1">
                    <!-- Precio mensual -->
                    <div class="flex items-center text-sm text-gray-500">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                      </svg>
                      Precio mensual: ${{ getItemInfo(entry).precio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                    </div>

                    <!-- Código y serie -->
                    <div class="flex items-center text-sm text-gray-500">
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                      </svg>
                      Código: {{ getItemInfo(entry).codigo }} | Serie: {{ getItemInfo(entry).numero_serie }}
                    </div>
                  </div>
                </div>

                <!-- Botón eliminar -->
                <button
                  type="button"
                  @click="eliminarItem(entry)"
                  class="text-red-500 hover:text-red-700 hover:bg-red-100 p-2 rounded-full transition-colors duration-200 flex-shrink-0"
                  title="Eliminar equipo"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Controles de cantidad, descuento y totales -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 lg:w-96">
              <!-- Cantidad (siempre 1 para equipos) -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad</label>
                <div class="relative">
                  <input
                    type="number"
                    value="1"
                    readonly
                    class="w-full px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed"
                  />
                </div>
              </div>

              <!-- Descuento -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Descuento %</label>
                <div class="relative">
                  <input
                    type="number"
                    :value="discounts[`${entry.tipo}-${entry.id}`] || 0"
                    @input="updateDiscount(entry, $event.target.value)"
                    min="0"
                    max="100"
                    step="0.01"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  />
                  <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                    <span class="text-gray-400 text-xs">%</span>
                  </div>
                </div>
              </div>

              <!-- Precio mensual (solo lectura) -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Precio Mensual</label>
                <div class="relative">
                  <input
                    type="text"
                    :value="'$' + (prices[`${entry.tipo}-${entry.id}`] || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })"
                    readonly
                    class="w-full px-3 py-2 text-sm bg-gray-100 border border-gray-300 rounded-lg text-gray-600 cursor-not-allowed"
                  />
                  <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                  </div>
                </div>
              </div>

              <!-- Subtotal del equipo -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Subtotal Mensual</label>
                <div class="px-3 py-2 text-sm font-semibold text-green-600 bg-green-50 border border-green-200 rounded-lg">
                  ${{ calcularSubtotalItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Información adicional del equipo (descuento aplicado) -->
          <div v-if="(discounts[`${entry.tipo}-${entry.id}`] || 0) > 0" class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div class="text-gray-600">
                <span class="font-medium">Subtotal sin descuento:</span>
                <span class="float-right">${{ calcularSubtotalSinDescuento(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </div>
              <div class="text-orange-600">
                <span class="font-medium">Descuento aplicado:</span>
                <span class="float-right">-${{ calcularDescuentoItem(entry).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mensaje cuando no hay equipos -->
    <div v-else class="mt-6 p-8 border-2 border-dashed border-gray-300 rounded-xl text-center">
      <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
      </svg>
      <p class="text-gray-500 text-lg font-medium">No hay equipos seleccionados</p>
      <p class="text-gray-400 text-sm mt-1">Busca y agrega equipos para comenzar con la renta</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  selectedProducts: {
    type: Array,
    required: true,
  },
  equipos: {
    type: Array,
    default: () => [],
  },
  quantities: {
    type: Object,
    required: true,
  },
  prices: {
    type: Object,
    required: true,
  },
  discounts: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits([
  'eliminar-producto',
  'update-quantity',
  'update-discount',
  'calcular-total'
]);

// Función para obtener información del equipo
const getItemInfo = (entry) => {
  const items = props.equipos;
  const item = items.find(i => i.id === entry.id);

  if (!item) {
    return {
      nombre: 'Equipo no encontrado',
      descripcion: '',
      precio: 0,
      codigo: 'N/A',
      numero_serie: 'N/A'
    };
  }

  return {
    nombre: item.nombre,
    descripcion: item.descripcion || '',
    precio: parseFloat(item.precio_renta_mensual) || 0,
    codigo: item.codigo || 'N/A',
    numero_serie: item.numero_serie || 'N/A'
  };
};

// Función para calcular el subtotal sin descuento de un equipo
const calcularSubtotalSinDescuento = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const cantidad = Number.parseFloat(props.quantities[key]) || 1;
  const precio = Number.parseFloat(props.prices[key]) || 0;

  return cantidad * precio;
};

// Función para calcular el descuento de un equipo
const calcularDescuentoItem = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuento = Number.parseFloat(props.discounts[key]) || 0;

  return subtotalSinDescuento * (descuento / 100);
};

// Función para calcular el subtotal final de un equipo (con descuento)
const calcularSubtotalItem = (entry) => {
  const subtotalSinDescuento = calcularSubtotalSinDescuento(entry);
  const descuentoItem = calcularDescuentoItem(entry);

  return subtotalSinDescuento - descuentoItem;
};

// Función para eliminar un equipo
const eliminarItem = (entry) => {
  emit('eliminar-producto', entry);
};

// Función para actualizar descuento
const updateDiscount = (entry, value) => {
  const key = `${entry.tipo}-${entry.id}`;
  const discount = Math.min(100, Math.max(0, Number.parseFloat(value) || 0));
  emit('update-discount', key, discount);
};
</script>

<style scoped>
/* Animaciones para la entrada de equipos */
@keyframes slideInFromRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.equipos-seleccionados > div:not(:first-child) {
  animation: slideInFromRight 0.3s ease-out;
}

/* Estilos para hover en equipos */
.hover-equipo {
  transition: all 0.3s ease;
}

.hover-equipo:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

/* Estilos para inputs focus */
input:focus {
  transform: scale(1.02);
  transition: transform 0.2s ease;
}

/* Estilos para botones de eliminar */
.delete-btn {
  transition: all 0.2s ease;
}

.delete-btn:hover {
  transform: scale(1.1);
}

/* Estilos responsivos */
@media (max-width: 640px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid-cols-4 {
    grid-template-columns: 1fr;
  }
}

/* Estilos para indicadores de estado */
.readonly-input {
  background-image: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 2px,
    rgba(0, 0, 0, 0.05) 2px,
    rgba(0, 0, 0, 0.05) 4px
  );
}

/* Efectos de loading */
.loading {
  opacity: 0.7;
  pointer-events: none;
}

/* Animación para números que cambian */
.number-change {
  transition: all 0.3s ease;
}

.number-increase {
  color: #10b981;
  font-weight: bold;
}

.number-decrease {
  color: #ef4444;
  font-weight: bold;
}
</style>

