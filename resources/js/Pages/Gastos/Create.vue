<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import axios from 'axios';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import Swal from 'sweetalert2';

const props = defineProps({
    categorias: Array,
    proveedores: Array,
    cuentasBancarias: Array,
    proyectos: Array,
    tecnicos: Array,
    balances: Object,
});

const { colors, cssVars, headerGradientStyle, focusRingStyle, primaryButtonStyle } = useCompanyColors();

const localBalances = ref({ ...props.balances, fondos_especiales: [] });
const loadingBalances = ref(false);

const proveedorSeleccionado = ref(null);

const onProveedorSeleccionado = (proveedor) => {
    if (proveedor) {
        proveedorSeleccionado.value = proveedor;
        form.proveedor_id = proveedor.id;
    } else {
        proveedorSeleccionado.value = null;
        form.proveedor_id = '';
    }
};

const form = useForm({
    categoria_gasto_id: '',
    proveedor_id: '',
    monto: '',
    descripcion: '',
    fecha: (() => {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    })(),
    metodo_pago: 'efectivo',
    user_id: usePage().props?.auth?.user?.id || '',
    cuenta_bancaria_id: '',
    proyecto_id: '',
    notas: '',
    // Campos CFDI (para cuando se importa desde XML)
    cfdi_uuid: '',
    cfdi_folio: '',
    cfdi_serie: '',
    cfdi_tipo_comprobante: '',
    cfdi_forma_pago: '',
    cfdi_metodo_pago: '',
    cfdi_uso: '',
    cfdi_fecha: '',
    cfdi_emisor_rfc: '',
    cfdi_emisor_nombre: '',
    // Montos del CFDI
    subtotal_cfdi: '',
    iva_cfdi: '',
    descuento_cfdi: '',
    comprobante: null,
    comprobante_preview: null
});

const metodosPago = [
    { value: 'efectivo', label: 'Efectivo', description: 'Uso de dinero cobrado' },
    { value: 'caja_chica', label: 'Caja Chica', description: 'Fondo de gastos' },
    { value: 'transferencia', label: 'Transferencia', description: 'Pago desde banco' },
    { value: 'tarjeta', label: 'Tarjeta', description: 'Tarjeta asignada' },
];

const { formatCurrency } = useFormatters();

const submit = () => {
    // Validaciones preventivas para feedback inmediato
    if (!form.proveedor_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo Requerido',
            text: 'Por favor, selecciona un proveedor para el gasto.',
            confirmButtonColor: colors.value.principal,
            customClass: {
                popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
            }
        });
        return;
    }
    if (!form.categoria_gasto_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo Requerido',
            text: 'Por favor, selecciona una categoría de gasto.',
            confirmButtonColor: colors.value.principal,
            customClass: {
                popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
            }
        });
        return;
    }
    if (!form.descripcion) {
        Swal.fire({
            icon: 'warning',
            title: 'Campo Requerido',
            text: 'Por favor, ingresa una descripción para el gasto.',
            confirmButtonColor: colors.value.principal,
            customClass: {
                popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
            }
        });
        return;
    }
    if (!form.monto || parseFloat(form.monto) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Monto Inválido',
            text: 'El monto del gasto debe ser mayor a cero.',
            confirmButtonColor: colors.value.principal,
            customClass: {
                popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
            }
        });
        return;
    }

    // Validación de fondos disponibles según el método de pago
    if (form.metodo_pago === 'efectivo') {
        let disponible = parseFloat(localBalances.value.efectivo_cobrado || 0);
        let nombreFondo = 'Efectivo Cobrado';

        // Si seleccionó una caja fuerte / cuenta bancaria especial
        if (form.cuenta_bancaria_id) {
            if (form.cuenta_bancaria_id === localBalances.value.cuenta_bancaria_id) {
                disponible = parseFloat(localBalances.value.cuenta_asignada_saldo || 0);
                nombreFondo = localBalances.value.cuenta_asignada_nombre;
            } else {
                const fondoEspecial = (localBalances.value.fondos_especiales || []).find(f => f.id === form.cuenta_bancaria_id);
                if (fondoEspecial) {
                    disponible = parseFloat(fondoEspecial.saldo || 0);
                    nombreFondo = fondoEspecial.nombre;
                }
            }
        }

        if (parseFloat(form.monto) > disponible) {
            Swal.fire({
                icon: 'error',
                title: 'Fondos Insuficientes',
                html: `No tienes suficiente dinero en <b>${nombreFondo}</b>.<br><br>Saldo actual: <b>${formatCurrency(disponible)}</b>`,
            confirmButtonColor: colors.value.principal,
                customClass: {
                    popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                    confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
                }
            });
            return;
        }
    }

    if (form.metodo_pago === 'caja_chica') {
        const disponible = parseFloat(localBalances.value.caja_chica || 0);
        if (parseFloat(form.monto) > disponible) {
            Swal.fire({
                icon: 'error',
                title: 'Fondos Insuficientes',
                html: `Saldo de Caja Chica insuficiente.<br><br>Saldo actual: <b>${formatCurrency(disponible)}</b>`,
            confirmButtonColor: colors.value.principal,
                customClass: {
                    popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                    confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
                }
            });
            return;
        }
    }

    console.log('Enviando formulario de gasto...', form.data());

    Swal.fire({
        title: 'Guardando...',
        text: 'Por favor espera mientras procesamos el gasto.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
        customClass: {
            popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
        }
    });

    form.post(route('gastos.store'), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('✅ Gasto guardado exitosamente');
            Swal.fire({
                icon: 'success',
                title: '¡Gasto Registrado!',
                text: 'El gasto se ha guardado correctamente.',
                timer: 2000,
                showConfirmButton: false,
                customClass: {
                    popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                }
            });
        },
        onError: (errors) => {
            console.error('❌ Error al guardar gasto:', errors);
            const firstError = Object.values(errors)[0];
            Swal.fire({
                icon: 'error',
                title: 'Error al Guardar',
                text: firstError || 'Ocurrió un problema al procesar el gasto. Revisa los logs o intenta de nuevo.',
            confirmButtonColor: colors.value.principal,
                customClass: {
                    popup: 'rounded-2xl border-2 border-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:text-white',
                    confirmButton: 'rounded-xl font-bold uppercase tracking-wide text-xs px-6 py-3',
                }
            });
        }
    });
};

