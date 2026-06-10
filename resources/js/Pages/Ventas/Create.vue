<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { normalizeText, includesSearch } from '@/Utils/searchHelper';
import axios from 'axios';
// Inicializar notificaciones
import { useNotification } from '@/Composables/useNotification';
import { resolverPrecio, detectarProductosSinPrecioEnLista } from '@/Utils/precioHelper';
import { translateErrorMessage } from '@/Utils/errorHelper';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
// import Totales from '@/Components/CreateComponents/Totales.vue'; // Reemplazado por widget lateral premium
// import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue'; // Reemplazado por botones laterales
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import CrearClienteModal from '@/Components/Modals/CrearClienteModal.vue';
import VentaPaymentModal from '@/Components/Modals/VentaPaymentModal.vue';
import VentaFallbackPriceModal from '@/Components/Modals/VentaFallbackPriceModal.vue';
import VentaErrorModal from '@/Components/Modals/VentaErrorModal.vue';
import VentaSeriesPickerModal from '@/Components/Modals/VentaSeriesPickerModal.vue';
import VentaKitComponentsModal from '@/Components/Modals/VentaKitComponentsModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const notyf = useNotification();

// Colores corporativos para estética
const { colors } = useCompanyColors();

const showErrorModal = ref(false);
const errorModalMessages = ref([]);
const stockErrorDetails = ref([]);

const closeErrorModal = () => {
  showErrorModal.value = false;
  errorModalMessages.value = [];
};

// Modal de precios fallback
const showFallbackPriceModal = ref(false);
const fallbackPriceProducts = ref([]);
const fallbackPriceAccepted = ref(false);

const closeFallbackPriceModal = () => {
  showFallbackPriceModal.value = false;
};

const acceptFallbackPriceAndContinue = () => {
  fallbackPriceAccepted.value = true;
  showFallbackPriceModal.value = false;
  calcularTotal();
  metodoPagoInmediato.value = '';
  cuentaBancariaInmediata.value = '';
  notasPagoInmediato.value = '';
  importeRecibido.value = 0;
  cambio.value = 0;
  showPaymentConfirmationModal.value = true;
};

const formatNumber = (value) => {
  return parseFloat(value || 0).toFixed(2);
};

const roundCurrency = (value) => {
  return Math.round((Number(value || 0) + Number.EPSILON) * 100) / 100;
};

const parseStockErrors = (message) => {
  if (!message.includes('Stock insuficiente para componente')) {
    return [message];
  }
  const errors = message.split(/,\s*(?=Stock insuficiente)/);
  const formattedErrors = [];
  let currentHeader = '';
  
  errors.forEach(err => {
    const match = err.match(/componente '([^']+)' del kit '([^']+)' en (.+)\. Disponible: (\d+), Necesario: (\d+)/);
    if (match) {
      const [_, componente, kit, almacen, disponible, necesario] = match;
      const header = `Stock insuficiente en ${almacen} para el kit ${kit}:`;
      if (header !== currentHeader) {
        formattedErrors.push(header);
        currentHeader = header;
      }
      formattedErrors.push(`• ${componente} (Necesario: ${necesario}, Disponible: ${disponible})`);
    } else {
      formattedErrors.push(err.trim());
    }
  });
  return formattedErrors;
};

const openErrorModal = (messages) => {
  let list = [];
  if (Array.isArray(messages)) {
    list = messages;
  } else if (typeof messages === 'string') {
    if (messages.includes('Stock insuficiente')) {
      list = parseStockErrors(messages);
    } else {
      list = messages.split(';').map(m => m.trim()).filter(Boolean);
    }
  } else {
    list = [String(messages || 'Ocurrió un error')];
  }
  errorModalMessages.value = list.length ? list : ['Ocurrió un error'];
  showErrorModal.value = true;
};

const showNotification = (message, type = 'success') => {
  if (type === 'error') {
    openErrorModal(message);
    return;
  }
  if (notyf[type]) {
      notyf[type](message);
  } else {
      notyf.success(message);
  }
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  clientes: Array,
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  catalogs: { type: Object, default: () => ({}) },
  almacenes: { type: Array, default: () => [] },
  priceLists: { type: Array, default: () => [] },
  vendedores: { type: Array, default: () => [] },
  user: { type: Object, default: () => ({}) },
  pedido: { type: Object, default: () => null },
  cita: { type: Object, default: () => null },
  taller: { type: Object, default: () => null },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16, isrPorcentaje: 1.25 }) },
});

// Copia reactiva de clientes
const clientesList = ref([...props.clientes]);

/** Cliente del servicio: fusionar cita.cliente con el listado (lista limitada a 100). */
const mergeClienteDesdeCita = (cit) => {
  if (!cit) return null;
  const raw = cit.cliente;
  const id = raw?.id ?? cit.cliente_id;
  if (id == null || id === '') return null;
  const fromList = clientesList.value.find((c) => Number(c.id) === Number(id));
  if (fromList && raw) return { ...raw, ...fromList };
  if (fromList) return fromList;
  if (raw) {
    if (!clientesList.value.some((c) => Number(c.id) === Number(raw.id))) {
      clientesList.value = [{ ...raw }, ...clientesList.value];
    }
    return raw;
  }
  return null;
};

const catalogs = computed(() => props.catalogs);
const userAlmacenPredeterminado = computed(() => props.user?.almacen_venta_id || null);
const numeroVentaFijo = ref('V0001');

const getCurrentDate = () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Formulario
const form = useForm({
  numero_venta: numeroVentaFijo,
  fecha: getCurrentDate(),
  cliente_id: '',
  price_list_id: '',
  almacen_id: userAlmacenPredeterminado.value || '',
  vendedor_type: 'App\\Models\\User',
  vendedor_id: '',
  pagado_por_user_id: '',
  metodo_pago: '',
  forma_pago_sat: '',
  metodo_pago_sat: '',
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  retencion_iva: 0,
  retencion_isr: 0,
  total: 0,
  productos: [],
  servicios: [],
  notas: '',
  estado: 'borrador',
  cuenta_bancaria_id: '',
  cita_id: '',
  taller_orden_id: '',
  descuento_general: 0, // Asegurar que existe
});

