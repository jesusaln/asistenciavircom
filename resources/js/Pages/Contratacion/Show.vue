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
    cicloInicial: { type: String, default: 'mensual' },
});

const periodoSeleccionado = ref(props.cicloInicial || 'mensual');
const metodoPago = ref('tarjeta');
const showPaymentModal = ref(false);
const showTerminos = ref(false);
const processing = ref(false);
const searchingCp = ref(false);
const colonias = ref([]);
const paymentError = ref('');
const formError = ref('');
const page = usePage();

const esServicioUnico = computed(() => props.plan.slug === 'servicio-unico');

const form = useForm({
    nombre_razon_social: props.clienteData?.nombre_comercial || '',
    email: props.clienteData?.email || '',
    telefono: props.clienteData?.telefono || '',
    whatsapp_optin: !!props.clienteData?.whatsapp_optin,
    
    // Contraseña
    password: '',
    password_confirmation: '',

    // Información Fiscal
    requiere_factura: !!(props.clienteData?.rfc && props.clienteData?.rfc !== 'XAXX010101000'),
    tipo_persona: props.clienteData?.tipo_persona || 'fisica',
    rfc: props.clienteData?.rfc || '',
    curp: props.clienteData?.curp || '',
    razon_social: props.clienteData?.razon_social || '',
    regimen_fiscal: props.clienteData?.regimen_fiscal || '',
    uso_cfdi: props.clienteData?.uso_cfdi || 'G03',
    domicilio_fiscal_cp: props.clienteData?.domicilio_fiscal_cp || props.clienteData?.codigo_postal || '',
    forma_pago_default: props.clienteData?.forma_pago_default || '99',
    
    // Dirección detallada
    calle: props.clienteData?.calle || '',
    numero_exterior: props.clienteData?.numero_exterior || '',
    numero_interior: props.clienteData?.numero_interior || '',
    codigo_postal: props.clienteData?.codigo_postal || '',
    colonia: props.clienteData?.colonia || '',
    municipio: props.clienteData?.municipio || '',
    estado: props.clienteData?.estado || '',
    pais: props.clienteData?.pais || 'MX',

    metodo_pago: 'tarjeta',
    periodo: props.cicloInicial || 'mensual',
    aceptar_terminos: false,
    equipos: [{ marca: '', modelo: '', serie: '' }],
});

const agregarEquipo = () => {
    if (props.plan.max_equipos && form.equipos.length >= props.plan.max_equipos) return;
    form.equipos.push({ marca: '', modelo: '', serie: '' });
};

const removerEquipo = (index) => {
    if (form.equipos.length > (props.plan.min_equipos || 1)) {
        form.equipos.splice(index, 1);
    }
};

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
                // Mapear nombre de estado a clave de 3 letras si es necesario
                // Pero el API suele devolver el nombre, el controller espera la clave
                // Intentaremos buscar en el catalogo
                const found = props.catalogos.estados?.find(e => 
                    e.label.toLowerCase().includes(data.estado.toLowerCase())
                );
                if (found) form.estado = found.value;
                else form.estado = data.estado; 
            }
            
            if (colonias.value.length === 1) {
                form.colonia = colonias.value[0];
            } else {
                form.colonia = '';
            }
        }
    } catch (e) {
        console.error('Error validando CP:', e);
    } finally {
        searchingCp.value = false;
    }
};

const regimenesFiltrados = computed(() => {
    if (!form.tipo_persona) return props.catalogos.regimenes || [];
    return props.catalogos.regimenes?.filter(r => {
        if (form.tipo_persona === 'fisica') return r.persona_fisica;
        if (form.tipo_persona === 'moral') return r.persona_moral;
        return true;
    }) || [];
});

const toUpper = (field) => {
    if (form[field]) form[field] = form[field].toUpperCase();
};

const seleccionarColonia = (colonia) => {
    form.colonia = colonia;
};

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
    '--color-primary-dark': (props.empresa?.color_principal || '#3b82f6') + 'dd',
    '--color-secondary': props.empresa?.color_secundario || '#6b7280',
    '--color-terciary': props.empresa?.color_terciario || '#fbbf24',
    '--color-terciary-soft': (props.empresa?.color_terciario || '#fbbf24') + '15',
}));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const precioPeriodo = computed(() => {
    if (periodoSeleccionado.value === 'anual') {
        return props.plan.precio_anual_calculado || props.plan.precio_anual;
    }
    return props.plan.precio_mensual;
});

const totalPagar = computed(() => {
    const subtotal = precioPeriodo.value + (Number(props.plan.precio_instalacion) || 0);
    return subtotal * 1.16; // 16% IVA
});

const iniciarPago = () => {
    paymentError.value = '';
    formError.value = '';
    
    if (!form.nombre_razon_social || !form.email || !form.telefono || !form.calle || !form.codigo_postal || !form.colonia) {
        formError.value = 'Por favor completa todos los campos obligatorios (Nombre, Email, Teléfono, y Dirección completa).';
        // Scroll hacia el error
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }
    if (!form.aceptar_terminos) {
        formError.value = 'Debes aceptar los Términos y Condiciones para continuar.';
        return;
    }
    if (form.requiere_factura && (!form.rfc || !form.razon_social || !form.regimen_fiscal)) {
        formError.value = 'Por favor completa los datos fiscales para continuar.';
        return;
    }
    showPaymentModal.value = true;
};

