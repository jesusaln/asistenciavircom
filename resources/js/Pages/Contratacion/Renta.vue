<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import axios from 'axios';

const props = defineProps({
    empresa: Object,
    plan: Object,
    clienteData: Object,
    catalogos: Object,
    pasarelas: Object,
});

// Pasos del proceso
const currentStep = ref(1);
const totalSteps = 4;

// Estados de UI
const metodoPago = ref('tarjeta');
const showPaymentModal = ref(false);
const processing = ref(false);
const searchingCp = ref(false);
const colonias = ref([]);
const paymentError = ref('');
const formError = ref('');
const page = usePage();

// Canvas para firma
const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
let ctx = null;
let points = [];
let lastWidth = 0;

// Estados de subida
const uploading = ref({
    ine_frontal: false,
    ine_trasera: false,
    comprobante_domicilio: false,
});

const form = useForm({
    // Datos personales
    nombre_razon_social: props.clienteData?.nombre_comercial || props.clienteData?.nombre_razon_social || '',
    email: props.clienteData?.email || '',
    telefono: props.clienteData?.telefono || '',
    
    password: '',
    password_confirmation: '',

    // Facturación
    requiere_factura: !!(props.clienteData?.rfc && props.clienteData?.rfc !== 'XAXX010101000'),
    tipo_persona: props.clienteData?.tipo_persona || 'fisica',
    rfc: props.clienteData?.rfc || '',
    razon_social: props.clienteData?.razon_social || '',
    regimen_fiscal: props.clienteData?.regimen_fiscal || '',
    uso_cfdi: props.clienteData?.uso_cfdi || 'G03',
    misma_direccion_fiscal: true,
    domicilio_fiscal_cp: props.clienteData?.domicilio_fiscal_cp || props.clienteData?.codigo_postal || '',
    domicilio_fiscal_calle: props.clienteData?.domicilio_fiscal_calle || '',
    domicilio_fiscal_numero: props.clienteData?.domicilio_fiscal_numero || '',
    domicilio_fiscal_colonia: props.clienteData?.domicilio_fiscal_colonia || '',
    domicilio_fiscal_municipio: props.clienteData?.domicilio_fiscal_municipio || '',
    domicilio_fiscal_estado: props.clienteData?.domicilio_fiscal_estado || '',
    
    // Dirección
    calle: props.clienteData?.calle || '',
    numero_exterior: props.clienteData?.numero_exterior || '',
    numero_interior: props.clienteData?.numero_interior || '',
    codigo_postal: props.clienteData?.codigo_postal || '',
    colonia: props.clienteData?.colonia || '',
    municipio: props.clienteData?.municipio || '',
    estado: props.clienteData?.estado || '',
    pais: props.clienteData?.pais || 'MX',

    // Documentos
    ine_frontal: '',
    ine_trasera: '',
    comprobante_domicilio: '',

    // Firma
    firma: '',
    nombre_firmante: props.clienteData?.nombre_razon_social || '',
    acepta_firma: false,

    // Pago
    metodo_pago: 'tarjeta',
    aceptar_terminos: false,
});

// Configuración de Pluma para firma
const penConfig = {
    color: '#001a5e',
    minWidth: 1.0,
    maxWidth: 3.5,
    velocityFilterWeight: 0.4,
    globalAlpha: 0.98,
};

// Validar código postal con Sepomex
const validarCodigoPostal = async () => {
    const cp = form.codigo_postal?.toString().replace(/\D/g, '');
    if (!cp || cp.length !== 5) return;

    searchingCp.value = true;
    try {
        const resp = await fetch(`/api/cp/${cp}`);
        if (resp.ok) {
            const data = await resp.json();
            colonias.value = data.colonias || [];
            if (data.municipio) form.municipio = data.municipio;
            if (data.estado) {
                const found = props.catalogos.estados?.find(e => 
                    e.label.toLowerCase().includes(data.estado.toLowerCase())
                );
                if (found) form.estado = found.value;
                else form.estado = data.estado;
            }
            if (colonias.value.length === 1) form.colonia = colonias.value[0];
        }
    } catch (e) {
        console.error('CP Search Error:', e);
    } finally {
        searchingCp.value = false;
    }
};

