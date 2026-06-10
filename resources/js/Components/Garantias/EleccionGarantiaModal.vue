<script setup>
import { defineProps, defineEmits } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCalendarAlt, faTools, faTimes } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    show: Boolean,
    serie: String,
    cliente: String
})

const emit = defineEmits(['close', 'select'])

const selectOption = (option) => {
    emit('select', option)
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl border border-white/10 overflow-hidden">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-brand-500/5 to-transparent">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Iniciar Garantía</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-bold mt-1">Serie: {{ serie }} • {{ cliente }}</p>
                </div>
                <button @click="emit('close')" class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all">
                    <FontAwesomeIcon :icon="faTimes" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-8 space-y-4">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-6">Selecciona cómo deseas procesar esta garantía:</p>
                
                <!-- Option: Cita Técnica -->
                <button 
                    @click="selectOption('cita')"
                    class="w-full group p-6 rounded-3xl border-2 border-slate-100 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500 bg-slate-50 dark:bg-slate-800/50 hover:bg-orange-50/30 dark:hover:bg-orange-900/10 transition-all text-left flex items-center gap-6"
                >
                    <div class="w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-900/30 text-brand-600 dark:text-orange-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <FontAwesomeIcon :icon="faCalendarAlt" />
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 dark:text-white leading-tight uppercase">Cita Técnica</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">El técnico visitará el domicilio del cliente.</p>
                    </div>
                    <div class="text-brand-500 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>

                <!-- Option: Orden de Taller -->
                <button 
                    @click="selectOption('taller')"
                    class="w-full group p-6 rounded-3xl border-2 border-slate-100 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500 bg-slate-50 dark:bg-slate-800/50 hover:bg-brand-50/30 dark:hover:bg-brand-900/10 transition-all text-left flex items-center gap-6"
                >
                    <div class="w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-900/20/30 text-brand-600 dark:text-brand-400 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <FontAwesomeIcon :icon="faTools" />
                    </div>
                    <div class="flex-1">
                        <h4 class="text-lg font-black text-slate-900 dark:text-white leading-tight uppercase">Orden de Taller</h4>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">El cliente dejó el equipo en sucursal.</p>
                    </div>
                    <div class="text-brand-500 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" /></svg>
                    </div>
                </button>
            </div>

            <!-- Footer -->
            <div class="px-8 py-6 bg-slate-50 dark:bg-black/20 text-center">
                <button @click="emit('close')" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 uppercase tracking-wide transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
