<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faArrowLeft, faSave, faWrench, faUser, faMobileAlt, faPlus } from '@fortawesome/free-solid-svg-icons'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue'
import CrearClienteModal from '@/Components/Modals/CrearClienteModal.vue'
import CrearMarcaModal from '@/Components/Modals/CrearMarcaModal.vue'
import MiniCalendar from '@/Components/Taller/MiniCalendar.vue'
import SignaturePad from '@/Components/UI/SignaturePad.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  clientes: Array,
  marcas: Array,
  catalogs: Object,
})

const { colors } = useCompanyColors()
const clientesList = ref([...props.clientes])
const marcasList = ref([...props.marcas])
const clienteSeleccionado = ref(null)
const mostrarModalCliente = ref(false)
const mostrarModalMarca = ref(false)
const nombreClienteBuscado = ref('')

const form = useForm({
  cliente_id: null,
  nombre_cliente: '',
  telefono_cliente: '',
  equipo_marca: '',
  equipo_modelo: '',
  equipo_serie: '',
  problema_reportado: '',
  accesorios: '',
  estado_fisico: '',
  fecha_compromiso: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  firma_recepcion: null,
})

const onClienteSeleccionado = (cliente) => {
  clienteSeleccionado.value = cliente
  form.cliente_id = cliente ? cliente.id : null
  if (cliente) {
    form.nombre_cliente = cliente.nombre_razon_social || cliente.nombre
    form.telefono_cliente = cliente.telefono
  }
}

const abrirModalNuevoCliente = (nombre) => {
  nombreClienteBuscado.value = nombre
  mostrarModalCliente.value = true
}

const onClienteCreado = (nuevo) => {
  if (!clientesList.value.some(c => c.id === nuevo.id)) {
    clientesList.value.push(nuevo)
  }
  onClienteSeleccionado(nuevo)
  mostrarModalCliente.value = false
}

const onMarcaCreada = (nueva) => {
  if (!marcasList.value.some(m => m.id === nueva.id)) {
    marcasList.value.push(nueva)
    marcasList.value.sort((a, b) => a.nombre.localeCompare(b.nombre))
  }
  form.equipo_marca = nueva.nombre
  mostrarModalMarca.value = false
}

const submit = () => {
  form.post(route('taller.store'))
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('cliente_id')) {
    const cid = params.get('cliente_id')
    const cliente = props.clientes.find(c => String(c.id) === String(cid))
    if (cliente) {
      onClienteSeleccionado(cliente)
    } else {
      // Si no está en la lista inicial, intentar cargar mínimamente
      form.cliente_id = cid
      form.nombre_cliente = params.get('cliente_nombre') || ''
      form.telefono_cliente = params.get('cliente_telefono') || ''
    }
  }
  
  if (params.get('equipo_serie')) {
    form.equipo_serie = params.get('equipo_serie')
  }
  if (params.get('equipo_modelo')) {
    form.equipo_modelo = params.get('equipo_modelo')
  }
  if (params.get('equipo_marca')) {
    const marcaVal = params.get('equipo_marca')
    if (!marcasList.value.some(m => m.nombre === marcaVal)) {
      marcasList.value.push({ id: 'temp-' + Date.now(), nombre: marcaVal })
    }
    form.equipo_marca = marcaVal
  }
  if (params.get('problema_reportado')) {
    form.problema_reportado = params.get('problema_reportado')
  }
})
</script>

