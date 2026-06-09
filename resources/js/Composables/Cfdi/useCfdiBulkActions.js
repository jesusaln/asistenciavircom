import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

/**
 * Composable for managing bulk actions on CFDIs.
 */
export function useCfdiBulkActions(cfdiItems, notyf) {
    const selectedIds = ref([])
    const isBulkProcessing = ref(false)
    const isBulkDownloading = ref(false)

    const toggleSelectAll = (e) => {
        if (e.target.checked) {
            selectedIds.value = cfdiItems.value.map(c => c.id)
        } else {
            selectedIds.value = []
        }
    }

    const toggleSelect = (id) => {
        const index = selectedIds.value.indexOf(id)
        if (index > -1) {
            selectedIds.value.splice(index, 1)
        } else {
            selectedIds.value.push(id)
        }
    }

    const isSelected = (id) => selectedIds.value.includes(id)

    const bulkCheckSat = async () => {
        if (!selectedIds.value.length) return
        isBulkProcessing.value = true
        try {
            const response = await axios.post(route('cfdi.bulk-check-sat'), { ids: selectedIds.value })
            if (response.data.success) {
                notyf.success(response.data.message)
                selectedIds.value = []
                router.reload()
            }
        } catch (e) {
            notyf.error('Error al procesar consulta masiva')
        } finally {
            isBulkProcessing.value = false
        }
    }

    const bulkSendEmail = async () => {
        if (!selectedIds.value.length) return
        if (!confirm(`¿Deseas enviar ${selectedIds.value.length} comprobantes por correo?`)) return

        isBulkProcessing.value = true
        try {
            const response = await axios.post(route('cfdi.bulk-send-email'), { ids: selectedIds.value })
            if (response.data.success) {
                notyf.success(response.data.message)
                selectedIds.value = []
            }
        } catch (e) {
            notyf.error('Error al enviar correos masivos')
        } finally {
            isBulkProcessing.value = false
        }
    }

    const bulkDownloadZip = async () => {
        if (!selectedIds.value.length) return
        if (selectedIds.value.length > 200) {
            notyf.error('Selecciona como máximo 200 CFDI por ZIP')
            return
        }
        isBulkDownloading.value = true

        try {
            const response = await axios.post(route('cfdi.bulk-download'), { ids: selectedIds.value })
            if (response.data.success && response.data.url) {
                window.open(response.data.url, '_blank', 'noopener,noreferrer')
                const name = response.data.filename || 'cfdis.zip'
                notyf.success(`Descarga: ${name}`)
                selectedIds.value = []
            } else {
                notyf.error(response.data.message || 'Error al generar ZIP')
            }
        } catch (e) {
            const msg = e.response?.data?.message
            notyf.error(msg || 'Error al solicitar descarga ZIP')
        } finally {
            isBulkDownloading.value = false
        }
    }

    return {
        selectedIds,
        isBulkProcessing,
        isBulkDownloading,
        toggleSelectAll,
        toggleSelect,
        isSelected,
        bulkCheckSat,
        bulkSendEmail,
        bulkDownloadZip
    }
}
