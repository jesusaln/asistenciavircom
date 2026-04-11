<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <FontAwesomeIcon icon="palette" class="text-purple-600 dark:text-purple-400" />
                </div>
                Identidad Visual
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Logos y Favicon -->
                <div class="space-y-8">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-800 pb-2">Logotipos y Marca</h3>
                    
                    <!-- Logo Principal -->
                    <div class="bg-gray-50 dark:bg-slate-900/50 p-6 rounded-2xl border border-gray-100 dark:border-slate-800 transition-all hover:shadow-md group">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Logo Principal</label>
                         <div class="flex flex-col sm:flex-row items-center gap-6">
                            <div class="relative w-32 h-32 border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-2xl flex items-center justify-center bg-white dark:bg-slate-950 overflow-hidden group-hover:border-blue-400 transition-colors">
                                <img v-if="logoPreview || form.logo_url" :src="logoPreview || form.logo_url" class="max-w-full max-h-full p-2 object-contain" />
                                <FontAwesomeIcon v-else icon="image" class="text-gray-200 dark:text-slate-800 text-5xl" />
                                
                                <!-- Loading Overlay -->
                                <div v-if="uploadingLogo" class="absolute inset-0 bg-white/80 dark:bg-slate-950/80 backdrop-blur-sm flex flex-col items-center justify-center">
                                    <FontAwesomeIcon icon="spinner" spin class="text-blue-600 text-2xl mb-2" />
                                    <span class="text-[10px] font-bold text-blue-600 uppercase">Subiendo...</span>
                                </div>
                            </div>
                            
                            <div class="flex-1 w-full sm:w-auto">
                                <div class="relative">
                                    <input 
                                        type="file" 
                                        id="logo-upload"
                                        @change="handleLogoChange" 
                                        accept="image/*" 
                                        class="hidden" 
                                    />
                                    <label 
                                        for="logo-upload"
                                        class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-all shadow-sm"
                                    >
                                        <FontAwesomeIcon icon="cloud-upload-alt" class="text-blue-500" />
                                        Seleccionar Logo
                                    </label>
                                </div>
                                
                                <div class="mt-4 flex flex-wrap gap-2">
                                     <button v-if="form.logo_url" @click="eliminarLogo" type="button" class="flex-1 sm:flex-none text-xs font-bold text-red-500 hover:text-red-700 px-3 py-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                        <FontAwesomeIcon icon="trash-alt" class="mr-1" /> Eliminar Actual
                                     </button>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-3 leading-relaxed">
                                    Formato WebP/PNG recomendado (200x200px). <br/>
                                    El archivo se subirá automáticamente al seleccionar.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Favicon -->
                    <div class="bg-gray-50 dark:bg-slate-900/50 p-6 rounded-2xl border border-gray-100 dark:border-slate-800 transition-all hover:shadow-md group">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Favicon (Icono de pestaña)</label>
                         <div class="flex flex-col sm:flex-row items-center gap-6">
                            <div class="relative w-16 h-16 border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-xl flex items-center justify-center bg-white dark:bg-slate-950 overflow-hidden group-hover:border-blue-400 transition-colors">
                                <img v-if="faviconPreview || form.favicon_url" :src="faviconPreview || form.favicon_url" class="max-w-full max-h-full p-2 object-contain" />
                                <FontAwesomeIcon v-else icon="globe" class="text-gray-200 dark:text-slate-800 text-2xl" />
                                
                                <!-- Loading Overlay -->
                                <div v-if="uploadingFavicon" class="absolute inset-0 bg-white/80 dark:bg-slate-950/80 backdrop-blur-sm flex items-center justify-center">
                                    <FontAwesomeIcon icon="spinner" spin class="text-blue-600" />
                                </div>
                            </div>
                             <div class="flex-1 w-full sm:w-auto">
                                <input 
                                    type="file" 
                                    id="favicon-upload"
                                    @change="handleFaviconChange" 
                                    accept="image/*" 
                                    class="hidden" 
                                />
                                <label 
                                    for="favicon-upload"
                                    class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl text-xs font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer transition-all shadow-sm"
                                >
                                    <FontAwesomeIcon icon="image" class="text-blue-500" />
                                    Cambiar Favicon
                                </label>
                                <p class="text-[10px] text-gray-400 mt-2">Dimensión sugerida: 32x32px.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colores -->
                <div class="space-y-8">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-800 pb-2">Paleta de Colores</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
                            <label for="color_principal" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Color Principal</label>
                            <div class="flex gap-4">
                                <input v-model="form.color_principal" id="color_principal" type="color" class="h-12 w-16 rounded-xl border-0 p-1 cursor-pointer bg-gray-100 dark:bg-slate-800 shadow-inner" />
                                <div class="flex-1 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono">#</span>
                                    <input v-model="form.color_principal" type="text" class="w-full pl-8 h-12 rounded-xl border-gray-100 dark:border-slate-800 uppercase bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-blue-500" maxlength="7" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
                            <label for="color_secundario" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Color Secundario</label>
                            <div class="flex gap-4">
                                <input v-model="form.color_secundario" id="color_secundario" type="color" class="h-12 w-16 rounded-xl border-0 p-1 cursor-pointer bg-gray-100 dark:bg-slate-800 shadow-inner" />
                                <div class="flex-1 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono">#</span>
                                    <input v-model="form.color_secundario" type="text" class="w-full pl-8 h-12 rounded-xl border-gray-100 dark:border-slate-800 uppercase bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-blue-500" maxlength="7" />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm">
                            <label for="color_terciario" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Color de Soporte / Alertas</label>
                            <div class="flex gap-4">
                                <input v-model="form.color_terciario" id="color_terciario" type="color" class="h-12 w-16 rounded-xl border-0 p-1 cursor-pointer bg-gray-100 dark:bg-slate-800 shadow-inner" />
                                <div class="flex-1 relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono">#</span>
                                    <input v-model="form.color_terciario" type="text" class="w-full pl-8 h-12 rounded-xl border-gray-100 dark:border-slate-800 uppercase bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-white font-mono font-bold focus:ring-2 focus:ring-blue-500" maxlength="7" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview Card -->
                    <div class="bg-gradient-to-br from-gray-900 to-slate-950 dark:from-slate-900 dark:to-black p-8 rounded-3xl border border-slate-800 shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                             <FontAwesomeIcon icon="eye" size="5x" class="text-white" />
                        </div>
                        <h4 class="text-xs font-bold text-blue-400 uppercase tracking-[0.2em] mb-6">Previsualización Real</h4>
                        
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-4">
                                <button type="button" class="px-6 py-3 rounded-xl text-white text-sm font-bold shadow-lg transform transition active:scale-95" :style="{ backgroundColor: form.color_principal, boxShadow: `0 10px 15px -3px ${form.color_principal}40` }">
                                    Botón Principal
                                </button>
                                <button type="button" class="px-6 py-3 rounded-xl text-white text-sm font-bold shadow-md transform transition active:scale-95" :style="{ backgroundColor: form.color_secundario }">
                                    Secundario
                                </button>
                            </div>
                            
                            <div class="p-4 rounded-xl border border-slate-700 bg-slate-800/50 backdrop-blur-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full animate-pulse" :style="{ backgroundColor: form.color_terciario }"></div>
                                    <span class="text-xs font-medium text-slate-300">El color terciario se aplica en detalles y estados.</span>
                                </div>
                            </div>
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
const uploadingLogo = ref(false);
const uploadingFavicon = ref(false);

