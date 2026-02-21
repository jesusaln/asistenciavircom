<template>
  <Head title="Editar Orden de Compra" />

  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative">
    
    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-amber-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-blue-600/10 rounded-full blur-[100px] animate-pulse-slow px-2" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10 animate-fade-in-up">
      
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-2 border-b border-slate-200/50 dark:border-slate-800/50">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Editar Orden</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-amber-600 dark:text-amber-400">Transacción № {{ form.numero_orden }}</span>
                <div class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="form.proveedor_id && selectedProducts.length > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        {{ form.proveedor_id && selectedProducts.length > 0 ? 'Expediente Válido' : 'Documento Incompleto' }}
                    </span>
                </div>
            </div>
        </div>

        <Link 
          :href="route('ordenescompra.index')"
          class="flex items-center gap-3 px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300 active:scale-95 border border-slate-200/50 dark:border-slate-800/50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Volver al Catálogo
        </Link>
      </div>

      <!-- Main Form Container -->
      <form @submit.prevent="updatePurchaseOrder" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <!-- Left Column: Primary Sections -->
        <div class="lg:col-span-8 space-y-10">
          
          <!-- Seccion: Info General -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center justify-between mb-10">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-600/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2" /></svg>
                        </div>
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Información de Cabecera</h2>
                            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Metadatos de la orden</p>
                        </div>
                    </div>
                    <div v-if="cargandoDatos" class="inline-flex items-center gap-2 px-3 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 rounded-full text-[9px] font-black uppercase tracking-widest">
                        <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Sincronizando...
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Número de Orden -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">N° Orden Operativa</label>
                        <div class="relative">
                            <input
                                v-model="form.numero_orden"
                                type="text"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-400 dark:text-slate-500 cursor-not-allowed select-none transition-all tracking-widest"
                                readonly
                            />
                            <div @click="copiarNumeroOrden" class="absolute inset-y-0 right-4 flex items-center cursor-pointer hover:text-blue-500 text-slate-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Fecha Emisión</label>
                        <input
                            v-model="form.fecha_orden"
                            type="date"
                            readonly
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-400 dark:text-slate-500 cursor-not-allowed transition-all"
                        />
                    </div>

                    <!-- Prioridad -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Nivel de Prioridad</label>
                        <select
                            v-model="form.prioridad"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-2 focus:ring-amber-600/20"
                        >
                            <option value="baja">BAJA</option>
                            <option value="media">MEDIA</option>
                            <option value="alta">ALTA</option>
                            <option value="urgente">URGENTE</option>
                        </select>
                    </div>
                </div>
          </div>

          <!-- Seccion: Proveedor -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Perfil del Proveedor</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Origen de suministros</p>
                    </div>
                </div>

                <div class="relative">
                    <BuscarProveedor
                        :proveedores="proveedoresList"
                        :proveedor-seleccionado="proveedorSeleccionado"
                        variant="premium"
                        @proveedor-seleccionado="onProveedorSeleccionado"
                    />
                </div>
          </div>

          <!-- Seccion: Productos -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Líneas de Suministro</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Artículos para inventario</p>
                    </div>
                </div>

                <div class="space-y-8">
                    <BuscarProducto
                        ref="buscarProductoRef"
                        :productos="productos"
                        :servicios="[]"
                        :validar-stock="false"
                        variant="premium"
                        @agregar-producto="agregarProducto"
                    />

                    <ProductosSeleccionados
                        :selected-products="selectedProducts"
                        :productos="productos"
                        :servicios="[]"
                        :quantities="quantities"
                        :prices="prices"
                        :discounts="discounts"
                        variant="premium"
                        title-singular="Producto"
                        @eliminar-producto="eliminarProducto"
                        @update-quantity="updateQuantity"
                        @update-discount="updateDiscount"
                    />
                </div>
          </div>
        </div>

        <!-- Right Column: Context & Actions -->
        <div class="lg:col-span-4 space-y-10">
            <div class="sticky top-10 space-y-10">
                
                <!-- Totales Card -->
                <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 flex flex-col items-center">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-amber-600 dark:text-amber-400 mb-10 w-full text-center">Resumen Liquidación</h3>
                    
                    <Totales
                        :totals="totales"
                        :item-count="selectedProducts.length"
                        :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (qty || 0), 0)"
                        variant="premium"
                        :enable-retencion-iva="props.defaults?.enableRetencionIva"
                        :enable-retencion-isr="props.defaults?.enableRetencionIsr"
                        :retencion-iva-default="retencionIvaDefault"
                        :retencion-isr-default="retencionIsrDefault"
                        v-model:aplicarRetencionIva="aplicarRetencionIva"
                        v-model:aplicarRetencionIsr="aplicarRetencionIsr"
                        @update:descuento-general="val => form.descuento_general = val"
                    />

                    <div class="w-full mt-10 space-y-3">
                        <button
                            @click="handlePreview"
                            type="button"
                            class="w-full flex items-center justify-center gap-3 px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Vista Previa Actual
                        </button>
                        
                        <button
                            type="submit"
                            class="w-full flex items-center justify-center gap-3 px-8 py-5 bg-amber-600 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-xl shadow-amber-600/20 hover:shadow-amber-600/40 hover:-translate-y-1 transition-all duration-300 active:scale-95 bg-gradient-to-r from-amber-600 to-orange-600 disabled:opacity-50 disabled:grayscale disabled:pointer-events-none"
                            :disabled="form.processing || !form.proveedor_id || selectedProducts.length === 0"
                        >
                            <svg v-if="form.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ form.processing ? 'Actualizando...' : 'Sincronizar Cambios' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Secondary Config Card -->
                <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 space-y-8">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white mb-2">Configuración Entrega</h3>
                    
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Almacén Destino</label>
                            <select v-model="form.almacen_id" class="w-full px-5 py-3 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-xl text-[11px] font-bold text-slate-900 dark:text-white uppercase focus:ring-1 focus:ring-blue-600/20 transition-all appearance-none cursor-pointer">
                                <option value="">Auto-selección</option>
                                <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Términos Pago</label>
                            <select v-model="form.terminos_pago" class="w-full px-5 py-3 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-xl text-[11px] font-bold text-slate-900 dark:text-white uppercase focus:ring-1 focus:ring-blue-600/20 appearance-none cursor-pointer">
                                <option value="contado">CONTADO</option>
                                <option value="15_dias">15 DÍAS</option>
                                <option value="30_dias">30 DÍAS</option>
                                <option value="60_dias">60 DÍAS</option>
                            </select>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Entrega Estimada</label>
                            <input v-model="form.fecha_entrega_esperada" type="date" class="w-full px-5 py-3 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-xl text-[11px] font-bold text-slate-900 dark:text-white uppercase focus:ring-1 focus:ring-blue-600/20" />
                            <div class="flex flex-wrap gap-2 mt-2">
                                <button type="button" @click="setFechaRapida('hoy')" class="px-2 py-1 bg-amber-500/5 text-amber-600 text-[8px] font-black uppercase rounded-md tracking-tighter hover:bg-amber-500/10 transition-all">+HOY</button>
                                <button type="button" @click="setFechaRapida('manana')" class="px-2 py-1 bg-blue-500/5 text-blue-600 text-[8px] font-black uppercase rounded-md tracking-tighter hover:bg-blue-500/10 transition-all">+MAÑANA</button>
                                <button type="button" @click="setFechaRapida('semana')" class="px-2 py-1 bg-indigo-500/5 text-indigo-600 text-[8px] font-black uppercase rounded-md tracking-tighter hover:bg-indigo-500/10 transition-all">+7D</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Card -->
                 <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white mb-6">Observaciones</h3>
                    <textarea 
                        v-model="form.observaciones"
                        rows="4"
                        class="w-full p-6 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-[11px] font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-amber-600/20 transition-all resize-none"
                        placeholder="Escriba especificaciones adicionales..."
                    ></textarea>
                 </div>
            </div>
        </div>
      </form>
    </div>

    <!-- Vista Previa Modal -->
    <Teleport to="body">
        <VistaPreviaModal
            :show="mostrarVistaPrevia"
            type="ordenescompra"
            :proveedor="proveedorSeleccionado"
            :productos="selectedProductsData"
            :totals="totales"
            :notas="form.observaciones"
            :orden-data="{
              numero_orden: form.numero_orden,
              fecha_orden: form.fecha_orden,
              fecha_entrega_esperada: form.fecha_entrega_esperada,
              prioridad: form.prioridad,
              direccion_entrega: form.direccion_entrega,
              terminos_pago: form.terminos_pago,
              metodo_pago: form.metodo_pago
            }"
            @close="mostrarVistaPrevia = false"
        />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import ProductosSeleccionados from '@/Components/CreateComponents/ProductosSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
  ordenCompra: { type: Object, required: true },
  proveedores: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16 }) }
});

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } });

