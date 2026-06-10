<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { 
  faArrowLeft, faSave, faWrench, faUser, faMobileAlt, faCalendarAlt, 
  faClipboardList, faHistory, faSignature, faTools, faCheckCircle, faFileSignature,
  faPrint, faReceipt, faExternalLinkAlt, faFileInvoiceDollar
} from '@fortawesome/free-solid-svg-icons'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import FirmaModal from '@/Components/Taller/FirmaModal.vue'
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  orden: Object,
  tecnicos: Array,
})

const { colors } = useCompanyColors()

const form = useForm({
  estado: props.orden.estado,
  tecnico_id: props.orden.tecnico_id,
  diagnostico: props.orden.diagnostico || '',
  trabajo_realizado: props.orden.trabajo_realizado || '',
  costo_final: props.orden.costo_final || 0,
  fecha_compromiso: props.orden.fecha_compromiso ? new Date(props.orden.fecha_compromiso).toISOString().split('T')[0] : '',
  firma_entrega: null,
})

const mostrarModalFirma = ref(false)
const facturando = ref(false)

const handleUpdateClick = () => {
  if (form.estado === 'entregado' && !props.orden.firma_entrega) {
    mostrarModalFirma.value = true
  } else {
    submit()
  }
}

const onFirmaConfirmada = (base64) => {
  form.firma_entrega = base64
  mostrarModalFirma.value = false
  submit()
}

const submit = () => {
  form.put(route('taller.update', props.orden.id), {
    preserveScroll: true,
  })
}

const onServicioSeleccionado = (servicio) => {
  form.costo_final = parseFloat(servicio.precio)
  if (form.trabajo_realizado) {
    form.trabajo_realizado += '\n' + servicio.nombre
  } else {
    form.trabajo_realizado = servicio.nombre
  }
}

const generarVenta = () => {
  router.visit(route('ventas.create', { 
    cliente_id: props.orden.cliente_id,
    taller_id: props.orden.id,
    monto: props.orden.costo_final,
    concepto: 'Servicio de Taller: ' + (props.orden.equipo_marca || '') + ' ' + (props.orden.equipo_modelo || '') + ' - Folio: ' + props.orden.folio
  }))
}

const facturar = () => {
  if (facturando.value) return
  facturando.value = true
  router.post(route('taller.facturar', props.orden.id), {}, {
    onFinish: () => { facturando.value = false },
  })
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'recepcionado': 'bg-brand-500/10 text-blue-400 border-blue-500/20',
    'en_revision': 'bg-purple-500/10 text-purple-400 border-purple-500/20',
    'reparando': 'bg-brand-500/10 text-brand-400 border-brand-500/20',
    'listo': 'bg-brand-500/10 text-emerald-400 border-emerald-500/20',
    'entregado': 'bg-slate-500/10 text-slate-400 border-slate-500/20',
    'sin_reparacion': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
    'cancelado': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
  }
  return classes[status] || 'bg-slate-500/10 text-slate-400'
}

