<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref } from 'vue';

const props = defineProps({
    title: String,
    message: String,
    confirmLabel: { type: String, default: 'Confirmar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    type: { type: String, default: 'primary' }, // primary, success, warning, danger
});

const visible = ref(false);
const resolvePromise = ref(null);

const show = () => {
    visible.value = true;
    return new Promise((resolve) => {
        resolvePromise.value = resolve;
    });
};

const handleConfirm = () => {
    visible.value = false;
    if (resolvePromise.value) resolvePromise.value(true);
};

const handleCancel = () => {
    visible.value = false;
    if (resolvePromise.value) resolvePromise.value(false);
};

defineExpose({ show });
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="handleCancel"></div>
                
                <!-- Modal Card -->
                <Transition name="scale">
                    <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                        <!-- Upper Detail -->
                        <div class="h-2 w-full" :class="{
                            'bg-[var(--color-primary)]': type === 'primary',
                            'bg-brand-500': type === 'success',
                            'bg-brand-500': type === 'warning',
                            'bg-brand-500': type === 'danger'
                        }"></div>

                        <div class="p-10 text-center">
                            <div class="w-16 h-16 rounded-2xl mx-auto mb-6 flex items-center justify-center text-2xl" :class="{
                                'bg-orange-50 text-[var(--color-primary)]': type === 'primary',
                                'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600': type === 'success',
                                'bg-brand-50 dark:bg-brand-900/20 text-amber-600': type === 'warning',
                                'bg-rose-50 dark:bg-rose-900/20 text-rose-600': type === 'danger'
                            }">
                                <font-awesome-icon v-if="type === 'primary'" icon="info-circle" />
                                <font-awesome-icon v-if="type === 'success'" icon="check-circle" />
                                <font-awesome-icon v-if="type === 'warning'" icon="exclamation-triangle" />
                                <font-awesome-icon v-if="type === 'danger'" icon="trash" />
                            </div>

                            <h3 class="text-2xl font-black text-slate-900 mb-4 leading-tight">{{ title }}</h3>
                            <p class="text-slate-500 font-medium leading-relaxed">{{ message }}</p>

                            <div class="mt-10 flex flex-col sm:flex-row gap-3">
                                <button 
                                    @click="handleCancel" 
                                    class="flex-1 px-8 py-4 bg-white text-slate-400 rounded-2xl font-black text-xs uppercase tracking-wide hover:bg-slate-100 transition-all"
                                >
                                    {{ cancelLabel }}
                                </button>
                                <button 
                                    @click="handleConfirm" 
                                    class="flex-1 px-8 py-4 text-white rounded-2xl font-black text-xs uppercase tracking-wide shadow-xl transition-all"
                                    :class="{
                                        'bg-[var(--color-primary)] shadow-brand-500/20 hover:bg-[var(--color-primary-dark)]': type === 'primary',
                                        'bg-brand-500 shadow-emerald-500/20 hover:bg-emerald-600': type === 'success',
                                        'bg-brand-500 shadow-brand-500/20 hover:bg-amber-600': type === 'warning',
                                        'bg-brand-500 shadow-rose-500/20 hover:bg-rose-600': type === 'danger'
                                    }"
                                >
                                    {{ confirmLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.scale-enter-active, .scale-leave-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.scale-enter-from { opacity: 0; transform: scale(0.9) translateY(20px); }
.scale-leave-to { opacity: 0; transform: scale(0.95); }
</style>