const confirmarPago = (metodo) => {
    if (metodo) metodoPago.value = metodo;
    paymentError.value = '';
    
    form.periodo = periodoSeleccionado.value;
    form.metodo_pago = metodoPago.value;
    
    processing.value = true;
    
    // 1. Primero registramos la poliza en el servidor
    form.post(route('contratacion.procesar', props.plan.slug), {
        onSuccess: (page) => {
            const polizaId = page.props.flash.created_poliza_id;
            const metodoFinal = page.props.flash.metodo_pago;

            if (polizaId) {
                ejecutarPasarelaPago(polizaId, metodoFinal);
            } else {
                processing.value = false;
                paymentError.value = 'No se pudo crear la póliza. Por favor intenta de nuevo.';
            }
        },
        onError: (errors) => {
            showPaymentModal.value = false;
            processing.value = false;
            paymentError.value = errors.error || 'Error al procesar la solicitud.';
        }
    });
};

/**
 * Orquestador de pasarelas de pago
 */
const ejecutarPasarelaPago = async (polizaId, metodo) => {
    processing.value = true;
    paymentError.value = '';
    
    try {
        if (metodo === 'paypal') {
            await iniciarPayPal(polizaId);
        } else if (metodo === 'mercadopago') {
            await iniciarMercadoPago(polizaId);
        } else if (metodo === 'tarjeta') {
            await iniciarStripe(polizaId);
        } else if (metodo === 'credito') {
            await iniciarCredito(polizaId);
        }
    } catch (error) {
        console.error('Error en pasarela:', error);
        paymentError.value = error.message || 'Hubo un problema al conectar con el sistema de pagos.';
        processing.value = false;
    }
};

// --- INTEGRACIONES ESPECIFICAS ---

const getCsrfToken = () => {
    // Obtener el token CSRF de múltiples fuentes
    return page.props.csrf_token 
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
};

const iniciarPayPal = async (polizaId) => {
    const response = await fetch(route('pago.poliza.paypal.crear'), {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ poliza_id: polizaId })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Error al crear orden de PayPal. Verifica que las credenciales estén configuradas.');
    }
    
    if (data.approve_url) {
        window.location.href = data.approve_url;
    } else {
        throw new Error('PayPal no devolvió una URL de aprobación. Verifica las credenciales de PayPal.');
    }
};

const iniciarMercadoPago = async (polizaId) => {
    const response = await fetch(route('pago.poliza.mercadopago.crear'), {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ poliza_id: polizaId })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Error al crear preferencia de MercadoPago. Verifica que las credenciales estén configuradas.');
    }
    
    if (data.init_point) {
        window.location.href = data.init_point;
    } else {
        throw new Error('MercadoPago no devolvió una URL de pago. Verifica las credenciales de MercadoPago.');
    }
};

const iniciarStripe = async (polizaId) => {
    const response = await fetch(route('pago.poliza.stripe.checkout'), {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ poliza_id: polizaId })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Error al crear sesión de Stripe. Verifica que las credenciales estén configuradas.');
    }
    
    if (data.checkout_url) {
        window.location.href = data.checkout_url;
    } else {
        throw new Error('Stripe no devolvió una URL de checkout. Verifica las credenciales de Stripe.');
    }
};

const iniciarCredito = async (polizaId) => {
    const response = await fetch(route('pago.poliza.credito.pagar'), {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json', 
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify({ poliza_id: polizaId })
    });
    
    const data = await response.json();
    
    if (!response.ok) {
        throw new Error(data.message || 'Error al procesar el pago con crédito.');
    }
    
    if (data.redirect) {
        window.location.href = data.redirect;
    } else {
        window.location.href = route('pago.poliza.exito', { poliza_id: polizaId });
    }
};
</script>

