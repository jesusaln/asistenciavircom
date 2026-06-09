<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import { labelMetodoPagoVenta } from '@/Utils/ventaPagoLabels';

defineOptions({ layout: AppLayout });

const { colors } = useCompanyColors();

const props = defineProps({
  venta: { type: Object, required: true },
  clientes: { type: Array, required: true },
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  priceLists: { type: Array, default: () => [] },
  vendedores: { type: Array, default: () => [] },
  cuentasBancarias: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16, isrPorcentaje: 1.25 }) },
});

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981' },
    { type: 'error', background: '#ef4444' },
    { type: 'info', background: '#3b82f6' }
  ]
});

const form = useForm({
  cliente_id: props.venta.cliente?.id || '',
  price_list_id: props.venta.cliente?.price_list_id || '',
  numero_venta: props.venta.numero_venta,
  fecha: props.venta.fecha,
  estado: props.venta.estado,
  descuento_general: props.venta.descuento_general || 0,
  metodo_pago: props.venta.metodo_pago || 'efectivo',
  metodo_pago_sat: props.venta.metodo_pago_sat || (props.venta.metodo_pago === 'credito' ? 'PPD' : 'PUE'),
  forma_pago_sat: props.venta.forma_pago_sat || (props.venta.metodo_pago === 'credito' ? '99' : '01'),
  productos: [],
  notas: props.venta.notas || '',
  retencion_iva: props.venta.retencion_iva || 0,
  retencion_isr: props.venta.retencion_isr || 0,
  vendedor_id: props.venta.vendedor_id || '',
  vendedor_type: props.venta.vendedor_type || 'App\\Models\\User',
  cuenta_bancaria_id: props.venta.cuenta_bancaria_id || '',
});

const mapeoFormaPagoSat = {
  efectivo: '01',
  transferencia: '03',
  tarjeta: '04',
  cheque: '02',
  credito: '99',
};

const aplicarSatDesdeMetodo = (metodo) => {
  const m = metodo || 'efectivo';
  form.forma_pago_sat = mapeoFormaPagoSat[m] || '99';
  form.metodo_pago_sat = m === 'credito' ? 'PPD' : 'PUE';
  if (m === 'efectivo' || m === 'credito') {
    form.cuenta_bancaria_id = '';
  }
};

watch(
  () => form.metodo_pago,
  (m) => {
    if (!props.venta.tiene_entrega_dinero) {
      aplicarSatDesdeMetodo(m);
    }
  }
);

const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serials = ref({});
const clienteSeleccionado = ref(props.venta.cliente);
const vendedorSeleccionado = ref('');
const mostrarVistaPrevia = ref(false);
const applyingDarkMode = ref(true);

/** Lista segura (Inertia siempre debe mandar vendedores; por si acaso) */
const listaVendedores = computed(() => {
  const v = props.vendedores;
  return Array.isArray(v) ? v : [];
});

const onVendedorChange = () => {
  const sel = vendedorSeleccionado.value;
  if (!sel) {
    form.vendedor_type = '';
    form.vendedor_id = '';
    return;
  }
  const parts = sel.split('-');
  const id = parts[parts.length - 1];
  form.vendedor_type = 'App\\Models\\User';
  form.vendedor_id = parseInt(id, 10);
};

const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    return;
  }
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;
  notyf.success(`Cliente: ${cliente.nombre_razon_social}`);
};

const filterKitComponents = (items) => {
  const kitComponentIds = new Set();
  items.forEach(item => {
    if (item.ventable_type === 'App\\Models\\Producto') {
      const p = props.productos.find(prod => prod.id === item.ventable.id);
      if (p?.tipo_producto === 'kit' && p.kit_items) {
        p.kit_items.forEach(ki => kitComponentIds.add(ki.item_id));
      }
    }
  });
  return kitComponentIds;
};

