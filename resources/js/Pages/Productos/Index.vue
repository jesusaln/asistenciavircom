
<!-- /resources/js/Pages/Productos/IndexNew.vue -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import ProductosHeader from '@/Components/IndexComponents/ProductosHeader.vue'
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  productos: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'nombre', sort_direction: 'asc' }) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedProducto = ref(null)
const selectedId = ref(null)
const showStockModal = ref(false)
const stockDetalle = ref(null)
const loadingStock = ref(false)
const showSatModal = ref(false)
const satTarget = ref(null)
const satForm = ref({ sat_clave_prod_serv: '' })
const satClaveDescription = ref('')
const satSuggestion = ref(null)
const satSaving = ref(false)
const satSuggesting = ref(false)
// URL helpers para series (compatibles con subcarpetas y Ziggy opcional)
const seriesUrl = (id) => {
  try { const stockUrl = route('productos.stock-detalle', id); return stockUrl.replace('/stock-detalle', '/series') }
  catch (e) { const base = typeof window !== 'undefined' ? window.location.origin : ''; return `${base}/productos/${id}/series` }
}
const seriesUpdateUrl = (productoId, serieId) => {
  try { const stockUrl = route('productos.stock-detalle', productoId); return stockUrl.replace('/stock-detalle', `/series/${serieId}`) }
  catch (e) { const base = typeof window !== 'undefined' ? window.location.origin : ''; return `${base}/productos/${productoId}/series/${serieId}` }
}

// Series: modal y conteos por producto
const showSeriesModal = ref(false)
const seriesDetalle = ref(null)
const seriesCountMap = ref({}) // { [productoId]: { en_stock: number, vendido: number } }
const seriesLoadingMap = ref({}) // { [productoId]: boolean }
const seriesSearch = ref({ enStock: '', vendidas: '' })
const seriesPage = ref({ enStock: 1, vendidas: 1 })
const seriesPerPage = ref({ enStock: 10, vendidas: 10 })
const editSerie = ref({ rowId: null, value: '' })



// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('nombre-asc')
const filtroEstado = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nuevo producto
const crearNuevoProducto = () => {
  router.visit(route('productos.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'nombre-asc'
  filtroEstado.value = ''
  router.visit(route('productos.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Estadísticas adicionales para el header moderno
const valorTotalCosto = computed(() => {
  // Calcular el valor total basado en precio de costo
  if (productosData.value && productosData.value.length > 0) {
    const productosConValor = productosData.value.filter(producto =>
      producto.precio_compra &&
      parseFloat(producto.precio_compra) > 0 &&
      producto.stock &&
      parseFloat(producto.stock) > 0
    )
    if (productosConValor.length > 0) {
      const totalValor = productosConValor.reduce((sum, producto) =>
        sum + ((parseFloat(producto.precio_compra) || 0) * (parseFloat(producto.stock) || 0)), 0
      )
      return totalValor
    }
  }
  return 0
})

const valorTotalVenta = computed(() => {
  // Calcular el valor total basado en precio de venta
  if (productosData.value && productosData.value.length > 0) {
    const productosConValor = productosData.value.filter(producto =>
      producto.precio_venta &&
      parseFloat(producto.precio_venta) > 0 &&
      producto.stock &&
      parseFloat(producto.stock) > 0
    )
    if (productosConValor.length > 0) {
      const totalValor = productosConValor.reduce((sum, producto) =>
        sum + ((parseFloat(producto.precio_venta) || 0) * (parseFloat(producto.stock) || 0)), 0
      )
      return totalValor
    }
  }
  return 0
})

// Datos
const productosPaginator = computed(() => props.productos)
const productosData = computed(() => productosPaginator.value?.data || [])

// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  activos: props.stats?.activos ?? 0,
  inactivos: props.stats?.inactivos ?? 0,
  agotado: props.stats?.agotado ?? 0,
  activosPorcentaje: props.stats?.activos > 0 ? Math.round((props.stats.activos / props.stats.total) * 100) : 0,
  inactivosPorcentaje: props.stats?.inactivos > 0 ? Math.round((props.stats.inactivos / props.stats.total) * 100) : 0,
  agotadoPorcentaje: props.stats?.agotado > 0 ? Math.round((props.stats.agotado / props.stats.total) * 100) : 0
}))

// Transformación de datos
const productosDocumentos = computed(() => {
  return productosData.value.map(p => ({
    id: p.id,
    titulo: p.nombre || 'Sin nombre',
    subtitulo: p.descripcion ? p.descripcion.substring(0, 50) + (p.descripcion.length > 50 ? '...' : '') : 'Sin descripción',
    estado: p.estado || 'activo',
    extra: `Código: ${p.codigo || 'N/A'} | Precio: $${p.precio_venta || 0} | Stock: ${p.stock || 0}`,
    fecha: p.created_at,
    raw: p
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('productos.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: newEstado,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedProducto.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarProducto = (id) => {
  router.visit(route('productos.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarProducto = () => {
  router.delete(route('productos.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Producto eliminado correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el producto')
    }
  })
}

const toggleProducto = (id) => {
  const producto = productosData.value.find(p => p.id === id)
  if (!producto) return notyf.error('Producto no encontrado')
  const nuevoEstado = producto.estado === 'activo' ? 'inactivo' : 'activo'
  const mensaje = nuevoEstado === 'activo' ? 'Producto activado correctamente' : 'Producto desactivado correctamente'

  router.put(route('productos.toggle', id), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success(mensaje)
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo cambiar el estado del producto')
    }
  })
}

const toggleDestacado = (id) => {
  router.put(route('productos.toggle-destacado', id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Visibilidad en index actualizada')
    }
  })
}

const csrfToken = () => {
  const meta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null
  return meta ? meta.getAttribute('content') : ''
}

const openSatModal = (producto) => {
  satTarget.value = producto
  satForm.value = { sat_clave_prod_serv: producto?.sat_clave_prod_serv || '' }
  satClaveDescription.value = ''
  satSuggestion.value = null
  showSatModal.value = true
  if (!producto?.sat_clave_prod_serv) {
    suggestSatClave(producto?.nombre || producto?.descripcion || '')
  }
}

const closeSatModal = () => {
  showSatModal.value = false
  satTarget.value = null
  satForm.value = { sat_clave_prod_serv: '' }
  satClaveDescription.value = ''
  satSuggestion.value = null
}

const suggestSatClave = async (nombre) => {
  const query = (nombre || '').trim()
  if (query.length < 3) return
  satSuggesting.value = true
  try {
    const response = await fetch(`${window.location.origin}/sat/buscar-clave-prod-serv?search=${encodeURIComponent(query)}`)
    if (!response.ok) return
    const results = await response.json()
    if (Array.isArray(results) && results.length > 0) {
      satForm.value.sat_clave_prod_serv = results[0].clave
      satClaveDescription.value = results[0].descripcion
      satSuggestion.value = results[0]
    }
  } catch (error) {
    console.error('Error buscando clave SAT:', error)
  } finally {
    satSuggesting.value = false
  }
}

const saveSatClave = async () => {
  if (!satTarget.value) return
  const clave = (satForm.value.sat_clave_prod_serv || '').trim()
  if (!clave) {
    notyf.error('Selecciona una clave SAT')
    return
  }
  satSaving.value = true
  try {
    const response = await fetch(`/productos/${satTarget.value.id}/sat`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken()
      },
      credentials: 'same-origin',
      body: JSON.stringify({ sat_clave_prod_serv: clave })
    })
    if (!response.ok) {
      const data = await response.json().catch(() => ({}))
      notyf.error(data?.message || 'No se pudo guardar la clave SAT')
      return
    }
    notyf.success('Clave SAT guardada')
    closeSatModal()
    router.reload({ preserveScroll: true })
  } catch (error) {
    notyf.error('No se pudo guardar la clave SAT')
  } finally {
    satSaving.value = false
  }
}

const exportProductos = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  const queryString = params.toString()
  const url = route('productos.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

const verStockDetalle = async (producto) => {
  loadingStock.value = true
  try {
    const response = await fetch(route('productos.stock-detalle', producto.id))
    if (response.ok) {
      stockDetalle.value = await response.json()
      showStockModal.value = true
    } else {
      notyf.error('Error al cargar el detalle de stock')
    }
  } catch (error) {
    console.error('Error:', error)
    notyf.error('Error al cargar el detalle de stock')
  } finally {
    loadingStock.value = false
  }
}

// Ver series del producto (en_stock y vendidas)
const verSeries = async (producto) => {
  try {
    let url = ''
    try {
      url = route('productos.series', producto.id)
    } catch (e) {
      const base = typeof window !== 'undefined' ? window.location.origin : ''
      url = `${base}/productos/${producto.id}/series`
    }
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    if (!response.ok) {
      notyf.error('Error al cargar las series del producto')
      return
    }
    seriesDetalle.value = await response.json()
    showSeriesModal.value = true
  } catch (error) {
    console.error('Error al cargar series:', error)
    notyf.error('Error al cargar las series del producto')
  }
}

// Prefetch de conteos de series (en stock) para la página actual
const prefetchSeriesCounts = async () => {
  const items = productosData.value || []
  for (const p of items) {
    const prod = p.raw || p
    const id = prod.id
    if (seriesCountMap.value[id] !== undefined || seriesLoadingMap.value[id]) continue
    seriesLoadingMap.value[id] = true
    try {
      let url = ''
      try {
        url = route('productos.series', id)
      } catch (e) {
        const base = typeof window !== 'undefined' ? window.location.origin : ''
        url = `${base}/productos/${id}/series`
      }
      const res = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      })
      if (res.ok) {
        const data = await res.json()
        const enStock = Number(data?.counts?.en_stock ?? 0)
        const vendido = Number(data?.counts?.vendido ?? 0)
        seriesCountMap.value[id] = { en_stock: enStock, vendido }
      } else {
        seriesCountMap.value[id] = { en_stock: 0, vendido: 0 }
      }
    } catch (_) {
      seriesCountMap.value[id] = { en_stock: 0, vendido: 0 }
    } finally {
      seriesLoadingMap.value[id] = false
    }
  }
}

onMounted(() => {
  prefetchSeriesCounts()
})

// Re-prefetch al cambiar de página o filtro
watch(productosData, () => {
  prefetchSeriesCounts()
})

const filteredEnStock = computed(() => {
  const q = (seriesSearch.value.enStock || '').toLowerCase()
  const list = seriesDetalle.value?.series?.en_stock || []
  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list
})

const filteredVendidas = computed(() => {
  const q = (seriesSearch.value.vendidas || '').toLowerCase()
  const list = seriesDetalle.value?.series?.vendido || []
  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list
})

// Agrupar series en stock por almacén
const seriesEnStockPorAlmacen = computed(() => {
  const series = filteredEnStock.value
  const grupos = {}
  
  series.forEach(s => {
    const almacenNombre = s.almacen_nombre || 'Sin almacén'
    if (!grupos[almacenNombre]) {
      grupos[almacenNombre] = []
    }
    grupos[almacenNombre].push(s)
  })
  
  // Convertir a array y ordenar por nombre de almacén
  return Object.entries(grupos)
    .map(([nombre, series]) => ({ nombre, series }))
    .sort((a, b) => a.nombre.localeCompare(b.nombre))
})

// Agrupar series vendidas por almacén
const seriesVendidasPorAlmacen = computed(() => {
  const series = filteredVendidas.value
  const grupos = {}
  
  series.forEach(s => {
    const almacenNombre = s.almacen_nombre || 'Sin almacén'
    if (!grupos[almacenNombre]) {
      grupos[almacenNombre] = []
    }
    grupos[almacenNombre].push(s)
  })
  
  // Convertir a array y ordenar por nombre de almacén
  return Object.entries(grupos)
    .map(([nombre, series]) => ({ nombre, series }))
    .sort((a, b) => a.nombre.localeCompare(b.nombre))
})

const editarSerie = (s) => {
  editSerie.value = { rowId: s.id, value: s.numero_serie }
}

const cancelarEdicion = () => {
  editSerie.value = { rowId: null, value: '' }
}

const guardarSerie = async (productoId, s) => {
  const nuevo = (editSerie.value.value || '').trim()
  if (!nuevo) return
  try {
    let url = ''
    try {
      url = route('productos.series.update', productoId, s.id)
    } catch (e) {
      const base = typeof window !== 'undefined' ? window.location.origin : ''
      url = `${base}/productos/${productoId}/series/${s.id}`
    }

    // Obtener el token CSRF
    const csrfToken = () => {
      const meta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null
      if (meta) return meta.getAttribute('content')
      const m = typeof document !== 'undefined' ? document.cookie.match(/XSRF-TOKEN=([^;]+)/) : null
      return m ? decodeURIComponent(m[1]) : null
    }
    const token = csrfToken()

    const res = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ numero_serie: nuevo })
    })
    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      notyf.error(data?.message || 'No se pudo actualizar la serie')
      return
    }
    // Actualizar lista localmente
    const idx = (seriesDetalle.value?.series?.en_stock || []).findIndex(x => x.id === s.id)
    if (idx >= 0) seriesDetalle.value.series.en_stock[idx].numero_serie = nuevo
    notyf.success('Serie actualizada')
    cancelarEdicion()
  } catch (err) {
    notyf.error('Error al actualizar la serie')
  }
}

