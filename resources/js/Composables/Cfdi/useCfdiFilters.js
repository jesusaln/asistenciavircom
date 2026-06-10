import { ref, watch, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Composable for managing CFDI index filters, sorting and pagination.
 */
export function useCfdiFilters(initialFilters = {}, routeName = 'cfdi.index') {
    const filters = ref({
        search: initialFilters.search || '',
        direccion: initialFilters.direccion || 'recibido',
        tipo: initialFilters.tipo || '',
        status: initialFilters.status || '',
        fecha_inicio: initialFilters.fecha_inicio || '',
        fecha_fin: initialFilters.fecha_fin || '',
        sort: initialFilters.sort || 'fecha',
        sort_dir: initialFilters.sort_dir || 'desc',
        ...initialFilters
    })

    const formatDateInput = (date) => {
        if (!date) return ''
        const d = new Date(date)
        let month = '' + (d.getMonth() + 1)
        let day = '' + d.getDate()
        const year = d.getFullYear()

        if (month.length < 2) month = '0' + month
        if (day.length < 2) day = '0' + day

        return [year, month, day].join('-')
    }

    const setQuickRange = (days) => {
        const end = new Date()
        const start = new Date()
        start.setDate(start.getDate() - days)

        filters.value.fecha_inicio = formatDateInput(start)
        filters.value.fecha_fin = formatDateInput(end)
    }

    const setCurrentMonthRange = () => {
        const date = new Date()
        const firstDay = new Date(date.getFullYear(), date.getMonth(), 1)
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0)

        filters.value.fecha_inicio = formatDateInput(firstDay)
        filters.value.fecha_fin = formatDateInput(lastDay)
    }

    const toggleSort = (field) => {
        if (filters.value.sort === field) {
            filters.value.sort_dir = filters.value.sort_dir === 'asc' ? 'desc' : 'asc'
        } else {
            filters.value.sort = field
            filters.value.sort_dir = 'desc'
        }
    }

    const handlePageChange = (page) => {
        router.get(route(routeName), { ...filters.value, page }, {
            preserveState: true,
            preserveScroll: true
        })
    }

    // Debounced search
    let timeout = null
    watch(filters, (newFilters) => {
        if (timeout) clearTimeout(timeout)
        timeout = setTimeout(() => {
            router.get(route(routeName), newFilters, {
                preserveState: true,
                replace: true
            })
        }, 500)
    }, { deep: true })

    onUnmounted(() => {
        if (timeout) clearTimeout(timeout)
    })

    const getSortIndicator = (field) => {
        if (filters.value.sort !== field) return ''
        return filters.value.sort_dir === 'asc' ? '↑' : '↓'
    }

    return {
        filters,
        setQuickRange,
        setCurrentMonthRange,
        toggleSort,
        handlePageChange,
        formatDateInput,
        getSortIndicator
    }
}