// Estado para indicar que se importó desde XML
const importadoDesdeXml = ref(false);
const folioXml = ref('');

// Cargar datos desde XML al montar el componente
onMounted(() => {
    // Establecer usuario actual por defecto
    form.user_id = usePage().props?.auth?.user?.id || '';
    
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from_xml') === '1') {
        cargarDatosDesdeXml();
    }
});

// Watcher para el responsable del gasto
watch(() => form.user_id, async (newUserId) => {
    if (!newUserId) return;
    
    loadingBalances.value = true;
    try {
        const response = await axios.get(route('gastos.balances', newUserId));
        localBalances.value = response.data;
        
        // Si tiene tarjeta asignada y el método es tarjeta, seleccionarla
        if (response.data.cuenta_bancaria_id && form.metodo_pago === 'tarjeta') {
            form.cuenta_bancaria_id = response.data.cuenta_bancaria_id;
        }
    } catch (error) {
        console.error('Error fetching user balances:', error);
    } finally {
        loadingBalances.value = false;
    }
});

// Watcher para el método de pago
watch(() => form.metodo_pago, (newMetodo) => {
    if (newMetodo === 'tarjeta' && localBalances.value.cuenta_bancaria_id) {
        form.cuenta_bancaria_id = localBalances.value.cuenta_bancaria_id;
    }
});

