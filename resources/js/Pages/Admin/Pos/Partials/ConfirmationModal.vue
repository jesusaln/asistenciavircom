<script setup>
defineProps({
    show: Boolean,
    title: { type: String, default: '¿Estás seguro?' },
    message: { type: String, default: 'Esta acción no se puede deshacer.' },
    confirmText: { type: String, default: 'Confirmar' },
    cancelText: { type: String, default: 'Cancelar' },
    variant: { type: String, default: 'danger' } // danger, warning, info
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[1000000] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="emit('cancel')"></div>
            
            <div class="relative w-full max-w-md bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden animate-in zoom-in-95 fade-in duration-200">
                <div class="p-8 text-center">
                    <!-- Icon -->
                    <div :class="variant === 'danger' ? 'bg-brand-500/20 text-rose-500' : 'bg-brand-500/20 text-brand-500'" 
                         class="w-16 h-16 rounded-[30%] flex items-center justify-center mx-auto mb-6">
                        <svg v-if="variant === 'danger'" class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <svg v-else class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-2xl font-black text-white mb-2 uppercase tracking-wider">{{ title }}</h3>
                    <p class="text-slate-400 mb-8">{{ message }}</p>

                    <div class="grid grid-cols-2 gap-4">
                        <button @click="emit('cancel')" 
                                class="py-4 bg-slate-800 hover:bg-slate-700 text-white rounded-2xl font-bold transition-all">
                            {{ cancelText }}
                        </button>
                        <button @click="emit('confirm')" 
                                :class="variant === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-amber-700'"
                                class="py-4 text-white rounded-2xl font-bold shadow-xl transition-all active:scale-95">
                            {{ confirmText }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
