<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';

const props = defineProps({
    renta: Object,
    empresa: Object,
});

// Canvas para la firma
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
let ctx = null;

// Drawing state
let points = [];
let lastWidth = 0;

// Pasos del proceso
const currentStep = ref(1);

// Formulario
const form = useForm({
    firma: '',
    nombre_firmante: props.renta?.cliente?.nombre_razon_social || '',
    acepta_terminos: false,
    ine_frontal: props.renta?.ine_frontal || '',
    ine_trasera: props.renta?.ine_trasera || '',
    comprobante_domicilio: props.renta?.comprobante_domicilio || '',
    solicitud_renta: props.renta?.solicitud_renta || '',
});

const uploading = ref({
    ine_frontal: false,
    ine_trasera: false,
    comprobante_domicilio: false,
    solicitud_renta: false,
});

const handleFileUpload = async (e, type) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value[type] = true;
    const formData = new FormData();
    formData.append('documento', file);
    formData.append('tipo', type);

    try {
        const response = await axios.post(route('portal.rentas.upload', props.renta.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        form[type] = response.data.path;
        window.$toast?.success('Documento subido correctamente');
    } catch (error) {
        window.$toast?.error('Error al subir el documento');
    } finally {
        uploading.value[type] = false;
    }
};

const canProceedToSignature = computed(() => {
    return form.ine_frontal && form.ine_trasera && form.comprobante_domicilio;
});

// Configuración de Pluma - Realismo Premium
const penConfig = {
    color: '#001a5e',
    minWidth: 1.0,
    maxWidth: 3.5,
    velocityFilterWeight: 0.4,
    globalAlpha: 0.98,
};

// Inicializar canvas
onMounted(() => {
    const canvas = canvasRef.value;
    if (canvas) {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx = canvas.getContext('2d', { desynchronized: true });
        ctx.scale(ratio, ratio);

        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width / ratio, canvas.height / ratio);

        dibujarGuia(canvas.width / ratio, canvas.height / ratio);

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
    points = [pos, pos, pos];
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

const drawCurve = (p0, p1, p2, p3) => {
    const dist = Math.sqrt(Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2));
    const time = p2.time - p1.time;
    const velocity = time > 0 ? dist / time : 0;
    
    let targetWidth;
    if (p2.pressure && p2.pressure !== 0.5 && p2.pressure > 0) {
        targetWidth = penConfig.minWidth + (penConfig.maxWidth - penConfig.minWidth) * p2.pressure;
    } else {
        targetWidth = Math.max(penConfig.minWidth, Math.min(penConfig.maxWidth, penConfig.maxWidth / (velocity * 0.3 + 1)));
    }
    
    const startWidth = lastWidth;
    const endWidth = lastWidth + (targetWidth - lastWidth) * penConfig.velocityFilterWeight;

    const steps = 12;
    let prevX = p1.x;
    let prevY = p1.y;

    for (let i = 1; i <= steps; i++) {
        const t = i / steps;
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

const enviarFirma = () => {
    if (!hasDrawn.value) {
        window.$toast?.error('Por favor, dibuja tu firma en el recuadro.');
        return;
    }
    
    form.firma = canvasRef.value.toDataURL('image/png');
    
    form.post(route('portal.rentas.firmar.store', props.renta.id), {
        onSuccess: () => {
            window.$toast?.success('¡Contrato firmado con éxito!');
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
};
</script>

<template>
    <Head title="Firma de Contrato de Renta" />

    <ClientLayout :empresa="empresa">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <!-- Header con Badge de Seguridad -->
            <div class="mb-8 text-center relative">
                <div class="absolute top-0 right-0 hidden md:block">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100 text-[10px] font-black uppercase tracking-widest shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Firma Electrónica Avanzada
                    </div>
                </div>
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white text-4xl mb-6 shadow-2xl transform hover:rotate-3 transition-transform">
                    <font-awesome-icon icon="file-signature" />
                </div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white dark:text-white tracking-tight">Firma de Contrato</h1>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-2">
                    Contrato de Renta #{{ renta.numero_contrato || renta.id }}
                </p>
            </div>

            <!-- Resumen del Contrato -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-8 mb-8">
                <h2 class="font-black text-gray-800 dark:text-gray-100 dark:text-white uppercase text-xs tracking-widest mb-6 flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-sm">📄</span>
                    Resumen del Acuerdo
                </h2>
                
                <div class="grid md:grid-cols-2 gap-8 text-sm">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase font-black tracking-widest">Arrendatario</p>
                            <p class="font-bold text-gray-800 dark:text-gray-100 dark:text-white text-lg">{{ renta.cliente.nombre_razon_social }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase font-black tracking-widest">Monto Mensual</p>
                            <p class="font-black text-blue-600 dark:text-blue-400 text-2xl">{{ formatCurrency(renta.monto_mensual) }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase font-black tracking-widest">Vencimiento</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 dark:text-white uppercase">{{ formatDate(renta.fecha_fin) }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase font-black tracking-widest">Día de Pago</p>
                                <p class="font-bold text-gray-800 dark:text-gray-100 dark:text-white">Día {{ renta.dia_pago }} de cada mes</p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase font-black tracking-widest">Equipos Arrendados</p>
                            <ul class="mt-1 space-y-1">
                                <li v-for="equipo in renta.equipos" :key="equipo.id" class="text-xs font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    {{ equipo.nombre }} - S/N: {{ equipo.serie || 'N/A' }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                    <p class="text-xs text-blue-800 dark:text-blue-300 font-medium leading-relaxed">
                        🔍 <strong>Lectura Importante:</strong> Al firmar este documento, usted acepta los términos y condiciones estipulados en el contrato de arrendamiento oficial, incluyendo sus responsabilidades de cuidado del equipo y los periodos de pago.
                    </p>
                </div>
            </div>

                <!-- PASO 1: SUBIR DOCUMENTOS -->
                <div v-if="currentStep === 1" class="space-y-8 animate-fade-in">
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 p-8">
                        <h2 class="font-black text-gray-800 dark:text-gray-100 dark:text-white uppercase text-xs tracking-widest mb-8 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm">📁</span>
                            Paso 1: Documentación Requerida
                        </h2>

                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- INE Frontal -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">INE Frontal *</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-40 flex flex-col items-center justify-center text-center', form.ine_frontal ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700 hover:border-emerald-400']">
                                    <template v-if="uploading.ine_frontal">
                                        <font-awesome-icon icon="spinner" spin class="text-2xl text-emerald-500 mb-2" />
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.ine_frontal">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300">INE Frontal Lista</span>
                                        <button @click="form.ine_frontal = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <font-awesome-icon icon="id-card" class="text-3xl text-gray-200 mb-3 group-hover:text-emerald-400" />
                                        <input type="file" @change="e => handleFileUpload(e, 'ine_frontal')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>

                            <!-- INE Trasera -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">INE Trasera *</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-40 flex flex-col items-center justify-center text-center', form.ine_trasera ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700 hover:border-emerald-400']">
                                    <template v-if="uploading.ine_trasera">
                                        <font-awesome-icon icon="spinner" spin class="text-2xl text-emerald-500 mb-2" />
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.ine_trasera">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300">INE Trasera Lista</span>
                                        <button @click="form.ine_trasera = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <font-awesome-icon icon="id-card" class="text-3xl text-gray-200 mb-3 group-hover:text-emerald-400" />
                                        <input type="file" @change="e => handleFileUpload(e, 'ine_trasera')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Comprobante Domicilio -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Comprobante de Domicilio *</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-40 flex flex-col items-center justify-center text-center', form.comprobante_domicilio ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700 hover:border-emerald-400']">
                                    <template v-if="uploading.comprobante_domicilio">
                                        <font-awesome-icon icon="spinner" spin class="text-2xl text-emerald-500 mb-2" />
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.comprobante_domicilio">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300">Comprobante Listo</span>
                                        <button @click="form.comprobante_domicilio = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <font-awesome-icon icon="home" class="text-3xl text-gray-200 mb-3 group-hover:text-emerald-400" />
                                        <input type="file" @change="e => handleFileUpload(e, 'comprobante_domicilio')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Solicitud de Renta -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Solicitud de Renta (Opcional)</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-40 flex flex-col items-center justify-center text-center', form.solicitud_renta ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700 hover:border-emerald-400']">
                                    <template v-if="uploading.solicitud_renta">
                                        <font-awesome-icon icon="spinner" spin class="text-2xl text-emerald-500 mb-2" />
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.solicitud_renta">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300 dark:text-gray-300">Solicitud Lista</span>
                                        <button @click="form.solicitud_renta = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <font-awesome-icon icon="file-alt" class="text-3xl text-gray-200 mb-3 group-hover:text-emerald-400" />
                                        <input type="file" @change="e => handleFileUpload(e, 'solicitud_renta')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end">
                            <button 
                                @click="currentStep = 2"
                                :disabled="!canProceedToSignature"
                                class="px-10 py-5 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-3"
                            >
                                Continuar a la Firma
                                <font-awesome-icon icon="arrow-right" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: FIRMA DIGITAL -->
                <div v-if="currentStep === 2" class="animate-fade-in">
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 p-8 mb-8">
                        <h2 class="font-black text-gray-800 dark:text-gray-100 dark:text-white uppercase text-xs tracking-widest mb-6 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-orange-100 dark:bg-orange-900/50 text-orange-600 dark:text-orange-400 flex items-center justify-center text-sm">✍️</span>
                            Paso 2: Firma del Titular o Representante
                        </h2>

                        <div class="relative mb-6 signature-container group">
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none opacity-[0.03]">
                                <span class="text-[70px] font-black uppercase tracking-[15px] -rotate-12">{{ empresa?.nombre_corto || 'VIRCOM' }}</span>
                            </div>

                            <canvas
                                ref="canvasRef"
                                width="640"
                                height="280"
                                class="w-full border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-3xl cursor-crosshair touch-none shadow-inner transition-colors"
                                :class="{ 'border-blue-500 border-solid bg-blue-50/10 dark:bg-blue-900/20': hasDrawn }"
                            ></canvas>
                            
                            <div v-if="!hasDrawn" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none transition-opacity duration-300 group-hover:opacity-60">
                                <div class="w-16 h-16 rounded-full bg-white dark:bg-slate-900 dark:bg-gray-700 flex items-center justify-center mb-4 shadow-lg border border-gray-100 dark:border-gray-600">
                                    <span class="text-2xl animate-bounce">🖋️</span>
                                </div>
                                <p class="text-gray-900 dark:text-white dark:text-white text-lg font-black tracking-tight">Dibuja tu firma en este recuadro</p>
                                <p class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-[10px] uppercase tracking-[0.3em] mt-2 font-black">Certificado Digital</p>
                            </div>

                            <div v-if="hasDrawn" class="absolute bottom-6 right-6 flex items-center gap-3 px-4 py-2 bg-emerald-600 text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-xl">
                                <font-awesome-icon icon="check-circle" />
                                Firma Lista
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-10">
                            <button 
                                @click="limpiarFirma" 
                                type="button"
                                class="inline-flex items-center gap-2 text-[10px] text-gray-400 font-black hover:text-red-500 transition-colors uppercase tracking-[0.2em] px-4 py-2 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 rounded-xl"
                            >
                                <font-awesome-icon icon="eraser" /> Borrar y reintentar
                            </button>
                            <div class="text-[9px] text-gray-300 dark:text-gray-600 dark:text-gray-300 font-mono font-bold">SHA-256 DIGITAL ENCRYPTION ENABLED</div>
                        </div>

                        <!-- Campos de Validación -->
                        <div class="grid md:grid-cols-2 gap-8 mb-10">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Nombre Completo del Firmante
                                </label>
                                <input
                                    v-model="form.nombre_firmante"
                                    type="text"
                                    placeholder="Nombre y Apellidos"
                                    class="w-full px-6 py-4 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-black text-gray-900 dark:text-white dark:text-white shadow-inner"
                                    :class="{ 'ring-2 ring-red-400': form.errors.nombre_firmante }"
                                />
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-start gap-4 cursor-pointer group p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-800/30 transition-all hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                    <input
                                        v-model="form.acepta_terminos"
                                        type="checkbox"
                                        class="mt-1 w-6 h-6 rounded-lg border-2 border-blue-200 text-blue-600 focus:ring-blue-500 transition-all"
                                    />
                                    <span class="text-xs font-bold text-blue-900 dark:text-blue-300 leading-relaxed">
                                        Acepto que mi firma electrónica tiene la misma validez legal que una firma autógrafa para este contrato de renta.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex flex-col sm:flex-row gap-6">
                            <button 
                                @click="currentStep = 1" 
                                class="px-8 py-5 bg-white dark:bg-slate-900 dark:bg-gray-700 text-gray-500 dark:text-gray-400 dark:text-gray-400 rounded-2xl font-black text-xs uppercase tracking-widest text-center border-2 border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 transition-all"
                            >
                                <font-awesome-icon icon="arrow-left" class="mr-2" />
                                Modificar Documentos
                            </button>
                            <button
                                @click="enviarFirma"
                                :disabled="form.processing || !hasDrawn || !form.nombre_firmante || !form.acepta_terminos"
                                class="flex-1 px-8 py-5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl font-black text-sm uppercase tracking-widest text-center shadow-2xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed group/submit"
                            >
                                <div v-if="form.processing" class="flex items-center justify-center gap-3">
                                    <font-awesome-icon icon="spinner" spin />
                                    <span>Procesando Firma...</span>
                                </div>
                                <div v-else class="flex items-center justify-center gap-3">
                                    <font-awesome-icon icon="lock" class="opacity-50 group-hover/submit:opacity-100 transition-opacity" />
                                    <span>Firmar Contrato Permanentemente</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

            <!-- Footer Legal -->
            <div class="text-center px-10">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 font-bold uppercase tracking-[0.2em] leading-relaxed">
                    Este proceso de firma cumple con lo establecido en el Código de Comercio 
                    <br> y la Ley de Firma Electrónica Avanzada de México.
                </p>
            </div>
        </div>
    </ClientLayout>
</template>

<style scoped>
canvas {
    touch-action: none;
    background-color: #ffffff;
    background-image: 
        radial-gradient(#e5e7eb 0.5px, transparent 0.5px),
        radial-gradient(#e5e7eb 0.5px, #ffffff 0.5px);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
}

.dark canvas {
    background-color: #111827;
    background-image: 
        radial-gradient(#1f2937 0.5px, transparent 0.5px),
        radial-gradient(#1f2937 0.5px, #111827 0.5px);
}

.signature-container {
    background: #fdfdfd;
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
}

.dark .signature-container {
    background: #111827;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .7; }
}
</style>
