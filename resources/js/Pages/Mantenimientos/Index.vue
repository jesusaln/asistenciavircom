<!-- /resources/js/Pages/Mantenimientos/Index.vue -->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

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

  // Debug: Verificar todos los mantenimientos y sus valores
  /* console.log('=== DEBUG MANTENIMIENTOS ===')
  const mantenimientos = mantenimientosData.value || [];
  mantenimientos.forEach(m => {
    console.log(`ID: ${m.id}, Tipo: ${m.tipo}, Estado: "${m.estado}", Días restantes: ${m.dias_restantes}, Próximo: ${m.proximo_mantenimiento}`)
  }) */

  // Verificar si hay mantenimientos que requieren atención (vencidos o próximos)
  const mantenimientosVencidos = mantenimientosData.value.filter(m => {
    const estadoLimpio = (m.estado || '').toString().toLowerCase().trim()
    const esProximo = m.dias_restantes <= 0 // Cualquier mantenimiento con 0 o menos días requiere atención
    return esProximo // Mostrar notificación si requiere atención
  })

  // console.log(`Total mantenimientos vencidos: ${mantenimientosVencidos.length}`)

  if (mantenimientosVencidos.length > 0) {
    notyf.open({
      type: 'warning',
      message: `¡Atención! Tienes ${mantenimientosVencidos.length} mantenimiento(s) que requieren atención inmediata (fecha de próximo mantenimiento alcanzada).`
    })
  }
})

// Props
const props = defineProps({
  mantenimientos: { type: [Object, Array], required: true },
  estadisticas: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'fecha', sort_direction: 'desc' }) },
  carros: { type: Array, default: () => [] },
  tiposMantenimiento: { type: Array, default: () => [] },
})

// Datos (definir después de props)
const mantenimientosPaginator = computed(() => props.mantenimientos || {})
const mantenimientosData = computed(() => {
  // Verificar diferentes estructuras posibles de datos
  if (Array.isArray(props.mantenimientos)) {
    return props.mantenimientos;
  }

  if (props.mantenimientos?.data && Array.isArray(props.mantenimientos.data)) {
    return props.mantenimientos.data;
  }

  return [];
})

// Debug: Verificar datos recibidos (después de definir mantenimientosData)
/* console.log('=== DEBUG PROPS RECEIVED ===');
console.log('Mantenimientos data:', mantenimientosData.value);
console.log('Mantenimientos count:', mantenimientosData.value?.length || 0);
console.log('Estadisticas:', props.estadisticas);
console.log('Carros count:', props.carros?.length || 0);
console.log('Tipos mantenimiento count:', props.tiposMantenimiento?.length || 0); */

// Debug adicional: Ver primeros mantenimientos
/* if (mantenimientosData.value?.length > 0) {
  console.log('Primer mantenimiento:', mantenimientosData.value[0]);
} */

// Estado UI
  const showModal = ref(false)
  const modalMode = ref('details')
  const selectedMantenimiento = ref(null)
  const selectedId = ref(null)
  const showHistorialModal = ref(false)
  const historialVehiculo = ref(null)
  const historialMantenimientos = ref([])
  const showReprogramarModal = ref(false)
  const mantenimientoAReprogramar = ref(null)
  const costoReprogramar = ref(0)
  const proximaFecha = ref('')

  const showCompletarModal = ref(false)
  const mantenimientoACompletar = ref(null)
  const costoCompletar = ref(0)
  const fechaCompletar = ref('')
  const kilometrajeCompletar = ref(0)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('fecha-asc') // Ordenar por fecha ascendente por defecto para mejor flujo cronológico
const filtroEstado = ref('')
const filtroTipo = ref('')
const filtroCarro = ref('')
const filtroPrioridad = ref('')

// Paginación
const perPage = ref(10)

// Header config
const headerConfig = {
  module: 'mantenimientos',
  createButtonText: 'Nuevo Mantenimiento',
  searchPlaceholder: 'Buscar por tipo, descripción o vehículo...'
}

// Los datos ya están definidos arriba después de los props

// Estadísticas usando las nuevas reglas de negocio
const estadisticas = computed(() => {
  const stats = props.estadisticas || {};

  // Calcular totales
  const totalActivos = stats.total_activos || 0;
  const totalCompletados = stats.completados || 0;
  const totalGeneral = totalActivos + totalCompletados;

  return {
    // Datos base
    total: totalGeneral,
    completados: totalCompletados,
    activos: totalActivos,
    vencidos: stats.vencidos || 0,
    por_vencer: stats.por_vencer || 0,
    al_dia: stats.al_dia || 0,

    // Porcentajes calculados correctamente
    completadosPorcentaje: totalGeneral > 0 ? Math.round((totalCompletados / totalGeneral) * 100) : 0,
    activosPorcentaje: totalGeneral > 0 ? Math.round((totalActivos / totalGeneral) * 100) : 0,
    vencidosPorcentaje: totalActivos > 0 ? Math.round((stats.vencidos / totalActivos) * 100) : 0,
    porVencerPorcentaje: totalActivos > 0 ? Math.round((stats.por_vencer / totalActivos) * 100) : 0,
    alDiaPorcentaje: totalActivos > 0 ? Math.round((stats.al_dia / totalActivos) * 100) : 0,

    // Estadísticas adicionales para compatibilidad
    pendientes: totalActivos,
    en_proceso: 0,
    pendientesPorcentaje: totalGeneral > 0 ? Math.round((totalActivos / totalGeneral) * 100) : 0,
    enProcesoPorcentaje: 0,
  };
})

