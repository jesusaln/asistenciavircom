 <script setup>
  import { Head, Link, useForm } from '@inertiajs/vue3'
  import { ref, watch, onMounted, nextTick } from 'vue'
  import axios from 'axios'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import CategoriaHerramientaModal from '@/Components/Modals/CategoriaHerramientaModal.vue'
 
  defineOptions({ layout: AppLayout })
 
  const props = defineProps({
    herramienta: { type: Object, required: true },
    categorias: { type: Array, default: () => [] },
  })
 
  // Estado reactivo para controlar la inicialización
  const isDataReady = ref(false)
  const formInitialized = ref(false)
 
  const form = useForm({
    nombre: '',
    numero_serie: '',
    estado: 'disponible',
    descripcion: '',
    foto: null,
    categoria_id: '',
    vida_util_meses: null,
    costo_reemplazo: null,
    dias_para_mantenimiento: null,
    requiere_mantenimiento: false,
  })
 
  const fotoPreview = ref('')
  const categoriasList = ref([...(props.categorias || [])])
  const showCategoriaModal = ref(false)
  const categoriaToEdit = ref(null)
 
  // Función para inicializar el formulario con los datos de la herramienta
  const initializeForm = () => {
    console.log('🔧 Inicializando formulario con datos:', props.herramienta)
 
    if (props.herramienta && props.herramienta.id) {
      form.nombre = props.herramienta.nombre || ''
      form.numero_serie = props.herramienta.numero_serie || ''
      form.estado = props.herramienta.estado || 'disponible'
      form.descripcion = props.herramienta.descripcion || ''
      form.categoria_id = props.herramienta.categoria_id || ''
      form.vida_util_meses = props.herramienta.vida_util_meses || null
      form.costo_reemplazo = props.herramienta.costo_reemplazo || null
      form.dias_para_mantenimiento = props.herramienta.dias_para_mantenimiento || null
      form.requiere_mantenimiento = Boolean(props.herramienta.requiere_mantenimiento)
 
      // Inicializar preview de foto
      fotoPreview.value = props.herramienta.foto ? `/storage/${props.herramienta.foto}` : ''
 
      console.log('✅ Formulario inicializado correctamente:', {
        nombre: form.nombre,
        numero_serie: form.numero_serie,
        estado: form.estado,
        categoria_id: form.categoria_id
      })
 
      formInitialized.value = true
      isDataReady.value = true
    }
  }
 
  // Watcher para detectar cambios en las props
  watch(() => props.herramienta, (newHerramienta) => {
    if (newHerramienta && newHerramienta.id && !formInitialized.value) {
      nextTick(() => {
        initializeForm()
      })
    }
  }, { immediate: true })
 
  // Inicializar cuando el componente se monte
  onMounted(() => {
    console.log('🚀 Edit Herramienta - Componente montado')
 
    // Pequeño delay para asegurar que las props estén completamente cargadas
    setTimeout(() => {
      initializeForm()
    }, 100)
  })

const handleFile = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
      Swal.fire({
        icon: 'error',
        title: 'Archivo inválido',
        text: 'Por favor selecciona un archivo de imagen válido'
      })
      return
    }

    // Validar tamaño (máximo 5MB)
    if (file.size > 5 * 1024 * 1024) {
      Swal.fire({
        icon: 'error',
        title: 'Imagen demasiado grande',
        text: 'La imagen es demasiado grande. El tamaño máximo es 5MB'
      })
      return
    }

    form.foto = file
    const reader = new FileReader()
    reader.onload = (ev) => { fotoPreview.value = ev.target.result }
    reader.readAsDataURL(file)
  }
}

const removeImage = () => {
  form.foto = null
  fotoPreview.value = props.herramienta.foto ? `/storage/${props.herramienta.foto}` : null
}

