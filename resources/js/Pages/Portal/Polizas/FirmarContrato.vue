<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import ClientLayout from '../Layout/ClientLayout.vue';

const props = defineProps({
    poliza: Object,
    empresa: Object,
});

// Canvas para la firma
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
let ctx = null;

// Drawing state
let points = [];
let lastVelocity = 0;
let lastWidth = 0;

// Formulario
const form = useForm({
    firma: '',
    nombre_firmante: '',
    acepta_terminos: false,
});

// Configuración de Pluma - Ajustada para máximo realismo 2.0
const penConfig = {
    color: '#001a5e', // Azul marino "tinta" institucional
    minWidth: 1.0,
    maxWidth: 3.5,
    pressureInfluence: 0.7, // Qué tanto afecta la presión (si el dispositivo la soporta)
    velocityFilterWeight: 0.4, // Suavizado de ancho de línea
    globalAlpha: 0.98,
};

// Inicializar canvas con Alta Resolución (Retina Support)
onMounted(() => {
    const canvas = canvasRef.value;
    if (canvas) {
        // Ajustar resolución DPI
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx = canvas.getContext('2d', { desynchronized: true }); // Mayor rendimiento
        ctx.scale(ratio, ratio);

        // Estilos iniciales
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width / ratio, canvas.height / ratio);

        // Guía de firma (línea tenue)
        dibujarGuia(canvas.width / ratio, canvas.height / ratio);

        // Eventos unificados (Pointer Events)
        canvas.addEventListener('pointerdown', handlePointerStart);
        canvas.addEventListener('pointermove', handlePointerMove);
        canvas.addEventListener('pointerup', handlePointerEnd);
        canvas.addEventListener('pointerleave', handlePointerEnd);
        canvas.addEventListener('pointercancel', handlePointerEnd);
    }
});

const dibujarGuia = (w, h) => {
    ctx.save();
    ctx.strokeStyle = '#f3f4f6';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(w * 0.08, h * 0.82);
    ctx.lineTo(w * 0.92, h * 0.82);
    ctx.stroke();
    
    ctx.font = '700 16px serif';
    ctx.fillStyle = '#d1d5db';
    ctx.fillText('X', w * 0.05, h * 0.82);
    
    ctx.font = 'italic 10px sans-serif';
    ctx.fillStyle = '#9ca3af';
    ctx.fillText('FIRMA AQUÍ', w * 0.08, h * 0.88);
    ctx.restore();
};

const getPointerPos = (e) => {
    const canvas = canvasRef.value;
    const rect = canvas.getBoundingClientRect();
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
        pressure: e.pressure || 0.5,
        time: Date.now()
    };
};

const handlePointerStart = (e) => {
    if (e.button !== 0 && e.pointerType === 'mouse') return;
    e.preventDefault();
    isDrawing.value = true;
    const pos = getPointerPos(e);
    points = [pos, pos, pos]; // Inicializar con el mismo punto para suavidad
    lastWidth = (penConfig.minWidth + penConfig.maxWidth) / 2;
};

const handlePointerMove = (e) => {
    if (!isDrawing.value) return;
    e.preventDefault();
    
    const point = getPointerPos(e);
    points.push(point);

    if (points.length > 3) {
        const p0 = points[points.length - 4];
        const p1 = points[points.length - 3];
        const p2 = points[points.length - 2];
        const p3 = points[points.length - 1];
        drawCurve(p0, p1, p2, p3);
        hasDrawn.value = true;
    }
};

const handlePointerEnd = () => {
    if (isDrawing.value && points.length < 3 && points.length > 0) {
        const pos = points[0];
        ctx.beginPath();
        ctx.arc(pos.x, pos.y, penConfig.minWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = penConfig.color;
        ctx.fill();
    }
    isDrawing.value = false;
    points = [];
};

// Interpolación y dibujo avanzado con variación de ancho per-segmento
const drawCurve = (p0, p1, p2, p3) => {
    const dist = Math.sqrt(Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2));
    const time = p2.time - p1.time;
    const velocity = time > 0 ? dist / time : 0;
    
    // Cálculo de ancho objetivo basado en velocidad y presión
    let targetWidth;
    if (p2.pressure && p2.pressure !== 0.5 && p2.pressure > 0) {
        // Dispositivo con presión (Stylus, iPad, etc)
        targetWidth = penConfig.minWidth + (penConfig.maxWidth - penConfig.minWidth) * p2.pressure;
    } else {
        // Mouse o touch sin presión (usamos velocidad)
        targetWidth = Math.max(penConfig.minWidth, Math.min(penConfig.maxWidth, penConfig.maxWidth / (velocity * 0.3 + 1)));
    }
    
    const startWidth = lastWidth;
    const endWidth = lastWidth + (targetWidth - lastWidth) * penConfig.velocityFilterWeight;

    // Dibujamos la curva en pequeños segmentos para interpolar el ancho y crear "tinta"
    const steps = 12;
    let prevX = p1.x;
    let prevY = p1.y;

    for (let i = 1; i <= steps; i++) {
        const t = i / steps;
        // Catmull-Rom
        const x = 0.5 * (
            (2 * p1.x) +
            (-p0.x + p2.x) * t +
            (2 * p0.x - 5 * p1.x + 4 * p2.x - p3.x) * t * t +
            (-p0.x + 3 * p1.x - 3 * p2.x + p3.x) * t * t * t
        );
        const y = 0.5 * (
            (2 * p1.y) +
            (-p0.y + p2.y) * t +
            (2 * p0.y - 5 * p1.y + 4 * p2.y - p3.y) * t * t +
            (-p0.y + 3 * p1.y - 3 * p2.y + p3.y) * t * t * t
        );
        
        const currentWidth = startWidth + (endWidth - startWidth) * t;
        
        ctx.beginPath();
        ctx.moveTo(prevX, prevY);
        ctx.lineTo(x, y);
        
        // Efecto de tinta: ligeramente más transparente si es más rápido/fino
        const speedAlpha = Math.max(0.7, Math.min(penConfig.globalAlpha, 1 - (velocity * 0.05)));
        ctx.strokeStyle = penConfig.color;
        ctx.globalAlpha = speedAlpha;
        ctx.lineWidth = currentWidth;
        ctx.stroke();
        
        prevX = x;
        prevY = y;
    }

    lastWidth = endWidth;
};

