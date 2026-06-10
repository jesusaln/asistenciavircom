import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

/**
 * Composable for single CFDI actions (SAT check, delete, create provider, etc).
 */
export function useCfdiActions(notyf, parseCfdiXml) {
    const isCheckingSat = ref({})
    const isCreatingProvider = ref({})
    const isSendingEmail = ref({})
    const isDeletingCfdi = ref(false)
    const satStatus = ref({})

    const selectedUuid = ref('')
    const xmlContent = ref('')
    const isLoadingXml = ref(false)
    const parsedCfdiData = ref(null)

    const checkSatStatus = async (cfdi) => {
        isCheckingSat.value[cfdi.id] = true
        try {
            const response = await axios.post(route('cfdi.check-sat', cfdi.id))
            if (response.data.success) {
                satStatus.value[cfdi.id] = {
                    estado: response.data.estado,
                    es_cancelable: response.data.es_cancelable
                }
                notyf.success(`Estado SAT: ${response.data.estado}`)
                if (response.data.estado.toLowerCase() === 'cancelado') {
                    router.reload()
                }
            } else {
                notyf.error(response.data.message || 'Error al consultar SAT')
            }
        } catch (e) {
            notyf.error(e.response?.data?.message || 'Error de conexión con el SAT')
        } finally {
            isCheckingSat.value[cfdi.id] = false
        }
    }

    const createProvider = async (cfdiId) => {
        isCreatingProvider.value[cfdiId] = true
        try {
            const response = await axios.post(route('cfdi.create-provider', cfdiId))
            if (response.data.success) {
                notyf.success('Proveedor creado correctamente')
                router.reload()
                return true
            }
        } catch (e) {
            notyf.error(e.response?.data?.message || 'Error al crear proveedor')
        } finally {
            isCreatingProvider.value[cfdiId] = false
        }
        return false
    }

    const deleteCfdi = async (cfdiId) => {
        isDeletingCfdi.value = true
        try {
            const response = await axios.delete(route('cfdi.destroy', cfdiId))
            if (response.data.success) {
                notyf.success('CFDI eliminado correctamente')
                router.reload()
                return true
            }
        } catch (e) {
            notyf.error(e.response?.data?.message || 'Error al intentar eliminar')
        } finally {
            isDeletingCfdi.value = false
        }
        return false
    }

    const enviarCorreo = async (uuid) => {
        isSendingEmail.value[uuid] = true
        try {
            const response = await axios.post(route('cfdi.enviar-correo', uuid))
            if (response.data.success) {
                notyf.success('Correo enviado correctamente')
                return true
            }
        } catch (e) {
            notyf.error('Error al conectar con el servidor')
        } finally {
            isSendingEmail.value[uuid] = false
        }
        return false
    }

    const fetchXml = async (uuid) => {
        selectedUuid.value = uuid
        xmlContent.value = ''
        parsedCfdiData.value = null
        isLoadingXml.value = true

        try {
            const response = await axios.get(route('cfdi.xml', { uuid, inline: 1 }), {
                responseType: 'text',
                headers: { Accept: 'application/xml' }
            })
            xmlContent.value = typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2)
            parsedCfdiData.value = parseCfdiXml(xmlContent.value)
            return true
        } catch (e) {
            notyf.error('Error al cargar el XML')
        } finally {
            isLoadingXml.value = false
        }
        return false
    }

    return {
        isCheckingSat,
        isCreatingProvider,
        isSendingEmail,
        isDeletingCfdi,
        satStatus,
        selectedUuid,
        xmlContent,
        isLoadingXml,
        parsedCfdiData,
        checkSatStatus,
        createProvider,
        deleteCfdi,
        enviarCorreo,
        fetchXml
    }
}
