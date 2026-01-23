<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" role="dialog" aria-modal="true">
          <!-- Header del modal -->
          <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-slate-800 bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-800">Detalles del Producto</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-200" aria-label="Cerrar modal">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del modal con scroll -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <div class="space-y-6">
              <!-- Información General -->
              <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-blue-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Información General
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.nombre }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.descripcion }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Código</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.codigo }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Número de Serie</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.numero_serie }}</p>
                  </div>
                </div>
              </div>

              <!-- Información Adicional -->
              <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-green-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.654 0 3-1.346 3-3s-1.346-3-3-3-3 1.346-3 3 1.346 3 3 3zm0 2c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4zm0 8c2.21 0 4-1.79 4-4h-8c0 2.21 1.79 4 4 4z" />
                  </svg>
                  Información Adicional
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.codigo_barras }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ categoriaNombre || 'Cargando...' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Marca</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ marcaNombre || 'Cargando...' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ proveedorNombre || 'Cargando...' }}</p>
                  </div>
                </div>
              </div>

              <!-- Información de Inventario -->
              <div class="bg-yellow-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-yellow-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                  Información de Inventario
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Almacén</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ almacenNombre || 'Cargando...' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Stock</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.stock }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Compra</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.precio_compra }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Precio de Venta</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.precio_venta }}</p>
                  </div>
                </div>
              </div>

              <!-- Información de Estado y Otros -->
              <div class="bg-purple-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-purple-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Estado y Otros
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.estado }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Impuestos</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.impuesto || 'No disponible' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Fecha de Vencimiento</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ producto.fecha_vencimiento || 'No disponible' }}</p>
                  </div>
                </div>
              </div>

              <!-- Imagen del Producto -->
              <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700">Imagen</label>
                <div class="mt-1 block w-full rounded-md bg-gray-100 p-2">
                  <img v-if="producto.imagen_url"
                       :src="producto.imagen_url"
                       alt="Imagen del Producto"
                       class="w-full h-auto rounded-md"
                       @error="handleImageError"/>
                  <span v-else>No disponible</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 dark:border-slate-800">
            <div class="flex justify-end space-x-3">
              <button @click="closeModal" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue';

// Simulación de una caché simple
const cache = {
  categorias: {},
  marcas: {},
  proveedores: {},
  almacenes: {}
};

const props = defineProps({
  producto: Object,
  isOpen: Boolean
});

const emit = defineEmits(['close']);

const closeModal = () => {
  emit('close');
};

const categoriaNombre = ref('Cargando...');
const marcaNombre = ref('Cargando...');
const proveedorNombre = ref('Cargando...');
const almacenNombre = ref('Cargando...');

// Función para obtener el nombre de una entidad desde el backend o caché
const fetchEntityNombre = async (endpoint, id, nombreRef, cacheNamespace) => {
  // Verificar si el dato está en caché
  if (cache[cacheNamespace][id]) {
    nombreRef.value = cache[cacheNamespace][id];
    return;
  }

  try {
    const response = await fetch(`/api/${endpoint}/${id}`);
    if (!response.ok) {
      throw new Error(`No se pudo obtener ${endpoint}`);
    }
    const data = await response.json();
    // Almacenar en caché
    cache[cacheNamespace][id] = data.nombre || data.nombre_razon_social || 'No disponible';
    nombreRef.value = cache[cacheNamespace][id];
  } catch (error) {
    console.error(`Error al obtener ${endpoint}:`, error);
    nombreRef.value = 'No disponible';
  }
};

// Función para manejar errores de carga de imagen
const handleImageError = (event) => {
  console.warn('Error al cargar imagen del producto:', event.target.src);
  // Establecer una imagen de placeholder local
  event.target.src = '/images/placeholder-product.svg';
  event.target.alt = 'Imagen no disponible';
};

// Llama a fetchEntityNombre cuando el modal se abre
onMounted(() => {
  if (props.producto) {
    if (props.producto.categoria_id) {
      fetchEntityNombre('categorias', props.producto.categoria_id, categoriaNombre, 'categorias');
    }
    if (props.producto.marca_id) {
      fetchEntityNombre('marcas', props.producto.marca_id, marcaNombre, 'marcas');
    }
    if (props.producto.proveedor_id) {
      fetchEntityNombre('proveedores', props.producto.proveedor_id, proveedorNombre, 'proveedores');
    }
    if (props.producto.almacen_id) {
      fetchEntityNombre('almacenes', props.producto.almacen_id, almacenNombre, 'almacenes');
    }
  }
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white dark:bg-slate-900,
.modal-leave-active .bg-white dark:bg-slate-900 {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white dark:bg-slate-900,
.modal-leave-to .bg-white dark:bg-slate-900 {
  transform: scale(0.95);
}
</style>

