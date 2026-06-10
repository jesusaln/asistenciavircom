<script setup>
import { ref, useTemplateRef, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    modelValue: String,
    label: { type: String, default: 'Firma' },
    placeholder: { type: String, default: 'Firme aquí' },
    error: String,
    height: { type: Number, default: 200 }
});

const emit = defineEmits(['update:modelValue', 'clear']);

const canvas = useTemplateRef('canvas');
const ctx = ref(null);
const isDrawing = ref(false);
const isEmpty = ref(true);

// Para suavizado de trazo
let lastX = 0;
let lastY = 0;

const initCanvas = () => {
    if (!canvas.value) return;
    
    const rect = canvas.value.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    
    // Ajustar tamaño interno para alta resolución
    canvas.value.width = rect.width * dpr;
    canvas.value.height = rect.height * dpr;
    
    ctx.value = canvas.value.getContext('2d');
    ctx.value.scale(dpr, dpr);
    
    // Estilo del trazo - Azul profesional para firmas
    ctx.value.strokeStyle = '#2563eb'; 
    ctx.value.lineWidth = 2.5;
    ctx.value.lineCap = 'round';
    ctx.value.lineJoin = 'round';
    
    // Si hay un valor previo (y no es data:,), podríamos intentar dibujarlo, 
    // pero usualmente es mejor dejarlo limpio para nueva firma.
    if (!props.modelValue) {
        isEmpty.value = true;
    }
};

const getPos = (e) => {
    const rect = canvas.value.getBoundingClientRect();
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
};

const startDrawing = (e) => {
    isDrawing.value = true;
    const { x, y } = getPos(e);
    lastX = x;
    lastY = y;
    ctx.value.beginPath();
    ctx.value.moveTo(x, y);
    // Para evitar scroll en móviles
    if (e.touches) e.preventDefault();
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const { x, y } = getPos(e);
    
    // Dibujo con curva cuadrática para mayor suavidad
    ctx.value.quadraticCurveTo(lastX, lastY, (lastX + x) / 2, (lastY + y) / 2);
    ctx.value.stroke();
    
    lastX = x;
    lastY = y;
    isEmpty.value = false;
    
    if (e.touches) e.preventDefault();
};

const stopDrawing = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    ctx.value.lineTo(lastX, lastY);
    ctx.value.stroke();
    ctx.value.closePath();
    saveSignature();
};

const clear = () => {
    const dpr = window.devicePixelRatio || 1;
    ctx.value.clearRect(0, 0, canvas.value.width / dpr, canvas.value.height / dpr);
    isEmpty.value = true;
    emit('update:modelValue', null);
    emit('clear');
};

const saveSignature = () => {
    if (isEmpty.value) {
        emit('update:modelValue', null);
        return;
    }
    // Validar que el canvas no esté realmente vacío (pocos pixeles)
    const dataUrl = canvas.value.toDataURL('image/png');
    // Si el dataUrl es demasiado corto, es probable que esté vacío o corrupto
    if (dataUrl.length < 100) {
        emit('update:modelValue', null);
    } else {
        emit('update:modelValue', dataUrl);
    }
};

onMounted(() => {
    // Pequeño delay para asegurar que el DOM está listo y el rect es correcto
    setTimeout(initCanvas, 100);
    window.addEventListener('resize', initCanvas);
});

onUnmounted(() => {
    window.removeEventListener('resize', initCanvas);
});

// Resetear si el modelValue cambia externamente a null
watch(() => props.modelValue, (newVal) => {
    if (!newVal && !isEmpty.value) {
        clear();
    }
});
</script>

<template>
    <div class="signature-pad-container w-full">
        <label v-if="label" class="block text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide mb-2 ml-1">
            {{ label }}
        </label>
        
        <div class="relative border-2 border-dashed border-[var(--ui-border)] rounded-[1.5rem] overflow-hidden bg-white shadow-inner group">
            <canvas 
                ref="canvas"
                class="w-full cursor-crosshair touch-none"
                :style="{ height: height + 'px' }"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart="startDrawing"
                @touchmove="draw"
                @touchend="stopDrawing"
            ></canvas>
            
            <div v-if="isEmpty" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none transition-opacity duration-300 group-hover:opacity-40">
                <div class="text-[var(--ui-text-muted)] opacity-20 mb-2">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wide italic">{{ placeholder }}</span>
            </div>

            <div class="absolute bottom-3 right-3 flex gap-2">
                <button 
                    v-if="!isEmpty"
                    type="button" 
                    @click="clear"
                    class="bg-rose-50 hover:bg-rose-100 text-rose-500 w-8 h-8 rounded-full flex items-center justify-center transition-all shadow-sm active:scale-90"
                    title="Limpiar firma"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <p v-if="error" class="mt-2 text-xs text-rose-500 font-bold ml-1">{{ error }}</p>

        <div v-if="modelValue && !isEmpty" class="mt-3 p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center gap-3 animate-fade-in">
             <div class="w-10 h-10 bg-white border border-emerald-500/20 rounded-xl p-1 flex items-center justify-center">
                 <img :src="modelValue" class="max-w-full max-h-full object-contain">
             </div>
             <div class="flex flex-col">
                 <span class="text-[10px] font-black text-emerald-600 uppercase tracking-wide leading-none">Firma Lista</span>
                 <span class="text-[9px] text-emerald-500/70 font-bold uppercase tracking-wide mt-1">Se guardará al actualizar</span>
             </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