// Referencias
const buscarClienteRef = ref(null);
const buscarProductoRef = ref(null);

// Estado fiscal
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);
const retencionIvaDefault = computed(() => Number(props.defaults?.retencionIvaDefault || 0));
const retencionIsrDefault = computed(() => Number(props.defaults?.retencionIsrDefault || 0));

// Estado
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serialsMap = ref({});
const clienteSeleccionado = ref(null);
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const requiereConfirmacionMargen = ref(false);
const mensajeAdvertenciaMargen = ref('');
const mostrarModalCliente = ref(false);
const nombreClienteBuscado = ref('');
const showKitComponentsModal = ref(false);
const activeKit = ref(null);
const vendedorSeleccionado = ref('');
const cobradorSeleccionado = ref('');
const vendedoresFiltrados = computed(() => props.vendedores || []);

const seleccionarVendedorPredeterminado = () => {
  const currentUserId = props.user?.id;
  const currentUserInList = vendedoresFiltrados.value.find(v => v.id === currentUserId && v.type === 'user');
  if (currentUserInList) {
    vendedorSeleccionado.value = `user-${currentUserId}`;
  } else {
    const jesusLopez = vendedoresFiltrados.value.find(v => 
      includesSearch(v.nombre, 'jesus') && includesSearch(v.nombre, 'lopez')
    );
    if (jesusLopez) {
      vendedorSeleccionado.value = `${jesusLopez.type}-${jesusLopez.id}`;
    }
  }
  onVendedorChange();
};

const onVendedorChange = () => {
  const sel = vendedorSeleccionado.value;
  if (!sel) {
    form.vendedor_type = '';
    form.vendedor_id = '';
    return;
  }
  const [type, id] = sel.split('-');
  form.vendedor_type = type === 'user' ? 'App\\Models\\User' : 'App\\Models\\Tecnico';
  form.vendedor_id = parseInt(id);

  // ✅ BLINDAJE: Sincronizar almacén predeterminado del vendedor (Fix reportado: ventas saliendo de almacén incorrecto)
  const vendedorObj = props.vendedores.find(v => (v.id === form.vendedor_id && v.type === type));
  if (vendedorObj && vendedorObj.almacen_venta_id) {
    if (form.almacen_id != vendedorObj.almacen_venta_id) {
      form.almacen_id = vendedorObj.almacen_venta_id;
      const alm = props.almacenes.find(a => a.id == vendedorObj.almacen_venta_id);
      if (alm) {
        showNotification(`Almacén cambiado a "${alm.nombre}" por predeterminado del vendedor`, 'info');
      }
    }
  }
};

const onCobradorChange = () => {
  const sel = cobradorSeleccionado.value;
  if (!sel) {
    form.pagado_por_user_id = '';
    return;
  }
  const [type, id] = sel.split('-');
  form.pagado_por_user_id = parseInt(id);
};

// Estado para modal de pago inmediato
const showPaymentConfirmationModal = ref(false);
const metodoPagoInmediato = ref('');
const cuentaBancariaInmediata = ref('');
const notasPagoInmediato = ref('');
const cuentasBancarias = ref([]);
const importeRecibido = ref('');
const cambio = ref(0);
const inputEfectivo = ref(null);


const saveToLocalStorage = (key, data) => {
  try { localStorage.setItem(key, JSON.stringify(data)); } catch (error) { console.warn(error); }
};

const loadFromLocalStorage = (key) => {
  try { const item = localStorage.getItem(key); return item ? JSON.parse(item) : null; } catch (error) { return null; }
};

const removeFromLocalStorage = (key) => {
  try { localStorage.removeItem(key); } catch (error) { console.warn(error); }
};

const saveState = () => {
  saveToLocalStorage('ventaEnProgreso', {
    cliente_id: form.cliente_id,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
  });
};

const validarSoloNumeros = (event) => {
  const char = event.key;
  if (!/[0-9.]/.test(char) && event.key !== 'Backspace' && event.key !== 'Delete' && event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    event.preventDefault();
  }
  if (char === '.' && importeRecibido.value && importeRecibido.value.toString().includes('.')) {
    event.preventDefault();
  }
};

const calcularCambio = () => {
  const total = roundCurrency(form.total);
  const recibido = roundCurrency(parseFloat(importeRecibido.value) || 0);
  cambio.value = roundCurrency(recibido - total);
};

const cargarCuentasBancarias = async () => {
  try {
    const response = await axios.get('/api/cuentas-bancarias/activas');
    cuentasBancarias.value = response.data;
  } catch (error) { console.error(error); }
};

const abrirModalPago = async () => {
  console.log('=== abrirModalPago called ===');
  console.log('form.cliente_id:', form.cliente_id);
  console.log('form.almacen_id:', form.almacen_id);
  console.log('selectedProducts:', selectedProducts.value);
  
  try {
    if (!validarDatos()) {
      console.log('validarDatos returned false');
      return;
    }
    console.log('validarDatos passed');
    
    if (form.price_list_id && !fallbackPriceAccepted.value) {
      console.log('Checking price list products...');
      const productosSinPrecio = detectarProductosSinPrecioEnLista(
        selectedProducts.value,
        props.productos,
        form.price_list_id,
        { serviciosUsanListasPrecios: props.defaults?.serviciosUsanListasPrecios }
      );
      console.log('productosSinPrecio:', productosSinPrecio);
      if (productosSinPrecio.length > 0) {
        fallbackPriceProducts.value = productosSinPrecio;
        showFallbackPriceModal.value = true;
        console.log('Showing fallback price modal');
        return;
      }
    }
    
    calcularTotal();
    metodoPagoInmediato.value = '';
    cuentaBancariaInmediata.value = '';
    notasPagoInmediato.value = '';
    importeRecibido.value = '';
    cambio.value = 0;

    showPaymentConfirmationModal.value = true;
    console.log('Payment confirmation modal should be showing now');
  } catch (error) {
    console.error('Error in abrirModalPago:', error);
    showNotification('Error al abrir modal de pago: ' + error.message, 'error');
  }
};

