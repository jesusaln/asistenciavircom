<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import CategoriaHerramientaModal from '@/Components/Modals/CategoriaHerramientaModal.vue'
import useCompanyColors from '@/Composables/useCompanyColors'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramienta: { type: Object, required: true },
  categorias: { type: Array, default: () => [] },
})

const { colors, headerGradientStyle } = useCompanyColors()

const form = useForm({
  nombre: '',
  numero_serie: '',
  estado: 'disponible',
  descripcion: '',
  foto: null,
  categoria_id: '',
  vida_util_meses: '',
  costo_reemplazo: '',
  dias_para_mantenimiento: '',
  requiere_mantenimiento: false,
})

const isDataReady = ref(false)
const fotoPreview = ref(null)
const categoriasList = ref([...(props.categorias || [])])
const showCategoriaModal = ref(false)

const initializeForm = () => {
  if (props.herramienta && props.herramienta.id) {
    form.nombre = props.herramienta.nombre || ''
    form.numero_serie = props.herramienta.numero_serie || ''
    form.estado = props.herramienta.estado || 'disponible'
    form.descripcion = props.herramienta.descripcion || ''
    form.categoria_id = props.herramienta.categoria_id || ''
    form.vida_util_meses = props.herramienta.vida_util_meses || ''
    form.costo_reemplazo = props.herramienta.costo_reemplazo || ''
    form.dias_para_mantenimiento = props.herramienta.dias_para_mantenimiento || ''
    form.requiere_mantenimiento = Boolean(props.herramienta.requiere_mantenimiento)
    
    fotoPreview.value = props.herramienta.foto ? `/storage/${props.herramienta.foto}` : null
    isDataReady.value = true
  }
}

watch(() => props.herramienta, (newVal) => {
  if (newVal && newVal.id) initializeForm()
}, { immediate: true })

const handleFile = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    if (!file.type.startsWith('image/')) return
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

const submit = () => {
  form.transform((data) => ({
    ...data,
    _method: 'PUT'
  })).post(route('herramientas.update', props.herramienta.id), { forceFormData: true })
}

const openCategoriaModal = () => { showCategoriaModal.value = true }
const closeCategoriaModal = () => { showCategoriaModal.value = false }

const handleCategoriaCreated = (categoria) => {
  categoriasList.value.push(categoria)
  form.categoria_id = categoria.id
}

const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true })
  initializeForm()
})

onBeforeUnmount(() => { if (observer) observer.disconnect() })
</script>