const validateNombre = () => {
  if (!form.nombre || form.nombre.trim() === '') {
    form.errors.nombre = 'El nombre de la herramienta es requerido'
    return false
  }
  delete form.errors.nombre
  return true
}

const submit = () => {
  console.log('🚀 Intentando enviar formulario...')

  // Validar que el nombre no esté vacío antes de enviar
  if (!validateNombre()) {
    console.log('❌ Validación fallida - nombre requerido')
    return
  }

  console.log('✅ Validación pasada, enviando formulario:', {
    nombre: form.nombre,
    numero_serie: form.numero_serie,
    estado: form.estado,
    categoria_id: form.categoria_id
  })

  form.transform((data) => ({ ...data, _method: 'PUT' })).post(route('herramientas.update', props.herramienta.id), { forceFormData: true })
}

const openCategoriaModal = (categoria = null) => {
  categoriaToEdit.value = categoria
  showCategoriaModal.value = true
}

const closeCategoriaModal = () => {
  showCategoriaModal.value = false
  categoriaToEdit.value = null
}

const handleCategoriaCreated = (categoria) => {
  categoriasList.value.push(categoria)
  form.categoria_id = categoria.id
}

const handleCategoriaUpdated = (categoria) => {
  const index = categoriasList.value.findIndex(c => c.id === categoria.id)
  if (index !== -1) {
    categoriasList.value[index] = categoria
  }
  // Si la categoría editada es la seleccionada actualmente, mantener la selección
  if (form.categoria_id === categoria.id) {
    form.categoria_id = categoria.id
  }
}
</script>