const logoForm = useForm({ logo: null });
const faviconForm = useForm({ favicon: null });

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
        subirLogo();
    }
};

const handleFaviconChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        faviconForm.favicon = file;
        faviconPreview.value = URL.createObjectURL(file);
        subirFavicon();
    }
};

const subirLogo = () => {
    if (!logoForm.logo) return;
    
    uploadingLogo.value = true;
    logoForm.post(route('empresa-configuracion.subir-logo'), {
        onSuccess: () => {
            notyf.success('Logotipo actualizado con éxito');
            logoForm.logo = null;
            // Delay a bit to show success before reload
            setTimeout(() => window.location.reload(), 800);
        },
        onError: (errors) => {
            uploadingLogo.value = false;
            const message = Object.values(errors)[0] || 'Error al subir logo';
            notyf.error(message);
        },
        onFinish: () => {
            if (logoForm.wasSuccessful) return;
            uploadingLogo.value = false;
        }
    });
};

const subirFavicon = () => {
    if (!faviconForm.favicon) return;
    
    uploadingFavicon.value = true;
    faviconForm.post(route('empresa-configuracion.subir-favicon'), {
        onSuccess: () => {
            notyf.success('Favicon actualizado');
            faviconForm.favicon = null;
            setTimeout(() => window.location.reload(), 800);
        },
        onError: () => {
            uploadingFavicon.value = false;
            notyf.error('Error al subir favicon');
        }
    });
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

