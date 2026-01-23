<script setup>
import { ref, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3' // Import router
import { notyf } from '@/Utils/notyf'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import axios from 'axios'

const loading = ref(true)
const folios = ref([])

onMounted(async () => {
    await loadFolios()
})

const loadFolios = async () => {
    try {
        loading.value = true
        const response = await axios.get(route('folios.config.index'))
        folios.value = response.data
    } catch (error) {
        console.error(error)
        notyf.error('Error al cargar configuración de folios')
    } finally {
        loading.value = false
    }
}

const updatePrefix = async (folio) => {
    try {
        // Optimistic UI update or wait? Wait is safer.
        await axios.put(route('folios.config.update', folio.id), {
            prefix: folio.prefix,
            padding: folio.padding
        })
        notyf.success('Prefijo actualizado correctamente')
    } catch (error) {
        console.error(error)
        notyf.error(error.response?.data?.message || 'Error al actualizar prefijo')
        // Reload to revert changes
        await loadFolios()
    }
}

const syncSequence = async (folio) => {
    if (!confirm(`¿Estás seguro de sincronizar la secuencia para ${folio.document_type}? Esto buscará el folio más alto existente en la base de datos y actualizará el contador. Usar solo si hay desincronización.`)) {
        return
    }

    try {
        const response = await axios.post(route('folios.config.sync', folio.id))
        notyf.success(response.data.message || 'Secuencia sincronizada')
        // Reload to show new number
        await loadFolios()
    } catch (error) {
        console.error(error)
        notyf.error('Error al sincronizar secuencia')
    }
}

const formatType = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1)
}
</script>

<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white dark:text-gray-100">Configuración de Folios</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                Administra los prefijos y secuencias de los documentos del sistema.
            </p>
        </div>

        <div v-if="loading" class="flex justify-center py-8">
            <FontAwesomeIcon icon="spinner" spin size="2x" class="text-blue-600 dark:text-blue-400" />
        </div>

        <div v-else class="overflow-x-auto border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                <thead class="bg-white dark:bg-slate-900 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Prefijo</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Último Folio Generado</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Siguiente Folio (Estimado)</th>
                         <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                    <tr v-for="folio in folios" :key="folio.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">
                            {{ formatType(folio.document_type) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            <input 
                                type="text" 
                                v-model="folio.prefix" 
                                @change="updatePrefix(folio)"
                                class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-24 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md uppercase bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-900 dark:text-white dark:text-gray-200"
                                maxlength="5"
                            >
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                             {{ folio.current_number }}
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 font-mono">
                             {{ folio.prefix }}{{ String(folio.current_number + 1).padStart(folio.padding, '0') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                            <button 
                                @click="syncSequence(folio)" 
                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-medium flex items-center gap-1"
                                title="Analizar base de datos y corregir secuencia"
                            >
                                <FontAwesomeIcon icon="sync" /> Sincronizar
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <FontAwesomeIcon icon="exclamation-triangle" class="text-yellow-400 dark:text-yellow-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        <strong>Nota:</strong> Al cambiar un prefijo, la secuencia continuará desde el número actual. 
                        Si deseas reiniciar la numeración para el nuevo prefijo, contacta a soporte técnico (requires manual reset). 
                        Usar la opción "Sincronizar" buscará el foliio más alto existente con el prefijo actual y ajustará el contador automáticamente.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