const cerrarModalPago = () => {
  showPaymentConfirmationModal.value = false;
  metodoPagoInmediato.value = '';
  importeRecibido.value = '';
  cambio.value = 0;

};

const onMetodoPagoChange = () => {
  if (metodoPagoInmediato.value === 'efectivo') {
    importeRecibido.value = '';
    cambio.value = 0;
    nextTick(() => { inputEfectivo.value?.focus(); });
  } else {
    importeRecibido.value = '';
    cambio.value = 0;
  }
};

const crearVentaConPago = () => {
  if (!metodoPagoInmediato.value) { showNotification('Debes seleccionar una forma de pago', 'error'); return; }
  if (metodoPagoInmediato.value === 'efectivo' && roundCurrency(cambio.value) < 0) { showNotification('Importe insuficiente', 'error'); return; }

  form.metodo_pago = metodoPagoInmediato.value;
  const mapeoFormaPagoSat = { 'efectivo': '01', 'transferencia': '03', 'tarjeta': '04', 'cheque': '02', 'credito': '99' };
  form.forma_pago_sat = mapeoFormaPagoSat[metodoPagoInmediato.value] || '99';
  form.metodo_pago_sat = metodoPagoInmediato.value === 'credito' ? 'PPD' : 'PUE';
  if (metodoPagoInmediato.value !== 'efectivo' && metodoPagoInmediato.value !== 'credito' && cuentaBancariaInmediata.value) {
    form.cuenta_bancaria_id = cuentaBancariaInmediata.value;
  } else {
    form.cuenta_bancaria_id = null;
  }


  
  showPaymentConfirmationModal.value = false;
  submitVentaAfterValidation();
};

const { formatCurrency } = useFormatters();

const fetchNextNumeroVenta = async () => {
  try {
    const response = await axios.get('/ventas/siguiente-numero');
    if (response.data && response.data.siguiente_numero) {
      numeroVentaFijo.value = response.data.siguiente_numero;
      form.numero_venta = response.data.siguiente_numero;
    }
  } catch (error) { numeroVentaFijo.value = 'V0001'; form.numero_venta = 'V0001'; }
};

const loadFromPedido = () => {
  if (!props.pedido) return;
  const p = props.pedido;
  showNotification(`Cargando pedido #${p.numero_pedido}...`, 'info');
  if (p.cliente) onClienteSeleccionado(p.cliente);
  form.notas = p.notas ? `[Pedido #${p.numero_pedido}] ${p.notas}` : `Generado desde Pedido #${p.numero_pedido}`;
  
  if (Array.isArray(p.items)) {
    p.items.forEach(item => {
      const tipo = (item.pedible_type && (item.pedible_type.includes('Producto') || item.pedible_type === 'producto')) ? 'producto' : 'servicio';
      let catalogoItem = tipo === 'producto' ? props.productos.find(x => x.id === item.pedible_id) : props.servicios.find(x => x.id === item.pedible_id);
      
      const itemData = catalogoItem ? {
        id: catalogoItem.id, tipo: tipo, nombre: catalogoItem.nombre, precio_venta: catalogoItem.precio_venta, precio: catalogoItem.precio, requiere_serie: catalogoItem.requiere_serie, tipo_producto: catalogoItem.tipo_producto,
      } : {
        id: item.pedible_id, tipo: tipo, nombre: item.pedible?.nombre || 'Item desconocido', precio: item.precio, requiere_serie: false, tipo_producto: item.pedible?.tipo_producto,
      };

      agregarProducto(itemData);
      const key = `${tipo}-${itemData.id}`;
      quantities.value[key] = parseFloat(item.cantidad);
      prices.value[key] = parseFloat(item.precio);
      if (item.descuento) discounts.value[key] = parseFloat(item.descuento);
    });
    calcularTotal();
    if (p.descuento_general) form.descuento_general = parseFloat(p.descuento_general);
  }
};

const loadFromCita = async () => {
  if (!props.cita) return;
  const cit = props.cita;
  form.cita_id = cit.id;
  const cli = mergeClienteDesdeCita(cit);
  if (cli) {
    onClienteSeleccionado(cli);
    await nextTick();
  }
  const refCita = cit.folio ? `Cita #${cit.id} (folio ${cit.folio})` : `Cita #${cit.id}`;
  form.notas = form.notas
    ? `${form.notas}\n\nVenta vinculada al servicio: ${refCita}.`
    : `Venta vinculada al servicio: ${refCita}.`;

  if (!Array.isArray(cit.items) || cit.items.length === 0) {
    showNotification(cli ? 'Cliente del servicio listo. Agrega lo vendido en sitio.' : 'Cita vinculada. Selecciona cliente y productos.', 'info');
    return;
  }

  const skipped = [];
  for (const line of cit.items) {
    const c = line.citable;
    if (!c || !line.citable_id) continue;
    const isProducto = (line.citable_type || '').includes('Producto');
    const tipo = isProducto ? 'producto' : 'servicio';
    const catalogo = isProducto
      ? props.productos.find((x) => x.id === line.citable_id)
      : props.servicios.find((x) => x.id === line.citable_id);
    const itemData = catalogo
      ? { ...catalogo, tipo }
      : {
          id: line.citable_id,
          tipo,
          nombre: c.nombre || c.descripcion || 'Item',
          precio: parseFloat(line.precio) || 0,
          precio_venta: parseFloat(line.precio) || 0,
          requiere_serie: !!(c.requiere_serie),
          tipo_producto: c.tipo_producto,
        };
    if (tipo === 'producto' && itemData.requiere_serie) {
      skipped.push(itemData.nombre || `Producto #${line.citable_id}`);
      continue;
    }
    await agregarProducto(itemData);
    const key = `${tipo}-${line.citable_id}`;
    quantities.value[key] = parseFloat(line.cantidad) || 1;
    prices.value[key] = parseFloat(line.precio) || 0;
    discounts.value[key] = parseFloat(line.descuento) || 0;
  }
  calcularTotal();
  if (skipped.length) {
    form.notas += `\n\nAgrega manualmente series/stock para: ${skipped.join(', ')}.`;
  }
  if (selectedProducts.value.length && form.price_list_id && form.almacen_id) {
    await recalcularPreciosPorLista();
  }
  showNotification('Datos cargados desde la cita. Revisa cantidades y precios antes de finalizar.', 'info');
};

