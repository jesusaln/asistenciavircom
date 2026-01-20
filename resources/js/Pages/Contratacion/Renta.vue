<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    empresa: Object,
    plan: Object,
    clienteData: Object,
    catalogos: Object,
    pasarelas: Object,
});

const metodoPago = ref('tarjeta');
const showPaymentModal = ref(false);
const processing = ref(false);
const searchingCp = ref(false);
const colonias = ref([]);
const paymentError = ref('');
const formError = ref('');
const page = usePage();

const form = useForm({
    nombre_razon_social: props.clienteData?.nombre_comercial || props.clienteData?.nombre_razon_social || '',
    email: props.clienteData?.email || '',
    telefono: props.clienteData?.telefono || '',
    
    password: '',
    password_confirmation: '',

    requiere_factura: !!(props.clienteData?.rfc && props.clienteData?.rfc !== 'XAXX010101000'),
    tipo_persona: props.clienteData?.tipo_persona || 'fisica',
    rfc: props.clienteData?.rfc || '',
    razon_social: props.clienteData?.razon_social || '',
    regimen_fiscal: props.clienteData?.regimen_fiscal || '',
    uso_cfdi: props.clienteData?.uso_cfdi || 'G03',
    domicilio_fiscal_cp: props.clienteData?.domicilio_fiscal_cp || props.clienteData?.codigo_postal || '',
    
    calle: props.clienteData?.calle || '',
    numero_exterior: props.clienteData?.numero_exterior || '',
    numero_interior: props.clienteData?.numero_interior || '',
    codigo_postal: props.clienteData?.codigo_postal || '',
    colonia: props.clienteData?.colonia || '',
    municipio: props.clienteData?.municipio || '',
    estado: props.clienteData?.estado || '',
    pais: props.clienteData?.pais || 'MX',

    metodo_pago: 'tarjeta',
    aceptar_terminos: false,
});

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

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const totalInversionInicial = computed(() => {
    const subtotal = Number(props.plan.precio_mensual) + Number(props.plan.deposito_garantia);
    return subtotal * 1.16;
});

const iniciarPago = () => {
    if (!form.nombre_razon_social || !form.email || !form.telefono || !form.calle || !form.codigo_postal || !form.colonia) {
        formError.value = 'Por favor completa los campos obligatorios.';
        return;
    }
    if (!form.aceptar_terminos) {
        formError.value = 'Debes aceptar los términos.';
        return;
    }
    showPaymentModal.value = true;
};

const confirmarPago = (metodo) => {
    if (metodo) metodoPago.value = metodo;
    form.metodo_pago = metodoPago.value;
    processing.value = true;
    
    form.post(route('contratacion.renta.procesar', props.plan.slug), {
        onSuccess: () => {
            // En un sistema real, aquí dispararíamos la pasarela. 
            // Como fallback, redirigimos al portal.
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
    '--color-primary': '#10b981', // Emerald
    '--color-primary-soft': '#d1fae5',
    '--color-primary-dark': '#059669',
}));

</script>

<template>
    <Head :title="`Rentar ${plan.nombre}`" />

    <div class="min-h-screen bg-slate-50 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="rentas" />

        <main class="flex-grow py-12 px-4 max-w-7xl mx-auto w-full">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-8 md:p-12">
                        <h2 class="text-3xl font-black text-gray-900 mb-10 flex items-center gap-4">
                            <span class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
                                📋
                            </span>
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
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Teléfono *</label>
                                <input v-model="form.telefono" type="tel" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="10 dígitos">
                            </div>

                            <div v-if="!clienteData" class="md:col-span-2 bg-emerald-50/50 p-8 rounded-3xl border border-emerald-100/50">
                                <p class="text-xs font-black text-emerald-700 uppercase tracking-widest mb-4">Crear contraseña para su Portal de Cliente</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <input v-model="form.password" type="password" class="w-full px-6 py-4 bg-white border-none rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Contraseña">
                                    <input v-model="form.password_confirmation" type="password" class="w-full px-6 py-4 bg-white border-none rounded-xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Confirmar">
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-8 border-t border-gray-50">
                                <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-8 flex items-center gap-3">
                                    📍 Ubicación de Instalación
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">C.P. *</label>
                                        <input v-model="form.codigo_postal" @blur="validarCodigoPostal" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="5 dígitos">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Calle y Número *</label>
                                        <input v-model="form.calle" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Calle y número exterior">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Colonia *</label>
                                        <input v-model="form.colonia" type="text" class="w-full px-6 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-emerald-500 transition-all font-medium" placeholder="Colonia">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12">
                            <label class="flex items-center gap-4 cursor-pointer group">
                                <input v-model="form.aceptar_terminos" type="checkbox" class="w-6 h-6 rounded-lg border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-600">Acepto el contrato de arrendamiento y términos de servicio.</span>
                            </label>
                        </div>
                    </div>
                </div>

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

                        <div class="space-y-4 mb-8">
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

                        <div class="p-6 bg-emerald-600 rounded-3xl text-white text-center mb-8">
                            <span class="text-[10px] font-black uppercase tracking-widest opacity-80 block mb-1">Inversión Inicial</span>
                            <div class="text-3xl font-black">{{ formatCurrency(totalInversionInicial) }}</div>
                        </div>

                        <button 
                            @click="iniciarPago"
                            :disabled="processing"
                            class="w-full py-5 bg-gray-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-lg active:scale-95 disabled:opacity-50"
                        >
                            {{ processing ? 'Procesando...' : 'Confirmar y Pagar' }}
                        </button>
                        
                        <p class="mt-4 text-[10px] text-gray-400 text-center font-bold uppercase tracking-widest">Pago 100% Seguro</p>
                    </div>
                </div>
            </div>
        </main>

        <PublicFooter :empresa="empresa" />

        <!-- Simple Payment Modal (Simulado) -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showPaymentModal = false"></div>
            <div class="bg-white rounded-[3rem] p-10 max-w-lg w-full relative z-[110] animate-scale-in">
                <h3 class="text-2xl font-black text-gray-900 mb-6 text-center">Seleccionar Método</h3>
                <div class="grid grid-cols-2 gap-4">
                    <button v-for="p in ['tarjeta', 'paypal', 'mercadopago']" :key="p" @click="confirmarPago(p)" class="p-6 border-2 border-gray-50 rounded-2xl hover:border-emerald-500 hover:bg-emerald-50 transition-all font-black text-xs uppercase tracking-widest text-gray-600">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-scale-in {
    animation: scaleIn 0.3s ease-out forwards;
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}
</style>
