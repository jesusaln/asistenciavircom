<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="palette" class="text-purple-600" />
                Apariencia Visual
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Logos -->
                <div class="space-y-6">
                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">Logotipos</h3>
                    
                    <!-- Logo Principal -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo Principal</label>
                         <div class="mt-1 flex items-center gap-4">
                            <div class="w-24 h-24 border dark:border-gray-600 rounded-lg flex items-center justify-center bg-white dark:bg-gray-700 overflow-hidden">
                                <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" class="max-w-full max-h-full object-contain" />
                                <FontAwesomeIcon v-else icon="image" class="text-gray-300 text-3xl" />
                            </div>
                            <div class="flex-1">
                                <input type="file" @change="handleLogoChange" accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2" />
                                <div class="flex gap-2">
                                     <button v-if="logoForm.logo" @click="subirLogo" type="button" class="text-xs bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded hover:bg-blue-700 dark:hover:bg-blue-600">Subir</button>
                                     <button v-if="form.logo_url" @click="eliminarLogo" type="button" class="text-xs bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300 px-3 py-1 rounded hover:bg-red-200 dark:hover:bg-red-900/60">Borrar Actual</button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recomendado: 200x200px PNG transparente.</p>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Favicon</label>
                         <div class="mt-1 flex items-center gap-4">
                            <div class="w-12 h-12 border dark:border-gray-600 rounded-lg flex items-center justify-center bg-white dark:bg-gray-700 overflow-hidden">
                                <img v-if="faviconPreview || form.favicon_url" :src="faviconPreview || form.favicon_url" class="max-w-full max-h-full object-contain" />
                                <FontAwesomeIcon v-else icon="globe" class="text-gray-300 text-xl" />
                            </div>
                             <div class="flex-1">
                                <input type="file" @change="handleFaviconChange" accept="image/*" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2" />
                                <div class="flex gap-2">
                                     <button v-if="faviconForm.favicon" @click="subirFavicon" type="button" class="text-xs bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded hover:bg-blue-700 dark:hover:bg-blue-600">Subir</button>
                                      <button v-if="form.favicon_url" @click="eliminarFavicon" type="button" class="text-xs bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300 px-3 py-1 rounded hover:bg-red-200 dark:hover:bg-red-900/60">Borrar</button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Imagen pequeña que aparece en la pestaña del navegador (32x32px).</p>
                    </div>
                </div>

                <!-- Colores -->
                <div class="space-y-6">
                    <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700 pb-2">Identidad de Color</h3>
                    
                    <div>
                        <label for="color_principal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color Principal</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_principal" id="color_principal" type="color" class="h-10 w-20 rounded border border-gray-300 cursor-pointer" />
                            <input v-model="form.color_principal" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 uppercase bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usado en encabezados, botones principales y énfasis.</p>
                    </div>

                    <div>
                        <label for="color_secundario" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color Secundario</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_secundario" id="color_secundario" type="color" class="h-10 w-20 rounded border border-gray-300 cursor-pointer" />
                            <input v-model="form.color_secundario" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 uppercase bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usado en elementos decorativos y fondos sutiles.</p>
                    </div>

                    <div>
                        <label for="color_terciario" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Color Terciario / Alertas</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_terciario" id="color_terciario" type="color" class="h-10 w-20 rounded border border-gray-300 cursor-pointer" />
                            <input v-model="form.color_terciario" type="text" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 uppercase bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Usado en notas informativas y elementos de soporte.</p>
                    </div>

                    <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Vista Previa</h4>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="px-4 py-2 rounded text-white text-sm font-medium" :style="{ backgroundColor: form.color_principal }">Principal</button>
                            <button type="button" class="px-4 py-2 rounded text-white text-sm font-medium" :style="{ backgroundColor: form.color_secundario }">Secundario</button>
                            <button type="button" class="px-4 py-2 rounded text-white text-sm font-medium" :style="{ backgroundColor: form.color_terciario }">Terciario</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const logoPreview = ref(null);
const faviconPreview = ref(null);

const logoForm = useForm({ logo: null });
const faviconForm = useForm({ favicon: null });

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const handleFaviconChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        faviconForm.favicon = file;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const subirLogo = () => {
    if (logoForm.logo) {
        logoForm.post(route('empresa-configuracion.subir-logo'), {
            onSuccess: () => {
                notyf.success('Logo actualizado');
                logoForm.logo = null;
                // Defer reload to user manual reload or let Inertia handle prop update if possible
                // Using hard reload for now to ensure global layout updates
                window.location.reload();
            },
            onError: () => notyf.error('Error al subir logo')
        });
    }
};

const subirFavicon = () => {
    if (faviconForm.favicon) {
        faviconForm.post(route('empresa-configuracion.subir-favicon'), {
            onSuccess: () => {
                notyf.success('Favicon actualizado');
                faviconForm.favicon = null;
                window.location.reload();
            },
            onError: () => notyf.error('Error al subir favicon')
        });
    }
};

const eliminarLogo = () => {
    if (confirm('¿Eliminar logo actual?')) {
        router.delete(route('empresa-configuracion.eliminar-logo'), {
            onSuccess: () => {
                notyf.success('Logo eliminado');
                window.location.reload();
            }
        });
    }
};

const eliminarFavicon = () => {
    if (confirm('¿Eliminar favicon actual?')) {
        router.delete(route('empresa-configuracion.eliminar-favicon'), {
            onSuccess: () => {
                notyf.success('Favicon eliminado');
                window.location.reload();
            }
        });
    }
};
</script>