onMounted(() => {
  const kitIds = filterKitComponents(props.venta.items);
  
  // En primer lugar, procesar componentes de kit para cargar sus series en serials.value
  props.venta.items.forEach(item => {
    const tipo = item.ventable_type === 'App\\Models\\Producto' ? 'producto' : 'servicio';
    if (tipo === 'producto' && kitIds.has(item.ventable.id) && Number(item.precio) === 0) {
      // Encontrar el kit padre
      const parentKit = props.venta.items.find(kitCandidate => {
        if (kitCandidate.ventable_type === 'App\\Models\\Producto') {
          const p = props.productos.find(prod => prod.id === kitCandidate.ventable.id);
          return p?.tipo_producto === 'kit' && p.kit_items?.some(ki => ki.item_id === item.ventable.id);
        }
        return false;
      });

      if (parentKit) {
        const kitKey = `kit-${parentKit.ventable.id}-component-${item.ventable.id}`;
        if (item.series && Array.isArray(item.series)) {
          serials.value[kitKey] = item.series.map(s => s.numero_serie || s);
        }
      }
      return; // Saltar para que no se añada a la lista principal (PySSeleccionados)
    }

    const key = `${tipo}-${item.ventable.id}`;
    selectedProducts.value.push({ 
       id: item.ventable.id, 
       tipo, 
       nombre: item.ventable.nombre,
       tipo_producto: props.productos.find(p => p.id === item.ventable.id)?.tipo_producto
    });
    quantities.value[key] = item.cantidad;
    prices.value[key] = item.precio;
    discounts.value[key] = item.descuento || 0;
    
    if (item.series && Array.isArray(item.series)) {
      serials.value[key] = item.series.map(s => s.numero_serie || s);
    }
  });

  if (props.venta.vendedor_id) {
    const v = props.vendedores.find((x) => x.id === props.venta.vendedor_id && x.type === 'user');
    vendedorSeleccionado.value = v ? `user-${v.id}` : `user-${props.venta.vendedor_id}`;
  }
  if (!props.venta.tiene_entrega_dinero && form.metodo_pago) {
    aplicarSatDesdeMetodo(form.metodo_pago);
  }
});

const totales = computed(() => {
  let subtotal = 0;
  selectedProducts.value.forEach(item => {
    const key = `${item.tipo}-${item.id}`;
    subtotal += (quantities.value[key] || 0) * (prices.value[key] || 0);
  });
  const totalDescItems = selectedProducts.value.reduce((acc, item) => {
    const key = `${item.tipo}-${item.id}`;
    return acc + (quantities.value[key] * prices.value[key] * (discounts.value[key] / 100));
  }, 0);
  const subTotalFinal = subtotal - totalDescItems - (form.descuento_general || 0);
  const iva = subTotalFinal * 0.16;
  return {
    subtotal,
    descuento_items: totalDescItems,
    iva,
    total: subTotalFinal + iva
  };
});

const actualizarVenta = () => {
  onVendedorChange();
  form.productos = selectedProducts.value
    .filter(item => item.tipo === 'producto')
    .map(item => {
      const key = `${item.tipo}-${item.id}`;
      
      // Obtener información para identificar si es kit y empaquetar subs
      const pInfo = props.productos.find(p => p.id === item.id) || {};
      let componentes_series = {};
      
      if (pInfo.tipo_producto === 'kit' || item.tipo_producto === 'kit') {
        Object.keys(serials.value).forEach(k => {
          if (k.startsWith(`kit-${item.id}-component-`)) {
            const compId = k.replace(`kit-${item.id}-component-`, '');
            componentes_series[compId] = serials.value[k];
          }
        });
      }

      return {
        id: item.id,
        tipo: item.tipo,
        cantidad: quantities.value[key],
        precio: prices.value[key],
        descuento: discounts.value[key],
        series: serials.value[key] || [],
        componentes_series: Object.keys(componentes_series).length > 0 ? componentes_series : undefined
      };
    });

  form.servicios = selectedProducts.value
    .filter(item => item.tipo === 'servicio')
    .map(item => {
      const key = `${item.tipo}-${item.id}`;
      return {
        id: item.id,
        tipo: item.tipo,
        cantidad: quantities.value[key],
        precio: prices.value[key],
        descuento: discounts.value[key]
      };
    });

  form.put(route('ventas.update', props.venta.id), {
    onSuccess: () => notyf.success('Venta actualizada correctamente'),
    onError: () => notyf.error('Error al actualizar venta, verifique campos requeridos y series.')
  });
};
const eliminarProducto = (item) => {
  const index = selectedProducts.value.findIndex(p => p.id === item.id && p.tipo === item.tipo);
  if (index !== -1) {
    selectedProducts.value.splice(index, 1);
    const key = `${item.tipo}-${item.id}`;
    delete quantities.value[key];
    delete prices.value[key];
    delete discounts.value[key];
    delete serials.value[key];
  }
};
</script>

