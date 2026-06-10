<script setup>
import { ref, computed, onMounted, watch, reactive, nextTick } from 'vue'
import { router, Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import IndexTable from '@/Components/IndexTable.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const props = defineProps({
  polizas: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  cuentasBancarias: { type: Array, default: () => [] },
  cuentasContables: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ total: 0, diario: 0, ingreso: 0, egreso: 0 }) },
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

onMounted(() => {
  const flash = usePage().props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
  fetchSaldosXml()
  const params = new URLSearchParams(window.location.search)
  if (params.get('show_saldos')) showSaldos.value = true
})

const searchTerm = ref(props.filters.search || '')
const tipoFilter = ref(props.filters.tipo || '')
const fechaInicio = ref(props.filters.fecha_inicio || '')
const fechaFin = ref(props.filters.fecha_fin || '')
const fileInput = ref(null)
const loading = ref(false)
const selectedPoliza = ref(null)
const showModal = ref(false)
const selectedDocumentos = ref([])
const soportesInput = ref(null)
const uploadingSoportes = ref(false)
const showManualModal = ref(false)
const savingManual = ref(false)

const accountSearch = reactive([]) // [{ query: '', show: false }]
const activeInputRect = ref({ top: 0, left: 0, width: 0 })
const activeIndex = ref(-1)
const dropdownSelectedIndex = ref(0)

const debeRefs = reactive([])
const haberRefs = reactive([])

const updateActiveRect = (i, event) => {
  const rect = event.target.getBoundingClientRect()
  activeInputRect.value = { 
    top: rect.top + window.scrollY, 
    left: rect.left + window.scrollX, 
    width: rect.width,
    height: rect.height
  }
  activeIndex.value = i
  accountSearch[i].show = true
  // Resetear el índice de selección al escribir o enfocar
  if (event.type === 'input') {
    dropdownSelectedIndex.value = 0
  }
}

const handleSearchKeyDown = (idx, event) => {
  const matches = filteredCuentas(accountSearch[idx].query)
  if (!matches.length) return

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    if (dropdownSelectedIndex.value < matches.length - 1) dropdownSelectedIndex.value++
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    if (dropdownSelectedIndex.value > 0) dropdownSelectedIndex.value--
  } else if (event.key === 'Enter') {
    event.preventDefault()
    selectAccount(idx, matches[dropdownSelectedIndex.value])
  }
}

const manualForm = reactive({
  fecha: new Date().toLocaleDateString('sv-SE'), // Formato YYYY-MM-DD local
  tipo: 'egreso',
  concepto: '',
  asientos: [
    { cuenta_id: null, debe: 0, haber: 0 },
    { cuenta_id: null, debe: 0, haber: 0 }
  ],
  files: [] // Soporte para archivos adjuntos
})

const manualFilesInput = ref(null)
const handleManualFiles = (e) => {
  manualForm.files = Array.from(e.target.files)
}

const loadTemplate = (template) => {
  if (name === 'didi') {
    manualForm.concepto = 'Gasto DiDi / Extranjero'
    manualForm.tipo = 'egreso'
    const cuentaGasto = props.cuentasContables.find(c => c.codigo === '602.01') || props.cuentasContables.find(c => c.codigo.startsWith('602'))
    const cuentaBanco = props.cuentasContables.find(c => c.codigo.startsWith('102'))
    
    manualForm.asientos = [
      { cuenta_id: cuentaGasto?.id || null, debe: 0, haber: 0 },
      { cuenta_id: cuentaBanco?.id || null, debe: 0, haber: 0 }
    ]
    notyf.success('Plantilla DiDi cargada. Captura el monto.')
  }
}

const sortField = ref(props.filters.sort || 'fecha')
const sortDir = ref(props.filters.sort_dir || 'desc')

const buildQuery = () => ({
  search: searchTerm.value,
  tipo: tipoFilter.value,
  fecha_inicio: fechaInicio.value,
  fecha_fin: fechaFin.value,
  sort: sortField.value,
  sort_dir: sortDir.value,
})

const applyFilters = () => {
  router.get(route('contabilidad.index'), buildQuery(), { preserveState: true, replace: true })
}

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
  applyFilters()
}

const sortIndicator = (field) => {
  if (sortField.value !== field) return ''
  return sortDir.value === 'asc' ? ' ↑' : ' ↓'
}

const setRange = (preset) => {
  const today = new Date()
  const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0')
  let start = new Date()
  switch (preset) {
    case 'day': break
    case 'week': start.setDate(today.getDate() - today.getDay()); break
    case 'month': start = new Date(today.getFullYear(), today.getMonth(), 1); break
    case 'year': start = new Date(today.getFullYear(), 0, 1); break
    default: return
  }
  fechaInicio.value = fmt(start)
  fechaFin.value = fmt(today)
  applyFilters()
}

const clearRange = () => {
  fechaInicio.value = ''
  fechaFin.value = ''
  mesRapido.value = ''
  applyFilters()
}

const mesRapido = ref('')
const mesesDisponibles = computed(() => {
  const nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
  const anioActual = new Date().getFullYear()
  const list = []
  for (let y = anioActual; y >= anioActual - 2; y--) {
    for (let m = 12; m >= 1; m--) {
      list.push({
        label: `${nombres[m-1]} ${y}`,
        value: `${y}-${String(m).padStart(2, '0')}`
      })
    }
  }
  return list
})

const aplicarMesRapido = () => {
  if (!mesRapido.value) {
    clearRange()
    return
  }
  const [year, month] = mesRapido.value.split('-').map(Number)
  const start = new Date(year, month - 1, 1)
  const end = new Date(year, month, 0)
  const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0')
  fechaInicio.value = fmt(start)
  fechaFin.value = fmt(end)
  applyFilters()
}

watch([fechaInicio, fechaFin], () => {
  if (fechaInicio.value && fechaFin.value && fechaInicio.value.length === 10 && fechaInicio.value.endsWith('-01')) {
    const startParts = fechaInicio.value.split('-')
    const end = new Date(Number(startParts[0]), Number(startParts[1]), 0)
    const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0')
    if (fechaFin.value === fmt(end)) {
      mesRapido.value = `${startParts[0]}-${startParts[1]}`
      return
    }
  }
  mesRapido.value = ''
}, { immediate: true })

const triggerUpload = () => fileInput.value.click()

// CFDI Pendientes Modal
const showCfdiModal = ref(false)
const cfdisPendientes = ref([])
const loadingCfdis = ref(false)
const integrandoCfdi = ref(null)
const cfdiFiltro = ref('todos')
const cfdiSearch = ref('')
const ahora = new Date()
const cfdiMes = ref(String(ahora.getMonth() + 1).padStart(2, '0'))
const cfdiAnio = ref(String(ahora.getFullYear()))
const cfdiMetodoPago = ref('')
const selectedCfdis = ref([])
const integrandoMulti = ref(false)

// Saldos XML (AR/AP)
const saldosData = ref({ por_cobrar: { items: [], total: 0, count: 0 }, por_pagar: { items: [], total: 0, count: 0 } })
const showSaldos = ref(false)
const loadingSaldos = ref(false)

const fetchSaldosXml = async () => {
  loadingSaldos.value = true
  try {
    const res = await axios.get(route('contabilidad.api.saldos-xml'))
    if (res.data.success) {
      saldosData.value = res.data.data
    }
  } catch (e) {
    console.error('Error fetching saldos XML:', e)
  } finally {
    loadingSaldos.value = false
  }
}

const aniosDisponibles = computed(() => {

  const years = [new Date().getFullYear()]
  for (let i = 1; i <= 3; i++) {
    years.push(years[0] - i)
  }
  return years.sort()
})

const toggleSelectCfdi = (uuid) => {
  const idx = selectedCfdis.value.indexOf(uuid)
  if (idx > -1) selectedCfdis.value.splice(idx, 1)
  else selectedCfdis.value.push(uuid)
}

let fechaMultiSeleccionada = ''

