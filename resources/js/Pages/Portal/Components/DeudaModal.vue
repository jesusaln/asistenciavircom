<script setup>
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    show: Boolean,
    pagosPendientes: {
        type: Array,
        default: () => []
    },
    empresa: Object
});

const emit = defineEmits(['close', 'pagar']);

const totalDeuda = computed(() => {
    return props.pagosPendientes.reduce((acc, sale) => acc + parseFloat(sale.monto_pendiente || sale.total), 0);
});

const facturasVencidas = computed(() => {
    // Si tuvieramos fecha vencimiento real la usariamos, por ahora asumimos que lo que llega aquí ya es lo pendiente/vencido importante
    return props.pagosPendientes;
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="relative inline-block align-bottom bg-white rounded-[2rem] text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                
                <!-- Header Alerta -->
                <div class="bg-rose-50 dark:bg-rose-900/20 p-6 border-b border-rose-100 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl shrink-0">
                        <font-awesome-icon icon="exclamation-triangle" />
                    </div>
                    <div>
                        <h3 class="text-lg leading-6 font-black text-slate-900 uppercase tracking-wider" id="modal-title">
                            Saldo Pendiente
                        </h3>
                        <p class="text-sm text-rose-600 font-bold mt-1">
                            Se requiere su atención inmediata
                        </p>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6">
                    <p class="text-sm text-slate-500 mb-6">
                        Estimado cliente, hemos detectado facturas pendientes en su cuenta. Para evitar interrupciones en su servicio, le invitamos a regularizar su saldo.
                    </p>

                    <!-- Resumen Deuda -->
                    <div class="bg-white rounded-2xl p-6 mb-6 border border-slate-100">
                        <div class="flex justify-between items-end mb-4">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-wide">Total a Pagar</span>
                            <span class="text-2xl font-black text-slate-900 text-right">
                                ${{ Number(totalDeuda).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                            </span>
                        </div>
                        
                        <!-- Lista mini -->
                        <div class="space-y-3 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="venta in facturasVencidas" :key="venta.id" class="flex justify-between items-center text-sm border-b border-slate-200 pb-2 last:border-0 last:pb-0">
                                <div>
                                    <p class="font-bold text-slate-700 font-mono">#{{ venta.folio || venta.numero_venta || venta.id }}</p>
                                    <p class="text-[10px] text-slate-400 truncate w-32">{{ venta.fecha }}</p>
                                </div>
                                <span class="font-bold text-slate-900">
                                    ${{ Number(venta.monto_pendiente || venta.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" 
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-4 bg-rose-600 text-base font-black text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:text-sm uppercase tracking-wide transition-all hover:shadow-xl"
                            @click="emit('pagar')">
                            Pagar Ahora 💳
                        </button>
                    </div>
                    <button type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-3 bg-white text-base font-bold text-slate-500 hover:text-slate-700 hover:bg-white focus:outline-none sm:text-xs uppercase tracking-wide transition-all"
                        @click="emit('close')">
                        Recordar en otro momento
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 4px;
}
</style>
