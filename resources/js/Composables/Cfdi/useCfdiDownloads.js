import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from '@/Utils/Swal'

/**
 * Composable for managing CFDI massive downloads and polling.
 */
export function useCfdiDownloads(props, notyf) {
    const isDeletingDescarga = ref({})
    const isRevalidatingDescarga = ref({})
    const descargaSending = ref(false)
    const isLoadingReview = ref(false)
    const isImportingSeleccionados = ref(false)

    const descargaForm = ref({
        direccion: 'recibido',
        fecha_inicio: '',
        fecha_fin: ''
    })

    const documentosStaging = ref([])
    const duplicadosStaging = ref([])
    const selectedStagingIds = ref([])
    const selectedDescargaParaReview = ref(null)

    const descargasItems = computed(() => {
        if (!Array.isArray(props.descargasMasivas)) {
            return []
        }
        return props.descargasMasivas.filter(Boolean)
    })

    const tieneDescargasActivas = computed(() => {
        return descargasItems.value.some(d => ['solicitando', 'pendiente', 'verificando', 'descargando'].includes(d.status))
    })

    let pollingInterval = null

    const startPolling = () => {
        if (pollingInterval) return
        pollingInterval = setInterval(() => {
            if (tieneDescargasActivas.value) {
                router.reload({
                    only: ['descargasMasivas'],
                    preserveState: true,
                    preserveScroll: true
                })
            } else {
                stopPolling()
            }
        }, 15000)
    }

    const stopPolling = () => {
        if (pollingInterval) {
            clearInterval(pollingInterval)
            pollingInterval = null
        }
    }

    const solicitarDescarga = async () => {
        if (!descargaForm.value.fecha_inicio || !descargaForm.value.fecha_fin) {
            notyf.error('Selecciona un rango de fechas')
            return
        }

        descargaSending.value = true
        try {
            const response = await axios.post(route('cfdi.descarga-masiva'), descargaForm.value)
            if (response.data.success) {
                notyf.success(response.data.message || 'Solicitud enviada')
                return true
            } else {
                notyf.error(response.data.message || 'Error al solicitar descarga')
            }
        } catch (e) {
            notyf.error(e.response?.data?.message || 'Error al solicitar descarga')
        } finally {
            descargaSending.value = false
        }
        return false
    }

    const verificarDescarga = async (id) => {
        try {
            const response = await axios.post(route('cfdi.descarga-masiva.verificar', id))
            if (response.data.success) {
                notyf.success(response.data.message || 'Consulta en proceso')
                router.reload({ preserveState: true })
            } else {
                notyf.error(response.data.message || 'Error al consultar')
            }
        } catch (e) {
            notyf.error(e.response?.data?.message || 'Error al consultar')
        }
    }

    const eliminarDescarga = async (id) => {
        const result = await Swal.fire({
            title: 'Eliminar descarga',
            text: '¿Seguro que deseas eliminar el registro de esta descarga?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#ef4444',
        })

        if (!result.isConfirmed) return
        isDeletingDescarga.value[id] = true
        try {
            const response = await axios.delete(route('cfdi.descarga-masiva.destroy', id))
            if (response.data.success) {
                notyf.success('Descarga eliminada')
                router.reload()
            }
        } catch (e) {
            notyf.error('Error al eliminar')
        } finally {
            isDeletingDescarga.value[id] = false
        }
    }

    const reintentarDescargaManual = async (descarga) => {
        const confirmacion = await Swal.fire({
            title: '¿Reintentar descarga?',
            html: `<div class="text-left text-sm"><p class="text-gray-600 mb-3">Esto intentará nuevamente la descarga del SAT.</p></div>`,
            showCancelButton: true,
            confirmButtonText: 'Sí, reintentar',
            cancelButtonText: 'Cancelar'
        })

        if (confirmacion.isConfirmed) {
            try {
                const response = await axios.post(route('cfdi.descarga-masiva.reintentar', descarga.id))
                if (response.data.success) {
                    notyf.success('Reintento iniciado')
                    router.reload()
                }
            } catch (e) {
                notyf.error('Error al reintentar')
            }
        }
    }

    const abrirRevisor = async (descarga) => {
        selectedDescargaParaReview.value = descarga
        documentosStaging.value = []
        duplicadosStaging.value = []
        selectedStagingIds.value = []
        isLoadingReview.value = true

        try {
            const response = await axios.get(route('cfdi.descarga-masiva.detalles', descarga.id))
            if (response.data.success) {
                const detalles = Array.isArray(response.data.detalles) ? response.data.detalles.filter(Boolean) : []
                const duplicados = Array.isArray(response.data.duplicados) ? response.data.duplicados.filter(Boolean) : []
                documentosStaging.value = detalles
                duplicadosStaging.value = duplicados
                selectedStagingIds.value = detalles.filter(d => !d.importado).map(d => d.id)
                return true
            }
        } catch (e) {
            notyf.error('Error al cargar detalles de la descarga')
        } finally {
            isLoadingReview.value = false
        }
        return false
    }

    const toggleSeleccionStaging = (id) => {
        const index = selectedStagingIds.value.indexOf(id)
        if (index > -1) selectedStagingIds.value.splice(index, 1)
        else selectedStagingIds.value.push(id)
    }

    const importarSeleccionados = async () => {
        if (selectedStagingIds.value.length === 0) {
            notyf.error('Selecciona al menos un documento para importar')
            return false
        }

        isImportingSeleccionados.value = true
        try {
            const response = await axios.post(route('cfdi.descarga-masiva.importar'), {
                ids: selectedStagingIds.value
            })
            if (response.data.success) {
                notyf.success(response.data.message)
                router.reload()
                return true
            }
        } catch (e) {
            notyf.error('Error al importar documentos')
        } finally {
            isImportingSeleccionados.value = false
        }
        return false
    }

    onMounted(() => {
        if (tieneDescargasActivas.value) startPolling()
    })

    onUnmounted(() => stopPolling())

    watch(tieneDescargasActivas, (newVal) => {
        if (newVal) startPolling()
        else stopPolling()
    })

    return {
        descargaForm,
        descargaSending,
        descargasItems,
        tieneDescargasActivas,
        isDeletingDescarga,
        isRevalidatingDescarga,
        documentosStaging,
        duplicadosStaging,
        selectedStagingIds,
        selectedDescargaParaReview,
        isLoadingReview,
        isImportingSeleccionados,
        solicitarDescarga,
        verificarDescarga,
        eliminarDescarga,
        reintentarDescargaManual,
        abrirRevisor,
        toggleSeleccionStaging,
        importarSeleccionados
    }
}
