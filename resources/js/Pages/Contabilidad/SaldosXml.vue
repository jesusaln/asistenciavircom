<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const saldosData = ref({ por_cobrar: { items: [], total: 0, count: 0 }, por_pagar: { items: [], total: 0, count: 0 } })
const loadingSaldos = ref(false)
const errorMsg = ref('')
const filtroRfc = ref('')
const csvPagos = ref([]) 
const loadingCsv = ref(false)
const csvFileName = ref('')

// --- FILTROS DE FECHA (MES Y AÑO INICIALIZADOS AL ACTUAL O LOCALSTORAGE) ---
const currentDate = new Date()
const currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0')
const currentYear = String(currentDate.getFullYear())

const filtroMes = ref(localStorage.getItem('saldos_filtro_mes') || currentMonth)
const filtroAnio = ref(localStorage.getItem('saldos_filtro_anio') || currentYear)

// --- IA PDF CONCILIACION BANCARIA ---
const bankModalOpen = ref(false)
const loadingBankPdf = ref(false)
const bankDataResult = ref(null)
const bankPassword = ref('')
const bankFile = ref(null)
const activeTab = ref('all') // all, matched, pending

const handleBankPdfSelect = async (e) => {
  const files = Array.from(e.target.files)
  if (files.length === 0) return
  bankFile.value = files[0]
  procesarPdfBancario()
}

