<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import axios from 'axios'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const props = defineProps({
  catalog: { type: Array, default: () => [] }
})

const searchTerm = ref('')
const showAuditModal = ref(false)
const problematicPolicies = ref([])
const loadingAudit = ref(false)

// History Modal
const showHistoryModal = ref(false)
const selectedAccount = ref(null)
const history = ref([])
const loadingHistory = ref(false)

// Create Account Modal
const showCreateModal = ref(false)
const createForm = useForm({
    codigo: '',
    nombre: '',
    padre_id: null,
    es_detalle: true,
    sat_codigo: '',
    tipo: 'activo',
    naturaleza: 'deudora'
})

// Cascading Selects para Cuenta Padre
const selectedNivel1 = ref(null)
const selectedNivel2 = ref(null)
const selectedNivel3 = ref(null)
const selectedNivel4 = ref(null)
const selectedNivel5 = ref(null)

const rootAccounts = computed(() => props.catalog.filter(c => c.nivel === 1 && !c.es_detalle))
const level2Accounts = computed(() => props.catalog.filter(c => c.padre_id === selectedNivel1.value && !c.es_detalle))
const level3Accounts = computed(() => props.catalog.filter(c => c.padre_id === selectedNivel2.value && !c.es_detalle))
const level4Accounts = computed(() => props.catalog.filter(c => c.padre_id === selectedNivel3.value && !c.es_detalle))
const level5Accounts = computed(() => props.catalog.filter(c => c.padre_id === selectedNivel4.value && !c.es_detalle))

// Computed para mostrar las cuentas que ya existen en el nivel actual
const existingSiblings = computed(() => {
    if (!createForm.padre_id) return rootAccounts.value
    return props.catalog.filter(c => c.padre_id === createForm.padre_id)
})

const getAccountBy = (id) => props.catalog.find(c => c.id === id) || {}

watch(selectedNivel1, (val) => {
    selectedNivel2.value = null
    createForm.padre_id = val
})

watch(selectedNivel2, (val) => {
    selectedNivel3.value = null
    createForm.padre_id = val || selectedNivel1.value
})

watch(selectedNivel3, (val) => {
    selectedNivel4.value = null
    createForm.padre_id = val || selectedNivel2.value
})

watch(selectedNivel4, (val) => {
    selectedNivel5.value = null
    createForm.padre_id = val || selectedNivel3.value
})

watch(selectedNivel5, (val) => {
    createForm.padre_id = val || selectedNivel4.value
})

const calculateSuggestedCode = (parent, isDetail) => {
    // Buscar todos los hijos del mismo padre, sin importar si son detalle o no
    const children = props.catalog.filter(c => c.padre_id === parent.id)
    
    if (children.length === 0) {
        return `${parent.codigo}-001`
    }
    
    // Buscar el último código usado
    const lastCode = children.map(c => c.codigo).sort().pop()
    const lastSeparatorIndex = Math.max(lastCode.lastIndexOf('-'), lastCode.lastIndexOf('.'))
    
    if (lastSeparatorIndex !== -1) {
        const prefix = lastCode.substring(0, lastSeparatorIndex)
        const suffix = lastCode.substring(lastSeparatorIndex + 1)
        
        if (!isNaN(suffix) && suffix.length > 0) {
            const nextNum = (parseInt(suffix) + 1).toString().padStart(Math.max(suffix.length, 3), '0')
            // Forzamos el uso del guion
            return prefix + '-' + nextNum
        }
    }
    
    return `${parent.codigo}-001`
}

watch(() => createForm.padre_id, (newParentId) => {
    if (!newParentId) return
    const parent = props.catalog.find(c => c.id === newParentId)
    if (!parent) return

    // 1. Heredar SAT
    createForm.sat_codigo = parent.sat_codigo || ''

    // 2. Adivinar intención inicial (Detalle vs Agrupación)
    createForm.es_detalle = parent.nivel >= 2

    // 3. Calcular código sugerido
    createForm.codigo = calculateSuggestedCode(parent, createForm.es_detalle)
})

watch(() => createForm.es_detalle, (newIsDetail) => {
    if (!createForm.padre_id) return
    const parent = props.catalog.find(c => c.id === createForm.padre_id)
    if (parent) {
        createForm.codigo = calculateSuggestedCode(parent, newIsDetail)
    }
})

