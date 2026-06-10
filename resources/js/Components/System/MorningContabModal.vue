<template>
    <Modal :show="show" maxWidth="2xl" @close="closeModal">
        <div class="p-8 relative overflow-hidden bg-gradient-to-br from-[var(--ui-surface)] to-[var(--ui-surface-soft)]">
            <!-- Decorative Background -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/30">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">Reporte Contable Matutino</h2>
                        <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">
                            Pólizas procesadas automáticamente durante la madrugada
                        </p>
                    </div>
                </div>

                <div v-if="reporte" class="space-y-6">
                    <!-- Resumen -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-[var(--ui-surface-alt)] rounded-2xl p-4 border border-[var(--ui-border)] flex flex-col items-center justify-center shadow-sm">
                            <span class="text-[10px] font-black uppercase text-[var(--ui-text-soft)] mb-1">Total XMLs</span>
                            <span class="text-2xl font-black text-[var(--ui-text)]">{{ reporte.resumen.total }}</span>
                        </div>
                        <div class="bg-emerald-500/10 rounded-2xl p-4 border border-emerald-500/20 flex flex-col items-center justify-center shadow-sm">
                            <span class="text-[10px] font-black uppercase text-emerald-600 mb-1">Exitosas</span>
                            <span class="text-2xl font-black text-emerald-600">{{ reporte.resumen.exitos }}</span>
                        </div>
                        <div class="bg-rose-500/10 rounded-2xl p-4 border border-rose-500/20 flex flex-col items-center justify-center shadow-sm">
                            <span class="text-[10px] font-black uppercase text-rose-600 mb-1">Errores</span>
                            <span class="text-2xl font-black text-rose-600">{{ reporte.resumen.errores }}</span>
                        </div>
                    </div>
                    
                    <!-- Estado Al Día -->
                    <div v-if="reporte.resumen.total === 0" class="mt-6 bg-brand-500/5 p-6 rounded-2xl border border-brand-500/20 text-center flex flex-col items-center justify-center space-y-3">
                        <div class="w-16 h-16 rounded-full bg-brand-500/10 flex items-center justify-center">
                            <svg class="w-8 h-8 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-wider">Sistema al Día</h3>
                            <p class="text-xs text-[var(--ui-text-soft)] font-medium mt-1">
                                No se encontraron nuevas facturas para contabilizar durante la madrugada.
                            </p>
                        </div>
                    </div>

                    <!-- Pólizas Exitosas -->
                    <div v-if="reporte.exitosas && reporte.exitosas.length > 0" class="mt-6">
                        <h3 class="text-xs font-black text-[var(--ui-text)] uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Pólizas Generadas
                        </h3>
                        <div class="max-h-64 overflow-y-auto custom-scrollbar pr-2 space-y-3">
                            <div v-for="exito in reporte.exitosas" :key="exito.uuid" class="bg-[var(--ui-surface-alt)] p-4 rounded-2xl border border-[var(--ui-border)] hover:border-brand-500/30 transition-colors shadow-sm">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <span class="text-xs font-black text-brand-500 bg-brand-500/10 px-2 py-1 rounded-lg uppercase">{{ exito.poliza_numero }}</span>
                                        <span class="text-[10px] text-[var(--ui-text-soft)] ml-2 uppercase font-bold">Folio: {{ exito.folio }}</span>
                                    </div>
                                    <span class="text-sm font-black text-[var(--ui-text)] bg-[var(--ui-surface)] px-2 py-1 rounded-lg border border-[var(--ui-border)]">${{ formatMoney(exito.total) }}</span>
                                </div>
                                <p class="text-xs text-[var(--ui-text)] font-medium mb-3 truncate opacity-80" :title="exito.concepto">{{ exito.concepto }}</p>
                                
                                <!-- Asientos -->
                                <div class="bg-[var(--ui-surface)] rounded-xl overflow-hidden border border-[var(--ui-border)]">
                                    <table class="w-full text-left text-[10px]">
                                        <thead class="bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)] uppercase font-black tracking-wider">
                                            <tr>
                                                <th class="px-3 py-2">Cuenta Contable</th>
                                                <th class="px-3 py-2 text-right">Cargo</th>
                                                <th class="px-3 py-2 text-right">Abono</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-[var(--ui-border)]">
                                            <tr v-for="(asiento, idx) in exito.asientos" :key="idx" class="hover:bg-[var(--ui-surface-soft)]/50 transition-colors">
                                                <td class="px-3 py-2 font-bold text-[var(--ui-text)] truncate max-w-[200px]" :title="asiento.cuenta">{{ asiento.cuenta }}</td>
                                                <td class="px-3 py-2 text-right font-black text-brand-500/90">${{ formatMoney(asiento.debe) }}</td>
                                                <td class="px-3 py-2 text-right font-black text-amber-500/90">${{ formatMoney(asiento.haber) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Errores -->
                    <div v-if="reporte.errores && reporte.errores.length > 0" class="mt-6">
                        <h3 class="text-xs font-black text-rose-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            XMLs sin contabilizar
                        </h3>
                        <div class="max-h-32 overflow-y-auto custom-scrollbar pr-2 space-y-2">
                            <div v-for="error in reporte.errores" :key="error.uuid" class="bg-rose-500/5 p-3 rounded-xl border border-rose-500/20">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-black text-rose-600 uppercase tracking-wider">Folio SAT: {{ error.folio }}</span>
                                    <span class="text-[9px] text-rose-500/70 font-mono">{{ error.uuid.substring(0,8) }}...</span>
                                </div>
                                <p class="text-[11px] text-rose-600 font-bold opacity-90">{{ error.error }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button @click="closeModal" class="px-6 py-3 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 text-white text-xs font-black uppercase tracking-wider hover:shadow-lg hover:shadow-brand-500/30 transition-all active:scale-95 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        Entendido, continuar al panel
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const show = ref(false);
const reporte = ref(null);
const page = usePage();

const formatMoney = (amount) => {
    return Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const checkAndShowReport = () => {
    const reportData = page.props.contab_daily_report;
    if (reportData && reportData.resumen) {
        const fecha = reportData.resumen.fecha;
        // Solo mostrar si no lo ha visto hoy
        const seen = localStorage.getItem(`contab_report_seen_${fecha}`);
        if (!seen) {
            reporte.value = reportData;
            show.value = true;
        }
    }
};

onMounted(() => {
    // Dar un pequeño retraso para que el usuario pueda apreciar el modal sobre el panel cargado
    setTimeout(checkAndShowReport, 2000); 
});

const closeModal = () => {
    show.value = false;
    if (reporte.value && reporte.value.resumen) {
        // Registrar en el navegador que ya vio el reporte de hoy para que no vuelva a molestar
        localStorage.setItem(`contab_report_seen_${reporte.value.resumen.fecha}`, 'true');
    }
};
</script>