// Transformación de datos
const mantenimientosDocumentos = computed(() => {
  return mantenimientosData.value.map(m => ({
    id: m.id,
    titulo: m.tipo || 'Sin tipo',
    subtitulo: m.descripcion ? m.descripcion.substring(0, 50) + (m.descripcion.length > 50 ? '...' : '') : 'Sin descripción',
    estado: m.estado || 'pendiente',
    extra: `Vehículo: ${m.carro ? m.carro.marca + ' ' + m.carro.modelo : 'N/A'} | Costo: $${m.costo || 0}`,
    fecha: m.fecha,
    raw: m
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('mantenimientos.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: filtroTipo.value,
    carro_id: filtroCarro.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoChange(newEstado) {
  filtroEstado.value = newEstado
  router.get(route('mantenimientos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstado,
    tipo: filtroTipo.value,
    carro_id: filtroCarro.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleTipoChange(newTipo) {
  filtroTipo.value = newTipo
  router.get(route('mantenimientos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: newTipo,
    carro_id: filtroCarro.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleCarroChange(newCarroId) {
  filtroCarro.value = newCarroId
  router.get(route('mantenimientos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: filtroTipo.value,
    carro_id: newCarroId,
    prioridad: filtroPrioridad.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handlePrioridadChange(newPrioridad) {
  filtroPrioridad.value = newPrioridad
  router.get(route('mantenimientos.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: filtroTipo.value,
    carro_id: filtroCarro.value,
    prioridad: newPrioridad,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('mantenimientos.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstado.value,
    tipo: filtroTipo.value,
    carro_id: filtroCarro.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedMantenimiento.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const verHistorialVehiculo = async (carroId) => {
  // console.log('verHistorialVehiculo llamado con carroId:', carroId)

  if (!carroId) {
    console.error('Error: carroId es null o undefined')
    notyf.error('No se puede mostrar el historial: vehículo no encontrado')
    return
  }

  try {
    // Buscar el vehículo en la lista de carros
    const carro = props.carros?.find(c => c.id === carroId)
    if (!carro) {
      notyf.error('Vehículo no encontrado')
      return
    }

    // Obtener todos los mantenimientos de este vehículo
    const mantenimientosDelVehiculo = mantenimientosData.value?.filter(m => m.carro_id === carroId) || []

    /* console.log('Mostrando historial del vehículo:', carro.marca + ' ' + carro.modelo)
    console.log('Total mantenimientos encontrados:', mantenimientosDelVehiculo.length) */

    // Configurar el modal de historial
    historialVehiculo.value = carro
    historialMantenimientos.value = mantenimientosDelVehiculo
    showHistorialModal.value = true

  } catch (error) {
    console.error('Error al obtener historial del vehículo:', error)
    notyf.error('Error al cargar el historial del vehículo')
  }
}

const limpiarFiltros = () => {
  searchTerm.value = ''
  filtroEstado.value = ''
  filtroTipo.value = ''
  filtroCarro.value = ''
  filtroPrioridad.value = ''
  sortBy.value = 'fecha-desc'

  router.get(route('mantenimientos.index'), {
    search: '',
    estado: '',
    tipo: '',
    carro_id: '',
    prioridad: '',
    sort_by: 'fecha',
    sort_direction: 'desc',
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const editarMantenimiento = (id) => {
  router.visit(route('mantenimientos.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarMantenimiento = () => {
  router.delete(route('mantenimientos.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Mantenimiento eliminado correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo eliminar el mantenimiento')
    }
  })
}

const cerrarHistorialModal = () => {
  showHistorialModal.value = false
  historialVehiculo.value = null
  historialMantenimientos.value = []
}

const abrirModalReprogramar = (mantenimiento) => {
  mantenimientoAReprogramar.value = mantenimiento
  costoReprogramar.value = mantenimiento.raw.costo || 0

  // Si es un mantenimiento completado, sugerir fecha basada en el tipo de servicio
  if (mantenimiento.raw.estado === 'completado') {
    // Calcular fecha próxima sugerida basada en el tipo de servicio
    const fechaBase = new Date()
    let mesesSugeridos = 3 // Por defecto

    switch (mantenimiento.raw.tipo) {
      case 'Cambio de aceite':
        mesesSugeridos = 6 // 6 meses
        break
      case 'Revisión periódica':
        mesesSugeridos = 12 // 1 año
        break
      case 'Alineación y balanceo':
        mesesSugeridos = 6 // 6 meses
        break
      case 'Cambio de filtros':
        mesesSugeridos = 12 // 1 año
        break
    }

    const proximaSugerida = new Date(fechaBase)
    proximaSugerida.setMonth(proximaSugerida.getMonth() + mesesSugeridos)
    proximaFecha.value = proximaSugerida.toISOString().split('T')[0]
  } else {
    // Para mantenimientos no completados, usar lógica existente
    const fechaActual = new Date(mantenimiento.raw.fecha)
    const proximaSugerida = new Date(fechaActual)
    proximaSugerida.setMonth(proximaSugerida.getMonth() + 3)
    proximaFecha.value = proximaSugerida.toISOString().split('T')[0]
  }

  showReprogramarModal.value = true
}

const reprogramarMantenimiento = () => {
  if (!mantenimientoAReprogramar.value) return

  // Si es un mantenimiento completado, crear uno nuevo en lugar de actualizar
  if (mantenimientoAReprogramar.value.raw.estado === 'completado') {
    // Crear nuevo mantenimiento recurrente
    const datosNuevo = {
      carro_id: mantenimientoAReprogramar.value.raw.carro_id,
      tipo: mantenimientoAReprogramar.value.raw.tipo,
      fecha: new Date().toISOString().split('T')[0], // Fecha de hoy
      proximo_mantenimiento: proximaFecha.value,
      descripcion: `Siguiente ${mantenimientoAReprogramar.value.raw.tipo} - Programado desde mantenimiento anterior ID: ${mantenimientoAReprogramar.value.id}`,
      costo: costoReprogramar.value,
      estado: 'pendiente',
      kilometraje_actual: mantenimientoAReprogramar.value.raw.kilometraje_actual,
      prioridad: mantenimientoAReprogramar.value.raw.prioridad,
      dias_anticipacion_alerta: mantenimientoAReprogramar.value.raw.dias_anticipacion_alerta,
      requiere_aprobacion: mantenimientoAReprogramar.value.raw.requiere_aprobacion,
      observaciones_alerta: mantenimientoAReprogramar.value.raw.observaciones_alerta,
      notas: `Generado automáticamente desde mantenimiento completado ID: ${mantenimientoAReprogramar.value.id}`
    }

    router.post(route('mantenimientos.store'), datosNuevo, {
      preserveScroll: true,
      onSuccess: () => {
        notyf.success('Nuevo mantenimiento programado exitosamente')
        cerrarModalReprogramar()
        router.reload()
      },
      onError: (errors) => {
        notyf.error('No se pudo programar el nuevo mantenimiento')
        console.error('Error al crear nuevo mantenimiento:', errors)
      }
    })
  } else {
    // Para mantenimientos no completados, usar reprogramación normal
    const datos = {
      costo: costoReprogramar.value,
      proxima_fecha: proximaFecha.value
    }

    router.patch(route('mantenimientos.reprogramar', mantenimientoAReprogramar.value.id), datos, {
      preserveScroll: true,
      onSuccess: () => {
        notyf.success('Mantenimiento reprogramado exitosamente')
        cerrarModalReprogramar()
        router.reload()
      },
      onError: (errors) => {
        notyf.error('No se pudo reprogramar el mantenimiento')
        console.error('Error al reprogramar mantenimiento:', errors)
      }
    })
  }
}

const cerrarModalReprogramar = () => {
  showReprogramarModal.value = false
  mantenimientoAReprogramar.value = null
  costoReprogramar.value = 0
  proximaFecha.value = ''
}

const abrirModalCompletar = (mantenimiento) => {
  mantenimientoACompletar.value = mantenimiento
  costoCompletar.value = mantenimiento.raw.costo || 0
  fechaCompletar.value = new Date().toISOString().split('T')[0] // Fecha de hoy por defecto
  kilometrajeCompletar.value = mantenimiento.raw.kilometraje_actual || 0
  showCompletarModal.value = true
}

const completarMantenimiento = () => {
  if (!mantenimientoACompletar.value) return

  const datos = {
    costo: costoCompletar.value,
    fecha_servicio: fechaCompletar.value,
    kilometraje: kilometrajeCompletar.value
  }

  router.post(route('mantenimientos.marcar-realizado-hoy', mantenimientoACompletar.value.raw.id), datos, {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Mantenimiento completado exitosamente')
      cerrarModalCompletar()
      router.reload()
    },
    onError: (errors) => {
      notyf.error('No se pudo completar el mantenimiento')
      console.error('Error al completar mantenimiento:', errors)
    }
  })
}

const cerrarModalCompletar = () => {
  showCompletarModal.value = false
  mantenimientoACompletar.value = null
  costoCompletar.value = 0
  fechaCompletar.value = ''
  kilometrajeCompletar.value = 0
}

// ==========================================
// NUEVAS ACCIONES RÁPIDAS PATCH
// ==========================================

/**
 * Completar mantenimiento usando nueva ruta PATCH
 */
const completarMantenimientoRapido = async (mantenimiento) => {
  try {
    const datos = {
      fecha_completado: new Date().toISOString().split('T')[0],
      notas_completado: 'Completado desde acciones rápidas',
      costo: mantenimiento.costo || 0
    }

    const response = await fetch(route('mantenimientos.completar', mantenimiento.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(datos)
    })

    if (response.ok) {
      const result = await response.json()
      notyf.success(result.message || 'Mantenimiento completado exitosamente')
      router.reload()
    } else {
      const error = await response.json()
      notyf.error(error.message || 'Error al completar el mantenimiento')
    }
  } catch (error) {
    console.error('Error completando mantenimiento:', error)
    notyf.error('Error de conexión al completar mantenimiento')
  }
}

/**
 * Posponer mantenimiento usando nueva ruta PATCH
 */
const posponerMantenimientoRapido = async (mantenimiento, dias = 30) => {
  try {
    const datos = {
      nuevos_dias: dias,
      motivo: `Pospuesto ${dias} días desde acciones rápidas`
    }

    const response = await fetch(route('mantenimientos.posponer', mantenimiento.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(datos)
    })

    if (response.ok) {
      const result = await response.json()
      notyf.success(result.message || `Mantenimiento pospuesto ${dias} días`)
      router.reload()
    } else {
      const error = await response.json()
      notyf.error(error.message || 'Error al posponer el mantenimiento')
    }
  } catch (error) {
    console.error('Error posponiendo mantenimiento:', error)
    notyf.error('Error de conexión al posponer mantenimiento')
  }
}

/**
 * Reprogramar mantenimiento usando nueva ruta PATCH
 */
const reprogramarMantenimientoRapido = async (mantenimiento, nuevaFecha) => {
  try {
    const datos = {
      nueva_fecha: nuevaFecha,
      motivo: 'Reprogramado desde acciones rápidas'
    }

    const response = await fetch(route('mantenimientos.reprogramar', mantenimiento.id), {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(datos)
    })

    if (response.ok) {
      const result = await response.json()
      notyf.success(result.message || 'Mantenimiento reprogramado exitosamente')
      router.reload()
    } else {
      const error = await response.json()
      notyf.error(error.message || 'Error al reprogramar el mantenimiento')
    }
  } catch (error) {
    console.error('Error reprogramando mantenimiento:', error)
    notyf.error('Error de conexión al reprogramar mantenimiento')
  }
}

/**
 * Obtener estado del mantenimiento usando las nuevas reglas de negocio
 */
const obtenerEstadoMantenimiento = (mantenimiento) => {
  // Usar el estado calculado que viene del backend si está disponible
  if (mantenimiento.estado_calculado) {
    return mantenimiento.estado_calculado
  }

  // Fallback a lógica del frontend
  const hoy = new Date()
  const proximo = new Date(mantenimiento.proximo_mantenimiento)
  const diasRestantes = Math.ceil((proximo.getTime() - hoy.getTime()) / (1000 * 60 * 60 * 24))

  if (mantenimiento.estado === 'completado') {
    return {
      estado: 'completado',
      descripcion: 'Servicio completado',
      clase: 'text-green-700 bg-green-100',
      dias_restantes: 0,
      es_vencido: false,
      es_proximo: false
    }
  }

  if (diasRestantes < 0) {
    return {
      estado: 'vencido',
      descripcion: `Vencido hace ${Math.abs(diasRestantes)} días`,
      clase: 'text-red-700 bg-red-100',
      dias_restantes: diasRestantes,
      es_vencido: true,
      es_proximo: false
    }
  }

  if (diasRestantes <= 30) {
    return {
      estado: 'por_vencer',
      descripcion: `Vence en ${diasRestantes} días`,
      clase: 'text-orange-700 bg-orange-100',
      dias_restantes: diasRestantes,
      es_vencido: false,
      es_proximo: true
    }
  }

  return {
    estado: 'al_dia',
    descripcion: `Próximo en ${diasRestantes} días`,
    clase: 'text-blue-700 bg-blue-100',
    dias_restantes: diasRestantes,
    es_vencido: false,
    es_proximo: false
  }
}

const exportMantenimientos = () => {
  const params = new URLSearchParams()
  if (searchTerm.value) params.append('search', searchTerm.value)
  if (filtroEstado.value) params.append('estado', filtroEstado.value)
  if (filtroTipo.value) params.append('tipo', filtroTipo.value)
  if (filtroCarro.value) params.append('carro_id', filtroCarro.value)
  const queryString = params.toString()
  const url = route('mantenimientos.export') + (queryString ? `?${queryString}` : '')
  window.location.href = url
}

// Paginación
const paginationData = computed(() => ({
  current_page: mantenimientosPaginator.value?.current_page || 1,
  last_page: mantenimientosPaginator.value?.last_page || 1,
  per_page: mantenimientosPaginator.value?.per_page || 10,
  from: mantenimientosPaginator.value?.from || 0,
  to: mantenimientosPaginator.value?.to || 0,
  total: mantenimientosPaginator.value?.total || 0,
  prev_page_url: mantenimientosPaginator.value?.prev_page_url,
  next_page_url: mantenimientosPaginator.value?.next_page_url,
  links: mantenimientosPaginator.value?.links || []
}))

const handlePerPageChange = (newPerPage) => {
  router.get(route('mantenimientos.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('mantenimientos.index'), {
    ...props.filters,
    ...props.sorting,
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

const obtenerClasesEstado = (mantenimiento) => {
  const estado = mantenimiento.estado
  const diasRestantes = mantenimiento.dias_restantes

  // Opción B: Lógica basada en fecha de próximo mantenimiento
  if (estado === 'completado') {
    if (diasRestantes !== null && diasRestantes <= 0) {
      // Completado pero próximo mantenimiento vencido
      return 'bg-red-100 text-red-700'
    } else if (diasRestantes !== null && diasRestantes <= 7) {
      // Completado pero próximo mantenimiento próximo
      return 'bg-orange-100 text-orange-700'
    } else {
      // Completado y próximo mantenimiento en orden
      return 'bg-green-100 text-green-700'
    }
  }

  // Estados legacy por si acaso
  const clases = {
    'completado': 'bg-green-100 text-green-700',
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'en_proceso': 'bg-blue-100 text-blue-700'
  }
  return clases[estado] || 'bg-gray-100 text-gray-700'
}

const obtenerLabelEstado = (mantenimiento) => {
  const estado = mantenimiento.estado
  const diasRestantes = mantenimiento.dias_restantes

  // Opción B: Lógica basada en fecha de próximo mantenimiento
  if (estado === 'completado') {
    if (diasRestantes === null) {
      return 'Completado'
    } else if (diasRestantes <= 0) {
      return 'Vencido'
    } else if (diasRestantes <= 7) {
      return 'Próximo'
    } else {
      return 'Programado'
    }
  }

  // Estados legacy por si acaso
  const labels = {
    'completado': 'Completado',
    'pendiente': 'Pendiente',
    'en_proceso': 'En Proceso'
  }
  return labels[estado] || 'Pendiente'
}

const obtenerClasesPrioridad = (prioridad) => {
  const clases = {
    'baja': 'bg-green-100 text-green-700 border-green-200',
    'media': 'bg-blue-100 text-blue-700 border-blue-200',
    'alta': 'bg-orange-100 text-orange-700 border-orange-200',
    'critica': 'bg-red-100 text-red-700 border-red-200'
  }
  return clases[prioridad] || 'bg-gray-100 text-gray-700 border-gray-200'
}

const obtenerLabelPrioridad = (prioridad) => {
  const labels = {
    'baja': 'Baja',
    'media': 'Media',
    'alta': 'Alta',
    'critica': 'Crítica'
  }
  return labels[prioridad] || 'Media'
}

const obtenerClasesUrgencia = (mantenimiento) => {
  const diasRestantes = mantenimiento.dias_restantes
  const prioridad = mantenimiento.prioridad

  // Opción B: Lógica simplificada basada en días restantes
  if (diasRestantes === null) {
    // No hay fecha de próximo mantenimiento programada
    return 'bg-gray-100 text-gray-700 border-gray-200'
  }

  if (diasRestantes <= 0 || prioridad === 'critica') {
    return 'bg-red-100 text-red-700 border-red-200'
  }

  if (diasRestantes <= 3 || prioridad === 'alta') {
    return 'bg-red-100 text-red-700 border-red-200'
  }

  if (diasRestantes <= 7) {
    return 'bg-orange-100 text-orange-700 border-orange-200'
  }

  if (diasRestantes <= 15) {
    return 'bg-yellow-100 text-yellow-700 border-yellow-200'
  }

  return 'bg-green-100 text-green-700 border-green-200'
}

const obtenerIconoUrgencia = (mantenimiento) => {
  const diasRestantes = mantenimiento.dias_restantes
  const prioridad = mantenimiento.prioridad

  // Opción B: Lógica simplificada
  if (diasRestantes === null) {
    return '📋'
  }

  if (diasRestantes <= 0 || prioridad === 'critica') {
    return '⚠️'
  }

  if (diasRestantes <= 7 || prioridad === 'alta') {
    return '⚡'
  }

  if (diasRestantes <= 15) {
    return '🔔'
  }

  return '✅'
}

const obtenerTextoUrgencia = (mantenimiento) => {
  const diasRestantes = mantenimiento.dias_restantes

  if (diasRestantes === null) {
    return 'Sin programar'
  }

  if (diasRestantes <= 0) {
    return 'Vencido'
  }

  if (diasRestantes <= 7) {
    return 'Urgente'
  }

  if (diasRestantes <= 15) {
    return 'Próximo'
  }

  return 'Normal'
}
</script>

<template>
  <div>
    <Head title="Mantenimientos" />
    <div class="mantenimientos-index min-h-screen bg-gray-50">
    <div class="max-w-8xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm mb-6 overflow-hidden">
        <!-- Header Principal -->
        <div class="px-8 py-6 border-b border-slate-100">
          <div class="flex flex-col lg:flex-row gap-6 items-start lg:items-center justify-between">
            <!-- Título y acciones principales -->
            <div class="flex items-center justify-between w-full lg:w-auto">
              <div class="flex items-center gap-4">
                <div class="bg-blue-100 p-3 rounded-xl">
                  <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-slate-900">Mantenimientos</h1>
                  <p class="text-slate-600 mt-1">Gestión y seguimiento de mantenimientos de vehículos</p>
                </div>
              </div>

              <!-- Botones de acción -->
              <div class="flex items-center gap-3">
                <button
                  @click="limpiarFiltros"
                  class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-50 text-gray-700 rounded-lg hover:bg-gray-100 transition-all duration-200 border border-gray-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  <span class="text-sm font-medium">Ver Todos</span>
                </button>

                <button
                  @click="exportMantenimientos"
                  class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-all duration-200 border border-green-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                  </svg>
                  <span class="text-sm font-medium">Exportar</span>
                </button>

                <Link
                  :href="route('mantenimientos.create')"
                  class="inline-flex items-center gap-2.5 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  <span>{{ headerConfig.createButtonText }}</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Estadísticas -->
        <div class="px-8 py-6 bg-slate-50/50 border-b border-slate-100">
          <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Resumen General</h3>
            <p class="text-sm text-slate-600">Vista general del estado de todos los mantenimientos</p>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <!-- Total -->
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Total</p>
                  <p class="text-2xl font-bold text-slate-900">{{ formatNumber(estadisticas.total) }}</p>
                </div>
                <div class="bg-slate-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Completados -->
            <div class="bg-white p-4 rounded-lg border border-green-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Completados</p>
                  <p class="text-2xl font-bold text-green-600">{{ formatNumber(estadisticas.completados) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-green-500 transition-all duration-300"
                        :style="{ width: estadisticas.completadosPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-green-600 font-medium">{{ estadisticas.completadosPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-green-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Pendientes -->
            <div class="bg-white p-4 rounded-lg border border-yellow-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Pendientes</p>
                  <p class="text-2xl font-bold text-yellow-600">{{ formatNumber(estadisticas.pendientes) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-yellow-500 transition-all duration-300"
                        :style="{ width: estadisticas.pendientesPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-yellow-600 font-medium">{{ estadisticas.pendientesPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-yellow-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- En Proceso -->
            <div class="bg-white p-4 rounded-lg border border-blue-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">En Proceso</p>
                  <p class="text-2xl font-bold text-blue-600">{{ formatNumber(estadisticas.en_proceso) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-blue-500 transition-all duration-300"
                        :style="{ width: estadisticas.enProcesoPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-blue-600 font-medium">{{ estadisticas.enProcesoPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-blue-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Vencidos (basado en reglas de negocio) -->
            <div class="bg-white p-4 rounded-lg border border-red-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Vencidos</p>
                  <p class="text-2xl font-bold text-red-600">{{ formatNumber(estadisticas.vencidos) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-red-500 transition-all duration-300"
                        :style="{ width: estadisticas.vencidosPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-red-600 font-medium">{{ estadisticas.vencidosPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-red-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Por Vencer (basado en reglas de negocio) -->
            <div class="bg-white p-4 rounded-lg border border-orange-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Por Vencer</p>
                  <p class="text-2xl font-bold text-orange-600">{{ formatNumber(estadisticas.por_vencer) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-orange-500 transition-all duration-300"
                        :style="{ width: estadisticas.porVencerPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-orange-600 font-medium">{{ estadisticas.porVencerPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-orange-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Al Día (basado en reglas de negocio) -->
            <div class="bg-white p-4 rounded-lg border border-blue-200 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-slate-600">Al Día</p>
                  <p class="text-2xl font-bold text-blue-600">{{ formatNumber(estadisticas.al_dia) }}</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div
                        class="h-full bg-blue-500 transition-all duration-300"
                        :style="{ width: estadisticas.alDiaPorcentaje + '%' }"
                      ></div>
                    </div>
                    <span class="text-xs text-blue-600 font-medium">{{ estadisticas.alDiaPorcentaje }}%</span>
                  </div>
                </div>
                <div class="bg-blue-100 p-2 rounded-lg">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="px-8 py-6">
          <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-800 mb-2">Filtros y Búsqueda</h3>
            <p class="text-sm text-slate-600">Filtra y busca mantenimientos específicos</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2 lg:col-span-3 xl:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-2">Búsqueda</label>
              <div class="relative">
                <input
                  v-model="searchTerm"
                  @input="handleSearchChange($event.target.value)"
                  type="text"
                  :placeholder="headerConfig.searchPlaceholder"
                  class="w-full pl-4 pr-10 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
                />
                <svg class="absolute right-3 top-3.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>

            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Estado</label>
              <select
                v-model="filtroEstado"
                @change="handleEstadoChange($event.target.value)"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              >
                <option value="">Todos los Estados</option>
                <option value="completado">✅ Completado</option>
                <option value="pendiente">⏳ Pendiente</option>
                <option value="en_proceso">🔄 En Proceso</option>
              </select>
            </div>

            <!-- Tipo -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
              <select
                v-model="filtroTipo"
                @change="handleTipoChange($event.target.value)"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              >
                <option value="">Todos los Tipos</option>
                <option v-for="tipo in props.tiposMantenimiento" :key="tipo" :value="tipo">{{ tipo }}</option>
              </select>
            </div>

            <!-- Vehículo -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Vehículo</label>
              <select
                v-model="filtroCarro"
                @change="handleCarroChange($event.target.value)"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              >
                <option value="">Todos los Vehículos</option>
                <option v-for="carro in props.carros" :key="carro.id" :value="carro.id">
                  {{ carro.marca }} {{ carro.modelo }}
                </option>
              </select>
            </div>

            <!-- Prioridad -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Prioridad</label>
              <select
                v-model="filtroPrioridad"
                @change="handlePrioridadChange($event.target.value)"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              >
                <option value="">Todas las Prioridades</option>
                <option value="baja">🟢 Baja</option>
                <option value="media">🔵 Media</option>
                <option value="alta">🟠 Alta</option>
                <option value="critica">🔴 Crítica</option>
              </select>
            </div>

            <!-- Orden -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Ordenar por</label>
              <select
                v-model="sortBy"
                @change="handleSortChange($event.target.value)"
                class="w-full px-4 py-3 border border-slate-300 rounded-lg bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              >
                <option value="fecha-desc">📅 Fecha Más Reciente</option>
                <option value="fecha-asc">📅 Fecha Más Antigua</option>
                <option value="tipo-asc">🔤 Tipo A-Z</option>
                <option value="tipo-desc">🔤 Tipo Z-A</option>
                <option value="costo-desc">💰 Costo Mayor</option>
                <option value="costo-asc">💰 Costo Menor</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vehículo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Próximo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prioridad</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Urgencia</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Costo</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="(mantenimiento, index) in mantenimientosDocumentos" :key="mantenimiento.id" :class="[
                'hover:bg-gray-50 transition-colors duration-150',
                mantenimiento.raw.dias_restantes <= 0 ? 'bg-red-50' : '',
                mantenimiento.raw.prioridad === 'critica' ? 'border-l-4 border-l-red-500' : '',
                mantenimiento.raw.prioridad === 'alta' ? 'border-l-4 border-l-orange-500' : ''
              ]">
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(mantenimiento.fecha) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ mantenimiento.raw.carro ? mantenimiento.raw.carro.marca + ' ' + mantenimiento.raw.carro.modelo : 'N/A' }}</div>
                  <div class="text-sm text-gray-500">{{ mantenimiento.raw.carro?.placas || '' }}</div>
                  <div v-if="mantenimiento.raw.kilometraje_actual" class="text-xs text-gray-400">
                    {{ formatNumber(mantenimiento.raw.kilometraje_actual) }} km
                  </div>
                  <!-- Debug: mostrar ID para rastreo -->
                  <div class="text-xs text-purple-500">ID: {{ mantenimiento.id }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ mantenimiento.titulo }}</div>
                  <div v-if="mantenimiento.raw.estado === 'completado'" class="text-xs text-green-600 mt-1">
                    ✓ Servicio realizado
                  </div>
                  <div v-else-if="mantenimiento.raw.estado === 'pendiente'" class="text-xs text-blue-600 mt-1">
                    ⏳ Programado
                  </div>
                  <div v-if="mantenimiento.raw.notas && mantenimiento.raw.notas.includes('automáticamente')" class="text-xs text-purple-600 mt-1">
                    🤖 Generado automáticamente
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ formatearFecha(mantenimiento.raw.proximo_mantenimiento) }}</div>
                  <div v-if="mantenimiento.raw.dias_restantes !== null && mantenimiento.raw.dias_restantes !== undefined" class="text-xs mt-1" :class="mantenimiento.raw.dias_restantes <= 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
                    {{ mantenimiento.raw.dias_restantes <= 0 ? `${Math.round(Math.abs(mantenimiento.raw.dias_restantes))} días vencido` : `${Math.round(mantenimiento.raw.dias_restantes)} días restantes` }}
                  </div>
                  <div v-else class="text-xs mt-1 text-gray-400">
                    Sin fecha de próximo mantenimiento
                  </div>
                  <div v-if="mantenimiento.raw.estado === 'completado'" class="text-xs mt-1 text-green-600 font-medium">
                    ✓ Servicio realizado
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerClasesPrioridad(mantenimiento.raw.prioridad)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ obtenerLabelPrioridad(mantenimiento.raw.prioridad) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <span class="text-lg">{{ obtenerIconoUrgencia(mantenimiento.raw) }}</span>
                    <span :class="obtenerClasesUrgencia(mantenimiento.raw)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ obtenerTextoUrgencia(mantenimiento.raw) }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div v-if="mantenimiento.raw.costo && mantenimiento.raw.costo > 0" class="text-sm font-medium text-gray-900">
                    ${{ formatNumber(mantenimiento.raw.costo) }}
                  </div>
                  <div v-else class="text-sm text-gray-400 italic">
                    Pendiente
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex flex-col gap-1">
                    <!-- Estado basado en reglas de negocio -->
                    <span :class="obtenerEstadoMantenimiento(mantenimiento.raw).clase" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                      {{ obtenerEstadoMantenimiento(mantenimiento.raw).descripcion }}
                    </span>
                    <!-- Información adicional -->
                    <div v-if="obtenerEstadoMantenimiento(mantenimiento.raw).estado === 'completado'" class="text-xs text-green-600">
                      Completado: {{ formatearFecha(mantenimiento.raw.fecha) }}
                    </div>
                    <div v-else class="text-xs text-gray-500">
                      {{ formatearFecha(mantenimiento.raw.proximo_mantenimiento) }}
                    </div>
                    <!-- Indicador de días restantes -->
                    <div v-if="mantenimiento.raw.dias_restantes !== null" class="text-xs" :class="mantenimiento.raw.dias_restantes <= 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
                      {{ mantenimiento.raw.dias_restantes <= 0 ? `${Math.round(Math.abs(mantenimiento.raw.dias_restantes))} días vencido` : `${Math.round(mantenimiento.raw.dias_restantes)} días restantes` }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="verDetalles(mantenimiento)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarMantenimiento(mantenimiento.id)" class="w-8 h-8 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors duration-150" title="Editar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button
                      v-if="mantenimiento.raw.carro?.id"
                      @click="verHistorialVehiculo(mantenimiento.raw.carro.id)"
                      class="w-8 h-8 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition-colors duration-150"
                      title="Ver historial del vehículo"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>
                    <button
                      v-else
                      class="w-8 h-8 bg-gray-50 text-gray-400 rounded-lg cursor-not-allowed"
                      title="Vehículo no disponible"
                      disabled
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </button>
                    <!-- Botón para completar mantenimiento (acciones rápidas) -->
                    <button
                      v-if="mantenimiento.raw.estado !== 'completado'"
                      @click="completarMantenimientoRapido(mantenimiento.raw)"
                      class="w-8 h-8 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-150"
                      title="Completar mantenimiento"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>

                    <!-- Botón para posponer mantenimiento (si está próximo) -->
                    <button
                      v-if="obtenerEstadoMantenimiento(mantenimiento.raw).estado === 'por_vencer'"
                      @click="posponerMantenimientoRapido(mantenimiento.raw, 30)"
                      class="w-8 h-8 bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100 transition-colors duration-150"
                      title="Posponer 30 días"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </button>

                    <!-- Botón para reprogramar mantenimiento (si está vencido) -->
                    <button
                      v-if="obtenerEstadoMantenimiento(mantenimiento.raw).estado === 'vencido'"
                      @click="abrirModalReprogramar(mantenimiento)"
                      class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150"
                      title="Reprogramar mantenimiento"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>

                    <!-- Botón para reprogramar mantenimientos completados (agregar siguiente servicio) -->
                    <button
                      v-if="mantenimiento.raw.estado === 'completado'"
                      @click="abrirModalReprogramar(mantenimiento)"
                      class="w-8 h-8 bg-indigo-50 text-amber-600 rounded-lg hover:bg-indigo-100 transition-colors duration-150"
                      title="Programar siguiente mantenimiento"
                    >
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                    </button>

                    <button @click="confirmarEliminacion(mantenimiento.id)" class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="mantenimientosDocumentos.length === 0">
                <td colspan="9" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay mantenimientos</p>
                      <p class="text-sm text-gray-500">Los mantenimientos aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700">
                Mostrando {{ paginationData.from }} - {{ paginationData.to }} de {{ paginationData.total }} resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white"
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
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === paginationData.currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal de Historial del Vehículo -->
      <div v-if="showHistorialModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showHistorialModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Historial de Mantenimientos</h3>
              <p class="text-sm text-gray-600 mt-1" v-if="historialVehiculo">
                {{ historialVehiculo.marca }} {{ historialVehiculo.modelo }}
                <span class="text-gray-400">•</span>
                Placas: {{ historialVehiculo.placas || 'N/A' }}
              </p>
            </div>
            <button @click="cerrarHistorialModal" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="historialMantenimientos.length === 0" class="text-center py-8">
              <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <p class="text-gray-700 font-medium">No hay mantenimientos registrados</p>
              <p class="text-sm text-gray-500">Este vehículo aún no tiene mantenimientos en el sistema</p>
            </div>

            <div v-else class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-blue-600">Total Mantenimientos</p>
                      <p class="text-2xl font-bold text-blue-900">{{ historialMantenimientos.length }}</p>
                    </div>
                    <div class="bg-blue-100 p-2 rounded-lg">
                      <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                  </div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-green-600">Completados</p>
                      <p class="text-2xl font-bold text-green-900">{{ historialMantenimientos.filter(m => m.estado === 'completado').length }}</p>
                    </div>
                    <div class="bg-green-100 p-2 rounded-lg">
                      <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                  </div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-yellow-600">Costo Total</p>
                      <p class="text-2xl font-bold text-yellow-900">${{ formatNumber(historialMantenimientos.reduce((sum, m) => sum + (m.costo || 0), 0)) }}</p>
                    </div>
                    <div class="bg-yellow-100 p-2 rounded-lg">
                      <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Lista de mantenimientos -->
              <div class="space-y-3">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Lista de Mantenimientos</h4>
                <div v-for="mantenimiento in historialMantenimientos" :key="mantenimiento.id" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-3 mb-2">
                        <h5 class="font-medium text-gray-900">{{ mantenimiento.tipo }}</h5>
                        <span :class="obtenerClasesEstado(mantenimiento.estado)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                          {{ obtenerLabelEstado(mantenimiento.estado) }}
                        </span>
                        <span v-if="mantenimiento.prioridad" :class="obtenerClasesPrioridad(mantenimiento.prioridad)" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                          {{ obtenerLabelPrioridad(mantenimiento.prioridad) }}
                        </span>
                      </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600">
                        <div>
                          <span class="font-medium">Fecha:</span>
                          {{ formatearFecha(mantenimiento.fecha) }}
                        </div>
                        <div>
                          <span class="font-medium">Próximo:</span>
                          {{ formatearFecha(mantenimiento.proximo_mantenimiento) }}
                        </div>
                        <div>
                          <span class="font-medium">Kilometraje:</span>
                          {{ formatNumber(mantenimiento.kilometraje_actual || 0) }} km
                        </div>
                        <div>
                          <span class="font-medium">Costo:</span>
                          ${{ formatNumber(mantenimiento.costo || 0) }}
                        </div>
                      </div>

                      <div v-if="mantenimiento.descripcion" class="mt-2 text-sm text-gray-700">
                        <span class="font-medium">Descripción:</span>
                        {{ mantenimiento.descripcion }}
                      </div>

                      <div v-if="mantenimiento.notas" class="mt-2 text-sm text-gray-700">
                        <span class="font-medium">Notas:</span>
                        {{ mantenimiento.notas }}
                      </div>
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                      <button @click="verDetalles(mantenimiento)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Ver detalles">
                        <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="cerrarHistorialModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cerrar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal mejorado -->
      <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showModal = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'details' ? 'Detalles del Mantenimiento' : 'Confirmar Eliminación' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div v-if="modalMode === 'details' && selectedMantenimiento">
              <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Vehículo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">
                        {{ selectedMantenimiento.carro ? selectedMantenimiento.carro.marca + ' ' + selectedMantenimiento.carro.modelo : 'N/A' }}
                      </p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Tipo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ selectedMantenimiento.tipo }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedMantenimiento.fecha) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Próximo Mantenimiento</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedMantenimiento.proximo_mantenimiento) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Estado</label>
                      <span :class="obtenerClasesEstado(selectedMantenimiento.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1">
                        {{ obtenerLabelEstado(selectedMantenimiento.estado) }}
                      </span>
                    </div>
                  </div>
                  <div class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Kilometraje Actual</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatNumber(selectedMantenimiento.kilometraje_actual || 0) }} km</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Costo</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">${{ formatNumber(selectedMantenimiento.costo || 0) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Fecha de Creación</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedMantenimiento.created_at) }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Última Actualización</label>
                      <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md">{{ formatearFecha(selectedMantenimiento.updated_at) }}</p>
                    </div>
                  </div>
                </div>
                <div v-if="selectedMantenimiento.descripcion">
                  <label class="block text-sm font-medium text-gray-700">Descripción</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedMantenimiento.descripcion }}</p>
                </div>
                <div v-if="selectedMantenimiento.notas">
                  <label class="block text-sm font-medium text-gray-700">Notas</label>
                  <p class="mt-1 text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-md whitespace-pre-wrap">{{ selectedMantenimiento.notas }}</p>
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
                <h3 class="text-lg font-medium text-gray-900 mb-2">¿Eliminar Mantenimiento?</h3>
                <p class="text-sm text-gray-500 mb-4">
                  ¿Estás seguro de que deseas eliminar el mantenimiento <strong>{{ selectedMantenimiento?.tipo }}</strong>?
                  Esta acción no se puede deshacer.
                </p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="showModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
            </button>
            <div v-if="modalMode === 'details'" class="flex gap-2">
              <button @click="editarMantenimiento(selectedMantenimiento.id)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors">
                Editar
              </button>
            </div>
            <button v-if="modalMode === 'confirm'" @click="eliminarMantenimiento" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal para Reprogramar Mantenimiento -->
      <div v-if="showReprogramarModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="cerrarModalReprogramar">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <div>
              <h3 class="text-lg font-medium text-gray-900">
                {{ mantenimientoAReprogramar?.raw.estado === 'completado' ? 'Programar Siguiente Mantenimiento' : 'Reprogramar Mantenimiento' }}
              </h3>
              <p class="text-sm text-gray-600 mt-1" v-if="mantenimientoAReprogramar">
                {{ mantenimientoAReprogramar.titulo }} - {{ mantenimientoAReprogramar.raw.carro?.marca }} {{ mantenimientoAReprogramar.raw.carro?.modelo }}
              </p>
              <p class="text-xs text-gray-500 mt-1" v-if="mantenimientoAReprogramar?.raw.estado === 'completado'">
                Servicio completado: {{ formatearFecha(mantenimientoAReprogramar.raw.fecha) }}
              </p>
              <p class="text-xs text-gray-500 mt-1" v-else>
                Último servicio realizado: {{ formatearFecha(mantenimientoAReprogramar.raw.fecha) }}
              </p>
            </div>
            <button @click="cerrarModalReprogramar" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <form @submit.prevent="reprogramarMantenimiento" class="space-y-4">
              <!-- Costo del servicio -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Costo del Último Servicio <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                  </div>
                  <input
                    v-model.number="costoReprogramar"
                    type="number"
                    step="0.01"
                    min="0"
                    max="999999.99"
                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                    placeholder="0.00"
                    required
                  />
                </div>
                <p class="text-xs text-gray-500 mt-1">Ingrese el costo total del mantenimiento realizado</p>
              </div>

              <!-- Fecha del próximo mantenimiento -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Fecha del Próximo Mantenimiento <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="proximaFecha"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
                  :min="new Date().toISOString().split('T')[0]"
                  required
                />
                <p class="text-xs text-gray-500 mt-1">Seleccione cuándo debe realizarse el próximo mantenimiento</p>
              </div>

              <!-- Información del mantenimiento actual -->
              <div v-if="mantenimientoAReprogramar" class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Información del Mantenimiento</h4>
                <div class="space-y-1 text-sm text-gray-600">
                  <div><strong>Vehículo:</strong> {{ mantenimientoAReprogramar.raw.carro?.marca }} {{ mantenimientoAReprogramar.raw.carro?.modelo }}</div>
                  <div><strong>Tipo:</strong> {{ mantenimientoAReprogramar.titulo }}</div>
                  <div><strong>Fecha del servicio:</strong> {{ formatearFecha(mantenimientoAReprogramar.raw.fecha) }}</div>
                  <div v-if="mantenimientoAReprogramar.raw.kilometraje_actual">
                    <strong>Kilometraje:</strong> {{ formatNumber(mantenimientoAReprogramar.raw.kilometraje_actual) }} km
                  </div>
                </div>
              </div>

              <!-- Botones -->
              <div class="flex justify-end gap-3 pt-4">
                <button
                  type="button"
                  @click="cerrarModalReprogramar"
                  class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  {{ mantenimientoAReprogramar?.raw.estado === 'completado' ? 'Programar Siguiente' : 'Reprogramar Mantenimiento' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</template>

<style scoped>
@import "tailwindcss" reference;
.mantenimientos-index {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>




