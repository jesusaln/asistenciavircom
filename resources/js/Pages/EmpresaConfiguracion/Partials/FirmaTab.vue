<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6 flex items-center gap-2">
                <FontAwesomeIcon icon="pen-nib" class="text-blue-600 dark:text-blue-400" />
                Firma Digital Autógrafa
            </h2>

            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <FontAwesomeIcon icon="info-circle" class="text-blue-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Dibuja tu firma en el recuadro de abajo. Esta firma se utilizará para "firmar" digitalmente de forma autógrafa documentos como Constancias de Liquidación y comprobantes de pago.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Firma Digital del Representante -->
            <div class="pt-6">
                <div class="flex flex-col gap-3 max-w-xl">
                    <div class="bg-white dark:bg-gray-950 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl overflow-hidden flex items-center justify-center relative w-full h-64 shadow-inner" style="touch-action: none;">
                        <!-- Vista de firma guardada -->
                        <img v-if="form.firma_digital && !isDrawingFirma" :src="form.firma_digital" class="max-h-[85%] max-w-[85%] object-contain pointer-events-none drop-shadow-sm" />
                        
                        <!-- Canvas para dibujar -->
                        <canvas v-show="isDrawingFirma" ref="signaturePad" class="absolute inset-0 w-full h-full cursor-crosshair z-10"
                            @mousedown="startDrawingFirma" @mousemove="drawFirma" @mouseup="stopDrawingFirma" @mouseleave="stopDrawingFirma"
                            @touchstart.prevent="startDrawingFirma" @touchmove.prevent="drawFirma" @touchend.prevent="stopDrawingFirma"
                        ></canvas>
                        
                        <!-- Placeholder -->
                        <div v-if="!isDrawingFirma && !form.firma_digital" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <FontAwesomeIcon icon="pen-fancy" class="text-gray-200 dark:text-gray-800 text-6xl mb-4" />
                            <span class="text-gray-400 dark:text-gray-600 text-sm font-bold uppercase tracking-widest opacity-50 border-b border-gray-300 dark:border-gray-700">Firmar Aquí</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                         <button v-if="!isDrawingFirma" @click="startEditFirma" type="button" class="flex-1 lg:flex-none text-sm font-bold uppercase py-3 px-6 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20 active:scale-95">
                             {{ form.firma_digital ? 'Dibujar Nueva Firma' : 'Iniciar Trazo' }}
                         </button>
                         <button v-if="isDrawingFirma" @click="clearFirma" type="button" class="flex-1 lg:flex-none text-sm font-bold uppercase py-3 px-6 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300 transition-all">
                             Limpiar
                         </button>
                         <button v-if="isDrawingFirma" @click="saveFirma" type="button" class="flex-1 lg:flex-none text-sm font-bold uppercase py-3 px-6 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all active:scale-95">
                             Capturar
                         </button>
                         <button v-if="form.firma_digital && !isDrawingFirma" @click="removeFirma" type="button" class="text-xs font-bold text-red-600 hover:text-red-700 px-4 py-2 ml-auto">
                             Descartar Firma
                         </button>
                    </div>
                </div>
                
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <FontAwesomeIcon icon="shield-alt" class="text-blue-500 mb-2" />
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">Seguro</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">La firma se guarda de forma segura en tu base de datos local.</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <FontAwesomeIcon icon="print" class="text-emerald-500 mb-2" />
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">Automático</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Aparecerá automáticamente en todos los PDFs oficiales que generes.</p>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <FontAwesomeIcon icon="mouse-pointer" class="text-purple-500 mb-2" />
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">Fácil</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Puedes usar tu mouse o una tableta digitalizadora para mayor precisión.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { notyf } from '@/Utils/notyf.js';

const props = defineProps({
    form: { type: Object, required: true },
});

const isDrawingFirma = ref(false);
const signaturePad = ref(null);
let firmaContext = null;
let isDrawingNow = false;

const startEditFirma = () => {
    isDrawingFirma.value = true;
    setTimeout(() => {
        if (signaturePad.value) {
            const rect = signaturePad.value.parentElement.getBoundingClientRect();
            signaturePad.value.width = rect.width;
            signaturePad.value.height = rect.height;
            
            firmaContext = signaturePad.value.getContext('2d');
            firmaContext.clearRect(0, 0, signaturePad.value.width, signaturePad.value.height);
            firmaContext.lineWidth = 3;
            firmaContext.lineCap = 'round';
            firmaContext.lineJoin = 'round';
            firmaContext.strokeStyle = '#000080';
        }
    }, 50);
};

const getCanvasPosition = (e) => {
    if (!signaturePad.value) return { x: 0, y: 0 };
    const rect = signaturePad.value.getBoundingClientRect();
    const scaleX = signaturePad.value.width / rect.width;
    const scaleY = signaturePad.value.height / rect.height;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    return {
        x: (clientX - rect.left) * scaleX,
        y: (clientY - rect.top) * scaleY
    };
};

const startDrawingFirma = (e) => {
    if (!isDrawingFirma.value) return;
    isDrawingNow = true;
    const pos = getCanvasPosition(e);
    firmaContext.beginPath();
    firmaContext.moveTo(pos.x, pos.y);
};

const drawFirma = (e) => {
    if (!isDrawingNow) return;
    const pos = getCanvasPosition(e);
    firmaContext.lineTo(pos.x, pos.y);
    firmaContext.stroke();
};

const stopDrawingFirma = () => {
    if (isDrawingNow) {
        firmaContext.closePath();
        isDrawingNow = false;
    }
};

const clearFirma = () => {
    if (firmaContext && signaturePad.value) {
        firmaContext.clearRect(0, 0, signaturePad.value.width, signaturePad.value.height);
    }
};

const saveFirma = () => {
    if (signaturePad.value) {
        props.form.firma_digital = signaturePad.value.toDataURL('image/png');
        isDrawingFirma.value = false;
        notyf.success('Firma capturada. ¡No olvides guardar cambios al finalizar!');
    }
};

const removeFirma = () => {
    if (confirm('¿Deseas borrar la firma actual?')) {
        props.form.firma_digital = null;
    }
};
</script>