// Subir documentos
const handleFileUpload = async (e, type) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value[type] = true;
    const formData = new FormData();
    formData.append('documento', file);
    formData.append('tipo', type);

    try {
        const response = await axios.post('/api/upload-temp', formData, {
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

// Canvas para firma - inicializar
const initCanvas = () => {
    nextTick(() => {
        const canvas = canvasRef.value;
        if (!canvas) return;
        
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
    });
};

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
    if (!canvas || !ctx) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    ctx.clearRect(0, 0, canvas.width / ratio, canvas.height / ratio);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width / ratio, canvas.height / ratio);
    dibujarGuia(canvas.width / ratio, canvas.height / ratio);
    hasDrawn.value = false;
    form.firma = '';
};

// Validaciones por paso
const canProceedStep1 = computed(() => {
    return form.nombre_razon_social && form.email && form.telefono && 
           form.codigo_postal && form.calle && form.colonia && form.municipio;
});

const canProceedStep2 = computed(() => {
    return form.ine_frontal && form.ine_trasera && form.comprobante_domicilio;
});

const canProceedStep3 = computed(() => {
    return hasDrawn.value && form.nombre_firmante && form.acepta_firma;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const totalInversionInicial = computed(() => {
    const subtotal = Number(props.plan.precio_mensual) + Number(props.plan.deposito_garantia);
    return subtotal * 1.16;
});

const goToStep = (step) => {
    if (step === 3) {
        currentStep.value = step;
        initCanvas();
    } else {
        currentStep.value = step;
    }
};

const iniciarPago = () => {
    if (!form.aceptar_terminos) {
        formError.value = 'Debes aceptar los términos.';
        return;
    }
    showPaymentModal.value = true;
};

const confirmarPago = (metodo) => {
    if (metodo) metodoPago.value = metodo;
    form.metodo_pago = metodoPago.value;
    
    // Capturar firma como imagen
    if (canvasRef.value) {
        form.firma = canvasRef.value.toDataURL('image/png');
    }
    
    processing.value = true;
    
    form.post(route('contratacion.renta.procesar', props.plan.slug), {
        onSuccess: () => {
            processing.value = false;
            window.location.href = route('portal.dashboard');
        },
        onError: (errors) => {
            showPaymentModal.value = false;
            processing.value = false;
            paymentError.value = errors.error || 'Error al procesar.';
        }
    });
};

const cssVars = computed(() => ({
    '--color-primary': '#10b981',
    '--color-primary-soft': '#d1fae5',
    '--color-primary-dark': '#059669',
}));

</script>

<template>
    <Head :title="`Rentar ${plan.nombre}`" />

    <div class="min-h-screen bg-slate-50 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="rentas" />

        <main class="flex-grow py-12 px-4 max-w-7xl mx-auto w-full">
            
            <!-- Progress Steps -->
            <div class="mb-12">
                <div class="flex items-center justify-center gap-2 md:gap-4">
                    <template v-for="step in totalSteps" :key="step">
                        <div 
                            class="flex items-center gap-2 cursor-pointer group"
                            @click="step < currentStep ? goToStep(step) : null"
                        >
                            <div :class="[
                                'w-10 h-10 md:w-12 md:h-12 rounded-2xl flex items-center justify-center font-black text-sm transition-all',
                                currentStep === step 
                                    ? 'bg-emerald-600 text-white shadow-xl shadow-emerald-500/30' 
                                    : currentStep > step 
                                        ? 'bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200' 
                                        : 'bg-gray-100 text-gray-400'
                            ]">
                                <template v-if="currentStep > step">✓</template>
                                <template v-else>{{ step }}</template>
                            </div>
                            <span :class="[
                                'hidden md:block text-xs font-bold uppercase tracking-widest transition-colors',
                                currentStep >= step ? 'text-gray-900' : 'text-gray-400'
                            ]">
                                {{ step === 1 ? 'Datos' : step === 2 ? 'Documentos' : step === 3 ? 'Firma' : 'Pago' }}
                            </span>
                        </div>
                        <div v-if="step < totalSteps" :class="[
                            'w-8 md:w-16 h-1 rounded-full transition-colors',
                            currentStep > step ? 'bg-emerald-500' : 'bg-gray-200'
                        ]"></div>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <!-- Formulario Principal -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- PASO 1: DATOS PERSONALES Y DIRECCIÓN -->
                    <div v-if="currentStep === 1" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12 animate-fade-in">
                        <h2 class="text-3xl font-black text-gray-900 mb-10 flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">📋</span>
                            Datos de Contratación
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Nombre o Razón Social *</label>
                                <input v-model="form.nombre_razon_social" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Su nombre completo">
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Email *</label>
                                <input v-model="form.email" type="email" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="correo@ejemplo.com">
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Teléfono / WhatsApp *</label>
                                <input v-model="form.telefono" type="tel" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="10 dígitos">
                            </div>

                            <div v-if="!clienteData" class="md:col-span-2 bg-emerald-50/50 p-8 rounded-3xl border border-emerald-100/50">
                                <p class="text-xs font-black text-emerald-700 uppercase tracking-widest mb-4">Crear contraseña para su Portal de Cliente</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <input v-model="form.password" type="password" class="w-full px-6 py-4 bg-white border-none rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Contraseña">
                                    <input v-model="form.password_confirmation" type="password" class="w-full px-6 py-4 bg-white border-none rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Confirmar">
                                </div>
                            </div>

                            <!-- Ubicación con Sepomex -->
                            <div class="md:col-span-2 pt-8 border-t border-gray-50">
                                <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-8 flex items-center gap-3">
                                    📍 Ubicación de Instalación
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Código Postal *</label>
                                        <div class="relative">
                                            <input v-model="form.codigo_postal" @blur="validarCodigoPostal" @keyup.enter="validarCodigoPostal" type="text" maxlength="5" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium pr-12" placeholder="83117">
                                            <div v-if="searchingCp" class="absolute right-4 top-1/2 -translate-y-1/2">
                                                <div class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Ciudad *</label>
                                        <input v-model="form.municipio" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Hermosillo" readonly>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Estado *</label>
                                        <select v-model="form.estado" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium">
                                            <option value="">Seleccione...</option>
                                            <option v-for="e in catalogos.estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Colonia *</label>
                                        <select v-if="colonias.length > 0" v-model="form.colonia" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium">
                                            <option value="">Seleccione colonia...</option>
                                            <option v-for="col in colonias" :key="col" :value="col">{{ col }}</option>
                                        </select>
                                        <input v-else v-model="form.colonia" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Ingrese C.P. para cargar colonias">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Calle *</label>
                                        <input v-model="form.calle" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Nombre de la calle">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Número Exterior *</label>
                                        <input v-model="form.numero_exterior" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="123">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Número Interior</label>
                                        <input v-model="form.numero_interior" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Opcional">
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DE FACTURACIÓN -->
                            <div class="md:col-span-2 pt-8 border-t border-gray-50">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center gap-3">
                                        🧾 Facturación
                                    </h3>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input v-model="form.requiere_factura" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500 transition-all">
                                        <span class="text-sm font-bold text-gray-600 group-hover:text-gray-900 transition-colors">Requiero Factura</span>
                                    </label>
                                </div>

                                <!-- Campos de facturación (solo si requiere) -->
                                <div v-if="form.requiere_factura" class="bg-blue-50/30 rounded-3xl p-6 border border-blue-100/50 space-y-6 animate-fade-in">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Tipo de Persona *</label>
                                            <select v-model="form.tipo_persona" class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-medium">
                                                <option value="fisica">Persona Física</option>
                                                <option value="moral">Persona Moral</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">RFC *</label>
                                            <input v-model="form.rfc" type="text" class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-medium uppercase" :placeholder="form.tipo_persona === 'moral' ? 'XXX010101XXX' : 'XXXX010101XXX'" maxlength="13">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Razón Social (como aparece en constancia SAT) *</label>
                                            <input v-model="form.razon_social" type="text" class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-medium uppercase" placeholder="NOMBRE O RAZÓN SOCIAL">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Régimen Fiscal *</label>
                                            <select v-model="form.regimen_fiscal" class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-medium">
                                                <option value="">Seleccione...</option>
                                                <option v-for="r in catalogos.regimenes" :key="r.clave" :value="r.clave">{{ r.clave }} - {{ r.descripcion }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Uso de CFDI *</label>
                                            <select v-model="form.uso_cfdi" class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-medium">
                                                <option v-for="u in catalogos.usosCfdi" :key="u.clave" :value="u.clave">{{ u.clave }} - {{ u.descripcion }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Dirección Fiscal -->
                                    <div class="pt-4 border-t border-blue-100/50">
                                        <label class="flex items-center gap-3 cursor-pointer group mb-4">
                                            <input v-model="form.misma_direccion_fiscal" type="checkbox" class="w-5 h-5 rounded-lg border-gray-300 text-blue-600 focus:ring-blue-500 transition-all">
                                            <span class="text-sm font-bold text-gray-600 group-hover:text-gray-900 transition-colors">La dirección fiscal es la misma que la de instalación</span>
                                        </label>

                                        <!-- Campos de dirección fiscal diferente -->
                                        <div v-if="!form.misma_direccion_fiscal" class="grid grid-cols-1 md:grid-cols-6 gap-4 animate-fade-in">
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">C.P. Fiscal *</label>
                                                <input v-model="form.domicilio_fiscal_cp" type="text" maxlength="5" class="w-full px-4 py-3 bg-white border-none rounded-xl focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="00000">
                                            </div>
                                            <div class="md:col-span-4">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Calle y Número *</label>
                                                <input v-model="form.domicilio_fiscal_calle" type="text" class="w-full px-4 py-3 bg-white border-none rounded-xl focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="Calle y número">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Colonia *</label>
                                                <input v-model="form.domicilio_fiscal_colonia" type="text" class="w-full px-4 py-3 bg-white border-none rounded-xl focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="Colonia">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Municipio *</label>
                                                <input v-model="form.domicilio_fiscal_municipio" type="text" class="w-full px-4 py-3 bg-white border-none rounded-xl focus:ring-2 focus:ring-blue-500 transition-all font-medium" placeholder="Municipio">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Estado *</label>
                                                <select v-model="form.domicilio_fiscal_estado" class="w-full px-4 py-3 bg-white border-none rounded-xl focus:ring-2 focus:ring-blue-500 transition-all font-medium">
                                                    <option value="">Seleccione...</option>
                                                    <option v-for="e in catalogos.estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Opción sin factura -->
                                <div v-else class="bg-gray-50/50 rounded-2xl p-4 flex items-center gap-3">
                                    <span class="text-lg">💡</span>
                                    <p class="text-xs text-gray-500 font-medium">Puede solicitar factura en cualquier momento desde su portal de cliente</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-end">
                            <button 
                                @click="goToStep(2)"
                                :disabled="!canProceedStep1"
                                class="px-10 py-5 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-3"
                            >
                                Continuar
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 2: DOCUMENTOS -->
                    <div v-if="currentStep === 2" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12 animate-fade-in">
                        <h2 class="text-3xl font-black text-gray-900 mb-10 flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl">📁</span>
                            Documentación Requerida
                        </h2>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- INE Frontal -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">INE Frontal *</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-44 flex flex-col items-center justify-center text-center', form.ine_frontal ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 hover:border-emerald-400']">
                                    <template v-if="uploading.ine_frontal">
                                        <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.ine_frontal">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600">INE Frontal Lista</span>
                                        <button @click="form.ine_frontal = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <div class="text-4xl text-gray-200 mb-3 group-hover:text-emerald-400 transition-colors">🪪</div>
                                        <input type="file" @change="e => handleFileUpload(e, 'ine_frontal')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>

                            <!-- INE Trasera -->
                            <div class="relative group">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">INE Trasera *</label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-44 flex flex-col items-center justify-center text-center', form.ine_trasera ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 hover:border-emerald-400']">
                                    <template v-if="uploading.ine_trasera">
                                        <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.ine_trasera">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600">INE Trasera Lista</span>
                                        <button @click="form.ine_trasera = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <div class="text-4xl text-gray-200 mb-3 group-hover:text-emerald-400 transition-colors">🪪</div>
                                        <input type="file" @change="e => handleFileUpload(e, 'ine_trasera')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Comprobante Domicilio -->
                            <div class="relative group md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Comprobante de Domicilio * <span class="text-gray-300">(Recibo de luz, agua, teléfono - máx. 3 meses)</span></label>
                                <div :class="['relative border-2 border-dashed rounded-3xl p-6 transition-all h-44 flex flex-col items-center justify-center text-center', form.comprobante_domicilio ? 'border-emerald-500 bg-emerald-50/10' : 'border-gray-200 hover:border-emerald-400']">
                                    <template v-if="uploading.comprobante_domicilio">
                                        <div class="w-8 h-8 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin mb-2"></div>
                                        <span class="text-[10px] font-bold text-emerald-500 uppercase">Subiendo...</span>
                                    </template>
                                    <template v-else-if="form.comprobante_domicilio">
                                        <div class="w-12 h-12 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xl mb-2">✅</div>
                                        <span class="text-xs font-bold text-gray-600">Comprobante Listo</span>
                                        <button @click="form.comprobante_domicilio = ''" class="mt-2 text-[10px] text-red-500 font-bold uppercase hover:underline">Cambiar</button>
                                    </template>
                                    <template v-else>
                                        <div class="text-4xl text-gray-200 mb-3 group-hover:text-emerald-400 transition-colors">🏠</div>
                                        <input type="file" @change="e => handleFileUpload(e, 'comprobante_domicilio')" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*,application/pdf" />
                                        <span class="text-xs font-bold text-gray-400">Seleccionar Archivo</span>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-between">
                            <button @click="goToStep(1)" class="px-8 py-5 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Atrás
                            </button>
                            <button 
                                @click="goToStep(3)"
                                :disabled="!canProceedStep2"
                                class="px-10 py-5 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-3"
                            >
                                Continuar a Firma
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 3: FIRMA DIGITAL -->
                    <div v-if="currentStep === 3" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12 animate-fade-in">
                        <h2 class="text-3xl font-black text-gray-900 mb-10 flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-2xl">✍️</span>
                            Firma Digital del Contrato
                        </h2>

                        <div class="relative mb-6 signature-container group">
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none opacity-[0.03]">
                                <span class="text-[50px] font-black uppercase tracking-[10px] -rotate-12">{{ empresa?.nombre_corto || 'VIRCOM' }}</span>
                            </div>

                            <canvas
                                ref="canvasRef"
                                width="640"
                                height="220"
                                class="w-full border-2 border-dashed border-gray-300 rounded-3xl cursor-crosshair touch-none shadow-inner transition-colors"
                                :class="{ 'border-blue-500 border-solid bg-blue-50/10': hasDrawn }"
                            ></canvas>
                            
                            <div v-if="!hasDrawn" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none transition-opacity duration-300 group-hover:opacity-60">
                                <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center mb-3 shadow-lg border border-gray-100">
                                    <span class="text-2xl animate-bounce">🖋️</span>
                                </div>
                                <p class="text-gray-900 text-lg font-black tracking-tight">Dibuja tu firma aquí</p>
                                <p class="text-gray-400 text-[10px] uppercase tracking-[0.3em] mt-2 font-black">Firma Electrónica Avanzada</p>
                            </div>

                            <div v-if="hasDrawn" class="absolute bottom-4 right-4 flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-full text-[10px] font-black uppercase tracking-widest shadow-xl">
                                ✓ Firma Lista
                            </div>
                        </div>

                        <div class="flex items-center justify-between mb-8">
                            <button 
                                @click="limpiarFirma" 
                                type="button"
                                class="inline-flex items-center gap-2 text-[10px] text-gray-400 font-black hover:text-red-500 transition-colors uppercase tracking-[0.2em] px-4 py-2 bg-gray-50 rounded-xl"
                            >
                                🗑️ Borrar y reintentar
                            </button>
                            <div class="text-[9px] text-gray-300 font-mono font-bold">SHA-256 DIGITAL ENCRYPTION</div>
                        </div>

                        <div class="grid md:grid-cols-2 gap-8 mb-8">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Nombre Completo del Firmante *</label>
                                <input
                                    v-model="form.nombre_firmante"
                                    type="text"
                                    placeholder="Nombre y Apellidos"
                                    class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all font-black text-gray-900 shadow-inner"
                                />
                            </div>
                            <div class="flex items-end">
                                <label class="flex items-start gap-4 cursor-pointer group p-4 bg-blue-50/50 rounded-2xl border border-blue-100/50 transition-all hover:bg-blue-50">
                                    <input v-model="form.acepta_firma" type="checkbox" class="mt-1 w-6 h-6 rounded-lg border-2 border-blue-200 text-blue-600 focus:ring-blue-500 transition-all" />
                                    <span class="text-xs font-bold text-blue-900 leading-relaxed">
                                        Acepto que mi firma electrónica tiene la misma validez legal que una firma autógrafa.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-10 flex justify-between">
                            <button @click="goToStep(2)" class="px-8 py-5 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Atrás
                            </button>
                            <button 
                                @click="goToStep(4)"
                                :disabled="!canProceedStep3"
                                class="px-10 py-5 bg-emerald-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-3"
                            >
                                Continuar a Pago
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            </button>
                        </div>
                    </div>

                    <!-- PASO 4: CONFIRMACIÓN Y PAGO -->
                    <div v-if="currentStep === 4" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12 animate-fade-in">
                        <h2 class="text-3xl font-black text-gray-900 mb-10 flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-2xl">💳</span>
                            Confirmar y Pagar
                        </h2>

                        <div class="bg-gradient-to-br from-emerald-50 to-blue-50 rounded-3xl p-8 mb-8">
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Resumen de tu solicitud</h3>
                            <div class="grid md:grid-cols-2 gap-6 text-sm">
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Nombre</p>
                                    <p class="font-bold text-gray-900">{{ form.nombre_razon_social }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Ubicación</p>
                                    <p class="font-bold text-gray-900">{{ form.colonia }}, {{ form.municipio }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Documentos</p>
                                    <p class="font-bold text-emerald-600">✓ INE y Comprobante verificados</p>
                                </div>
                                <div>
                                    <p class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Firma</p>
                                    <p class="font-bold text-emerald-600">✓ Contrato firmado digitalmente</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="flex items-center gap-4 cursor-pointer group">
                                <input v-model="form.aceptar_terminos" type="checkbox" class="w-6 h-6 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-600">Acepto el <a href="#" class="text-emerald-600 underline">contrato de arrendamiento</a> y <a href="#" class="text-emerald-600 underline">términos de servicio</a>.</span>
                            </label>
                        </div>

                        <div class="flex justify-between">
                            <button @click="goToStep(3)" class="px-8 py-5 bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-200 transition-all flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                Atrás
                            </button>
                            <button 
                                @click="iniciarPago"
                                :disabled="processing || !form.aceptar_terminos"
                                class="px-10 py-5 bg-gradient-to-r from-emerald-600 to-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-2xl shadow-emerald-500/30 hover:-translate-y-1 transition-all disabled:opacity-50 flex items-center gap-3"
                            >
                                {{ processing ? 'Procesando...' : 'Proceder al Pago' }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar - Resumen -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 p-8 sticky top-24">
                        <h3 class="text-xl font-black text-gray-900 mb-8">Resumen de Renta</h3>
                        
                        <div class="flex items-center gap-4 mb-8 bg-slate-50 p-4 rounded-2xl">
                            <div class="text-3xl">{{ plan.icono || '🖥️' }}</div>
                            <div>
                                <div class="font-black text-gray-900">{{ plan.nombre }}</div>
                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ plan.tipo_label }}</div>
                            </div>
                        </div>

                        <!-- Equipamiento -->
                        <div class="mb-6">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Equipamiento Incluido</p>
                            <ul class="space-y-2">
                                <li v-for="equipo in plan.equipamiento_incluido" :key="equipo" class="flex items-center gap-2 text-xs text-gray-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ equipo }}
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-4 mb-8 pt-6 border-t border-gray-50">
                            <div class="flex justify-between text-sm font-medium">
                                <span class="text-gray-400">Renta Mensual</span>
                                <span class="text-gray-900 font-bold">{{ formatCurrency(plan.precio_mensual) }}</span>
                            </div>
                            <div class="flex justify-between text-sm font-medium">
                                <span class="text-gray-400">Depósito Garantía</span>
                                <span class="text-gray-900 font-bold">{{ formatCurrency(plan.deposito_garantia) }}</span>
                            </div>
                            <div class="pt-4 border-t border-gray-50 flex justify-between text-sm">
                                <span class="text-gray-400 font-bold">IVA (16%)</span>
                                <span class="text-gray-900 font-black">{{ formatCurrency(totalInversionInicial - (Number(plan.precio_mensual) + Number(plan.deposito_garantia))) }}</span>
                            </div>
                        </div>

                        <div class="p-6 bg-emerald-600 rounded-3xl text-white text-center mb-6">
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-80 block mb-1">Inversión Inicial</span>
                            <div class="text-3xl font-black">{{ formatCurrency(totalInversionInicial) }}</div>
                        </div>

                        <!-- Progress -->
                        <div class="text-center">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Paso {{ currentStep }} de {{ totalSteps }}</p>
                            <div class="mt-2 w-full bg-gray-100 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all" :style="{ width: `${(currentStep / totalSteps) * 100}%` }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <PublicFooter :empresa="empresa" />

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showPaymentModal = false"></div>
            <div class="bg-white rounded-[3rem] p-10 max-w-lg w-full relative z-[110] animate-scale-in">
                <h3 class="text-2xl font-black text-gray-900 mb-6 text-center">Seleccionar Método de Pago</h3>
                <div class="grid grid-cols-1 gap-4">
                    <button v-for="p in ['tarjeta', 'paypal', 'mercadopago']" :key="p" @click="confirmarPago(p)" class="p-6 border-2 border-gray-50 rounded-2xl hover:border-emerald-500 hover:bg-emerald-50 transition-all font-black text-xs uppercase tracking-widest text-gray-600 flex items-center justify-center gap-3">
                        <span class="text-2xl">{{ p === 'tarjeta' ? '💳' : p === 'paypal' ? '🅿️' : '🔵' }}</span>
                        {{ p }}
                    </button>
                </div>
                <button @click="showPaymentModal = false" class="mt-6 w-full py-4 bg-gray-100 rounded-2xl text-gray-500 font-bold text-xs uppercase hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-scale-in {
    animation: scaleIn 0.3s ease-out forwards;
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

canvas {
    touch-action: none;
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
    border-radius: 1.5rem;
}
</style>