const formatearFecha = (f) => f ? f.split(' ')[0] : '';

const form = useForm({
  numero_orden: props.ordenCompra.numero_orden || '',
  fecha_orden: formatearFecha(props.ordenCompra.fecha_orden),
  fecha_entrega_esperada: formatearFecha(props.ordenCompra.fecha_entrega_esperada),
  prioridad: props.ordenCompra.prioridad || 'media',
  proveedor_id: props.ordenCompra.proveedor_id || '',
  almacen_id: props.ordenCompra.almacen_id || '',
  direccion_entrega: props.ordenCompra.direccion_entrega || '',
  terminos_pago: props.ordenCompra.terminos_pago || '30_dias',
  metodo_pago: props.ordenCompra.metodo_pago || 'transferencia',
  subtotal: props.ordenCompra.subtotal || 0,
  descuento_general: props.ordenCompra.descuento_general || 0,
  iva: props.ordenCompra.iva || 0,
  total: props.ordenCompra.total || 0,
  items: [],
  observaciones: props.ordenCompra.observaciones || '',
  aplicar_retencion_iva: props.ordenCompra.aplicar_retencion_iva || false,
  aplicar_retencion_isr: props.ordenCompra.aplicar_retencion_isr || false,
});

const proveedoresList = ref([...props.proveedores]);
const proveedorSeleccionado = ref(null);
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const mostrarVistaPrevia = ref(false);
const aplicarRetencionIva = ref(form.aplicar_retencion_iva);
const aplicarRetencionIsr = ref(form.aplicar_retencion_isr);
const cargandoDatos = ref(false);

