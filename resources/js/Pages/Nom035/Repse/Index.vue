<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
    contratistas: Array,
    months: Array
})

const getDocStatus = (contratista, month, year) => {
    // Aquí iría la lógica para buscar si existe documento para ese mes
    // Por ahora simularemos la respuesta si el contratista tiene docs
    return 'pending' // pending, validated, rejected, missing
}

const getStatusColor = (status) => {
    switch(status) {
        case 'validated': return 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20'
        case 'rejected': return 'text-rose-500 bg-rose-500/10 border-rose-500/20'
        case 'pending': return 'text-amber-500 bg-amber-500/10 border-amber-500/20'
        default: return 'text-slate-400 bg-slate-400/5 border-slate-400/10'
    }
}
</script>

<template>
    <AppLayout title="Vigilancia REPSE - Contratistas">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-[var(--ui-text-main)] uppercase tracking-tight">
                        Vigilancia REPSE
                    </h2>
                    <p class="text-sm text-[var(--ui-text-soft)] mt-1">Control y cumplimiento de servicios especializados.</p>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('comisiones.repse.my_contracts')" class="px-4 py-2 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-600 text-xs font-black hover:bg-indigo-500/20 transition-all">
                        <font-awesome-icon icon="file-contract" class="mr-2" />
                        MIS CONTRATOS
                    </Link>
                    <Link :href="route('contratos.plantillas.index')" class="px-4 py-2 bg-slate-500/10 border border-slate-500/20 rounded-xl text-slate-600 text-xs font-black hover:bg-slate-500/20 transition-all">
                        <font-awesome-icon icon="file-signature" class="mr-2" />
                        PLANTILLAS
                    </Link>
                    <Link :href="route('comisiones.vencimientos')" class="px-4 py-2 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-600 text-xs font-black hover:bg-amber-500/20 transition-all">
                        <font-awesome-icon icon="calendar-xmark" class="mr-2" />
                        VENCIMIENTOS
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12 px-6 max-w-[1600px] mx-auto">
            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] p-6 rounded-3xl">
                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-2">Contratistas Activos</p>
                    <p class="text-4xl font-black text-[var(--ui-text-main)]">{{ contratistas.length }}</p>
                </div>
                <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] p-6 rounded-3xl">
                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-2">Docs. por Validar</p>
                    <p class="text-4xl font-black text-amber-500">{{ contratistas.reduce((acc, c) => acc + c.pending_count, 0) }}</p>
                </div>
                <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] p-6 rounded-3xl">
                    <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest mb-2">Cumplimiento Global</p>
                    <p class="text-4xl font-black text-emerald-500">--%</p>
                </div>
            </div>

            <!-- Matriz de Cumplimiento -->
            <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-[2.5rem] overflow-hidden shadow-xl">
                <div class="p-8 border-b border-[var(--ui-border)] bg-[var(--ui-surface)]">
                    <h3 class="font-black text-sm uppercase tracking-widest">Matriz de Cumplimiento Mensual</h3>
                </div>
                
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest">Contratista / RFC</th>
                            <th v-for="m in months" :key="m.month + '-' + m.year" class="px-4 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-center">
                                {{ m.label }}
                            </th>
                            <th class="px-8 py-5 text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--ui-border)]/50">
                        <tr v-for="c in contratistas" :key="c.id" class="hover:bg-indigo-500/5 transition-all group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-indigo-500/10 rounded-full flex items-center justify-center text-indigo-500 font-black text-xs border border-indigo-500/20">
                                        {{ c.nombre_razon_social.charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-[var(--ui-text-main)]">{{ c.nombre_razon_social }}</p>
                                        <p class="text-[10px] text-[var(--ui-text-soft)] font-mono uppercase">{{ c.rfc }}</p>
                                    </div>
                                </div>
                            </td>
                            
                            <td v-for="m in months" :key="m.month + '-' + m.year" class="px-4 py-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="w-8 h-8 rounded-lg border flex items-center justify-center text-[10px] font-black" :class="getStatusColor('missing')">
                                        <font-awesome-icon icon="circle" class="text-[6px] opacity-40" />
                                    </span>
                                </div>
                            </td>

                            <td class="px-8 py-6 text-right">
                                <Link :href="route('comisiones.repse.show', c.id)" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl font-bold text-xs hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/20">
                                    VER EXPEDIENTE
                                </Link>
                            </td>
                        </tr>

                        <tr v-if="contratistas.length === 0">
                            <td colspan="10" class="px-8 py-20 text-center">
                                <div class="flex flex-col items-center gap-4 opacity-40">
                                    <font-awesome-icon icon="truck-loading" class="text-5xl" />
                                    <p class="text-sm font-bold">No hay contratistas marcados con REPSE.</p>
                                    <Link href="/proveedores" class="text-xs text-indigo-500 underline">Ir a proveedores para activar REPSE</Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Info Box -->
            <div class="mt-8 p-6 bg-blue-500/5 border border-blue-500/10 rounded-3xl flex items-start gap-4">
                <font-awesome-icon icon="info-circle" class="text-blue-500 text-xl mt-1" />
                <div class="space-y-1">
                    <h4 class="text-sm font-black text-blue-900 uppercase">Responsabilidad Solidaria</h4>
                    <p class="text-xs text-blue-800/70 leading-relaxed">
                        De acuerdo con la Reforma a la Subcontratación 2021, es responsabilidad de la empresa contratante vigilar que el contratista cumpla con sus obligaciones fiscales y de seguridad social. 
                        Este módulo facilita la recolección de Opiniones del SAT, IMSS, INFONAVIT y pagos de cuotas obrero-patronales.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
