<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import LoadingSpinner from './UI/LoadingSpinner.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    title: {
        type: String,
        default: '',
    },
    scrollable: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    overlayClass: {
        type: String,
        default: 'bg-slate-900/50 dark:bg-slate-950/60 backdrop-blur-md',
    },
});

const emit = defineEmits(['close']);
const dialog = ref();
const showSlot = ref(props.show);

watch(() => props.show, () => {
    if (props.show) {
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
        dialog.value?.showModal();
    } else {
        document.body.style.overflow = null;
        setTimeout(() => {
            dialog.value?.close();
            showSlot.value = false;
        }, 200);
    }
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));

onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = null;
});

const maxWidthClass = computed(() => {
    return {
        'sm': 'sm:max-w-sm',
        'md': 'sm:max-w-md',
        'lg': 'sm:max-w-lg',
        'xl': 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
        '6xl': 'sm:max-w-6xl',
        '7xl': 'sm:max-w-7xl',
        'full': 'sm:max-w-[95vw]',
    }[props.maxWidth];
});
</script>

<template>
    <dialog class="z-50 m-0 min-h-full min-w-full overflow-y-auto custom-scrollbar bg-transparent backdrop:bg-transparent focus:outline-none" ref="dialog" :aria-labelledby="title ? 'modal-title' : undefined" role="dialog" :aria-modal="true">
        <div class="fixed inset-0 overflow-y-auto custom-scrollbar px-4 py-6 sm:px-0 z-50" scroll-region>
            <transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-show="show" class="absolute inset-0 transform transition-all" @click="close">
                    <div :class="overlayClass" class="absolute inset-0" />
                </div>
            </transition>

            <transition
                enter-active-class="ease-out duration-200"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="relative mb-6 rounded-2xl border overflow-hidden transform transition-all sm:w-full sm:mx-auto bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 shadow-xl"
                    :class="maxWidthClass"
                >
                    <div v-if="title || $slots.header" class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-700">
                        <h3 v-if="title" id="modal-title" class="text-lg font-medium text-slate-900 dark:text-slate-100">{{ title }}</h3>
                        <slot name="header" />
                        <button v-if="closeable" @click="close" class="text-slate-400 hover:text-brand-600 dark:hover:text-slate-300 transition-colors ml-auto" aria-label="Cerrar modal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div v-else-if="$slots.header" class="p-6 border-b border-slate-200 dark:border-slate-700">
                        <slot name="header" />
                    </div>

                    <div :class="{ 'max-h-[70vh] overflow-y-auto custom-scrollbar': scrollable }" class="p-6">
                        <div v-if="loading" class="flex flex-col items-center justify-center py-8">
                            <LoadingSpinner size="lg" color="amber" />
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Cargando...</p>
                        </div>
                        <slot v-else v-if="showSlot"/>
                    </div>

                    <div v-if="$slots.footer" class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/50">
                        <slot name="footer" />
                    </div>
                </div>
            </transition>
        </div>
    </dialog>
</template>
