<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import CrudPageHeader from '@/Components/CrudPageHeader.vue'
import FormCard from '@/Components/FormCard.vue'
import axios from 'axios'
import Swal from 'sweetalert2'

defineOptions({ layout: AppLayout })

const props = defineProps({
    almacen: { type: Object, required: true },
    inventario: { type: Array, default: () => [] },
    herramientas: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    revisiones: { type: Array, default: () => [] }
})

// Estado de la auditoría local
const auditData = ref(props.inventario.map(item => ({
    ...item,
    nueva_cantidad: item.cantidad,
    original_cantidad: item.cantidad
})))

const isSaving = ref(false)
const observaciones = ref('')

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value)
}

const ajustarCantidad = (item, delta) => {
    item.nueva_cantidad = Math.max(0, item.nueva_cantidad + delta)
}

// Computados para la auditoría
const cambiosRealizados = computed(() => {
    return auditData.value.filter(item => item.nueva_cantidad !== item.original_cantidad)
})

const totalMermas = computed(() => {
    return cambiosRealizados.value.filter(item => item.nueva_cantidad < item.original_cantidad).length
})

const totalExcedentes = computed(() => {
    return cambiosRealizados.value.filter(item => item.nueva_cantidad > item.original_cantidad).length
})

const valorTotalAuditado = computed(() => {
    return auditData.value.reduce((acc, item) => {
        const precioUnitario = item.valor / item.original_cantidad || 0
        return acc + (item.nueva_cantidad * precioUnitario)
    }, 0)
})

const finalizarAuditoria = async () => {
    if (cambiosRealizados.value.length === 0) return
    
    // Construir tabla de reporte para el SweetAlert
    let tableRows = cambiosRealizados.value.map(item => {
        const diff = item.nueva_cantidad - item.original_cantidad
        const isMerma = diff < 0
        const colorClass = isMerma ? 'color: #ef4444;' : 'color: #10b981;'
        const icon = isMerma ? '⚠️' : '✅'
        const label = isMerma ? 'Merma' : 'Excedente'
        
        return `
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 12px; text-align: left; font-size: 12px;">
                    <strong>${item.producto}</strong><br>
                    <small style="color: #666; font-family: monospace;">${item.sku}</small>
                </td>
                <td style="padding: 12px; text-align: center;">${item.original_cantidad}</td>
                <td style="padding: 12px; text-align: center;"><strong>${item.nueva_cantidad}</strong></td>
                <td style="padding: 12px; text-align: center; font-weight: bold; ${colorClass}">
                    ${icon} ${Math.abs(diff)}<br>
                    <small style="font-size: 9px; text-transform: uppercase;">${label}</small>
                </td>
            </tr>
        `
    }).join('')

    const reportHtml = `
        <div style="max-height: 400px; overflow-y: auto; margin-top: 10px; border: 1px solid #f1f5f9; border-radius: 1rem;">
            <table style="width: 100%; border-collapse: collapse; font-family: sans-serif;">
                <thead>
                    <tr style="background: #f8fafc; color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 12px; text-align: left;">Producto</th>
                        <th style="padding: 12px; text-align: center;">Sistema</th>
                        <th style="padding: 12px; text-align: center;">Físico</th>
                        <th style="padding: 12px; text-align: center;">Ajuste</th>
                    </tr>
                </thead>
                <tbody>
                    ${tableRows}
                </tbody>
            </table>
        </div>
        <div style="margin-top: 20px; padding: 15px; background: #fffbeb; border-radius: 1rem; border: 1px solid #fef3c7; text-align: left;">
            <p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                <strong>Nota de Revisión:</strong> Esta auditoría será enviada a <b>Jesus Lopez</b> (Super Admin) para su verificación final. Los cambios se aplicarán al inventario una vez aprobados.
            </p>
        </div>
    `

    const result = await Swal.fire({
        title: '<span style="font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px;">Reporte Final de Auditoría</span>',
        html: reportHtml,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '🚀 Enviar a Jesus Lopez',
        cancelButtonText: 'Seguir Editando',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        width: '650px',
        customClass: {
            popup: 'rounded-[2.5rem] shadow-2xl border-none',
            confirmButton: 'rounded-2xl font-black uppercase tracking-widest text-[10px] py-4 px-8',
            cancelButton: 'rounded-2xl font-black uppercase tracking-widest text-[10px] py-4 px-8'
        }
    })

    if (!result.isConfirmed) return

    isSaving.value = true
    
    try {
        const response = await axios.post(route('almacenes.finalizar-auditoria', props.almacen.id), {
            ajustes: cambiosRealizados.value.map(item => ({
                id: item.id,
                nueva_cantidad: item.nueva_cantidad
            })),
            observaciones: observaciones.value
        })

        if (response.data.success) {
            await Swal.fire({
                title: '¡Reporte Enviado!',
                text: 'La auditoría está ahora en manos de Jesus Lopez para su revisión.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                customClass: { popup: 'rounded-[2rem]' }
            })
            router.reload()
        }
    } catch (error) {
        console.error(error)
        Swal.fire({
            title: 'Error de Envío',
            text: 'No pudimos contactar con el servidor. Revisa tu conexión.',
            icon: 'error',
            confirmButtonColor: '#ef4444',
            customClass: { popup: 'rounded-[2rem]' }
        })
    } finally {
        isSaving.value = false
    }
}

