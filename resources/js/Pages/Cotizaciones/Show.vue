<!-- resources/js/Pages/Cotizaciones/Show.vue -->
<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

// Colores de empresa
const { colors } = useCompanyColors();

// Ahora puedes usar ref en tu componente
const myRef = ref(null);

defineProps({
    cotizacion: Object,
    canConvert: {
        type: Boolean,
        default: true
    },
    canEdit: {
        type: Boolean,
        default: true
    },
    canDelete: {
        type: Boolean,
        default: true
    },
    ivaPorcentaje: {
        type: Number,
        default: 16
    },
    isrPorcentaje: {
        type: Number,
        default: 1.25
    }
});

// Totales calculados desde los items
const subtotal = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.cantidad * item.precio), 0);
});

const descuentoItems = computed(() => {
    return items.value.reduce((sum, item) => sum + (item.cantidad * item.precio * item.descuento / 100), 0);
});

const descuentoGeneral = computed(() => cotizacion.descuento_general || 0);

const subtotalConDescuentos = computed(() => {
    return subtotal.value - descuentoItems.value - descuentoGeneral.value;
});

const iva = computed(() => subtotalConDescuentos.value * (ivaPorcentaje / 100));

// ISR del cotizacion (viene calculado del backend)
const isr = computed(() => cotizacion.isr || 0);

// Total: subtotal + iva - isr (ISR se retiene)
const total = computed(() => subtotalConDescuentos.value + iva.value - isr.value);

// Items
const items = computed(() => {
    return cotizacion.productos.map(item => ({
        id: item.id,
        nombre: item.nombre,
        tipo: item.tipo,
        cantidad: item.pivot?.cantidad || 1,
        precio: item.pivot?.precio || 0,
        descuento: item.pivot?.descuento || 0
    }));
});

// Estado
const mostrarVistaPrevia = ref(false);
</script>

<template>
    <Head title="Ver Cotización" />
    <AppLayout>
        <div class="cotizaciones-show min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
            <div class="w-full">
                <!-- Encabezado -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                    <div class="px-6 py-4 text-white" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                        <h1 class="text-xl font-bold">Cotización #{{ cotizacion.numero_cotizacion || cotizacion.id }}</h1>
                        <p class="text-sm opacity-90 mt-1">{{ cotizacion.fecha_cotizacion ? new Date(cotizacion.fecha_cotizacion).toLocaleDateString('es-MX') : '' }}</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p><strong>Cliente:</strong> {{ cotizacion.cliente.nombre_razon_social }}</p>
                                <p v-if="cotizacion.cliente.email"><strong>Email:</strong> {{ cotizacion.cliente.email }}</p>
                            </div>
                            <div>
                                <p>
                                    <strong>Estado:</strong>
                                    <span class="ml-2 px-3 py-1 rounded-full text-sm font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800': cotizacion.estado === 'aprobada',
                                              'bg-yellow-100 text-yellow-800': cotizacion.estado === 'pendiente',
                                              'bg-red-100 text-red-800': cotizacion.estado === 'rechazada',
                                              'bg-gray-100 text-gray-800': cotizacion.estado === 'borrador'
                                          }">
                                        {{ cotizacion.estado }}
                                    </span>
                                </p>
                                <p><strong>Total:</strong> ${{ Number(total).toFixed(2) }}</p>
                            </div>
                        </div>
                        <p v-if="cotizacion.notas" class="mt-2">
                            <strong>Notas:</strong> {{ cotizacion.notas }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de ítems -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4">Productos y Servicios</h2>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Nombre</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tipo</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Cantidad</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Precio</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Descuento</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-gray-500">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="item in items" :key="item.id">
                                    <td class="px-4 py-3 text-sm">{{ item.nombre }}</td>
                                    <td class="px-4 py-3 text-sm capitalize">{{ item.tipo }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3 text-sm text-right">${{ Number(item.precio).toFixed(2) }}</td>
                                    <td class="px-4 py-3 text-sm text-right">{{ item.descuento }}%</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        ${{ Number(item.cantidad * item.precio * (1 - item.descuento / 100)).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="space-y-2 text-right">
                            <p><strong>Subtotal:</strong> ${{ Number(subtotal).toFixed(2) }}</p>
                            <p v-if="descuentoItems > 0"><strong>Descuentos por ítem:</strong> ${{ Number(descuentoItems).toFixed(2) }}</p>
                            <p v-if="descuentoGeneral > 0"><strong>Descuento general:</strong> ${{ Number(descuentoGeneral).toFixed(2) }}</p>
                            <p><strong>Subtotal con descuentos:</strong> ${{ Number(subtotalConDescuentos).toFixed(2) }}</p>
                            <p><strong>IVA ({{ ivaPorcentaje }}%):</strong> ${{ Number(iva).toFixed(2) }}</p>
                            <p v-if="isr > 0" class="text-orange-600"><strong>Retención ISR ({{ isrPorcentaje }}%):</strong> -${{ Number(isr).toFixed(2) }}</p>
                            <p class="text-xl"><strong>Total:</strong> ${{ Number(total).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-6 flex flex-wrap gap-3">
                        <button
                            v-if="canConvert"
                            @click="mostrarVistaPrevia = true"
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Convertir a Pedido
                        </button>

                        <Link
                            v-if="canEdit"
                            :href="route('cotizaciones.edit', cotizacion.id)"
                            class="text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                            :style="{ backgroundColor: colors.principal }"
                        >
                            Editar
                        </Link>

                        <Link
                            v-if="canDelete"
                            :href="route('cotizaciones.destroy', cotizacion.id)"
                            method="delete"
                            as="button"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Eliminar
                        </Link>

                        <Link
                            :href="route('cotizaciones.pdf', cotizacion.id)"
                            target="_blank"
                            class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            📄 PDF
                        </Link>

                        <Link
                            :href="route('cotizaciones.ticket', cotizacion.id)"
                            target="_blank"
                            class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            🖨️ Ticket
                        </Link>

                        <button
                            @click="mostrarVistaPrevia = true"
                            class="bg-white0 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        >
                            Vista Previa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Vista Previa -->
        <VistaPreviaModal
            :show="mostrarVistaPrevia"
            type="cotizacion"
            :cliente="cotizacion.cliente"
            :items="items"
            :totals="{
                subtotal: subtotal,
                descuentoItems: descuentoItems,
                descuentoGeneral: descuentoGeneral,
                subtotalConDescuentos: subtotalConDescuentos,
                iva: iva,
                total: total
            }"
            :descuento-general="descuentoGeneral"
            :notas="cotizacion.notas"
            @close="mostrarVistaPrevia = false"
            @print="() => window.print()"
        />
    </AppLayout>
</template>

