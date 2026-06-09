<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const props = defineProps({
    gasto: Object,
    categorias: Array,
    proveedores: Array,
});

const { colors, cssVars, headerGradientStyle, focusRingStyle, primaryButtonStyle } = useCompanyColors();

const proveedorSeleccionado = ref(null);

// Inicializar proveedor seleccionado si existe
onMounted(() => {
    if (props.gasto.proveedor_id) {
        proveedorSeleccionado.value = props.proveedores.find(p => p.id === props.gasto.proveedor_id) || null;
    }
});

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
    categoria_gasto_id: props.gasto.categoria_gasto_id,
    proveedor_id: props.gasto.proveedor_id,
    monto: props.gasto.total, // Usamos 'total' porque el monto original
    descripcion: extractDescription(props.gasto.notas),
    fecha: props.gasto.fecha_compra ? props.gasto.fecha_compra.split('T')[0] : '',
    metodo_pago: props.gasto.metodo_pago,
    notas: extractNotes(props.gasto.notas),
});

// Helper para separar descripción de notas
function extractDescription(notas) {
    if (!notas) return '';
    const parts = notas.split('\n\n');
    return parts[0] || '';
}

function extractNotes(notas) {
    if (!notas) return '';
    const parts = notas.split('\n\n');
    return parts.length > 1 ? parts.slice(1).join('\n\n') : '';
}

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
    form.put(route('gastos.update', props.gasto.id));
};
</script>

<template>
    <AppLayout title="Editar Gasto">
        <Head title="Editar Gasto" />

        <template #header>
            <div class="rounded-xl border border-gray-200/60 overflow-hidden" :style="cssVars">
                <div class="px-6 py-6 text-white" :style="headerGradientStyle">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md" :style="headerGradientStyle">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold tracking-tight">Editar Gasto {{ gasto.numero_compra }}</h2>
                                <p class="text-sm text-white/90 mt-0.5">Actualiza el gasto y mantiene el historial</p>
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
                            {{ form.processing ? 'Guardando...' : 'Actualizar Gasto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