const aprobarMovimiento = async (id) => {
    const result = await Swal.fire({
        title: '¿Aprobar Auditoría?',
        text: 'Se actualizará el stock real del producto de inmediato.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Aceptar',
        confirmButtonColor: '#10b981',
        customClass: { popup: 'rounded-[2rem]' }
    })

    if (!result.isConfirmed) return

    try {
        const response = await axios.post(route('almacenes.aprobar-auditoria', id))
        if (response.data.success) {
            Swal.fire({
                title: '¡Aprobado!',
                text: response.data.mensaje,
                icon: 'success',
                confirmButtonColor: '#10b981',
                customClass: { popup: 'rounded-[2rem]' }
            })
            router.reload()
        }
    } catch (error) {
        console.error(error)
        Swal.fire('Error', 'No se pudo aprobar el movimiento', 'error')
    }
}

const activeTab = ref('inventario')
</script>

<template>
    <Head :title="'Auditoría: ' + almacen.nombre" />
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950/20 pb-32">
        <div class="w-full px-4 sm:px-6 py-6">
            
            <!-- Header -->
            <CrudPageHeader :title="almacen.nombre" :subtitle="'Responsable: ' + (almacen.responsable?.name || 'Sin asignar')">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <a :href="route('almacenes.exportar-mermas', almacen.id)"
                            class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/20">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Excel: Reporte de Faltantes
                        </a>
                        <Link :href="route('almacenes.index')"
                            class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver
                        </Link>
                    </div>
                </template>
            </CrudPageHeader>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Valor Auditado</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(valorTotalAuditado) }}</p>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Faltantes</span>
                        <div class="w-8 h-8 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" /></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black text-rose-500">{{ totalMermas }}</p>
                </div>

                <div class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-200 dark:border-white/5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Sobrantes</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black text-emerald-500">{{ totalExcedentes }}</p>
                </div>

                <div class="bg-brand-50 dark:bg-brand-500/10 p-6 rounded-[2rem] border border-brand-200 dark:border-brand-500/20 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2 relative z-10">
                        <span class="text-[10px] font-black text-brand-600 dark:text-brand-400 uppercase tracking-wider">En Revisión</span>
                        <div class="w-8 h-8 rounded-lg bg-brand-500/20 flex items-center justify-center text-amber-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                    </div>
                    <p class="text-2xl font-black text-brand-700 dark:text-brand-300 relative z-10">{{ revisiones.length }}</p>
                    <svg class="absolute -right-4 -bottom-4 w-24 h-24 text-brand-500/10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="flex gap-2 mb-6 bg-slate-200/50 dark:bg-slate-800/50 p-1 rounded-2xl w-fit">
                <button @click="activeTab = 'inventario'"
                    class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all"
                    :class="activeTab === 'inventario' ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    Realizar Auditoría
                </button>
                <button @click="activeTab = 'revisiones'"
                    class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all relative"
                    :class="activeTab === 'revisiones' ? 'bg-white dark:bg-slate-700 text-brand-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    Revisiones Pendientes
                    <span v-if="revisiones.length > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white text-[8px] flex items-center justify-center rounded-full font-black animate-bounce">{{ revisiones.length }}</span>
                </button>
                <button @click="activeTab = 'herramientas'"
                    class="px-6 py-2.5 text-sm font-bold rounded-xl transition-all"
                    :class="activeTab === 'herramientas' ? 'bg-white dark:bg-slate-700 text-sky-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                    Herramientas
                </button>
            </div>

            <!-- Main Tables -->
            <div v-show="activeTab === 'inventario'" class="animate-in fade-in slide-in-from-bottom-4 duration-500 pb-20">
                <FormCard title="Panel de Auditoría de Stock" class="!p-0 overflow-hidden">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-900/50 text-slate-500 font-black">
                            <tr>
                                <th class="px-6 py-4">Producto</th>
                                <th class="px-6 py-4 text-center">Sistema</th>
                                <th class="px-6 py-4 text-center">Conteo Físico</th>
                                <th class="px-6 py-4 text-center">Ajuste</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="item in auditData" :key="item.id" 
                                :class="item.nueva_cantidad !== item.original_cantidad ? 'bg-brand-50/30 dark:bg-brand-500/5' : ''"
                                class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ item.producto }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ item.sku }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-500 font-bold">
                                        {{ item.original_cantidad }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-4">
                                        <button @click="ajustarCantidad(item, -1)" 
                                            class="w-8 h-8 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center hover:bg-rose-500 hover:border-rose-500 hover:text-white transition-all font-black text-lg shadow-sm">
                                            -
                                        </button>
                                        <span class="text-xl font-black min-w-[30px]" :class="item.nueva_cantidad !== item.original_cantidad ? 'text-amber-600' : 'text-slate-900 dark:text-white'">
                                            {{ item.nueva_cantidad }}
                                        </span>
                                        <button @click="ajustarCantidad(item, 1)"
                                            class="w-8 h-8 rounded-full border border-slate-300 dark:border-slate-600 flex items-center justify-center hover:bg-emerald-500 hover:border-emerald-500 hover:text-white transition-all font-black text-lg shadow-sm">
                                            +
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div v-if="item.nueva_cantidad < item.original_cantidad" class="text-rose-500 font-black text-xs flex flex-col">
                                        <span>-{{ item.original_cantidad - item.nueva_cantidad }} PZA</span>
                                        <span class="text-[9px] uppercase tracking-tighter opacity-70">⚠️ MERMA</span>
                                    </div>
                                    <div v-else-if="item.nueva_cantidad > item.original_cantidad" class="text-emerald-500 font-black text-xs flex flex-col">
                                        <span>+{{ item.nueva_cantidad - item.original_cantidad }} PZA</span>
                                        <span class="text-[9px] uppercase tracking-tighter opacity-70">✅ SOBRANTE</span>
                                    </div>
                                    <span v-else class="text-slate-300 dark:text-slate-700 font-bold text-xs uppercase tracking-widest italic">Correcto</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </FormCard>

                <!-- Floating Save Bar -->
                <div v-if="cambiosRealizados.length > 0" 
                    class="fixed bottom-8 left-1/2 -translate-x-1/2 w-full max-w-5xl px-4 animate-in slide-in-from-bottom-12 duration-500 z-50">
                    <div class="bg-slate-900 dark:bg-white p-5 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.4)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.1)] flex items-center justify-between gap-8 border border-white/10 dark:border-slate-200">
                        <div class="flex items-center gap-8 flex-1 pl-4">
                            <div class="flex flex-col">
                                <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">Reporte Auditado</span>
                                <div class="flex items-center gap-6 mt-1">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></div>
                                        <span class="text-rose-400 dark:text-rose-600 font-black text-sm">{{ totalMermas }} Mermas</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                        <span class="text-emerald-400 dark:text-emerald-600 font-black text-sm">{{ totalExcedentes }} Sobrantes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="h-10 w-px bg-slate-700 dark:bg-slate-200"></div>
                            <div class="flex-1">
                                <input v-model="observaciones" type="text" 
                                    placeholder="Nota para Jesus Lopez..." 
                                    class="bg-transparent border-none text-white dark:text-slate-900 placeholder:text-slate-600 dark:placeholder:text-slate-400 text-sm focus:ring-0 w-full font-medium">
                            </div>
                        </div>
                        <button @click="finalizarAuditoria" 
                            :disabled="isSaving"
                            class="bg-emerald-500 hover:bg-emerald-600 disabled:bg-slate-700 text-white font-black px-10 py-4 rounded-[1.5rem] transition-all flex items-center gap-3 shadow-xl shadow-emerald-500/20 active:scale-95 group">
                            <svg v-if="isSaving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <svg v-else class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            <span class="uppercase tracking-widest text-[10px]">Enviar a Revisión</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Revisiones Tab -->
            <div v-show="activeTab === 'revisiones'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                <FormCard title="Historial de Auditorías en Revisión" class="!p-0 overflow-hidden border-brand-500/20">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-brand-50/50 dark:bg-brand-900/10 text-brand-700 dark:text-brand-400 font-black">
                            <tr>
                                <th class="px-6 py-4">Producto Auditado</th>
                                <th class="px-6 py-4 text-center">Ajuste Propuesto</th>
                                <th class="px-6 py-4">Auditado Por</th>
                                <th class="px-6 py-4 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="r in revisiones" :key="r.id" class="hover:bg-brand-50/20 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ r.producto }}
                                    <div class="text-[10px] text-slate-400 font-mono">{{ r.sku }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="flex items-center gap-3 text-xs">
                                            <span class="text-slate-400 line-through">{{ r.anterior }}</span>
                                            <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                            <span class="font-black text-slate-900 dark:text-white text-base">{{ r.nueva }}</span>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full mt-1"
                                            :class="r.tipo === 'MERMA' ? 'bg-rose-100 text-rose-600' : 'bg-emerald-100 text-emerald-600'">
                                            {{ r.tipo }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-slate-700 dark:text-slate-300 font-bold text-sm">{{ r.usuario }}</div>
                                    <div class="text-[10px] text-slate-400">{{ r.fecha }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="aprobarMovimiento(r.id)"
                                        class="bg-emerald-500 hover:bg-emerald-600 text-white font-black text-[10px] uppercase tracking-widest px-6 py-2 rounded-xl transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                                        Aceptar
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!revisiones.length">
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">No hay auditorías esperando revisión de Jesus Lopez.</td>
                            </tr>
                        </tbody>
                    </table>
                </FormCard>
            </div>

            <!-- Herramientas Tab -->
            <div v-show="activeTab === 'herramientas'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                <FormCard title="Resguardo Físico de Herramientas" class="!p-0 overflow-hidden border-sky-500/20">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400 font-black">
                            <tr>
                                <th class="px-6 py-4">Herramienta / Equipo</th>
                                <th class="px-6 py-4 text-center">Estado Físico</th>
                                <th class="px-6 py-4">Marca/Modelo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                            <tr v-for="h in herramientas" :key="h.id" class="hover:bg-sky-50/20 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">
                                    {{ h.nombre }}
                                    <div class="text-[10px] text-slate-400 font-mono tracking-tighter">{{ h.codigo }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400': h.estado === 'bueno',
                                            'bg-brand-100 text-brand-700 dark:bg-brand-500/10 dark:text-amber-400': h.estado === 'regular',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400': h.estado === 'malo'
                                        }">
                                        {{ h.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 italic">
                                    {{ h.marca }}
                                    <div class="text-xs font-bold not-italic">{{ h.modelo }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </FormCard>
            </div>

        </div>
    </div>
</template>