const integrarMultiCfdi = async () => {
  if (selectedCfdis.value.length < 2) { notyf.error('Selecciona al menos 2 CFDI'); return }

  // Check for mixed PUE/PPD
  const selectedItems = cfdisPendientes.value.filter(c => selectedCfdis.value.includes(c.uuid))
  const tiposPago = [...new Set(selectedItems.map(c => c.metodo_pago).filter(Boolean))]
  if (tiposPago.length > 1) {
    const noContinuar = await Swal.fire({
      icon: 'warning',
      title: 'Métodos de pago mixtos',
      html: `<p class="text-sm">Estás agrupando CFDI con diferentes métodos de pago:<br>
        <strong>${tiposPago.join(' y ')}</strong></p>
        <p class="text-xs text-slate-500 mt-2">Los PPD quedan como pendientes (DIARIO) y los PUE como pagados (INGRESO/EGRESO).<br>
        Se usará el método predominante para el tipo de póliza.</p>`,
      confirmButtonText: 'Continuar de todas formas',
      confirmButtonColor: '#F59E0B',
      showCancelButton: true,
      cancelButtonText: 'Revisar selección',
    })
    if (!noContinuar.isConfirmed) return
  }

  integrandoMulti.value = true
  try {
    const previewRes = await axios.post(route('contabilidad.preview-multi'), { uuids: selectedCfdis.value })
    if (!previewRes.data.success) { notyf.error(previewRes.data.message); return }
    const p = previewRes.data.preview
    const items = previewRes.data.items || []
    fechaMultiSeleccionada = p.fecha || ''

    let itemsHtml = items.map(i => `
      <tr class="group transition-colors hover:bg-white/[0.02]">
        <td class="px-4 py-3 text-xs font-mono font-bold text-slate-500 border-b border-white/5">${i.folio}</td>
        <td class="px-4 py-3 text-xs font-bold text-slate-200 border-b border-white/5">${i.emisor || i.receptor || ''}</td>
        <td class="px-4 py-3 text-xs text-right font-mono font-black text-white border-b border-white/5">$${i.total.toLocaleString('es-MX', {minimumFractionDigits: 2})}</td>
      </tr>`).join('')
    let rowsHtml = p.asientos.map(a => `
      <tr class="group transition-colors hover:bg-white/[0.02]">
        <td class="px-4 py-4 text-xs font-mono font-bold text-slate-500 border-b border-white/5">${a.cuenta_codigo}</td>
        <td class="px-4 py-4 text-xs border-b border-white/5">
          <span class="font-bold text-slate-200">${a.cuenta_nombre}</span>
          ${a.auxiliar ? '<br><span class="text-[10px] text-slate-500 font-medium">' + a.auxiliar + '</span>' : ''}
        </td>
        <td class="px-4 py-4 text-xs text-right font-mono font-black text-white border-b border-white/5">${a.debe > 0 ? '$'+a.debe.toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—'}</td>
        <td class="px-4 py-4 text-xs text-right font-mono font-black text-white border-b border-white/5">${a.haber > 0 ? '$'+a.haber.toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—'}</td>
      </tr>`).join('')

    const modalContent = `
      <div class="text-left font-sans selection:bg-brand-500/30">
        <!-- Header Card -->
        <div class="mb-8 p-6 rounded-[2.5rem] bg-slate-800/50 border border-white/5 shadow-2xl relative overflow-hidden">
          <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-brand-500 text-slate-950 flex items-center justify-center shadow-lg">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                </div>
                <div>
                  <h3 class="text-xs font-black uppercase tracking-[0.2em] text-brand-500/80">Agrupar ${selectedCfdis.value.length} CFDI</h3>
                </div>
              </div>
              <div class="text-right">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Tipo Póliza</p>
                <span class="px-3 py-1 bg-slate-900 rounded-full text-[10px] font-black text-slate-300 border border-white/5 uppercase tracking-widest">${p.tipo}</span>
              </div>
            </div>
            <h4 class="text-xl font-black text-white leading-tight mb-6 pr-10">${p.concepto}</h4>
            <div class="grid grid-cols-2 gap-4 pt-6 border-t border-white/5">
              <div class="p-4 rounded-3xl bg-white/[0.03] border border-white/5">
                <p class="text-[10px] uppercase font-black text-slate-500 tracking-widest mb-2">Fecha de la póliza</p>
                <input id="swal-fecha" type="date" value="${p.fecha}" class="w-full px-4 py-2 bg-slate-900 border border-white/10 rounded-xl text-sm font-bold text-white focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all">
              </div>
              <div class="p-4 rounded-3xl bg-brand-500/[0.03] border-brand-500/10 border">
                <p class="text-[10px] uppercase font-black text-brand-500/60 tracking-widest mb-1">Total</p>
                <p class="text-2xl font-black text-amber-400">$${p.total.toLocaleString('es-MX', {minimumFractionDigits: 2})}</p>
              </div>
            </div>
          </div>
          <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-brand-500/10 rounded-full blur-[80px]"></div>
        </div>

        <!-- Items Table -->
        <div class="rounded-[2.5rem] border border-white/5 overflow-hidden bg-slate-900 shadow-2xl mb-6">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-white/[0.02]">
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Folio</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Contraparte</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">${itemsHtml}</tbody>
          </table>
        </div>

        <!-- Accounts Table -->
        <div class="rounded-[2.5rem] border border-white/5 overflow-hidden bg-slate-900 shadow-2xl">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-white/[0.02]">
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Cuenta</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Descripción</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Debe</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Haber</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">${rowsHtml}</tbody>
          </table>
        </div>

        <div class="mt-8 flex items-center gap-4 p-5 bg-brand-500/[0.02] border-brand-500/10 rounded-[2rem] border">
          <div class="w-10 h-10 rounded-2xl bg-brand-500/10 text-brand-500 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <p class="text-xs text-slate-400 font-medium leading-relaxed">
            Verifica las cuentas y el total antes de confirmar. Se creará <span class="text-brand-500 font-bold">una sola póliza</span> agrupando los ${selectedCfdis.value.length} CFDI seleccionados.
          </p>
        </div>
      </div>`

    const { value: formValues } = await Swal.fire({
      title: null,
      html: modalContent,
      showCancelButton: true,
      confirmButtonText: 'Confirmar e Integrar',
      cancelButtonText: 'Cerrar',
      confirmButtonColor: '#F59E0B',
      cancelButtonColor: '#1e293b',
      width: 850,
      padding: '0',
      background: '#0f172a',
      showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
      hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' },
      customClass: {
        container: 'backdrop-blur-sm',
        popup: 'rounded-[3rem] border border-white/10 shadow-2xl overflow-hidden',
        htmlContainer: 'm-0 p-8',
        confirmButton: 'rounded-2xl px-10 py-4 text-[11px] font-black uppercase tracking-[0.2em] transition-all hover:scale-105 active:scale-95 hover:shadow-[0_0_20px_rgba(245,158,11,0.3)]',
        cancelButton: 'rounded-2xl px-10 py-4 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-white transition-all'
      },
      preConfirm: () => { return document.getElementById('swal-fecha').value },
    })
    if (!formValues) return

    fechaMultiSeleccionada = formValues

    const res = await axios.post(route('contabilidad.integrar-multi'), { uuids: selectedCfdis.value, fecha: fechaMultiSeleccionada })
    if (res.data.success) {
      notyf.success(res.data.message)
      selectedCfdis.value.forEach(u => { const c = cfdisPendientes.value.find(c => c.uuid === u); if (c) c.integrada = true })
      selectedCfdis.value = []
    }
  } catch (e) {
    const msg = e.response?.data?.message || 'Error'
    // Check if message contains a poliza number like #E00056
    const match = msg.match(/#(\w+\d+)/)
    if (match) {
      Swal.fire({
        icon: 'warning',
        title: 'Ya integrado',
        html: `<p class="text-sm">${msg.replace(/(#\w+\d+)/, '<strong>$1</strong>')}</p>
               <a href="#" onclick="event.preventDefault(); window.location.href='/contabilidad/polizas'" class="text-brand-600 underline text-sm font-semibold mt-2 inline-block">Ir a Contabilidad</a>`,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#F59E0B',
      })
    } else {
      notyf.error(msg)
    }
  }
  finally {
    integrandoMulti.value = false
  }
}

const cfdisFiltrados = computed(() => {
  let items = [...cfdisPendientes.value]
  
  // Filtrar primero
  if (cfdiFiltro.value === 'integrado') items = items.filter(c => c.integrada)
  else if (cfdiFiltro.value === 'pendiente') items = items.filter(c => !c.integrada)
  else if (cfdiFiltro.value === 'emitido') items = items.filter(c => c.direccion === 'emitido')
  else if (cfdiFiltro.value === 'recibido') items = items.filter(c => c.direccion === 'recibido')
  else if (['I', 'E', 'P', 'N'].includes(cfdiFiltro.value)) items = items.filter(c => c.tipo === cfdiFiltro.value)

  // Filtro por búsqueda (Folio, UUID, Nombre)
  if (cfdiSearch.value) {
    const s = cfdiSearch.value.toLowerCase()
    items = items.filter(c => 
      (c.folio && c.folio.toLowerCase().includes(s)) || 
      (c.uuid && c.uuid.toLowerCase().includes(s)) ||
      (c.emisor && c.emisor.toLowerCase().includes(s)) ||
      (c.receptor && c.receptor.toLowerCase().includes(s))
    )
  }

  // Filtro por mes/año
  if (cfdiMes.value || cfdiAnio.value) {
    items = items.filter(c => {
      if (!c.fecha) return true
      const parts = c.fecha.split('/')
      if (parts.length < 3) return true
      if (cfdiMes.value && parts[1] !== cfdiMes.value) return false
      if (cfdiAnio.value && parts[2] !== cfdiAnio.value) return false
      return true
    })
  }

  // Filtro PUE/PPD
  if (cfdiMetodoPago.value) {
    items = items.filter(c => {
      const uuid = c.uuid || ''
      // Check the backend field if available, otherwise infer from tipo/folio
      return c.metodo_pago === cfdiMetodoPago.value
    })
  }

  // Luego ordenar por integración (integradas al fondo) y fecha
  return items.sort((a, b) => {
    if (a.integrada && !b.integrada) return 1
    if (!a.integrada && b.integrada) return -1
    
    const dateA = a.fecha_inicial_pago_raw || a.fecha_raw || ''
    const dateB = b.fecha_inicial_pago_raw || b.fecha_raw || ''
    return dateA.localeCompare(dateB) // Ascendente (más antiguo primero)
  })
})

const cargarCfdisPendientes = async () => {
  loadingCfdis.value = true
  try {
    const res = await axios.get(route('contabilidad.cfdis-pendientes'), {
      params: { mes: cfdiMes.value, anio: cfdiAnio.value }
    })
    cfdisPendientes.value = res.data.cfdis || []
  } catch { notyf.error('Error al cargar CFDI') }
  loadingCfdis.value = false
}

const auditBancosData = ref(null)
const loadingAuditBancos = ref(false)
const showAuditBancosModal = ref(false)

const abrirAuditoriaBancosModal = async () => {
  loadingAuditBancos.value = true
  showAuditBancosModal.value = true
  try {
    const res = await axios.get(route('api.audit-bancos-balanza'))
    if (res.data?.success) {
      auditBancosData.value = res.data
    }
  } catch (e) {
    notyf.error('Error al ejecutar auditoría de bancos vs mayor')
  } finally {
    loadingAuditBancos.value = false
  }
}

const abrirCfdiModal = async () => {
  showCfdiModal.value = true
  cfdiFiltro.value = 'pendiente'
  selectedCfdis.value = []
  if (!cfdiMes.value) cfdiMes.value = String(new Date().getMonth() + 1).padStart(2, '0')
  if (!cfdiAnio.value) cfdiAnio.value = String(new Date().getFullYear())
  
  await cargarCfdisPendientes()
}

watch([cfdiMes, cfdiAnio], () => {
  if (showCfdiModal.value) cargarCfdisPendientes()
})

const integrarCfdiModal = async (cfdi, currentClasificacion = '') => {
  integrandoCfdi.value = cfdi.uuid
  try {
    const preview = await axios.post(route('cfdi.contabilidad.preview', cfdi.uuid), {
      clasificacion: currentClasificacion
    })
    if (!preview.data.success) { notyf.error(preview.data.message); return }
    const p = preview.data.preview
    const esCompra = p.tipo === 'egreso' || p.tipo === 'diario'
    const isRep = cfdi.tipo === 'P' || p.concepto.toLowerCase().includes('pago');

    const getTableHtml = (asientos) => {
      return asientos.map((a, idx) => {
        const isExpenseRow = idx === 0 && esCompra && !isRep;
        return `
          <tr class="group transition-colors hover:bg-white/[0.02]">
            <td class="px-4 py-4 text-xs font-mono font-bold text-slate-500 border-b border-white/5">${a.cuenta_codigo}</td>
            <td class="px-4 py-4 text-xs border-b border-white/5">
              <div class="flex flex-col">
                ${isExpenseRow ? `
                  <div class="relative mb-1">
                    <select id="swal-clasificacion-inline" class="w-full py-2 pl-3 pr-10 bg-slate-900 border border-brand-500/30 rounded-xl text-[11px] font-black text-brand-400 appearance-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all cursor-pointer outline-none shadow-lg shadow-brand-500/5">
                      <option value="" ${currentClasificacion === '' ? 'selected' : ''}>${a.cuenta_nombre} (Automático)</option>
                      <option value="costo" ${currentClasificacion === 'costo' ? 'selected' : ''}>Costo de Venta (Reventa)</option>
                      <option value="gasto" ${currentClasificacion === 'gasto' ? 'selected' : ''}>Gasto Administrativo</option>
                      <option value="activo" ${currentClasificacion === 'activo' ? 'selected' : ''}>Equipo / Activo Fijo</option>
                    </select>
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-brand-500">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                  </div>
                  <span class="text-[9px] text-brand-500/50 font-bold uppercase tracking-widest px-1">Clasificar como...</span>
                ` : `
                  <span class="font-bold text-slate-200">${a.cuenta_nombre}</span>
                  ${a.auxiliar ? '<span class="text-[10px] text-slate-500 font-medium">' + a.auxiliar + '</span>' : ''}
                `}
              </div>
            </td>
            <td class="px-4 py-4 text-xs text-right font-mono font-black text-white border-b border-white/5">${a.debe > 0 ? '$' + a.debe.toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—'}</td>
            <td class="px-4 py-4 text-xs text-right font-mono font-black text-white border-b border-white/5">${a.haber > 0 ? '$' + a.haber.toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—'}</td>
          </tr>
        `}).join('')
    }

    const modalContent = `
      <div class="text-left font-sans selection:bg-brand-500/30">
        <!-- Header Info Card (Premium Dark) -->
        <div class="mb-8 p-6 rounded-[2.5rem] bg-slate-800/50 border border-white/5 shadow-2xl relative overflow-hidden group">
          <div class="relative z-10">
            <div class="flex justify-between items-center mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl ${isRep ? 'bg-blue-500 text-slate-950' : 'bg-brand-500 text-slate-950'} flex items-center justify-center shadow-lg">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${isRep 
                      ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />'
                      : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'}
                  </svg>
                </div>
                <div>
                  <h3 class="text-xs font-black uppercase tracking-[0.2em] ${isRep ? 'text-blue-400' : 'text-brand-500/80'}">
                    ${isRep ? 'Complemento de Pago (REP)' : 'Vista Previa de Póliza'}
                  </h3>
                  <p class="text-xs font-mono text-slate-500">ID: ${cfdi.uuid.substring(0, 8)}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Tipo Póliza</p>
                <span class="px-3 py-1 bg-slate-900 rounded-full text-[10px] font-black text-slate-300 border border-white/5 uppercase tracking-widest">${p.tipo}</span>
              </div>
            </div>
            
            <h4 class="text-xl font-black text-white leading-tight mb-6 pr-10">${p.concepto}</h4>
            
            <div class="grid grid-cols-2 gap-4 pt-6 border-t border-white/5">
              <div class="p-4 rounded-3xl bg-white/[0.03] border border-white/5">
                <p class="text-[10px] uppercase font-black text-slate-500 tracking-widest mb-1">Fecha Emisión</p>
                <p class="text-sm font-bold text-slate-200">${p.fecha}</p>
              </div>
              <div class="p-4 rounded-3xl ${isRep ? 'bg-blue-500/[0.03] border-blue-500/10' : 'bg-brand-500/[0.03] border-brand-500/10'}">
                <p class="text-[10px] uppercase font-black ${isRep ? 'text-blue-500/60' : 'text-brand-500/60'} tracking-widest mb-1">Total Pagado</p>
                <p class="text-2xl font-black ${isRep ? 'text-blue-400' : 'text-amber-400'}">$${p.total.toLocaleString('es-MX', {minimumFractionDigits: 2})}</p>
              </div>
            </div>
          </div>
          <!-- Abstract Background Elements -->
          <div class="absolute -right-20 -bottom-20 w-64 h-64 ${isRep ? 'bg-blue-500/10' : 'bg-brand-500/10'} rounded-full blur-[80px]"></div>
        </div>

        <!-- Accounts Table (Dark Glass) -->
        <div class="rounded-[2.5rem] border border-white/5 overflow-hidden bg-slate-900 shadow-2xl">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-white/[0.02]">
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Cuenta</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-left">Descripción / Clasificación</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Debe</th>
                <th class="px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] text-right">Haber</th>
              </tr>
            </thead>
            <tbody id="swal-table-body" class="divide-y divide-white/5">
              ${getTableHtml(p.asientos)}
            </tbody>
          </table>
        </div>
        
        <!-- Bank Account Selector -->
        <div class="mt-6 p-5 rounded-[2rem] bg-indigo-500/[0.03] border border-indigo-500/10">
          <label class="block text-[10px] font-black uppercase tracking-wider text-indigo-400 mb-3">Cuenta Bancaria / Caja</label>
          <select id="swal-banco" class="w-full py-3 pl-4 pr-10 bg-slate-900 border border-white/10 rounded-xl text-xs font-bold text-slate-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none cursor-pointer appearance-none">
            <option value="">No especificar (sin movimiento bancario)</option>
            ${(props.cuentasBancarias || []).map(b => `<option value="${b.id}">${b.nombre || b.alias || b.banco} (${b.banco || ''}) - ${b.numero_cuenta || b.cuenta || 'S/N'} | $${(b.saldo_actual || b.saldo || 0).toLocaleString('es-MX', {minimumFractionDigits: 2})}</option>`).join('')}
          </select>
          <p class="text-[9px] text-indigo-500/70 mt-2 font-medium">Selecciona la cuenta donde se registró el movimiento de dinero.</p>
        </div>
        
        <div class="mt-8 flex items-center gap-4 p-5 ${isRep ? 'bg-blue-500/[0.02] border-blue-500/10' : 'bg-brand-500/[0.02] border-brand-500/10'} rounded-[2rem] border">
          <div class="w-10 h-10 rounded-2xl ${isRep ? 'bg-blue-500/10 text-blue-500' : 'bg-brand-500/10 text-brand-500'} flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <p class="text-xs text-slate-400 font-medium leading-relaxed">
            ${isRep 
              ? 'Este es un <span class="text-blue-400 font-bold">Complemento de Pago</span>. El sistema afectará la cuenta de Bancos y la cuenta del Cliente/Proveedor.'
              : 'Verifica que la <span class="text-brand-500 font-bold tracking-tight">clasificación</span> sea la correcta antes de integrar.'}
          </p>
        </div>
      </div>
    `

    const result = await Swal.fire({
      title: null,
      html: modalContent,
      showCancelButton: true,
      confirmButtonText: isRep ? 'Generar Póliza de Pago' : 'Confirmar e Integrar',
      cancelButtonText: 'Cerrar',
      confirmButtonColor: isRep ? '#3b82f6' : '#F59E0B',
      cancelButtonColor: '#1e293b',
      width: 850,
      padding: '0',
      background: '#0f172a',
      showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
      hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' },
      customClass: {
        container: 'backdrop-blur-sm',
        popup: 'rounded-[3rem] border border-white/10 shadow-2xl overflow-hidden',
        htmlContainer: 'm-0 p-8',
        confirmButton: `rounded-2xl px-10 py-4 text-[11px] font-black uppercase tracking-[0.2em] transition-all hover:scale-105 active:scale-95 ${isRep ? 'hover:shadow-[0_0_20px_rgba(59,130,246,0.3)]' : 'hover:shadow-[0_0_20px_rgba(245,158,11,0.3)]'}`,
        cancelButton: 'rounded-2xl px-10 py-4 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-white transition-all'
      },
      didOpen: () => {
        const select = document.getElementById('swal-clasificacion-inline')
        if (select) {
          select.addEventListener('change', async (e) => {
            const newVal = e.target.value
            const tableBody = document.getElementById('swal-table-body')
            tableBody.innerHTML = `<tr><td colspan="4" class="py-20 text-center"><div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-brand-500/10 border-t-brand-500"></div></td></tr>`
            integrarCfdiModal(cfdi, newVal)
          })
        }
      },
      preConfirm: () => {
        const select = document.getElementById('swal-clasificacion-inline')
        const banco = document.getElementById('swal-banco')
        return { clasificacion: select ? select.value : currentClasificacion, banco_id: banco ? parseInt(banco.value) || null : null }
      }
    })



    if (!result.isConfirmed) return

    const selectedClasificacion = result.value?.clasificacion
    const selectedBancoId = result.value?.banco_id || null
    const create = await axios.post(route('cfdi.contabilidad', cfdi.uuid), {
      clasificacion: selectedClasificacion,
      banco_id: selectedBancoId
    })
    
    if (create.data.success) {
      notyf.success(create.data.message)
      cfdi.integrada = true
      cfdi.poliza_id = create.data.poliza_id
      cfdi.poliza_numero = create.data.poliza_numero
    }
  } catch (e) { 
    const msg = e.response?.data?.message || 'Error al procesar el CFDI'
    notyf.error(msg)
  } finally {
    integrandoCfdi.value = null
  }
}



const verPdf = (uuid) => {
  window.open(route('cfdi.ver-pdf', uuid), '_blank')
}

const verXml = (uuid) => {
  window.open(route('cfdi.descargar-xml', { uuid, inline: 1 }), '_blank')
}

const desasociarCfdi = async (cfdi) => {
  const result = await Swal.fire({
    title: '¿Desasociar CFDI?',
    text: `¿Desasociar este CFDI? Se eliminará la póliza #${cfdi.poliza_numero}. Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, desasociar',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) {
    router.delete(route('contabilidad.destroy', cfdi.poliza_id), {
      onSuccess: () => {
        notyf.success('CFDI desasociado y póliza eliminada')
        cfdi.integrada = false
        cfdi.poliza_id = null
        cfdi.poliza_numero = null
      },
      onError: () => notyf.error('Error al desasociar')
    })
  }
}

const showPreviewModal = ref(false)
const previewData = ref(null)
const selectedBancoId = ref('')

const handleFileUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return

  loading.value = true
  const formData = new FormData()
  formData.append('xml', file)

  axios.post(route('contabilidad.preview-xml'), formData)
    .then(res => {
      previewData.value = res.data
      showPreviewModal.value = true
      loading.value = false
    })
    .catch(err => {
      notyf.error(err.response?.data?.error || 'Error al previsualizar XML')
      loading.value = false
    })
    .finally(() => {
      e.target.value = ''
    })
}

const confirmarPoliza = () => {
  if (!previewData.value) return
  loading.value = true
  
  router.post(route('contabilidad.upload-xml'), {
    xml_content: previewData.value.xml_content,
    cuenta_bancaria_id: selectedBancoId.value
  }, {
    onSuccess: () => {
      notyf.success('Póliza generada correctamente')
      showPreviewModal.value = false
      loading.value = false
      selectedBancoId.value = ''
    },
    onError: (errors) => {
      notyf.error(errors.xml || 'Error al guardar póliza')
      loading.value = false
    }
  })
}

const verDetalles = async (poliza) => {
  // Usar los datos que ya están cargados en la tabla (incluyen asientos)
  selectedPoliza.value = { ...poliza } // Copia para evitar mutar la fila original
  selectedDocumentos.value = []
  showModal.value = true
  
  loading.value = true
  try {
    const response = await axios.get(route('contabilidad.show', poliza.id))
    if (response.data?.poliza) {
      // Preservar multi_uuids si ya existían en la fila
      const polizaData = response.data.poliza
      if (poliza.multi_uuids && !polizaData.multi_uuids) {
        polizaData.multi_uuids = poliza.multi_uuids
      }
      selectedPoliza.value = polizaData
    }
    if (response.data?.documentos) {
      selectedDocumentos.value = response.data.documentos
    }
  } catch (e) {
    console.error('Error al cargar detalles:', e)
  } finally {
    loading.value = false
  }
}

const showEditModal = ref(false)
const editForm = ref({
  id: null,
  concepto: '',
  fecha: '',
  estado: 'borrador'
})

const editPoliza = (poliza) => {
  editForm.value = {
    id: poliza.id,
    concepto: poliza.concepto,
    fecha: poliza.fecha,
    estado: poliza.estado
  }
  showEditModal.value = true
}

const updatePoliza = () => {
  router.put(route('contabilidad.update', editForm.value.id), editForm.value, {
    onSuccess: () => {
      notyf.success('Póliza actualizada')
      showEditModal.value = false
    },
    onError: () => notyf.error('Error al actualizar')
  })
}

const deletePoliza = async (poliza) => {
  const result = await Swal.fire({
    title: '¿Eliminar póliza?',
    text: `¿Estás seguro de eliminar la póliza ${poliza.numero}? Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })
  if (result.isConfirmed) {
    router.delete(route('contabilidad.destroy', poliza.id), {
      onSuccess: () => notyf.success('Póliza eliminada'),
      onError: () => notyf.error('Error al eliminar')
    })
  }
}

const triggerSoportesUpload = () => soportesInput.value.click()

const handleSoportesUpload = async (e) => {
  const files = Array.from(e.target.files)
  if (files.length === 0) return

  uploadingSoportes.value = true
  const formData = new FormData()
  files.forEach(file => formData.append('files[]', file))

  try {
    const res = await axios.post(route('contabilidad.polizas.soportes', selectedPoliza.value.id), formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.data) {
      notyf.success('Documentos adjuntados')
      // Actualizar la póliza seleccionada con los nuevos soportes
      const updatedRes = await axios.get(route('contabilidad.show', selectedPoliza.value.id))
      if (updatedRes.data?.poliza) {
        selectedPoliza.value = updatedRes.data.poliza
        // También actualizar en la lista principal
        const idx = props.polizas.data.findIndex(p => p.id === selectedPoliza.value.id)
        if (idx > -1) props.polizas.data[idx].soportes = updatedRes.data.poliza.soportes
      }
    }
  } catch (err) {
    notyf.error('Error al subir archivos')
  } finally {
    uploadingSoportes.value = false
    e.target.value = ''
  }
}

const deleteSoporte = async (index) => {
  const result = await Swal.fire({
    title: '¿Eliminar documento?',
    text: '¿Estás seguro de eliminar este documento?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  })
  if (!result.isConfirmed) return
  
  try {
    await axios.delete(route('contabilidad.polizas.soportes.destroy', { poliza: selectedPoliza.value.id, index }))
    notyf.success('Documento eliminado')
    selectedPoliza.value.soportes.splice(index, 1)
  } catch (err) {
    notyf.error('Error al eliminar')
  }
}

const addAsiento = () => {
  manualForm.asientos.push({ cuenta_id: null, debe: 0, haber: 0 })
  accountSearch.push({ query: '', show: false })
}

const removeAsiento = (idx) => {
  if (manualForm.asientos.length <= 2) return
  manualForm.asientos.splice(idx, 1)
  accountSearch.splice(idx, 1)
}

const filteredCuentas = (query) => {
  if (!query) return props.cuentasContables.slice(0, 10)
  const q = query.toLowerCase()
  return props.cuentasContables.filter(c => 
    c.codigo.toLowerCase().includes(q) || 
    c.nombre.toLowerCase().includes(q)
  ).slice(0, 15)
}

const selectAccount = (idx, account) => {
  manualForm.asientos[idx].cuenta_id = account.id
  accountSearch[idx].query = `${account.codigo} - ${account.nombre}`
  accountSearch[idx].show = false

  // Auto-focus inteligente garantizado por ID
  let target = 'debe'
  const isBank = account.codigo.startsWith('102')

  if (manualForm.tipo === 'egreso') {
    target = isBank ? 'haber' : 'debe'
  } else if (manualForm.tipo === 'ingreso') {
    target = isBank ? 'debe' : 'haber'
  }

  nextTick(() => {
    const inputId = `${target}-input-${idx}`
    const el = document.getElementById(inputId)
    if (el) {
      el.focus()
      el.select()
    }
  })
}

const clearAccount = (idx) => {
  manualForm.asientos[idx].cuenta_id = null
  accountSearch[idx].query = ''
  accountSearch[idx].show = true
  
  nextTick(() => {
    const searchInput = document.getElementById(`search-input-${idx}`)
    if (searchInput) searchInput.focus()
  })
}

const focusNextRow = (idx) => {
  if (idx === manualForm.asientos.length - 1) {
    addAsiento()
  }
  nextTick(() => {
    const nextSearchInput = document.getElementById(`search-input-${idx + 1}`)
    if (nextSearchInput) {
      nextSearchInput.focus()
    }
  })
}

watch(showManualModal, (v) => {
  if (v) {
    accountSearch.splice(0, accountSearch.length)
    manualForm.asientos.forEach(a => {
      const acc = props.cuentasContables.find(c => c.id === a.cuenta_id)
      accountSearch.push({ 
        query: acc ? `${acc.codigo} - ${acc.nombre}` : '', 
        show: false 
      })
    })
  }
})

const manualTotalDebe = computed(() => manualForm.asientos.reduce((acc, a) => acc + Number(a.debe), 0))
const manualTotalHaber = computed(() => manualForm.asientos.reduce((acc, a) => acc + Number(a.haber), 0))
const manualDiferencia = computed(() => Math.abs(manualTotalDebe.value - manualTotalHaber.value))

// Auto-completar bancos según tipo
watch(() => manualForm.tipo, (newTipo) => {
  if (newTipo === 'diario') return
  
  // Buscar cuenta de bancos (102.01 o similar)
  const cuentaBanco = props.cuentasContables.find(c => c.codigo.startsWith('102'))
  if (!cuentaBanco) return

  // Limpiar asientos previos y poner uno base + bancos
  manualForm.asientos = [
    { cuenta_id: null, debe: 0, haber: 0 },
    { cuenta_id: cuentaBanco.id, debe: 0, haber: 0 }
  ]
  
  notyf.success(`Modo ${newTipo}: Se añadió fila de Bancos`)
})

const saveManualPoliza = async () => {
  if (manualDiferencia.value > 0.01) {
    notyf.error('La póliza no está cuadrada')
    return
  }
  if (!manualForm.concepto) {
    notyf.error('El concepto es obligatorio')
    return
  }

  savingManual.value = true
  try {
    // Usar FormData para soportar envío de archivos
    const formData = new FormData()
    formData.append('fecha', manualForm.fecha)
    formData.append('tipo', manualForm.tipo)
    formData.append('concepto', manualForm.concepto)
    formData.append('asientos', JSON.stringify(manualForm.asientos))
    
    manualForm.files.forEach((file, index) => {
      formData.append(`files[${index}]`, file)
    })

    await axios.post(route('contabilidad.store'), formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    notyf.success('Póliza creada correctamente')
    showManualModal.value = false
    router.reload()
    
    // Reset form
    manualForm.asientos = [{ cuenta_id: null, debe: 0, haber: 0 }, { cuenta_id: null, debe: 0, haber: 0 }]
    manualForm.concepto = ''
    manualForm.files = []
  } catch (err) {
    notyf.error(err.response?.data?.message || 'Error al crear la póliza')
  } finally {
    savingManual.value = false
  }
}

const descuadradasCount = computed(() => {
  return (props.polizas.data || []).filter(r => r.descuadrada).length
})

const formatTipo = (v, row) => {
  if (!v) return ''
  const isPue = v.toLowerCase() === 'ingreso' || v.toLowerCase() === 'egreso'
  const color = isPue ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'text-brand-600 bg-brand-50 dark:bg-brand-900/20'
  return `<span class="inline-block px-2 py-0.5 rounded-xl text-[10px] font-bold uppercase tracking-wide ${color}">${v.toUpperCase()}</span>`
}

const formatCurrency = (n) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n)

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  // dateStr can be YYYY-MM-DD or ISO string
  const parts = dateStr.substring(0, 10).split('-')
  if (parts.length !== 3) return dateStr
  return `${parts[2]}/${parts[1]}/${parts[0]}`
}

const statusClass = (status) => {
  const classes = {
    borrador: 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400',
    asentada: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    anulada: 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400'
  }
  return classes[status] || classes.borrador
}

const abrirXml = (uuid) => {
  if (uuid) window.open('/cfdi/' + uuid + '/ver-pdf', '_blank')
}

const downloadXml = (poliza) => {
  if (!poliza.xml_content) return
  try {
    const blob = new Blob([poliza.xml_content], { type: 'text/xml' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `poliza_${poliza.numero}.xml`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Error downloading XML:', err)
  }
}
const n = (val) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0)

// --- Sincronización AI Histórica Masiva ---
const abrirSyncAnualModal = async () => {
  const { value: anioElegido } = await Swal.fire({
    title: '🤖 Sincronización Contable AI',
    html: `
      <p class="text-sm text-slate-300 mb-4 leading-relaxed">
        El motor de IA analizará de forma autónoma todos los XML del SAT pendientes del año seleccionado, generará las pólizas en lotes y creará subcuentas si el rubro lo amerita.
      </p>
      <div class="space-y-3 text-left">
        <label class="block text-xs uppercase tracking-widest font-black text-slate-400">Selecciona el año a procesar</label>
        <select id="swal-sync-anio" class="w-full px-4 py-3 bg-slate-900 border border-purple-500/30 rounded-2xl text-sm font-bold text-purple-300 outline-none focus:ring-2 focus:ring-purple-500 cursor-pointer">
          <option value="2026">Año 2026</option>
          <option value="2025">Año 2025</option>
          <option value="2024">Año 2024</option>
          <option value="todo">Todos los Años Históricos</option>
        </select>
      </div>`,
    confirmButtonText: 'Iniciar Sincronización AI',
    showCancelButton: true,
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#9333ea',
    background: '#0f172a',
    customClass: {
      popup: 'rounded-[3rem] border border-purple-500/20 shadow-2xl overflow-hidden',
      confirmButton: 'rounded-2xl px-8 py-3.5 text-xs font-black uppercase tracking-[0.2em] transition-all hover:scale-105 shadow-[0_0_20px_rgba(147,51,234,0.4)]',
      cancelButton: 'rounded-2xl px-8 py-3.5 text-xs font-black uppercase text-slate-400'
    },
    preConfirm: () => document.getElementById('swal-sync-anio').value
  })

  if (!anioElegido) return
  if (window.iniciarAiSyncGlobal) {
      window.iniciarAiSyncGlobal(anioElegido)
  }
}
</script>

<template>
  <div>
    <Head title="Contabilidad - Pólizas" />

    <div class="py-6 px-4 sm:px-6">
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Contabilidad</h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestión de pólizas y automatización de XML</p>
        </div>

        
        <div class="flex flex-wrap items-center gap-3">
          <button @click="abrirAuditoriaBancosModal"
            class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-black tracking-wide rounded-2xl transition-all shadow-lg shadow-emerald-500/25">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Auditoría Mayor vs Bancos
          </button>

          <button @click="abrirSyncAnualModal"
            class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-sm font-black tracking-wide rounded-2xl transition-all shadow-lg shadow-purple-500/25">
            <svg class="w-4 h-4 mr-1.5 animate-pulse text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Sincronización AI Histórica
          </button>

          <Link :href="route('contabilidad.catalog')" 
            class="inline-flex items-center px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-2xl transition-all shadow-sm border border-slate-200 dark:border-slate-600">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            Catálogo
          </Link>

          <button @click="showManualModal = true"
            class="inline-flex items-center px-4 py-2.5 bg-sky-500 hover:bg-sky-600 text-white text-sm font-bold rounded-2xl transition-all shadow-lg shadow-sky-500/20">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Póliza
          </button>

          <button @click="abrirCfdiModal"
            class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-slate-800 hover:bg-brand-50 dark:hover:bg-brand-900/20 text-brand-600 dark:text-brand-400 text-sm font-bold rounded-2xl transition-all shadow-sm border border-brand-200 dark:border-brand-900/50">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            CFDI
          </button>

          <button @click="triggerUpload" :disabled="loading"
            class="inline-flex items-center px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-sm font-black uppercase tracking-wide rounded-2xl transition-all shadow-lg shadow-brand-500/20 disabled:opacity-50">
            <svg v-if="!loading" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white mr-1.5"></div>
            Cargar XML
          </button>
          <input type="file" ref="fileInput" @change="handleFileUpload" hidden accept=".xml" />
        </div>
      </div>

      <!-- Barra de Filtros -->
      <div class="mb-8 p-4 bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-1 p-1 bg-slate-100 dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700">
            <button @click="setRange('day')" class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="fechaInicio && fechaFin && fechaInicio === fechaFin ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">Hoy</button>
            <button @click="setRange('week')" class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="fechaInicio && !fechaFin.includes('T') && 'week-check' ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">Semana</button>
            <button @click="setRange('month')" class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="fechaInicio && fechaInicio.endsWith('01') ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">Mes</button>
            <button @click="setRange('year')" class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all"
              :class="fechaInicio && fechaInicio.endsWith('01-01') ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">Año</button>
          </div>

          <div class="relative group">
            <select v-model="mesRapido" @change="aplicarMesRapido"
              class="px-4 py-2 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all outline-none cursor-pointer">
              <option value="">Seleccionar Mes...</option>
              <option v-for="mes in mesesDisponibles" :key="mes.value" :value="mes.value">
                📅 {{ mes.label }}
              </option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <input v-model="fechaInicio" @change="applyFilters" type="date"
              class="px-4 py-2 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500" title="Desde" />
            <span class="text-slate-400 font-bold">→</span>
            <input v-model="fechaFin" @change="applyFilters" type="date"
              class="px-4 py-2 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500" title="Hasta" />
            <button v-if="fechaInicio || fechaFin" @click="clearRange" class="w-8 h-8 flex items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <select v-model="tipoFilter" @change="applyFilters"
            class="px-4 py-2.5 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all outline-none">
            <option value="">Todos los tipos</option>
            <option value="diario">DIARIO</option>
            <option value="egreso">EGRESO</option>
            <option value="ingreso">INGRESO</option>
          </select>

          <div class="relative group">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-brand-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input v-model="searchTerm" @keyup.enter="applyFilters" type="text" placeholder="Buscar póliza..."
              class="w-full sm:w-64 px-4 py-2.5 pl-10 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all outline-none" />
          </div>
        </div>
      </div>

      <div class="mt-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
          <button @click="tipoFilter = ''; applyFilters()"
            class="text-left bg-white dark:bg-slate-800 rounded-2xl border p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300"
            :class="!tipoFilter ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-700'">
            <span class="block text-[10px] font-bold uppercase tracking-wide text-slate-400 dark:text-slate-500">Total</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.total }}</span>
          </button>
          <div class="text-left bg-white dark:bg-slate-800 rounded-2xl border p-4 shadow-sm"
            :class="descuadradasCount > 0 ? 'border-rose-300 dark:border-rose-700 ring-2 ring-rose-500/20' : 'border-slate-200 dark:border-slate-700'">
            <span class="block text-[10px] font-bold uppercase tracking-wide text-rose-500">Descuadradas</span>
            <span class="text-2xl font-black" :class="descuadradasCount > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white'">{{ descuadradasCount }}</span>
          </div>
          <button @click="tipoFilter = 'diario'; applyFilters()"
            class="text-left bg-white dark:bg-slate-800 rounded-2xl border p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300"
            :class="tipoFilter === 'diario' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-700'">
            <span class="block text-[10px] font-bold uppercase tracking-wide text-brand-600 dark:text-amber-400">DIARIO</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.diario }}</span>
          </button>
          <button @click="tipoFilter = 'ingreso'; applyFilters()"
            class="text-left bg-white dark:bg-slate-800 rounded-2xl border p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300"
            :class="tipoFilter === 'ingreso' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-700'">
            <span class="block text-[10px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">INGRESO</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.ingreso }}</span>
          </button>
          <button @click="tipoFilter = 'egreso'; applyFilters()"
            class="text-left bg-white dark:bg-slate-800 rounded-2xl border p-4 shadow-sm transition-all hover:shadow-md hover:border-amber-300"
            :class="tipoFilter === 'egreso' ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-700'">
            <span class="block text-[10px] font-bold uppercase tracking-wide text-rose-600 dark:text-rose-400">EGRESO</span>
            <span class="text-2xl font-black text-slate-900 dark:text-white">{{ stats.egreso }}</span>
          </button>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                  <th @click="toggleSort('fecha')" class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Fecha{{ sortIndicator('fecha') }}</th>
                  <th @click="toggleSort('tipo')" class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Tipo{{ sortIndicator('tipo') }}</th>
                  <th @click="toggleSort('numero')" class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Número{{ sortIndicator('numero') }}</th>
                  <th @click="toggleSort('concepto')" class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Concepto{{ sortIndicator('concepto') }}</th>
                  <th @click="toggleSort('total')" class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Total{{ sortIndicator('total') }}</th>
                  <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-for="row in (props.polizas.data || [])" :key="row.id" 
                  @click="verDetalles(row)"
                  class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-150 cursor-pointer">
                  <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">{{ formatDate(row.fecha) }}</td>
                  <td class="px-5 py-4 whitespace-nowrap" v-html="formatTipo(row.tipo, row)"></td>
                  <td class="px-5 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">{{ row.numero }}</td>
                   <td class="px-5 py-4 text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate">{{ row.concepto }}</td>
                    <td class="px-5 py-4 whitespace-nowrap text-sm text-right font-mono" :class="row.descuadrada ? 'text-rose-600 bg-rose-50 dark:bg-rose-900/20 font-black' : 'text-slate-900 dark:text-slate-100'">
                      <span v-if="row.descuadrada" class="mr-1" title="Póliza descuadrada">⚠️</span>
                      ${{ new Intl.NumberFormat('es-MX').format(row.total) }}
                    </td>
                   <td class="px-5 py-4 whitespace-nowrap text-right">
                     <div class="flex justify-end gap-2">
                       <button @click.stop="verDetalles(row)" class="w-9 h-9 flex items-center justify-center rounded-xl transition-all bg-sky-50 dark:bg-sky-900/20 text-sky-600 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/30" title="Ver Asientos">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                       </button>
                       <button @click.stop="editPoliza(row)" class="w-9 h-9 flex items-center justify-center rounded-xl transition-all bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-900/30" title="Editar">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                       </button>
                       <button @click.stop="deletePoliza(row)" class="w-9 h-9 flex items-center justify-center rounded-xl transition-all bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30" title="Eliminar">
                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                       </button>
                     </div>
                   </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="props.polizas.last_page > 1" class="px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
            <div class="flex justify-between items-center">
              <div class="text-sm text-slate-500">
                Mostrando {{ props.polizas.from || 0 }} - {{ props.polizas.to || 0 }} de {{ props.polizas.total || 0 }} pólizas
              </div>
              <div class="flex gap-1.5">
                  <Link v-for="(link, i) in props.polizas.links" :key="i"
                      :href="link.url || '#'"
                      v-html="link.label"
                      class="px-3 py-1.5 text-sm rounded-xl transition-all"
                      :class="link.active 
                          ? 'bg-brand-500 text-white shadow-sm' 
                          : link.url ? 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' : 'text-slate-300 cursor-default'"
                  />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Detalles -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-100 dark:border-slate-700">
          <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
            <div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Detalle de Póliza #{{ selectedPoliza.numero }}
              </h3>
              <p class="text-sm text-slate-500">{{ selectedPoliza.concepto }}</p>
            </div>
            <div class="flex items-center gap-2">

                <button @click="showModal = false" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                    <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
          </div>
          
          <div class="flex-1 overflow-y-auto p-6">
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="w-12 h-12 border-4 border-brand-500/20 border-t-brand-500 rounded-full animate-spin mb-4"></div>
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">Cargando detalles...</p>
            </div>
            <template v-else>
              <div v-if="selectedPoliza && selectedPoliza.id" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700">
                  <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Fecha</span>
                  <span class="text-lg font-bold text-slate-700 dark:text-slate-200">{{ formatDate(selectedPoliza.fecha) }}</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700">
                  <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Tipo</span>
                  <span class="text-lg font-bold text-slate-700 dark:text-slate-200">{{ selectedPoliza.tipo?.toUpperCase() || '-' }}</span>
                  <span v-if="selectedPoliza.concepto && selectedPoliza.concepto.includes('[PPD]')" class="ml-2 px-2 py-0.5 rounded-xl text-[10px] font-bold bg-brand-50 dark:bg-brand-900/20 text-amber-600">PPD</span>
                  <span v-else-if="selectedPoliza.concepto && selectedPoliza.concepto.includes('[PUE]')" class="ml-2 px-2 py-0.5 rounded-xl text-[10px] font-bold bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600">PUE</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-700">
                  <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total</span>
                  <span class="text-lg font-bold text-brand-500">{{ formatCurrency(selectedPoliza.total) }}</span>
              </div>
            </div>

            <div v-if="selectedPoliza.descuadrada" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 border-2 border-rose-300 dark:border-rose-700 rounded-2xl flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400 text-lg font-black shrink-0">⚠️</div>
              <div>
                <p class="text-sm font-bold text-rose-700 dark:text-rose-300">Póliza descuadrada</p>
                <p class="text-xs text-rose-600 dark:text-rose-400 mt-0.5">
                  La suma del Debe no coincide con la del Haber.
                  Diferencia: <strong class="font-mono">{{ formatCurrency(selectedPoliza.diferencia) }}</strong>
                </p>
              </div>
            </div>
            <div v-if="selectedPoliza.cfdi_uuid || selectedDocumentos.length" class="mb-8">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3 px-1">Documentos Vinculados</span>
                
                <div class="grid grid-cols-1 gap-3">
                  <!-- Si tenemos documentos con detalle completo -->
                  <template v-if="selectedDocumentos.length > 0">
                    <div v-for="doc in selectedDocumentos" :key="doc.uuid" 
                      class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl flex items-center justify-between group">
                      <div class="overflow-hidden">
                        <div class="flex items-center gap-2 mb-1">
                          <span class="text-[10px] font-black px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-800 text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">{{ doc.relacion }}</span>
                          <span class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ doc.emisor || doc.receptor }}</span>
                        </div>
                        <p class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 truncate">{{ doc.uuid }}</p>
                      </div>
                      <div class="text-right shrink-0 ml-4">
                        <p class="text-xs font-black text-slate-900 dark:text-white font-mono">${{ doc.total.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                        <button @click="abrirXml(doc.uuid)" class="text-[9px] font-bold text-emerald-600 hover:underline">Ver XML</button>
                      </div>
                    </div>
                  </template>
                  
                  <!-- Fallback: Solo UUIDs -->
                  <template v-else-if="selectedPoliza.multi_uuids && selectedPoliza.multi_uuids.length > 1">
                    <div v-for="(u, i) in selectedPoliza.multi_uuids" :key="i" 
                      class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl flex items-center justify-between">
                      <span class="text-[11px] font-mono text-emerald-700 dark:text-emerald-300 truncate">{{ u }}</span>
                      <button @click="abrirXml(u)" class="text-[10px] font-bold text-emerald-600 ml-2">Ver XML</button>
                    </div>
                  </template>
                  
                  <!-- Fallback: UUID único -->
                  <div v-else-if="selectedPoliza.cfdi_uuid" 
                    class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800 rounded-2xl flex items-center justify-between">
                    <span class="text-[11px] font-mono text-emerald-700 dark:text-emerald-300 truncate">{{ selectedPoliza.cfdi_uuid }}</span>
                    <button @click="abrirXml(selectedPoliza.cfdi_uuid)" class="text-[10px] font-bold text-emerald-600 ml-2">Ver XML</button>
                  </div>
                </div>
            </div>

            <div v-if="selectedPoliza.conceptos && selectedPoliza.conceptos.length" class="mb-8">
                <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Conceptos del CFDI</h4>
                <div class="overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Cant</th>
                                <th class="px-4 py-3 font-semibold">Descripción</th>
                                <th class="px-4 py-3 font-semibold text-right">P/U</th>
                                <th class="px-4 py-3 font-semibold text-right">Importe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="(c, i) in selectedPoliza.conceptos" :key="i" class="text-slate-700 dark:text-slate-300">
                                <td class="px-4 py-3 font-mono">{{ Number(c.cantidad || c.Cantidad || 1) }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                  <span>{{ c.descripcion || c.Descripcion || '-' }}</span>
                                  <span v-if="c.empleado" class="block text-[10px] font-normal text-slate-400 dark:text-slate-500 truncate">{{ c.empleado }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">${{ Number(c.valor_unitario || c.ValorUnitario || 0).toFixed(2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900 dark:text-white font-mono">${{ Number(c.importe || c.Importe || 0).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Asientos Contables</h4>
            <div class="overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
              <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                  <tr>
                    <th class="px-4 py-3 font-semibold">Cuenta</th>
                    <th class="px-4 py-3 font-semibold text-right">Debe</th>
                    <th class="px-4 py-3 font-semibold text-right">Haber</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr v-for="a in selectedPoliza.asientos" :key="a.id" class="text-slate-700 dark:text-slate-300">
                    <td class="px-4 py-3">
                      <div class="font-bold text-slate-900 dark:text-white">{{ a.cuenta?.nombre }}</div>
                      <div class="text-[10px] font-mono text-brand-600 dark:text-brand-400 opacity-80">{{ a.cuenta?.codigo }}</div>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold" :class="{'text-emerald-500': a.debe > 0}">{{ a.debe > 0 ? formatCurrency(a.debe) : '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold" :class="{'text-rose-500': a.haber > 0}">{{ a.haber > 0 ? formatCurrency(a.haber) : '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Sección de Soportes -->
            <div class="mt-8 border-t border-slate-100 dark:border-slate-700 pt-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Documentos de Soporte</h4>
                <button @click="triggerSoportesUpload" :disabled="uploadingSoportes"
                  class="inline-flex items-center px-3 py-1.5 bg-brand-500 hover:bg-brand-600 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm disabled:opacity-50">
                  <svg v-if="!uploadingSoportes" class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                  </svg>
                  <div v-else class="animate-spin rounded-full h-3 h-3 border-2 border-white/30 border-t-white mr-1"></div>
                  Adjuntar Archivo
                </button>
                <input type="file" ref="soportesInput" @change="handleSoportesUpload" hidden multiple accept=".pdf,.jpg,.jpeg,.png,.xml" />
              </div>

              <div v-if="selectedPoliza && selectedPoliza.soportes && selectedPoliza.soportes.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div v-for="(soporte, idx) in selectedPoliza.soportes" :key="idx" 
                  class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-700 group">
                  <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-8 h-8 rounded-xl bg-brand-100 dark:bg-brand-900/30 flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="overflow-hidden">
                      <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">{{ soporte.name }}</p>
                      <p class="text-[9px] text-slate-400">{{ soporte.date }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a :href="soporte.url" target="_blank" class="p-1.5 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-slate-500 transition-all">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </a>
                    <button @click="deleteSoporte(idx)" class="p-1.5 hover:bg-rose-100 dark:hover:bg-rose-900/30 rounded-lg text-rose-500 transition-all">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </div>
              </div>
              <div v-else class="p-6 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl text-center">
                <p class="text-xs text-slate-400 font-medium">No hay documentos adjuntos. Sube el acuse o comprobante de pago.</p>
              </div>
            </div>
            </template>
          </div>

          <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
              <button @click="showModal = false" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl hover:bg-slate-300 dark:hover:bg-slate-600 transition-all">
                  Cerrar
              </button>
          </div>
        </div>
      </div>
      <!-- Modal Editar -->
      <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-md border border-slate-100 dark:border-slate-700">
          <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center">
              <h3 class="text-xl font-bold text-slate-900 dark:text-white">Editar Póliza</h3>
              <button @click="showEditModal = false" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">
                  <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
              </button>
          </div>
          <div class="p-6 space-y-4">
              <div>
                  <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Concepto</label>
                  <input v-model="editForm.concepto" type="text" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all" />
              </div>
              <div>
                  <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Fecha</label>
                  <input v-model="editForm.fecha" type="date" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all" />
              </div>
              <div>
                  <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1">Estado</label>
                  <select v-model="editForm.estado" class="w-full px-4 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                      <option value="borrador">Borrador</option>
                      <option value="asentada">Asentada</option>
                      <option value="anulada">Anulada</option>
                  </select>
              </div>
          </div>
          <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 rounded-b-3xl flex justify-end gap-3">
              <button @click="showEditModal = false" class="px-4 py-2 text-slate-600 dark:text-slate-400 font-semibold hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">Cancelar</button>
              <button @click="updatePoliza" class="px-6 py-2 bg-brand-500 text-white font-bold rounded-xl hover:bg-brand-600 transition-all shadow-sm">Guardar Cambios</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Previsualización -->
    <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
      <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-100 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-brand-500/10">
          <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center">
              <svg class="w-5 h-5 mr-2 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Confirmar Generación de Póliza
            </h3>
            <p class="text-sm text-slate-500">Revisa los asientos contables antes de guardar</p>
          </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="previewData" class="bg-slate-50 dark:bg-slate-900/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-700 mb-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Fecha</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ previewData.fecha }}</span>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Tipo</span>
                <span class="text-sm font-bold" :class="selectedBancoId ? 'text-rose-500' : 'text-slate-700 dark:text-slate-200'">
                    {{ selectedBancoId ? 'EGRESO' : previewData.tipo?.toUpperCase() || '-' }}
                </span>
              </div>
              <div class="col-span-2">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Concepto</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ previewData.concepto }}</span>
              </div>
              <div v-if="previewData.cfdi_uuid" class="col-span-2 md:col-span-4">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">UUID del XML</span>
                <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded">{{ previewData.cfdi_uuid }}</span>
              </div>
              <div v-if="previewData.descripcion_pago" class="col-span-2 md:col-span-4">
                <span class="block text-[10px] font-bold text-blue-400 uppercase">Documentos que se marcarán como PAGADOS</span>
                <div class="mt-1 text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 px-3 py-2 rounded-xl border border-blue-100 dark:border-blue-800 flex items-center shadow-sm">
                  <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  {{ previewData.descripcion_pago }}
                </div>
              </div>
            </div>
          </div>

          <!-- Selección de Banco/Pago -->
          <div v-if="previewData" class="mb-6 p-4 bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-700 rounded-2xl">
            <label class="block text-xs font-bold text-brand-700 dark:text-brand-400 uppercase mb-2">¿Fue pagada? Selecciona la cuenta bancaria / tarjeta:</label>
            <select v-model="selectedBancoId" class="w-full px-4 py-2.5 rounded-xl border border-brand-300 dark:border-brand-600 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-brand-500/20">
                <option value="">No marcar como pagada aún (Queda en Provisión)</option>
                <option v-for="b in cuentasBancarias" :key="b.id" :value="b.id">
                    {{ b.nombre }} - {{ b.banco }} ({{ b.numero_cuenta }})
                </option>
            </select>
            <p v-if="selectedBancoId" class="mt-2 text-[11px] text-brand-600 dark:text-brand-400 italic">
                * Al seleccionar un banco, la póliza se generará como <strong>EGRESO</strong> y el abono irá directamente a la cuenta bancaria.
            </p>
          </div>

          <h4 v-if="previewData" class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4">Asientos Propuestos</h4>
          <div v-if="previewData" class="overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
            <table class="w-full text-sm text-left">
              <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                <tr>
                  <th class="px-4 py-3 font-semibold">Cuenta</th>
                  <th class="px-4 py-3 font-semibold text-right">Debe</th>
                  <th class="px-4 py-3 font-semibold text-right">Haber</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                <tr v-for="(a, idx) in previewData.asientos" :key="idx" class="text-slate-700 dark:text-slate-300">
                  <td class="px-4 py-3">
                    <div class="font-bold text-slate-900 dark:text-white">{{ a.cuenta_nombre }}</div>
                    <div class="text-[10px] font-mono text-brand-600 dark:text-amber-400">{{ a.cuenta_codigo }}</div>
                    <div v-if="a.auxiliar" class="text-[10px] text-slate-400 italic">Aux: {{ a.auxiliar }}</div>
                  </td>
                  <td class="px-4 py-3 text-right font-semibold" :class="{'text-emerald-500': a.debe > 0}">{{ a.debe > 0 ? formatCurrency(a.debe) : '-' }}</td>
                  <td class="px-4 py-3 text-right font-semibold" :class="{'text-rose-500': a.haber > 0}">{{ a.haber > 0 ? formatCurrency(a.haber) : '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end gap-3">
            <button @click="showPreviewModal = false" :disabled="loading" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl hover:bg-slate-300 transition-all">
                Cancelar
            </button>
            <button @click="confirmarPoliza" :disabled="loading" class="px-8 py-2 bg-emerald-500 text-white font-bold rounded-xl hover:bg-emerald-600 transition-all shadow-lg flex items-center">
                <svg v-if="!loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div v-else class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white mr-2"></div>
                Confirmar y Generar
            </button>
        </div>
      </div>
    </div>

    <!-- Modal CFDI Pendientes -->
    <div v-if="showCfdiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCfdiModal = false"></div>
      <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-100 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">CFDI</h3>
            <button @click="showCfdiModal = false" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">
              <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <div class="flex gap-2 flex-wrap">
            <button @click="cfdiFiltro = 'todos'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'todos' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Todos</button>
            <button @click="cfdiFiltro = 'pendiente'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'pendiente' ? 'bg-brand-500 text-white border-brand-500' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Pendientes</button>
            <button @click="cfdiFiltro = 'integrado'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'integrado' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Integrados</button>
            <button @click="cfdiFiltro = 'emitido'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'emitido' ? 'bg-brand-600 text-white border-amber-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Emitidos</button>
            <button @click="cfdiFiltro = 'recibido'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'recibido' ? 'bg-violet-600 text-white border-violet-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Recibidos</button>
            <button @click="cfdiFiltro = 'I'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'I' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Ingresos</button>
            <button @click="cfdiFiltro = 'E'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'E' ? 'bg-rose-600 text-white border-rose-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Egresos</button>
            <button @click="cfdiFiltro = 'P'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'P' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Pagos</button>
            <button @click="cfdiFiltro = 'N'" class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wide rounded-xl border transition-all"
              :class="cfdiFiltro === 'N' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100'">Nóminas</button>
          </div>
          <div class="flex gap-2 mt-3 items-center">
            <div class="relative flex-1 max-w-sm">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              <input v-model="cfdiSearch" type="text" placeholder="Buscar por folio, nombre o UUID..."
                class="w-full pl-9 pr-4 py-1.5 text-[10px] font-bold border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 outline-none">
            </div>
            <select v-model="cfdiMes" class="px-3 py-1.5 text-[10px] font-bold border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200">
              <option value="">Todos los meses</option>
              <option v-for="(nom, idx) in ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']" :key="idx" :value="String(idx+1).padStart(2,'0')">{{ nom }}</option>
            </select>
            <select v-model="cfdiAnio" class="px-3 py-1.5 text-[10px] font-bold border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200">
              <option value="">Todos los años</option>
              <option v-for="a in aniosDisponibles" :key="a" :value="a">{{ a }}</option>
            </select>
            <select v-model="cfdiMetodoPago" class="px-3 py-1.5 text-[10px] font-bold border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200">
              <option value="">PUE/PPD</option>
              <option value="PUE">PUE (Pagado)</option>
              <option value="PPD">PPD (Pendiente)</option>
            </select>
          </div>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
          <div v-if="loadingCfdis" class="flex items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-4 border-brand-500/30 border-t-brand-500"></div>
          </div>
          <div v-else-if="!cfdisFiltrados.length" class="text-center py-16 text-slate-400">
            <p class="text-lg font-semibold">No hay CFDI</p>
            <p class="text-sm mt-1">Ningún CFDI coincide con el filtro seleccionado</p>
          </div>

          <div class="space-y-2">
            <div v-for="cfdi in cfdisFiltrados" :key="cfdi.id"
              class="flex items-center justify-between p-4 rounded-2xl border transition-all cursor-pointer"
              :class="cfdi.integrada ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800' : selectedCfdis.includes(cfdi.uuid) ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-300 dark:border-amber-700' : 'bg-white dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 hover:border-amber-300'"
              @click="!cfdi.integrada && toggleSelectCfdi(cfdi.uuid)">
              <div v-if="!cfdi.integrada" class="mr-3 flex items-center" @click.stop>
                <input type="checkbox" :checked="selectedCfdis.includes(cfdi.uuid)" @change="toggleSelectCfdi(cfdi.uuid)" class="w-4 h-4 rounded border-slate-300 text-brand-500 focus:ring-brand-500" />
              </div>
              <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2 text-sm items-center">
                <div class="md:col-span-2">
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-lg"
                      :class="cfdi.direccion === 'emitido' ? 'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-amber-400' : 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'">{{ cfdi.direccion }}</span>
                    <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-lg border shadow-sm"
                      :class="cfdi.tipo === 'P' ? 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800' : cfdi.metodo_pago === 'PPD' ? 'bg-orange-50 text-brand-600 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800' : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800'">
                      {{ cfdi.tipo === 'P' ? 'REP' : (cfdi.metodo_pago || 'PUE') }}
                    </span>
                    <span v-if="cfdi.estado_sistema" class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-lg border shadow-sm"
                      :class="cfdi.estado_sistema === 'pagado' ? 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-800' : 'bg-brand-50 text-brand-600 border-brand-100 dark:bg-brand-900/20 dark:border-amber-800'">
                      {{ cfdi.estado_sistema === 'pagado' ? 'Liquidado' : 'Pendiente' }}
                    </span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ cfdi.emisor || cfdi.receptor }}</span>
                  </div>
                  <span class="text-[11px] font-mono text-slate-400 block truncate">
                    <span v-if="cfdi.serie || cfdi.folio" class="text-slate-600 dark:text-slate-400 font-bold mr-1">{{ cfdi.serie }}{{ cfdi.folio }}</span>
                    <span v-if="cfdi.serie || cfdi.folio" class="mr-1">-</span>
                    {{ cfdi.uuid }}
                  </span>
                  <div v-if="cfdi.descripcion_pago" class="mt-1 flex">
                    <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-800 shadow-sm flex items-center">
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      {{ cfdi.descripcion_pago }}
                    </span>
                  </div>
                </div>
                <div class="text-slate-500 text-xs">
                  {{ cfdi.fecha }}
                  <span v-if="cfdi.periodo" class="block text-[10px] text-brand-600 font-semibold mt-0.5">{{ cfdi.periodo }}</span>
                </div>
                <div class="font-semibold text-slate-800 dark:text-slate-200">${{ Number(cfdi.total).toFixed(2) }}</div>
                <div class="text-right flex items-center justify-end gap-2">
                   <button @click.stop="verPdf(cfdi.uuid)" class="p-2 text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-900/20 rounded-xl transition-all" title="Ver PDF">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                   </button>
                   <button @click.stop="verXml(cfdi.uuid)" class="p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-all" title="Ver XML">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                   </button>
                  <div v-if="cfdi.integrada" class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 text-emerald-600 text-xs font-semibold px-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                      Integrado (#{{ cfdi.poliza_numero }})
                    </span>
                    <button @click.stop="desasociarCfdi(cfdi)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all" title="Desasociar (Eliminar Póliza)">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                  <button v-else @click.stop="integrarCfdiModal(cfdi)" :disabled="integrandoCfdi === cfdi.uuid"
                    class="px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white text-xs font-bold rounded-xl transition-all disabled:opacity-50">
                    {{ integrandoCfdi === cfdi.uuid ? '...' : 'Integrar' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
          <div>
            <div v-if="selectedCfdis.length >= 2" class="flex items-center gap-4">
              <span class="text-sm font-bold text-amber-600">{{ selectedCfdis.length }} seleccionados</span>
              <button @click="selectedCfdis = []" class="text-xs font-bold text-slate-400 hover:text-rose-500 transition-all uppercase tracking-wider">Limpiar Selección</button>
            </div>
          </div>
          <div class="flex gap-3">
            <button v-if="selectedCfdis.length >= 2" @click="integrarMultiCfdi" :disabled="integrandoMulti"
              class="px-6 py-2 bg-brand-500 hover:bg-brand-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-brand-500/20 flex items-center">
              <div v-if="integrandoMulti" class="animate-spin rounded-full h-3 w-3 border-2 border-white/30 border-t-white mr-2"></div>
              Integrar Agrupados
            </button>
            <button @click="showCfdiModal = false" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl hover:bg-slate-300 transition-all">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal Póliza Manual -->
    <div v-if="showManualModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showManualModal = false"></div>
      <div class="relative bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col border border-slate-100 dark:border-slate-700">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
          <div class="flex items-center gap-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <div class="w-8 h-8 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
              </div>
              Nueva Póliza Manual
            </h3>
            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-2"></div>
            <button @click="loadTemplate('didi')" class="px-3 py-1 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase rounded-lg border border-rose-100 dark:border-rose-800 hover:bg-rose-100 transition-all flex items-center gap-1.5 shadow-sm">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1.319C11.188 17.681 10 16.634 10 15.5m0-11c0 1.134 1.188 2.181 2.599 2.819" /></svg>
              Plantilla DiDi
            </button>
          </div>
          <button @click="showManualModal = false" class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition-all">
            <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Fecha</label>
              <input type="date" v-model="manualForm.fecha" 
                @keydown.enter.prevent="document.getElementById('manual-tipo').focus()"
                class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 focus:ring-sky-500/20 focus:border-sky-500 transition-all font-semibold" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tipo</label>
              <select id="manual-tipo" v-model="manualForm.tipo"
                @keydown.enter.prevent="document.getElementById('manual-concepto').focus()"
                class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 focus:ring-sky-500/20 focus:border-sky-500 transition-all font-bold">
                <option value="egreso">Egreso (Pago)</option>
                <option value="ingreso">Ingreso (Cobro)</option>
                <option value="diario">Diario</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Concepto General</label>
              <input id="manual-concepto" type="text" v-model="manualForm.concepto" placeholder="Ej: Pago de IVA Marzo 2026"
                @keydown.enter.prevent="document.getElementById('search-input-0')?.focus()"
                class="w-full px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 focus:ring-sky-500/20 focus:border-sky-500 transition-all font-semibold" />
            </div>
          </div>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Asientos Contables</h4>
              <button @click="addAsiento" class="text-[10px] font-black uppercase tracking-wider text-sky-600 hover:text-sky-700 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                Agregar Fila
              </button>
            </div>

            <div class="border border-slate-100 dark:border-slate-700 rounded-3xl shadow-sm">
              <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500">
                  <tr>
                    <th class="px-4 py-3 font-semibold">Cuenta Contable</th>
                    <th class="px-4 py-3 font-semibold text-right w-32">Debe</th>
                    <th class="px-4 py-3 font-semibold text-right w-32">Haber</th>
                    <th class="px-4 py-3 w-10"></th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr v-for="(a, i) in manualForm.asientos" :key="i">
                    <td class="px-3 py-2 relative" :class="{ 'z-[80]': accountSearch[i].show }">
                      
                      <!-- Vista de cuenta seleccionada (Píldora) -->
                      <div v-if="a.cuenta_id" class="flex items-center justify-between bg-sky-50 dark:bg-sky-900/20 border border-sky-100 dark:border-sky-900/50 rounded-xl px-3 py-2 w-full shadow-sm">
                        <div class="flex flex-col min-w-0">
                          <span class="text-[10px] font-black text-sky-600 dark:text-sky-400 truncate">{{ props.cuentasContables.find(c => c.id === a.cuenta_id)?.codigo }}</span>
                          <span class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">{{ props.cuentasContables.find(c => c.id === a.cuenta_id)?.nombre }}</span>
                        </div>
                        <button @click="clearAccount(i)" type="button" class="ml-2 p-1.5 text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-900/50 hover:text-sky-600 rounded-lg transition-all flex-shrink-0">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>

                      <!-- Input de Búsqueda -->
                      <template v-else>
                        <div class="relative">
                          <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                          </div>
                          <input 
                            type="text"
                            :id="`search-input-${i}`"
                            v-model="accountSearch[i].query"
                            @focus="updateActiveRect(i, $event)"
                            @input="updateActiveRect(i, $event)"
                            @keydown="handleSearchKeyDown(i, $event)"
                            @blur="window.setTimeout(() => accountSearch[i].show = false, 200)"
                            placeholder="Buscar cuenta..."
                            class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 text-xs font-bold text-slate-700 dark:text-slate-200 placeholder-slate-400 transition-all shadow-inner"
                          />
                        </div>
                        
                        <!-- Resultados de búsqueda con Teleport -->
                        <Teleport to="body">
                          <div v-if="accountSearch[i].show && activeIndex === i" 
                            class="fixed bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl shadow-2xl z-[100] max-h-60 overflow-y-auto animate-in fade-in zoom-in-95"
                            :style="{
                              top: (activeInputRect.top + activeInputRect.height + 6) + 'px',
                              left: activeInputRect.left + 'px',
                              width: activeInputRect.width + 'px'
                            }">
                            <div v-for="(c, idx) in filteredCuentas(accountSearch[i].query)" :key="c.id"
                              @mousedown.prevent="selectAccount(i, c)"
                              @mouseenter="dropdownSelectedIndex = idx"
                              class="px-4 py-2 cursor-pointer border-b border-slate-50 dark:border-slate-800 last:border-b-0 group transition-colors"
                              :class="{ 'bg-sky-50 dark:bg-sky-900/40 ring-inset ring-2 ring-sky-500/10': dropdownSelectedIndex === idx, 'hover:bg-sky-50 dark:hover:bg-sky-900/20': dropdownSelectedIndex !== idx }">
                              <div class="text-[11px] font-black transition-colors"
                                :class="dropdownSelectedIndex === idx ? 'text-sky-700 dark:text-sky-300' : 'text-sky-600 dark:text-sky-400 group-hover:text-sky-700 dark:group-hover:text-sky-300'">{{ c.codigo }}</div>
                              <div class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ c.nombre }}</div>
                            </div>
                            <div v-if="filteredCuentas(accountSearch[i].query).length === 0" class="p-4 text-center text-xs font-bold text-slate-400 italic bg-slate-50/50 dark:bg-slate-900/30">
                              No se encontraron cuentas
                            </div>
                          </div>
                        </Teleport>
                      </template>
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" v-model="a.debe" step="0.01" 
                        :id="`debe-input-${i}`"
                        @keydown.enter.prevent="focusNextRow(i)"
                        class="w-full bg-transparent border-0 focus:ring-0 text-right font-mono font-bold text-emerald-600 focus:bg-emerald-50 dark:focus:bg-emerald-900/20 rounded-xl transition-all" />
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" v-model="a.haber" step="0.01" 
                        :id="`haber-input-${i}`"
                        @keydown.enter.prevent="focusNextRow(i)"
                        class="w-full bg-transparent border-0 focus:ring-0 text-right font-mono font-bold text-rose-600 focus:bg-rose-50 dark:focus:bg-rose-900/20 rounded-xl transition-all" />
                    </td>
                    <td class="px-3 py-2 text-center">
                      <button @click="removeAsiento(i)" :disabled="manualForm.asientos.length <= 2"
                        class="p-1.5 text-slate-300 hover:text-rose-500 transition-all disabled:opacity-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-slate-50/50 dark:bg-slate-900/30 font-black">
                  <tr>
                    <td class="px-4 py-3 text-right text-slate-400 uppercase text-[10px]">Totales</td>
                    <td class="px-4 py-3 text-right font-mono text-emerald-600">${{ manualTotalDebe.toFixed(2) }}</td>
                    <td class="px-4 py-3 text-right font-mono text-rose-600">${{ manualTotalHaber.toFixed(2) }}</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Adjuntar archivos en póliza manual -->
            <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-3xl">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-xs font-bold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Soportes del Gasto</h4>
                  <p class="text-[10px] text-slate-400">Adjunta el PDF o Imagen de DiDi/Amazon/etc.</p>
                </div>
                <button @click="manualFilesInput.click()" type="button" class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase hover:bg-slate-50 transition-all shadow-sm">
                  Seleccionar Archivos
                </button>
                <input type="file" ref="manualFilesInput" @change="handleManualFiles" hidden multiple accept="image/*,.pdf" />
              </div>
              <div v-if="manualForm.files.length > 0" class="mt-4 flex flex-wrap gap-2">
                <div v-for="(file, idx) in manualForm.files" :key="idx" class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-bold rounded-lg border border-emerald-100 dark:border-emerald-800 flex items-center gap-2">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-2.828-6.828l-6.414 6.586a6 6 0 008.485 8.486L20.5 13" /></svg>
                  {{ file.name }}
                  <button @click="manualForm.files.splice(idx, 1)" type="button" class="text-rose-500 hover:text-rose-700">×</button>
                </div>
              </div>
            </div>

            <div v-if="manualDiferencia > 0.01" class="p-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl flex items-center justify-between">
              <span class="text-xs font-bold text-rose-600">Póliza Descuadrada</span>
              <span class="text-xs font-mono font-black text-rose-700">Diferencia: ${{ manualDiferencia.toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
          <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Asegúrate que el Debe y Haber coincidan</p>
          <div class="flex gap-3">
            <button @click="showManualModal = false" class="px-6 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded-xl hover:bg-slate-300 transition-all">Cancelar</button>
            <button @click="saveManualPoliza" :disabled="savingManual || manualDiferencia > 0.01"
              class="px-8 py-2 bg-sky-500 hover:bg-sky-600 text-white font-bold rounded-xl transition-all shadow-lg shadow-sky-500/20 disabled:opacity-50">
              <div v-if="savingManual" class="animate-spin rounded-full h-4 w-4 border-2 border-white/30 border-t-white mr-2 inline-block align-middle"></div>
              Guardar Póliza
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Auditoría Mayor vs Bancos -->
    <div v-if="showAuditBancosModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 backdrop-blur-sm bg-slate-950/80">
      <div class="relative bg-slate-900 border border-white/10 rounded-[3rem] w-full max-w-4xl p-8 text-white shadow-2xl overflow-hidden z-10">
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl"></div>

        <div class="flex items-center justify-between pb-6 border-b border-white/10 relative z-10">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-slate-950 flex items-center justify-center font-black text-2xl shadow-lg shadow-emerald-500/25">
              ⚖️
            </div>
            <div>
              <h3 class="text-xl font-black text-white tracking-tight">Auditoría en Tiempo Real: Libro Mayor (102) vs Bancos Reales</h3>
              <p class="text-xs text-slate-400 font-medium mt-0.5">Comparativa de sumatorias contables y cuentas de tesorería</p>
            </div>
          </div>
          <button @click="showAuditBancosModal = false" class="text-slate-400 hover:text-white transition-colors p-2 rounded-xl bg-white/5 hover:bg-white/10">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <div v-if="loadingAuditBancos" class="py-24 text-center space-y-4">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-emerald-500/20 border-t-emerald-500"></div>
          <p class="text-sm font-bold tracking-widest uppercase text-emerald-400">Analizando saldos contables y bancarios...</p>
        </div>

        <div v-else-if="auditBancosData" class="space-y-8 pt-6 relative z-10">
          <!-- Tarjetas Totales -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/5 border border-white/5 rounded-3xl p-6 text-center shadow-lg">
              <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Saldo Libro Mayor (102)</p>
              <p class="text-3xl font-black font-mono text-emerald-400">${{ Number(auditBancosData.total_contable_mayor).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
              <p class="text-[10px] text-slate-500 mt-2">Sumatoria subcuentas contables</p>
            </div>

            <div class="bg-white/5 border border-white/5 rounded-3xl p-6 text-center shadow-lg">
              <p class="text-[10px] uppercase font-black text-slate-400 tracking-widest mb-1">Saldos Cuentas Bancarias</p>
              <p class="text-3xl font-black font-mono text-sky-400">${{ Number(auditBancosData.total_bancos_real).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
              <p class="text-[10px] text-slate-500 mt-2">Sumatoria cuentas reales</p>
            </div>

            <div class="p-6 rounded-3xl text-center shadow-lg border" :class="auditBancosData.descuadrado ? 'bg-rose-500/10 border-rose-500/30' : 'bg-emerald-500/10 border-emerald-500/30'">
              <p class="text-[10px] uppercase font-black tracking-widest mb-1" :class="auditBancosData.descuadrado ? 'text-rose-400' : 'text-emerald-400'">Diferencia de Conciliación</p>
              <p class="text-3xl font-black font-mono" :class="auditBancosData.descuadrado ? 'text-rose-400 animate-pulse font-bold' : 'text-emerald-400 font-bold'">
                ${{ Number(auditBancosData.diferencia).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
              </p>
              <p class="text-[11px] font-bold mt-2" :class="auditBancosData.descuadrado ? 'text-rose-400' : 'text-emerald-400'">
                {{ auditBancosData.descuadrado ? '⚠️ Existe un descuadre' : '✓ Totalmente conciliado' }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Desglose Mayor -->
            <div class="bg-white/5 border border-white/5 rounded-3xl p-6 space-y-4">
              <h4 class="text-xs font-black uppercase tracking-widest text-emerald-400">Desglose Subcuentas Mayor (102)</h4>
              <div class="divide-y divide-white/5 max-h-60 overflow-y-auto pr-2">
                <div v-for="cta in auditBancosData.desglose_mayor" :key="cta.codigo" class="py-3 flex items-center justify-between">
                  <div>
                    <p class="text-xs font-bold text-white">{{ cta.nombre }}</p>
                    <p class="text-[10px] font-mono text-slate-400">{{ cta.codigo }}</p>
                  </div>
                  <span class="text-sm font-mono font-bold text-emerald-300">${{ Number(cta.saldo).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>
            </div>

            <!-- Desglose Bancos Reales -->
            <div class="bg-white/5 border border-white/5 rounded-3xl p-6 space-y-4">
              <h4 class="text-xs font-black uppercase tracking-widest text-sky-400">Cuentas Bancarias Registradas</h4>
              <div class="divide-y divide-white/5 max-h-60 overflow-y-auto pr-2">
                <div v-for="banco in auditBancosData.cuentas_bancarias" :key="banco.numero_cuenta" class="py-3 flex items-center justify-between">
                  <div>
                    <p class="text-xs font-bold text-white">{{ banco.banco }} - {{ banco.numero_cuenta }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">Vínculo: {{ banco.cuenta_contable }}</p>
                  </div>
                  <span class="text-sm font-mono font-bold text-sky-300">${{ Number(banco.saldo).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-4 pt-4 border-t border-white/10">
            <button @click="abrirAuditoriaBancosModal" class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all">
              🔄 Recalcular
            </button>
            <button @click="showAuditBancosModal = false" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-lg shadow-emerald-600/30">
              Cerrar Auditoría
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.animate-in { animation-duration: 0.3s; animation-fill-mode: both; }
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in-95 { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.fade-in { animation-name: fade-in; }
.zoom-in-95 { animation-name: zoom-in-95; }
</style>
