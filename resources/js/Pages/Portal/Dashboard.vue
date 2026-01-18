<script setup>
import { Link, Head, useForm, usePage } from '@inertiajs/vue3';
import ClientLayout from './Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import PortalConfirmModal from './Components/PortalConfirmModal.vue';
import DeudaModal from './Components/DeudaModal.vue';

const props = defineProps({
    tickets: Object,
    cliente: Object,
    polizas: Array,
    pagosPendientes: Array,
    pedidos: Array,
    citas: Array,
    ventas: Object, // Changed from Array to Object (paginated)
    credenciales: Array,
    empresa: Object,
    rentas: Array,
    faqs: Array,
    catalogos: Object,
});

const activeTab = ref('resumen');
const revealedPasswords = ref({});
const isLoadingPassword = ref({});
const confirmModal = ref(null);
const showDeudaModal = ref(false);

const page = usePage();
const isBlocked = computed(() => page.props.auth?.client?.portal_blocked || false);

watch(isBlocked, (blocked) => {
    if (blocked) {
        activeTab.value = 'pagos';
    }
}, { immediate: true });

watch(activeTab, (newTab) => {
    if (isBlocked.value && newTab !== 'pagos') {
        setTimeout(() => {
            activeTab.value = 'pagos';
            // Usar una notificación discreta o nada para no saturar
             if (typeof window.$toast !== 'undefined') {
                window.$toast.error('Menú restringido por adeudos vencidos.');
             }
        }, 100);
    }
});

onMounted(() => {
    // Si hay pagos pendientes, mostrar modal inmediatamente
    if (props.pagosPendientes && props.pagosPendientes.length > 0) {
        showDeudaModal.value = true;
    }
});

const handlePagarDeuda = () => {
    showDeudaModal.value = false;
    activeTab.value = 'pagos';
    // Opcional: Scrollear a la tabla de pagos
    setTimeout(() => {
        const el = document.getElementById('historial-pagos');
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    }, 300);
};

const revealPassword = async (id) => {
    if (revealedPasswords.value[id]) {
        delete revealedPasswords.value[id];
        return;
    }
// ... (rest of logic)

    try {
        isLoadingPassword.value[id] = true;
        const response = await axios.post(route('portal.credenciales.revelar', id));
        revealedPasswords.value[id] = response.data.password;
    } catch (error) {
        window.$toast.error('No se pudo revelar la contraseña. Intente de nuevo.');
    } finally {
        isLoadingPassword.value[id] = false;
    }
};

const payingWithCredit = ref({});
const payingWithMP = ref({});



const payWithMercadoPago = async (ventaId) => {
    try {
        payingWithMP.value[ventaId] = true;
        const response = await axios.post(route('portal.pagos.mercadopago.crear'), { venta_id: ventaId });
        
        if (response.data.success && response.data.init_point) {
            window.location.href = response.data.init_point; 
        } else {
             window.$toast.error(response.data.message || 'No se pudo iniciar el pago.');
        }
    } catch (error) {
         window.$toast.error(error.response?.data?.message || 'Error al conectar con pasarela.');
    } finally {
        payingWithMP.value[ventaId] = false;
    }
};