const retencionIvaDefault = computed(() => parseFloat(props.defaults?.retencionIvaDefault ?? 10.6667));
const retencionIsrDefault = computed(() => parseFloat(props.defaults?.retencionIsrDefault ?? 10));

onMounted(() => {
    // Vincular proveedor inicial
    const p = props.proveedores.find(pv => pv.id === form.proveedor_id);
    if (p) proveedorSeleccionado.value = p;

    // Vincular items iniciales
    if (props.ordenCompra.items) {
        props.ordenCompra.items.forEach(item => {
            const prod = props.productos.find(pr => pr.id === item.producto_id);
            if (prod) {
                const k = `producto-${prod.id}`;
                selectedProducts.value.push({ ...prod, tipo: 'producto' });
                quantities.value[k] = item.cantidad;
                prices.value[k] = item.precio;
                discounts.value[k] = item.descuento || 0;
            }
        });
    }
});

const handlePreview = () => {
    if (!form.proveedor_id || selectedProducts.value.length === 0) {
        notyf.error('Documento incompleto');
        return;
    }
    mostrarVistaPrevia.value = true;
};

const onProveedorSeleccionado = (p) => {
    proveedorSeleccionado.value = p;
    form.proveedor_id = p?.id || '';
    if (p) notyf.success('Proveedor actualizado');
};

