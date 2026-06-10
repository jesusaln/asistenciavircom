<template>
    <Head title="Ver Compra" />
    <AppLayout>
        <div class="compras-show min-h-screen bg-[var(--ui-surface)] dark:from-slate-950 dark:to-slate-900 p-6">
            <div class="w-full">
                <!-- Encabezado -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-6">
                    <div class="px-6 py-4 bg-gradient-to-r from-brand-500 to-brand-600 dark:from-brand-600 dark:to-brand-700 px-6 py-4">
                        <h1 class="text-xl font-bold">Compra #{{ compra.id }}</h1>
                    </div>
                    <div class="p-6 text-slate-900 dark:text-white">
                        <p class="text-slate-900 dark:text-white"><strong>Proveedor:</strong> {{ compra.proveedor.nombre_razon_social }}</p>
                        <p>
                            <strong>Estado:</strong>
                            <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium"
                                  :class="{
                                      'bg-emerald-50 dark:bg-emerald-900/20/30 text-emerald-800 dark:text-emerald-200': compra.estado === 'recibida',
                                      'bg-sky-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200': compra.estado === 'aprobada',
                                      'bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-amber-200': compra.estado === 'pendiente',
                                      'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200': compra.estado === 'rechazada' || compra.estado === 'cancelada',
                                      'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200': compra.estado === 'borrador'
                                  }">
                                {{ compra.estado }}
                            </span>
                        </p>
                        <p v-if="compra.notas" class="mt-2 text-slate-900 dark:text-white">
                            <strong>Notas:</strong> {{ compra.notas }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de ítems -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-6">
                    <div class="p-6">
                        <h2 class="text-lg font-semibold mb-4 text-slate-900 dark:text-white">Productos y Servicios</h2>
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Descuento</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Subtotal</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock Antes</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Stock Después</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                <tr v-for="item in items" :key="item.id">
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">
                                        <div>
                                            <div class="font-medium">{{ item.nombre }}</div>
                                            <div v-if="item.descripcion" class="text-xs text-slate-500 mt-1">{{ item.descripcion }}</div>
                                            <div v-if="item.codigo" class="text-xs text-sky-600 dark:text-sky-400 mt-1">Código: {{ item.codigo }}</div>
                                            <div v-if="item.categoria" class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Categoría: {{ item.categoria.nombre }}</div>
                                            <div v-if="item.marca" class="text-xs text-violet-600 dark:text-violet-400 mt-1">Marca: {{ item.marca.nombre }}</div>
                                            <div v-if="item.unidad_medida" class="text-xs text-brand-600 dark:text-brand-400 mt-1">Unidad: {{ item.unidad_medida }}</div>
                                            <div v-if="item.requiere_serie" class="text-xs text-rose-600 dark:text-rose-400 mt-1">
                                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                                </svg>
                                                Requiere serie
                                            </div>
                                            <div v-if="item.expires" class="text-xs text-brand-600 dark:text-brand-400 mt-1">
                                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                                Producto perecedero
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-right">{{ item.cantidad }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-right">${{ item.precio.toFixed(2) }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-right">{{ item.descuento }}%</td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-right">
                                        ${{ ((item.cantidad * item.precio) * (1 - item.descuento / 100)).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-sky-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200">
                                            {{ item.stock_antes }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-emerald-50 dark:bg-emerald-900/20/30 text-emerald-800 dark:text-emerald-200">
                                            {{ item.stock_despues }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900 dark:text-white text-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                                            :class="{
                                                'bg-emerald-50 dark:bg-emerald-900/20/30 text-emerald-800 dark:text-emerald-200': item.diferencia_stock > 0,
                                                'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200': item.diferencia_stock < 0,
                                                'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200': item.diferencia_stock === 0
                                            }"
                                        >
                                            <svg v-if="item.diferencia_stock > 0" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            <svg v-else-if="item.diferencia_stock < 0" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            {{ item.diferencia_stock > 0 ? '+' : '' }}{{ item.diferencia_stock }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-6">
                    <div class="p-6 text-slate-900 dark:text-white">
                        <div class="space-y-2 text-right">
                            <p class="text-slate-900 dark:text-white"><strong>Subtotal:</strong> ${{ subtotal.toFixed(2) }}</p>
                            <p v-if="descuentoGeneral > 0" class="text-slate-900 dark:text-white"><strong>Descuento General:</strong> ${{ descuentoGeneral.toFixed(2) }}</p>
                            <p class="text-slate-900 dark:text-white"><strong>IVA:</strong> ${{ iva.toFixed(2) }}</p>
                            <p class="text-xl text-slate-900 dark:text-white"><strong>Total:</strong> ${{ total.toFixed(2) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Operaciones -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden mb-6">
                    <div class="border-b border-slate-300 dark:border-slate-600">
                        <button
                            @click="accordionOpen = !accordionOpen"
                            class="w-full px-6 py-4 text-left flex justify-between items-center hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                        >
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Operaciones de Compra</h3>
                            <svg
                                :class="accordionOpen ? 'transform rotate-180' : ''"
                                class="w-5 h-5 text-slate-500 dark:text-slate-400 transition-transform"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <div v-show="accordionOpen" class="px-6 py-4">
                        <!-- Sección de Cuentas por Pagar -->
                        <div class="border-t border-slate-300 dark:border-slate-600 pt-6 mt-6">
                            <div class="px-4 py-5 sm:p-0">
                                <dl>
                                    <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                        <dt class="text-sm font-medium text-slate-500">
                                            Estado de Pago
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">
                                            <span v-if="compra.cuentas_por_pagar"
                                                  :class="{
                                                      'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200': compra.cuentas_por_pagar.estado === 'vencido',
                                                      'bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-amber-200': compra.cuentas_por_pagar.estado === 'parcial',
                                                      'bg-emerald-50 dark:bg-emerald-900/20/30 text-emerald-800 dark:text-emerald-200': compra.cuentas_por_pagar.estado === 'pagado',
                                                      'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200': compra.cuentas_por_pagar.estado === 'pendiente'
                                                  }"
                                                  class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium">
                                                {{ compra.cuentas_por_pagar.estado }}
                                            </span>
                                            <span v-else class="text-slate-500">No registrada</span>
                                        </dd>
                                    </div>

                                    <div v-if="compra.cuentas_por_pagar" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                        <dt class="text-sm font-medium text-slate-500">
                                            Monto Pendiente
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">
                                            ${{ compra.cuentas_por_pagar.monto_pendiente.toFixed(2) }}
                                        </dd>
                                    </div>

                                    <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:py-5">
                                        <dt class="text-sm font-medium text-slate-500">
                                            Acciones
                                        </dt>
                                        <dd class="mt-1 text-sm text-slate-900 sm:mt-0 sm:col-span-2">
                                            <div class="flex gap-2">
                                                <Link v-if="!compra.cuentas_por_pagar"
                                                      :href="route('cuentas-por-pagar.create', { compra_id: compra.id })"
                                                      class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-amber-300">
                                                    Crear Cuenta por Pagar
                                                </Link>
                                                <Link v-else
                                                      :href="route('cuentas-por-pagar.edit', compra.cuentas_por_pagar.id)"
                                                      class="text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-amber-300">
                                                    Gestionar Pagos
                                                </Link>
                                            </div>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
                    <div class="p-6 flex flex-wrap gap-3">
                        <Link
                            v-if="canEdit"
                            :href="route('compras.edit', compra.id)"
                            class="bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Editar
                        </Link>

                        <Link
                            v-if="canDelete"
                            :href="route('compras.destroy', compra.id)"
                            method="delete"
                            as="button"
                            class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Eliminar
                        </Link>

                        <button
                            @click="mostrarVistaPrevia = true"
                            class="bg-slate-600 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors"
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
            type="compra"
            :proveedor="compra.proveedor"
            :items="items"
            :totals="{
                subtotal,
                descuentoGeneral,
                iva,
                total
            }"
            :descuento-general="descuentoGeneral"
            :notas="compra.notas"
            @close="mostrarVistaPrevia = false"
            @print="() => window.print()"
        />
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

// Props
const props = defineProps({
    compra: Object,
    canReceive: {
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
    }
});

// Totales
const subtotal = computed(() => parseFloat(props.compra.subtotal) || 0);
const descuentoGeneral = computed(() => parseFloat(props.compra.descuento_general) || 0);
const iva = computed(() => parseFloat(props.compra.iva) || 0);
const total = computed(() => parseFloat(props.compra.total) || 0);

// Items
const items = computed(() => {
    if (!props.compra.productos || !Array.isArray(props.compra.productos)) {
        return [];
    }
    return props.compra.productos.map(item => ({
        id: item.id,
        nombre: item.nombre || 'Sin nombre',
        descripcion: item.descripcion || '',
        cantidad: parseInt(item.cantidad) || 0,
        precio: parseFloat(item.precio) || 0,
        descuento: parseFloat(item.descuento) || 0,
        stock_antes: parseInt(item.stock_antes) || 0,
        stock_despues: parseInt(item.stock_despues) || 0,
        diferencia_stock: parseInt(item.diferencia_stock) || 0
    }));
});

// Estado
const mostrarVistaPrevia = ref(false);
const accordionOpen = ref(false);
</script>

  <style scoped>
  /* Aquí van tus estilos personalizados */
  </style>

