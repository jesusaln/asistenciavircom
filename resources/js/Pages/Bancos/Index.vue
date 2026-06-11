<script setup>
import { ref, computed, watch, onMounted, reactive } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import Swal from '@/Utils/Swal'
import AdministradorXmlRepModal from '@/Components/Bancos/AdministradorXmlRepModal.vue'
import FormalizarCobranzaModal from '@/Components/Bancos/FormalizarCobranzaModal.vue'
import AceptarEntregaModal from '@/Components/Bancos/AceptarEntregaModal.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuentas: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  proveedores: { type: Array, default: () => [] },
  cuentasCobrar: { type: Array, default: () => [] },
  cuentasPagar: { type: Array, default: () => [] },
  cuentasContables: { type: Array, default: () => [] }
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const activeTab = ref('cuentas') // 'cuentas' | 'movimientos'
const cuentasBancarias = ref([...props.cuentas])
const movimientos = ref([])
const selectedCuentaFiltro = ref(null) // Filtro activo de cuenta para la vista de movimientos
const searchMovsQuery = ref('') // Buscador inteligente de movimientos
const currentPageMovs = ref(1)
const itemsPerPageMovs = ref(15)

const loadingMovs = ref(false)
const showAccountModal = ref(false)
const showMovModal = ref(false)
const savingAccount = ref(false)
const savingMov = ref(false)
const showPolizaModal = ref(false)
const selectedPoliza = ref(null)

const verPoliza = (poliza) => {
  selectedPoliza.value = poliza
  showPolizaModal.value = true
}

const subTab = ref('movimientos') // 'movimientos' | 'entregas'
const entregasPendientes = ref([])
const loadingEntregas = ref(false)
const acceptingEntregaId = ref(null)
const selectedEntregaToAccept = ref(null)

watch([searchMovsQuery, selectedCuentaFiltro, itemsPerPageMovs], () => {
  currentPageMovs.value = 1
})

const verCuentaMovimientos = (cuenta) => {
  selectedCuentaFiltro.value = cuenta
  activeTab.value = 'movimientos'
  subTab.value = 'movimientos'
}

// Autofill/Linking States
const searchQuery = ref('')
const searchTab = ref('pendientes') // 'pendientes' | 'todos'

// Account Form
const isEditingAccount = ref(false)
const accountForm = reactive({
  id: null,
  nombre_banco: '',
  alias: '',
  numero_cuenta: '',
  clabe: '',
  moneda: 'MXN',
  saldo_inicial: 0,
  cuenta_contable_id: null,
  es_fiscal: true,
  tipo: 'cuenta'
})

// Movement Form
const isEditingMov = ref(false)
const editingMovId = ref(null)
const movForm = reactive({
  cuenta_bancaria_id: '',
  fecha: new Date().toISOString().substring(0, 10),
  tipo: 'egreso',
  forma_pago_sat: '03',
  monto: 0,
  concepto: '',
  referencia: '',
  beneficiario_rfc: '',
  beneficiario_nombre: '',
  cxc_id: null,
  cxp_id: null
})

// Fetch Movements
const fetchMovements = async () => {
  loadingMovs.value = true
  try {
    const res = await axios.get(route('bancos.api.movimientos'))
    if (res.data.success) {
      movimientos.value = res.data.movimientos
    }
  } catch (e) {
    notyf.error('Error al cargar movimientos bancarios')
  } finally {
    loadingMovs.value = false
  }
}

// Fetch Entregas Pendientes
const fetchEntregasPendientes = async () => {
  loadingEntregas.value = true
  try {
    const res = await axios.get(route('bancos.api.entregas.pendientes'))
    if (res.data.success) {
      entregasPendientes.value = res.data.entregas
    }
  } catch (e) {
    console.error('Error al cargar entregas pendientes', e)
  } finally {
    loadingEntregas.value = false
  }
}

const onFormalizarSuccess = (resData) => {
  if (resData && resData.cuenta) {
    const idx = cuentasBancarias.value.findIndex(c => c.id === resData.cuenta.id)
    if (idx !== -1) {
      cuentasBancarias.value[idx] = resData.cuenta
      if (selectedCuentaFiltro.value?.id === resData.cuenta.id) {
        selectedCuentaFiltro.value = resData.cuenta
      }
    }
    fetchMovements()
    subTab.value = 'movimientos'
  } else {
    fetchEntregasPendientes()
    subTab.value = 'entregas'
  }
}

const abrirModalAceptarEntrega = (entrega) => {
  selectedEntregaToAccept.value = entrega
  showAceptarModal.value = true
}

const onAceptarEntregaSuccess = (cuenta) => {
  const idx = cuentasBancarias.value.findIndex(c => c.id === cuenta.id)
  if (idx !== -1) {
    cuentasBancarias.value[idx] = cuenta
    if (selectedCuentaFiltro.value?.id === cuenta.id) {
      selectedCuentaFiltro.value = cuenta
    }
  }
  if (selectedEntregaToAccept.value) {
    entregasPendientes.value = entregasPendientes.value.filter(e => e.id !== selectedEntregaToAccept.value.id)
  }
  fetchMovements()
  subTab.value = 'movimientos'
  showAceptarModal.value = false
}

// On Mount
onMounted(() => {
  const flash = usePage().props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
  fetchMovements()
  fetchEntregasPendientes()
})

// Calculations
const totalConsolidado = computed(() => {
  return cuentasBancarias.value.reduce((acc, c) => acc + parseFloat(c.saldo_inicial || 0), 0)
})

const movimientosFiltradosBusqueda = computed(() => {
  let list = movimientos.value
  if (selectedCuentaFiltro.value) {
    list = list.filter(m => m.cuenta_bancaria_id === selectedCuentaFiltro.value.id)
  }
  const q = searchMovsQuery.value.toLowerCase().trim()
  if (q) {
    list = list.filter(m => {
      const fecha = m.fecha ? String(m.fecha) : ''
      const banco = String(m.cuenta_bancaria?.alias || m.cuenta_bancaria?.nombre_banco || '').toLowerCase()
      const cuenta = String(m.cuenta_bancaria?.numero_cuenta || '').toLowerCase()
      const concepto = String(m.concepto || '').toLowerCase()
      const ref = String(m.referencia || '').toLowerCase()
      const ben = String(m.beneficiario_nombre || '').toLowerCase()
      const rfc = String(m.beneficiario_rfc || '').toLowerCase()
      const monto = String(m.monto || '')
      return concepto.includes(q) || monto.includes(q) || fecha.includes(q) || banco.includes(q) || cuenta.includes(q) || ref.includes(q) || ben.includes(q) || rfc.includes(q)
    })
  }
  return list
})

const totalPagesMovs = computed(() => {
  return Math.ceil(movimientosFiltradosBusqueda.value.length / itemsPerPageMovs.value) || 1
})

const visiblePageNumbersMovs = computed(() => {
  const current = currentPageMovs.value
  const total = totalPagesMovs.value
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  if (current <= 4) {
    return [1, 2, 3, 4, 5, '...', total]
  }
  if (current >= total - 3) {
    return [1, '...', total - 4, total - 3, total - 2, total - 1, total]
  }
  return [1, '...', current - 1, current, current + 1, '...', total]
})

const movimientosPaginados = computed(() => {
  const start = (currentPageMovs.value - 1) * itemsPerPageMovs.value
  return movimientosFiltradosBusqueda.value.slice(start, start + itemsPerPageMovs.value)
})