const agregarProducto = (item) => {
    const key = `producto-${item.id}`;
    if (selectedProducts.value.some(p => p.id === item.id)) {
        notyf.open({ type: 'warning', message: 'Ya registrado' });
        return;
    }
    selectedProducts.value.push({ ...item, tipo: 'producto' });
    quantities.value[key] = 1;
    prices.value[key] = item.precio_compra || item.precio_venta || 0;
    discounts.value[key] = 0;
    notyf.success('Línea añadida');
};

const eliminarProducto = (p) => {
    const key = `${p.tipo}-${p.id}`;
    selectedProducts.value = selectedProducts.value.filter(i => !(i.id === p.id && i.tipo === p.tipo));
    delete quantities.value[key];
    delete prices.value[key];
    delete discounts.value[key];
};

const updateQuantity = (k, q) => quantities.value[k] = parseFloat(q) || 0;
const updateDiscount = (k, d) => discounts.value[k] = parseFloat(d) || 0;

const totales = computed(() => {
    let sub = 0, descItems = 0;
    selectedProducts.value.forEach(p => {
        const k = `${p.tipo}-${p.id}`, q = quantities.value[k], pr = prices.value[k], d = discounts.value[k];
        const subItem = q * pr;
        descItems += subItem * (d / 100);
        sub += subItem;
    });
    const descGen = parseFloat(form.descuento_general) || 0;
    const base = Math.max(0, sub - descItems - descGen);
    const iva = base * ((props.defaults?.ivaPorcentaje || 16) / 100);
    const retIva = aplicarRetencionIva.value ? base * (retencionIvaDefault.value / 100) : 0;
    const retIsr = aplicarRetencionIsr.value ? base * (retencionIsrDefault.value / 100) : 0;
    return {
        subtotal: sub,
        descuentoItems: descItems,
        descuentoGeneral: descGen,
        subtotalConDescuentos: base,
        iva,
        retencion_iva: retIva,
        retencion_isr: retIsr,
        total: base + iva - retIva - retIsr
    };
});

const selectedProductsData = computed(() => {
    return selectedProducts.value.map(p => {
        const k = `${p.tipo}-${p.id}`;
        return {
            ...p,
            cantidad: quantities.value[k],
            precio: prices.value[k],
            descuento: discounts.value[k],
            total: quantities.value[k] * prices.value[k] * (1 - (discounts.value[k] / 100))
        };
    });
});

const setFechaRapida = (t) => {
    const d = new Date();
    if (t === 'manana') d.setDate(d.getDate() + 1);
    if (t === 'semana') d.setDate(d.getDate() + 7);
    form.fecha_entrega_esperada = d.toISOString().split('T')[0];
};

const copiarNumeroOrden = async () => {
    try {
        await navigator.clipboard.writeText(form.numero_orden);
        notyf.success('Número copiado');
    } catch {}
};

const updatePurchaseOrder = () => {
    if (form.processing || !form.proveedor_id || selectedProducts.value.length === 0) return;
    
    form.items = selectedProductsData.value.map(p => ({
        producto_id: p.id,
        cantidad: p.cantidad,
        precio: p.precio,
        descuento: p.descuento,
        iva: p.iva || props.defaults?.ivaPorcentaje || 16
    }));
    
    form.subtotal = totales.value.subtotalConDescuentos;
    form.iva = totales.value.iva;
    form.total = totales.value.total;
    form.aplicar_retencion_iva = aplicarRetencionIva.value;
    form.aplicar_retencion_isr = aplicarRetencionIsr.value;
    
    form.put(route('ordenescompra.update', props.ordenCompra.id), {
        onSuccess: () => notyf.success('Orden sincronizada correctamente'),
        onError: () => notyf.error('Error en sincronización')
    });
};
</script>

<style>
.animate-fade-in-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
@keyframes pulse-slow { 0%, 100% { opacity: 0.1; transform: scale(1); } 50% { opacity: 0.15; transform: scale(1.1); } }
input:focus, select:focus, textarea:focus { outline: none; }
</style>
