<template>
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="updateAvailable" class="fixed bottom-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900">
                            Nueva actualización disponible
                        </p>
                        <p class="mt-1 text-sm text-gray-500">
                            Una nueva versión del sistema está lista. Actualiza para ver los cambios.
                        </p>
                        <div class="mt-3 flex space-x-7">
                            <button
                                @click="refreshPage"
                                class="bg-white rounded-md text-sm font-medium text-amber-600 hover:text-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                            >
                                Actualizar ahora
                            </button>
                            <button
                                @click="dismiss"
                                class="bg-white rounded-md text-sm font-medium text-gray-700 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                            >
                                Ignorar
                            </button>
                        </div>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button
                            @click="dismiss"
                            class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                        >
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const { props } = usePage();
// Obtenemos la versión inicial desde las props de Inertia (o la detectamos en el primer request)
const initialVersion = ref(props.app_version || null);
const updateAvailable = ref(false);
let intervalId = null;

const checkForUpdate = async () => {
    try {
        const response = await axios.get('/api/app-version');
        const latestVersion = response.data.version;

        if (!initialVersion.value) {
            initialVersion.value = latestVersion;
            return;
        }

        if (latestVersion && latestVersion !== initialVersion.value) {
            updateAvailable.value = true;
            clearInterval(intervalId);
        }
    } catch (error) {
        // Silently fail - network issues shouldn't break the app
    }
};

const refreshPage = () => {
    window.location.reload();
};

const dismiss = () => {
    updateAvailable.value = false;
};

onMounted(() => {
    // Verificar cada 2 minutos en producción
    intervalId = setInterval(checkForUpdate, 120000);
});

onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>