// Filtered pending invoices / accounts
const filteredAccounts = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  if (movForm.tipo === 'ingreso') {
    return props.cuentasCobrar.filter(item => {
      const clientName = (item.cliente?.nombre_razon_social || '').toLowerCase()
      const clientRfc = (item.cliente?.rfc || '').toLowerCase()
      const folio = (item.cobrable?.numero_venta || item.referencia || '').toLowerCase()
      return clientName.includes(query) || clientRfc.includes(query) || folio.includes(query)
    })
  } else if (movForm.tipo === 'egreso') {
    return props.cuentasPagar.filter(item => {
      const supplierName = (item.proveedor?.nombre_razon_social || item.compra?.proveedor?.nombre_razon_social || '').toLowerCase()
      const supplierRfc = (item.proveedor?.rfc || item.compra?.proveedor?.rfc || '').toLowerCase()
      const folio = (item.compra?.numero_compra || item.referencia || '').toLowerCase()
      return supplierName.includes(query) || supplierRfc.includes(query) || folio.includes(query)
    })
  }
  return []
})

// Filtered general entities (clients or suppliers)
const filteredEntities = computed(() => {
  const query = searchQuery.value.toLowerCase().trim()
  if (movForm.tipo === 'ingreso') {
    return props.clientes.filter(c => {
      return (c.nombre_razon_social || '').toLowerCase().includes(query) || (c.rfc || '').toLowerCase().includes(query)
    })
  } else if (movForm.tipo === 'egreso') {
    return props.proveedores.filter(p => {
      return (p.nombre_razon_social || '').toLowerCase().includes(query) || (p.rfc || '').toLowerCase().includes(query)
    })
  }
  return []
})

// Auto-fill CxC/CxP logic
const selectAccount = (item) => {
  if (movForm.tipo === 'ingreso') {
    movForm.cxc_id = item.id
    movForm.cxp_id = null
    movForm.monto = parseFloat(item.monto_pendiente)
    movForm.beneficiario_nombre = item.cliente?.nombre_razon_social || ''
    movForm.beneficiario_rfc = item.cliente?.rfc || ''
    const folio = item.cobrable?.numero_venta || item.referencia || `CxC #${item.id}`
    movForm.concepto = `Pago Factura ${folio} - ${item.cliente?.nombre_razon_social || ''}`
    movForm.referencia = folio
  } else {
    movForm.cxp_id = item.id
    movForm.cxc_id = null
    movForm.monto = parseFloat(item.monto_pendiente)
    const name = item.proveedor?.nombre_razon_social || item.compra?.proveedor?.nombre_razon_social || ''
    const rfc = item.proveedor?.rfc || item.compra?.proveedor?.rfc || ''
    movForm.beneficiario_nombre = name
    movForm.beneficiario_rfc = rfc
    const folio = item.compra?.numero_compra || item.referencia || `CxP #${item.id}`
    movForm.concepto = `Pago Factura ${folio} - ${name}`
    movForm.referencia = folio
  }
  searchQuery.value = ''
  notyf.success('Campos auto-completados desde la factura')
}

// Modal Administrador XML / REP logic
const showXmlRepModal = ref(false)
const handleSelectFromXmlRepModal = (data) => {
  showMovModal.value = true
  movForm.tipo = data.tipo || 'egreso'
  movForm.monto = parseFloat(data.monto) || 0
  movForm.concepto = data.concepto || ''
  movForm.referencia = data.referencia || ''
  movForm.beneficiario_nombre = data.contraparte_nombre || ''
  movForm.beneficiario_rfc = data.contraparte_rfc || ''
  
  searchQuery.value = data.folio || data.referencia || ''
  setTimeout(() => {
    if (filteredAccounts.value.length > 0) {
      const match = filteredAccounts.value.find(item => {
        const f = (item.cobrable?.numero_venta || item.compra?.numero_compra || item.referencia || '').toLowerCase()
        return f === (data.folio || '').toLowerCase() || f === (data.referencia || '').toLowerCase()
      })
      if (match) {
        if (data.tipo === 'ingreso') {
          movForm.cxc_id = match.id
          movForm.cxp_id = null
        } else {
          movForm.cxp_id = match.id
          movForm.cxc_id = null
        }
      }
    }
  }, 300)

  notyf.success('Datos del XML / REP cargados al formulario exitosamente.')
}

// Auto-fill Entity (Client/Supplier) logic
const selectEntity = (entity) => {
  movForm.beneficiario_nombre = entity.nombre_razon_social
  movForm.beneficiario_rfc = entity.rfc || ''
  movForm.cxc_id = null
  movForm.cxp_id = null
  searchQuery.value = ''
  notyf.success(`Campos auto-completados para: ${entity.nombre_razon_social}`)
}

// Desvincular
const clearLink = () => {
  movForm.cxc_id = null
  movForm.cxp_id = null
  movForm.monto = 0
  movForm.concepto = ''
  movForm.referencia = ''
  movForm.beneficiario_nombre = ''
  movForm.beneficiario_rfc = ''
  notyf.success('Factura desvinculada')
}

// Actions
const openAddAccountModal = () => {
  isEditingAccount.value = false
  accountForm.id = null
  accountForm.nombre_banco = ''
  accountForm.alias = ''
  accountForm.numero_cuenta = ''
  accountForm.clabe = ''
  accountForm.moneda = 'MXN'
  accountForm.saldo_inicial = 0
  accountForm.cuenta_contable_id = null
  accountForm.es_fiscal = true
  accountForm.tipo = 'cuenta'
  showAccountModal.value = true
}

const openEditAccountModal = (cuenta) => {
  isEditingAccount.value = true
  accountForm.id = cuenta.id
  accountForm.nombre_banco = cuenta.nombre_banco
  accountForm.alias = cuenta.alias || ''
  accountForm.numero_cuenta = cuenta.numero_cuenta || ''
  accountForm.clabe = cuenta.clabe || ''
  accountForm.moneda = cuenta.moneda || 'MXN'
  accountForm.saldo_inicial = parseFloat(cuenta.saldo_actual ?? cuenta.saldo_inicial) || 0
  accountForm.cuenta_contable_id = cuenta.cuenta_contable_id
  accountForm.es_fiscal = Boolean(cuenta.es_fiscal)
  accountForm.tipo = cuenta.tipo || 'cuenta'
  showAccountModal.value = true
}