// Paginación
const paginationData = computed(() => {
  const p = productosPaginator.value || {}
  return {
    currentPage: p.current_page ?? 1,
    lastPage:    p.last_page ?? 1,
    perPage:     p.per_page ?? 10,
    from:        p.from ?? 0,
    to:          p.to ?? 0,
    total:       p.total ?? 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links:       p.links ?? []
  }
})

const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('productos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'asc',
    estado: filtroEstado.value,
    per_page: perPage.value,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-ES').format(num)
const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inválida'
  }
}

const obtenerClasesEstado = (estado) => {
  const clases = {
    'activo': 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300',
    'inactivo': 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300',
    'agotado': 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300'
  }
  return clases[estado] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const obtenerLabelEstado = (estado) => {
  const labels = {
    'activo': 'Activo',
    'inactivo': 'Inactivo',
    'agotado': 'Agotado'
  }
  return labels[estado] || 'Pendiente'
}

// Funciones para obtener conteos de series (badges)
const getSeriesCount = (productoId, tipo) => {
  const counts = seriesCountMap.value[productoId]
  if (!counts) return 0
  return Number(counts[tipo] || 0)
}
const getSeriesTotal = (productoId) => {
  const counts = seriesCountMap.value[productoId]
  if (!counts) return 0
  return Number(counts.en_stock || 0) + Number(counts.vendido || 0)
}

// Calcular cuántas series faltan por producto
const faltanSeriesParaProducto = (producto) => {
  if (!producto.requiere_serie) return 0
  const stock = Number(producto.stock || 0)
  const seriesRegistradas = getSeriesCount(producto.id, 'en_stock')
  return Math.max(0, stock - seriesRegistradas)
}
</script>

<template>
<div>
    <Head title="Productos" />
    <div class="productos-index min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900">
    <div class="w-full px-6 py-8">
      <!-- Header específico de productos -->
      <ProductosHeader
        :total="estadisticas.total"
        :activos="estadisticas.activos"
        :inactivos="estadisticas.inactivos"
        :agotado="estadisticas.agotado"
        :valor-total-costo="valorTotalCosto"
        :valor-total-venta="valorTotalVenta"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado="filtroEstado"
        @crear-nueva="crearNuevoProducto"
        @search-change="handleSearchChange"
        @filtro-estado-change="handleEstadoChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
      />

      <!-- Tabla -->
      <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Código</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">SAT</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Precio</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">En Inicio</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
              <tr v-for="producto in productosDocumentos" :key="producto.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white dark:text-gray-100">{{ formatearFecha(producto.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">{{ producto.titulo }}</div>
                  <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ producto.subtitulo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700 dark:text-gray-300">{{ producto.raw.codigo || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <button
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                    :class="producto.raw.sat_clave_prod_serv ? 'bg-green-100 dark:bg-green-800/50 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-800/50 text-red-700 dark:text-red-200'"
                    @click="openSatModal(producto.raw)"
                  >
                    {{ producto.raw.sat_clave_prod_serv || 'Sin clave' }}
                  </button>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700 dark:text-gray-300">${{ formatNumber(producto.raw.precio_venta || 0) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 cursor-pointer font-medium hover:underline"
                    @click="verStockDetalle(producto.raw)"
                    title="Ver detalle de stock por almacén"
                  >
                    {{ producto.raw.stock || 0 }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesEstado(producto.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelEstado(producto.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-center">
                  <button
                    @click="toggleDestacado(producto.id)"
                    class="transition-all duration-300 transform hover:scale-125"
                    :class="producto.raw.destacado ? 'text-amber-500 scale-110' : 'text-gray-300 dark:text-gray-600 dark:text-gray-300 hover:text-amber-300 dark:hover:text-amber-500'"
                    :title="producto.raw.destacado ? 'Quitar del inicio' : 'Mostrar en el inicio'"
                  >
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                      <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                  </button>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(producto)" class="w-8 h-8 bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-gray-600 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button v-if="(getSeriesTotal(producto.id) > 0) || producto.raw.requiere_serie"
                            @click="verSeries(producto.raw)"
                            :class="[
                              'relative w-8 h-8 rounded-lg transition-colors duration-150',
                              faltanSeriesParaProducto(producto.raw) > 0 
                                ? 'bg-amber-100 dark:bg-amber-800/50 text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800' 
                                : 'bg-emerald-50 dark:bg-emerald-800/50 text-emerald-600 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-800'
                            ]"
                            :title="faltanSeriesParaProducto(producto.raw) > 0 
                              ? `Series (Faltan ${faltanSeriesParaProducto(producto.raw)})` 
                              : 'Series'">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-7 4h8M5 8h14" />
                      </svg>
                      <span v-if="faltanSeriesParaProducto(producto.raw) > 0" 
                            class="absolute -top-1 -right-1 bg-amber-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-semibold shadow-sm">
                        {{ faltanSeriesParaProducto(producto.raw) }}
                      </span>
                    </button>
                    <button @click="editarProducto(producto.id)" class="w-8 h-8 bg-amber-50 dark:bg-gray-700 text-amber-600 dark:text-amber-300 rounded-lg hover:bg-amber-100 dark:hover:bg-gray-600 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button v-if="producto.estado !== 'activo'" @click="confirmarEliminacion(producto.id)" class="w-8 h-8 bg-red-50 dark:bg-gray-700 text-red-600 dark:text-red-300 rounded-lg hover:bg-red-100 dark:hover:bg-gray-600 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="productosDocumentos.length === 0">
                <td colspan="8" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 dark:text-gray-300 font-medium">No hay productos</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Los productos aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white dark:bg-slate-900 dark:bg-gray-800 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 dark:border-gray-600 rounded-md text-sm py-1 px-2 bg-white dark:bg-slate-900 dark:bg-gray-700 dark:text-gray-200"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-blue-50 dark:bg-blue-500/50 border-blue-500 text-blue-600 dark:text-white' : 'bg-white dark:bg-slate-900 dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-600"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-800 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100">
              {{ modalMode === 'details' ? 'Detalles del Producto' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedProducto">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">{{ selectedProducto.nombre }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">{{ selectedProducto.codigo || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio Venta</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">${{ formatNumber(selectedProducto.precio_venta || 0) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">{{ selectedProducto.stock || 0 }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                      <span :class="obtenerClasesEstado(selectedProducto.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedProducto.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Creación</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">{{ formatearFecha(selectedProducto.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Última Actualización</label>
                      <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md">{{ formatearFecha(selectedProducto.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedProducto.descripcion">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                  <p class="mt-1 text-sm text-gray-900 dark:text-white dark:text-gray-100 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedProducto.descripcion }}</p>
                </div>
              </div>
            </div>

            <div v-if="modalMode === 'confirm'">
              <div class="text-center">
                <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                  <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-2">¿Eliminar Producto?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-4">
                  ¿Estás seguro de que deseas eliminar el producto <strong>{{ selectedProducto?.nombre }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Listado de series vendidas</h4>
            <div class="max-h-64 overflow-y-auto border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-lg">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">#</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">Número de serie</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                  <tr v-for="(s, idx) in pagedVendidas" :key="s.id">
                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ (seriesPage.vendidas - 1) * seriesPerPage.vendidas + idx + 1 }}</td>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">{{ s.numero_serie }}</td>
                  </tr>
                  <tr v-if="filteredVendidas.length === 0">
                    <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Sin series vendidas</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="toggleProducto(selectedProducto.id)" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Cambiar Estado
              </button>
              <button @click="editarProducto(selectedProducto.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarProducto" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal SAT -->
      <div v-if="showSatModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeSatModal">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-xl overflow-hidden">
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100">
              Clave SAT del producto: {{ satTarget?.nombre || '' }}
            </h3>
            <button @click="closeSatModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">
              Se usa el nombre para sugerir la primera coincidencia del catalogo SAT.
            </p>
            <div v-if="satSuggesting" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">Buscando sugerencia...</div>
            <SatClaveProdServSearch
              v-model="satForm.sat_clave_prod_serv"
              :initial-description="satClaveDescription"
            />
            <div v-if="satSuggestion" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
              Sugerencia aplicada: {{ satSuggestion.clave }} - {{ satSuggestion.descripcion }}
            </div>
          </div>
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
            <button @click="closeSatModal" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors">
              Cancelar
            </button>
            <button @click="saveSatClave" :disabled="satSaving" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
              {{ satSaving ? 'Guardando...' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de Detalle de Stock -->
      <div v-if="showStockModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showStockModal = false">
        <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100">
              Detalle de Stock: {{ stockDetalle?.producto?.nombre }}
            </h3>
            <button @click="showStockModal = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="loadingStock" class="flex items-center justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              <span class="ml-3 text-gray-600 dark:text-gray-300 dark:text-gray-300">Cargando...</span>
            </div>

            <div v-else-if="stockDetalle">
              <!-- Información del producto -->
              <div class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Producto:</span>
                    <span class="ml-2 text-gray-900 dark:text-white dark:text-gray-100">{{ stockDetalle.producto.nombre }}</span>
                  </div>
                  <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Código:</span>
                    <span class="ml-2 text-gray-900 dark:text-white dark:text-gray-100">{{ stockDetalle.producto.codigo || 'N/A' }}</span>
                  </div>
                  <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Stock Total:</span>
                    <span class="ml-2 text-gray-900 dark:text-white dark:text-gray-100 font-semibold">{{ stockDetalle.producto.stock_total }}</span>
                  </div>
                </div>
              </div>

              <!-- Tabla de stock por almacén -->
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                        Almacén
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                        Cantidad Disponible
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                        Stock Mínimo
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                        Estado
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                    <tr v-for="stock in stockDetalle.stock_por_almacen" :key="stock.almacen_id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-800/50 flex items-center justify-center">
                              <svg class="h-4 w-4 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                              </svg>
                            </div>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                              {{ stock.almacen_nombre }}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-gray-100">{{ stock.cantidad }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ stock.stock_minimo }}</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          :class="stock.cantidad > stock.stock_minimo ? 'bg-green-100 dark:bg-green-800/50 text-green-800 dark:text-green-200' : 'bg-red-100 dark:bg-red-800/50 text-red-800 dark:text-red-200'"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                          {{ stock.cantidad > stock.stock_minimo ? 'Normal' : 'Bajo' }}
                        </span>
                      </td>
                    </tr>
                    <tr v-if="stockDetalle.stock_por_almacen.length === 0">
                      <td colspan="4" class="px-6 py-8 text-center">
                        <div class="flex flex-col items-center space-y-2">
                         <svg class="w-8 h-8 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m8-5v2m0 0v2m0-2h2m-2 0h-2"/>
                         </svg>
                       </div>
                     </td>
                   </tr>
                 </tbody>
               </table>
             </div>
           </div>
         </div>

         <!-- Footer del modal -->
         <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
           <button @click="showStockModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors">
             Cerrar
           </button>
         </div>
       </div>
     </div>
   </div>
 </div>

 <!-- Modal Series -->
 <div v-if="showSeriesModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showSeriesModal = false">
   <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
     <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
       <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">Series del Producto: {{ seriesDetalle?.producto?.nombre || '' }}</h3>
       <button @click="showSeriesModal = false" class="text-gray-400 dark:text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors">
         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
       </button>
     </div>
     <div class="flex-1 overflow-y-auto p-6">
       <div v-if="!seriesDetalle"><p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Cargando...</p></div>
        <div v-else>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
           <div>
             <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar en stock</label>
             <input v-model.trim="seriesSearch.enStock" type="text" placeholder="Buscar número de serie" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
           </div>
           <div>
             <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar vendidas</label>
             <input v-model.trim="seriesSearch.vendidas" type="text" placeholder="Buscar número de serie" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-gray-500 focus:border-gray-500" />
           </div>
         </div>
         <div class="grid grid-cols-2 gap-4 mb-4">
            <div :class="[
               'p-3 border rounded-lg',
               'bg-emerald-50 dark:bg-emerald-800/50 border-emerald-200 dark:border-emerald-700'
             ]">
               <div :class="[
                 'text-xs',
                 'text-emerald-700 dark:text-emerald-300'
               ]">Series en stock</div>
               <div :class="[
                 'text-2xl font-semibold',
                 'text-emerald-800 dark:text-emerald-200'
               ]">{{ seriesDetalle.counts.en_stock }}</div>
             </div>
           <div class="p-3 bg-white dark:bg-slate-900 dark:bg-gray-700/50 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg">
             <div class="text-xs text-gray-700 dark:text-gray-300">Series vendidas</div>
             <div class="text-2xl font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-100">{{ seriesDetalle.counts.vendido }}</div>
           </div>
         </div>


          <div>
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Listado de series en stock ({{ filteredEnStock.length }} total)</h4>
            <div class="overflow-y-auto border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-lg" style="max-height: calc(100vh - 500px); min-height: 200px;">
              <!-- Agrupar por almacén -->
              <div v-if="seriesEnStockPorAlmacen.length > 0">
                <div v-for="(grupo, grupoIdx) in seriesEnStockPorAlmacen" :key="grupoIdx" class="border-b border-gray-200 dark:border-slate-800 dark:border-gray-700 last:border-b-0">
                  <!-- Header del grupo (almacén) -->
                  <div class="bg-blue-50 dark:bg-blue-900/30 px-4 py-3 border-b border-blue-100 dark:border-blue-800/50">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="text-sm font-semibold text-blue-900 dark:text-blue-200">{{ grupo.nombre }}</span>
                        <span class="text-xs text-blue-600 dark:text-blue-300 bg-blue-100 dark:bg-blue-800/50 px-2 py-0.5 rounded-full">{{ grupo.series.length }} {{ grupo.series.length === 1 ? 'serie' : 'series' }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Series del almacén -->
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">#</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">Número de serie</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300">Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                      <tr v-for="(s, idx) in grupo.series" :key="s.id" class="hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ idx + 1 }}</td>
                        <td class="px-4 py-2 text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                          <template v-if="editSerie.rowId === s.id">
                            <input v-model.trim="editSerie.value" type="text" class="w-full border border-emerald-300 dark:border-emerald-600 dark:bg-gray-700 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                          </template>
                          <template v-else>
                            {{ s.numero_serie }}
                          </template>
                        </td>
                        <td class="px-4 py-2 text-right text-sm">
                          <template v-if="editSerie.rowId === s.id">
                            <button @click="guardarSerie(seriesDetalle.producto.id, s)" class="px-2 py-1 bg-emerald-600 text-white rounded text-xs mr-2">Guardar</button>
                            <button @click="cancelarEdicion()" class="px-2 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded text-xs">Cancelar</button>
                          </template>
                          <template v-else>
                            <button @click="editarSerie(s)" class="px-2 py-1 bg-emerald-50 dark:bg-emerald-800/50 text-emerald-700 dark:text-emerald-300 rounded text-xs border border-emerald-200 dark:border-emerald-700">Editar</button>
                          </template>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <!-- Sin series -->
              <div v-else class="px-4 py-8 text-center">
                <div class="flex flex-col items-center space-y-2">
                  <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                  </svg>
                  <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Sin series en stock</p>
                </div>
              </div>
            </div>
          </div>
       </div>
     </div>
     <div class="px-6 py-4 border-t bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 border-gray-200 dark:border-slate-800 dark:border-gray-700 text-right flex-shrink-0">
       <button @click="showSeriesModal = false" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 transition-colors">Cerrar</button>
     </div>
   </div>
 </div>
</div>
</template>

<style scoped>
.productos-index {
 min-height: 100vh;
}
</style>