<template>
  <Head :title="`Editar Herramienta - ${props.herramienta.nombre}`" />

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-semibold text-slate-900">Editar Herramienta</h1>
    <Link class="text-blue-600 hover:underline" :href="route('herramientas.index')">Volver</Link>
  </div>

  <!-- Indicador de carga mientras se inicializa el formulario -->
  <div v-if="!isDataReady" class="bg-white shadow rounded p-6 text-center">
    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
    <p class="mt-2 text-gray-600">Cargando formulario...</p>
  </div>

  <form v-if="isDataReady" @submit.prevent="submit" class="bg-white shadow-lg rounded-lg p-8 space-y-8 max-w-4xl">
    <!-- Información básica -->
    <div class="border-b border-gray-200 pb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Básica</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Nombre de la herramienta <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.nombre"
            type="text"
            :class="[
              'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200',
              form.nombre && form.nombre.trim() !== '' ? 'border-gray-300' : 'border-red-300'
            ]"
            placeholder="Ej: Taladro inalámbrico, Multímetro digital..."
            required
            @blur="validateNombre"
          />
          <div v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</div>
          <div v-if="!form.nombre || form.nombre.trim() === ''" class="mt-1 text-sm text-red-600">
            El nombre de la herramienta es requerido
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Número de serie</label>
          <input
            v-model="form.numero_serie"
            type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
            placeholder="Ej: TL-001, SN123456..."
          />
          <div v-if="form.errors.numero_serie" class="mt-1 text-sm text-red-600">{{ form.errors.numero_serie }}</div>
        </div>
      </div>
    </div>

    <!-- Categorización y estado -->
    <div class="border-b border-gray-200 pb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Categorización y Estado</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
          <div class="flex gap-2">
            <select v-model="form.categoria_id" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Sin categoría</option>
              <option v-for="cat in categoriasList" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
            </select>
            <button type="button" @click="openCategoriaModal" class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200">
              Gestionar
            </button>
          </div>
          <p v-if="!categoriasList.length" class="text-xs text-gray-500 mt-1">No hay categorías. Usa "Gestionar" para crear una nueva.</p>
          <div v-if="form.errors.categoria_id" class="mt-1 text-sm text-red-600">{{ form.errors.categoria_id }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
          <select v-model="form.estado" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="disponible">🟢 Disponible</option>
            <option value="asignada">🔵 Asignada</option>
            <option value="mantenimiento">🟡 En mantenimiento</option>
            <option value="baja">🔴 De baja</option>
            <option value="perdida">⚫ Perdida</option>
          </select>
          <div v-if="form.errors.estado" class="mt-1 text-sm text-red-600">{{ form.errors.estado }}</div>
        </div>
      </div>
    </div>

    <!-- Información técnica -->
    <div class="border-b border-gray-200 pb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Técnica</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Vida útil (meses)</label>
          <input
            v-model="form.vida_util_meses"
            type="number"
            min="1"
            max="120"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="24"
          />
          <div v-if="form.errors.vida_util_meses" class="mt-1 text-sm text-red-600">{{ form.errors.vida_util_meses }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Costo de reemplazo ($)</label>
          <input
            v-model="form.costo_reemplazo"
            type="number"
            step="0.01"
            min="0"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="1500.00"
          />
          <div v-if="form.errors.costo_reemplazo" class="mt-1 text-sm text-red-600">{{ form.errors.costo_reemplazo }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Días para mantenimiento</label>
          <input
            v-model="form.dias_para_mantenimiento"
            type="number"
            min="1"
            max="365"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="90"
          />
          <div v-if="form.errors.dias_para_mantenimiento" class="mt-1 text-sm text-red-600">{{ form.errors.dias_para_mantenimiento }}</div>
        </div>
      </div>

      <div class="mt-4">
        <label class="flex items-center">
          <input
            v-model="form.requiere_mantenimiento"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="ml-2 text-sm text-gray-700">Esta herramienta requiere mantenimiento programado</span>
        </label>
      </div>
    </div>

    <!-- Descripción y foto -->
    <div class="border-b border-gray-200 pb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-4">Descripción y Documentación</h2>
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
          <textarea
            v-model="form.descripcion"
            rows="4"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Describe las características, uso, cuidados especiales..."
          ></textarea>
          <div v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Foto de la herramienta</label>
          <div class="flex flex-col items-center space-y-4">
            <!-- Vista previa de imagen actual -->
            <div v-if="fotoPreview" class="relative">
              <img :src="fotoPreview" class="w-48 h-48 object-cover rounded-lg border-2 border-gray-200" />
              <button
                @click="removeImage"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600"
                title="Eliminar imagen"
              >
                ×
              </button>
            </div>

            <!-- Área de carga de imagen -->
            <div v-else class="w-48 h-48 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center text-gray-500 hover:border-gray-400 transition-colors">
              <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="text-sm">Foto de la herramienta</span>
            </div>

            <!-- Botones de acción -->
            <div class="flex space-x-2">
              <label class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer">
                <span>{{ fotoPreview ? 'Cambiar imagen' : 'Seleccionar imagen' }}</span>
                <input @change="handleFile" accept="image/*" type="file" class="hidden" />
              </label>

              <label v-if="!fotoPreview" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 cursor-pointer">
                <span>Capturar foto</span>
                <input @change="handleFile" accept="image/*" capture="environment" type="file" class="hidden" />
              </label>
            </div>

            <div v-if="form.errors.foto" class="text-red-600 text-sm text-center">{{ form.errors.foto }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Botones de acción -->
    <div class="flex justify-end space-x-4 pt-6">
      <Link
        :href="route('herramientas.index')"
        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200"
      >
        Cancelar
      </Link>
      <button
        :disabled="form.processing"
        type="submit"
        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
      >
        <span v-if="form.processing" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Actualizando...
        </span>
        <span v-else>Actualizar Herramienta</span>
      </button>
    </div>
  </form>

  <!-- Modal de gestión de categorías -->
  <CategoriaHerramientaModal
    v-if="isDataReady"
    :show="showCategoriaModal"
    :categorias="categoriasList"
    @close="closeCategoriaModal"
    @categoria-created="handleCategoriaCreated"
    @categoria-updated="handleCategoriaUpdated"
  />
</template>