const submitAccount = async () => {
  savingAccount.value = true
  try {
    if (isEditingAccount.value) {
      const res = await axios.put(route('bancos.api.cuentas.update', accountForm.id), accountForm)
      if (res.data.success) {
        notyf.success('Cuenta bancaria actualizada con éxito')
        const idx = cuentasBancarias.value.findIndex(c => c.id === accountForm.id)
        if (idx !== -1) {
          cuentasBancarias.value[idx] = res.data.cuenta
        }
        showAccountModal.value = false
      }
    } else {
      const res = await axios.post(route('bancos.api.cuentas.store'), accountForm)
      if (res.data.success) {
        notyf.success('Cuenta bancaria agregada con éxito')
        cuentasBancarias.value.push(res.data.cuenta)
        showAccountModal.value = false
      }
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al guardar cuenta bancaria')
  } finally {
    savingAccount.value = false
  }
}

const deleteAccount = async (cuenta) => {
  const result = await Swal.fire({
    title: 'Eliminar cuenta bancaria',
    text: `¿Estás seguro de que deseas eliminar la cuenta "${cuenta.alias || cuenta.nombre_banco}"? Esta acción no se puede deshacer.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  })

  if (!result.isConfirmed) {
    return
  }
  try {
    const res = await axios.delete(route('bancos.api.cuentas.destroy', cuenta.id))
    if (res.data.success) {
      notyf.success('Cuenta eliminada con éxito')
      cuentasBancarias.value = cuentasBancarias.value.filter(c => c.id !== cuenta.id)
      if (selectedCuentaFiltro.value?.id === cuenta.id) {
        selectedCuentaFiltro.value = null
      }
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al eliminar cuenta bancaria')
  }
}

const openNewMovModal = () => {
  isEditingMov.value = false
  editingMovId.value = null
  movForm.cuenta_bancaria_id = selectedCuentaFiltro.value ? selectedCuentaFiltro.value.id : (cuentasBancarias.value[0]?.id || '')
  movForm.fecha = new Date().toISOString().substring(0, 10)
  movForm.tipo = 'egreso'
  movForm.forma_pago_sat = '03'
  movForm.monto = 0
  movForm.concepto = ''
  movForm.referencia = ''
  movForm.beneficiario_rfc = ''
  movForm.beneficiario_nombre = ''
  movForm.cuenta_destino_id = ''
  movForm.cxc_id = null
  movForm.cxp_id = null
  showMovModal.value = true
}

const editMov = (m) => {
  isEditingMov.value = true
  editingMovId.value = m.id
  movForm.cuenta_bancaria_id = m.cuenta_bancaria_id
  movForm.fecha = m.fecha
  movForm.tipo = m.tipo
  movForm.monto = m.monto
  movForm.concepto = m.concepto
  movForm.referencia = m.referencia || ''
  movForm.forma_pago_sat = m.forma_pago_sat || '03'
  movForm.beneficiario_rfc = m.rfc_tercero || ''
  movForm.beneficiario_nombre = ''
  movForm.cuenta_destino_id = ''
  movForm.cxc_id = null
  movForm.cxp_id = null
  showMovModal.value = true
}

const deleteMov = async (m) => {
  const result = await Swal.fire({
    title: 'Eliminar movimiento bancario',
    text: '¿Estás seguro de eliminar este movimiento bancario y cancelar su póliza contable asociada?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  })

  if (!result.isConfirmed) return
  try {
    const res = await axios.delete(route('bancos.api.movimientos.destroy', m.id))
    if (res.data.success) {
      notyf.success('Movimiento y póliza cancelados exitosamente')
      movimientos.value = movimientos.value.filter(mov => mov.id !== m.id)
      const c = cuentasBancarias.value.find(c => c.id === m.cuenta_bancaria_id)
      if (c) {
        c.movimientos_count = Math.max(0, (c.movimientos_count || 1) - 1)
        const mNum = parseFloat(m.monto)
        if (m.tipo === 'ingreso') {
          c.saldo_actual = parseFloat(c.saldo_actual ?? c.saldo_inicial) - mNum
          c.saldo_inicial = parseFloat(c.saldo_inicial) - mNum
        } else {
          c.saldo_actual = parseFloat(c.saldo_actual ?? c.saldo_inicial) + mNum
          c.saldo_inicial = parseFloat(c.saldo_inicial) + mNum
        }
      }
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al eliminar movimiento')
  }
}

const submitMovement = async () => {
  savingMov.value = true
  try {
    if (isEditingMov.value) {
      const res = await axios.put(route('bancos.api.movimientos.update', editingMovId.value), movForm)
      if (res.data.success) {
        notyf.success('Movimiento bancario y póliza actualizados con éxito')
        const idx = movimientos.value.findIndex(m => m.id === editingMovId.value)
        if (idx !== -1) {
          movimientos.value[idx] = res.data.movimiento
        }
        showMovModal.value = false
      }
    } else {
      const res = await axios.post(route('bancos.api.movimientos.store'), movForm)
      if (res.data.success) {
        notyf.success('Movimiento bancario registrado y póliza generada')
        movimientos.value.unshift(res.data.movimiento)
        const c = cuentasBancarias.value.find(c => c.id === parseInt(movForm.cuenta_bancaria_id))
        if (c) {
          c.movimientos_count = (c.movimientos_count || 0) + 1
          const montoNum = parseFloat(movForm.monto)
          if (movForm.tipo === 'ingreso') {
            c.saldo_actual = parseFloat(c.saldo_actual ?? c.saldo_inicial) + montoNum
            c.saldo_inicial = parseFloat(c.saldo_inicial) + montoNum
          } else if (movForm.tipo === 'egreso') {
            c.saldo_actual = parseFloat(c.saldo_actual ?? c.saldo_inicial) - montoNum
            c.saldo_inicial = parseFloat(c.saldo_inicial) - montoNum
          } else if (movForm.tipo === 'traspaso') {
            c.saldo_actual = parseFloat(c.saldo_actual ?? c.saldo_inicial) - montoNum
            c.saldo_inicial = parseFloat(c.saldo_inicial) - montoNum
            const dest = cuentasBancarias.value.find(dest => dest.id === parseInt(movForm.cuenta_destino_id))
            if (dest) {
              dest.movimientos_count = (dest.movimientos_count || 0) + 1
              dest.saldo_actual = parseFloat(dest.saldo_actual ?? dest.saldo_inicial) + montoNum
              dest.saldo_inicial = parseFloat(dest.saldo_inicial) + montoNum
            }
          }
        }
        showMovModal.value = false
        movForm.forma_pago_sat = '03'
        movForm.monto = 0
        movForm.concepto = ''
        movForm.referencia = ''
        movForm.beneficiario_rfc = ''
        movForm.beneficiario_nombre = ''
        movForm.cuenta_destino_id = ''
        movForm.cxc_id = null
        movForm.cxp_id = null
      }
    }
  } catch (e) {
    notyf.error(e.response?.data?.message || 'Error al registrar/actualizar movimiento')
  } finally {
    savingMov.value = false
  }
}
</script>

<template>
  <Head title="Bancos y Tesorería" />

  <div class="p-6 w-full px-4 sm:px-6 lg:px-8 xl:px-12 space-y-8 text-slate-100 selection:bg-indigo-500/30">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-black tracking-tight text-white flex items-center gap-3">
          <span class="p-2.5 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </span>
          Bancos y Tesorería
        </h1>
        <p class="text-sm text-slate-400 mt-1">Administra tus saldos reales, flujos de efectivo y conciliación directa con pólizas.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <button type="button" @click="showFormalizarModal = true" class="px-5 py-3 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all font-black text-xs uppercase tracking-widest text-emerald-400 active:scale-95 flex items-center gap-2 shadow-lg cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
          Corte de Caja / Formalizar Cobranza
        </button>
        <button type="button" @click="showXmlRepModal = true" class="px-5 py-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20 transition-all font-black text-xs uppercase tracking-widest text-amber-400 active:scale-95 flex items-center gap-2 shadow-lg cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          Ver Administrador XML / REP
        </button>
        <button @click="openAddAccountModal" class="px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 transition-all font-black text-xs uppercase tracking-widest text-white shadow-lg shadow-indigo-600/20 active:scale-95 flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
          Nueva Cuenta
        </button>
        <button @click="openNewMovModal()" class="px-5 py-3 rounded-2xl bg-slate-800 border border-white/10 hover:bg-slate-700 transition-all font-black text-xs uppercase tracking-widest text-slate-300 active:scale-95 flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm-5-4h.01" /></svg>
          Registrar Movimiento
        </button>
      </div>
    </div>

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Total Cash -->
      <div class="p-6 rounded-[2rem] bg-slate-900 border border-white/5 shadow-2xl relative overflow-hidden group">
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>
        <div class="relative z-10 space-y-3">
          <p class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Total Consolidado</p>
          <h2 class="text-3xl font-black text-white">${{ totalConsolidado.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }} <span class="text-xs text-slate-500 font-bold">MXN</span></h2>
          <p class="text-xs text-slate-500 font-medium">Suma de saldos de todas las cuentas registradas.</p>
        </div>
      </div>

      <!-- Total Ingresos -->
      <div class="p-6 rounded-[2rem] bg-slate-900 border border-white/5 shadow-2xl relative overflow-hidden group">
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>
        <div class="relative z-10 space-y-3">
          <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Entradas del Mes</p>
          <h2 class="text-3xl font-black text-white">$0.00 <span class="text-xs text-slate-500 font-bold">MXN</span></h2>
          <p class="text-xs text-slate-500 font-medium">Registrado a través de ingresos bancarios.</p>
        </div>
      </div>

      <!-- Total Egresos -->
      <div class="p-6 rounded-[2rem] bg-slate-900 border border-white/5 shadow-2xl relative overflow-hidden group">
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-rose-500/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>
        <div class="relative z-10 space-y-3">
          <p class="text-[10px] font-black uppercase tracking-widest text-rose-400">Salidas del Mes</p>
          <h2 class="text-3xl font-black text-white">$0.00 <span class="text-xs text-slate-500 font-bold">MXN</span></h2>
          <p class="text-xs text-slate-500 font-medium">Registrado a través de egresos bancarios.</p>
        </div>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-white/5 pb-px">
      <button @click="activeTab = 'cuentas'" class="px-6 py-3 font-black text-xs uppercase tracking-widest transition-all relative" :class="activeTab === 'cuentas' ? 'text-indigo-400' : 'text-slate-500 hover:text-slate-300'">
        Cuentas Bancarias
        <span v-if="activeTab === 'cuentas'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-500 rounded-full"></span>
      </button>
      <button @click="activeTab = 'movimientos'; selectedCuentaFiltro = null" class="px-6 py-3 font-black text-xs uppercase tracking-widest transition-all relative" :class="activeTab === 'movimientos' ? 'text-indigo-400' : 'text-slate-500 hover:text-slate-300'">
        Historial de Movimientos
        <span v-if="activeTab === 'movimientos'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-500 rounded-full"></span>
      </button>
    </div>

    <!-- View: Accounts -->
    <div v-if="activeTab === 'cuentas'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div v-for="c in cuentasBancarias" :key="c.id" @click="verCuentaMovimientos(c)" class="p-6 rounded-[2rem] bg-slate-900 border border-white/5 hover:border-indigo-500/30 transition-all shadow-2xl relative group cursor-pointer">
        <!-- Floating Logo Icon -->
        <div class="absolute right-6 top-6 w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-black">
          <span v-if="c.tipo === 'tarjeta_credito'" class="text-xl" title="Tarjeta de Crédito">💳</span>
          <span v-else class="text-xl" title="Cuenta Bancaria">🏦</span>
        </div>

        <div class="space-y-6">
          <div>
            <h3 class="text-lg font-black text-white group-hover:text-indigo-400 transition-colors flex items-center gap-1.5">
              {{ c.alias || c.nombre_banco }}
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
              <span v-if="c.tipo === 'tarjeta_credito'" class="px-2 py-0.5 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-full text-[8px] font-black uppercase tracking-wider">Crédito</span>
              <span v-else class="px-2 py-0.5 bg-sky-500/10 border border-sky-500/20 text-sky-400 rounded-full text-[8px] font-black uppercase tracking-wider">Cuenta</span>
              <span class="text-[9px] font-mono text-slate-500 uppercase">{{ c.nombre_banco }} | {{ c.moneda }}</span>
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex justify-between text-xs font-medium">
              <span class="text-slate-500">Cuenta:</span>
              <span class="font-mono text-slate-300">{{ c.numero_cuenta || 'No especificada' }}</span>
            </div>
            <div class="flex justify-between text-xs font-medium">
              <span class="text-slate-500">CLABE:</span>
              <span class="font-mono text-slate-300">{{ c.clabe || 'No especificada' }}</span>
            </div>
            <div class="flex justify-between text-xs font-medium pt-2 border-t border-white/5 items-center">
              <span class="text-slate-500">Contabilidad:</span>
              <span v-if="c.es_fiscal && c.cuenta_contable" class="px-2 py-0.5 bg-slate-800 rounded text-[10px] font-mono text-slate-300 border border-white/5">{{ c.cuenta_contable.codigo }} - {{ c.cuenta_contable.nombre }}</span>
              <span v-else class="px-2.5 py-0.5 bg-emerald-500/10 text-emerald-400 rounded-full text-[9px] font-bold border border-emerald-500/20 uppercase tracking-wide">Caja Chica (Uso Interno)</span>
            </div>
          </div>

          <div class="pt-4 border-t border-white/5 flex items-center justify-between">
            <div>
              <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Saldo Disponible</p>
              <h4 class="text-2xl font-black text-white">${{ parseFloat(c.saldo_actual ?? c.saldo_inicial).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</h4>
            </div>
            <div class="flex items-center gap-1.5">
              <Link :href="route('conciliacion.index', { banco: c.nombre_banco })" @click.stop="" title="Conciliar Banco" class="p-2.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 hover:border-emerald-500/30 transition-all shadow-sm flex items-center justify-center cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
              </Link>
              <button @click.stop="openEditAccountModal(c)" title="Editar Cuenta" class="p-2.5 rounded-xl bg-white/5 hover:bg-indigo-500/20 text-slate-400 hover:text-indigo-300 border border-white/5 hover:border-indigo-500/30 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
              </button>
              <button v-if="c.movimientos_count === 0" @click.stop="deleteAccount(c)" title="Eliminar Cuenta (Sin Movimientos)" class="p-2.5 rounded-xl bg-white/5 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 border border-white/5 hover:border-rose-500/30 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Add Account Card -->
      <div @click="openAddAccountModal" class="p-6 rounded-[2rem] border-2 border-dashed border-white/10 hover:border-indigo-500/30 transition-all flex flex-col items-center justify-center gap-3 cursor-pointer group py-12">
        <span class="w-12 h-12 rounded-2xl bg-white/[0.02] border border-white/5 text-slate-400 group-hover:bg-indigo-500/10 group-hover:text-indigo-400 group-hover:border-indigo-500/20 transition-all flex items-center justify-center shadow-lg">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
        </span>
        <h4 class="text-sm font-black text-slate-400 group-hover:text-white transition-colors">Agregar Nueva Cuenta</h4>
        <p class="text-xs text-slate-500 text-center max-w-[200px]">Vincula una nueva cuenta de cheques, débito o crédito corporativo.</p>
      </div>
    </div>

    <!-- View: Movements -->
    <div v-else class="bg-slate-900 border border-white/5 rounded-[2.5rem] shadow-2xl overflow-hidden">
      <!-- Filtro Activo de Cuenta -->
      <div v-if="selectedCuentaFiltro" class="px-6 py-4 bg-slate-800/80 border-b border-white/5 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
          <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
          <span class="text-xs text-slate-400 font-medium tracking-wide">Mostrando movimientos de:</span>
          <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 font-black text-xs rounded-xl border border-indigo-500/30 flex items-center gap-2 shadow-sm">
            🏦 {{ selectedCuentaFiltro.alias || selectedCuentaFiltro.nombre_banco }}
            <span class="text-[10px] font-mono opacity-80">({{ selectedCuentaFiltro.numero_cuenta || 'S/N' }})</span>
          </span>
        </div>
        <button @click="selectedCuentaFiltro = null" class="px-3.5 py-1.5 bg-slate-700 hover:bg-slate-600 active:scale-95 text-xs font-bold text-white rounded-xl transition-all shadow-sm flex items-center gap-1.5 cursor-pointer">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          Ver Todas las Cuentas
        </button>
      </div>

      <!-- Pestañas cuando hay una cuenta seleccionada -->
      <div v-if="selectedCuentaFiltro" class="px-6 py-3 bg-slate-900/90 border-b border-white/5 flex items-center gap-3">
        <button @click="subTab = 'movimientos'" :class="subTab === 'movimientos' ? 'bg-indigo-500 text-white font-black shadow-lg shadow-indigo-500/20' : 'bg-slate-800 text-slate-400 hover:text-white font-semibold'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          Historial de Movimientos
        </button>
        <button @click="subTab = 'entregas'" :class="subTab === 'entregas' ? 'bg-indigo-500 text-white font-black shadow-lg shadow-indigo-500/20' : 'bg-slate-800 text-slate-400 hover:text-white font-semibold'" class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-2 cursor-pointer relative">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          Depósitos / Entregas Pendientes
          <span v-if="entregasPendientes.length > 0" class="absolute -top-1.5 -right-1.5 min-w-[20px] h-5 px-1 bg-rose-500 text-white font-black text-[10px] rounded-full flex items-center justify-center animate-bounce shadow-md">
            {{ entregasPendientes.length }}
          </span>
        </button>
      </div>

      <!-- View: Entregas Pendientes -->
      <div v-if="selectedCuentaFiltro && subTab === 'entregas'" class="p-6">
        <div v-if="loadingEntregas" class="py-20 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500/10 border-t-indigo-500"></div>
        </div>
        <div v-else-if="entregasPendientes.length === 0" class="py-20 text-center space-y-3 bg-slate-950/40 rounded-3xl border border-white/5 p-8">
          <div class="w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-3xl flex items-center justify-center mx-auto border border-emerald-500/20">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          </div>
          <h3 class="text-sm font-black text-slate-300">¡Todo al día en Tesorería!</h3>
          <p class="text-xs text-slate-500 max-w-[300px] mx-auto">No hay entregas de dinero ni depósitos pendientes de confirmar en este momento.</p>
        </div>
        <div v-else class="space-y-6">
          <div class="flex items-center justify-between pb-4 border-b border-white/5">
            <div>
              <h3 class="text-base font-black text-white">Entregas de Efectivo y Depósitos Pendientes</h3>
              <p class="text-xs text-slate-400">Revisa y acepta los depósitos para acreditarlos en <span class="text-indigo-400 font-bold">{{ selectedCuentaFiltro.alias || selectedCuentaFiltro.nombre_banco }}</span>.</p>
            </div>
            <button @click="fetchEntregasPendientes" title="Actualizar lista" class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-all cursor-pointer border border-white/5">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="entrega in entregasPendientes" :key="entrega.id" class="bg-slate-950/60 border border-white/10 rounded-3xl p-6 hover:border-indigo-500/30 transition-all flex flex-col justify-between shadow-xl">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="px-3 py-1 bg-amber-500/10 text-amber-400 rounded-xl text-[10px] font-black uppercase tracking-wider border border-amber-500/20">
                    Entrega #{{ entrega.id }} {{ entrega.es_lote ? '(Lote)' : '' }}
                  </span>
                  <span class="text-xs font-mono text-slate-400 font-medium">{{ entrega.fecha_entrega }}</span>
                </div>

                <div>
                  <h4 class="text-2xl font-black text-white">${{ entrega.total.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</h4>
                  <p class="text-xs text-slate-400 font-medium mt-1">Responsable: <span class="text-slate-200 font-bold">{{ entrega.usuario }}</span></p>
                </div>

                <div class="text-xs text-slate-400 bg-slate-900/80 p-4 rounded-2xl border border-white/5 space-y-1.5 font-medium">
                  <div v-if="entrega.monto_efectivo > 0" class="flex justify-between font-mono"><span class="text-slate-500">Efectivo:</span> <span>${{ entrega.monto_efectivo.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span></div>
                  <div v-if="entrega.monto_transferencia > 0" class="flex justify-between font-mono"><span class="text-slate-500">Transferencia:</span> <span>${{ entrega.monto_transferencia.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span></div>
                  <div v-if="entrega.monto_cheques > 0" class="flex justify-between font-mono"><span class="text-slate-500">Cheques:</span> <span>${{ entrega.monto_cheques.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span></div>
                  <div v-if="entrega.monto_tarjetas > 0" class="flex justify-between font-mono"><span class="text-slate-500">Tarjetas:</span> <span>${{ entrega.monto_tarjetas.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span></div>
                  <div v-if="entrega.notas" class="pt-2 mt-2 border-t border-white/5 text-[11px] italic text-slate-400 line-clamp-3">
                    "{{ entrega.notas }}"
                  </div>
                </div>
              </div>

              <div class="mt-6 pt-4 border-t border-white/5 flex items-center justify-end">
                <button @click="abrirModalAceptarEntrega(entrega)" class="px-5 py-3 bg-indigo-500 hover:bg-indigo-400 text-white font-black rounded-2xl text-xs uppercase tracking-widest transition-all shadow-lg shadow-indigo-500/20 active:scale-95 flex items-center gap-2 cursor-pointer">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                  Confirmar y Acreditar...
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- View: Historial de Movimientos -->
      <div v-show="!selectedCuentaFiltro || subTab === 'movimientos'">
        <div v-if="loadingMovs" class="py-20 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500/10 border-t-indigo-500"></div>
        </div>

        <div v-else-if="movimientosFiltradosBusqueda.length === 0" class="py-20 text-center space-y-3">
          <div class="w-16 h-16 bg-white/[0.02] border border-white/5 text-slate-500 rounded-3xl flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          <h3 class="text-sm font-black text-slate-400">Sin movimientos encontrados</h3>
          <p class="text-xs text-slate-500 max-w-[250px] mx-auto">No hay registros que coincidan con tus criterios de búsqueda o filtro.</p>
        </div>

        <template v-else>
          <!-- Búsqueda y Selector de Paginación Superior -->
          <div class="p-6 border-b border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950/30">
            <div class="relative w-full sm:max-w-md">
              <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </span>
              <input type="text" v-model="searchMovsQuery" placeholder="Buscar por concepto, monto ($), fecha, contraparte..." class="w-full pl-11 pr-10 py-2.5 bg-slate-900/80 border border-white/10 rounded-2xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500/50 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium">
              <button v-if="searchMovsQuery" @click="searchMovsQuery = ''" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-4 text-xs text-slate-400">
              <div class="flex items-center gap-2">
                <span>Mostrar:</span>
                <select v-model="itemsPerPageMovs" class="bg-slate-900 border border-white/10 text-white rounded-xl px-2.5 py-1.5 text-xs font-bold outline-none cursor-pointer focus:ring-2 focus:ring-indigo-500/30">
                  <option :value="10">10</option>
                  <option :value="15">15</option>
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                </select>
              </div>
              <span class="font-bold text-slate-500">|</span>
              <div>Total: <span class="font-black text-white">{{ movimientosFiltradosBusqueda.length }}</span> movs</div>
            </div>
          </div>

          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-white/5 text-[10px] font-black uppercase text-slate-500 tracking-wider bg-slate-950/20">
                <th class="px-6 py-4">Fecha</th>
                <th class="px-6 py-4">Concepto / Contraparte</th>
                <th class="px-6 py-4 text-right">Monto</th>
                <th class="px-6 py-4 text-center">Póliza / SAT</th>
                <th class="px-6 py-4 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-xs font-medium text-slate-300">
              <tr v-for="m in movimientosPaginados" :key="m.id" class="group transition-all hover:bg-white/[0.01]">
                <td class="px-6 py-4 font-mono text-slate-400 whitespace-nowrap">
                  {{ m.fecha }}
                </td>
                <td class="px-6 py-4 max-w-md">
                  <div class="font-bold text-white group-hover:text-indigo-300 transition-colors">{{ m.concepto }}</div>
                  <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-1">
                    <span v-if="m.referencia" class="font-mono">Ref: {{ m.referencia }}</span>
                    <span v-if="m.cuenta_bancaria" class="px-2 py-0.5 bg-white/5 rounded text-[10px] text-slate-400">🏦 {{ m.cuenta_bancaria.alias || m.cuenta_bancaria.nombre_banco }}</span>
                    <span v-if="m.forma_pago_sat" class="px-2 py-0.5 bg-indigo-500/10 text-indigo-400 rounded text-[10px]">SAT: {{ m.forma_pago_sat }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <span class="text-xs font-black" :class="m.tipo === 'ingreso' ? 'text-emerald-400' : 'text-rose-400'">
                    {{ m.tipo === 'ingreso' ? '+' : '-' }}${{ parseFloat(m.monto).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <button v-if="m.poliza" @click="verPoliza(m.poliza)" class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 hover:bg-indigo-500/20 rounded-full text-[9px] font-mono font-black uppercase tracking-wider cursor-pointer transition-colors">
                    🔒 Póliza #{{ m.poliza.tipo.substring(0, 1).toUpperCase() }}{{ String(m.poliza.numero).padStart(5, '0') }}
                  </button>
                  <span v-else class="inline-flex items-center px-2 py-0.5 bg-slate-800 rounded-full text-[9px] font-bold text-slate-500 border border-white/5 uppercase">
                    Sin vincular
                  </span>
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button @click="editMov(m)" title="Editar movimiento y recalcular póliza" class="p-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                    <button @click="deleteMov(m)" title="Eliminar movimiento y cancelar póliza" class="p-1.5 bg-slate-800 hover:bg-rose-600 text-slate-400 hover:text-white rounded-lg transition-colors cursor-pointer">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Paginación Inferior -->
          <div class="p-6 border-t border-white/5 bg-slate-950/30 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-xs text-slate-400">
              Mostrando <span class="font-bold text-white">{{ (currentPageMovs - 1) * itemsPerPageMovs + 1 }}</span> a <span class="font-bold text-white">{{ Math.min(currentPageMovs * itemsPerPageMovs, movimientosFiltradosBusqueda.length) }}</span> de <span class="font-bold text-white">{{ movimientosFiltradosBusqueda.length }}</span> movimientos
            </div>

            <div class="flex items-center gap-1.5 overflow-x-auto py-1">
              <button @click="currentPageMovs = Math.max(1, currentPageMovs - 1)" :disabled="currentPageMovs === 1" class="px-3.5 py-2 bg-slate-800 disabled:opacity-40 hover:bg-slate-700 text-white rounded-xl text-xs font-black transition-all active:scale-95 disabled:hover:bg-slate-800 disabled:active:scale-100 border border-white/5 shadow-sm">
                Anterior
              </button>
              <div class="flex items-center gap-1 px-2">
                <template v-for="(p, idx) in visiblePageNumbersMovs" :key="idx">
                  <span v-if="p === '...'" class="text-xs text-slate-500 font-bold px-1.5">...</span>
                  <button v-else @click="currentPageMovs = p" :class="currentPageMovs === p ? 'bg-indigo-600 text-white font-black' : 'bg-slate-800/50 text-slate-400 hover:text-white hover:bg-slate-800'" class="w-8 h-8 rounded-xl text-xs font-bold transition-all flex items-center justify-center border border-white/5 shadow-sm">
                    {{ p }}
                  </button>
                </template>
              </div>
              <button @click="currentPageMovs = Math.min(totalPagesMovs, currentPageMovs + 1)" :disabled="currentPageMovs === totalPagesMovs" class="px-3.5 py-2 bg-slate-800 disabled:opacity-40 hover:bg-slate-700 text-white rounded-xl text-xs font-black transition-all active:scale-95 disabled:hover:bg-slate-800 disabled:active:scale-100 border border-white/5 shadow-sm">
                Siguiente
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Modal: Cuenta Bancaria -->
    <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate__animated animate__fadeIn">
      <div class="w-full max-w-lg bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
          <h3 class="text-lg font-black text-white">{{ isEditingAccount ? 'Editar Cuenta Bancaria' : 'Nueva Cuenta Bancaria' }}</h3>
          <button @click="showAccountModal = false" class="text-slate-400 hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <form @submit.prevent="submitAccount" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Banco</label>
              <input type="text" v-model="accountForm.nombre_banco" required placeholder="Ej. BBVA" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Alias (Nombre Corto)</label>
              <input type="text" v-model="accountForm.alias" placeholder="Ej. Chequera Principal" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Número de Cuenta</label>
              <input type="text" v-model="accountForm.numero_cuenta" placeholder="Ej. 0123456789" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">CLABE Interbancaria</label>
              <input type="text" v-model="accountForm.clabe" placeholder="18 dígitos" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Moneda</label>
              <select v-model="accountForm.moneda" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                <option value="MXN">Pesos Mexicanos (MXN)</option>
                <option value="USD">Dólares Americanos (USD)</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Saldo Inicial</label>
              <input type="number" step="0.01" v-model="accountForm.saldo_inicial" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
            </div>
          </div>

          <div class="space-y-1">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tipo de Recurso</label>
            <select v-model="accountForm.tipo" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
              <option value="cuenta">🏦 Cuenta de Débito / Cheques</option>
              <option value="tarjeta_credito">💳 Tarjeta de Crédito Corporativa</option>
            </select>
          </div>

          <!-- Tipo de Cuenta (Fiscal o Caja Chica) -->
          <div class="space-y-3 bg-slate-950/40 p-4 rounded-2xl border border-white/5">
            <div class="flex items-center justify-between">
              <div>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">¿Esta cuenta entra a Contabilidad Fiscal?</label>
                <p class="text-[9px] text-slate-500 mt-0.5">Si se desactiva, se tratará como caja chica y no generará pólizas fiscales del SAT.</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="accountForm.es_fiscal" class="sr-only peer">
                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600 peer-checked:after:bg-white"></div>
              </label>
            </div>

            <div v-if="accountForm.es_fiscal" class="pt-3 border-t border-white/5 space-y-1 animate__animated animate__fadeIn">
              <label class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Vincular a Cuenta Contable (Libro Mayor)</label>
              <select v-model="accountForm.cuenta_contable_id" class="w-full px-4 py-2.5 bg-slate-900 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                <option :value="null">Autoseleccionar o asignar más tarde...</option>
                <option v-for="cc in cuentasContables" :key="cc.id" :value="cc.id">{{ cc.codigo }} - {{ cc.nombre }}</option>
              </select>
              <p class="text-[9px] text-slate-500">Asegura que los abonos y cargos afecten exactamente a esta subcuenta en la Balanza de Comprobación.</p>
            </div>
          </div>

          <div class="pt-4 border-t border-white/5 flex items-center justify-end gap-3">
            <button type="button" @click="showAccountModal = false" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 transition-all font-black text-[10px] uppercase tracking-widest text-slate-300">Cancelar</button>
            <button type="submit" :disabled="savingAccount" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 transition-all font-black text-[10px] uppercase tracking-widest text-white flex items-center gap-1">
              <svg v-if="savingAccount" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
              <span>{{ isEditingAccount ? 'Guardar Cambios' : 'Guardar Cuenta' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Nuevo Movimiento -->
    <div v-if="showMovModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate__animated animate__fadeIn">
      <div class="w-full max-w-4xl bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-5">
        
        <!-- Left: Form Block (ColSpan 3) -->
        <div class="md:col-span-3 p-6 border-b md:border-b-0 md:border-r border-white/5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-white/5 mb-4">
              <div class="flex items-center gap-3">
                <h3 class="text-lg font-black text-white">{{ isEditingMov ? 'Editar Movimiento Bancario' : 'Registrar Movimiento Bancario' }}</h3>
                <button type="button" @click="showXmlRepModal = true" class="hidden sm:inline-flex px-3 py-1 bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20 text-amber-400 rounded-xl text-[10px] font-black uppercase tracking-wider items-center gap-1.5 transition-all">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                  Ver XML y Pagos REP
                </button>
              </div>
              <button @click="showMovModal = false" class="text-slate-400 hover:text-white transition-colors md:hidden">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form @submit.prevent="submitMovement" class="space-y-4">
              <!-- Linked Invoice Indicator -->
              <div v-if="movForm.cxc_id || movForm.cxp_id" class="p-3.5 bg-emerald-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-between animate__animated animate__bounceIn">
                <span class="text-[10px] text-emerald-400 font-black flex items-center gap-1.5">
                  <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  Vinculado a factura: <span class="underline text-white font-black">{{ movForm.referencia }}</span>
                </span>
                <button type="button" @click="clearLink" class="text-[9px] font-black text-rose-400 hover:text-rose-300 uppercase tracking-widest transition-colors">Desvincular</button>
              </div>

              <div class="space-y-1">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cuenta Bancaria de Origen / Destino</label>
                <select v-model="movForm.cuenta_bancaria_id" required class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                  <option value="" disabled>Seleccione una cuenta...</option>
                  <option v-for="c in cuentasBancarias" :key="c.id" :value="c.id">{{ c.alias || c.nombre_banco }} ({{ c.nombre_banco }} - Saldo: ${{ parseFloat(c.saldo_inicial).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})</option>
                </select>
              </div>

              <div class="grid grid-cols-3 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Fecha</label>
                  <input type="date" v-model="movForm.fecha" required class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Tipo</label>
                  <select v-model="movForm.tipo" required class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                    <option value="egreso">Egreso (Salida)</option>
                    <option value="ingreso">Ingreso (Entrada)</option>
                    <option value="traspaso">Traspaso entre Cuentas</option>
                  </select>
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Monto</label>
                  <input type="number" step="0.01" v-model="movForm.monto" required min="0.01" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
              </div>

              <div v-if="movForm.tipo === 'traspaso'" class="space-y-1">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Cuenta Destino</label>
                <select v-model="movForm.cuenta_destino_id" required class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                  <option value="" disabled>Selecciona la cuenta destino</option>
                  <option v-for="c in cuentasBancarias.filter(acc => acc.id !== parseInt(movForm.cuenta_bancaria_id))" :key="c.id" :value="c.id">
                    {{ c.alias || c.nombre_banco }} ({{ c.nombre_banco }} - Saldo: ${{ parseFloat(c.saldo_inicial).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }})
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Concepto / Descripción</label>
                  <input type="text" v-model="movForm.concepto" required placeholder="Ej. Pago de Renta" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Referencia / Folio</label>
                  <input type="text" v-model="movForm.referencia" placeholder="Ej. REF10023" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
              </div>

              <div class="space-y-1">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Forma de Pago (SAT)</label>
                <select v-model="movForm.forma_pago_sat" required class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                  <option value="01">01 - Efectivo</option>
                  <option value="02">02 - Cheque nominativo</option>
                  <option value="03">03 - Transferencia electrónica de fondos</option>
                  <option value="04">04 - Tarjeta de crédito</option>
                  <option value="28">28 - Tarjeta de débito</option>
                  <option value="99">99 - Por definir</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">RFC Beneficiario / Ordenante</label>
                  <input type="text" v-model="movForm.beneficiario_rfc" placeholder="Opcional" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
                <div class="space-y-1">
                  <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">Nombre Beneficiario</label>
                  <input type="text" v-model="movForm.beneficiario_nombre" placeholder="Opcional" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                </div>
              </div>

              <div class="p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10 text-[10px] text-slate-400 font-medium leading-relaxed">
                💡 Al registrar este movimiento, el sistema **generará automáticamente la Póliza Contable de {{ movForm.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}** y conciliará al instante la deuda contra la CxC/CxP seleccionada.
              </div>

              <div class="pt-4 border-t border-white/5 flex items-center justify-end gap-3">
                <button type="button" @click="showMovModal = false" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 transition-all font-black text-[10px] uppercase tracking-widest text-slate-300">Cancelar</button>
                <button type="submit" :disabled="savingMov" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 transition-all font-black text-[10px] uppercase tracking-widest text-white flex items-center gap-1">
                  <svg v-if="savingMov" class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4"/></svg>
                  <span>{{ isEditingMov ? 'Guardar Cambios y Póliza' : 'Registrar y Generar Póliza' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right: Autofill/Search Block (ColSpan 2) -->
        <div class="md:col-span-2 p-6 bg-slate-950/40 flex flex-col justify-between border-t md:border-t-0 md:border-l border-white/5">
          <div class="flex-1 flex flex-col min-h-0">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-xs font-black uppercase tracking-widest text-indigo-400 flex items-center gap-1.5">
                <span class="p-1 rounded bg-indigo-500/10 text-indigo-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                {{ movForm.tipo === 'ingreso' ? 'Buscar Clientes / CxC' : 'Buscar Proveedores / CxP' }}
              </h4>
              <button @click="showMovModal = false" class="text-slate-400 hover:text-white transition-colors hidden md:block">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <!-- Search input -->
            <input type="text" v-model="searchQuery" :placeholder="movForm.tipo === 'ingreso' ? 'Buscar por nombre, RFC o folio...' : 'Buscar por nombre, RFC o folio...'" class="w-full px-4 py-2.5 bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-white focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none mb-4">

            <!-- Tabs -->
            <div class="flex gap-2 p-1 bg-slate-950 rounded-xl border border-white/5 mb-4">
              <button type="button" @click="searchTab = 'pendientes'" :class="[searchTab === 'pendientes' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-white']" class="flex-1 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                Facturas Pendientes
              </button>
              <button type="button" @click="searchTab = 'todos'" :class="[searchTab === 'todos' ? 'bg-indigo-600 text-white shadow-lg' : 'text-slate-400 hover:text-white']" class="flex-1 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                {{ movForm.tipo === 'ingreso' ? 'Clientes' : 'Proveedores' }}
              </button>
            </div>

            <!-- Scrollable list -->
            <div class="overflow-y-auto max-h-[360px] space-y-2 pr-1 custom-scrollbar">
              
              <!-- Tab: Pendientes (CxC / CxP) -->
              <template v-if="searchTab === 'pendientes'">
                <div v-if="filteredAccounts.length === 0" class="text-center py-8 text-slate-500 text-xs font-medium leading-relaxed">
                  💡 No hay facturas pendientes que coincidan con la búsqueda.
                </div>
                <button type="button" v-for="item in filteredAccounts" :key="item.id" @click="selectAccount(item)" class="w-full text-left p-3.5 rounded-2xl bg-slate-900/60 hover:bg-indigo-600/10 border border-white/5 hover:border-indigo-500/30 transition-all group flex flex-col gap-1.5">
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[11px] font-black text-white group-hover:text-indigo-400 transition-colors">
                      {{ item.cobrable?.numero_venta || item.compra?.numero_compra || item.referencia || `ID #${item.id}` }}
                    </span>
                    <span class="text-[11px] font-black text-emerald-400">
                      ${{ parseFloat(item.monto_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                    </span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-medium truncate">
                    {{ item.cliente?.nombre_razon_social || item.proveedor?.nombre_razon_social || item.compra?.proveedor?.nombre_razon_social || 'Entidad Desconocida' }}
                  </div>
                  <div class="flex justify-between items-center text-[9px] text-slate-500">
                    <span>Vence: {{ new Date(item.fecha_vencimiento).toLocaleDateString('es-MX') }}</span>
                    <span class="px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400 font-bold uppercase tracking-wider text-[8px]">{{ item.estado }}</span>
                  </div>
                </button>
              </template>

              <!-- Tab: Todos (Clientes / Proveedores) -->
              <template v-else>
                <div v-if="filteredEntities.length === 0" class="text-center py-8 text-slate-500 text-xs font-medium leading-relaxed">
                  💡 No se encontraron registros.
                </div>
                <button type="button" v-for="entity in filteredEntities" :key="entity.id" @click="selectEntity(entity)" class="w-full text-left p-3.5 rounded-2xl bg-slate-900/60 hover:bg-indigo-600/10 border border-white/5 hover:border-indigo-500/30 transition-all group flex flex-col gap-1">
                  <div class="flex justify-between items-center w-full">
                    <span class="text-[11px] font-black text-white group-hover:text-indigo-400 transition-colors">
                      {{ entity.nombre_razon_social }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center text-[9px] text-slate-400 font-mono">
                    <span>RFC: {{ entity.rfc || 'SIN RFC' }}</span>
                  </div>
                </button>
              </template>

            </div>
          </div>

          <!-- Quick Tip -->
          <div class="mt-4 pt-4 border-t border-white/5 text-[9px] text-slate-500 leading-relaxed">
            💡 Haz clic en una factura o cliente para auto-completar instantáneamente el monto, concepto, referencia y RFC en el formulario principal.
          </div>
        </div>

      </div>
    </div>

    <!-- Administrador XML / REP Modal -->
    <AdministradorXmlRepModal
      :show="showXmlRepModal"
      @close="showXmlRepModal = false"
      @select="handleSelectFromXmlRepModal"
    />

    <!-- Modal de Formalización de Cobranza (Corte de Caja) -->
    <FormalizarCobranzaModal
      v-model:show="showFormalizarModal"
      :cuentas="cuentasBancarias"
      :cuenta-seleccionada="selectedCuentaFiltro"
      @success="onFormalizarSuccess"
    />

    <!-- Modal de Confirmación de Depósito (Tesorería) -->
    <AceptarEntregaModal
      v-model:show="showAceptarModal"
      :entrega="selectedEntregaToAccept"
      :cuentas="cuentasBancarias"
      :cuenta-seleccionada="selectedCuentaFiltro"
      @success="onAceptarEntregaSuccess"
    />

    <!-- Modal: Ver Póliza -->
    <div v-if="showPolizaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.self="showPolizaModal = false">
      <div class="w-full max-w-2xl bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="p-6 border-b border-white/5 flex items-center justify-between">
          <h3 class="text-lg font-black text-white">Póliza #{{ selectedPoliza?.tipo?.substring(0,1).toUpperCase() }}{{ String(selectedPoliza?.numero || '').padStart(5, '0') }}</h3>
          <button @click="showPolizaModal = false" class="text-slate-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="p-6 space-y-4">
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div class="bg-slate-800/50 p-3 rounded-xl"><p class="text-[10px] uppercase tracking-wider text-slate-400">Tipo</p><p class="font-black text-white">{{ selectedPoliza?.tipo?.toUpperCase() }}</p></div>
            <div class="bg-slate-800/50 p-3 rounded-xl"><p class="text-[10px] uppercase tracking-wider text-slate-400">Fecha</p><p class="font-black text-white">{{ selectedPoliza?.fecha }}</p></div>
            <div class="bg-slate-800/50 p-3 rounded-xl"><p class="text-[10px] uppercase tracking-wider text-slate-400">Total</p><p class="font-black text-white">\${{ parseFloat(selectedPoliza?.total || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p></div>
          </div>
          <div class="bg-slate-800/50 p-4 rounded-xl">
            <p class="text-[10px] uppercase tracking-wider text-slate-400 mb-1">Concepto</p>
            <p class="text-sm font-bold text-slate-200">{{ selectedPoliza?.concepto }}</p>
          </div>
          <div>
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-3">Asientos Contables</h4>
            <table class="w-full border-collapse">
              <thead><tr class="bg-white/[0.02] border-b border-white/5">
                <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase text-left">Cuenta</th>
                <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase text-right">Debe</th>
                <th class="px-4 py-3 text-[10px] font-black text-slate-500 uppercase text-right">Haber</th>
              </tr></thead>
              <tbody class="divide-y divide-white/5">
                <tr v-for="(a, i) in (selectedPoliza?.asientos || [])" :key="i">
                  <td class="px-4 py-3 text-xs text-slate-300"><span class="font-mono text-indigo-400">{{ a.cuenta?.codigo }}</span> <span class="text-slate-500">{{ a.cuenta?.nombre }}</span></td>
                  <td class="px-4 py-3 text-xs text-right font-mono text-emerald-400">{{ a.debe > 0 ? '\$' + parseFloat(a.debe).toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—' }}</td>
                  <td class="px-4 py-3 text-xs text-right font-mono text-rose-400">{{ a.haber > 0 ? '\$' + parseFloat(a.haber).toLocaleString('es-MX', {minimumFractionDigits: 2}) : '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="p-6 border-t border-white/5 flex justify-end">
          <button @click="showPolizaModal = false" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 font-black text-[10px] uppercase tracking-widest text-slate-300">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>
