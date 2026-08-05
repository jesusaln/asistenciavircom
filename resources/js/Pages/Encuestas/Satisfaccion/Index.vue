<script setup>
import { computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AppLayout.vue'
import { ref, watch } from 'vue'

const props = defineProps({
    encuestas: Object,
    stats: Object,
    filtros: Object,
})

const search = ref(props.filtros?.search ?? '')
const estado = ref(props.filtros?.estado ?? '')
const minCalificacion = ref(props.filtros?.min_calificacion ?? '')
const maxCalificacion = ref(props.filtros?.max_calificacion ?? '')
const fechaDesde = ref(props.filtros?.fecha_desde ?? '')
const fechaHasta = ref(props.filtros?.fecha_hasta ?? '')
const soloConQueja = ref(props.filtros?.solo_con_queja ?? false)

const aplicarFiltros = () => {
    router.get(route('encuestas.satisfaccion.index'), {
        search: search.value || undefined,
        estado: estado.value || undefined,
        min_calificacion: minCalificacion.value || undefined,
        max_calificacion: maxCalificacion.value || undefined,
        fecha_desde: fechaDesde.value || undefined,
        fecha_hasta: fechaHasta.value || undefined,
        solo_con_queja: soloConQueja.value || undefined,
    }, { preserveState: true, replace: true })
}

const limpiarFiltros = () => {
    search.value = ''
    estado.value = ''
    minCalificacion.value = ''
    maxCalificacion.value = ''
    fechaDesde.value = ''
    fechaHasta.value = ''
    soloConQueja.value = false
    aplicarFiltros()
}

const npsBadge = (nps) => {
    if (nps === null || nps === undefined) return { color: 'bg-ui-muted', text: '—' }
    if (nps >= 9) return { color: 'bg-success-500/20 text-success-300', text: 'Promotor' }
    if (nps >= 7) return { color: 'bg-warning-500/20 text-warning-300', text: 'Neutral' }
    return { color: 'bg-danger-500/20 text-danger-300', text: 'Detractor' }
}

const califBadge = (c) => {
    if (c === null || c === undefined) return { color: 'bg-ui-muted text-ui-muted-fg', text: '—' }
    if (c >= 4.5) return { color: 'bg-success-500/20 text-success-300', text: '⭐ Excelente' }
    if (c >= 3.5) return { color: 'bg-info-500/20 text-info-300', text: '👍 Bueno' }
    if (c >= 2.5) return { color: 'bg-warning-500/20 text-warning-300', text: '⚠️ Regular' }
    return { color: 'bg-danger-500/20 text-danger-300', text: '🚨 Malo' }
}

const estadoBadge = (e) => {
    const map = {
        pendiente: { color: 'bg-warning-500/20 text-warning-300', text: 'Pendiente' },
        en_progreso: { color: 'bg-info-500/20 text-info-300', text: 'En progreso' },
        completada: { color: 'bg-success-500/20 text-success-300', text: 'Completada' },
        cancelada: { color: 'bg-ui-muted text-ui-muted-fg', text: 'Cancelada' },
        expirada: { color: 'bg-ui-muted text-ui-muted-fg', text: 'Expirada' },
        fallida_envio: { color: 'bg-danger-500/20 text-danger-300', text: 'Fallida' },
    }
    return map[e] || map.pendiente
}

const formatearFecha = (fecha) => {
    if (!fecha) return '—'
    return new Date(fecha).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
    <AdminLayout title="Encuestas de Satisfacción">
        <template #header>
            <div>
                <h1 class="text-2xl font-black text-white">📊 Encuestas de Satisfacción</h1>
                <p class="text-sm text-white/60 mt-1">Resultados post-instalación y seguimiento de clientes</p>
            </div>
        </template>

        <div class="min-w-0 p-4 md:p-6 pt-0">
        <!-- KPIs -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Total</div>
                <div class="text-2xl font-black text-white">{{ stats.total }}</div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Tasa Respuesta</div>
                <div class="text-2xl font-black text-info-300">{{ stats.tasa_respuesta }}%</div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Calificación</div>
                <div class="text-2xl font-black text-white">{{ stats.prom_calificacion ?? '—' }}<span class="text-sm text-white/40">/5</span></div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">NPS</div>
                <div class="text-2xl font-black" :class="stats.nps_score >= 50 ? 'text-success-300' : stats.nps_score >= 0 ? 'text-warning-300' : 'text-danger-300'">
                    {{ stats.nps_score }}
                </div>
                <div class="text-[9px] text-white/40 mt-1">{{ stats.promotores }}P · {{ stats.neutrales }}N · {{ stats.detractores }}D</div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Pendientes</div>
                <div class="text-2xl font-black text-warning-300">{{ stats.pendientes }}</div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Cupones Usados</div>
                <div class="text-2xl font-black text-white">{{ stats.codigos_usados }}<span class="text-sm text-white/40">/{{ stats.codigos_generados }}</span></div>
                <div class="text-[9px] text-white/40 mt-1">{{ stats.tasa_uso_cupones }}% conversión</div>
            </div>
            <div class="card p-4">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Canceladas</div>
                <div class="text-2xl font-black text-white/40">{{ stats.canceladas }}</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-7 gap-3">
                <div class="md:col-span-2">
                    <label class="text-[10px] font-bold uppercase text-white/40 mb-1 block">Buscar</label>
                    <input v-model="search" @keyup.enter="aplicarFiltros" type="text" placeholder="Folio, cliente, código..."
                        class="w-full px-3 py-2 bg-white/[0.03] border border-white/[0.05] rounded-lg text-white text-sm focus:border-brand-500/40 focus:outline-none">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-white/40 mb-1 block">Estado</label>
                    <select v-model="estado" @change="aplicarFiltros"
                        class="w-full px-3 py-2 bg-white/[0.03] border border-white/[0.05] rounded-lg text-white text-sm focus:border-brand-500/40 focus:outline-none">
                        <option value="">Todos</option>
                        <option value="activas">Activas (pendiente + en progreso)</option>
                        <option value="completadas">Completadas</option>
                        <option value="canceladas">Canceladas</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-white/40 mb-1 block">Calif. mín</label>
                    <input v-model="minCalificacion" @change="aplicarFiltros" type="number" min="1" max="5" step="0.1"
                        class="w-full px-3 py-2 bg-white/[0.03] border border-white/[0.05] rounded-lg text-white text-sm focus:border-brand-500/40 focus:outline-none">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-white/40 mb-1 block">Calif. máx</label>
                    <input v-model="maxCalificacion" @change="aplicarFiltros" type="number" min="1" max="5" step="0.1"
                        class="w-full px-3 py-2 bg-white/[0.03] border border-white/[0.05] rounded-lg text-white text-sm focus:border-brand-500/40 focus:outline-none">
                </div>
                <div>
                    <label class="text-[10px] font-bold uppercase text-white/40 mb-1 block">Desde</label>
                    <input v-model="fechaDesde" @change="aplicarFiltros" type="date"
                        class="w-full px-3 py-2 bg-white/[0.03] border border-white/[0.05] rounded-lg text-white text-sm focus:border-brand-500/40 focus:outline-none">
                </div>
                <div class="flex items-end gap-2">
                    <label class="flex items-center gap-2 text-xs text-white/70 cursor-pointer">
                        <input v-model="soloConQueja" @change="aplicarFiltros" type="checkbox" class="rounded">
                        <span>Solo con queja/comentario</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end mt-3">
                <button @click="limpiarFiltros" class="text-xs text-white/40 hover:text-white px-3 py-1 rounded-lg hover:bg-white/[0.05]">
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[10px] font-black uppercase tracking-wider text-white/40 border-b border-white/[0.05]">
                        <th class="px-4 py-3">Folio</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Cita</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-center">Calif.</th>
                        <th class="px-4 py-3 text-center">NPS</th>
                        <th class="px-4 py-3">Comentario</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Respondida</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in encuestas.data" :key="e.id" class="border-b border-white/[0.03] hover:bg-white/[0.02] transition-colors">
                        <td class="px-4 py-3">
                            <Link :href="route('encuestas.satisfaccion.show', e.id)" class="font-mono text-xs text-brand-400 hover:text-brand-300">
                                {{ e.folio }}
                            </Link>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-white text-sm">{{ e.nombre_cliente_snapshot || (e.cliente?.nombre_razon_social ?? '—') }}</div>
                            <div class="text-[10px] text-white/40 font-mono">{{ e.wa_id }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="e.cita" class="text-xs">
                                <div class="text-white/80">{{ e.cita.folio }}</div>
                                <div class="text-white/40 text-[10px]">{{ e.cita.marca_equipo }} · {{ e.cita.tipo_servicio }}</div>
                            </div>
                            <span v-else class="text-white/30">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <span :class="estadoBadge(e.estado).color" class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                {{ estadoBadge(e.estado).text }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="califBadge(e.calificacion_global).color" class="px-2 py-1 rounded-md text-xs font-bold">
                                {{ e.calificacion_global ?? '—' }}{{ e.calificacion_global ? '/5' : '' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="npsBadge(e.nps_score).color" class="px-2 py-1 rounded-md text-xs font-bold">
                                {{ e.nps_score ?? '—' }}{{ e.nps_score !== null ? '/10' : '' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <div v-if="e.respuestas?.p2_comentario && e.respuestas.p2_comentario.toLowerCase() !== 'no'" class="text-xs text-white/80 line-clamp-2">
                                "{{ e.respuestas.p2_comentario }}"
                            </div>
                            <span v-else class="text-white/30 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3">
                            <div v-if="e.codigo_promocional" class="text-xs">
                                <div class="font-mono text-white/90">{{ e.codigo_promocional }}</div>
                                <div v-if="e.codigo_usado" class="text-[9px] text-success-300">✓ Usado</div>
                                <div v-else-if="e.codigo_expires_at" class="text-[9px] text-white/40">vence {{ new Date(e.codigo_expires_at).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}</div>
                            </div>
                            <span v-else class="text-white/30 text-xs">—</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-white/60">
                            {{ formatearFecha(e.completada_at ?? e.enviada_at ?? e.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="encuestas.data.length === 0">
                        <td colspan="9" class="px-4 py-12 text-center text-white/40 text-sm">
                            No hay encuestas con esos filtros
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div v-if="encuestas.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-white/[0.05]">
                <div class="text-xs text-white/40">
                    Mostrando {{ encuestas.from }} a {{ encuestas.to }} de {{ encuestas.total }} resultados
                </div>
                <div class="flex gap-1">
                    <Link v-if="encuestas.prev_page_url" :href="encuestas.prev_page_url" class="px-3 py-1 text-xs rounded-lg bg-white/[0.03] hover:bg-white/[0.05] text-white/70">
                        ← Anterior
                    </Link>
                    <Link v-if="encuestas.next_page_url" :href="encuestas.next_page_url" class="px-3 py-1 text-xs rounded-lg bg-white/[0.03] hover:bg-white/[0.05] text-white/70">
                        Siguiente →
                    </Link>
                </div>
            </div>
        </div>
        </div>
    </AdminLayout>
</template>
