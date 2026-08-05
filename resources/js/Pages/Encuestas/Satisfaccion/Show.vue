<script setup>
import AdminLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    encuesta: Object,
})

const formatearFecha = (fecha) => {
    if (!fecha) return '—'
    return new Date(fecha).toLocaleString('es-MX', { dateStyle: 'short', timeStyle: 'short' })
}

const npsBadge = (nps) => {
    if (nps === null || nps === undefined) return { color: 'bg-ui-muted text-ui-muted-fg', text: '—' }
    if (nps >= 9) return { color: 'bg-success-500/20 text-success-300', text: 'Promotor' }
    if (nps >= 7) return { color: 'bg-warning-500/20 text-warning-300', text: 'Neutral' }
    return { color: 'bg-danger-500/20 text-danger-300', text: 'Detractor' }
}
</script>

<template>
    <AdminLayout :title="`Encuesta ${encuesta.folio}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-white">📋 Encuesta {{ encuesta.folio }}</h1>
                    <p class="text-sm text-white/60 mt-1">Detalle de la encuesta de satisfacción</p>
                </div>
                <Link :href="route('encuestas.satisfaccion.index')" class="text-xs text-white/50 hover:text-white px-3 py-1.5 rounded-lg bg-white/[0.05]">← Volver</Link>
            </div>
        </template>

        <div class="max-w-3xl space-y-4">
            <div class="card p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Cliente</div>
                        <div class="text-white font-semibold">{{ encuesta.nombre_cliente_snapshot || encuesta.cliente?.nombre_razon_social || '—' }}</div>
                        <div class="text-xs text-white/40 font-mono mt-1">{{ encuesta.wa_id }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Estado</div>
                        <div class="text-white">{{ encuesta.estado }}</div>
                        <div class="text-xs text-white/40 mt-1">Respondida: {{ formatearFecha(encuesta.completada_at) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">Calificación</div>
                        <div class="text-2xl font-black text-white">{{ encuesta.calificacion_global ?? '—' }}/5</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-1">NPS</div>
                        <div class="text-2xl font-black">
                            <span :class="npsBadge(encuesta.nps_score).color" class="px-2 py-1 rounded-md text-sm font-bold">
                                {{ encuesta.nps_score ?? '—' }}/10 · {{ npsBadge(encuesta.nps_score).text }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-3">Respuestas</div>
                <div v-if="encuesta.respuestas" class="space-y-3 text-sm">
                    <div v-for="(val, key) in encuesta.respuestas" :key="key" class="flex justify-between gap-4 py-2 border-b border-white/[0.05] last:border-0">
                        <span class="text-white/60">{{ key }}</span>
                        <span class="text-white font-medium text-right">{{ val }}</span>
                    </div>
                </div>
                <div v-else class="text-white/40 text-sm">Sin respuestas registradas.</div>
            </div>

            <div class="card p-5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 mb-3">Código promocional</div>
                <div v-if="encuesta.codigo_promocional" class="flex items-center justify-between">
                    <div>
                        <div class="font-mono text-lg text-white">{{ encuesta.codigo_promocional }}</div>
                        <div class="text-xs text-white/40 mt-1">
                            {{ encuesta.descuento_porcentaje }}% en {{ encuesta.servicio_aplicable }} · Vence {{ formatearFecha(encuesta.codigo_expires_at) }}
                        </div>
                    </div>
                    <span v-if="encuesta.codigo_usado" class="px-2 py-1 rounded-md bg-success-500/20 text-success-300 text-xs font-bold">✓ Usado</span>
                    <span v-else class="px-2 py-1 rounded-md bg-info-500/20 text-info-300 text-xs font-bold">Disponible</span>
                </div>
                <div v-else class="text-white/40 text-sm">Sin código generado.</div>
            </div>
        </div>
    </AdminLayout>
</template>