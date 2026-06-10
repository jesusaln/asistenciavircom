<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="palette" class="text-purple-600" />
                Apariencia Visual
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Logos -->
                <div class="space-y-6">
                    <h3 class="text-md font-medium text-slate-900 dark:text-slate-100 border-b border-slate-200 dark:border-slate-700 pb-2">Logotipos</h3>
                    
                    <!-- Logo Principal -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Logo Principal</label>
                         <div class="mt-1 flex items-center gap-4">
                            <div class="w-16 h-16 border dark:border-slate-700 rounded-xl flex items-center justify-center bg-white dark:bg-slate-700 overflow-hidden">
                                <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" class="max-w-full max-h-full object-contain" />
                                <FontAwesomeIcon v-else icon="image" class="text-slate-300 text-3xl" />
                            </div>
                            <div class="flex-1">
                                <input type="file" @change="handleLogoChange" accept="image/*" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-sky-50 dark:bg-sky-900/20 file:text-sky-800 dark:text-sky-200 hover:file:bg-sky-100 mb-2" />
                                <div class="flex gap-2">
                                     <button v-if="logoForm.logo" @click="subirLogo" type="button" class="text-xs bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-xl hover:bg-blue-700 dark:hover:bg-blue-600">Subir</button>
                                     <button v-if="form.logo_url" @click="eliminarLogo" type="button" class="text-xs bg-rose-50 dark:bg-rose-900/20/40 text-rose-600 dark:text-rose-300 px-3 py-1 rounded-xl hover:bg-rose-200 dark:hover:bg-rose-900/60">Borrar Actual</button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Recomendado: 200x200px PNG transparente.</p>
                    </div>

                    <!-- Favicon -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Favicon</label>
                         <div class="mt-1 flex items-center gap-4">
                            <div class="w-10 h-10 border dark:border-slate-700 rounded-xl flex items-center justify-center bg-white dark:bg-slate-700 overflow-hidden">
                                <img v-if="faviconPreview || form.favicon_url" :src="faviconPreview || form.favicon_url" class="max-w-full max-h-full object-contain" />
                                <FontAwesomeIcon v-else icon="globe" class="text-slate-300 text-xl" />
                            </div>
                             <div class="flex-1">
                                <input type="file" @change="handleFaviconChange" accept="image/*" class="block w-full text-sm text-slate-500 dark:text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-sky-50 dark:bg-sky-900/20 file:text-sky-800 dark:text-sky-200 hover:file:bg-sky-100 mb-2" />
                                <div class="flex gap-2">
                                     <button v-if="faviconForm.favicon" @click="subirFavicon" type="button" class="text-xs bg-blue-600 dark:bg-blue-700 text-white px-3 py-1 rounded-xl hover:bg-blue-700 dark:hover:bg-blue-600">Subir</button>
                                      <button v-if="form.favicon_url" @click="eliminarFavicon" type="button" class="text-xs bg-rose-50 dark:bg-rose-900/20/40 text-rose-600 dark:text-rose-300 px-3 py-1 rounded-xl hover:bg-rose-200 dark:hover:bg-rose-900/60">Borrar</button>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Imagen pequeña que aparece en la pestaña del navegador (32x32px).</p>
                    </div>
                </div>

                <!-- Colores -->
                <div class="space-y-6">
                    <h3 class="text-md font-medium text-slate-900 dark:text-slate-100 border-b border-slate-200 dark:border-slate-700 pb-2">Identidad de Color</h3>
                    
                    <div>
                        <label for="color_principal" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Color Principal</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_principal" id="color_principal" type="color" class="h-10 w-20 rounded-xl border border-slate-300 cursor-pointer" />
                            <input v-model="form.color_principal" type="text" class="flex-1 rounded-xl border-slate-300 dark:border-slate-700 uppercase bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Usado en encabezados, botones principales y énfasis.</p>
                    </div>

                    <div>
                        <label for="color_secundario" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Color Secundario</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_secundario" id="color_secundario" type="color" class="h-10 w-20 rounded-xl border border-slate-300 cursor-pointer" />
                            <input v-model="form.color_secundario" type="text" class="flex-1 rounded-xl border-slate-300 dark:border-slate-700 uppercase bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Usado en elementos decorativos y fondos sutiles.</p>
                    </div>

                    <div>
                        <label for="color_terciario" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Color Terciario / Alertas</label>
                        <div class="flex gap-3">
                            <input v-model="form.color_terciario" id="color_terciario" type="color" class="h-10 w-20 rounded-xl border border-slate-300 cursor-pointer" />
                            <input v-model="form.color_terciario" type="text" class="flex-1 rounded-xl border-slate-300 dark:border-slate-700 uppercase bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" maxlength="7" />
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Usado en notas informativas y elementos de soporte.</p>
                    </div>

                    <div class="bg-white dark:bg-slate-700 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-2">Vista Previa</h4>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="px-4 py-2 rounded-xl text-white text-sm font-medium" :style="{ backgroundColor: form.color_principal }">Principal</button>
                            <button type="button" class="px-4 py-2 rounded-xl text-white text-sm font-medium" :style="{ backgroundColor: form.color_secundario }">Secundario</button>
                            <button type="button" class="px-4 py-2 rounded-xl text-white text-sm font-medium" :style="{ backgroundColor: form.color_terciario }">Terciario</button>
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
import Swal from '@/Utils/Swal';

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

const eliminarLogo = async () => {
    const result = await Swal.fire({
        title: 'Eliminar logo',
        text: '¿Estás seguro de que deseas eliminar el logo actual?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.eliminar-logo'), {
            onSuccess: () => {
                notyf.success('Logo eliminado');
                window.location.reload();
            }
        });
    }
};

const eliminarFavicon = async () => {
    const result = await Swal.fire({
        title: 'Eliminar favicon',
        text: '¿Estás seguro de que deseas eliminar el favicon actual?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.eliminar-favicon'), {
            onSuccess: () => {
                notyf.success('Favicon eliminado');
                window.location.reload();
            }
        });
    }
};
</script>