const cargarDatosDesdeXml = () => {
    try {
        const cfdiDataStr = sessionStorage.getItem('cfdi_gasto_import_data');
        if (!cfdiDataStr) {
            console.warn('No se encontraron datos de XML en sessionStorage');
            return;
        }

        const cfdiData = JSON.parse(cfdiDataStr);
        console.log('Datos del XML cargados:', cfdiData);

        // Marcar como importado
        importadoDesdeXml.value = true;
        folioXml.value = (cfdiData.serie || '') + (cfdiData.folio || '');

        // Pre-llenar formulario básico
        form.monto = cfdiData.total || '';
        form.descripcion = cfdiData.descripcion_final || cfdiData.descripcion_sugerida || '';
        
        // Fecha del CFDI
        if (cfdiData.fecha) {
            const fecha = new Date(cfdiData.fecha);
            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            form.fecha = `${year}-${month}-${day}`;
        }

        // Notas con referencia al XML
        form.notas = `Importado desde CFDI: ${folioXml.value}`;

        // *** LLENAR CAMPOS CFDI ***
        form.cfdi_uuid = cfdiData.timbre?.uuid || '';
        form.cfdi_folio = cfdiData.folio || '';
        form.cfdi_serie = cfdiData.serie || '';
        form.cfdi_tipo_comprobante = cfdiData.tipo_comprobante || '';
        form.cfdi_forma_pago = cfdiData.forma_pago || '';
        form.cfdi_metodo_pago = cfdiData.metodo_pago || '';
        form.cfdi_uso = cfdiData.receptor?.uso_cfdi || '';
        form.cfdi_fecha = cfdiData.fecha || '';
        form.cfdi_emisor_rfc = cfdiData.emisor?.rfc || '';
        form.cfdi_emisor_nombre = cfdiData.emisor?.nombre || '';
        
        // Mapear forma de pago del SAT al método de pago del sistema
        const mapeoFormaPago = {
            '01': 'efectivo',       // Efectivo
            '02': 'cheque',         // Cheque nominativo
            '03': 'transferencia',  // Transferencia electrónica
            '04': 'tarjeta',        // Tarjeta de crédito
            '28': 'tarjeta',        // Tarjeta de débito
        };
        if (cfdiData.forma_pago && mapeoFormaPago[cfdiData.forma_pago]) {
            form.metodo_pago = mapeoFormaPago[cfdiData.forma_pago];
        }
        
        // Montos del CFDI para cálculo exacto
        form.subtotal_cfdi = cfdiData.subtotal || '';
        form.iva_cfdi = cfdiData.impuestos?.total_impuestos_trasladados || '';
        form.descuento_cfdi = cfdiData.descuento || 0;

        // Pre-seleccionar proveedor
        // Prioridad: proveedor_completo > proveedor_encontrado > proveedor_id
        let proveedorParaSeleccionar = null;

        if (cfdiData.proveedor_completo) {
            // Usar datos completos del proveedor (incluye proveedores recién creados)
            proveedorParaSeleccionar = {
                id: cfdiData.proveedor_completo.id,
                nombre_razon_social: cfdiData.proveedor_completo.nombre_razon_social || cfdiData.proveedor_completo.nombre,
                rfc: cfdiData.proveedor_completo.rfc,
            };
        } else if (cfdiData.proveedor_encontrado) {
            proveedorParaSeleccionar = {
                id: cfdiData.proveedor_encontrado.id,
                nombre_razon_social: cfdiData.proveedor_encontrado.nombre || cfdiData.proveedor_encontrado.nombre_razon_social,
                rfc: cfdiData.proveedor_encontrado.rfc,
            };
        }

        if (proveedorParaSeleccionar) {
            // Buscar en la lista existente
            let proveedorEnLista = props.proveedores.find(
                p => p.id === proveedorParaSeleccionar.id
            );
            
            if (!proveedorEnLista) {
                // Si no está en la lista (proveedor recién creado), usar los datos directamente
                proveedorEnLista = proveedorParaSeleccionar;
                console.log('Proveedor creado recientemente, usando datos del XML:', proveedorEnLista);
            }
            
            proveedorSeleccionado.value = proveedorEnLista;
            form.proveedor_id = proveedorEnLista.id;
            console.log('Proveedor pre-seleccionado:', proveedorEnLista);
        }

        // Limpiar sessionStorage después de cargar
        sessionStorage.removeItem('cfdi_gasto_import_data');
    } catch (error) {
        console.error('Error al cargar datos del XML:', error);
    }
};
// Lógica inteligente: Detectar cuenta bancaria según el responsable
watch(() => form.user_id, (newUserId) => {
    if (newUserId) {
        // Buscar si este usuario tiene una tarjeta/cuenta asignada
        const cuentaAsignada = props.cuentasBancarias.find(c => c.responsable_id === newUserId);
        
        if (cuentaAsignada) {
            form.cuenta_bancaria_id = cuentaAsignada.id;
            form.metodo_pago = 'tarjeta';
        } else {
            // Si no tiene cuenta asignada, lo más probable es que sea efectivo (Caja Chica)
            form.cuenta_bancaria_id = '';
            form.metodo_pago = 'efectivo';
        }
    }
});

// Cuando cambia el método de pago, si es tarjeta y no hay cuenta, intentar buscar la del responsable
watch(() => form.metodo_pago, (newMetodo) => {
    if (newMetodo === 'tarjeta' && !form.cuenta_bancaria_id && form.user_id) {
        const cuentaAsignada = props.cuentasBancarias.find(c => c.responsable_id === form.user_id);
        if (cuentaAsignada) form.cuenta_bancaria_id = cuentaAsignada.id;
    }
    
    if (newMetodo === 'efectivo' || newMetodo === 'caja_chica') {
        form.cuenta_bancaria_id = '';
    }
});
const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.comprobante = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            form.comprobante_preview = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeFile = () => {
    form.comprobante = null;
    form.comprobante_preview = null;
};
</script>

