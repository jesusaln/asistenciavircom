<!-- resources/js/Pages/Cotizaciones/Show.vue -->
<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
        <div class="cotizaciones-show min-h-screen bg-[var(--ui-surface)] p-6">
            <div class="w-full">
                <!-- Encabezado -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                    <div class="px-6 py-4 text-white" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                        <h1 class="text-xl font-bold">Cotización #{{ cotizacion.numero_cotizacion || cotizacion.id }}</h1>
                        <p class="text-sm opacity-90 mt-1">{{ cotizacion.fecha_cotizacion ? new Date(cotizacion.fecha_cotizacion).toLocaleDateString('es-MX') : '' }}</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-slate-700 dark:text-slate-200"><strong>Cliente:</strong> <span class="text-slate-900 dark:text-slate-100">{{ cotizacion.cliente.nombre_razon_social }}</span></p>
                                <p v-if="cotizacion.cliente.email" class="text-slate-700 dark:text-slate-200"><strong>Email:</strong> <span class="text-slate-900 dark:text-slate-100">{{ cotizacion.cliente.email }}</span></p>
                            </div>
                            <div>
                                <p class="text-slate-700 dark:text-slate-200">
                                    <strong>Estado:</strong>
                                    <span class="ml-2 px-3 py-1 rounded-full text-sm font-medium"
                                          :class="{
                                              'bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300': cotizacion.estado === 'aprobada',
                                              'bg-brand-50 dark:bg-brand-900/20/20 text-brand-800 dark:text-brand-200 dark:text-amber-300': cotizacion.estado === 'pendiente',
                                              'bg-rose-50 dark:bg-rose-900/20/20 text-rose-800 dark:text-rose-200 dark:text-rose-300': cotizacion.estado === 'rechazada',
                                              'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200': cotizacion.estado === 'borrador'
                                          }">
                                        {{ cotizacion.estado }}
                                    </span>
                                </p>
                                <p class="text-xl text-slate-900 dark:text-slate-100"><strong>Total:</strong> ${{ Number(total).toFixed(2) }}</p>
                            </div>
                        </div>
                        <p v-if="cotizacion.notas" class="mt-2 text-slate-500 dark:text-slate-200">
                            <strong>Notas:</strong> {{ cotizacion.notas }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de ítems -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4">Productos y Servicios</h2>
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Nombre</th>
                                    <th class="px-4 py-2 text-left text-sm font-medium text-slate-500 dark:text-slate-400">Tipo</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Cantidad</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Precio</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Descuento</th>
                                    <th class="px-4 py-2 text-right text-sm font-medium text-slate-500 dark:text-slate-400">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="item in items" :key="item.id">
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.nombre }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 capitalize">{{ item.tipo }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">{{ item.cantidad }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">${{ Number(item.precio).toFixed(2) }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">{{ item.descuento }}%</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200 text-right">
                                        ${{ Number(item.cantidad * item.precio * (1 - item.descuento / 100)).toFixed(2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="space-y-2 text-right">
                            <p class="text-slate-700 dark:text-slate-200"><strong>Subtotal:</strong> <span class="text-slate-900 dark:text-slate-100">${{ Number(subtotal).toFixed(2) }}</span></p>
                            <p v-if="descuentoItems > 0" class="text-slate-700 dark:text-slate-200"><strong>Descuentos por ítem:</strong> <span class="text-slate-900 dark:text-slate-100">${{ Number(descuentoItems).toFixed(2) }}</span></p>
                            <p v-if="descuentoGeneral > 0" class="text-slate-700 dark:text-slate-200"><strong>Descuento general:</strong> <span class="text-slate-900 dark:text-slate-100">${{ Number(descuentoGeneral).toFixed(2) }}</span></p>
                            <p class="text-slate-700 dark:text-slate-200"><strong>Subtotal con descuentos:</strong> <span class="text-slate-900 dark:text-slate-100">${{ Number(subtotalConDescuentos).toFixed(2) }}</span></p>
                            <p class="text-slate-700 dark:text-slate-200"><strong>IVA ({{ ivaPorcentaje }}%):</strong> <span class="text-slate-900 dark:text-slate-100">${{ Number(iva).toFixed(2) }}</span></p>
                            <p v-if="isr > 0" class="text-brand-600 dark:text-orange-400"><strong>Retención ISR ({{ isrPorcentaje }}%):</strong> -<span class="text-slate-900 dark:text-slate-100">${{ Number(isr).toFixed(2) }}</span></p>
                            <p class="text-xl text-slate-900 dark:text-slate-100"><strong>Total:</strong> <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">${{ Number(total).toFixed(2) }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="p-6 flex flex-wrap gap-3">
                        <button
                            v-if="canConvert"
                            @click="mostrarVistaPrevia = true"
                            class="bg-brand-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Convertir a Pedido
                        </button>

                        <Link
                            v-if="canEdit"
                            :href="route('cotizaciones.edit', cotizacion.id)"
                            class="text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                            :style="{ backgroundColor: colors.principal }"
                        >
                            Editar
                        </Link>

                        <Link
                            v-if="canDelete"
                            :href="route('cotizaciones.destroy', cotizacion.id)"
                            method="delete"
                            as="button"
                            class="bg-brand-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Eliminar
                        </Link>

                        <Link
                            :href="route('cotizaciones.pdf', cotizacion.id)"
                            target="_blank"
                            class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            📄 PDF
                        </Link>

                        <Link
                            :href="route('cotizaciones.ticket', cotizacion.id)"
                            target="_blank"
                            class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            🖨️ Ticket
                        </Link>

                        <button
                            @click="mostrarVistaPrevia = true"
                            class="bg-white dark:bg-slate-700 hover:bg-slate-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
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

