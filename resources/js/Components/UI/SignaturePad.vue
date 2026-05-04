<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: String,
    label: {
        type: String,
        default: 'Firma'
    },
    placeholder: {
        type: String,
        default: 'Firme aquí'
    },
    error: String,
    height: {
        type: Number,
        default: 200
    }
});

const emit = defineEmits(['update:modelValue', 'clear']);

const canvas = ref(null);
const ctx = ref(null);
const isDrawing = ref(false);
const isEmpty = ref(true);

const initCanvas = () => {
    if (!canvas.value) return;
    
    ctx.value = canvas.value.getContext('2d');
    ctx.value.strokeStyle = '#000000';
    ctx.value.lineWidth = 2;
    ctx.value.lineCap = 'round';
    ctx.value.lineJoin = 'round';

    // Ajustar resolución
    const rect = canvas.value.getBoundingClientRect();
    canvas.value.width = rect.width;
    canvas.value.height = rect.height;
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
    ctx.value.beginPath();
    ctx.value.moveTo(x, y);
    e.preventDefault();
};

const draw = (e) => {
    if (!isDrawing.value) return;
    const { x, y } = getPos(e);
    ctx.value.lineTo(x, y);
    ctx.value.stroke();
    isEmpty.value = false;
    e.preventDefault();
};

const stopDrawing = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    ctx.value.closePath();
    saveSignature();
};

const clear = () => {
    ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
    isEmpty.value = true;
    emit('update:modelValue', null);
    emit('clear');
};

const saveSignature = () => {
    if (isEmpty.value) return;
    const dataUrl = canvas.value.toDataURL('image/png');
    emit('update:modelValue', dataUrl);
};

onMounted(() => {
    initCanvas();
    window.addEventListener('resize', initCanvas);
});

onUnmounted(() => {
    window.removeEventListener('resize', initCanvas);
});
</script>

<template>
    <div class="signature-pad-container">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">{{ label }}</label>
        
        <div class="relative border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-white">
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
            
            <div v-if="isEmpty" class="absolute inset-0 flex items-center justify-center pointer-events-none text-gray-400 font-medium italic">
                {{ placeholder }}
            </div>

            <div class="absolute bottom-2 right-2 flex gap-2">
                <button 
                    type="button" 
                    @click="clear"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 p-1.5 rounded-lg text-xs font-bold transition-colors uppercase tracking-wider"
                >
                    Limpiar
                </button>
            </div>
        </div>
        
        <p v-if="error" class="mt-1 text-xs text-red-600 font-medium">{{ error }}</p>

        <div v-if="modelValue && !isEmpty" class="mt-2 p-2 bg-gray-50 rounded-lg border border-gray-100 flex items-center gap-3">
             <div class="w-12 h-8 bg-white border border-gray-200 rounded p-1">
                 <img :src="modelValue" class="w-full h-full object-contain">
             </div>
             <span class="text-[10px] font-bold text-green-600 uppercase">Firma capturada correctamente</span>
        </div>
    </div>
</template>

<style scoped>
.signature-pad-container {
    width: 100%;
}
canvas {
    display: block;
}
</style>