<template>
  <Head title="Editar Registro de Venta" />
  
  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      
      <!-- Premium Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
        <div class="flex items-center space-x-6">
          <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform hover:scale-105" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
             <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
             </svg>
          </div>
          <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-wider">Edición de Registro</h1>
            <div class="flex items-center mt-1 space-x-3">
              <span class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-[0.3em]">Referencia: {{ venta.numero_venta }}</span>
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
              <span class="text-[10px] font-black text-blue-500 dark:text-blue-400 uppercase tracking-widest">En Proceso</span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link :href="route('ventas.index')" class="px-6 py-3 bg-white dark:bg-slate-900 text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-slate-400 rounded-xl border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all shadow-sm">
            Regresar
          </Link>
          <button @click="actualizarVenta" class="flex items-center gap-3 px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-xl hover:shadow-emerald-500/20 transition-all transform hover:-translate-y-1 active:translate-y-0">
             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
             Guardar Cambios
          </button>
        </div>
      </div>

      <div v-if="venta.tiene_entrega_dinero" class="mb-8 bg-white dark:bg-slate-900 border border-amber-200/80 dark:border-amber-700/50 border-l-[5px] border-l-amber-500 p-6 rounded-r-2xl shadow-sm flex items-start gap-4">
        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400 mt-1 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div>
          <h3 class="text-sm font-black text-slate-900 dark:text-amber-100 uppercase tracking-widest">Dinero Entregado (Corte Realizado)</h3>
          <p class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-300 leading-relaxed">
            El dinero de esta venta ya entró en un corte: no se pueden cambiar cliente, productos, cantidades ni precios. Sí puedes editar <strong class="text-slate-900 dark:text-white">vendedor/técnico y observaciones</strong> en la sección de abajo.
            <a href="#vendedor-y-observaciones" class="ml-1 font-black text-amber-800 dark:text-amber-300 underline decoration-2 underline-offset-2">Ir a vendedor y observaciones</a>
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
        <!-- Main Form Column -->
        <div class="xl:col-span-8 space-y-10">

          <div
            id="vendedor-y-observaciones"
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border overflow-hidden scroll-mt-28 border-l-[5px] border-l-emerald-500"
            :class="venta.tiene_entrega_dinero ? 'border-emerald-400/60 ring-2 ring-emerald-500/20 border-gray-100 dark:border-slate-800' : 'border-gray-100 dark:border-slate-800'"
          >
             <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800/50 bg-gray-50/50 dark:bg-slate-950/20">
                <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Vendedor y observaciones</h2>
                <p v-if="!venta.tiene_entrega_dinero" class="mt-1 text-[10px] text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                  Comisión y reportes según el responsable; guarda con «Guardar cambios» o «Actualizar registro».
                </p>
             </div>
             <div class="p-8 space-y-6">
                <div>
                  <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">Vendedor o técnico (comisión / reportes)</label>
                  <select
                    v-model="vendedorSeleccionado"
                    @change="onVendedorChange"
                    class="w-full bg-gray-50 dark:bg-slate-950 border-2 border-emerald-600/35 dark:border-slate-700 rounded-2xl px-4 py-3 text-base font-bold text-gray-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 outline-none transition-all"
                  >
                    <option value="">— Elegir vendedor o técnico —</option>
                    <option v-for="v in listaVendedores" :key="`dv-${v.type}-${v.id}`" :value="`${v.type}-${v.id}`">{{ v.nombre }}</option>
                  </select>
                  <p v-if="listaVendedores.length === 0" class="text-[11px] text-red-600 dark:text-red-400 mt-2 font-bold">
                    No se cargó la lista. Recarga (Ctrl+F5) o revisa roles y «es técnico / es vendedor» en RRHH.
                  </p>
                </div>
                <div>
                  <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">Observaciones</label>
                  <textarea v-model="form.notas" rows="4" class="w-full bg-gray-50 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 focus:border-emerald-500 rounded-2xl px-6 py-4 text-sm font-bold text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400 dark:placeholder-slate-600" placeholder="Notas internas o para el cliente..."></textarea>
                </div>
             </div>
          </div>

          <!-- Forma de pago -->
          <div
            v-if="!venta.tiene_entrega_dinero"
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 overflow-hidden"
          >
            <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800/50 bg-gray-50/50 dark:bg-slate-950/20">
              <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Forma de pago</h2>
              <p class="mt-1 text-[10px] text-slate-600 dark:text-slate-400 leading-relaxed max-w-3xl">
                Método acordado con el cliente; se usa en cobranza y CFDI. SAT: {{ form.metodo_pago_sat }} / forma {{ form.forma_pago_sat }}.
              </p>
            </div>
            <div class="p-8 space-y-6">
              <div>
                <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">Método</label>
                <select
                  v-model="form.metodo_pago"
                  class="w-full bg-gray-50 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 rounded-2xl px-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:border-emerald-500 outline-none"
                >
                  <option value="efectivo">Efectivo</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="credito">Crédito</option>
                </select>
              </div>
              <div v-if="form.metodo_pago === 'transferencia' || form.metodo_pago === 'tarjeta'">
                <label class="block text-[10px] font-black text-gray-500 dark:text-slate-400 uppercase tracking-widest mb-2">Cuenta bancaria (destino)</label>
                <select
                  v-model="form.cuenta_bancaria_id"
                  class="w-full bg-gray-50 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 rounded-2xl px-4 py-3 text-sm font-bold text-gray-900 dark:text-white focus:border-emerald-500 outline-none"
                >
                  <option value="">— Seleccionar —</option>
                  <option v-for="c in cuentasBancarias" :key="c.id" :value="String(c.id)">{{ c.banco }} — {{ c.nombre }}</option>
                </select>
              </div>
            </div>
          </div>
          <div
            v-else
            class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 overflow-hidden p-8"
          >
            <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2">Forma de pago registrada</h2>
            <p class="text-sm font-bold text-gray-700 dark:text-slate-300">{{ labelMetodoPagoVenta(form.metodo_pago) || '—' }} · SAT {{ form.metodo_pago_sat }} / {{ form.forma_pago_sat }}</p>
          </div>
          
          <!-- Cliente Selector Card -->
          <div :class="{'pointer-events-none opacity-50 grayscale transition-all duration-300': venta.tiene_entrega_dinero}" class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 overflow-hidden group transition-all">
             <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800/50 flex items-center justify-between bg-gray-50/50 dark:bg-slate-950/20">
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                  </div>
                  <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest transition-colors">Identificación de Cliente</h2>
                </div>
             </div>
             <div class="p-8">
               <BuscarCliente 
                 :clientes="clientes" 
                 :cliente-seleccionado="clienteSeleccionado" 
                 @cliente-seleccionado="onClienteSeleccionado"
               />
             </div>
          </div>

          <!-- PyS Selector Card -->
          <div :class="{'pointer-events-none opacity-50 grayscale transition-all duration-300': venta.tiene_entrega_dinero}" class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-gray-100 dark:border-slate-800 overflow-hidden">
             <div class="px-8 py-5 border-b border-gray-100 dark:border-slate-800/50 flex items-center justify-between bg-gray-50/50 dark:bg-slate-950/20">
                <div class="flex items-center space-x-3">
                   <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400">
                     <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                   </div>
                   <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Productos y Servicios</h2>
                </div>
             </div>
             <div class="p-8">
                <div class="mb-8 p-6 bg-gray-50 dark:bg-slate-950/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-slate-800">
                  <BuscarProducto 
                    :productos="productos" 
                    :servicios="servicios" 
                    @producto-seleccionado="item => {
                       const k = `${item.tipo}-${item.id}`;
                       if (!selectedProducts.find(p => p.id === item.id && p.tipo === item.tipo)) {
                          selectedProducts.push({ ...item });
                          quantities[k] = 1;
                          prices[k] = item.precio || 0;
                          discounts[k] = 0;
                       }
                    }"
                  />
                </div>
                <PySSeleccionados 
                  :selected-products="selectedProducts"
                  :quantities="quantities"
                  :prices="prices"
                  :discounts="discounts"
                  :serials="serials"
                  @eliminar-producto="eliminarProducto"
                  @update-quantity="(k, q) => quantities[k] = q"
                  @update-price="(k, p) => prices[k] = p"
                  @update-discount="(k, d) => discounts[k] = d"
                  @update-serials="(k, s) => serials[k] = s"
                />
             </div>
          </div>
        </div>

        <!-- Sidebar Column -->
        <div class="xl:col-span-4 space-y-10">
          <!-- Multi-step Totales Widget -->
          <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-gray-100 dark:border-slate-800 overflow-hidden sticky top-10">
             <div class="px-8 py-6 border-b border-gray-100 dark:border-slate-800/50" :style="{ background: `linear-gradient(135deg, ${colors.principal}10 0%, ${colors.secundario}05 100%)` }">
                <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-[0.2em]">Cálculo Económico</h2>
             </div>
             <div class="p-8 space-y-6">
                <div class="space-y-4">
                   <div class="flex justify-between items-center text-xs font-bold">
                      <span class="text-gray-400 dark:text-slate-500 uppercase tracking-widest">Subtotal Bruto</span>
                      <span class="text-gray-900 dark:text-white">${{ totales.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                   </div>
                   <div class="flex justify-between items-center text-xs font-bold text-rose-500">
                      <span class="uppercase tracking-widest">Deducción Items</span>
                      <span>-${{ totales.descuento_items.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                   </div>
                     <div class="flex flex-col gap-2 pt-2" :class="{'pointer-events-none opacity-50': venta.tiene_entrega_dinero}">
                        <span class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest ml-1">Descuento Global ($)</span>
                        <input type="number" :disabled="venta.tiene_entrega_dinero" v-model="form.descuento_general" class="w-full bg-gray-50 dark:bg-slate-950 border-2 border-gray-100 dark:border-slate-800 rounded-xl px-4 py-2 text-sm font-black text-gray-900 dark:text-white outline-none" />
                     </div>
                </div>

                <div class="pt-6 border-t border-gray-100 dark:border-slate-800/50 space-y-4">
                    <div class="flex justify-between items-center text-xs font-bold">
                      <span class="text-gray-400 dark:text-slate-500 uppercase tracking-widest">Impuesto (IVA 16%)</span>
                      <span class="text-gray-900 dark:text-white">${{ totales.iva.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                   </div>
                   <div class="bg-slate-50 dark:bg-indigo-950/40 p-6 rounded-2xl border border-indigo-200/80 dark:border-indigo-800/40 text-center mt-4">
                      <span class="text-[9px] font-black text-indigo-900 dark:text-indigo-200 uppercase tracking-[0.3em] block mb-1">Monto de Liquidación</span>
                      <p class="text-4xl font-black text-indigo-800 dark:text-indigo-300 tracking-tighter">
                         <span class="text-xl text-indigo-500 dark:text-indigo-500/80">$</span>{{ totales.total.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                      </p>
                   </div>
                </div>

                <div class="pt-6 space-y-3">
                   <button @click="actualizarVenta" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-emerald-500/20 transition-all">
                      Actualizar Registro
                   </button>
                   <Link :href="route('ventas.show', venta.id)" class="w-full flex justify-center py-4 bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800 transition-all">
                      Cancelar Edición
                   </Link>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ventas-edit {
  font-family: 'Figtree', sans-serif;
}
</style>