const payWithCredit = async (ventaId) => {
    const confirmed = await confirmModal.value.show();
    if (!confirmed) return;

    try {
        payingWithCredit.value[ventaId] = true;
        const response = await axios.post(route('portal.ventas.pagar-credito'), { venta_id: ventaId });
        
        if (response.data.success) {
            window.$toast.success(response.data.message, '¡Pago Exitoso!');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    } catch (error) {
        window.$toast.error(error.response?.data?.message || 'Error al procesar el pago.', 'Error');
    } finally {
        payingWithCredit.value[ventaId] = false;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Mapeo de estados a clases estéticas
const getStatusClasses = (estado) => {
    const maps = {
        'abierto': 'bg-blue-50 text-blue-600 border-blue-100',
        'resuelto': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'en_progreso': 'bg-amber-50 text-amber-600 border-amber-100',
        'cerrado': 'bg-white text-gray-500 border-gray-100',
    };
    return maps[estado] || 'bg-white text-gray-500 border-gray-100';
};

const profileForm = useForm({
    nombre_razon_social: props.cliente.nombre_razon_social,
    email: props.cliente.email,
    telefono: props.cliente.telefono,
    calle: props.cliente.calle,
    numero_exterior: props.cliente.numero_exterior,
    numero_interior: props.cliente.numero_interior,
    colonia: props.cliente.colonia,
    municipio: props.cliente.municipio,
    estado: props.cliente.estado,
    codigo_postal: props.cliente.codigo_postal,
    domicilio_fiscal_cp: props.cliente.domicilio_fiscal_cp,
    rfc: props.cliente.rfc,
    regimen_fiscal: props.cliente.regimen_fiscal,
    uso_cfdi: props.cliente.uso_cfdi,
    password: '',
    password_confirmation: '',
});

const rfcRegex = /^([A-ZÑ&]{3,4})\d{6}([A-Z0-9]{3})$/i;
const rfcError = ref('');

const validateRfc = () => {
    if (!profileForm.rfc || profileForm.rfc.trim() === '') {
        rfcError.value = '';
        return true;
    }
    if (!rfcRegex.test(profileForm.rfc.toUpperCase())) {
        rfcError.value = 'Formato de RFC inválido. Ejemplo: XAXX010101000';
        return false;
    }
    rfcError.value = '';
    return true;
};

const updateProfile = () => {
    if (!validateRfc()) {
        window.$toast.error('Por favor corrija el RFC antes de continuar.');
        return;
    }

    profileForm.post(route('portal.perfil.update'), {
        preserveScroll: true,
        onSuccess: () => {
            window.$toast.success('Perfil actualizado correctamente.');
            profileForm.password = '';
            profileForm.password_confirmation = '';
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            window.$toast.error(firstError || 'Hubo un error al actualizar el perfil.');
        }
    });
};

const searchingCP = ref(false);
const coloniasEncontradas = ref([]);

const buscarCP = async () => {
    const cp = profileForm.codigo_postal;
    if (!cp || cp.length !== 5) return;

    searchingCP.value = true;
    try {
        const response = await axios.get(`/api/cp/${cp}`);
        const data = response.data;
        
        profileForm.estado = data.estado_clave || data.estado; // Preferimos la clave si existe
        profileForm.municipio = data.municipio;
        coloniasEncontradas.value = data.colonias || [];
        
        if (coloniasEncontradas.value.length === 1) {
            profileForm.colonia = coloniasEncontradas.value[0];
        }

        // Si el estado es texto largo, intentar buscar la clave en el catálogo
        if (profileForm.estado.length > 3) {
            const estadoEncontrado = props.catalogos.estados.find(e => 
                e.nombre.toLowerCase().includes(data.estado.toLowerCase())
            );
            if (estadoEncontrado) {
                profileForm.estado = estadoEncontrado.clave;
            }
        }
        
        window.$toast.success('Dirección actualizada por código postal.');
    } catch (error) {
        console.error('Error buscando CP:', error);
        window.$toast.error('No se encontró información para este código postal.');
    } finally {
        searchingCP.value = false;
    }
};

const activeFaq = ref(null);
const toggleFaq = (id) => {
    activeFaq.value = activeFaq.value === id ? null : id;
};

// Helper para verificar si una fecha está vencida
const isOverdue = (dateString) => {
    if (!dateString) return false;
    return new Date(dateString) < new Date();
};

// Computed para calcular el total pendiente
const totalPendiente = computed(() => {
    if (!props.pagosPendientes || props.pagosPendientes.length === 0) return 0;
    return props.pagosPendientes.reduce((acc, pago) => acc + parseFloat(pago.total || 0), 0);
});
</script>

<template>
    <Head title="Mi Panel de Soporte" />
    
    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <!-- Banner de Bloqueo por Falta de Pago -->
            <div v-if="isBlocked" class="mb-8 bg-red-600 rounded-[2rem] p-6 text-white shadow-xl shadow-red-500/20 flex flex-col sm:flex-row items-center justify-between gap-6 border-4 border-red-500 ring-4 ring-red-100 animate-pulse">
                <div class="flex items-center gap-6">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-3xl">
                        <font-awesome-icon icon="user-lock" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black uppercase tracking-tight mb-1">Servicio Suspendido</h2>
                        <p class="font-medium text-red-50 text-sm max-w-xl">
                            Su acceso al portal ha sido limitado temporalmente debido a adeudos vencidos. 
                            <span class="block mt-1 opacity-80 font-normal">Para restablecer el acceso completo a soporte, pólizas y tienda, por favor regularice su situación.</span>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold uppercase tracking-widest opacity-70 mb-1">Total Vencido Aprox.</p>
                    <p class="text-3xl font-black">${{ Number(totalPendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>
            
            <!-- Header de Bienvenida -->
            <div class="mb-10">
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2 transition-colors">
                    Hola, <span class="text-[var(--color-primary)]">{{ cliente.nombre_razon_social?.split(' ')[0] }}</span>
                </h1>
                <p class="text-gray-500 dark:text-gray-400 font-medium transition-colors">Gestione sus servicios y soporte técnico desde un solo lugar.</p>
            </div>

            <!-- Alerta Pagos Pendientes Premium -->
            <div v-if="pagosPendientes && pagosPendientes.length > 0" 
                 class="mb-10 bg-white dark:bg-gray-800 border border-red-100 dark:border-red-900/50 rounded-[2rem] p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl shadow-red-500/5 dark:shadow-none overflow-hidden relative group transition-colors">
                <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform"></div>
                
                <div class="flex items-center gap-6 relative z-10">
                    <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center text-red-500 text-2xl">
                        ⚠️
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white transition-colors">Pagos Pendientes</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-medium transition-colors">Tiene {{ pagosPendientes.length }} factura(s) que requieren su atención.</p>
                    </div>
                </div>
                <button @click="activeTab = 'pagos'" class="relative z-10 w-full sm:w-auto px-8 py-4 bg-red-500 text-white font-black text-sm uppercase tracking-widest rounded-2xl hover:bg-red-600 hover:shadow-lg hover:shadow-red-500/30 transition-all">
                    Gestionar Pagos
                </button>
            </div>

            <!-- Resumen de Crédito (Vista Rápida) -->
            <div v-if="cliente.credito_activo || cliente.estado_credito !== 'sin_credito'" 
                 class="mb-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <div class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none flex items-center justify-between group overflow-hidden relative transition-colors">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-[var(--color-primary-soft)] rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 transition-colors">Estado de Crédito</p>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white flex items-center gap-2 transition-colors">
                             <font-awesome-icon 
                                :icon="cliente.estado_credito === 'autorizado' ? 'check-circle' : 'info-circle'" 
                                :class="cliente.estado_credito === 'autorizado' ? 'text-emerald-500' : 'text-amber-500'" 
                             />
                             {{ cliente.estado_credito === 'autorizado' ? 'Autorizado' : (cliente.estado_credito === 'en_revision' ? 'En Revisión' : cliente.estado_credito) }}
                        </h3>
                    </div>
                </div>

                <div v-if="cliente.credito_activo" class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none group overflow-hidden relative transition-colors">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 transition-colors">Crédito Disponible</p>
                        <h3 class="text-3xl font-black text-emerald-600">
                             ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                        </h3>
                    </div>
                </div>

                <div v-if="cliente.credito_activo" class="bg-white dark:bg-gray-800 p-8 rounded-[2rem] border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none group overflow-hidden relative transition-colors">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-full -mr-12 -mt-12 group-hover:scale-125 transition-transform duration-500"></div>
                    <div class="relative z-10">
                         <Link :href="route('portal.credito.index')" class="flex items-center justify-between w-full group/link">
                            <div>
                                <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1 transition-colors">Línea Total</p>
                                <h3 class="text-xl font-black text-gray-900 dark:text-white transition-colors">
                                    ${{ Number(cliente.limite_credito).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </h3>
                            </div>
                            <font-awesome-icon icon="chevron-right" class="text-gray-300 group-hover/link:text-[var(--color-primary)] transition-colors" />
                         </Link>
                    </div>
                </div>
            </div>

            <!-- Main Content Tabs -->
            <div class="grid lg:grid-cols-4 gap-10">
                
                <!-- Sidebar Navigation -->
                <aside class="lg:col-span-1 space-y-2">
                    <button 
                        @click="activeTab = 'resumen'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'resumen' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="th-large" /> 
                        <span class="text-sm uppercase tracking-widest">Resumen</span>
                    </button>

                    <button 
                        @click="activeTab = 'tickets'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'tickets' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="ticket-alt" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Tickets</span>
                    </button>
                    
                    <button 
                        @click="activeTab = 'polizas'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'polizas' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="file-contract" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Pólizas</span>
                    </button>

                    <button 
                        @click="activeTab = 'citas'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'citas' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="calendar-check" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Citas</span>
                    </button>

                    <button 
                        @click="activeTab = 'equipos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'equipos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="desktop" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Equipos</span>
                    </button>

                    <button 
                        @click="activeTab = 'credenciales'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'credenciales' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="lock" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Accesos</span>
                    </button>

                    <button 
                        @click="activeTab = 'pedidos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'pedidos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="shopping-cart" /> 
                        <span class="text-sm uppercase tracking-widest">Mis Pedidos</span>
                    </button>

                    <Link 
                        :href="route('portal.credito.index')" 
                        class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700"
                    >
                        <font-awesome-icon icon="credit-card" /> 
                        <span class="text-sm uppercase tracking-widest">Mi Crédito</span>
                    </Link>

                     <button 
                        @click="activeTab = 'pagos'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'pagos' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="receipt" /> 
                        <span class="text-sm uppercase tracking-widest">Historial Pagos</span>
                    </button>

                    <button 
                        @click="activeTab = 'ayuda'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'ayuda' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="question-circle" /> 
                        <span class="text-sm uppercase tracking-widest">Centro de Ayuda</span>
                    </button>

                    <button 
                        @click="activeTab = 'perfil'" 
                        :class="[
                            'w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left',
                            activeTab === 'perfil' 
                                ? 'bg-[var(--color-primary)] text-white shadow-xl shadow-[var(--color-primary)]/20 shadow-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white border border-gray-100 dark:border-gray-700 transition-colors'
                        ]"
                    >
                        <font-awesome-icon icon="user-circle" /> 
                        <span class="text-sm uppercase tracking-widest">Mi Perfil</span>
                    </button>

                    <a 
                        :href="route('catalogo.index')"
                        class="w-full flex items-center gap-4 px-6 py-4 rounded-2xl font-bold transition-all text-left bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20 hover:shadow-xl hover:-translate-y-0.5"
                    >
                        <font-awesome-icon icon="shopping-bag" /> 
                        <span class="text-sm uppercase tracking-widest">Ir a la Tienda</span>
                    </a>
                </aside>

                <!-- Tab Panels Area -->
                <div class="lg:col-span-3">
                    
                    <!-- Tab: Resumen (NUEVO) -->
                    <div v-show="activeTab === 'resumen'" class="animate-fade-in space-y-8">
                        <div class="grid sm:grid-cols-2 gap-6">
                            <!-- Widget: Soporte -->
                            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                                            <font-awesome-icon icon="ticket-alt" />
                                        </div>
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Soporte Técnico</h3>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-4xl font-black text-gray-900">{{ tickets.total || 0 }}</p>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tickets Totales</p>
                                        </div>
                                        <button @click="activeTab = 'tickets'" class="text-xs font-black text-blue-600 hover:underline uppercase tracking-widest">Ver Historial</button>
                                    </div>
                                    <Link :href="route('portal.tickets.create')" class="mt-8 w-full flex items-center justify-center py-4 bg-gray-900 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-[var(--color-primary)] transition-all">
                                        + Solicitar Ayuda Ahora
                                    </Link>
                                </div>
                            </div>

                            <!-- Widget: Pagos -->
                            <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-red-50 rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center gap-4 mb-6">
                                        <div class="w-12 h-12 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-xl">
                                            <font-awesome-icon icon="receipt" />
                                        </div>
                                        <h3 class="text-lg font-black text-gray-900 uppercase tracking-tight">Finanzas</h3>
                                    </div>
                                    <div class="flex items-end justify-between">
                                        <div>
                                            <p class="text-4xl font-black text-red-500">${{ Number(pagosPendientes.reduce((acc, p) => acc + parseFloat(p.total), 0)).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Por Pagar</p>
                                        </div>
                                        <button @click="activeTab = 'pagos'" class="text-xs font-black text-red-600 hover:underline uppercase tracking-widest">Detalles</button>
                                    </div>
                                    <button @click="activeTab = 'pagos'" class="mt-8 w-full flex items-center justify-center py-4 bg-red-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-red-600 transition-all">
                                        Pagar Facturas Pendientes
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Fila Inferior: Póliza Destacada & Equipos -->
                        <div class="grid lg:grid-cols-3 gap-6">
                             <!-- Póliza Widget -->
                             <div class="lg:col-span-2 bg-gradient-to-br from-gray-900 to-gray-800 rounded-[2rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                                <div class="absolute right-0 bottom-0 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                    <font-awesome-icon icon="shield-alt" class="text-[12rem]" />
                                </div>
                                <div class="relative z-10">
                                    <div class="flex justify-between items-start mb-10">
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-2 font-mono">Contrato Vigente</p>
                                            <h3 class="text-2xl font-black">{{ polizas[0]?.nombre || 'Sin Póliza Activa' }}</h3>
                                        </div>
                                        <div class="px-4 py-2 bg-white/10 backdrop-blur-md rounded-xl text-[10px] font-bold uppercase tracking-widest">
                                            {{ polizas[0]?.estado || 'N/A' }}
                                        </div>
                                    </div>

                                    <div v-if="polizas[0]" class="grid sm:grid-cols-2 gap-8 mb-8 text-center sm:text-left">
                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Días Restantes</p>
                                            <p class="text-3xl font-black">{{ polizas[0]?.dias_para_vencer }} <span class="text-xs text-gray-500">Días</span></p>
                                        </div>
                                        <div class="p-6 bg-white/5 rounded-3xl border border-white/5">
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Tickets del Mes</p>
                                            <p class="text-3xl font-black">{{ polizas[0]?.tickets_mes_actual_count }} <span class="text-xs text-gray-500">Consumidos</span></p>
                                        </div>
                                    </div>

                                    <button @click="activeTab = 'polizas'" class="flex items-center gap-2 group/btn font-black text-[10px] uppercase tracking-widest text-[var(--color-primary)]">
                                        Ver todas mis pólizas
                                        <font-awesome-icon icon="arrow-right" class="group-hover/btn:translate-x-1 transition-transform" />
                                    </button>
                                </div>
                             </div>

                             <!-- Acceso Rápido Tienda -->
                             <div class="bg-[var(--color-primary)] rounded-[2rem] p-8 text-white flex flex-col justify-between shadow-xl shadow-[var(--color-primary)]/30">
                                 <div>
                                     <h3 class="text-xl font-black uppercase tracking-tight mb-2">Comprar Insumos</h3>
                                     <p class="text-white/80 text-sm font-medium leading-relaxed">¿Necesitas refacciones o equipo nuevo? Visita nuestra tienda online.</p>
                                 </div>
                                 <a :href="route('catalogo.index')" class="w-full py-4 bg-white text-[var(--color-primary)] rounded-2xl font-black text-xs uppercase tracking-widest text-center shadow-lg hover:shadow-2xl transition-all">
                                     Ir a la Tienda 🛍️
                                 </a>
                             </div>
                        </div>
                    </div>

                    <!-- Tab: Tickets -->
                    <div v-show="activeTab === 'tickets'" class="animate-fade-in space-y-6">
                        <div class="flex justify-between items-center px-2">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Historial de Soporte</h2>
                            <Link
                                :href="route('portal.tickets.create')"
                                class="px-6 py-3 bg-[var(--color-terciary)] text-white rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-lg transition-all"
                            >
                                + Nuevo Ticket
                            </Link>
                        </div>

                        <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                            <div class="divide-y divide-gray-50">
                                <div v-for="ticket in tickets.data" :key="ticket.id" class="group">
                                    <Link :href="route('portal.tickets.show', ticket)" class="block p-6 hover:bg-white/50 transition-all">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div class="flex gap-4 items-start">
                                                <div class="mt-1 w-10 h-10 rounded-xl bg-white flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all">
                                                    <font-awesome-icon icon="ticket-alt" />
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-gray-900 group-hover:text-[var(--color-primary)] transition-colors">{{ ticket.titulo }}</h3>
                                                    <div class="flex items-center gap-3 mt-1">
                                                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 font-mono">{{ ticket.numero }}</span>
                                                        <span class="h-1 w-1 rounded-full bg-gray-300"></span>
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ ticket.categoria?.nombre || 'General' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between sm:justify-end gap-6">
                                                <div class="text-right hidden sm:block">
                                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</p>
                                                    <p class="text-xs font-bold text-gray-600">{{ formatDate(ticket.created_at) }}</p>
                                                </div>
                                                <span 
                                                    class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                                    :class="getStatusClasses(ticket.estado)"
                                                >
                                                    {{ ticket.estado }}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                </div>
                                <div v-if="tickets.data.length === 0" class="p-20 text-center">
                                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 text-2xl">
                                        ✨
                                    </div>
                                    <h3 class="text-lg font-black text-gray-900 mb-2">Todo en Orden</h3>
                                    <p class="text-gray-500 font-medium">No tiene solicitudes de soporte activas en este momento.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Premium -->
                        <div v-if="tickets.next_page_url || tickets.prev_page_url" class="flex justify-center gap-4 pt-4">
                             <Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url" class="px-6 py-3 bg-white border border-gray-100 rounded-xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-white transition-all">Anterior</Link>
                             <Link v-if="tickets.next_page_url" :href="tickets.next_page_url" class="px-6 py-3 bg-white border border-gray-100 rounded-xl font-black text-[10px] uppercase tracking-widest text-gray-500 hover:bg-white transition-all">Siguiente</Link>
                        </div>
                    </div>

                    <!-- Tab: Pólizas -->
                    <div v-show="activeTab === 'polizas'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Mis Servicios Activos</h2>
                        </div>
                        
                        <div class="grid gap-6 md:grid-cols-2">
                            <div v-for="poliza in polizas" :key="poliza.id" class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden group hover:-translate-y-1 transition-all duration-300">
                                <div class="p-8">
                                    <div class="flex justify-between items-start mb-6">
                                        <div class="w-14 h-14 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] text-xl group-hover:scale-110 transition-transform">
                                            <font-awesome-icon icon="shield-alt" />
                                        </div>
                                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] font-black rounded-full uppercase tracking-widest border border-emerald-100">
                                            {{ poliza.estado }}
                                        </span>
                                    </div>
                                    
                                    <h3 class="text-xl font-black text-gray-900 mb-1 group-hover:text-[var(--color-primary)] transition-colors">{{ poliza.nombre }}</h3>
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6 font-mono">{{ poliza.folio }}</p>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-gray-50">
                                        <!-- Barra de Horas -->
                                        <div v-if="poliza.horas_incluidas_mensual > 0">
                                            <div class="flex justify-between items-end mb-2">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Horas de Soporte</p>
                                                <p class="text-[10px] font-bold" :class="poliza.excede_horas ? 'text-red-500' : 'text-gray-600'">
                                                    {{ poliza.horas_consumidas_mes || 0 }} / {{ poliza.horas_incluidas_mensual }} hrs
                                                </p>
                                            </div>
                                            <div class="w-full bg-white rounded-full h-1.5 overflow-hidden">
                                                 <div 
                                                    class="h-full rounded-full transition-all duration-1000 ease-out" 
                                                    :class="poliza.excede_horas ? 'bg-red-500' : 'bg-blue-500'"
                                                    :style="{ width: Math.min(poliza.porcentaje_horas || 0, 100) + '%' }"
                                                 ></div>
                                            </div>
                                        </div>

                                        <!-- Barra de Tickets -->
                                        <div v-if="poliza.limite_mensual_tickets > 0">
                                            <div class="flex justify-between items-end mb-2">
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tickets Incluidos</p>
                                                <p class="text-[10px] font-bold text-gray-600">
                                                    {{ poliza.tickets_mes_actual_count || 0 }} / {{ poliza.limite_mensual_tickets }}
                                                </p>
                                            </div>
                                            <div class="w-full bg-white rounded-full h-1.5 overflow-hidden">
                                                 <div 
                                                    class="bg-emerald-500 h-full rounded-full transition-all duration-1000 ease-out" 
                                                    :style="{ width: Math.min(poliza.porcentaje_tickets || 0, 100) + '%' }"
                                                 ></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 flex items-center justify-between">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                            Vencimiento: <span class="text-gray-900 ml-1">{{ formatDate(poliza.fecha_fin) }}</span>
                                        </p>
                                        <p v-if="poliza.dias_para_vencer <= 30" class="text-[10px] font-black text-orange-500 uppercase">
                                            Vence en {{ poliza.dias_para_vencer }} días
                                        </p>
                                    </div>
                                </div>
                                <div class="bg-white px-8 py-4 flex justify-between items-center group-hover:bg-[var(--color-primary-soft)] transition-colors">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-[var(--color-primary)]">
                                        {{ poliza.renovacion_automatica ? 'Renovación Automática' : 'Renovación Manual' }}
                                    </span>
                                    <div class="flex gap-2">
                                        <a :href="route('portal.polizas.imprimir', poliza.id)" target="_blank" class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 hover:text-[var(--color-primary)] hover:underline flex items-center gap-1">
                                            <font-awesome-icon icon="file-pdf" /> Contrato
                                        </a>
                                        <Link :href="route('portal.polizas.show', poliza.id)" class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)] hover:underline flex items-center gap-2">
                                            <font-awesome-icon icon="eye" /> Detalles
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <div v-if="polizas.length === 0" class="col-span-full py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-gray-100">
                                <p class="text-gray-400 font-bold mb-6">No tiene pólizas vigentes en su cuenta.</p>
                                <Link :href="route('catalogo.polizas')" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all inline-block">Ver Catálogo de Pólizas</Link>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Citas -->
                    <div v-show="activeTab === 'citas'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                            <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Mis Próximas Citas</h2>
                        </div>
                        <div v-if="citas && citas.length > 0" class="grid gap-6 md:grid-cols-2">
                            <div v-for="cita in citas" :key="cita.id" class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 p-8">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                                        <font-awesome-icon icon="calendar-check" />
                                    </div>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-600 text-[10px] font-black rounded-full uppercase tracking-widest border border-blue-200">
                                        {{ cita.estado }}
                                    </span>
                                </div>
                                <p class="text-lg font-black text-gray-900">{{ formatDate(cita.fecha_hora) }}</p>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Técnico: {{ cita.tecnico?.name || 'Por confirmar' }}</p>
                                <p class="text-sm text-gray-600 mt-4 h-10 overflow-hidden">{{ cita.descripcion }}</p>
                            </div>
                        </div>
                        <div v-else class="py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-gray-100">
                            <p class="text-gray-400 font-bold">No tiene citas programadas.</p>
                        </div>
                    </div>

                    <div v-show="activeTab === 'equipos'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Inventario de Equipos Protegidos</h2>
                             <p class="text-gray-500 text-sm font-medium">Equipos registrados bajo sus contratos de soporte.</p>
                        </div>

                        <div class="grid gap-6 md:grid-cols-2">
                            <template v-for="poliza in polizas" :key="'e-'+poliza.id">
                                <div v-for="equipo in poliza.equipos" :key="equipo.id" 
                                     class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-[var(--color-primary)] transition-all">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all text-xl">
                                        <font-awesome-icon :icon="equipo.tipo === 'Laptop' || equipo.tipo === 'Servidor' ? 'server' : 'desktop'" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900">{{ equipo.nombre || equipo.modelo }}</h4>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.numero_serie || 'N/A' }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Cubierto por {{ poliza.folio }}</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Equipos manuales de la póliza (condiciones especiales) -->
                            <template v-for="poliza in polizas" :key="'ce-'+poliza.id">
                                <div v-for="(equipo, idx) in poliza.condiciones_especiales?.equipos_cliente" :key="poliza.id + '-ce-' + idx"
                                     class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-[var(--color-primary)] transition-all">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-[var(--color-primary-soft)] group-hover:text-[var(--color-primary)] transition-all text-xl">
                                        <font-awesome-icon icon="desktop" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-900">{{ equipo.nombre }}</h4>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.serie || 'N/A' }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase">Contrato Activo</div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="!polizas.some(p => p.equipos?.length > 0 || p.condiciones_especiales?.equipos_cliente?.length > 0)" 
                                 class="col-span-full py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="desktop" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">Sin equipos vinculados</h3>
                                <p class="text-gray-500 font-medium text-sm">Comuníquese con soporte para registrar sus activos.</p>
                            </div>
                        </div>

                        <!-- Equipos de Renta (POS, etc) -->
                        <div v-if="rentas && rentas.length > 0" class="mt-12">
                            <div class="px-2 mb-6">
                                 <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Equipos en Renta (POS)</h2>
                                 <p class="text-gray-500 text-sm font-medium">Equipos activos actualmente bajo contrato de arrendamiento.</p>
                            </div>
                            
                            <div class="grid gap-6 md:grid-cols-2">
                                <template v-for="renta in rentas" :key="'r-'+renta.id">
                                    <div v-for="equipo in renta.equipos" :key="'re-'+equipo.id" 
                                         class="bg-white rounded-[2rem] p-6 shadow-xl shadow-gray-200/50 border border-gray-100 flex items-center gap-6 group hover:border-emerald-500 transition-all relative overflow-hidden">
                                        
                                        <!-- Badge Renta -->
                                        <div class="absolute top-0 right-0 px-4 py-1 bg-emerald-500 text-white text-[8px] font-black uppercase tracking-widest rounded-bl-xl shadow-sm">
                                            Renta
                                        </div>

                                        <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-gray-400 group-hover:bg-emerald-50 group-hover:text-emerald-600 transition-all text-xl">
                                            <font-awesome-icon :icon="equipo.tipo === 'Laptop' || equipo.tipo === 'Servidor' ? 'server' : 'desktop'" />
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-900">{{ equipo.nombre || equipo.modelo }}</h4>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest font-mono">S/N: {{ equipo.numero_serie || 'N/A' }}</p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Contrato {{ renta.numero_contrato }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Historial y Pagos -->
                    <div v-show="activeTab === 'pagos'" class="animate-fade-in space-y-6" id="historial-pagos">
                        
                        <!-- Sección: Pagos Pendientes -->
                        <div v-if="pagosPendientes && pagosPendientes.length > 0" class="mb-8">
                            <div class="px-2 mb-4">
                                <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight flex items-center gap-3">
                                    <span class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></span>
                                    Pagos Pendientes ({{ pagosPendientes.length }})
                                </h2>
                                <p class="text-gray-500 text-sm font-medium">Estos son los pagos que requieren su atención.</p>
                            </div>

                            <div class="bg-white rounded-[2rem] shadow-xl shadow-red-500/10 border border-red-100 overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left">
                                        <thead class="bg-red-50 border-b border-red-100">
                                            <tr>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest">Tipo</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest">Folio</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest">Concepto</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest">Vencimiento</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest text-right">Monto</th>
                                                <th class="px-8 py-4 text-[10px] font-black text-red-600 uppercase tracking-widest text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-red-50">
                                            <tr v-for="pago in pagosPendientes" :key="pago.tipo + '-' + pago.id" class="hover:bg-red-50/50 transition-colors">
                                                <td class="px-8 py-4">
                                                    <span 
                                                        class="px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                                        :class="pago.tipo === 'venta' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600'"
                                                    >
                                                        {{ pago.tipo === 'venta' ? 'Venta' : 'Servicio' }}
                                                    </span>
                                                </td>
                                                <td class="px-8 py-4 font-mono text-xs font-bold text-gray-500">#{{ pago.folio }}</td>
                                                <td class="px-8 py-4 text-xs font-medium text-gray-600 max-w-xs truncate">{{ pago.concepto }}</td>
                                                <td class="px-8 py-4 text-xs font-bold" :class="isOverdue(pago.fecha_vencimiento) ? 'text-red-600' : 'text-gray-900'">
                                                    {{ formatDate(pago.fecha_vencimiento) }}
                                                    <span v-if="isOverdue(pago.fecha_vencimiento)" class="ml-1 text-[9px] bg-red-100 text-red-600 px-1 rounded">VENCIDO</span>
                                                </td>
                                                <td class="px-8 py-4 text-sm font-black text-red-600 text-right">${{ Number(pago.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</td>
                                                <td class="px-8 py-4 text-right">
                                                    <div class="flex justify-end gap-2">
                                                        <!-- Botón Pagar con Crédito (solo ventas) -->
                                                        <button v-if="pago.tipo === 'venta' && pago.puede_pagar_credito && cliente.credito_activo && cliente.credito_disponible >= pago.total"
                                                                @click="payWithCredit(pago.id)"
                                                                :disabled="payingWithCredit[pago.id]"
                                                                class="px-3 py-1 bg-emerald-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all disabled:opacity-50"
                                                                title="Pagar con Crédito">
                                                            {{ payingWithCredit[pago.id] ? '...' : '💳 Crédito' }}
                                                        </button>
                                                        
                                                        <!-- Botón MercadoPago (solo ventas) -->
                                                        <button v-if="pago.tipo === 'venta'"
                                                                @click="payWithMercadoPago(pago.id)"
                                                                :disabled="payingWithMP[pago.id]"
                                                                class="px-3 py-1 bg-blue-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all disabled:opacity-50 flex items-center gap-1"
                                                                title="Pagar Online">
                                                            <font-awesome-icon :icon="payingWithMP[pago.id] ? 'spinner' : 'credit-card'" :spin="payingWithMP[pago.id]" />
                                                            {{ payingWithMP[pago.id] ? '...' : 'Pagar' }}
                                                        </button>

                                                        <!-- Para CxC: Mostrar botón de contacto -->
                                                        <a v-if="pago.tipo === 'cxc'" 
                                                           :href="'https://wa.me/' + (empresa?.telefono || '').replace(/\\D/g, '') + '?text=Hola, quiero pagar mi cuenta ' + pago.folio"
                                                           target="_blank"
                                                           class="px-3 py-1 bg-green-500 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-green-600 transition-all flex items-center gap-1">
                                                            <font-awesome-icon :icon="['fab', 'whatsapp']" /> Coordinar Pago
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-red-50 border-t border-red-100">
                                            <tr>
                                                <td colspan="4" class="px-8 py-4 text-right text-sm font-black text-gray-700 uppercase tracking-widest">Total Pendiente:</td>
                                                <td class="px-8 py-4 text-right text-lg font-black text-red-600">${{ totalPendiente.toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Historial de Ventas -->
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Historial de Ventas</h2>
                        </div>

                        <div class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-white border-b border-gray-100">
                                        <tr>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Folio</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Concepto</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Monto</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Estado</th>
                                            <th class="px-8 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="venta in ventas.data" :key="venta.id" class="hover:bg-white/50 transition-colors">
                                            <td class="px-8 py-4 font-mono text-xs font-bold text-gray-500">#{{ venta.folio || venta.id }}</td>
                                            <td class="px-8 py-4 text-xs font-bold text-gray-900">{{ formatDate(venta.fecha) }}</td>
                                            <td class="px-8 py-4 text-xs font-medium text-gray-600 max-w-xs truncate">{{ venta.notas || 'Venta General' }}</td>
                                            <td class="px-8 py-4 text-xs font-black text-gray-900 text-right">${{ Number(venta.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</td>
                                            <td class="px-8 py-4 text-center">
                                                <span 
                                                    class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border"
                                                    :class="{
                                                        'bg-green-50 text-green-600 border-green-100': venta.estado === 'pagado',
                                                        'bg-yellow-50 text-yellow-600 border-yellow-100': venta.estado === 'pendiente',
                                                        'bg-red-50 text-red-600 border-red-100': venta.estado === 'cancelado' || venta.estado === 'vencida'
                                                    }"
                                                >
                                                    {{ venta.estado }}
                                                </span>
                                            </td>
                                            <td class="px-8 py-4 text-right">
                                                <a :href="route('portal.ventas.pdf', venta.id)" 
                                                   target="_blank"
                                                   class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all inline-flex items-center gap-1"
                                                   title="Descargar PDF">
                                                    <font-awesome-icon icon="file-pdf" /> PDF
                                                </a>
                                            </td>
                                        </tr>
                                        <tr v-if="!ventas.data || ventas.data.length === 0">
                                            <td colspan="6" class="px-8 py-12 text-center text-gray-400 text-sm font-medium">No se encontraron registros de ventas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                         <!-- Pagination -->
                         <div v-if="ventas.links && ventas.links.length > 3" class="flex justify-center gap-2 mt-4">
                            <template v-for="(link, key) in ventas.links" :key="key">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-4 py-2 text-xs font-bold rounded-lg border"
                                    :class="link.active ? 'bg-[var(--color-primary)] text-white border-[var(--color-primary)]' : 'bg-white text-gray-500 border-gray-100 hover:bg-white'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-4 py-2 text-xs text-gray-400" v-html="link.label"></span>
                            </template>
                        </div>
                    </div>

                    <!-- Tab: Credenciales -->
                    <div v-show="activeTab === 'credenciales'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Caja Fuerte de Accesos</h2>
                             <p class="text-gray-500 text-sm font-medium">Sus claves de acceso se encuentran encriptadas y cada revelación es auditada.</p>
                        </div>

                        <div class="grid gap-6">
                            <div v-for="cred in credenciales" :key="cred.id" 
                                 class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 border-l-4 border-l-[var(--color-primary)]">
                                <div class="flex flex-col md:flex-row justify-between gap-6">
                                    <div class="flex-1 space-y-4">
                                        <div>
                                            <h3 class="text-xl font-black text-gray-900">{{ cred.nombre }}</h3>
                                            <p v-if="cred.host" class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ cred.host }}{{ cred.puerto ? ':' + cred.puerto : '' }}</p>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-white p-4 rounded-xl">
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Usuario</p>
                                                <p class="text-sm font-bold text-gray-800">{{ cred.usuario }}</p>
                                            </div>
                                            <div class="bg-white p-4 rounded-xl relative overflow-hidden">
                                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Contraseña</p>
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-sm font-mono font-bold text-gray-800">
                                                        {{ revealedPasswords[cred.id] || '••••••••••••' }}
                                                    </p>
                                                    <button 
                                                        @click="revealPassword(cred.id)"
                                                        class="p-1 hover:text-[var(--color-primary)] transition-colors text-gray-400"
                                                        :disabled="isLoadingPassword[cred.id]"
                                                    >
                                                        <font-awesome-icon :icon="isLoadingPassword[cred.id] ? 'spinner' : (revealedPasswords[cred.id] ? 'eye-slash' : 'eye')" :spin="isLoadingPassword[cred.id]" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="cred.notas" class="border-t border-gray-100 pt-4 mt-4">
                                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Notas Adicionales</p>
                                            <p class="text-xs text-gray-600 font-medium">{{ cred.notas }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex md:flex-col justify-end gap-2">
                                        <div class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-[10px] font-black uppercase text-center flex items-center gap-2">
                                            <font-awesome-icon icon="shield-check" /> Protegido
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="credenciales.length === 0" class="py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="lock" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">No hay credenciales</h3>
                                <p class="text-gray-500 font-medium text-sm">Nuestro equipo aún no ha registrado claves de acceso para su cuenta.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Pedidos -->
                    <div v-show="activeTab === 'pedidos'" class="animate-fade-in space-y-6">
                        <div class="px-2">
                             <h2 class="text-xl font-black text-gray-900 uppercase tracking-tight">Mis Pedidos Online</h2>
                             <p class="text-gray-500 text-sm font-medium">Siga el estado de sus compras realizadas en nuestra tienda.</p>
                        </div>

                        <div class="grid gap-6">
                            <div v-for="pedido in pedidos" :key="pedido.id" 
                                 class="bg-white rounded-[2rem] p-8 shadow-xl shadow-gray-200/50 border border-gray-100 group hover:border-[var(--color-primary)] transition-all">
                                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                                    <div class="flex items-center gap-6">
                                        <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] text-2xl">
                                            <font-awesome-icon icon="box" />
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-black text-gray-900 group-hover:text-[var(--color-primary)] transition-colors">Pedido #{{ pedido.numero_pedido || pedido.id }}</h3>
                                            <p class="text-sm font-bold text-gray-500">{{ formatDate(pedido.fecha_pedido || pedido.created_at) }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col items-end gap-2">
                                        <span 
                                            class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border border-blue-100 bg-blue-50 text-blue-600"
                                        >
                                            {{ pedido.estado }}
                                        </span>
                                        <p class="text-lg font-black text-gray-900">${{ Number(pedido.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p>
                                    </div>
                                    
                                    <div class="flex gap-4">
                                         <Link :href="route('portal.pedidos.show', pedido.id)" class="px-6 py-3 bg-white text-gray-700 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-200">
                                            Detalles
                                        </Link>
                                    </div>
                                </div>
                                
                                <div v-if="pedido.numero_guia" class="mt-6 pt-6 border-t border-gray-50 flex items-center gap-4 text-emerald-600">
                                    <font-awesome-icon icon="truck" />
                                    <p class="text-xs font-bold uppercase tracking-widest">Guía: <span class="text-gray-900 ml-2 font-mono">{{ pedido.numero_guia }}</span> ({{ pedido.empresa_envio }})</p>
                                </div>
                            </div>

                            <div v-if="pedidos.length === 0" class="py-20 bg-white rounded-[2rem] border-2 border-dashed border-gray-100 text-center">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                    <font-awesome-icon icon="shopping-cart" size="lg" />
                                </div>
                                <h3 class="text-lg font-black text-gray-900 mb-1">No hay pedidos</h3>
                                <p class="text-gray-500 font-medium text-sm">Aún no ha realizado compras en nuestra tienda en línea.</p>
                                <a :href="route('catalogo.index')" class="mt-6 inline-block px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all">Ir a la Tienda</a>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Centro de Ayuda -->
                    <div v-show="activeTab === 'ayuda'" class="animate-fade-in space-y-10">
                        <div class="px-2">
                             <h2 class="text-3xl font-black text-gray-900 tracking-tight">Centro de Ayuda</h2>
                             <p class="text-gray-500 font-medium mt-2">Encuentre respuestas rápidas y recursos para sus servicios.</p>
                        </div>

                        <!-- Canales de Soporte Rápido -->
                        <div class="grid sm:grid-cols-3 gap-6">
                            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 text-center group hover:border-[var(--color-primary)] transition-all">
                                <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl group-hover:scale-110 transition-transform">
                                    <font-awesome-icon :icon="['fab', 'whatsapp']" />
                                </div>
                                <h4 class="font-black text-gray-900 mb-2 font-mono">WhatsApp</h4>
                                <p class="text-xs text-gray-500 font-medium mb-4">Atención inmediata para urgencias.</p>
                                <a :href="'https://wa.me/' + (empresa?.telefono || '521234567890')" target="_blank" class="px-6 py-2 bg-emerald-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 transition-all">Abrir Chat</a>
                            </div>

                            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 text-center group hover:border-[var(--color-primary)] transition-all">
                                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl group-hover:scale-110 transition-transform">
                                    <font-awesome-icon icon="envelope" />
                                </div>
                                <h4 class="font-black text-gray-900 mb-2 font-mono">Email</h4>
                                <p class="text-xs text-gray-500 font-medium mb-4">Consultas generales y seguimientos.</p>
                                <a :href="'mailto:' + (empresa?.email || 'soporte@asistenciavircom.com')" class="px-6 py-2 bg-blue-500 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all">Enviar Correo</a>
                            </div>

                            <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 text-center group hover:border-[var(--color-primary)] transition-all">
                                <div class="w-16 h-16 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl group-hover:scale-110 transition-transform">
                                    <font-awesome-icon icon="file-pdf" />
                                </div>
                                <h4 class="font-black text-gray-900 mb-2 font-mono">Manuales</h4>
                                <p class="text-xs text-gray-500 font-medium mb-4">Guías de configuración rápidas.</p>
                                <Link :href="route('portal.manual')" class="px-6 py-2 bg-gray-900 text-white rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-800 transition-all">Ver Manual</Link>
                            </div>
                        </div>

                        <!-- FAQ Section -->
                        <div class="bg-white rounded-[3rem] p-10 shadow-2xl shadow-gray-200/50 border border-gray-50">
                            <h3 class="text-2xl font-black text-gray-900 mb-10 text-center">Preguntas Frecuentes</h3>
                            
                            <div class="space-y-4 w-full">
                                <div v-for="faq in faqs" :key="faq.id" 
                                     class="border border-gray-100 rounded-[2rem] overflow-hidden transition-all duration-300"
                                     :class="activeFaq === faq.id ? 'border-[var(--color-primary)] shadow-lg' : 'hover:border-gray-200'">
                                    
                                    <button 
                                        @click="toggleFaq(faq.id)"
                                        class="w-full px-8 py-6 flex items-center justify-between text-left group"
                                    >
                                        <span class="font-bold text-gray-900 group-hover:text-[var(--color-primary)] transition-colors">{{ faq.pregunta }}</span>
                                        <font-awesome-icon 
                                            :icon="activeFaq === faq.id ? 'minus' : 'plus'" 
                                            class="text-xs transition-transform duration-300"
                                            :class="activeFaq === faq.id ? 'text-[var(--color-primary)]' : 'text-gray-300'"
                                        />
                                    </button>

                                    <div v-show="activeFaq === faq.id" class="px-8 pb-8 animate-fade-in">
                                        <div class="h-px bg-white mb-6"></div>
                                        <p class="text-gray-600 font-medium leading-relaxed whitespace-pre-line text-sm">
                                            {{ faq.respuesta }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="faqs.length === 0" class="text-center py-10">
                                    <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No hay preguntas frecuentes registradas.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Mi Perfil -->
                    <div v-show="activeTab === 'perfil'" class="animate-fade-in space-y-10 pb-20">
                        <div class="px-2">
                             <h2 class="text-3xl font-black text-gray-900 tracking-tight">Mi Perfil</h2>
                             <p class="text-gray-500 font-medium mt-2">Gestione sus datos de contacto, facturación y seguridad.</p>
                        </div>

                        <form @submit.prevent="updateProfile" class="space-y-8">
                            <!-- Sección: Datos de Contacto -->
                            <div class="bg-white rounded-[3rem] p-10 shadow-xl border border-gray-100">
                                <h3 class="text-lg font-black text-gray-900 mb-8 flex items-center gap-3">
                                    <font-awesome-icon icon="address-book" class="text-[var(--color-primary)]" />
                                    Datos de Contacto
                                </h3>
                                
                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nombre o Razón Social</label>
                                        <input v-model="profileForm.nombre_razon_social" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Correo Electrónico</label>
                                        <input v-model="profileForm.email" type="email" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teléfono</label>
                                        <input v-model="profileForm.telefono" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                </div>

                                <div class="mt-8 pt-8 border-t border-gray-50">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-6">Dirección de Contacto</p>
                                    <div class="grid sm:grid-cols-3 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Código Postal</label>
                                            <div class="relative">
                                                <input v-model="profileForm.codigo_postal" @blur="buscarCP" type="text" maxlength="5" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                                <div v-if="searchingCP" class="absolute right-4 top-1/2 -translate-y-1/2">
                                                    <font-awesome-icon icon="spinner" spin class="text-[var(--color-primary)]" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-span-2 space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Calle</label>
                                            <input v-model="profileForm.calle" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No. Exterior</label>
                                            <input v-model="profileForm.numero_exterior" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No. Interior</label>
                                            <input v-model="profileForm.numero_interior" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Colonia</label>
                                            <template v-if="coloniasEncontradas.length > 0">
                                                <select v-model="profileForm.colonia" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all">
                                                    <option v-for="col in coloniasEncontradas" :key="col" :value="col">{{ col }}</option>
                                                </select>
                                            </template>
                                            <template v-else>
                                                <input v-model="profileForm.colonia" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                            </template>
                                        </div>
                                        <div class="space-y-2 text-gray-400">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Municipio</label>
                                            <input v-model="profileForm.municipio" type="text" readonly class="w-full bg-gray-100 border-none rounded-2xl p-4 font-bold text-gray-500 cursor-not-allowed" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estado</label>
                                            <select v-model="profileForm.estado" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all">
                                                <option v-for="est in catalogos.estados" :key="est.id" :value="est.clave">{{ est.nombre }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección: Datos Fiscales (CFDI 4.0) -->
                            <div class="bg-white rounded-[3rem] p-10 shadow-xl border border-gray-100">
                                <h3 class="text-lg font-black text-gray-900 mb-8 flex items-center gap-3">
                                    <font-awesome-icon icon="file-invoice" class="text-[var(--color-primary)]" />
                                    Datos Fiscales
                                </h3>
                                
                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">RFC</label>
                                        <input 
                                            v-model="profileForm.rfc" 
                                            @blur="validateRfc"
                                            type="text" 
                                            maxlength="13"
                                            class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all uppercase"
                                            :class="{'ring-2 ring-red-400': rfcError}"
                                        />
                                        <p v-if="rfcError" class="text-red-500 text-xs font-medium">{{ rfcError }}</p>
                                        <p v-if="profileForm.errors.rfc" class="text-red-500 text-xs font-medium">{{ profileForm.errors.rfc }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Domicilio Fiscal (C.P.)</label>
                                        <input v-model="profileForm.domicilio_fiscal_cp" type="text" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                    <div class="space-y-2 sm:col-span-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Régimen Fiscal</label>
                                        <select v-model="profileForm.regimen_fiscal" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all text-xs">
                                            <option v-for="reg in catalogos.regimenes" :key="reg.id" :value="reg.clave">
                                                {{ reg.clave }} - {{ reg.descripcion }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="space-y-2 sm:col-span-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Uso de CFDI Sugerido</label>
                                        <select v-model="profileForm.uso_cfdi" class="w-full bg-white border-none rounded-2xl p-4 font-bold text-gray-900 focus:ring-2 focus:ring-[var(--color-primary)] transition-all text-xs">
                                            <option v-for="uso in catalogos.usos_cfdi" :key="uso.id" :value="uso.clave">
                                                {{ uso.clave }} - {{ uso.descripcion }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección: Seguridad -->
                            <div class="bg-gray-900 rounded-[3rem] p-10 shadow-xl text-white">
                                <h3 class="text-lg font-black mb-8 flex items-center gap-3">
                                    <font-awesome-icon icon="lock" class="text-[var(--color-primary)]" />
                                    Seguridad y Contraseña
                                </h3>
                                
                                <div class="grid sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Nueva Contraseña</label>
                                        <input v-model="profileForm.password" type="password" placeholder="Dejar en blanco para no cambiar" class="w-full bg-white/5 border-white/10 rounded-2xl p-4 font-bold text-white focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Confirmar Contraseña</label>
                                        <input v-model="profileForm.password_confirmation" type="password" class="w-full bg-white/5 border-white/10 rounded-2xl p-4 font-bold text-white focus:ring-2 focus:ring-[var(--color-primary)] transition-all" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-6">
                                <button 
                                    type="submit" 
                                    :disabled="profileForm.processing"
                                    class="px-12 py-5 bg-[var(--color-primary)] text-white rounded-[2rem] font-black text-sm uppercase tracking-[0.2em] shadow-2xl shadow-orange-500/30 hover:shadow-orange-500/50 hover:-translate-y-1 transition-all disabled:opacity-50"
                                >
                                    {{ profileForm.processing ? 'Guardando...' : 'Actualizar mi Información' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación Premium -->
        <PortalConfirmModal 
            ref="confirmModal"
            title="Confirmar Pago"
            message="¿Estás seguro de querer utilizar tu crédito comercial para liquidar esta factura? El saldo se descontará de tu línea disponible."
            confirm-label="Sí, pagar ahora"
            cancel-label="No, volver"
            type="success"
        />
        <DeudaModal 
            :show="showDeudaModal" 
            :pagos-pendientes="pagosPendientes"
            :empresa="empresa"
            @close="showDeudaModal = false"
            @pagar="handlePagarDeuda"
        />
    </ClientLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
