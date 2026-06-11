<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    matrix: Array
})

const getStatusColor = (accepted) => {
    return accepted ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
}
</script>

<template>
    <AppLayout title="NOM-035 - Matriz de Firmas Digitales">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Matriz de Firmas (Política)
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Control de acuse de recibo y conocimiento de la política de prevención.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-600 text-xs font-black">
                        {{ matrix.filter(m => m.accepted).length }} FIRMADOS
                    </div>
                    <div class="px-4 py-2 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-600 text-xs font-black">
                        {{ matrix.filter(m => !m.accepted).length }} PENDIENTES
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 xl:px-12 w-full">
            <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden shadow-xl">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Colaborador</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">Estado de Firma</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">Fecha y Hora</th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ui-border)]/50">
                        <tr v-for="user in matrix" :key="user.id" class="hover:bg-indigo-500/5 transition-all group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-indigo-500/10 rounded-full flex items-center justify-center text-indigo-500 font-black text-xs border border-indigo-500/20">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ user.name }}</p>
                                        <p class="text-[10px] text-[var(--ui-text-soft)] font-medium">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span :class="['px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border', getStatusColor(user.accepted)]">
                                    {{ user.accepted ? 'Firmado' : 'Pendiente' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="text-xs font-bold text-[var(--ui-text-main)]" v-if="user.accepted_at">
                                    {{ new Date(user.accepted_at).toLocaleString('es-MX') }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-300 uppercase italic" v-else>No registrado</span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <span class="text-[10px] font-mono text-[var(--ui-text-soft)] bg-slate-100 px-2 py-1 rounded" v-if="user.ip">
                                    {{ user.ip }}
                                </span>
                                <span class="text-slate-200" v-else>-</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-8 p-6 bg-blue-500/5 border border-blue-500/10 rounded-3xl flex items-start gap-4">
                <font-awesome-icon icon="info-circle" class="text-blue-500 text-xl mt-1" />
                <div class="space-y-1">
                    <h4 class="text-sm font-black text-blue-900 uppercase">Validez Jurídica de la Firma Digital</h4>
                    <p class="text-xs text-blue-800/70 leading-relaxed">
                        Este registro cumple con los requisitos de la NOM-035-STPS-2018 al documentar fehacientemente que el trabajador ha sido informado sobre la política de prevención. 
                        El registro incluye marca de tiempo (Timestamp) e identificador de red (IP) para garantizar la trazabilidad en caso de inspección.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
