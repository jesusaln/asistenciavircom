export const formatMoney = (val) => {
    const n = parseFloat(val)
    return isNaN(n) ? (val || '-') : n.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' })
}

export const formatDateShort = (date) => {
    if (!date) return '---'
    const dateStr = date.includes('T') ? date : `${date}T00:00:00`
    return new Date(dateStr).toLocaleDateString('es-MX')
}

export const formatDateTime = (dateStr) => {
    if (!dateStr) return '---'
    const date = new Date(dateStr)
    return date.toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    })
}

export const getTipoBadge = (tipo) => {
    if (!tipo) tipo = 'I'
    tipo = tipo.toUpperCase()

    const map = {
        'I': { label: 'Ingreso', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
        'E': { label: 'Egreso', color: 'bg-red-100 text-red-700 border-red-200' },
        'P': { label: 'Pago', color: 'bg-blue-100 text-blue-700 border-blue-200' },
        'N': { label: 'Nómina', color: 'bg-brand-100 text-brand-700 border-amber-200' },
        'T': { label: 'Traslado', color: 'bg-indigo-100 text-indigo-700 border-indigo-200' }
    }
    return map[tipo] || { label: 'Otro', color: 'bg-gray-100 text-gray-700 border-gray-200' }
}

export const getStatusBadgeClass = (status) => {
    switch (status?.toLowerCase()) {
        case 'timbrado':
        case 'vigente':
            return 'bg-emerald-50 text-emerald-700 border-emerald-100'
        case 'cancelado':
            return 'bg-red-50 text-red-700 border-red-100'
        default:
            return 'bg-white text-gray-700 border-gray-100'
    }
}

export const getTipoLabel = (tipo) => {
    const tipos = {
        'I': 'Factura',
        'P': 'Pago (REP)',
        'E': 'Egreso',
        'N': 'Nómina',
        'T': 'Traslado'
    }
    return tipos[tipo] || tipo
}

export const getTipoBadgeClass = (tipo) => {
    switch (tipo) {
        case 'I': return 'bg-blue-50 text-blue-700 border-blue-100'
        case 'P': return 'bg-purple-50 text-purple-700 border-purple-100'
        case 'E': return 'bg-orange-50 text-brand-700 border-orange-100'
        default: return 'bg-white text-gray-600 border-gray-100'
    }
}