const formatDate = (date) => {
  if (!date) return 'Sin fecha'
  return new Date(date).toLocaleString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head :title="'Orden ' + orden.folio" />

  <div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div class="flex items-center gap-5">
          <Link :href="route('taller.index')" class="w-12 h-12 rounded-2xl bg-white/[0.05] border border-white/[0.1] flex items-center justify-center text-slate-400 hover:text-white transition-all">
            <FontAwesomeIcon :icon="faArrowLeft" />
          </Link>
          <div>
            <div class="flex items-center gap-3">
              <h1 class="text-3xl font-black text-white tracking-tight">{{ orden.folio }}</h1>
              <span :class="getStatusBadgeClass(orden.estado)" class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wide border">
                {{ orden.estado.replace('_', ' ') }}
              </span>
            </div>
            <p class="text-slate-400 font-medium mt-1">Recepción: {{ formatDate(orden.fecha_recepcion) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <a :href="route('taller.reporte', orden.id)" target="_blank" class="flex items-center gap-3 px-6 py-3 bg-white/[0.05] border border-white/[0.1] rounded-2xl text-slate-300 font-bold hover:bg-white/[0.1] transition-all">
            <FontAwesomeIcon :icon="faPrint" class="text-brand-500" />
            <span>Imprimir Recepción</span>
          </a>
          <button
            v-if="!orden.venta"
            type="button"
            @click="generarVenta"
            class="flex items-center gap-3 px-6 py-3 bg-sky-600 border border-sky-500/50 rounded-2xl text-white font-black uppercase tracking-wide hover:bg-sky-700 transition-all shadow-lg shadow-sky-500/20"
          >
            <FontAwesomeIcon :icon="faCheckCircle" />
            <span>Crear Venta</span>
          </button>
          <button
            v-if="!orden.venta && orden.costo_final > 0"
            type="button"
            @click="facturar"
            :disabled="facturando"
            class="flex items-center gap-3 px-6 py-3 bg-emerald-600 border border-emerald-500/50 rounded-2xl text-white font-black uppercase tracking-wide hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/20 disabled:opacity-50"
          >
            <FontAwesomeIcon v-if="!facturando" :icon="faFileInvoiceDollar" />
            <span v-else class="mr-2 animate-spin border-2 border-white/30 border-t-white rounded-full w-4 h-4"></span>
            <span>Facturar</span>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Details -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Equipment & Client Summary -->
          <div class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] p-8 backdrop-blur-3xl shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <h3 class="text-xs font-black text-brand-500 uppercase tracking-[0.2em] mb-4">Información del Cliente</h3>
                <div class="space-y-3">
                  <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faUser" class="text-slate-500 w-4" />
                    <span class="text-white font-bold">{{ orden.cliente?.nombre_razon_social || orden.nombre_cliente }}</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faMobileAlt" class="text-slate-500 w-4" />
                    <span class="text-slate-300">{{ orden.telefono_cliente || 'N/A' }}</span>
                  </div>
                </div>
              </div>
              <div>
                <h3 class="text-xs font-black text-brand-500 uppercase tracking-[0.2em] mb-4">Equipo</h3>
                <div class="space-y-3">
                  <div class="flex items-center gap-3">
                    <FontAwesomeIcon :icon="faWrench" class="text-slate-500 w-4" />
                    <span class="text-white font-bold">{{ orden.equipo_marca }} {{ orden.equipo_modelo }}</span>
                  </div>
                  <div class="text-xs text-slate-400">Serie: <span class="text-slate-200 font-bold ml-1">{{ orden.equipo_serie || 'S/N' }}</span></div>
                </div>
              </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-white/[0.04]">
              <h3 class="text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Problema Reportado</h3>
              <p class="text-slate-300 leading-relaxed bg-white/[0.02] p-5 rounded-2xl border border-white/[0.05]">
                {{ orden.problema_reportado }}
              </p>
            </div>
          </div>

          <!-- Technical Update Form -->
          <div class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] p-8 backdrop-blur-3xl shadow-2xl">
            <h2 class="text-lg font-black text-white mb-6 flex items-center gap-3">
              <FontAwesomeIcon :icon="faTools" class="text-brand-500" />
              Actualización Técnica
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Cambiar Estado</label>
                  <select v-model="form.estado" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50">
                    <option value="recepcionado">Recepcionado</option>
                    <option value="en_revision">En Revisión</option>
                    <option value="reparando">Reparando</option>
                    <option value="listo">Listo para Entrega</option>
                    <option value="entregado">Entregado</option>
                    <option value="sin_reparacion">Sin Reparación</option>
                    <option value="cancelado">Cancelado</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Técnico Asignado</label>
                  <select v-model="form.tecnico_id" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50">
                    <option :value="null">Sin asignar</option>
                    <option v-for="t in tecnicos" :key="t.id" :value="t.id">{{ t.name }}</option>
                  </select>
                </div>
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Diagnóstico Interno</label>
                <textarea v-model="form.diagnostico" rows="3" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50"></textarea>
              </div>

              <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Trabajo Realizado (Para el Cliente)</label>
                <textarea v-model="form.trabajo_realizado" rows="3" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50"></textarea>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Costo Final</label>
                  <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">$</span>
                    <input v-model="form.costo_final" type="number" step="0.01" min="0" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 pl-8 pr-4 text-white focus:ring-2 focus:ring-brand-500/50">
                  </div>
                </div>
                <div class="space-y-2">
                  <label class="text-[10px] font-black text-slate-500 uppercase tracking-wide ml-1">Fecha Compromiso</label>
                  <input v-model="form.fecha_compromiso" type="date" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50">
                </div>
              </div>
              <div class="flex flex-col sm:flex-row justify-end items-center gap-4 pt-4">
                <button 
                  type="button" 
                  @click="handleUpdateClick"
                  :disabled="form.processing"
                  class="w-full sm:w-auto px-8 py-4 bg-[var(--ui-accent)] hover:brightness-110 text-[var(--ui-accent-contrast)] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-[var(--ui-accent)]/20 transition-all active:scale-95 disabled:opacity-50"
                >
                  <FontAwesomeIcon :icon="faSave" class="mr-2" />
                  Actualizar Orden
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Column: Signatures & History -->
        <div class="space-y-6">
          
          <!-- Signatures -->
          <div class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] p-8 backdrop-blur-3xl shadow-2xl relative overflow-hidden">
            <h2 class="text-sm font-black text-white uppercase tracking-wide mb-6 flex items-center gap-3">
              <FontAwesomeIcon :icon="faSignature" class="text-brand-500" />
              Firmas Digitales
            </h2>

            <div class="space-y-6">
              <div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-3">Firma de Recepción</p>
                <div class="aspect-[4/3] bg-white/[0.03] border border-white/[0.05] rounded-2xl flex items-center justify-center overflow-hidden group relative">
                  <img v-if="orden.firma_recepcion_url" :src="orden.firma_recepcion_url" class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-500" alt="Firma Entrada">
                  <div v-else class="text-slate-500 text-xs font-medium italic">Sin firma capturada</div>
                </div>
              </div>

              <div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-3">Firma de Entrega</p>
                <div class="aspect-[4/3] bg-white/[0.03] border border-white/[0.05] rounded-2xl flex items-center justify-center overflow-hidden group relative">
                  <img v-if="orden.firma_entrega_url" :src="orden.firma_entrega_url" class="w-full h-full object-contain p-4 group-hover:scale-110 transition-transform duration-500" alt="Firma Salida">
                  <div v-else class="text-slate-500 text-xs font-medium italic">Pendiente de entrega</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions / Status -->
          <div class="bg-gradient-to-br from-brand-500/10 to-transparent border border-brand-500/20 rounded-[2.5rem] p-8 backdrop-blur-3xl">
             <div class="flex items-center gap-4 mb-6">
                <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center text-brand-500">
                  <FontAwesomeIcon :icon="faCheckCircle" />
                </div>
                <p class="text-sm font-black text-white uppercase tracking-wide">Resumen</p>
             </div>
             <div class="space-y-6">
                <div class="flex justify-between items-center py-2 border-b border-white/[0.05]">
                  <span class="text-xs text-slate-400 font-bold">Estado Actual:</span>
                  <span class="text-xs text-white font-black uppercase tracking-wider">{{ orden.estado.replace('_', ' ') }}</span>
                </div>

                <div class="flex justify-between items-center py-2 border-b border-white/[0.05]">
                  <span class="text-xs text-slate-400 font-bold">Costo Final:</span>
                  <span class="text-xs text-white font-black">${{ Number(orden.costo_final || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                </div>

                <div v-if="orden.venta" class="mt-4 pt-4 border-t border-brand-500/20">
                  <p class="text-[10px] font-black text-emerald-500 uppercase tracking-wide mb-3">Venta Vinculada</p>
                  <Link :href="route('ventas.show', orden.venta.id)" class="group block p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl hover:bg-emerald-500/20 transition-all">
                    <div class="flex justify-between items-center mb-1">
                      <span class="text-xs font-black text-white group-hover:text-emerald-400 transition-colors">
                        <FontAwesomeIcon :icon="faReceipt" class="mr-2" />
                        {{ orden.venta.numero_venta }}
                      </span>
                      <FontAwesomeIcon :icon="faExternalLinkAlt" class="text-[10px] text-slate-500 group-hover:text-white" />
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-[10px] text-slate-400">Total:</span>
                      <span class="text-xs font-bold text-emerald-400">${{ Number(orden.venta.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                    </div>
                  </Link>
                </div>
             </div>
          </div>

        </div>

      </div>

    </div>

    <!-- Modales -->
    <FirmaModal 
      :show="mostrarModalFirma"
      title="Firma de Entrega"
      subtitle="El cliente debe firmar la entrega del equipo"
      @close="mostrarModalFirma = false"
      @confirm="onFirmaConfirmada"
    />
  </div>
</template>

<style scoped>
</style>