const loadFromTaller = async () => {
  if (!props.taller) return;
  const t = props.taller;
  form.taller_orden_id = t.id;
  
  // Si el cliente no está en la lista inicial (lazy load), lo buscamos o lo usamos de la prop
  const cli = t.cliente;
  if (cli) {
    onClienteSeleccionado(cli);
    await nextTick();
  }
  
  const refTaller = t.folio ? `Orden de Taller #${t.folio}` : `Orden de Taller #${t.id}`;
  form.notas = form.notas
    ? `${form.notas}\n\nVenta vinculada a: ${refTaller}.`
    : `Venta vinculada a: ${refTaller}.`;

  // Obtener parámetros de la URL (si vienen de Taller/Show.vue)
  const urlParams = new URLSearchParams(window.location.search);
  const monto = urlParams.get('monto');
  const concepto = urlParams.get('concepto');

  if (monto && concepto) {
    // Agregar el servicio de taller como un item genérico
    // Buscamos si existe un servicio "Servicio de Taller" o similar en props.servicios
    let servicioTaller = props.servicios.find(s => s.nombre.toLowerCase().includes('taller')) || props.servicios[0];
    
    if (servicioTaller) {
        await agregarProducto({ ...servicioTaller, tipo: 'servicio', nombre: concepto });
        const key = `servicio-${servicioTaller.id}`;
        quantities.value[key] = 1;
        prices.value[key] = parseFloat(monto);
    }
  }
  
  calcularTotal();
  showNotification('Datos cargados desde el taller.', 'info');
};

const handlePreview = () => {
  if (clienteSeleccionado.value && selectedProducts.value.length > 0) mostrarVistaPrevia.value = true;
  else showNotification('Selecciona un cliente y productos', 'error');
};

const updateSerials = (key, serials) => { serialsMap.value[key] = serials; };

// Selector de series
const showSeriesPicker = ref(false);
const pickerKey = ref('');
const pickerProducto = ref(null);
const pickerSeries = ref([]);
const pickerSearch = ref('');
const selectedSeries = ref([]);
const pickerRequiredOverride = ref(null);
const pickerRequired = computed(() => {
  if (pickerRequiredOverride.value !== null) return pickerRequiredOverride.value;
  if (!pickerKey.value) return 0;
  return Number.parseFloat(quantities.value[pickerKey.value]) || 0;
});

const nombreAlmacen = (id) => {
  const a = props.almacenes?.find(x => String(x.id) === String(id));
  return a ? a.nombre : `ID ${id}`;
};

const filteredPickerSeries = computed(() => {
  const q = (pickerSearch.value || '').toLowerCase();
  let list = pickerSeries.value || [];
  if (form.almacen_id) list = list.filter(s => String(s.almacen_id) === String(form.almacen_id));
  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list;
});