<template>
  <Head :title="`Editar Herramienta - ${props.herramienta.nombre}`" />

  <div class="min-h-screen bg-[var(--ui-surface)] py-8 px-4 transition-colors duration-200">
    <div class="max-w-4xl mx-auto">
      
      <!-- Header -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link :href="route('herramientas.index')" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </Link>
          <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Editar Herramienta</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Actualiza la información de este activo</p>
          </div>
        </div>
      </div>

      <div v-if="!isDataReady" class="flex flex-col items-center justify-center py-20">
        <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <p class="mt-4 text-slate-500 font-bold uppercase tracking-wide text-xs">Cargando Datos...</p>
      </div>

      <form v-else @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna Izquierda: Información Principal -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-200">
            <div :style="headerGradientStyle" class="px-8 py-4 opacity-10"></div>
            <div class="p-8 -mt-4 relative">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2 ml-1">Nombre de la Herramienta</label>
                  <input v-model="form.nombre" type="text" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all" required />
                  <div v-if="form.errors.nombre" class="mt-1 text-[10px] text-rose-500 font-bold uppercase tracking-wider">{{ form.errors.nombre }}</div>
                </div>

                <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Categoría</label>
                  <div class="flex gap-2">
                    <select v-model="form.categoria_id" class="flex-1 px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-brand-500 transition-all">
                      <option value="">Sin categoría</option>
                      <option v-for="cat in categoriasList" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                    </select>
                    <button type="button" @click="openCategoriaModal" class="p-3 bg-emerald-100 dark:bg-slate-800/30 text-emerald-600 dark:text-slate-400 rounded-2xl border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-800/50 hover:bg-emerald-200 transition-all">
                      <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Número de Serie</label>
                  <input v-model="form.numero_serie" type="text" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-brand-500 transition-all" />
                </div>

                <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Estado Actual</label>
                  <select v-model="form.estado" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-brand-500 transition-all">
                    <option value="disponible">🟢 Disponible</option>
                    <option value="asignada">🔵 Asignada</option>
                    <option value="mantenimiento">🟡 En mantenimiento</option>
                    <option value="baja">🔴 De baja</option>
                    <option value="perdida">⚫ Perdida</option>
                  </select>
                </div>

                <div class="md:col-span-2">
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Descripción</label>
                  <textarea v-model="form.descripcion" rows="4" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white text-sm focus:ring-2 focus:ring-brand-500 transition-all"></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Mantenimiento y Vida Útil -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide mb-6 flex items-center gap-2">
              <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              Control de Vida Útil y Mantenimiento
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Vida Útil (Meses)</label>
                <input v-model="form.vida_util_meses" type="number" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Días p/ Mant.</label>
                <input v-model="form.dias_para_mantenimiento" type="number" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 transition-all" />
              </div>
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Costo Reemplazo</label>
                <input v-model="form.costo_reemplazo" type="number" step="0.01" class="w-full px-4 py-3 rounded-2xl bg-[var(--ui-surface)]/50 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500 transition-all" />
              </div>
              <div class="md:col-span-3">
                <label class="inline-flex items-center cursor-pointer group">
                  <div class="relative">
                    <input type="checkbox" v-model="form.requiere_mantenimiento" class="sr-only">
                    <div class="block bg-slate-200 dark:bg-slate-700 w-10 h-6 rounded-full transition-colors group-hover:bg-slate-300 dark:group-hover:bg-slate-600"></div>
                    <div :class="form.requiere_mantenimiento ? 'translate-x-4 bg-blue-600' : 'translate-x-1 bg-white dark:bg-slate-400'" class="absolute left-0 top-1 w-4 h-4 rounded-full transition-transform duration-200"></div>
                  </div>
                  <div class="ml-3 text-sm font-bold text-slate-700 dark:text-slate-200">Activar alertas de mantenimiento programado</div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Multimedia y Acciones -->
        <div class="space-y-6">
          <!-- Card: Foto -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-6 text-center">Imagen del Equipo</label>
            <div class="relative aspect-square rounded-3xl overflow-hidden bg-[var(--ui-surface)]/50 border-2 border-dashed border-slate-200 dark:border-slate-700 group transition-all shadow-inner">
              <img v-if="fotoPreview" :src="fotoPreview" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
              <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 gap-4">
                <svg class="w-16 h-16 opacity-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-[10px] font-bold uppercase tracking-wide opacity-50">Sin Imagen</p>
              </div>
              <button v-if="form.foto || (props.herramienta.foto && fotoPreview !== `/storage/${props.herramienta.foto}`)" @click.prevent="removeImage" class="absolute top-4 right-4 w-10 h-10 bg-brand-500 text-white rounded-2xl shadow-xl flex items-center justify-center hover:bg-rose-600 transition-all">✕</button>
            </div>
            
            <div class="mt-6 space-y-3">
              <label class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-2xl font-bold text-sm hover:bg-blue-700 transition-all cursor-pointer shadow-xl shadow-sky-200 dark:shadow-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ props.herramienta.foto ? 'Cambiar Foto' : 'Subir Foto' }}
                <input @change="handleFile" type="file" class="hidden" accept="image/*" />
              </label>
              <label class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-600 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Tomar Foto
                <input @change="handleFile" type="file" class="hidden" accept="image/*" capture="environment" />
              </label>
            </div>
          </div>

          <!-- Acciones -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <button :disabled="form.processing" type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black text-sm uppercase tracking-wide hover:bg-blue-700 disabled:opacity-50 transition-all shadow-xl shadow-sky-200 dark:shadow-none active:scale-95 mb-4">
              {{ form.processing ? 'Actualizando...' : 'Actualizar Registro' }}
            </button>
            <Link :href="route('herramientas.index')" class="block w-full py-4 bg-[var(--ui-surface)]/50 text-slate-500 dark:text-slate-400 rounded-2xl font-black text-xs uppercase tracking-wide text-center hover:bg-slate-100 dark:hover:bg-slate-900 transition-all">
              Cancelar y Volver
            </Link>
          </div>
        </div>
      </form>
    </div>
  </div>

  <CategoriaHerramientaModal
    :show="showCategoriaModal"
    :categorias="categoriasList"
    @close="closeCategoriaModal"
    @categoria-created="handleCategoriaCreated"
  />
</template>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