<template>
    <AppLayout title="Registrar Gasto">
        <Head title="Registrar Gasto" />

        <template #header>
            <div class="rounded-xl border border-slate-200/60 overflow-hidden" :style="cssVars">
                <div class="px-6 py-6 text-white" :style="headerGradientStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="headerGradientStyle">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight">Registrar Nuevo Gasto</h2>
                                <p class="text-sm text-white/90 mt-0.5">Carga un gasto operativo con categoria y proveedor</p>
                            </div>
                        </div>
                        <Link :href="route('gastos.index')"
                            class="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-wide rounded-xl bg-white/10 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-transparent transition">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver
                        </Link>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6" :style="cssVars">
            <div class="w-full sm:px-6 lg:px-8">
                <!-- Alerta de importación desde XML -->
                <div v-if="importadoDesdeXml" class="mb-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-xl p-4">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-emerald-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">Datos importados desde XML (CFDI)</p>
                            <p class="text-xs text-emerald-800 dark:text-emerald-200">Folio: {{ folioXml }} - Verifica los datos y selecciona una categoría</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="bg-white shadow rounded-xl p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Categoría <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="form.categoria_gasto_id"
                                class="w-full border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                :class="{ 'border-rose-500': form.errors.categoria_gasto_id }">
                                <option value="">Seleccionar categoría</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                            <p v-if="form.errors.categoria_gasto_id" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.categoria_gasto_id }}
                            </p>
                        </div>

                        <!-- Monto -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Monto <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-slate-500">$</span>
                                <input type="number" v-model="form.monto" step="0.01" min="0.01"
                                    class="w-full pl-8 border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                    :style="focusRingStyle"
                                    :class="{ 'border-rose-500': form.errors.monto }"
                                    placeholder="0.00" />
                            </div>
                            <p v-if="form.errors.monto" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.monto }}
                            </p>
                        </div>

                        <!-- Proveedor -->
                        <div class="md:col-span-2">
                             <BuscarProveedor
                                :proveedores="props.proveedores"
                                :proveedor-seleccionado="proveedorSeleccionado"
                                label-busqueda="Proveedor"
                                placeholder-busqueda="Buscar proveedor..."
                                @proveedor-seleccionado="onProveedorSeleccionado"
                            />
                            <!-- Hidden input for form binding consistency checks if needed -->
                            <input type="hidden" v-model="form.proveedor_id">
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Fecha
                            </label>
                            <input type="date" v-model="form.fecha"
                                class="w-full border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            />
                        </div>

                        <!-- Responsable del Gasto -->
                        <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-2 border-slate-100 dark:border-slate-800">
                            <label class="block text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">Responsable del Gasto *</label>
                            <select v-model="form.user_id" class="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition-all dark:text-slate-200">
                                <option value="">Seleccionar responsable</option>
                                <option :value="$page.props.auth.user.id">Yo mismo ({{ $page.props.auth.user.name }})</option>
                                <option v-for="user in tecnicos" :key="user.id" :value="user.id">{{ user.name }}</option>
                            </select>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 font-medium">El sistema detectará automáticamente si este usuario tiene una tarjeta asignada.</p>
                        </div>

                        <!-- Método de Pago -->
                        <div class="mt-4 md:col-span-2">
                            <label class="block text-sm font-black text-slate-500 uppercase tracking-wide mb-3">Método de Pago *</label>
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                                <button 
                                    v-for="metodo in metodosPago" 
                                    :key="metodo.value"
                                    type="button"
                                    @click="form.metodo_pago = metodo.value"
                                    class="relative p-4 rounded-2xl border-2 text-xs font-bold uppercase transition-all flex flex-col items-center justify-center gap-2 group overflow-hidden"
                                    :class="form.metodo_pago === metodo.value 
                                        ? 'border-brand-500 bg-brand-500/10 text-brand-700 dark:text-brand-400 dark:border-brand-400 dark:bg-brand-400/20 shadow-sm' 
                                        : 'border-slate-100 bg-slate-50/50 text-slate-400 dark:border-slate-800 dark:bg-slate-900/50 dark:text-slate-500 hover:border-slate-300 dark:hover:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900'"
                                >
                                    <!-- Balance badge for Cash and Caja Chica -->
                                    <div v-if="(metodo.value === 'efectivo' || metodo.value === 'caja_chica') && form.user_id" 
                                         class="absolute top-1 right-1 px-1.5 py-0.5 rounded-lg text-[10px] font-black z-10"
                                         :class="form.metodo_pago === metodo.value 
                                            ? 'bg-brand-500 text-white shadow-lg' 
                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-300 dark:border-slate-600'"
                                    >
                                        {{ formatCurrency(metodo.value === 'efectivo' ? localBalances.efectivo_cobrado : localBalances.caja_chica) }}
                                    </div>

                                    <div class="p-2 rounded-xl" :class="form.metodo_pago === metodo.value ? 'bg-brand-500 text-white' : 'bg-white dark:bg-slate-800 shadow-sm group-hover:scale-110 transition-transform'">
                                        <svg v-if="metodo.value === 'efectivo'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        <svg v-if="metodo.value === 'tarjeta'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                        <svg v-if="metodo.value === 'transferencia'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                        <svg v-if="metodo.value === 'caja_chica'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <span class="block dark:text-slate-300">{{ metodo.label }}</span>
                                    <span class="text-[9px] lowercase font-medium opacity-60 dark:text-slate-400">{{ metodo.description }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Info Contextual de Pago -->
                        <div v-if="form.metodo_pago === 'efectivo' && form.user_id" class="md:col-span-2 space-y-3">
                            <!-- Balance de Cobros -->
                            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/50 p-4 rounded-2xl flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-amber-900 dark:text-amber-400">Monto de Cobros Pendientes</h4>
                                    <p class="text-[11px] text-amber-800 dark:text-amber-500 mt-1">
                                        Esta persona tiene <b class="text-lg">{{ formatCurrency(localBalances.efectivo_cobrado) }}</b> acumulados de sus cobros a clientes.
                                    </p>
                                </div>
                            </div>

                            <!-- Balance de Cuenta Asignada (Ej. Opata) -->
                            <div v-if="localBalances.cuenta_asignada_nombre" 
                                 class="bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50 p-4 rounded-2xl flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <h4 class="text-sm font-bold text-blue-900 dark:text-blue-400">Fondo: {{ localBalances.cuenta_asignada_nombre }}</h4>
                                        <button v-if="form.cuenta_bancaria_id !== localBalances.cuenta_bancaria_id"
                                                @click="form.cuenta_bancaria_id = localBalances.cuenta_bancaria_id"
                                                type="button"
                                                class="text-[10px] bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-lg font-black uppercase">
                                            Usar este fondo
                                        </button>
                                        <span v-else class="text-[10px] bg-blue-500 text-white px-2 py-1 rounded-lg font-black uppercase">Seleccionado</span>
                                    </div>
                                    <p class="text-[11px] text-blue-800 dark:text-blue-500 mt-1">
                                        Saldo disponible en esta caja: <b class="text-lg">{{ formatCurrency(localBalances.cuenta_asignada_saldo) }}</b>.
                                    </p>
                                </div>
                            </div>

                            <!-- Otros Fondos Especiales (Solo para Admin) -->
                            <template v-if="localBalances.fondos_especiales && localBalances.fondos_especiales.length > 0">
                                <div v-for="fondo in localBalances.fondos_especiales.filter(f => f.id !== localBalances.cuenta_bancaria_id)" 
                                     :key="fondo.id"
                                     class="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 p-4 rounded-2xl flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-400 text-white flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Fondo: {{ fondo.nombre }}</h4>
                                            <button v-if="form.cuenta_bancaria_id !== fondo.id"
                                                    @click="form.cuenta_bancaria_id = fondo.id"
                                                    type="button"
                                                    class="text-[10px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-400 px-2 py-1 rounded-lg font-black uppercase">
                                                Usar este fondo
                                            </button>
                                            <span v-else class="text-[10px] bg-brand-500 text-white px-2 py-1 rounded-lg font-black uppercase">Seleccionado</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                            Saldo disponible (Vista Admin): <b class="text-lg">{{ formatCurrency(fondo.saldo) }}</b>.
                                        </p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div v-if="form.metodo_pago === 'caja_chica' && form.user_id" class="md:col-span-2 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/50 p-4 rounded-2xl flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-400">Saldo en Caja Chica</h4>
                                <p class="text-[11px] text-emerald-800 dark:text-emerald-500 mt-1">
                                    Su fondo actual de Caja Chica es de <b class="text-lg">{{ formatCurrency(localBalances.caja_chica) }}</b>.
                                </p>
                            </div>
                        </div>

                        <!-- Cuenta Bancaria -->
                        <div v-if="form.metodo_pago === 'tarjeta' || form.metodo_pago === 'transferencia' || (form.metodo_pago === 'efectivo' && form.cuenta_bancaria_id)" class="md:col-span-2 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border-2 border-slate-100 dark:border-slate-800">
                            <label class="block text-sm font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2">
                                {{ form.metodo_pago === 'tarjeta' ? 'Tarjeta Responsable' : (form.metodo_pago === 'efectivo' ? 'Fondo de Efectivo (Caja Fuerte)' : 'Cuenta de Transferencia') }}
                            </label>
                            <select v-model="form.cuenta_bancaria_id"
                                class="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 rounded-xl shadow-sm focus:ring-2 focus:border-transparent p-3 text-sm font-bold dark:text-slate-200"
                                :style="focusRingStyle"
                            >
                                <option value="">Seleccionar cuenta...</option>
                                <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                                    {{ cuenta.label }} - {{ formatCurrency(cuenta.saldo_actual) }}
                                </option>
                            </select>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2 font-medium">
                                {{ form.metodo_pago === 'tarjeta' ? 'El sistema ha pre-seleccionado la tarjeta a cargo del responsable.' : 'Selecciona la cuenta bancaria de donde se transfirió el dinero.' }}
                            </p>
                        </div>

                        <!-- Proyecto (opcional) -->
                        <div v-if="proyectos.length > 0">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Proyecto (opcional)
                            </label>
                            <select v-model="form.proyecto_id"
                                class="w-full border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Sin proyecto</option>
                                <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
                                    {{ proyecto.nombre }}
                                </option>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Asocia este gasto a un proyecto para rastrear costos</p>
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Descripción <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" v-model="form.descripcion"
                                class="w-full border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                :class="{ 'border-rose-500': form.errors.descripcion }"
                                placeholder="Descripción del gasto..." />
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-rose-600">
                                {{ form.errors.descripcion }}
                            </p>
                        </div>

                        <!-- Evidencia Fotográfica (NUEVO) -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2 uppercase tracking-wide">
                                Evidencia del Gasto (Ticket / Factura)
                            </label>
                            <div class="relative group">
                                <div v-if="!form.comprobante_preview" 
                                     class="border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-[2rem] p-8 transition-all hover:border-brand-500 hover:bg-brand-500/5 flex flex-col items-center justify-center gap-4 cursor-pointer overflow-hidden relative"
                                     @click="$refs.fileInput.click()">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-brand-500 group-hover:scale-110 transition-all">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Toca para tomar foto o seleccionar archivo</p>
                                        <p class="text-xs text-slate-500 mt-1">JPG, PNG o WEBP (Máx. 10MB)</p>
                                    </div>
                                    <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileUpload" />
                                </div>
                                <div v-else class="relative rounded-[2rem] overflow-hidden border-2 border-brand-500 shadow-xl shadow-brand-500/10 group">
                                    <img :src="form.comprobante_preview" class="w-full h-64 object-cover" />
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-4">
                                        <button type="button" @click="$refs.fileInput.click()" class="p-3 rounded-xl bg-white text-brand-600 font-bold shadow-lg hover:scale-110 transition-transform">Cambiar</button>
                                        <button type="button" @click="removeFile" class="p-3 rounded-xl bg-rose-500 text-white font-bold shadow-lg hover:scale-110 transition-transform">Eliminar</button>
                                    </div>
                                    <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileUpload" />
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Notas adicionales
                            </label>
                            <textarea v-model="form.notas" rows="3"
                                class="w-full border-slate-300 rounded-xl shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                placeholder="Información adicional..."></textarea>
                        </div>
                    </div>

                    <!-- Preview del monto -->
                    <div v-if="form.monto" class="mt-6 p-4 bg-white rounded-xl">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Total del gasto:</span>
                            <span class="text-2xl font-bold text-slate-900">{{ formatCurrency(form.monto) }}</span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">El IVA se calcula automáticamente</p>
                    </div>

                    <!-- Botones -->
                    <div class="mt-6 flex justify-end gap-3">
                        <Link :href="route('gastos.index')"
                            class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition">
                            Cancelar
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 text-white rounded-xl transition disabled:opacity-50 hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :style="primaryButtonStyle">
                            {{ form.processing ? 'Guardando...' : 'Registrar Gasto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