// Resetear
const limpiarFirma = () => {
    const canvas = canvasRef.value;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width / ratio, canvas.height / ratio);
    dibujarGuia(canvas.width / ratio, canvas.height / ratio);
    hasDrawn.value = false;
    form.firma = '';
};

// Enviar firma
const enviarFirma = () => {
    if (!hasDrawn.value) {
        alert('Por favor, dibuja tu firma en el recuadro.');
        return;
    }
    
    // Convertir canvas a base64
    form.firma = canvasRef.value.toDataURL('image/png');
    
    form.post(route('portal.polizas.firmar.store', props.poliza.id), {
        onSuccess: () => {
            // Redireccionado automáticamente
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-MX', { 
        day: '2-digit', month: 'long', year: 'numeric' 
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};
</script>

<template>
    <Head :title="`Firmar Contrato ${poliza.folio}`" />

    <ClientLayout :empresa="empresa">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header con Badge de Seguridad -->
            <div class="mb-8 text-center relative">
                <div class="absolute top-0 right-0 hidden md:block">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100 text-[10px] font-black uppercase tracking-widest shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Conexión Segura SSL
                    </div>
                </div>
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white text-4xl mb-6 shadow-2xl transform hover:rotate-3 transition-transform">
                    ✍️
                </div>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Firma Digital del Contrato</h1>
                <p class="text-gray-500 mt-2 flex items-center justify-center gap-2">
                    Póliza <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md font-bold text-sm">{{ poliza.folio }}</span>
                </p>
            </div>

            <!-- Resumen del Contrato -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mb-8">
                <h2 class="font-black text-gray-800 uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-sm">📋</span>
                    Resumen del Contrato
                </h2>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400 text-xs uppercase font-bold">Plan</p>
                        <p class="font-bold text-gray-800">{{ poliza.plan_poliza?.nombre || poliza.nombre }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase font-bold">Monto Mensual</p>
                        <p class="font-bold text-emerald-600">{{ formatCurrency(poliza.monto_mensual) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase font-bold">Vigencia Desde</p>
                        <p class="font-bold text-gray-800">{{ formatDate(poliza.fecha_inicio) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs uppercase font-bold">Vigencia Hasta</p>
                        <p class="font-bold text-gray-800">{{ formatDate(poliza.fecha_fin) }}</p>
                    </div>
                </div>

                <div class="mt-4 p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <p class="text-xs text-amber-700 font-medium">
                        ⚠️ <strong>Importante:</strong> Al firmar este contrato, aceptas los términos y condiciones del servicio, 
                        incluyendo las cláusulas de pago, cancelación y uso de los servicios incluidos en tu póliza.
                    </p>
                </div>

                <div class="mt-4">
                    <a :href="route('portal.polizas.contrato.pdf', poliza.id)" 
                       target="_blank"
                       class="inline-flex items-center gap-2 text-blue-600 font-bold text-sm hover:underline">
                        📄 Ver contrato completo (PDF)
                    </a>
                </div>
            </div>

            <!-- Área de Firma -->
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mb-8">
                <h2 class="font-black text-gray-800 uppercase text-xs tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-sm">✍️</span>
                    Tu Firma
                </h2>

                <!-- Canvas de Firma con Marca de Agua -->
                <div class="relative mb-4 signature-container group">
                    <!-- Marca de Agua Faint -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden opacity-[0.03]">
                        <span class="text-[80px] font-black uppercase tracking-[20px] -rotate-12">{{ empresa.nombre_corto || 'VIRCOM' }}</span>
                    </div>

                    <canvas
                        ref="canvasRef"
                        width="600"
                        height="240"
                        class="w-full border-2 border-dashed border-gray-300 rounded-2xl cursor-crosshair touch-none shadow-inner transition-colors"
                        :class="{ 'border-blue-500 border-solid bg-blue-50/10': hasDrawn }"
                    ></canvas>
                    
                    <!-- Overlay de Guía -->
                    <div v-if="!hasDrawn" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none transition-opacity duration-300 group-hover:opacity-60">
                        <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-3 shadow-sm border border-gray-100">
                            <span class="text-2xl animate-bounce">🖋️</span>
                        </div>
                        <p class="text-gray-600 text-base font-bold">Escribe tu firma aquí</p>
                        <p class="text-gray-400 text-[10px] uppercase tracking-[0.2em] mt-2 font-black">Dedo • Mouse • Stylus</p>
                    </div>

                    <!-- Badge de Certificación -->
                    <div v-if="hasDrawn" class="absolute bottom-4 right-4 flex items-center gap-2 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-[9px] font-black uppercase tracking-widest shadow-lg animate-in fade-in zoom-in duration-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 box"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Firma Capturada
                    </div>
                </div>

                <div class="flex items-center justify-between mb-8">
                    <button 
                        @click="limpiarFirma" 
                        type="button"
                        class="inline-flex items-center gap-2 text-xs text-gray-400 font-bold hover:text-red-500 transition-colors uppercase tracking-widest px-2 py-1"
                    >
                        <span class="text-lg">clear</span> Borrar y reintentar
                    </button>
                    <div class="text-[10px] text-gray-300 font-mono">ID: {{ poliza.folio }}-{{ Math.random().toString(36).substr(2, 6).toUpperCase() }}</div>
                </div>

                <!-- Nombre del firmante -->
                <div class="mb-6">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">
                        Nombre completo del firmante
                    </label>
                    <input
                        v-model="form.nombre_firmante"
                        type="text"
                        placeholder="Ej: Juan Pérez García"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all font-medium"
                        :class="{ 'border-red-300': form.errors.nombre_firmante }"
                    />
                    <p v-if="form.errors.nombre_firmante" class="mt-1 text-xs text-red-500 font-medium">
                        {{ form.errors.nombre_firmante }}
                    </p>
                </div>

                <!-- Checkbox de aceptación -->
                <div class="mb-6">
                    <label class="flex items-start gap-3 cursor-pointer group">
                        <input
                            v-model="form.acepta_terminos"
                            type="checkbox"
                            class="mt-1 w-5 h-5 rounded border-2 border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span class="text-sm text-gray-600 group-hover:text-gray-800 transition-colors">
                            He leído y acepto los <strong>términos y condiciones</strong> del contrato de póliza de servicio, 
                            incluyendo las cláusulas de pago, renovación y cancelación.
                        </span>
                    </label>
                    <p v-if="form.errors.acepta_terminos" class="mt-1 text-xs text-red-500 font-medium">
                        {{ form.errors.acepta_terminos }}
                    </p>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <Link 
                        :href="route('portal.polizas.show', poliza.id)" 
                        class="flex-1 px-6 py-4 bg-gray-100 text-gray-700 rounded-xl font-black text-sm uppercase tracking-widest text-center hover:bg-gray-200 transition-all"
                    >
                        ← Cancelar
                    </Link>
                    <button
                        @click="enviarFirma"
                        :disabled="form.processing || !hasDrawn || !form.nombre_firmante || !form.acepta_terminos"
                        class="flex-[2] relative px-6 py-4 bg-gray-900 text-white rounded-2xl font-black text-sm uppercase tracking-[0.2em] text-center hover:bg-black transition-all shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed group"
                    >
                        <div v-if="form.processing" class="flex items-center justify-center gap-3">
                            <span class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span>Procesando Firma...</span>
                        </div>
                        <div v-else class="flex items-center justify-center gap-2">
                            <span>Finalizar y Firmar Contrato</span>
                            <span class="text-xl group-hover:translate-x-1 transition-transform">→</span>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Información Legal -->
            <div class="text-center text-xs text-gray-400">
                <p>
                    🔒 Tu firma será cifrada y almacenada de forma segura.
                    <br>
                    Al firmar, se generará un código hash único que garantiza la integridad del documento.
                </p>
            </div>
        </div>
    </ClientLayout>
</template>

<style scoped>
canvas {
    touch-action: none;
    /* Efecto de papel sutil */
    background-color: #ffffff;
    background-image: 
        radial-gradient(#e5e7eb 0.5px, transparent 0.5px),
        radial-gradient(#e5e7eb 0.5px, #ffffff 0.5px);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
}

.signature-container {
    background: #fdfdfd;
    position: relative;
    overflow: hidden;
}

.signature-container::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
    opacity: 0.03;
    z-index: 10;
}
</style>