const procesarPdfBancario = async () => {
  if (!bankFile.value) return
  loadingBankPdf.value = true
  bankModalOpen.value = true
  bankDataResult.value = null

  const fd = new FormData()
  fd.append('file', bankFile.value)
  if (bankPassword.value) fd.append('password', bankPassword.value)

  try {
    const res = await axios.post(route('contabilidad.api.conciliar-pdf-banco'), fd)
    if (res.data.success) {
      bankDataResult.value = res.data.banco
      notyf.success(`Estado de cuenta ${bankDataResult.value.banco_nombre} procesado con éxito`)
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al procesar el estado de cuenta en PDF.')
    bankModalOpen.value = false
  } finally {
    loadingBankPdf.value = false
  }
}

const generarPolizaSuelta = async (mov) => {
  mov.is_loading = true
  try {
    const res = await axios.post(route('contabilidad.api.generar-poliza-bancaria'), {
      fecha: mov.fecha,
      concepto: mov.concepto,
      monto: mov.monto,
      tipo: mov.tipo,
      banco_nombre: bankDataResult.value?.banco_nombre || 'Banco',
      cfdi_id: mov.cfdi_id || null
    })
    if (res.data.success) {
      mov.status = 'matched'
      mov.match_reason = `Póliza de ${res.data.poliza.tipo} #${res.data.poliza.numero} generada manualmente.`
      mov.poliza_id = res.data.poliza.id
      notyf.success(res.data.message)
      if (bankDataResult.value.resumen_match) {
        bankDataResult.value.resumen_match.conciliados++
        bankDataResult.value.resumen_match.pendientes--
      }
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al generar la póliza contable.')
  } finally {
    mov.is_loading = false
  }
}

const movimientosBancariosFiltrados = computed(() => {
  if (!bankDataResult.value?.movimientos) return []
  if (activeTab.value === 'matched') return bankDataResult.value.movimientos.filter(m => m.status === 'matched')
  if (activeTab.value === 'pending') return bankDataResult.value.movimientos.filter(m => m.status === 'pending')
  return bankDataResult.value.movimientos
})

const handleCsvUpload = async (e) => {
  const files = Array.from(e.target.files)
  if (files.length === 0) return
  
  csvFileName.value = files.length === 1 ? files[0].name : `${files.length} archivos seleccionados`
  loadingCsv.value = true
  
  const fd = new FormData()
  files.forEach(f => {
    fd.append('csv', f)
  })

  try {
    const res = await axios.post(route('contabilidad.api.conciliar-csv'), fd)
    if (res.data.success) {
      csvPagos.value = res.data.coincidencias || []
      notyf.success(`${csvPagos.value.length} coincidencias de pago detectadas`)
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al procesar archivos')
  } finally {
    loadingCsv.value = false
  }
}

const estaPagadoCsv = (uuid) => csvPagos.value.some(p => p.uuid === uuid)

const fetchSaldosXml = async () => {
  localStorage.setItem('saldos_filtro_mes', filtroMes.value)
  localStorage.setItem('saldos_filtro_anio', filtroAnio.value)
  loadingSaldos.value = true
  errorMsg.value = ''
  try {
    const res = await axios.get(route('contabilidad.api.saldos-xml', {
      mes: filtroMes.value,
      anio: filtroAnio.value
    }))
    if (res.data.success) {
      saldosData.value = res.data.data
    }
  } catch (e) {
    errorMsg.value = 'Error al cargar saldos. Verifica la conexión.'
    notyf.error('Error al cargar saldos')
  } finally {
    loadingSaldos.value = false
  }
}

onMounted(() => { fetchSaldosXml() })

const n = (val) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val || 0)

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const p = dateStr.substring(0, 10).split('-')
  return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : dateStr
}

const diasColor = (dias) => {
  if (dias > 90) return 'text-rose-600'
  if (dias > 30) return 'text-amber-600'
  return 'text-slate-500'
}

const sortFieldCobrar = ref('dias_vencimiento')
const sortDirCobrar = ref('asc')
const sortFieldPagar = ref('dias_vencimiento')
const sortDirPagar = ref('asc')

const toggleSort = (side, field) => {
  const sf = side === 'cobrar' ? sortFieldCobrar : sortFieldPagar
  const sd = side === 'cobrar' ? sortDirCobrar : sortDirPagar
  if (sf.value === field) sd.value = sd.value === 'asc' ? 'desc' : 'asc'
  else { sf.value = field; sd.value = 'asc' }
}

const sortItems = (items, field, dir) => {
  return [...items].sort((a, b) => {
    const va = a[field] ?? 0
    const vb = b[field] ?? 0
    if (typeof va === 'string') return dir === 'asc' ? va.localeCompare(vb) : vb.localeCompare(va)
    return dir === 'asc' ? va - vb : vb - va
  })
}

const filteredItems = (items) => {
  let r = items
  if (filtroRfc.value) {
    const q = filtroRfc.value.toUpperCase()
    r = r.filter(i => (i.rfc || '').toUpperCase().includes(q) || (i.razon_social || '').toUpperCase().includes(q))
  }
  return r
}

const porCobrarFacturas = computed(() => sortItems(filteredItems(saldosData.value.por_cobrar?.facturas || []), sortFieldCobrar.value, sortDirCobrar.value))
const porCobrarNotas = computed(() => sortItems(filteredItems(saldosData.value.por_cobrar?.notas || []), sortFieldCobrar.value, sortDirCobrar.value))

const porPagarFacturas = computed(() => sortItems(filteredItems(saldosData.value.por_pagar?.facturas || []), sortFieldPagar.value, sortDirPagar.value))
const porPagarNotas = computed(() => sortItems(filteredItems(saldosData.value.por_pagar?.notas || []), sortFieldPagar.value, sortDirPagar.value))

const sortIcon = (side, field) => {
  const sf = side === 'cobrar' ? sortFieldCobrar : sortFieldPagar
  const sd = side === 'cobrar' ? sortDirCobrar : sortDirPagar
  if (sf.value !== field) return '↕'
  return sd.value === 'asc' ? '↑' : '↓'
}

// --- MODAL DETALLE DE FACTURA (PÓLIZAS + XML) ---
const showDetailModal = ref(false)
const loadingDetail = ref(false)
const detailCfdi = ref(null)
const detailPolizas = ref([])
const activeDetailTab = ref('polizas') // polizas, xml, conceptos

const abrirDetalleFactura = async (item) => {
  showDetailModal.value = true
  loadingDetail.value = true
  detailCfdi.value = null
  detailPolizas.value = []
  activeDetailTab.value = 'polizas'

  try {
    const res = await axios.get(route('contabilidad.api.saldos-xml.detalle', { uuid: item.uuid }))
    if (res.data.success) {
      detailCfdi.value = res.data.cfdi
      detailPolizas.value = res.data.polizas
    }
  } catch (e) {
    notyf.error('Error al cargar los detalles de la factura.')
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

const sendingEmail = ref(false)
const sendingWhatsApp = ref(false)

const enviarComprobanteCorreo = async () => {
  if (!detailCfdi.value) return
  
  const { value: emailDestino } = await Swal.fire({
    title: 'Enviar Comprobante',
    text: 'Ingrese el correo electrónico de destino:',
    input: 'email',
    inputValue: detailCfdi.value.email_receptor || '',
    showCancelButton: true,
    confirmButtonText: '📧 Enviar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#4f46e5',
    customClass: { popup: 'rounded-3xl' }
  })
  if (!emailDestino) return

  sendingEmail.value = true
  try {
    const res = await axios.post(route('contabilidad.api.saldos-xml.enviar-correo', { uuid: detailCfdi.value.uuid }), { email: emailDestino })
    if (res.data.success) {
      Swal.fire({
        title: '¡Enviado!',
        text: res.data.message || `El comprobante ha sido enviado exitosamente a ${emailDestino}`,
        icon: 'success',
        confirmButtonColor: '#4f46e5',
        customClass: { popup: 'rounded-3xl' }
      })
    }
  } catch (e) {
    Swal.fire({
      title: 'Error de Envío',
      text: e.response?.data?.message || 'Ocurrió un error al enviar el comprobante por correo electrónico.',
      icon: 'error',
      confirmButtonColor: '#ef4444',
      customClass: { popup: 'rounded-3xl' }
    })
  } finally {
    sendingEmail.value = false
  }
}

const enviarComprobanteWhatsApp = async () => {
  if (!detailCfdi.value) return

  const { value: telDestino } = await Swal.fire({
    title: 'Enviar por WhatsApp',
    text: 'Ingrese el número de WhatsApp (10 dígitos):',
    input: 'text',
    inputValue: detailCfdi.value.telefono_receptor || '',
    showCancelButton: true,
    confirmButtonText: '💬 Abrir WhatsApp',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#22c55e',
    customClass: { popup: 'rounded-3xl' }
  })
  if (!telDestino) return

  sendingWhatsApp.value = true
  try {
    const res = await axios.post(route('contabilidad.api.saldos-xml.enviar-whatsapp', { uuid: detailCfdi.value.uuid }), { telefono: telDestino })
    if (res.data.success) {
      if (res.data.via === 'web' && res.data.url) {
        window.open(res.data.url, '_blank')
        Swal.fire({
          title: 'Abriendo WhatsApp Web',
          text: 'Se abrió una nueva pestaña para enviar el mensaje directamente por WhatsApp.',
          icon: 'info',
          confirmButtonColor: '#22c55e',
          customClass: { popup: 'rounded-3xl' }
        })
      } else {
        Swal.fire({
          title: '¡Notificación Enviada!',
          text: res.data.message || 'Mensaje de WhatsApp enviado correctamente.',
          icon: 'success',
          confirmButtonColor: '#22c55e',
          customClass: { popup: 'rounded-3xl' }
        })
      }
    }
  } catch (e) {
    Swal.fire({
      title: 'Error de WhatsApp',
      text: e.response?.data?.message || 'Error al intentar notificar por WhatsApp.',
      icon: 'error',
      confirmButtonColor: '#ef4444',
      customClass: { popup: 'rounded-3xl' }
    })
  } finally {
    sendingWhatsApp.value = false
  }
}
</script>

<template>
  <div>
    <Head title="Conciliación y Saldos XML (AR/AP)" />

    <!-- CONTENEDOR 100% ANCHO EXPANDIDO PARA PANTALLAS GRANDES -->
    <div class="py-6 px-4 sm:px-6 lg:px-8 w-full max-w-none mx-auto">
      <div class="mb-8 flex flex-col xl:flex-row xl:items-center justify-between gap-6">
        
        <div class="flex items-center gap-4">
          <Link :href="route('contabilidad.index')" 
            class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-brand-600 transition-all border border-slate-200 dark:border-slate-600 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          </Link>
          <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-3">
              <span>Mesa de Conciliación Bancaria</span>
              <span class="px-2.5 py-1 text-xs bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 font-bold uppercase rounded-lg border border-indigo-500/20 tracking-wider">AI Powered</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Saldos de facturas pendientes y emparejamiento automático con estados de cuenta bancarios en PDF</p>
          </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-3">
          <!-- Filtros de Mes y Año Premium -->
          <div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-inner">
            <div class="flex items-center gap-1 pl-3 text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <select v-model="filtroMes" @change="fetchSaldosXml" class="text-xs font-bold border-none bg-transparent text-slate-800 dark:text-slate-200 focus:ring-0 cursor-pointer pr-8 py-1.5">
              <option value="todos">📅 Todos los meses</option>
              <option value="01">Enero</option>
              <option value="02">Febrero</option>
              <option value="03">Marzo</option>
              <option value="04">Abril</option>
              <option value="05">Mayo</option>
              <option value="06">Junio</option>
              <option value="07">Julio</option>
              <option value="08">Agosto</option>
              <option value="09">Septiembre</option>
              <option value="10">Octubre</option>
              <option value="11">Noviembre</option>
              <option value="12">Diciembre</option>
            </select>
            <span class="text-slate-300 dark:text-slate-600">|</span>
            <select v-model="filtroAnio" @change="fetchSaldosXml" class="text-xs font-bold border-none bg-transparent text-slate-800 dark:text-slate-200 focus:ring-0 cursor-pointer pr-8 py-1.5">
              <option value="todos">🗓️ Todos los años</option>
              <option value="2026">2026</option>
              <option value="2025">2025</option>
              <option value="2024">2024</option>
            </select>
          </div>

          <!-- Boton Subir PDF Banco con IA -->
          <label class="relative inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md cursor-pointer overflow-hidden group">
            <span class="absolute right-0 top-0 w-24 h-24 -mt-8 -mr-8 bg-white/20 rounded-full blur-xl group-hover:scale-150 transition-all duration-700"></span>
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>📥 Conciliar PDF Bancario (AI)</span>
            <input type="file" accept=".pdf" @change="handleBankPdfSelect" class="hidden" />
          </label>

          <label class="inline-flex items-center px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-sm cursor-pointer">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            {{ loadingCsv ? 'Procesando...' : (csvFileName ? csvFileName : 'Subir CSV/TXT') }}
            <input type="file" multiple accept=".csv,.txt,.xls,.xlsx" @change="handleCsvUpload" class="hidden" />
          </label>

          <Link :href="route('bancos.index')" class="inline-flex items-center px-4 py-2.5 bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-sm border border-white/10">
            <svg class="w-4 h-4 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span>🏦 Ir a Bancos y Tesorería</span>
          </Link>

          <button @click="fetchSaldosXml" :disabled="loadingSaldos" 
            class="inline-flex items-center px-4 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-sm">
            <svg v-if="loadingSaldos" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Actualizar
          </button>
        </div>
      </div>

      <!-- Password Input si el PDF estara protegido -->
      <div v-if="bankFile && !bankModalOpen" class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 rounded-2xl flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <div>
            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">¿El estado de cuenta de {{ bankFile.name }} tiene contraseña?</p>
            <p class="text-xs text-slate-500 dark:text-slate-400">Si es de BBVA o Banorte, ingresa tu RFC o contraseña para desbloquearlo en el servidor.</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <input v-model="bankPassword" type="password" placeholder="Contraseña o RFC..." class="text-sm px-3 py-1.5 border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-slate-900 dark:text-white" />
          <button @click="procesarPdfBancario" class="px-4 py-1.5 bg-indigo-600 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-indigo-700 transition-all">Procesar PDF</button>
        </div>
      </div>

      <!-- Error General -->
      <div v-if="errorMsg" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl text-sm text-rose-700 dark:text-rose-300">{{ errorMsg }}</div>

      <!-- Loading General -->
      <div v-if="loadingSaldos" class="flex items-center justify-center py-20">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-brand-500/30 border-t-brand-500"></div>
      </div>

      <template v-if="!loadingSaldos">
        <!-- Filtros y Buscador -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
          <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input v-model="filtroRfc" placeholder="Buscar por RFC o nombre del cliente/proveedor..." class="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all"/>
          </div>
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Mostrando resultados del corte: <span class="font-black text-brand-600 dark:text-brand-400">{{ filtroMes === 'todos' ? 'Todos los meses' : filtroMes }} / {{ filtroAnio === 'todos' ? 'Todos los años' : filtroAnio }}</span>
          </div>
        </div>

        <!-- Resumen Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
              <span class="block text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide mb-1">Por Cobrar (Clientes PPD y Notas)</span>
              <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">${{ n(saldosData.por_cobrar.total) }} <span class="text-xs font-normal text-slate-400">pendiente</span></p>
              <p class="text-xs text-slate-400 mt-1">{{ saldosData.por_cobrar.count }} registros ({{ porCobrarFacturas.length }} facturas / {{ porCobrarNotas.length }} notas)</p>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 text-2xl font-black">
              💰
            </div>
          </div>
          <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
              <span class="block text-[10px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wide mb-1">Por Pagar (Proveedores PPD y Notas)</span>
              <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">${{ n(saldosData.por_pagar.total) }} <span class="text-xs font-normal text-slate-400">pendiente</span></p>
              <p class="text-xs text-slate-400 mt-1">{{ saldosData.por_pagar.count }} registros ({{ porPagarFacturas.length }} facturas / {{ porPagarNotas.length }} notas)</p>
            </div>
            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-500 text-2xl font-black">
              💸
            </div>
          </div>
        </div>

        <!-- Detalle Tables 100% Ancho en 2 Columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- COLUMNA CLIENTES -->
          <div class="space-y-6">
            <!-- FACTURAS CXC -->
            <div class="space-y-3">
              <h2 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center justify-between px-1">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>
                  📄 Facturas Fiscales (CFDI PPD)
                </span>
                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold rounded-full text-[10px]">{{ porCobrarFacturas.length }}</span>
              </h2>
              <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                      <tr>
                        <th @click="toggleSort('cobrar', 'folio')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Folio {{ sortIcon('cobrar', 'folio') }}</th>
                        <th @click="toggleSort('cobrar', 'razon_social')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Cliente {{ sortIcon('cobrar', 'razon_social') }}</th>
                        <th @click="toggleSort('cobrar', 'saldo')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Saldo {{ sortIcon('cobrar', 'saldo') }}</th>
                        <th @click="toggleSort('cobrar', 'dias_vencimiento')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Estado {{ sortIcon('cobrar', 'dias_vencimiento') }}</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                      <tr v-for="item in porCobrarFacturas" :key="item.uuid" 
                        @click="abrirDetalleFactura(item)"
                        class="transition-all cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-700/50"
                        :class="(item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)) ? 'bg-emerald-500/5 border-l-4 border-emerald-500' : (item.estado_pago === 'parcial' ? 'bg-amber-500/5 border-l-4 border-amber-500' : 'bg-rose-500/5 border-l-4 border-rose-500')">
                        <td class="px-4 py-3 whitespace-nowrap">
                          <div class="text-xs font-black text-slate-900 dark:text-white">{{ item.serie }}{{ item.folio }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDate(item.fecha) }}</div>
                        </td>
                        <td class="px-4 py-3">
                          <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px]" :title="item.razon_social">{{ item.razon_social }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ item.rfc }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="text-xs font-black tabular-nums">
                            <span v-if="item.saldo < item.total"><span class="text-emerald-600 dark:text-emerald-400">${{ n(item.total) }}</span> <span class="text-rose-600 dark:text-rose-400">/${{ n(item.saldo) }}</span></span>
                            <span v-else class="text-slate-900 dark:text-white">${{ n(item.total) }}</span>
                          </div>
                          <div v-if="item.pagado > 0" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5 tabular-nums">Pagó: ${{ n(item.pagado) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="flex flex-col items-end gap-1">
                            <span v-if="item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)" class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>✅ Pagado</span>
                            </span>
                            <span v-else-if="item.estado_pago === 'parcial'" class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>⏳ Parcial</span>
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>🔴 Pendiente</span>
                            </span>
                            <span class="text-[10px] font-mono font-bold" :class="diasColor(item.dias_vencimiento)">{{ Math.round(item.dias_vencimiento) }}d</span>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="!porCobrarFacturas.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin facturas fiscales en este periodo</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- NOTAS DE VENTA CXC -->
            <div class="space-y-3">
              <h2 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center justify-between px-1">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 bg-indigo-500 rounded-full"></span>
                  🏷️ Notas de Venta (Sin Facturar)
                </span>
                <span class="px-2 py-0.5 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold rounded-full text-[10px]">{{ porCobrarNotas.length }}</span>
              </h2>
              <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                      <tr>
                        <th @click="toggleSort('cobrar', 'folio')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Folio {{ sortIcon('cobrar', 'folio') }}</th>
                        <th @click="toggleSort('cobrar', 'razon_social')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Cliente {{ sortIcon('cobrar', 'razon_social') }}</th>
                        <th @click="toggleSort('cobrar', 'saldo')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Saldo {{ sortIcon('cobrar', 'saldo') }}</th>
                        <th @click="toggleSort('cobrar', 'dias_vencimiento')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Estado {{ sortIcon('cobrar', 'dias_vencimiento') }}</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                      <tr v-for="item in porCobrarNotas" :key="item.uuid" 
                        @click="abrirDetalleFactura(item)"
                        class="transition-all cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-700/50"
                        :class="(item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)) ? 'bg-emerald-500/5 border-l-4 border-emerald-500' : (item.estado_pago === 'parcial' ? 'bg-amber-500/5 border-l-4 border-amber-500' : 'bg-rose-500/5 border-l-4 border-rose-500')">
                        <td class="px-4 py-3 whitespace-nowrap">
                          <div class="text-xs font-black text-slate-900 dark:text-white">{{ item.serie }}{{ item.folio }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDate(item.fecha) }}</div>
                        </td>
                        <td class="px-4 py-3">
                          <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px]" :title="item.razon_social">{{ item.razon_social }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ item.rfc }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="text-xs font-black tabular-nums">
                            <span v-if="item.saldo < item.total"><span class="text-emerald-600 dark:text-emerald-400">${{ n(item.total) }}</span> <span class="text-rose-600 dark:text-rose-400">/${{ n(item.saldo) }}</span></span>
                            <span v-else class="text-slate-900 dark:text-white">${{ n(item.total) }}</span>
                          </div>
                          <div v-if="item.pagado > 0" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5 tabular-nums">Pagó: ${{ n(item.pagado) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="flex flex-col items-end gap-1">
                            <span v-if="item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)" class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>✅ Pagado</span>
                            </span>
                            <span v-else-if="item.estado_pago === 'parcial'" class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>⏳ Parcial</span>
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>🔴 Pendiente</span>
                            </span>
                            <span class="text-[10px] font-mono font-bold" :class="diasColor(item.dias_vencimiento)">{{ Math.round(item.dias_vencimiento) }}d</span>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="!porCobrarNotas.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin notas de venta en este periodo</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- COLUMNA PROVEEDORES -->
          <div class="space-y-6">
            <!-- FACTURAS CXP -->
            <div class="space-y-3">
              <h2 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center justify-between px-1">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 bg-rose-500 rounded-full"></span>
                  📄 Facturas Recibidas (CFDI PPD)
                </span>
                <span class="px-2 py-0.5 bg-rose-500/10 text-rose-600 dark:text-rose-400 font-bold rounded-full text-[10px]">{{ porPagarFacturas.length }}</span>
              </h2>
              <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                      <tr>
                        <th @click="toggleSort('pagar', 'folio')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Folio {{ sortIcon('pagar', 'folio') }}</th>
                        <th @click="toggleSort('pagar', 'razon_social')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Proveedor {{ sortIcon('pagar', 'razon_social') }}</th>
                        <th @click="toggleSort('pagar', 'saldo')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Saldo {{ sortIcon('pagar', 'saldo') }}</th>
                        <th @click="toggleSort('pagar', 'dias_vencimiento')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Estado {{ sortIcon('pagar', 'dias_vencimiento') }}</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                      <tr v-for="item in porPagarFacturas" :key="item.uuid"
                        @click="abrirDetalleFactura(item)"
                        class="transition-all cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-700/50"
                        :class="(item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)) ? 'bg-emerald-500/5 border-l-4 border-emerald-500' : (item.estado_pago === 'parcial' ? 'bg-amber-500/5 border-l-4 border-amber-500' : 'bg-rose-500/5 border-l-4 border-rose-500')">
                        <td class="px-4 py-3 whitespace-nowrap">
                          <div class="text-xs font-black text-slate-900 dark:text-white">{{ item.serie }}{{ item.folio }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDate(item.fecha) }}</div>
                        </td>
                        <td class="px-4 py-3">
                          <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px]" :title="item.razon_social">{{ item.razon_social }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ item.rfc }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="text-xs font-black tabular-nums">
                            <span v-if="item.saldo < item.total"><span class="text-emerald-600 dark:text-emerald-400">${{ n(item.total) }}</span> <span class="text-rose-600 dark:text-rose-400">/${{ n(item.saldo) }}</span></span>
                            <span v-else class="text-slate-900 dark:text-white">${{ n(item.total) }}</span>
                          </div>
                          <div v-if="item.pagado > 0" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5 tabular-nums">Pagó: ${{ n(item.pagado) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="flex flex-col items-end gap-1">
                            <span v-if="item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)" class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>✅ Pagado</span>
                            </span>
                            <span v-else-if="item.estado_pago === 'parcial'" class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>⏳ Parcial</span>
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>🔴 Pendiente</span>
                            </span>
                            <span class="text-[10px] font-mono font-bold" :class="diasColor(item.dias_vencimiento)">{{ Math.round(item.dias_vencimiento) }}d</span>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="!porPagarFacturas.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin facturas recibidas en este periodo</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- ÓRDENES / NOTAS DE COMPRA CXP -->
            <div class="space-y-3">
              <h2 class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-wider flex items-center justify-between px-1">
                <span class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 bg-amber-500 rounded-full"></span>
                  🛒 Órdenes / Notas de Compra (Sin Facturar)
                </span>
                <span class="px-2 py-0.5 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold rounded-full text-[10px]">{{ porPagarNotas.length }}</span>
              </h2>
              <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700">
                      <tr>
                        <th @click="toggleSort('pagar', 'folio')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Folio {{ sortIcon('pagar', 'folio') }}</th>
                        <th @click="toggleSort('pagar', 'razon_social')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-brand-600 select-none">Proveedor {{ sortIcon('pagar', 'razon_social') }}</th>
                        <th @click="toggleSort('pagar', 'saldo')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Saldo {{ sortIcon('pagar', 'saldo') }}</th>
                        <th @click="toggleSort('pagar', 'dias_vencimiento')" class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right cursor-pointer hover:text-brand-600 select-none">Estado {{ sortIcon('pagar', 'dias_vencimiento') }}</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                      <tr v-for="item in porPagarNotas" :key="item.uuid"
                        @click="abrirDetalleFactura(item)"
                        class="transition-all cursor-pointer hover:bg-slate-50/80 dark:hover:bg-slate-700/50"
                        :class="(item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)) ? 'bg-emerald-500/5 border-l-4 border-emerald-500' : (item.estado_pago === 'parcial' ? 'bg-amber-500/5 border-l-4 border-amber-500' : 'bg-rose-500/5 border-l-4 border-rose-500')">
                        <td class="px-4 py-3 whitespace-nowrap">
                          <div class="text-xs font-black text-slate-900 dark:text-white">{{ item.serie }}{{ item.folio }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDate(item.fecha) }}</div>
                        </td>
                        <td class="px-4 py-3">
                          <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate max-w-[200px]" :title="item.razon_social">{{ item.razon_social }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ item.rfc }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="text-xs font-black tabular-nums">
                            <span v-if="item.saldo < item.total"><span class="text-emerald-600 dark:text-emerald-400">${{ n(item.total) }}</span> <span class="text-rose-600 dark:text-rose-400">/${{ n(item.saldo) }}</span></span>
                            <span v-else class="text-slate-900 dark:text-white">${{ n(item.total) }}</span>
                          </div>
                          <div v-if="item.pagado > 0" class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5 tabular-nums">Pagó: ${{ n(item.pagado) }}</div>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                          <div class="flex flex-col items-end gap-1">
                            <span v-if="item.estado_pago === 'pagado' || estaPagadoCsv(item.uuid)" class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>✅ Pagado</span>
                            </span>
                            <span v-else-if="item.estado_pago === 'parcial'" class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>⏳ Parcial</span>
                            </span>
                            <span v-else class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 font-black text-[9px] uppercase tracking-wider flex items-center gap-1">
                              <span>🔴 Pendiente</span>
                            </span>
                            <span class="text-[10px] font-mono font-bold" :class="diasColor(item.dias_vencimiento)">{{ Math.round(item.dias_vencimiento) }}d</span>
                          </div>
                        </td>
                      </tr>
                      <tr v-if="!porPagarNotas.length">
                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin notas de compra en este periodo</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- MODAL DE CONCILIACIÓN BANCARIA AI (PDF) -->
    <div v-if="bankModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm animate-fadeIn">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col overflow-hidden">
        
        <!-- Encabezado Modal -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-indigo-900/20 via-slate-900 to-slate-900 text-white">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
              <h3 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                <span>Auditoría Bancaria AI</span>
                <span v-if="bankDataResult" class="px-3 py-1 text-xs bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-500/30 font-mono">{{ bankDataResult.banco_nombre }}</span>
              </h3>
              <p class="text-xs text-slate-400 mt-1">Cruce automatizado renglón por renglón del estado de cuenta bancario contra tus registros contables</p>
            </div>
          </div>
          <button @click="bankModalOpen = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Estado de Carga -->
        <div v-if="loadingBankPdf" class="flex-1 flex flex-col items-center justify-center py-32 px-4 text-center">
          <div class="relative w-24 h-24 mb-6">
            <div class="absolute inset-0 rounded-full border-4 border-indigo-500/20 animate-pulse"></div>
            <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-indigo-500 animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center text-2xl">🤖</div>
          </div>
          <h4 class="text-xl font-black text-slate-900 dark:text-white mb-2">Gemini 2.0 Flash Leyendo Estado de Cuenta...</h4>
          <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md">Analizando tablas, columnas de retiro/depósito y emparejando automáticamente por montos y fechas exactas.</p>
        </div>

        <!-- Resultados del Banco -->
        <div v-else-if="bankDataResult" class="flex-1 flex flex-col overflow-hidden">
          
          <!-- Barra Superior de Saldos -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Periodo Detectado</span>
              <span class="text-lg font-black text-slate-900 dark:text-white font-mono">{{ bankDataResult.periodo_anio_mes }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Cuenta Bancaria</span>
              <span class="text-lg font-black text-slate-900 dark:text-white font-mono">**** {{ bankDataResult.cuenta_numero }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Saldo Inicial</span>
              <span class="text-lg font-black text-slate-900 dark:text-white tabular-nums">${{ n(bankDataResult.saldo_inicial) }}</span>
            </div>
            <div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Saldo Final</span>
              <span class="text-lg font-black text-indigo-600 dark:text-indigo-400 tabular-nums">${{ n(bankDataResult.saldo_final) }}</span>
            </div>
          </div>

          <!-- Filtros de Pestañas en Modal -->
          <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <button @click="activeTab = 'all'" class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all" :class="activeTab === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                Todos ({{ bankDataResult.movimientos.length }})
              </button>
              <button @click="activeTab = 'matched'" class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" :class="activeTab === 'matched' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Conciliados ({{ bankDataResult.resumen_match?.conciliados || 0 }})
              </button>
              <button @click="activeTab = 'pending'" class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5" :class="activeTab === 'pending' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                Pendientes ({{ bankDataResult.resumen_match?.pendientes || 0 }})
              </button>
            </div>
          </div>

          <!-- Tabla de Movimientos del Banco -->
          <div class="flex-1 overflow-y-auto p-6">
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
              <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/80 sticky top-0 z-10 border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Fecha / Ref</th>
                    <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Concepto Bancario</th>
                    <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Retiro (Cargo)</th>
                    <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Depósito (Abono)</th>
                    <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-center">Estado Conciliación</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr v-for="mov in movimientosBancariosFiltrados" :key="mov.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                    <td class="px-4 py-3 align-top whitespace-nowrap">
                      <div class="text-xs font-black text-slate-900 dark:text-white">{{ mov.fecha }}</div>
                      <div class="text-[10px] text-slate-400 font-mono">{{ mov.referencia || 'SIN REF' }}</div>
                    </td>
                    <td class="px-4 py-3 align-top">
                      <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ mov.concepto }}</div>
                      <div v-if="mov.cfdi_folio" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium mt-0.5 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Cruce CFDI: {{ mov.cfdi_folio }} - {{ mov.cfdi_emisor }}</span>
                      </div>
                      
                      <!-- Alerta Inteligente de SAT REP para PPD -->
                      <div v-if="mov.requiere_rep" class="mt-1 flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider w-fit shadow-sm border"
                        :class="mov.tiene_rep 
                          ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400'
                          : (mov.rep_tipo === 'emitir' 
                            ? 'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400' 
                            : 'bg-amber-500/10 border-amber-500/20 text-amber-600 dark:text-amber-400')">
                        <svg class="w-3.5 h-3.5" :class="!mov.tiene_rep && 'animate-pulse'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path v-if="mov.tiene_rep" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ mov.rep_mensaje }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-right align-top tabular-nums">
                      <span v-if="mov.tipo === 'cargo'" class="text-xs font-black text-rose-600 dark:text-rose-400">${{ n(mov.monto) }}</span>
                      <span v-else class="text-slate-300 dark:text-slate-600">-</span>
                    </td>
                    <td class="px-4 py-3 text-right align-top tabular-nums">
                      <span v-if="mov.tipo === 'abono'" class="text-xs font-black text-emerald-600 dark:text-emerald-400">${{ n(mov.monto) }}</span>
                      <span v-else class="text-slate-300 dark:text-slate-600">-</span>
                    </td>
                    <td class="px-4 py-3 text-center align-top">
                      <div class="flex flex-col items-center gap-1.5">
                        <div v-if="mov.status === 'matched'" class="flex flex-col items-center gap-1">
                          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                            :class="mov.status_badge_class || 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400'">
                            <span>{{ mov.status_badge_text || '✅ Conciliado Automático' }}</span>
                          </div>
                          <div v-if="mov.match_reason" class="text-[9px] text-slate-400 dark:text-slate-500 max-w-[150px] leading-tight text-center font-medium">
                            {{ mov.match_reason }}
                          </div>
                        </div>
                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-wider">Pendiente</span>

                        <button v-if="mov.suggested_action && !mov.poliza_id && mov.status_badge_text !== '✅ Conciliado (Libros)'" @click="generarPolizaSuelta(mov)" :disabled="mov.is_loading"
                          class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all shadow-sm flex items-center gap-1.5 mt-1">
                          <svg v-if="mov.is_loading" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                          <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                          <span>{{ mov.suggested_action === 'create_comision' ? 'Generar Póliza Comisión' : 'Generar Póliza Bancaria' }}</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="!movimientosBancariosFiltrados.length">
                    <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-xs">No hay movimientos bancarios en esta sección</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Pie del Modal -->
          <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
            <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              {{ bankDataResult.resumen_match?.conciliados || 0 }} de {{ bankDataResult.movimientos.length }} movimientos tienen su póliza o CFDI enlazado exitosamente.
            </span>
            <button @click="bankModalOpen = false" class="px-6 py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
              Guardar y Cerrar
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- MODAL DETALLE DE FACTURA (PÓLIZAS Y XML) -->
    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm animate-fadeIn">
      <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">
        
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-indigo-900/20 via-slate-900 to-slate-900 text-white">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20 text-lg">
              📄
            </div>
            <div>
              <h3 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                <span>Detalle de Factura: {{ detailCfdi?.serie }}{{ detailCfdi?.folio || 'Sin Folio' }}</span>
              </h3>
              <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ detailCfdi?.uuid || 'Cargando UUID...' }}</p>
            </div>
          </div>
          <button @click="showDetailModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loadingDetail" class="flex-1 flex flex-col items-center justify-center py-24 px-4 text-center">
          <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-500/30 border-t-indigo-500 mb-4"></div>
          <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Consultando Base de Datos y XML Contable...</h4>
        </div>

        <!-- Content -->
        <div v-else-if="detailCfdi" class="flex-1 flex flex-col overflow-hidden">
          
          <!-- CFDI Summary Info -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-5 bg-slate-50 dark:bg-slate-800/40 border-b border-slate-100 dark:border-slate-800 text-xs">
            <div>
              <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Emisor</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block truncate" :title="detailCfdi.nombre_emisor">{{ detailCfdi.nombre_emisor }}</span>
              <span class="font-mono text-[10px] text-slate-500 block">{{ detailCfdi.rfc_emisor }}</span>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Receptor</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block truncate" :title="detailCfdi.nombre_receptor">{{ detailCfdi.nombre_receptor }}</span>
              <span class="font-mono text-[10px] text-slate-500 block">{{ detailCfdi.rfc_receptor }}</span>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Fecha Emisión</span>
              <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ formatDate(detailCfdi.fecha_emision) }}</span>
              <span class="text-[10px] text-slate-500 block">{{ detailCfdi.metodo_pago }} - {{ detailCfdi.forma_pago }}</span>
            </div>
            <div>
              <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block mb-0.5">Monto Total CFDI</span>
              <span class="text-base font-black text-indigo-600 dark:text-indigo-400 block tabular-nums">${{ n(detailCfdi.total) }}</span>
              <span class="text-[9px] text-slate-400 block">Subtotal: ${{ n(detailCfdi.subtotal) }}</span>
            </div>
          </div>

          <!-- Tabs -->
          <div class="px-6 py-2 border-b border-slate-100 dark:border-slate-800 flex items-center bg-slate-50 dark:bg-slate-800/20">
            <div class="flex gap-2">
              <button @click="activeDetailTab = 'polizas'" 
                class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all" 
                :class="activeDetailTab === 'polizas' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                📒 Pólizas Contables ({{ detailPolizas.length }})
              </button>
              <button @click="activeDetailTab = 'conceptos'" 
                class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all" 
                :class="activeDetailTab === 'conceptos' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                🛍️ Conceptos ({{ detailCfdi.conceptos?.length || 0 }})
              </button>
              <button @click="activeDetailTab = 'pdf'" 
                class="px-4 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all" 
                :class="activeDetailTab === 'pdf' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'">
                📄 Vista Previa PDF
              </button>
            </div>
          </div>

          <!-- Body Scrollable -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6 max-h-[55vh]">
            
            <!-- Tab: Pólizas Contables -->
            <div v-if="activeDetailTab === 'polizas'" class="space-y-4">
              <div v-if="detailPolizas.length === 0" class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl">
                <p class="text-xs text-slate-400 font-medium">💡 No hay pólizas contables creadas para esta factura aún.</p>
                <p class="text-[10px] text-slate-500 mt-1">Registra un movimiento en Bancos o genera la póliza del diario para Comprobantela en la contabilidad.</p>
              </div>
              <div v-for="poliza in detailPolizas" :key="poliza.id" class="p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700/50 pb-3">
                  <div>
                    <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded bg-indigo-500/10 text-indigo-500 border border-indigo-500/20 mr-2">{{ poliza.tipo }}</span>
                    <span class="text-xs font-black text-slate-800 dark:text-slate-200">Póliza Número #{{ poliza.numero }}</span>
                    <span class="text-[10px] text-slate-400 block mt-0.5">Fecha: {{ formatDate(poliza.fecha) }} | Concepto: {{ poliza.concepto }}</span>
                  </div>
                  <div class="text-right">
                    <span class="text-xs font-black text-slate-900 dark:text-white block">${{ n(poliza.total) }}</span>
                    <span class="text-[9px] uppercase tracking-wider font-bold" :class="poliza.estado === 'asentada' ? 'text-emerald-500' : 'text-amber-500'">{{ poliza.estado }}</span>
                  </div>
                </div>

                <!-- Asientos Contables Table -->
                <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-700/60">
                  <table class="w-full text-left text-[10px]">
                    <thead class="bg-slate-50 dark:bg-slate-700/30">
                      <tr>
                        <th class="px-3 py-2 text-[9px] font-bold text-slate-500 uppercase tracking-wider">Código Cuenta</th>
                        <th class="px-3 py-2 text-[9px] font-bold text-slate-500 uppercase tracking-wider">Nombre de Cuenta</th>
                        <th class="px-3 py-2 text-[9px] font-bold text-slate-500 uppercase tracking-wider">Concepto / Ref</th>
                        <th class="px-3 py-2 text-[9px] font-bold text-slate-500 uppercase tracking-wider text-right">Debe (Cargo)</th>
                        <th class="px-3 py-2 text-[9px] font-bold text-slate-500 uppercase tracking-wider text-right">Haber (Abono)</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/40">
                      <tr v-for="asiento in poliza.asientos" :key="asiento.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/10">
                        <td class="px-3 py-2 font-mono font-bold text-slate-700 dark:text-slate-300">{{ asiento.cuenta?.codigo || asiento.cuenta_contable?.codigo || asiento.cuenta_codigo || '-' }}</td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-400">{{ asiento.cuenta?.nombre || asiento.cuenta_contable?.nombre || asiento.cuenta_nombre || '-' }}</td>
                        <td class="px-3 py-2 text-slate-600 dark:text-slate-400 truncate max-w-[150px]" :title="asiento.referencia || poliza.concepto">{{ asiento.referencia || poliza.concepto }}</td>
                        <td class="px-3 py-2 text-right font-bold text-slate-900 dark:text-white tabular-nums">{{ parseFloat(asiento.debe) > 0 ? `$${n(asiento.debe)}` : '-' }}</td>
                        <td class="px-3 py-2 text-right font-bold text-slate-900 dark:text-white tabular-nums">{{ parseFloat(asiento.haber) > 0 ? `$${n(asiento.haber)}` : '-' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Tab: Conceptos -->
            <div v-if="activeDetailTab === 'conceptos'" class="space-y-4">
              <div v-if="!detailCfdi.conceptos || detailCfdi.conceptos.length === 0" class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl text-slate-400 text-xs">
                💡 No hay conceptos disponibles para este CFDI.
              </div>
              <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                <table class="w-full text-left text-xs">
                  <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                      <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Clave SAT</th>
                      <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Descripción</th>
                      <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Cant</th>
                      <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Unitario</th>
                      <th class="px-4 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-wider text-right">Importe</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    <tr v-for="concept in detailCfdi.conceptos" :key="concept.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                      <td class="px-4 py-3 font-mono text-[10px] text-slate-500">{{ concept.clave_prod_serv }}</td>
                      <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-medium">{{ concept.descripcion }}</td>
                      <td class="px-4 py-3 text-right font-bold">{{ concept.cantidad }}</td>
                      <td class="px-4 py-3 text-right tabular-nums">${{ n(concept.valor_unitario) }}</td>
                      <td class="px-4 py-3 text-right font-black tabular-nums">${{ n(concept.importe) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Comprobante PDF -->
            <div v-if="activeDetailTab === 'pdf'" class="space-y-4">
              <div v-if="!detailCfdi.pdf_url" class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 border border-dashed border-slate-200 dark:border-slate-700 rounded-2xl text-slate-400 text-xs">
                💡 No se encontró la vista previa en PDF para este comprobante.
              </div>
              <div v-else class="h-[55vh] rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-slate-900 shadow-inner">
                <iframe :src="detailCfdi.pdf_url" class="w-full h-full border-0"></iframe>
              </div>
            </div>

          </div>

          <!-- Footer -->
          <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <button @click="enviarComprobanteCorreo" :disabled="sendingEmail" 
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                <svg v-if="sendingEmail" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                <span v-else class="mr-2">📧</span>
                {{ sendingEmail ? 'Enviando Correo...' : 'Enviar por Correo' }}
              </button>
              <button @click="enviarComprobanteWhatsApp" :disabled="sendingWhatsApp" 
                class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                <svg v-if="sendingWhatsApp" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                <span v-else class="mr-2">💬</span>
                {{ sendingWhatsApp ? 'Enviando WhatsApp...' : 'Enviar por WhatsApp' }}
              </button>
            </div>
            <button @click="showDetailModal = false" class="px-6 py-2.5 bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
              Cerrar Detalle
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