const submitCreate = () => {
    createForm.post(route('contabilidad.cuentas.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.reset()
        },
        preserveScroll: true
    })
}

const deleteAccount = async (account) => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Estás seguro de eliminar la cuenta ${account.codigo} - ${account.nombre}? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    })
    if (result.isConfirmed) {
        router.delete(route('contabilidad.cuentas.destroy', account.id), {
            preserveScroll: true,
            onError: (err) => {
                Swal.fire({
                    title: 'Error',
                    text: err.cuenta || 'No se pudo eliminar la cuenta.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                })
            }
        })
    }
}

const filteredCatalog = computed(() => {
    if (!searchTerm.value) return props.catalog
    const s = searchTerm.value.toLowerCase()
    return props.catalog.filter(c => 
        c.codigo.toLowerCase().includes(s) || 
        c.nombre.toLowerCase().includes(s)
    )
})

const totals = computed(() => {
  return props.catalog.reduce((acc, c) => {
    if (c.nivel === 1) {
      acc.debe += (c.debe || 0)
      acc.haber += (c.haber || 0)
      acc.saldo += (c.saldo || 0)
    }
    return acc
  }, { debe: 0, haber: 0, saldo: 0 })
})

const getTypeColor = (tipo) => {
  const colors = { activo: 'bg-emerald-100 text-emerald-700', pasivo: 'bg-rose-100 text-rose-700', capital: 'bg-sky-100 text-sky-700', ingreso: 'bg-indigo-100 text-indigo-700', egreso: 'bg-brand-100 text-amber-700' }
  return colors[tipo] || 'bg-slate-100 text-slate-700'
}

const openAudit = async () => {
  if (Math.abs(totals.value.debe - totals.value.haber) < 0.01) return
  
  showAuditModal.value = true
  loadingAudit.value = true
  try {
    const res = await axios.get(route('contabilidad.api.audit-balance'))
    problematicPolicies.value = res.data.polizas
  } catch (e) {
    console.error(e)
  } finally {
    loadingAudit.value = false
  }
}

const openHistory = async (account) => {
    if (!account.es_detalle && account.nivel > 1) return
    
    selectedAccount.value = account
    showHistoryModal.value = true
    loadingHistory.value = true
    history.value = []
    
    try {
        const res = await axios.get(route('contabilidad.api.cuenta-detalle', account.id))
        history.value = res.data.asientos
    } catch (e) {
        console.error(e)
    } finally {
        loadingHistory.value = false
    }
}

const printCatalog = () => {
    window.open(route('contabilidad.catalog.pdf'), '_blank')
}
</script>

<template>
  <Head title="Catálogo de Cuentas" />

  <div class="py-6 px-4 sm:px-6">
    <CrudPageHeader title="Catálogo de Cuentas" subtitle="Estructura contable y clasificación SAT">
      <template #actions>
        <div class="flex items-center gap-3">
          <input v-model="searchTerm" type="text" placeholder="Buscar cuenta o código..."
            class="w-64 px-4 py-2.5 text-sm border border-slate-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition-all" />
          
          <button @click="showCreateModal = true" 
            class="inline-flex items-center px-4 py-2.5 bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-brand-900/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Nueva Cuenta
          </button>

          <button @click="printCatalog" 
            class="inline-flex items-center px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-rose-900/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir Catálogo
          </button>

          <Link :href="route('contabilidad.index')" 
            class="inline-flex items-center px-4 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all shadow-sm">
            Volver a Pólizas
          </Link>
        </div>
      </template>
    </CrudPageHeader>

    <div class="mt-6 bg-white dark:bg-slate-800 rounded-3xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 text-xs uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4 font-bold">Código</th>
            <th class="px-6 py-4 font-bold">Nombre de la Cuenta</th>
            <th class="px-6 py-4 font-bold">Tipo</th>
            <th class="px-6 py-4 font-bold text-right">Debe</th>
            <th class="px-6 py-4 font-bold text-right">Haber</th>
            <th class="px-6 py-4 font-bold text-right">Saldo Actual</th>
            <th class="px-6 py-4 font-bold text-center">Acciones</th>
          </tr>
</thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
          <tr v-for="c in filteredCatalog" :key="c.id" 
            @click="openHistory(c)"
            class="hover:bg-brand-50/50 dark:hover:bg-brand-900/10 cursor-pointer transition-colors group">
            <td class="px-6 py-4 font-mono text-sm font-bold text-brand-600 dark:text-brand-400 group-hover:underline">{{ c.codigo }}</td>
            <td class="px-6 py-4">
              <div :style="{ paddingLeft: (c.nivel - 1) * 20 + 'px' }" class="flex items-center gap-2 text-slate-700 dark:text-slate-200">
                <div v-if="c.nivel > 1" class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                <span :class="{'font-bold': c.nivel === 1}">{{ c.nombre }}</span>
                <span v-if="c.es_detalle" class="ml-auto opacity-0 group-hover:opacity-100 text-[10px] bg-brand-100 text-brand-700 px-1.5 py-0.5 rounded font-black tracking-tighter transition-opacity">VER AUXILIAR</span>
              </div>
            </td>
            <td class="px-6 py-4 text-xs font-bold uppercase">
              <span :class="getTypeColor(c.tipo)" class="px-2 py-1 rounded-md">
                {{ c.tipo }}
              </span>
            </td>
            <td class="px-6 py-4 text-right font-mono text-xs text-slate-500">${{ Number(c.debe || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
            <td class="px-6 py-4 text-right font-mono text-xs text-slate-500">${{ Number(c.haber || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
            <td class="px-6 py-4 text-right font-mono text-sm font-bold" :class="c.saldo >= 0 ? 'text-emerald-600' : 'text-rose-600'">
              ${{ Number(c.saldo || 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
            </td>
            <td class="px-6 py-4 text-center">
                <button v-if="!c.debe && !c.haber" @click.stop="deleteAccount(c)" 
                    class="p-2 text-slate-300 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-all"
                    title="Eliminar cuenta (Solo si no tiene movimientos)">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
          </tr>
        </tbody>
        <tfoot class="bg-slate-50/80 dark:bg-slate-900 font-bold border-t-2 border-slate-200 dark:border-slate-700">
          <tr>
            <td colspan="3" class="px-6 py-5 text-xs uppercase tracking-widest text-slate-500 text-right">Totales del Catálogo</td>
            <td class="px-6 py-5 text-right font-mono text-sm text-slate-900 dark:text-slate-100">${{ totals.debe.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
            <td class="px-6 py-5 text-right font-mono text-sm text-slate-900 dark:text-slate-100">${{ totals.haber.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
            <td class="px-6 py-5 text-right font-mono text-sm" :class="totals.saldo >= 0 ? 'text-emerald-600' : 'text-rose-600'">
              ${{ totals.saldo.toLocaleString(undefined, {minimumFractionDigits: 2}) }}
            </td>
          </tr>
          <!-- Fila de Diferencia (Descuadre) -->
          <tr @click="openAudit" class="bg-slate-100/50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-700 cursor-pointer group">
            <td colspan="3" class="px-6 py-4 text-xs uppercase tracking-widest text-slate-400 text-right group-hover:text-slate-600 transition-colors">Diferencia entre Debe y Haber</td>
            <td colspan="2" class="px-6 py-4 text-right font-mono text-sm" :class="Math.abs(totals.debe - totals.haber) > 0.01 ? 'text-rose-600 underline decoration-double font-black' : 'text-emerald-600'">
              ${{ Math.abs(totals.debe - totals.haber).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
              <span v-if="Math.abs(totals.debe - totals.haber) <= 0.01" class="ml-2 text-[10px] font-black uppercase">(Balance Cuadrado)</span>
              <template v-else>
                <span class="ml-2 text-[10px] font-black uppercase">(Descuadre detectado)</span>
                <span class="ml-2 px-2 py-0.5 bg-rose-600 text-white text-[9px] rounded-full group-hover:scale-110 transition-transform inline-block">AUDITAR</span>
              </template>
            </td>
            <td></td>
          </tr>
        </tfoot>
      </table>
      
      <div v-if="filteredCatalog.length === 0" class="p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-200">No se encontraron cuentas</h3>
        <p class="mt-1 text-sm text-slate-500">Intenta con otro término de búsqueda.</p>
      </div>
    </div>
  </div>

  <!-- Modal de Historial / Auxiliar -->
  <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-5xl overflow-hidden border border-slate-100 dark:border-slate-700 animate-in fade-in zoom-in duration-300">
      <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-start bg-slate-50/50 dark:bg-slate-900/50">
        <div>
          <h3 class="text-2xl font-black text-slate-900 dark:text-slate-100">{{ selectedAccount?.nombre }}</h3>
          <div class="flex items-center gap-3 mt-1">
            <span class="text-xs font-mono font-bold text-brand-600 bg-brand-50 dark:bg-brand-900/20 px-2 py-0.5 rounded">{{ selectedAccount?.codigo }}</span>
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Libro Auxiliar / Historial de Transacciones</span>
          </div>
        </div>
        <button @click="showHistoryModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-700 rounded-full transition-all shadow-sm">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <div class="p-0 max-h-[75vh] overflow-y-auto">
        <div v-if="loadingHistory" class="py-24 flex flex-col items-center justify-center text-slate-400">
          <div class="w-12 h-12 border-4 border-brand-500 border-t-transparent rounded-full animate-spin mb-6"></div>
          <span class="text-xs font-black uppercase tracking-[0.3em] animate-pulse">Cargando Movimientos...</span>
        </div>
        
        <div v-else-if="history.length === 0" class="py-24 text-center">
            <div class="text-slate-200 dark:text-slate-700 mb-6">
                <svg class="w-20 h-20 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293H6.414a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 002.586 13H2" /></svg>
            </div>
            <h4 class="text-xl font-bold text-slate-900 dark:text-slate-100">Sin movimientos registrados</h4>
            <p class="text-sm text-slate-500 mt-2">Esta cuenta no tiene pólizas o asientos asociados en el sistema.</p>
        </div>

        <div v-else>
          <div class="sticky top-0 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm z-10 grid grid-cols-12 gap-4 px-8 py-4 border-b border-slate-100 dark:border-slate-700 text-[10px] font-black uppercase tracking-widest text-slate-400">
            <div class="col-span-1">Fecha</div>
            <div class="col-span-1">Póliza</div>
            <div class="col-span-3">Concepto / Referencia</div>
            <div class="col-span-3">Facturas / CFDIs</div>
            <div class="col-span-2 text-right">Debe</div>
            <div class="col-span-2 text-right">Haber</div>
          </div>

          <div class="divide-y divide-slate-50 dark:divide-slate-700/50">
            <div v-for="a in history" :key="a.id" class="grid grid-cols-12 gap-4 px-8 py-5 hover:bg-slate-50/50 dark:hover:bg-slate-900/30 transition-colors items-center">
              <div class="col-span-1 text-xs font-bold text-slate-500">{{ a.fecha }}</div>
              <div class="col-span-1">
                <Link :href="route('contabilidad.show', a.poliza_id)" class="px-2 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-black rounded hover:bg-brand-100 hover:text-brand-700 transition-colors">
                    {{ a.poliza_numero }}
                </Link>
              </div>
              <div class="col-span-3">
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ a.concepto }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ a.referencia }}</p>
              </div>
              <div class="col-span-3 flex flex-wrap gap-1">
                <div v-for="cfdi in a.cfdis" :key="cfdi.uuid" class="group/cfdi relative">
                    <Link :href="route('cfdi.ver-pdf-view', cfdi.uuid)" class="flex items-center gap-1.5 px-2 py-1 bg-brand-50 dark:bg-brand-900/10 border border-brand-100 dark:border-brand-900/30 rounded-lg hover:border-brand-300 transition-all">
                        <span class="text-[9px] font-black text-amber-600">{{ cfdi.tipo }}</span>
                        <span class="text-[10px] font-bold text-brand-700 dark:text-amber-400">{{ cfdi.folio_completo }}</span>
                    </Link>
                    <!-- Tooltip info -->
                    <div class="absolute bottom-full left-0 mb-2 hidden group-hover/cfdi:block z-20 w-48 p-2 bg-slate-900 text-white text-[9px] rounded-xl shadow-xl">
                        <p class="font-bold border-b border-slate-700 pb-1 mb-1">{{ cfdi.emisor }}</p>
                        <p class="opacity-70">Total: ${{ cfdi.total.toLocaleString() }}</p>
                    </div>
                </div>
              </div>
              <div class="col-span-2 text-right font-mono text-sm font-bold text-slate-700 dark:text-slate-300">
                {{ a.debe > 0 ? '$' + a.debe.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-' }}
              </div>
              <div class="col-span-2 text-right font-mono text-sm font-bold text-slate-700 dark:text-slate-300">
                {{ a.haber > 0 ? '$' + a.haber.toLocaleString(undefined, {minimumFractionDigits: 2}) : '-' }}
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="px-8 py-6 bg-slate-50/80 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-700 flex justify-between items-center">
        <div class="flex gap-8">
            <div class="flex flex-col">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Debe</span>
                <span class="text-xl font-mono font-black text-slate-900 dark:text-slate-100">${{ history.reduce((acc, a) => acc + a.debe, 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
            </div>
            <div class="flex flex-col border-l border-slate-200 dark:border-slate-700 pl-8">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Haber</span>
                <span class="text-xl font-mono font-black text-slate-900 dark:text-slate-100">${{ history.reduce((acc, a) => acc + a.haber, 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
            </div>
            <div class="flex flex-col border-l border-slate-200 dark:border-slate-700 pl-8">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Saldo del Período</span>
                <span class="text-xl font-mono font-black" :class="history.reduce((acc, a) => acc + (a.debe - a.haber), 0) >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                    ${{ Math.abs(history.reduce((acc, a) => acc + (a.debe - a.haber), 0)).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                </span>
            </div>
        </div>
        <button @click="showHistoryModal = false" class="px-8 py-3 bg-slate-900 dark:bg-slate-700 text-white text-sm font-bold rounded-2xl hover:bg-slate-800 transition-all shadow-lg hover:shadow-xl active:scale-95">
          Cerrar Auxiliar
        </button>
      </div>
    </div>
  </div>

  <!-- Modal de Auditoría -->
  <div v-if="showAuditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-100 dark:border-slate-700 animate-in fade-in zoom-in duration-200">
      <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50/50 dark:bg-slate-900/50">
        <div>
          <h3 class="text-lg font-black text-slate-900 dark:text-slate-100">Auditoría de Descuadre</h3>
          <p class="text-xs text-slate-500 uppercase tracking-widest">Identificando pólizas fuera de balance</p>
        </div>
        <button @click="showAuditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      
      <div class="p-6 max-h-[60vh] overflow-y-auto">
        <div v-if="loadingAudit" class="py-12 flex flex-col items-center justify-center text-slate-400">
          <div class="w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full animate-spin mb-4"></div>
          <span class="text-xs font-bold uppercase tracking-widest">Analizando pólizas...</span>
        </div>
        
        <div v-else-if="problematicPolicies.length === 0" class="py-12 text-center">
            <div class="text-emerald-500 mb-4">
                <svg class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h4 class="font-bold text-slate-900 dark:text-slate-100">¡Todo parece estar en orden!</h4>
            <p class="text-sm text-slate-500">No se encontraron pólizas con errores de balance individuales.</p>
        </div>

        <div v-else class="space-y-4">
          <div class="p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 rounded-2xl mb-6 flex justify-between items-center">
            <div>
              <p class="text-xs text-rose-700 dark:text-rose-400 font-bold uppercase tracking-wider mb-1">Resumen de Auditoría</p>
              <p class="text-sm text-rose-600 dark:text-rose-300">Se han detectado {{ problematicPolicies.length }} pólizas que no están cuadradas.</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-rose-700 dark:text-rose-400 font-bold uppercase tracking-wider mb-1">Impacto Neto (Catalog)</p>
              <p class="text-lg font-black text-rose-600 dark:text-rose-300 font-mono">
                ${{ Math.abs(problematicPolicies.reduce((acc, p) => acc + (p.debe_haber_diff || 0), 0)).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
              </p>
            </div>
          </div>

          <div v-for="p in problematicPolicies" :key="p.id" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900/40 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-brand-200 transition-colors">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-black rounded uppercase">{{ p.tipo }}</span>
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ p.numero }}</span>
                <span class="text-xs text-slate-500">— {{ new Date(p.fecha).toLocaleDateString() }}</span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-sm">{{ p.concepto }}</p>
            </div>
            <div class="text-right">
              <div class="text-rose-600 font-mono text-sm font-bold mb-1">
                Diff: ${{ Number(p.diferencia).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
              </div>
              <Link :href="route('contabilidad.show', p.id)" class="text-[10px] font-black text-brand-600 hover:text-brand-700 uppercase tracking-tighter underline">Ver Detalle</Link>
            </div>
          </div>

          <!-- Total Final al fondo de la lista -->
          <div class="mt-4 p-6 bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl">
            <div class="flex flex-col gap-4">
              <div class="flex justify-between items-center pb-4 border-b border-slate-800">
                <div class="flex flex-col">
                  <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Suma de Errores Individuales</span>
                  <span class="text-xs text-slate-400 italic">Total absoluto de todas las diferencias</span>
                </div>
                <span class="text-xl font-mono font-bold text-slate-300">
                  ${{ problematicPolicies.reduce((acc, p) => acc + (p.diferencia || 0), 0).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                </span>
              </div>
              
              <div class="flex justify-between items-center">
                <div class="flex flex-col">
                  <span class="text-[10px] font-black text-brand-500 uppercase tracking-widest">Impacto Neto en Balance</span>
                  <span class="text-xs text-slate-400 italic">Diferencia real que verás en el catálogo</span>
                </div>
                <div class="text-right">
                  <span class="text-2xl font-mono font-black text-amber-400">
                    ${{ Math.abs(problematicPolicies.reduce((acc, p) => acc + (p.debe_haber_diff || 0), 0)).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                  </span>
                  <p v-if="Math.abs(problematicPolicies.reduce((acc, p) => acc + (p.debe_haber_diff || 0), 0) - (totals.debe - totals.haber)) < 0.01" class="text-[9px] text-emerald-500 font-black uppercase mt-1">
                    ✓ Coincide con el catálogo
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 flex justify-end">
        <button @click="showAuditModal = false" class="px-6 py-2.5 bg-slate-900 dark:bg-slate-700 text-white text-sm font-bold rounded-xl hover:bg-slate-800 transition-colors">
          Entendido
        </button>
      </div>
    </div>
  </div>
  <!-- Modal de Nueva Cuenta -->
  <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-md">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden border border-slate-100 dark:border-slate-700 animate-in fade-in zoom-in duration-300">
      <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center shrink-0">
        <div>
          <h3 class="text-xl font-black text-slate-900 dark:text-slate-100">Nueva Cuenta Contable</h3>
          <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Añadir estructura al catálogo</p>
        </div>
        <button @click="showCreateModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-full transition-all">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <form @submit.prevent="submitCreate" class="p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 space-y-3">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Nivel de la Cuenta Padre</label>
            
            <!-- Nivel 1 -->
            <select v-model="selectedNivel1" 
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all font-mono">
              <option :value="null">-- Crear como Cuenta Raíz (Nivel 1) --</option>
              <option v-for="c in rootAccounts" :key="c.id" :value="c.id">
                {{ c.codigo }} - {{ c.nombre }}
              </option>
            </select>

            <!-- Nivel 2 -->
            <select v-if="selectedNivel1 && level2Accounts.length > 0" v-model="selectedNivel2" 
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all font-mono animate-in slide-in-from-top-2">
              <option :value="null">-- Colgar directamente de {{ getAccountBy(selectedNivel1).nombre }} --</option>
              <option v-for="c in level2Accounts" :key="c.id" :value="c.id">
                └─ {{ c.codigo }} - {{ c.nombre }}
              </option>
            </select>

            <!-- Nivel 3 -->
            <select v-if="selectedNivel2 && level3Accounts.length > 0" v-model="selectedNivel3" 
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all font-mono animate-in slide-in-from-top-2">
              <option :value="null">-- Colgar directamente de {{ getAccountBy(selectedNivel2).nombre }} --</option>
              <option v-for="c in level3Accounts" :key="c.id" :value="c.id">
                &nbsp;&nbsp;&nbsp;└─ {{ c.codigo }} - {{ c.nombre }}
              </option>
            </select>

            <!-- Nivel 4 -->
            <select v-if="selectedNivel3 && level4Accounts.length > 0" v-model="selectedNivel4" 
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all font-mono animate-in slide-in-from-top-2">
              <option :value="null">-- Colgar directamente de {{ getAccountBy(selectedNivel3).nombre }} --</option>
              <option v-for="c in level4Accounts" :key="c.id" :value="c.id">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└─ {{ c.codigo }} - {{ c.nombre }}
              </option>
            </select>

            <!-- Nivel 5 -->
            <select v-if="selectedNivel4 && level5Accounts.length > 0" v-model="selectedNivel5" 
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all font-mono animate-in slide-in-from-top-2">
              <option :value="null">-- Colgar directamente de {{ getAccountBy(selectedNivel4).nombre }} --</option>
              <option v-for="c in level5Accounts" :key="c.id" :value="c.id">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;└─ {{ c.codigo }} - {{ c.nombre }}
              </option>
            </select>

            <p class="text-[9px] text-slate-400">Las sub-cuentas aparecerán automáticamente al seleccionar un nivel superior.</p>

            <!-- Panel de Cuentas Existentes -->
            <div v-if="createForm.padre_id" class="mt-4 p-4 bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl animate-in fade-in">
              <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-3">
                Cuentas ya existentes en este nivel
              </label>
              <div v-if="existingSiblings.length > 0" class="flex flex-wrap gap-2 max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                <span v-for="sib in existingSiblings" :key="sib.id" 
                  class="px-2.5 py-1.5 text-xs font-mono font-bold rounded-lg border transition-all"
                  :class="sib.es_detalle ? 'bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-400 border-brand-200 dark:border-brand-800/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600'">
                  {{ sib.codigo }} <span class="font-sans font-normal opacity-75 ml-1">{{ sib.nombre }}</span>
                </span>
              </div>
              <div v-else class="text-xs text-slate-400 italic">
                Aún no hay cuentas colgadas de este padre.
              </div>
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Código de Cuenta</label>
            <input v-model="createForm.codigo" type="text" required placeholder="Ej. 102.01-003"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all" />
            <div v-if="createForm.errors.codigo" class="mt-1 text-[10px] text-rose-500 font-bold">{{ createForm.errors.codigo }}</div>
          </div>

          <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Código SAT</label>
            <input v-model="createForm.sat_codigo" type="text" placeholder="Ej. 102.01"
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all" />
          </div>

          <div class="col-span-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nombre de la Cuenta</label>
            <input v-model="createForm.nombre" type="text" required placeholder="Nombre descriptivo..."
              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm focus:ring-2 focus:ring-brand-500/30 transition-all" />
          </div>

          <div v-if="!createForm.padre_id">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tipo</label>
            <select v-model="createForm.tipo" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm transition-all">
              <option value="activo">Activo</option>
              <option value="pasivo">Pasivo</option>
              <option value="capital">Capital</option>
              <option value="ingreso">Ingreso</option>
              <option value="egreso">Egreso</option>
            </select>
          </div>

          <div v-if="!createForm.padre_id">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Naturaleza</label>
            <select v-model="createForm.naturaleza" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm transition-all">
              <option value="deudora">Deudora</option>
              <option value="acreedora">Acreedora</option>
            </select>
          </div>

          <div class="col-span-2 flex items-center gap-3 p-4 bg-brand-50 dark:bg-brand-900/10 rounded-2xl border border-brand-100 dark:border-brand-900/30">
            <input v-model="createForm.es_detalle" type="checkbox" class="w-5 h-5 text-brand-600 rounded-lg border-slate-300 focus:ring-brand-500" />
            <div>
              <p class="text-xs font-bold text-brand-900 dark:text-amber-200">Esta es una cuenta de detalle</p>
              <p class="text-[10px] text-brand-700 dark:text-amber-400">Permite registrar asientos y pólizas directamente.</p>
            </div>
          </div>
        </div>

        <div class="flex gap-3 pt-4">
          <button type="button" @click="showCreateModal = false" 
            class="flex-1 px-6 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold rounded-2xl hover:bg-slate-200 transition-all">
            Cancelar
          </button>
          <button type="submit" :disabled="createForm.processing"
            class="flex-[2] px-6 py-4 bg-slate-900 dark:bg-brand-600 text-white text-sm font-black rounded-2xl hover:bg-slate-800 dark:hover:bg-brand-500 transition-all shadow-xl disabled:opacity-50">
            {{ createForm.processing ? 'Guardando...' : 'Crear Cuenta' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