<template>
  <Head title="Nueva Orden de Taller" />

  <div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-full mx-auto">
      
      <!-- Breadcrumbs / Back -->
      <div class="mb-6">
        <Link :href="route('taller.index')" class="text-[var(--ui-text-muted)] hover:text-[var(--ui-text)] transition-colors flex items-center gap-2 font-bold text-sm">
          <FontAwesomeIcon :icon="faArrowLeft" />
          Regresar al listado
        </Link>
      </div>

      <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl bg-[var(--ui-accent)]/20 flex items-center justify-center border border-[var(--ui-accent)]/30 shadow-[0_0_20px_rgba(245,158,11,0.15)]">
          <FontAwesomeIcon :icon="faWrench" class="text-[var(--ui-accent)] text-2xl" />
        </div>
        <div>
          <h1 class="text-3xl font-black text-[var(--ui-text)] tracking-tight uppercase">Nueva Orden de Taller</h1>
          <p class="text-[var(--ui-text-muted)] font-medium">Recepción de equipo para revisión técnica.</p>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- Cliente Section -->
        <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2rem] p-8 backdrop-blur-3xl shadow-2xl relative">
          <div class="absolute top-0 right-0 p-8 opacity-[0.03] pointer-events-none overflow-hidden rounded-[2rem] inset-0">
            <FontAwesomeIcon :icon="faUser" class="absolute top-8 right-8 text-8xl text-[var(--ui-text)]" />
          </div>
          
          <h2 class="text-lg font-black text-[var(--ui-accent)] uppercase tracking-wide mb-6 flex items-center gap-3">
            <span class="w-8 h-px bg-[var(--ui-accent)]/30"></span>
            Información del Cliente
          </h2>

          <div class="space-y-6">
            <BuscarCliente 
              :clientes="clientesList"
              :cliente-seleccionado="clienteSeleccionado"
              label-busqueda="Seleccionar Cliente"
              placeholder-busqueda="Buscar por nombre, RFC, email o teléfono..."
              :requerido="true"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="abrirModalNuevoCliente"
            />
          </div>
        </div>

        <!-- Detalles del Equipo -->
        <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2rem] p-8 backdrop-blur-3xl shadow-2xl relative">
          <div class="absolute top-0 right-0 p-8 opacity-[0.03] pointer-events-none overflow-hidden rounded-[2rem] inset-0">
            <FontAwesomeIcon :icon="faMobileAlt" class="absolute top-8 right-8 text-8xl text-[var(--ui-text)]" />
          </div>

          <h2 class="text-lg font-black text-[var(--ui-accent)] uppercase tracking-wide mb-6 flex items-center gap-3">
            <span class="w-8 h-px bg-[var(--ui-accent)]/30"></span>
            Detalles del Equipo
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="space-y-2">
              <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Marca</label>
              <div class="flex gap-2">
                <select 
                  v-model="form.equipo_marca" 
                  class="flex-1 bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
                  required
                >
                  <option value="" disabled>-- Seleccionar --</option>
                  <option v-for="m in marcasList" :key="m.id" :value="m.nombre">{{ m.nombre }}</option>
                </select>
                <button 
                  type="button" 
                  @click="mostrarModalMarca = true"
                  class="w-[52px] h-[52px] rounded-2xl bg-[var(--ui-surface)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-accent)] hover:bg-[var(--ui-accent)] hover:text-[var(--ui-accent-contrast)] transition-all shadow-xl active:scale-95"
                  title="Nueva Marca"
                >
                  <FontAwesomeIcon :icon="faPlus" />
                </button>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Modelo</label>
              <input v-model="form.equipo_modelo" type="text" placeholder="Ej. S21 Ultra" class="w-full bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all" required>
            </div>
            <div class="space-y-2">
              <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">No. Serie</label>
              <input v-model="form.equipo_serie" type="text" placeholder="Opcional" class="w-full bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all">
            </div>
          </div>

          <div class="space-y-2 mb-6">
            <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Problema Reportado</label>
            <textarea 
              v-model="form.problema_reportado"
              rows="4" 
              placeholder="Describe la falla que presenta el equipo..."
              class="w-full bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
              required
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-2">
              <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Estado Físico del Equipo</label>
              <textarea 
                v-model="form.estado_fisico"
                rows="2" 
                placeholder="Describe el estado físico del equipo al recibirlo (golpes, rayones, etc.)"
                class="w-full bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
              ></textarea>
            </div>
            <div class="space-y-2">
              <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Accesorios Incluidos</label>
              <textarea 
                v-model="form.accesorios"
                rows="2" 
                placeholder="Lista de accesorios que entrega el cliente (cargador, funda, audífonos, etc.)"
                class="w-full bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
              ></textarea>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <MiniCalendar 
                v-model="form.fecha_compromiso"
                label="Fecha Compromiso de Entrega"
              />
            </div>
          </div>

          <div class="mt-8 pt-8 border-t border-[var(--ui-border)]">
            <h2 class="text-lg font-black text-[var(--ui-accent)] uppercase tracking-wide mb-6 flex items-center gap-3">
              <span class="w-8 h-px bg-[var(--ui-accent)]/30"></span>
              Firma de Recepción
            </h2>
            <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-3xl p-6">
              <SignaturePad 
                v-model="form.firma_recepcion"
                label=""
                placeholder="El cliente debe firmar aquí al entregar el equipo"
                :height="200"
              />
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end pt-4">
          <button 
            type="submit" 
            :disabled="form.processing"
            class="inline-flex items-center justify-center px-10 py-4 bg-gradient-to-r from-[var(--ui-accent)] to-brand-600 hover:brightness-110 text-[var(--ui-accent-contrast)] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-[var(--ui-accent)]/20 transition-all duration-200 hover:scale-[1.05] active:scale-[0.95] disabled:opacity-50"
          >
            <FontAwesomeIcon v-if="!form.processing" :icon="faSave" class="mr-3" />
            <span v-else class="mr-3 animate-spin border-2 border-white/30 border-t-white rounded-full w-4 h-4"></span>
            Crear Orden de Taller
          </button>
        </div>

      </form>
    </div>

    <!-- Modales -->
    <CrearClienteModal 
      :show="mostrarModalCliente"
      :nombre-inicial="nombreClienteBuscado"
      :catalogs="catalogs"
      @close="mostrarModalCliente = false"
      @cliente-creado="onClienteCreado"
    />

    <CrearMarcaModal 
      :show="mostrarModalMarca"
      @close="mostrarModalMarca = false"
      @marca-creada="onMarcaCreada"
    />
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}
</style>
