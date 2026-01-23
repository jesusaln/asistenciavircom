<template>
    <Transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" @click="close"></div>

                <!-- Modal panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div v-if="show" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-red-100">
                        <!-- Header with gradient icon -->
                        <div class="px-6 pt-8 pb-4 text-center">
                            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-50 mb-6">
                                <div class="h-16 w-16 rounded-full bg-gradient-to-br from-red-500 to-orange-400 flex items-center justify-center shadow-lg shadow-red-200">
                                    <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight" id="modal-title">
                                ¡Ups! Algo no salió como esperábamos
                            </h3>
                            <div class="mt-4">
                                <p class="text-gray-500 dark:text-gray-400 text-base leading-relaxed">
                                    Ha ocurrido un error inesperado al procesar tu solicitud. No te preocupes, no es tu culpa.
                                </p>
                            </div>
                        </div>

                        <!-- Error details (optional/collapsed) -->
                        <div v-if="error" class="px-6 py-4 bg-gray-50/50 border-y border-gray-100 dark:border-slate-800">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Detalles del Error</span>
                                <button 
                                    @click="copyError" 
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 transition-colors"
                                >
                                    <svg v-if="!copied" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" stroke-width="2"/></svg>
                                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-width="2"/></svg>
                                    {{ copied ? '¡Copiado!' : 'Copiar para soporte' }}
                                </button>
                            </div>
                            <div class="max-h-32 overflow-y-auto rounded-lg bg-gray-900 p-3 shadow-inner">
                                <code class="text-xs text-green-400 break-all leading-tight font-mono">
                                    {{ error }}
                                </code>
                            </div>
                        </div>

                        <!-- Footer actions -->
                        <div class="px-6 py-6 bg-white dark:bg-slate-900 sm:flex sm:flex-row-reverse gap-3">
                            <button 
                                type="button" 
                                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-md px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-base font-semibold text-white hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto transition-all transform hover:scale-[1.02] active:scale-[0.98]"
                                @click="contactSupport"
                            >
                                Contactar a Soporte
                            </button>
                            <button 
                                type="button" 
                                class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-200 dark:border-slate-800 px-6 py-3 bg-white dark:bg-slate-900 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto transition-colors"
                                @click="close"
                            >
                                Entendido
                            </button>
                        </div>

                        <!-- Support message -->
                        <div class="px-6 pb-6 text-center">
                            <p class="text-xs text-gray-400">
                                Tu sesión sigue activa. Puedes intentar actualizar la página o volver al inicio.
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    show: Boolean,
    error: String
})

const emit = defineEmits(['close'])

const copied = ref(false)

const close = () => {
    emit('close')
}

const copyError = () => {
    if (!props.error) return
    
    navigator.clipboard.writeText(props.error).then(() => {
        copied.value = true
        setTimeout(() => {
            copied.value = false
        }, 3000)
    })
}

const contactSupport = () => {
    // URL de WhatsApp de soporte o página de soporte
    const message = encodeURIComponent(`Hola, tengo un problema en el sistema: ${props.error || 'Error desconocido'}`)
    window.open(`https://wa.me/526621442233?text=${message}`, '_blank')
}

onMounted(() => {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && props.show) {
            close()
        }
    })
})
</script>
