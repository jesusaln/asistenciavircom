<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    vencimientos: Array
})

const getStatusColor = (expiry) => {
    const today = new Date()
    const expiryDate = new Date(expiry)
    const diffTime = expiryDate - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays < 0) return 'text-rose-500 bg-rose-500/10 border-rose-500/20'
    if (diffDays < 30) return 'text-amber-500 bg-amber-500/10 border-amber-500/20'
    return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
}

const getDaysText = (expiry) => {
    const today = new Date()
    const expiryDate = new Date(expiry)
    const diffTime = expiryDate - today
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays < 0) return 'VENCIDO HACE ' + Math.abs(diffDays) + ' DÍAS'
    if (diffDays === 0) return 'VENCE HOY'
    return 'VENCE EN ' + diffDays + ' DÍAS'
}
</script>

<template>
    <AppLayout title="Vencimientos de Registros REPSE">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('comisiones.repse')" class="p-2 bg-[var(--ui-surface-soft)] rounded-lg text-[var(--ui-text-soft)] hover:text-amber-500 transition-colors">
                    <font-awesome-icon icon="arrow-left" />
                </Link>
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Vencimientos REPSE
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Control de vigencia de registros (3 años).</p>
                </div>
            </div>
        </template>

        <div class="py-12 px-6 max-w-5xl mx-auto">
            <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden">
                <div class="p-8 border-b border-[var(--ui-border)] bg-[var(--ui-surface)] flex items-center justify-between">
                    <h3 class="font-black text-sm uppercase tracking-widest text-amber-600">
                        <font-awesome-icon icon="bell" class="mr-2" />
                        Próximas Expiraciones
                    </h3>
                </div>

                <div class="divide-y divide-[var(--ui-border)]/50">
                    <div v-for="v in vencimientos" :key="v.id" class="p-8 flex items-center justify-between hover:bg-amber-500/5 transition-all group">
                        <div class="flex items-center gap-6">
                            <div class="h-14 w-14 bg-white/5 rounded-2xl flex items-center justify-center text-amber-500 text-xl border border-white/10 group-hover:border-amber-500/30 transition-all">
                                <font-awesome-icon icon="id-card" />
                            </div>
                            <div>
                                <h4 class="font-black text-lg text-[var(--ui-text-main)] uppercase tracking-tight">{{ v.nombre_razon_social }}</h4>
                                <p class="text-xs text-[var(--ui-text-soft)] font-bold uppercase tracking-widest mt-1">
                                    RFC: {{ v.rfc }} | Registro: {{ v.repse_number || 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-xs font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-2">Fecha de Expiración</p>
                            <div :class="['px-4 py-2 rounded-xl border text-xs font-black tracking-widest transition-all', getStatusColor(v.repse_expiry)]">
                                {{ new Date(v.repse_expiry).toLocaleDateString() }}
                                <br>
                                <span class="text-[9px] opacity-70">{{ getDaysText(v.repse_expiry) }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="vencimientos.length === 0" class="py-24 text-center">
                        <font-awesome-icon icon="check-circle" class="text-5xl text-emerald-500/20 mb-4" />
                        <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-widest">No hay registros por vencer próximamente.</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-8 bg-amber-500/5 border border-amber-500/10 rounded-3xl">
                <h5 class="text-sm font-black text-amber-900 uppercase mb-2">Aviso Importante</h5>
                <p class="text-xs text-amber-800/70 leading-relaxed italic">
                    Recuerda que el registro REPSE tiene una vigencia de 3 años según el artículo 15 de la LFT. 
                    El sistema te notificará automáticamente 3 meses antes de que expire el registro de un proveedor para que puedas solicitar su renovación.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
