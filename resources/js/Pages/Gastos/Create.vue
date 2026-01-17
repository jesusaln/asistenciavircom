<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const props = defineProps({
    categorias: Array,
    proveedores: Array,
    cuentasBancarias: Array,
    proyectos: Array,
});

const { colors, cssVars, headerGradientStyle, focusRingStyle, primaryButtonStyle } = useCompanyColors();

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
});

const metodosPago = [
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'transferencia', label: 'Transferencia' },
    { value: 'tarjeta', label: 'Tarjeta' },
    { value: 'cheque', label: 'Cheque' },
];

const formatCurrency = (value) => {
    const num = parseFloat(value) || 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num);
};

const submit = () => {
    form.post(route('gastos.store'));
};

// Estado para indicar que se importó desde XML
const importadoDesdeXml = ref(false);
const folioXml = ref('');

// Cargar datos desde XML al montar el componente
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('from_xml') === '1') {
        cargarDatosDesdeXml();
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
</script>

<template>
    <AppLayout title="Registrar Gasto">
        <Head title="Registrar Gasto" />

        <template #header>
            <div class="rounded-xl border border-gray-200/60 overflow-hidden" :style="cssVars">
                <div class="px-6 py-6 text-white" :style="headerGradientStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="headerGradientStyle">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight">Registrar Nuevo Gasto</h2>
                                <p class="text-sm text-white/90 mt-0.5">Carga un gasto operativo con categoria y proveedor</p>
                            </div>
                        </div>
                        <Link :href="route('gastos.index')"
                            class="inline-flex items-center px-4 py-2 text-xs font-semibold uppercase tracking-widest rounded-lg bg-white/10 text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/70 focus:ring-offset-2 focus:ring-offset-transparent transition">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <div v-if="importadoDesdeXml" class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-emerald-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-emerald-800">Datos importados desde XML (CFDI)</p>
                            <p class="text-xs text-emerald-700">Folio: {{ folioXml }} - Verifica los datos y selecciona una categoría</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.categoria_gasto_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                :class="{ 'border-red-500': form.errors.categoria_gasto_id }">
                                <option value="">Seleccionar categoría</option>
                                <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                                    {{ cat.nombre }}
                                </option>
                            </select>
                            <p v-if="form.errors.categoria_gasto_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.categoria_gasto_id }}
                            </p>
                        </div>

                        <!-- Monto -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Monto <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">$</span>
                                <input type="number" v-model="form.monto" step="0.01" min="0.01"
                                    class="w-full pl-8 border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                    :style="focusRingStyle"
                                    :class="{ 'border-red-500': form.errors.monto }"
                                    placeholder="0.00" />
                            </div>
                            <p v-if="form.errors.monto" class="mt-1 text-sm text-red-600">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Fecha
                            </label>
                            <input type="date" v-model="form.fecha"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            />
                        </div>

                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Método de Pago <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.metodo_pago"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option v-for="metodo in metodosPago" :key="metodo.value" :value="metodo.value">
                                    {{ metodo.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Cuenta Bancaria -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Cuenta Bancaria (origen del gasto)
                            </label>
                            <select v-model="form.cuenta_bancaria_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Sin especificar</option>
                                <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                                    {{ cuenta.nombre }} - {{ cuenta.banco }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Selecciona de qué cuenta bancaria salió este gasto</p>
                        </div>

                        <!-- Proyecto (opcional) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Proyecto (opcional)
                            </label>
                            <select v-model="form.proyecto_id"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                            >
                                <option value="">Sin proyecto</option>
                                <option v-for="proyecto in proyectos" :key="proyecto.id" :value="proyecto.id">
                                    {{ proyecto.nombre }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Asocia este gasto a un proyecto para rastrear costos</p>
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.descripcion"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                :class="{ 'border-red-500': form.errors.descripcion }"
                                placeholder="Descripción del gasto..." />
                            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">
                                {{ form.errors.descripcion }}
                            </p>
                        </div>

                        <!-- Notas -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Notas adicionales
                            </label>
                            <textarea v-model="form.notas" rows="3"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:border-transparent"
                                :style="focusRingStyle"
                                placeholder="Información adicional..."></textarea>
                        </div>
                    </div>

                    <!-- Preview del monto -->
                    <div v-if="form.monto" class="mt-6 p-4 bg-white rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total del gasto:</span>
                            <span class="text-2xl font-bold text-gray-900">{{ formatCurrency(form.monto) }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">El IVA se calcula automáticamente</p>
                    </div>

                    <!-- Botones -->
                    <div class="mt-6 flex justify-end gap-3">
                        <Link :href="route('gastos.index')"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition">
                            Cancelar
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-4 py-2 text-white rounded-md transition disabled:opacity-50 hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :style="primaryButtonStyle">
                            {{ form.processing ? 'Guardando...' : 'Registrar Gasto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