<template>
    <Head :title="`Contratar ${plan.nombre}`" />

    <div class="min-h-screen bg-white dark:bg-slate-900 flex flex-col font-sans" :style="cssVars">
        <!-- Navbar / Header Corporativo -->
        <PublicNavbar :empresa="empresa" activeTab="checkout" />

        <main class="flex-grow py-8 px-4">
            <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Formulario -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Datos del Cliente -->
                    <div v-if="!clienteData" class="mb-6 p-4 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-between border border-[var(--color-primary)]/20 animate-fade-in">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">👋</span>
                            <div>
                                <p class="text-sm font-bold text-[var(--color-primary-dark)]">¿Ya eres cliente?</p>
                                <p class="text-xs text-gray-600 dark:text-gray-300">Inicia sesión para autocompletar tus datos.</p>
                            </div>
                        </div>
                        <a :href="route('portal.login')" class="px-4 py-2 bg-white dark:bg-slate-900 text-[var(--color-primary)] text-xs font-black uppercase tracking-widest rounded-xl shadow-sm hover:shadow hover:-translate-y-0.5 transition-all">
                            Iniciar Sesión
                        </a>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 transition-all hover:shadow-md">
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-8 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center text-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            </span>
                            Informacion del Cliente
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
                            <div class="md:col-span-2">
                                <h3 class="text-sm font-black text-[var(--color-primary)] uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-[var(--color-primary)] animate-pulse"></span>
                                    1. Información General
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Nombre Comercial / Cliente *</label>
                                        <input v-model="form.nombre_razon_social" type="text" class="form-input-premium" placeholder="Nombre completo o del negocio" @blur="toUpper('nombre_razon_social')">
                                        <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.nombre_razon_social">⚠️ {{ form.errors.nombre_razon_social }}</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Email de Contacto / Acceso *</label>
                                        <input v-model="form.email" type="email" class="form-input-premium" placeholder="correo@ejemplo.com" :readonly="!!clienteData" :class="{'bg-white dark:bg-slate-900/50 cursor-not-allowed': !!clienteData}">
                                        <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.email">⚠️ {{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Teléfono *</label>
                                        <input v-model="form.telefono" type="tel" class="form-input-premium" placeholder="10 dígitos" maxlength="10">
                                        <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.telefono">⚠️ {{ form.errors.telefono }}</p>
                                    </div>
                                    <div class="flex items-center gap-3 pt-6">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="form.whatsapp_optin" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                        </label>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-300">Autorizo recibir WhatsApp</span>
                                    </div>

                                    <template v-if="!clienteData">
                                        <div class="md:col-span-2 mt-4 p-6 bg-blue-50/30 border border-blue-100/50 rounded-3xl">
                                            <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                Seguridad de la Cuenta
                                            </p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Contraseña *</label>
                                                    <input v-model="form.password" type="password" class="form-input-premium bg-white dark:bg-slate-900" placeholder="Contraseña segura">
                                                    <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.password">⚠️ {{ form.errors.password }}</p>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Confirmar *</label>
                                                    <input v-model="form.password_confirmation" type="password" class="form-input-premium bg-white dark:bg-slate-900" placeholder="Repite la contraseña">
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: INFORMACIÓN FISCAL -->
                            <div class="md:col-span-2 border-t border-gray-50 pt-8 mt-4">
                                <div class="flex items-center justify-between mb-8">
                                    <h3 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-[0.2em] flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </span>
                                        2. Datos de Facturación
                                    </h3>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative inline-flex items-center">
                                            <input type="checkbox" v-model="form.requiere_factura" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:bg-slate-900 after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[var(--color-primary)]"></div>
                                        </div>
                                        <span class="text-xs font-black text-gray-400 group-hover:text-gray-600 dark:text-gray-300 transition-colors">¿REQUERIR FACTURA?</span>
                                    </label>
                                </div>

                                <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-4 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
                                    <div v-if="form.requiere_factura" class="grid grid-cols-1 md:grid-cols-2 gap-6 p-8 bg-white dark:bg-slate-900/50 rounded-[2.5rem] border border-gray-100 shadow-inner">
                                        
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Tipo de Persona *</label>
                                            <select v-model="form.tipo_persona" class="form-select-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100">
                                                <option v-for="t in catalogos.tiposPersona" :key="t.value" :value="t.value">{{ t.label }}</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">RFC *</label>
                                            <input v-model="form.rfc" type="text" class="form-input-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100 uppercase" placeholder="XXXX010101XXX" @blur="toUpper('rfc')">
                                            <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.rfc">{{ form.errors.rfc }}</p>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Razón Social Fiscal *</label>
                                            <input v-model="form.razon_social" type="text" class="form-input-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100" placeholder="Nombre completo como aparece en CSF" @blur="toUpper('razon_social')">
                                            <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.razon_social">{{ form.errors.razon_social }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Régimen Fiscal *</label>
                                            <select v-model="form.regimen_fiscal" class="form-select-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100">
                                                <option value="">Selecciona una opción...</option>
                                                <option v-for="r in regimenesFiltrados" :key="r.clave" :value="r.clave">{{ r.clave }} - {{ r.descripcion }}</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Uso CFDI *</label>
                                            <select v-model="form.uso_cfdi" class="form-select-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100">
                                                <option v-for="u in catalogos.usosCfdi" :key="u.clave" :value="u.clave">{{ u.clave }} - {{ u.descripcion }}</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">C.P. Fiscal *</label>
                                            <input v-model="form.domicilio_fiscal_cp" type="text" class="form-input-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100" placeholder="12345" maxlength="5">
                                            <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.domicilio_fiscal_cp">{{ form.errors.domicilio_fiscal_cp }}</p>
                                        </div>

                                        <div>
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Forma de Pago Preferida</label>
                                            <select v-model="form.forma_pago_default" class="form-select-premium bg-white dark:bg-slate-900 shadow-sm border-gray-100">
                                                <option v-for="f in catalogos.formasPago" :key="f.value" :value="f.value">{{ f.label }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- SECCIÓN 3: DIRECCIÓN DE SERVICIO -->
                            <div class="md:col-span-2 border-t border-gray-50 pt-8 mt-4">
                                <h3 class="text-sm font-black text-blue-900 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-500 flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </span>
                                    3. Ubicación del Servicio
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Código Postal *</label>
                                        <div class="relative">
                                            <input 
                                                v-model="form.codigo_postal" 
                                                @blur="validarCodigoPostal"
                                                @input="form.codigo_postal = form.codigo_postal.replace(/\D/g, '')"
                                                type="text" 
                                                class="form-input-premium" 
                                                placeholder="5 dígitos" 
                                                maxlength="5"
                                            >
                                            <div v-if="searchingCp" class="absolute right-3 top-1/2 -translate-y-1/2">
                                                <div class="w-4 h-4 border-2 border-gray-200 dark:border-slate-800 border-t-blue-500 rounded-full animate-spin"></div>
                                            </div>
                                        </div>
                                        <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.codigo_postal">{{ form.errors.codigo_postal }}</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Calle / Avenida *</label>
                                        <input v-model="form.calle" type="text" class="form-input-premium" placeholder="Nombre completo de la calle" @blur="toUpper('calle')">
                                        <p class="text-red-500 text-[10px] mt-1 font-bold" v-if="form.errors.calle">{{ form.errors.calle }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Núm. Exterior *</label>
                                        <input v-model="form.numero_exterior" type="text" class="form-input-premium" placeholder="Ej: 123-A" @blur="toUpper('numero_exterior')">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Núm. Interior</label>
                                        <input v-model="form.numero_interior" type="text" class="form-input-premium" placeholder="Dep / Suite" @blur="toUpper('numero_interior')">
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Estado</label>
                                        <select v-model="form.estado" class="form-select-premium border-blue-50 bg-blue-50/10">
                                            <option value="">Seleccionar...</option>
                                            <option v-for="e in catalogos.estados" :key="e.value" :value="e.value">{{ e.label }}</option>
                                        </select>
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Municipio / Ciudad *</label>
                                        <input v-model="form.municipio" type="text" class="form-input-premium" placeholder="Nombre de la ciudad" @blur="toUpper('municipio')">
                                    </div>

                                    <div class="md:col-span-3">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Colonia / Fraccionamiento *</label>
                                        <div class="relative">
                                            <input v-model="form.colonia" type="text" class="form-input-premium pr-10" placeholder="Seleccione abajo o escriba su colonia">
                                            <div class="absolute right-4 top-4 text-gray-300">🏢</div>
                                        </div>

                                        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100">
                                            <div v-if="colonias.length > 0" class="mt-4 p-5 bg-white dark:bg-slate-900/80 backdrop-blur-sm rounded-[2rem] border border-gray-100 shadow-inner">
                                                <div class="flex items-center justify-between mb-4">
                                                    <span class="text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Opciones detectadas</span>
                                                    <span class="text-[10px] text-gray-400 font-bold uppercase">{{ colonias.length }}</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                                    <button v-for="col in colonias" :key="col" type="button" @click="form.colonia = col" class="px-4 py-2 bg-white dark:bg-slate-900 border border-gray-100 rounded-xl text-[10px] font-bold text-gray-600 dark:text-gray-300 text-left hover:border-blue-500 hover:text-blue-600 transition-all truncate" :title="col">
                                                        {{ col }}
                                                    </button>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 border-t border-gray-50 pt-8 mt-2">
                                <label class="flex items-start gap-4 cursor-pointer group p-6 rounded-3xl hover:bg-white dark:bg-slate-900 transition-colors border-2 border-dashed border-transparent hover:border-gray-200 dark:border-slate-800">
                                    <input v-model="form.aceptar_terminos" type="checkbox" class="mt-1 w-6 h-6 text-[var(--color-primary)] rounded-lg border-gray-200 dark:border-slate-800 focus:ring-[var(--color-primary)]">
                                    <div class="text-sm">
                                        <span class="font-bold text-gray-900 dark:text-white block text-lg mb-1">Acepto los términos y condiciones</span>
                                        <p class="text-gray-500 dark:text-gray-400 font-medium">Declaro haber leído el <button type="button" @click="showTerminos = true" class="text-[var(--color-primary)] hover:underline font-black">Contrato de Prestación de Servicios</button> y estar de acuerdo con sus términos.</p>
                                        <p class="text-red-500 text-xs mt-2 font-bold animate-bounce" v-if="form.errors.aceptar_terminos">Debes aceptar los términos para continuar</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Aviso Servicio Único -->
                    <div v-if="esServicioUnico" class="bg-blue-50 rounded-3xl border border-blue-100 p-6 md:p-8 mb-6 animate-fade-in-up">
                        <h3 class="flex items-center gap-2 font-black text-blue-900 text-lg mb-2">
                             ℹ️ Cobertura del Servicio
                        </h3>
                        <p class="text-blue-800 text-sm font-medium">Este servicio está disponible en modo <strong>REMOTO para todo México</strong>.</p>
                        <p class="text-blue-800 text-sm font-medium mt-1">El servicio <strong>presencial (en sitio)</strong> está limitado exclusivamente a la ciudad de <strong>Hermosillo, Sonora</strong>.</p>
                    </div>

                    <!-- Seleccion del Ciclo (Solo si NO es servicio unico) -->
                    <div v-if="!esServicioUnico" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 transition-all hover:shadow-md">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </span>
                            Configuracion del Plan
                        </h2>

                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Ciclo de Facturacion</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div 
                                @click="periodoSeleccionado = 'mensual'"
                                :class="[
                                    'cursor-pointer border-2 rounded-2xl p-6 transition-all relative group',
                                    periodoSeleccionado === 'mensual' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]/30' : 'border-gray-100 hover:border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/30'
                                ]"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-gray-900 dark:text-white">Mensual</span>
                                    <div v-if="periodoSeleccionado === 'mensual'" class="w-5 h-5 rounded-full bg-[var(--color-primary)] flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white dark:bg-slate-900 rounded-full"></div>
                                    </div>
                                    <div v-else class="w-5 h-5 rounded-full border-2 border-gray-200 dark:border-slate-800 group-hover:border-gray-300"></div>
                                </div>
                                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ formatCurrency(plan.precio_mensual) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Pago mes a mes</div>
                            </div>

                            <div 
                                v-if="plan.precio_anual || plan.precio_anual_calculado"
                                @click="periodoSeleccionado = 'anual'"
                                :class="[
                                    'cursor-pointer border-2 rounded-2xl p-6 transition-all relative group',
                                    periodoSeleccionado === 'anual' ? 'border-[var(--color-primary)] bg-[var(--color-primary-soft)]/30' : 'border-gray-100 hover:border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/30'
                                ]"
                            >
                                <div class="absolute -top-3 right-4 bg-green-500 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg border-2 border-white uppercase">
                                    Ahorra {{ formatCurrency(plan.ahorro_anual_calculado || plan.ahorro_anual) }}
                                </div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-bold text-gray-900 dark:text-white">Anual</span>
                                    <div v-if="periodoSeleccionado === 'anual'" class="w-5 h-5 rounded-full bg-[var(--color-primary)] flex items-center justify-center">
                                        <div class="w-2 h-2 bg-white dark:bg-slate-900 rounded-full"></div>
                                    </div>
                                    <div v-else class="w-5 h-5 rounded-full border-2 border-gray-200 dark:border-slate-800 group-hover:border-gray-300"></div>
                                </div>
                                <div class="text-2xl font-black text-gray-900 dark:text-white">{{ formatCurrency(plan.precio_anual_calculado || plan.precio_anual) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">Pago unico anual (-15%)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Registro de Equipos -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 transition-all hover:shadow-md">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-[var(--color-primary-soft)] text-[var(--color-primary)] flex items-center justify-center text-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </span>
                            Equipos a Cubrir
                        </h2>
                        
                        <div class="mb-8 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center text-xl">🛡️</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">
                                Tu plan cubre de <span class="font-bold text-gray-900 dark:text-white">{{ plan.min_equipos || 1 }}</span> a <span class="font-bold text-gray-900 dark:text-white">{{ plan.max_equipos || 'Ilimitados' }}</span> equipos registrados.
                            </div>
                        </div>

                        <div v-for="(equipo, index) in form.equipos" :key="index" class="mb-8 p-6 bg-white dark:bg-slate-900/30 rounded-3xl border border-gray-100 relative group">
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Dispositivo #{{ index + 1 }}</h4>
                                <button v-if="form.equipos.length > (plan.min_equipos || 1)" @click="removerEquipo(index)" type="button" class="w-8 h-8 rounded-full bg-red-50 text-red-500 flex items-center justify-center hover:bg-red-100 transition-colors">✕</button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Marca / Tipo <span class="text-gray-300 font-medium">(Opcional)</span></label>
                                    <input v-model="equipo.marca" type="text" class="form-input-premium text-xs p-3" placeholder="Ej: Laptop HP">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Modelo <span class="text-gray-300 font-medium">(Opcional)</span></label>
                                    <input v-model="equipo.modelo" type="text" class="form-input-premium text-xs p-3" placeholder="Ej: Pavilion 15">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">No. Serie <span class="text-gray-300 font-medium">(Opcional)</span></label>
                                    <input v-model="equipo.serie" type="text" class="form-input-premium text-xs p-3" placeholder="Si lo conoce">
                                </div>
                            </div>
                        </div>

                        <button 
                            v-if="!plan.max_equipos || form.equipos.length < plan.max_equipos" 
                            @click="agregarEquipo" 
                            type="button" 
                            class="w-full py-4 border-2 border-dashed border-gray-200 dark:border-slate-800 text-gray-400 rounded-2xl hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] hover:bg-[var(--color-primary-soft)]/20 transition-all font-black text-xs uppercase tracking-widest"
                        >
                            + Agregar Dispositivo
                        </button>

                        <!-- Nota de Cobertura Global con Ejemplo Visual -->
                        <div class="mt-8 p-8 rounded-[2rem] border transition-all hover:shadow-xl group/note" 
                             :style="{ 
                                backgroundColor: 'var(--color-terciary-soft)',
                                borderColor: 'var(--color-terciary-soft)'
                             }">
                            <div class="flex items-start gap-4 mb-6">
                                <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center text-2xl flex-shrink-0" :style="{ color: 'var(--color-terciary)' }">💡</div>
                                <div class="text-xs leading-relaxed" :style="{ color: 'var(--color-terciary)' }">
                                    <span class="font-black uppercase tracking-widest block mb-1">Nota sobre la cobertura</span>
                                    Los beneficios de su plan (servicios o tickets) se aplican de forma <span class="font-bold underline">global a la poliza</span>.
                                </div>
                            </div>
                            
                            <!-- Diagrama Visual -->
                            <div class="bg-white dark:bg-slate-900/60 rounded-2xl p-6 border border-[var(--color-terciary-soft)]/50">
                                <p class="text-[10px] font-black opacity-40 uppercase tracking-[0.2em] mb-4 text-center" :style="{ color: 'var(--color-terciary)' }">Ejemplo de funcionamiento</p>
                                
                                <div class="flex items-center justify-around gap-2">
                                    <!-- Grupo de Equipos -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex -space-x-4">
                                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center border-2 border-white shadow-sm" :style="{ backgroundColor: 'var(--color-terciary-soft)' }">
                                                <svg class="w-5 h-5" :style="{ color: 'var(--color-terciary)' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            </div>
                                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center border-2 border-white shadow-sm" :style="{ backgroundColor: 'var(--color-terciary)' }">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                            </div>
                                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center border-2 border-white shadow-sm" :style="{ backgroundColor: 'var(--color-terciary)' }">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            </div>
                                        </div>
                                        <span class="text-[9px] font-bold opacity-60" :style="{ color: 'var(--color-terciary)' }">Varios Equipos</span>
                                    </div>

                                    <!-- Flecha de Union -->
                                    <div :style="{ color: 'var(--color-terciary)' }" class="opacity-30">
                                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </div>

                                    <!-- Tecnico / Servicio Unificado -->
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-[var(--color-primary)] flex items-center justify-center border-4 border-[var(--color-primary-soft)] shadow-lg shadow-[var(--color-primary)]/20">
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 20l2 2 4-4"></path></svg>
                                        </div>
                                        <span class="text-[9px] font-black text-[var(--color-primary)]">Soporte Unico</span>
                                    </div>
                                </div>
                                
                                <p class="mt-4 text-[10px] text-center opacity-70 italic leading-relaxed" :style="{ color: 'var(--color-terciary)' }">
                                    Si su plan incluye 2 tickets, puede usarlos en <span class="font-bold underline">cualquiera</span> de los equipos registrados hasta agotarlos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Resumen -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-gray-200/50 border border-gray-100 p-8 sticky top-24 overflow-hidden group">
                        <!-- Glassmorphism Effect -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-[var(--color-primary)] opacity-[0.03] rounded-full -mr-16 -mt-16 blur-2xl group-hover:opacity-10 transition-opacity"></div>
                        
                        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-6 flex items-center justify-between">
                            Resumen
                            <span class="text-[10px] bg-green-500 text-white px-3 py-1 rounded-full uppercase tracking-tighter">Seguro</span>
                        </h3>
                        
                        <!-- Plan Card -->
                        <div class="flex items-center gap-4 mb-8 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100">
                            <div class="w-14 h-14 bg-white dark:bg-slate-900 rounded-xl shadow-sm flex items-center justify-center text-3xl border border-gray-50">{{ plan.icono_display }}</div>
                            <div>
                                <div class="font-black text-gray-900 dark:text-white text-lg leading-tight">{{ plan.nombre }}</div>
                                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Poliza Seleccionada</div>
                            </div>
                        </div>

                        <!-- Breakdown -->
                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between items-center group/item text-sm">
                                <span class="text-gray-400 font-bold group-hover/item:text-gray-600 dark:text-gray-300 transition-colors">
                                    {{ esServicioUnico ? 'Costo del Servicio' : (periodoSeleccionado === 'mensual' ? 'Subtotal Mensual' : 'Subtotal Anual') }}
                                </span>
                                <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(precioPeriodo) }}</span>
                            </div>
                            <div v-if="plan.precio_instalacion > 0" class="flex justify-between items-center group/item text-sm">
                                <span class="text-gray-400 font-bold group-hover/item:text-gray-600 dark:text-gray-300 transition-colors">Activacion (Unico)</span>
                                <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(plan.precio_instalacion) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm pt-4 border-t border-gray-50">
                                <span class="text-gray-400 font-bold">IVA (16%)</span>
                                <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(totalPagar - (precioPeriodo + (Number(plan.precio_instalacion) || 0))) }}</span>
                            </div>
                        </div>

                        <!-- Footer Total -->
                        <div class="mb-10 text-center p-6 bg-[var(--color-primary-soft)] rounded-3xl border border-[var(--color-primary-soft)]">
                            <span class="text-[10px] font-black text-[var(--color-primary)] uppercase tracking-[0.2em] block mb-2">Total a Invertir</span>
                            <div class="text-4xl font-black text-gray-900 dark:text-white tabular-nums">{{ formatCurrency(totalPagar) }}</div>
                            <span class="text-[10px] text-gray-400 font-bold mt-1 block">Factura inmediata</span>
                        </div>

                        <!-- Mensaje de Error de Validación -->
                        <div v-if="formError" class="mb-6 p-4 bg-red-50 border-2 border-red-200 rounded-2xl animate-shake">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-red-700 text-sm">{{ formError }}</p>
                                    <button @click="formError = ''" class="text-xs text-red-400 hover:text-red-600 mt-1">Cerrar ✕</button>
                                </div>
                            </div>
                        </div>

                        <button 
                            @click="iniciarPago"
                            class="w-full py-5 bg-[var(--color-primary)] text-white font-black text-sm uppercase tracking-widest rounded-2xl shadow-xl shadow-[var(--color-primary)]/20 hover:bg-[var(--color-primary-dark)] hover:-translate-y-1 active:scale-95 transition-all flex items-center justify-center gap-3 group/btn"
                        >
                            <svg class="w-5 h-5 group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Confirmar y Pagar
                        </button>
                    </div>
                </div>
            </div>
        </main>

    <!-- Modal de Pago -->
    <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden transform transition-all animate-fade-in-up">
            <div class="px-8 py-6 border-b flex justify-between items-center bg-white dark:bg-slate-900/50">
                <h3 class="text-xl font-black text-gray-900 dark:text-white">Pasarela de Pago</h3>
                <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-900 dark:text-white transition-colors">✕</button>
            </div>
            
            <div class="p-8">
                <div class="text-center mb-8">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total a Procesar</p>
                    <p class="text-4xl font-black text-gray-900 dark:text-white">{{ formatCurrency(totalPagar) }}</p>
                </div>

                <div class="space-y-4">
                    <!-- Tarjeta Bancaria (Stripe) -->
                    <button 
                        @click="confirmarPago('tarjeta')"
                        :disabled="processing"
                        class="w-full p-6 rounded-2xl border-2 border-gray-100 flex items-center gap-4 hover:border-[#635BFF] hover:bg-[#635BFF]/5 transition-all group text-left disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="w-14 h-12 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform overflow-hidden">
                            <span v-if="processing && metodoPago === 'tarjeta'" class="animate-spin text-sm">🌀</span>
                            <!-- Logo Stripe local -->
                            <img v-else src="/images/payments/stripe-logo.svg" alt="Stripe" class="w-10 h-10 object-contain rounded">
                        </div>
                        <div class="flex-1">
                            <div class="font-black text-gray-900 dark:text-white">{{ processing && metodoPago === 'tarjeta' ? 'Conectando con Stripe...' : 'Tarjeta Bancaria' }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-tight flex items-center gap-2">
                                <span>Visa</span>
                                <span class="text-gray-300">•</span>
                                <span>Mastercard</span>
                                <span class="text-gray-300">•</span>
                                <span>AMEX</span>
                            </div>
                        </div>
                        <div class="ml-auto opacity-0 group-hover:opacity-100 text-[#635BFF] transition-opacity">→</div>
                    </button>

                    <!-- PayPal -->
                    <button 
                        @click="confirmarPago('paypal')"
                        :disabled="processing"
                        class="w-full p-6 rounded-2xl border-2 border-gray-100 flex items-center gap-4 hover:border-[#003087] hover:bg-[#003087]/5 transition-all group text-left disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="w-14 h-12 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform overflow-hidden">
                            <span v-if="processing && metodoPago === 'paypal'" class="animate-spin text-sm text-[#003087]">🌀</span>
                            <!-- Logo PayPal local -->
                            <img v-else src="/images/payments/paypal-logo.svg" alt="PayPal" class="w-10 h-10 object-contain rounded">
                        </div>
                        <div class="flex-1">
                            <div class="font-black text-gray-900 dark:text-white">{{ processing && metodoPago === 'paypal' ? 'Conectando con PayPal...' : 'PayPal' }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-tight">Pago seguro internacional</div>
                        </div>
                        <div class="ml-auto opacity-0 group-hover:opacity-100 text-[#003087] transition-opacity">→</div>
                    </button>

                    <!-- Mercado Pago -->
                    <button 
                        @click="confirmarPago('mercadopago')"
                        :disabled="processing"
                        class="w-full p-6 rounded-2xl border-2 border-gray-100 flex items-center gap-4 hover:border-[#00AEEF] hover:bg-[#00AEEF]/5 transition-all group text-left disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div class="w-14 h-12 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform overflow-hidden">
                            <span v-if="processing && metodoPago === 'mercadopago'" class="animate-spin text-sm text-[#00AEEF]">🌀</span>
                            <!-- Logo MercadoPago local -->
                            <img v-else src="/images/payments/mercadopago-logo.svg" alt="Mercado Pago" class="w-10 h-10 object-contain rounded">
                        </div>
                        <div class="flex-1">
                            <div class="font-black text-gray-900 dark:text-white">{{ processing && metodoPago === 'mercadopago' ? 'Conectando con Mercado Pago...' : 'Mercado Pago' }}</div>
                            <div class="text-xs text-gray-400 font-bold uppercase tracking-tight">México • Efectivo, OXXO, SPEI</div>
                        </div>
                        <div class="ml-auto opacity-0 group-hover:opacity-100 text-[#00AEEF] transition-opacity">→</div>
                    </button>

                    <!-- Crédito Comercial -->
                    <button 
                        v-if="clienteData?.credito_activo"
                        @click="confirmarPago('credito')"
                        :disabled="processing || clienteData.credito_disponible < totalPagar"
                        class="w-full p-6 rounded-2xl border-2 flex items-center gap-4 transition-all group text-left disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="clienteData.credito_disponible < totalPagar 
                            ? 'border-gray-100 bg-white dark:bg-slate-900 grayscale cursor-not-allowed' 
                            : 'border-gray-100 hover:border-emerald-500 hover:bg-emerald-50'"
                    >
                        <div class="w-14 h-12 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex items-center justify-center group-hover:scale-110 transition-transform overflow-hidden px-2">
                             <font-awesome-icon icon="credit-card" class="text-xl text-emerald-600" />
                        </div>
                        <div class="flex-1">
                            <div class="font-black text-gray-900 dark:text-white text-sm">Crédito Comercial</div>
                            <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">
                                {{ clienteData.credito_disponible < totalPagar ? 'Saldo insuficiente' : 'Pagar con mi línea de crédito' }}
                                • Disp: {{ formatCurrency(clienteData.credito_disponible) }}
                            </div>
                        </div>
                        <div class="ml-auto opacity-0 group-hover:opacity-100 text-emerald-600 transition-opacity">→</div>
                    </button>
                </div>
                
                <!-- Mensaje de error -->
                <div v-if="paymentError" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <p class="text-red-600 text-sm font-medium flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        {{ paymentError }}
                    </p>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-50 flex items-center justify-center gap-2 grayscale opacity-40">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Pago Encriptado SSL</span>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal de Terminos -->
    <div v-if="showTerminos" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden animate-fade-in-up">
            <div class="px-8 py-6 border-b flex justify-between items-center bg-white dark:bg-slate-900/50">
                <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Terminos y Condiciones
                </h3>
                <button @click="showTerminos = false" class="text-gray-400 hover:text-gray-900 dark:text-white transition-colors">✕</button>
            </div>
            
            <div class="p-8 overflow-y-auto custom-scrollbar text-sm text-gray-600 dark:text-gray-300 space-y-6 leading-relaxed">
                <div class="p-6 bg-[var(--color-primary-soft)]/20 rounded-3xl border border-[var(--color-primary-soft)]/30">
                    <h4 class="font-black text-gray-900 dark:text-white uppercase tracking-widest text-[10px] mb-2">Resumen de Contratacion</h4>
                    <p class="text-xs">Usted esta contratando el plan <span class="font-black text-gray-900 dark:text-white">{{ plan.nombre }}</span> en modalidad <span class="font-black text-gray-900 dark:text-white">{{ periodoSeleccionado }}</span>. Este es un contrato de prestacion de servicios tecnologicos entre Vircom y el Cliente.</p>
                </div>

                <div class="space-y-4">
                    <section>
                        <h4 class="font-black text-gray-900 dark:text-white mb-2">1. Objeto del Servicio</h4>
                        <p>Vircom se compromete a brindar soporte tecnico remoto y/o presencial segun las caracteristicas del plan seleccionado. El servicio incluye mantenimiento preventivo y correctivo de los equipos registrados.</p>
                    </section>
                    
                    <section>
                        <h4 class="font-black text-gray-900 dark:text-white mb-2">2. Vigencia y Renovacion</h4>
                        <p v-if="esServicioUnico">El servicio contratado corresponde a un evento único y no genera suscripción recurrente. La garantía del servicio es de 7 días naturales.</p>
                        <p v-else>El contrato tiene una vigencia forzosa de acuerdo al periodo elegido. La renovacion sera automatica al termino del periodo salvo aviso previo de 30 dias.</p>
                    </section>

                    <section>
                        <h4 class="font-black text-gray-900 dark:text-white mb-2">3. Responsabilidades del Cliente</h4>
                        <p>El cliente es responsable de mantener respaldos actualizados de su informacion. Vircom no se hace responsable por perdida de datos en equipos no respaldados.</p>
                    </section>

                    <section>
                        <h4 class="font-black text-gray-900 dark:text-white mb-2">4. Pagos y Facturacion</h4>
                        <p>Los pagos deben realizarse en los primeros 5 dias naturales del periodo. La factura sera emitida automaticamente a los datos fiscales proporcionados en este formulario.</p>
                    </section>
                </div>

                <div class="pt-6 border-t border-gray-100 italic text-[10px] text-gray-400 text-center">
                    Al confirmar, usted acepta digitalmente los terminos y condiciones aqui descritos.
                </div>
            </div>

            <div class="px-8 py-6 border-t bg-white dark:bg-slate-900/50 flex flex-col sm:flex-row gap-3">
                <button @click="showTerminos = false" class="w-full sm:w-1/3 py-4 text-gray-500 dark:text-gray-400 font-bold hover:bg-gray-100 rounded-2xl transition-all">Regresar</button>
                <button 
                    @click="showTerminos = false; form.aceptar_terminos = true" 
                    class="w-full sm:w-2/3 py-4 bg-[var(--color-primary)] text-white font-black rounded-2xl shadow-xl shadow-[var(--color-primary)]/20 hover:opacity-90 active:scale-[0.98] transition-all"
                >
                    He leido y Acepto los Terminos
                </button>
            </div>
            </div>
        </div>
        
        <PublicFooter :empresa="empresa" />
    </div>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-shake {
    animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
    20%, 40%, 60%, 80% { transform: translateX(4px); }
}

.form-input-premium {
    width: 100%;
    border-radius: 1rem; /* 2xl */
    border-width: 1px;
    border-color: #f3f4f6; /* gray-100 */
    background-color: rgb(249 250 251 / 0.5); /* gray-50/50 */
    padding: 1rem;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
    outline: 2px solid transparent;
    outline-offset: 2px;
    color: #111827; /* gray-900 */
    font-weight: 500;
}
.form-input-premium:focus {
    background-color: white;
    box-shadow: 0 0 0 4px var(--color-primary-soft);
    border-color: var(--color-primary);
}

.form-select-premium {
    width: 100%;
    border-radius: 1rem;
    border-width: 1px;
    border-color: #f3f4f6;
    background-color: rgb(249 250 251 / 0.5);
    padding: 1rem;
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
    outline: 2px solid transparent;
    outline-offset: 2px;
    color: #111827;
    font-weight: 500;
}
.form-select-premium:focus {
    background-color: white;
    box-shadow: 0 0 0 4px var(--color-primary-soft);
    border-color: var(--color-primary);
}

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
