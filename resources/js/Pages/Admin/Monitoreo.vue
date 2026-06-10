<template>
    <AppLayout title="Monitoreo del Sistema">
        <template #header>
            <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">
                Monitoreo <span class="text-[var(--ui-accent)]">CDD</span>
            </h2>
        </template>

        <div class="p-6 space-y-6">
            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="card p-6 rounded-2xl" :class="status.reverb_ok ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20'">
                    <div class="text-xs font-black uppercase tracking-wide text-[var(--ui-text-muted)]">Reverb</div>
                    <div class="text-lg font-black mt-1" :class="status.reverb_ok ? 'text-emerald-400' : 'text-red-400'">
                        {{ status.reverb_ok ? '✅ Online' : '❌ Offline' }}
                    </div>
                </div>
                <div class="card p-6 rounded-2xl" :class="status.redis_ok ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20'">
                    <div class="text-xs font-black uppercase tracking-wide text-[var(--ui-text-muted)]">Redis</div>
                    <div class="text-lg font-black mt-1" :class="status.redis_ok ? 'text-emerald-400' : 'text-red-400'">
                        {{ status.redis_ok ? '✅ Online' : '❌ Offline' }}
                    </div>
                </div>
                <div class="card p-6 rounded-2xl" :class="status.db_ok ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20'">
                    <div class="text-xs font-black uppercase tracking-wide text-[var(--ui-text-muted)]">Base de Datos</div>
                    <div class="text-lg font-black mt-1" :class="status.db_ok ? 'text-emerald-400' : 'text-red-400'">
                        {{ status.db_ok ? '✅ Online' : '❌ Offline' }}
                    </div>
                </div>
            </div>

            <!-- Queue Stats -->
            <div class="card p-6 rounded-2xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-black uppercase tracking-wide text-[var(--ui-text)]">Colas de Trabajo</h3>
                    <div class="flex gap-2">
                        <button @click="refresh" :disabled="loading" class="btn text-xs px-4 py-2">
                            {{ loading ? 'Cargando...' : '🔄 Refrescar' }}
                        </button>
                        <button v-if="canRetry && status.failed_jobs > 0" @click="retryAll" :disabled="loading" class="btn text-xs px-4 py-2 bg-amber-500/10 border-amber-500/20 text-amber-400">
                            🗑 Limpiar fallidos
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Fallidos</div>
                        <div class="text-2xl font-black mt-1" :class="status.failed_jobs > 0 ? 'text-red-400' : 'text-emerald-400'">
                            {{ status.failed_jobs }}
                        </div>
                    </div>
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Pendientes</div>
                        <div class="text-2xl font-black mt-1" :class="status.pending_jobs > 20 ? 'text-amber-400' : 'text-[var(--ui-text)]'">
                            {{ status.pending_jobs >= 0 ? status.pending_jobs : '?' }}
                        </div>
                    </div>
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Dead Letter</div>
                        <div class="text-2xl font-black mt-1" :class="status.dead_letter_jobs > 0 ? 'text-amber-400' : 'text-[var(--ui-text)]'">
                            {{ status.dead_letter_jobs }}
                        </div>
                    </div>
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Usuarios Activos</div>
                        <div class="text-2xl font-black mt-1 text-[var(--ui-text)]">
                            {{ status.usuarios_activos ?? '?' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- WhatsApp + Citas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="card p-6 rounded-2xl">
                    <h3 class="text-sm font-black uppercase tracking-wide text-[var(--ui-text)] mb-4">WhatsApp (24h)</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Chats Totales</div>
                            <div class="text-2xl font-black mt-1 text-[var(--ui-text)]">{{ status.whatsapp?.chats_24h ?? '?' }}</div>
                        </div>
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Entrantes</div>
                            <div class="text-2xl font-black mt-1 text-[var(--ui-text)]">{{ status.whatsapp?.inbound_24h ?? '?' }}</div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Bot 👍 (7d)</div>
                            <div class="text-lg font-black mt-1 text-emerald-400">{{ status.whatsapp?.feedback_positivo_7d ?? '?' }}</div>
                        </div>
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Bot 👎 (7d)</div>
                            <div class="text-lg font-black mt-1 text-red-400">{{ status.whatsapp?.feedback_negativo_7d ?? '?' }}</div>
                        </div>
                    </div>
                </div>

                <div class="card p-6 rounded-2xl">
                    <h3 class="text-sm font-black uppercase tracking-wide text-[var(--ui-text)] mb-4">Citas Hoy</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Programadas</div>
                            <div class="text-2xl font-black mt-1 text-[var(--ui-text)]">{{ status.citas_hoy?.programadas ?? '?' }}</div>
                        </div>
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Completadas</div>
                            <div class="text-2xl font-black mt-1 text-emerald-400">{{ status.citas_hoy?.completadas ?? '?' }}</div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Total Citas</div>
                            <div class="text-lg font-black mt-1 text-[var(--ui-text)]">{{ status.citas_hoy?.total ?? '?' }}</div>
                        </div>
                        <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                            <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">BD Size</div>
                            <div class="text-lg font-black mt-1 text-[var(--ui-text)]">{{ status.db_info?.size ?? '?' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Usage -->
            <div class="card p-6 rounded-2xl">
                <h3 class="text-sm font-black uppercase tracking-wide text-[var(--ui-text)] mb-4">Recursos</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Memoria</div>
                        <div class="text-lg font-black mt-1 text-[var(--ui-text)]">{{ status.memory_used }}</div>
                    </div>
                    <div class="bg-[var(--ui-surface-alt)] p-4 rounded-xl">
                        <div class="text-[10px] font-black uppercase text-[var(--ui-text-muted)]">Disco</div>
                        <div class="text-lg font-black mt-1 text-[var(--ui-text)]">{{ status.disk_used }}</div>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div v-if="status.alerts && status.alerts.length > 0" class="card p-6 rounded-2xl bg-red-500/5 border-red-500/20">
                <h3 class="text-sm font-black uppercase tracking-wide text-red-400 mb-3">🚨 Alertas Activas</h3>
                <ul class="space-y-2">
                    <li v-for="alert in status.alerts" :key="alert" class="text-sm text-red-300 flex items-start gap-2">
                        <span class="text-red-400 mt-0.5">•</span>
                        {{ alert }}
                    </li>
                </ul>
            </div>

            <div v-else class="card p-6 rounded-2xl bg-emerald-500/5 border-emerald-500/20">
                <div class="text-sm font-black text-emerald-400">✅ Sin alertas — todo funcionando correctamente</div>
            </div>

            <div class="text-[10px] text-[var(--ui-text-muted)] text-right">
                Última actualización: {{ status.timestamp }}
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
    status: Object,
    canRetry: Boolean,
})

const status = ref(props.status)
const loading = ref(false)

const refresh = async () => {
    loading.value = true
    try {
        const res = await axios.post(route('monitoreo.refresh'))
        status.value = res.data
    } catch (e) {
        console.error(e)
    }
    loading.value = false
}

const retryAll = async () => {
    if (!confirm('¿Eliminar todos los jobs fallidos? Esto no afecta datos reales.')) return
    loading.value = true
    try {
        await axios.post(route('monitoreo.retry-failed'))
        await refresh()
    } catch (e) {
        console.error(e)
    }
    loading.value = false
}

setInterval(refresh, 60000)
</script>