const openSerials = async (entry) => {
  // ... (lógica del usuario)
  try {
    pickerRequiredOverride.value = null;
    pickerKey.value = `${entry.tipo}-${entry.id}`;
    pickerProducto.value = props.productos.find(p => p.id === entry.id) || { id: entry.id, nombre: entry.nombre || 'Producto' };
    let url = `/productos/${entry.id}/series?almacen_id=${form.almacen_id}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error(translateErrorMessage('Error network'));
    const data = await res.json();
    pickerSeries.value = data?.series?.en_stock || [];
    selectedSeries.value = (serialsMap.value[pickerKey.value] || []).slice(0, pickerRequired.value);
    showSeriesPicker.value = true;
  } catch (e) { showNotification('Error cargando series', 'error'); }
};

const closeSeriesPicker = () => {
  showSeriesPicker.value = false;
  pickerKey.value = '';
  selectedSeries.value = [];
  pickerRequiredOverride.value = null;
};

const toggleSerie = (numero) => {
  const idx = selectedSeries.value.indexOf(numero);
  if (idx >= 0) selectedSeries.value.splice(idx, 1);
  else if (selectedSeries.value.length < pickerRequired.value) {
    const s = pickerSeries.value.find(x => x.numero_serie === numero);
    if (s && String(form.almacen_id) !== String(s.almacen_id)) { showNotification('La serie es de otro almacén', 'error'); return; }
    selectedSeries.value.push(numero);
    if (selectedSeries.value.length === pickerRequired.value) setTimeout(confirmSeries, 300);
  }
};

const confirmSeries = () => {
  if (!pickerKey.value) return;
  if (selectedSeries.value.length !== pickerRequired.value) { showNotification(`Selecciona ${pickerRequired.value} series`, 'error'); return; }
  
  // Use spread to ensure reactivity triggers in modals
  serialsMap.value = {
    ...serialsMap.value,
    [pickerKey.value]: [...selectedSeries.value]
  };
  
  closeSeriesPicker();
  notyf.success('Series seleccionadas');
};

const handleKitComponentsSeries = (kitEntry) => {
  activeKit.value = props.productos.find(p => p.id === kitEntry.id) || kitEntry;
  showKitComponentsModal.value = true;
};

const openComponentSerials = async (component) => {
  const kitId = String(activeKit.value.id);
  const kitQty = quantities.value[`producto-${kitId}`] || 1;
  const qty = component.cantidad || 1;
  const required = qty * kitQty;
  
  // Try to find the real product ID of the component
  const componentProductId = String(component.item_id || component.producto_id || component.item?.id || component.id);
  
  pickerRequiredOverride.value = required;
  pickerKey.value = `kit-${kitId}-component-${componentProductId}`;
  pickerProducto.value = component.item || component.producto || { id: componentProductId, nombre: 'Componente' };
  
  console.log(`[KIT SERIES] Opening picker for: ${pickerKey.value}`, { required });

  try {
    const url = `/productos/${componentProductId}/series?almacen_id=${form.almacen_id}`;
    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();
    
    // Web controller returns { series: { en_stock: [...] } }
    const seriesArray = data?.series?.en_stock || data?.series || [];
    pickerSeries.value = Array.isArray(seriesArray) ? seriesArray : [];
    selectedSeries.value = (serialsMap.value[pickerKey.value] || []).slice(0, required);
    showSeriesPicker.value = true;
  } catch (e) {
    console.error('[KIT SERIES] Error:', e);
    showNotification('Error cargando series del componente: ' + e.message, 'error');
  }
};

const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null; form.cliente_id = ''; saveState(); return;
  }
  if (clienteSeleccionado.value?.id === cliente.id) return;
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;
  if (cliente.price_list_id) { form.price_list_id = cliente.price_list_id; recalcularPreciosPorLista(); }
  saveState();
};

const onPriceListChange = () => {
  fallbackPriceAccepted.value = false; fallbackPriceProducts.value = []; recalcularPreciosPorLista();
};

const recalcularPreciosPorLista = async () => {
  if (selectedProducts.value.length === 0 || !form.almacen_id) return;
  try {
    const response = await axios.post('/productos/recalcular-precios', {
      productos: selectedProducts.value.map(e => ({ id: e.id, tipo: e.tipo })),
      price_list_id: form.price_list_id || null,
      almacen_id: form.almacen_id
    });
    if (response.data?.precios) {
      Object.keys(response.data.precios).forEach(k => { if(response.data.precios[k] !== undefined) prices.value[k] = parseFloat(response.data.precios[k]); });
      calcularTotal();
      saveState();
      notyf.success('Precios actualizados');
    }
  } catch (e) { console.error(e); }
};

// Cliente del servicio ya seleccionado en el primer render (evita el paso de buscar cliente).
if (props.cita) {
  const cli = mergeClienteDesdeCita(props.cita);
  if (cli) onClienteSeleccionado(cli);
  form.cita_id = props.cita.id;
}

const crearNuevoCliente = (nombre) => { nombreClienteBuscado.value = nombre; mostrarModalCliente.value = true; };
const onClienteCreado = (nuevo) => {
  if (!clientesList.value.some(c => c.id === nuevo.id)) clientesList.value.push(nuevo);
  onClienteSeleccionado(nuevo);
};

const agregarProducto = async (item) => {
  if (!item || !item.id) return;
  const key = `${item.tipo}-${item.id}`;
  const exists = selectedProducts.value.some(p => p.id === item.id && p.tipo === item.tipo);

  if (!exists) {
    selectedProducts.value.push({ ...item, nombre: item.nombre || item.descripcion });
    quantities.value[key] = 1;
    let precio = resolverPrecio(
      item,
      form.price_list_id,
      { serviciosUsanListasPrecios: props.defaults?.serviciosUsanListasPrecios }
    );
    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    notyf.success(`Agregado: ${item.nombre || 'Item'}`);
    if (item.requiere_serie) openSerials(item);
  }
};

const eliminarProducto = (item) => {
  selectedProducts.value = selectedProducts.value.filter(p => !(p.id === item.id && p.tipo === item.tipo));
  const key = `${item.tipo}-${item.id}`;
  delete quantities.value[key]; delete prices.value[key]; delete discounts.value[key];
  calcularTotal(); saveState();
};

const updateQuantity = (key, val) => { quantities.value[key] = val; calcularTotal(); saveState(); };
const updatePrice = (key, val) => { prices.value[key] = parseFloat(val) || 0; calcularTotal(); saveState(); };
const updateDiscount = (key, val) => { discounts.value[key] = val; calcularTotal(); saveState(); };

// Modo de descuento general: 'porcentaje' o 'monto'
const descuentoGeneralTipo = ref('monto');

const totales = computed(() => {
  let subtotal = 0, descuentoItems = 0;
  selectedProducts.value.forEach(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    const qty = parseFloat(quantities.value[key]) || 0;
    const price = parseFloat(prices.value[key]) || 0;
    const disc = parseFloat(discounts.value[key]) || 0;
    const sub = qty * price;
    subtotal += sub;
    descuentoItems += sub * (disc / 100);
  });
  const subTotalDesc = Math.max(0, subtotal - descuentoItems);
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subTotalDesc * ivaRate;
  
  let retIva = 0, retIsr = 0;
  if (aplicarRetencionIva.value) retIva = subTotalDesc * (retencionIvaDefault.value / 100);
  if (aplicarRetencionIsr.value) retIsr = subTotalDesc * (retencionIsrDefault.value / 100);
  else if (props.defaults?.enableIsr && clienteSeleccionado.value?.tipo_persona === 'moral') retIsr = subTotalDesc * ((props.defaults?.isrPorcentaje ?? 1.25) / 100);

  // Calcular descuento general según tipo
  let descuentoGeneralCalc = 0;
  const descGralVal = Number(form.descuento_general) || 0;
  if (descuentoGeneralTipo.value === 'porcentaje') {
    descuentoGeneralCalc = subTotalDesc * (descGralVal / 100);
  } else {
    descuentoGeneralCalc = descGralVal;
  }
  descuentoGeneralCalc = Math.min(descuentoGeneralCalc, subTotalDesc + iva); // No puede exceder el total

  const total = subTotalDesc + iva - retIva - retIsr - descuentoGeneralCalc;
  
  return { subtotal, descuentoItems, subTotalConDescuentos: subTotalDesc, iva, retencion_iva: retIva, retencion_isr: retIsr, descuentoGeneral: descuentoGeneralCalc, total: Math.max(0, total) };
});

const calcularTotal = () => {
  form.subtotal = roundCurrency(totales.value.subtotal);
  form.descuento_items = roundCurrency(totales.value.descuentoItems);
  form.iva = roundCurrency(totales.value.iva);
  form.retencion_iva = roundCurrency(totales.value.retencion_iva);
  form.retencion_isr = roundCurrency(totales.value.retencion_isr);
  form.total = roundCurrency(totales.value.total);
};

const validarDatos = () => {
  if (!form.cliente_id) { 
    showNotification('Debes seleccionar un cliente', 'error'); 
    return false; 
  }
  if (!form.almacen_id) { 
    showNotification('Debes seleccionar un almacén', 'error'); 
    return false; 
  }
  if (selectedProducts.value.length === 0) { 
    showNotification('Debes agregar al menos un producto o servicio', 'error'); 
    return false; 
  }
  return true;
};

const submitVentaAfterValidation = async () => {
  // Preparar arrays para el backend
  const productosParaEnviar = [];
  const serviciosParaEnviar = [];

  selectedProducts.value.forEach((entry) => {
    const key = `${entry.tipo}-${entry.id}`;
    const item = {
      id: entry.id,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };

    if (entry.tipo === 'producto') {
      // Agregar series si existen
      const series = serialsMap.value[key];
      if (series && Array.isArray(series) && series.length > 0) {
        item.series = series;
      }
      
      // Agregar series de componentes de kits
      if (entry.tipo_producto === 'kit') {
          const componentSeries = {};
          Object.keys(serialsMap.value).forEach(mapKey => {
            if (mapKey.startsWith(`kit-${entry.id}-component-`)) {
               const componentId = parseInt(mapKey.split('-').pop());
               const s = serialsMap.value[mapKey];
               if (Array.isArray(s) && s.length > 0) componentSeries[componentId] = s;
            }
          });
          if (Object.keys(componentSeries).length > 0) item.componentes_series = componentSeries;
      }

      productosParaEnviar.push(item);
    } else {
      serviciosParaEnviar.push(item);
    }
  });

  form.productos = productosParaEnviar;
  form.servicios = serviciosParaEnviar;

  // Calcular totales una última vez para asegurar consistencia
  calcularTotal();

  console.log('Enviando venta:', form.data());

  form.post(route('ventas.store'), {
    onSuccess: () => {
      removeFromLocalStorage('ventaEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      serialsMap.value = {};
      clienteSeleccionado.value = null;
      form.reset();
      // Restaurar valores por defecto
      form.fecha = getCurrentDate();
      form.numero_venta = numeroVentaFijo.value;
      if (props.userAlmacenPredeterminado) form.almacen_id = props.userAlmacenPredeterminado;
      
      showNotification('Venta creada exitosamente', 'success');
    },
    onError: (errors) => {
      console.error('Errores al crear venta:', errors);
      
      // ✅ Handle detailed stock errors
      if (errors.stock_type === 'stock_error') {
        stockErrorDetails.value = errors.stock_details || [];
      } else {
        stockErrorDetails.value = [];
      }

      // Recopilar todos los mensajes de error
      const mensajes = [];
      Object.entries(errors).forEach(([key, value]) => {
        // Skip metadata fields
        if (key === 'stock_type' || key === 'stock_details') return;
        
        if (typeof value === 'string') {
          mensajes.push(value);
        } else if (Array.isArray(value)) {
          mensajes.push(...value);
        }
      });
      
      if (mensajes.length > 0) {
        openErrorModal(mensajes);
      } else {
        notyf.error('Hubo un error al guardar la venta.');
      }
    }
  });
};

onMounted(async () => {
    await fetchNextNumeroVenta();
    await cargarCuentasBancarias();
    seleccionarVendedorPredeterminado();
    if (props.pedido) loadFromPedido();
    else if (props.cita) await loadFromCita();
    else if (props.taller) await loadFromTaller();
    // Leer localStorage...
});
</script>

<template>
  <Head title="Nueva Venta" />
  <div class="ventas-create min-h-screen bg-[var(--ui-surface)] transition-colors duration-200 font-sans text-slate-800 dark:text-slate-200">
     
     <div class="max-w-[98%] mx-auto px-4 sm:px-6 lg:px-10 py-10">
        
        <!-- Header Inline Premium -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
            <div class="flex items-center space-x-8">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-br from-brand-500 to-brand-600 rounded-3xl blur opacity-25 group-hover:opacity-50 transition duration-500"></div>
                    <div class="relative w-16 h-16 rounded-3xl flex items-center justify-center shadow-2xl transform transition-all group-hover:scale-105 group-hover:rotate-3" 
                         :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                         <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                         </svg>
                    </div>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white leading-tight uppercase tracking-wider">
                        Nueva <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-blue-600 dark:from-indigo-400 dark:to-blue-400">Venta</span>
                    </h1>
                    <div class="flex items-center mt-2 flex-wrap gap-2">
                         <div v-if="cita" class="px-3 py-1 bg-sky-50 dark:bg-sky-900/20 dark:bg-blue-950/50 rounded-full border border-sky-200 dark:border-sky-800/30 dark:border-blue-800">
                            <span class="text-[10px] font-black text-sky-800 dark:text-sky-200 dark:text-blue-300 uppercase tracking-wide">Vinculada a cita #{{ cita.id }}</span>
                         </div>
                         <div v-if="taller" class="px-3 py-1 bg-brand-50 dark:bg-brand-900/20 rounded-full border border-brand-200 dark:border-brand-800/30">
                            <span class="text-[10px] font-black text-brand-800 dark:text-brand-200 uppercase tracking-wide">Vinculada a Taller #{{ taller.folio || taller.id }}</span>
                         </div>
                         <div class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-800">
                            <span class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide">Folio: {{ form.numero_venta || 'AUTO' }}</span>
                         </div>
                         <div class="flex items-center space-x-2">
                             <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.8)]"></span>
                             <span class="text-[10px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-[0.2em]">En Proceso</span>
                         </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <Link :href="route('ventas.index')" class="px-8 py-3.5 bg-white dark:bg-slate-800 text-[11px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-400 rounded-2xl border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all duration-200 shadow-sm shadow-slate-200/50 dark:shadow-none">Cancelar Operación</Link>
                <button @click="abrirModalPago" class="group relative px-10 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-indigo-600/20 hover:shadow-indigo-600/40 transition-all duration-200 transform hover:shadow-xl hover:shadow-xl active:translate-y-0 active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    Finalizar Venta
                </button>
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-10">
            <!-- Main Content -->
            <div class="xl:col-span-8 space-y-6">
                
                <!-- Datos Generales -->
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-800 dark:to-slate-900 rounded-[2rem] blur opacity-10"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-slate-800/50 overflow-hidden transition-all duration-500 hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center bg-slate-50/30 dark:bg-slate-950/20">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-brand-500/10 text-emerald-600 dark:text-slate-400 mr-4 border border-emerald-500/20 shadow-inner">
                                 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                            </div>
                            <h2 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Información General</h2>
                        </div>
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
                        <!-- Fecha -->
                        <div>
                             <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Fecha</label>
                             <input type="date" v-model="form.fecha" class="w-full bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 dark:text-white focus:border-brand-500 focus:ring-0"/>
                        </div>
                        <!-- Almacén -->
                        <div>
                             <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Almacén</label>
                             <select v-model="form.almacen_id" class="w-full bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 dark:text-white focus:border-brand-500 focus:ring-0">
                                 <option v-for="alm in almacenes" :key="alm.id" :value="alm.id">{{ alm.nombre }}</option>
                             </select>
                        </div>
                         <!-- Lista Precios -->
                        <div>
                             <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Lista de Precios</label>
                             <select v-model="form.price_list_id" @change="onPriceListChange" class="w-full bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 dark:text-white focus:border-brand-500 focus:ring-0">
                                 <option value="">Lista General</option>
                                 <option v-for="pl in priceLists" :key="pl.id" :value="pl.id">{{ pl.nombre }}</option>
                             </select>
                        </div>
                        <!-- Vendedor -->
                        <div>
                             <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Vendedor</label>
                             <select v-model="vendedorSeleccionado" @change="onVendedorChange" class="w-full bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 dark:text-white focus:border-brand-500 focus:ring-0">
                                 <option value="">Seleccionar vendedor...</option>
                                 <option v-for="v in vendedoresFiltrados" :key="`${v.type}-${v.id}`" :value="`${v.type}-${v.id}`">{{ v.nombre }}</option>
                             </select>
                        </div>
                        <!-- Cobrador (Para Mi Corte) -->
                        <div v-if="form.metodo_pago !== 'credito'">
                             <label class="block text-[10px] font-black text-orange-400 dark:text-brand-500 uppercase tracking-wide mb-2">¿Quién tiene el dinero? (Para Mi Corte)</label>
                             <select v-model="cobradorSeleccionado" @change="onCobradorChange" class="w-full bg-orange-50 dark:bg-slate-950 border-2 border-orange-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-700 dark:text-white focus:border-brand-500 focus:ring-0">
                                 <option value="">-- Yo (Usuario actual) --</option>
                                 <option v-for="v in vendedoresFiltrados" :key="`cobrador-${v.type}-${v.id}`" :value="`${v.type}-${v.id}`">{{ v.nombre }}</option>
                             </select>
                        </div>
                    </div>
                  </div>
                </div>

                <!-- Cliente -->
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-blue-200 to-indigo-300 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-[2rem] blur opacity-10"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-slate-800/50 overflow-hidden transition-all duration-500 hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center bg-slate-50/30 dark:bg-slate-950/20">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-brand-500/10 text-blue-600 dark:text-blue-400 mr-4 border border-blue-500/20 shadow-inner">
                                 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <h2 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Cliente</h2>
                        </div>
                        <div class="p-8">
                            <BuscarCliente 
                              ref="buscarClienteRef"
                              :clientes="clientesList"
                              :cliente-seleccionado="clienteSeleccionado"
                              @cliente-seleccionado="onClienteSeleccionado"
                              @crear-nuevo-cliente="crearNuevoCliente"
                            />
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-indigo-200 to-purple-300 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-[2rem] blur opacity-10"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-[2rem] shadow-xl border border-slate-100 dark:border-slate-800/50 overflow-hidden transition-all duration-500 hover:shadow-2xl">
                        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50 flex items-center bg-slate-50/30 dark:bg-slate-950/20">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 mr-4 border border-indigo-500/20 shadow-inner">
                                 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <h2 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Productos y Servicios</h2>
                        </div>
                        <div class="p-8">
                            <div class="mb-8 p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                                 <BuscarProducto
                                    ref="buscarProductoRef"
                                    :productos="productos"
                                    :servicios="servicios"
                                    :almacen-id="form.almacen_id"
                                    :price-list-id="form.price_list_id"
                                    :servicios-usan-listas-precios="props.defaults?.serviciosUsanListasPrecios"
                                    @agregar-producto="agregarProducto"
                                  />
                            </div>
                            <PySSeleccionados
                              :selectedProducts="selectedProducts"
                              :quantities="quantities"
                              :prices="prices"
                              :discounts="discounts"
                              :serials="serialsMap"
                              @eliminar-producto="eliminarProducto"
                              @update-quantity="updateQuantity"
                              @update-price="updatePrice"
                              @update-discount="updateDiscount"
                              @update-serials="updateSerials"
                              @open-serials="openSerials"
                              @open-kit-serials="handleKitComponentsSeries"
                            />
                        </div>
                    </div>
                </div>

                <!-- Notas -->
                 <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800/50 flex items-center bg-slate-50/50 dark:bg-slate-950/20">
                         <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide">Notas Adicionales</h2>
                    </div>
                    <div class="p-8">
                         <textarea v-model="form.notas" rows="4" class="w-full bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-2xl px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-brand-500 focus:ring-0"></textarea>
                    </div>
                  </div>
            </div>

            <!-- Sidebar Sticky -->
             <div class="xl:col-span-4 space-y-6">
                 <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl border-2 border-slate-100 dark:border-slate-800 overflow-hidden sticky top-6">
                     <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800/50" :style="{ background: `linear-gradient(135deg, ${colors.principal}15 0%, ${colors.secundario}05 100%)` }">
                        <h2 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide">Resumen</h2>
                     </div>
                     <div class="p-8 space-y-5">
                        <div class="space-y-3">
                             <div class="flex justify-between text-xs font-bold text-slate-400 dark:text-slate-500 uppercase"><span>Subtotal</span><span>${{ formatNumber(totales.subtotal) }}</span></div>
                             <div class="flex justify-between text-xs font-bold text-rose-500 uppercase" v-if="totales.descuentoItems > 0"><span>Desc. Items</span><span>-${{ formatNumber(totales.descuentoItems) }}</span></div>
                             <!-- Descuento General Input con selector de tipo -->
                             <div class="pt-3 border-t border-dashed border-slate-100 dark:border-slate-800">
                                 <label class="text-[10px] uppercase font-black text-slate-400 mb-2 block">Descuento Global</label>
                                 <div class="flex items-center gap-2">
                                   <input type="number" v-model.number="form.descuento_general" @input="calcularTotal" min="0" :max="descuentoGeneralTipo === 'porcentaje' ? 100 : undefined" class="flex-1 bg-[var(--ui-surface)] border-2 border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-sm font-bold text-slate-900 dark:text-white" />
                                   <div class="flex bg-slate-100 dark:bg-slate-800 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                                     <button type="button" @click="descuentoGeneralTipo = 'monto'; calcularTotal()" :class="['px-2.5 py-2 text-[10px] font-black transition-all', descuentoGeneralTipo === 'monto' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-700']">
                                       $
                                     </button>
                                     <button type="button" @click="descuentoGeneralTipo = 'porcentaje'; calcularTotal()" :class="['px-2.5 py-2 text-[10px] font-black transition-all', descuentoGeneralTipo === 'porcentaje' ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-700']">
                                       %
                                     </button>
                                   </div>
                                 </div>
                                 <div v-if="totales.descuentoGeneral > 0" class="flex justify-between text-xs font-bold text-rose-500 mt-2">
                                   <span>Desc. Global</span><span>-${{ formatNumber(totales.descuentoGeneral) }}</span>
                                 </div>
                             </div>
                             
                             <!-- Retenciones Toggles -->
                             <div class="flex flex-col gap-2 pt-3 border-t border-dashed border-slate-100 dark:border-slate-800">
                                <label class="flex items-center justify-between cursor-pointer group">
                                    <span class="text-[10px] uppercase font-black text-slate-400 group-hover:text-indigo-500 transition-colors">Ret. IVA</span>
                                    <input type="checkbox" v-model="aplicarRetencionIva" @change="calcularTotal" class="rounded-xl border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-brand-500 bg-[var(--ui-surface)] dark:bg-slate-800" />
                                </label>
                                <label class="flex items-center justify-between cursor-pointer group">
                                    <span class="text-[10px] uppercase font-black text-slate-400 group-hover:text-indigo-500 transition-colors">Ret. ISR</span>
                                    <input type="checkbox" v-model="aplicarRetencionIsr" @change="calcularTotal" class="rounded-xl border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-brand-500 bg-[var(--ui-surface)] dark:bg-slate-800" />
                                </label>
                             </div>
                             
                             <div class="pt-3 flex justify-between text-xs font-bold text-slate-400 dark:text-slate-500 uppercase"><span>IVA ({{ props.defaults?.ivaPorcentaje ?? 16 }}%)</span><span>${{ formatNumber(totales.iva) }}</span></div>
                             <div v-if="totales.retencion_iva > 0" class="flex justify-between text-xs font-bold text-brand-500 uppercase"><span>Ret. IVA</span><span>-${{ formatNumber(totales.retencion_iva) }}</span></div>
                             <div v-if="totales.retencion_isr > 0" class="flex justify-between text-xs font-bold text-brand-500 uppercase"><span>Ret. ISR</span><span>-${{ formatNumber(totales.retencion_isr) }}</span></div>
                        </div>
                        <div class="pt-6 border-t-2 border-slate-100 dark:border-slate-800 text-center">
                            <span class="text-[10px] font-black text-indigo-400 uppercase tracking-wide mb-1 block">Total a Pagar</span>
                            <div class="text-4xl font-black text-indigo-600 dark:text-indigo-400 tracking-tighter">${{ formatNumber(totales.total) }}</div>
                        </div>
                        <button @click="abrirModalPago" :disabled="form.processing" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black uppercase tracking-wide rounded-2xl shadow-xl hover:shadow-emerald-500/20 transition-all transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                             Cobrar Venta
                        </button>
                     </div>
                 </div>
             </div>
        </div>
     </div>

     <!-- Modales -->
     <VistaPreviaModal :show="mostrarVistaPrevia" type="venta" :items="selectedProducts" :totals="totales" :cliente="clienteSeleccionado" :notas="form.notas" @close="mostrarVistaPrevia = false" />
     <CrearClienteModal :show="mostrarModalCliente" :catalogs="catalogs" :nombre-inicial="nombreClienteBuscado" @close="mostrarModalCliente = false" @cliente-creado="onClienteCreado" />
     
     <!-- Modal Pago -->
     <VentaPaymentModal
       :show="showPaymentConfirmationModal"
       :total="form.total"
       v-model:metodoPagoInmediato="metodoPagoInmediato"
       v-model:importeRecibido="importeRecibido"
       v-model:cuentaBancariaId="cuentaBancariaInmediata"
       v-model:notasPago="notasPagoInmediato"
       :cambio="cambio"
       :processing="form.processing"
       :inputRef="inputEfectivo"
       :formatNumber="formatNumber"
       :cuentasBancarias="cuentasBancarias"
       @metodo-change="onMetodoPagoChange"
       @importe-change="calcularCambio"
       @cancel="cerrarModalPago"
       @confirm="crearVentaConPago"
     />
     
     <!-- Otros modales (Series, Fallback, Error) -->
     
     <!-- Modal Fallback Price - cuando productos no tienen precio en lista -->
     <VentaFallbackPriceModal
       :show="showFallbackPriceModal"
       :products="fallbackPriceProducts"
       @close="closeFallbackPriceModal"
       @accept="acceptFallbackPriceAndContinue"
     />
     
     <VentaErrorModal
        :show="showErrorModal"
        :messages="errorModalMessages"
        :stock-details="stockErrorDetails"
        @close="closeErrorModal"
      />
     <VentaSeriesPickerModal
      :show="showSeriesPicker"
      :product="pickerProducto"
      :picker-required="pickerRequiredOverride"
      :selected-series="selectedSeries"
      v-model:picker-search="pickerSearch"
      :filtered-picker-series="filteredPickerSeries"
      @close="showSeriesPicker = false"
      @toggle="toggleSerie"
      @confirm="confirmSeries"
    />

     <VentaKitComponentsModal
       :show="showKitComponentsModal"
       :kit="activeKit"
       :quantities="quantities"
       :serials="serialsMap"
       @select-component="openComponentSerials"
       @close="showKitComponentsModal = false"
     />

  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
