<!-- /resources/js/Pages/Ventas/Edit.vue -->
<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
  venta: { type: Object, required: true },
  clientes: { type: Array, required: true },
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  priceLists: { type: Array, default: () => [] },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16, isrPorcentaje: 1.25 }) }, // ✅ FIX: Add ISR/IVA defaults
});

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'info', background: '#3b82f6', icon: false }
  ]
});

// --- Formulario ---
const form = useForm({
  cliente_id: props.venta.cliente.id,
  price_list_id: props.venta.cliente.price_list_id || '',  // ✅ FIX: Usar lista del cliente
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
  retencion_isr: props.venta.retencion_isr || 0
});

// --- Estado reactivo ---
const clientesList = ref([...props.clientes]);
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serials = ref({});
const clienteSeleccionado = ref(props.venta.cliente);
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const showSeriesPicker = ref(false);
const pickerKey = ref('');
const pickerProducto = ref(null);
const pickerSeries = ref([]);
const pickerSearch = ref('');
const selectedSeries = ref([]);

// Estado fiscal
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);
const retencionIvaDefault = computed(() => Number(props.defaults?.retencionIvaDefault || 0));
const retencionIsrDefault = computed(() => Number(props.defaults?.retencionIsrDefault || 0));

const pickerRequired = computed(() => {
  if (pickerRequiredOverride.value !== null) return pickerRequiredOverride.value;
  if (!pickerKey.value) return 0;
  return Number.parseFloat(quantities.value[pickerKey.value]) || 0;
});
const filteredPickerSeries = computed(() => {
  const q = (pickerSearch.value || '').toLowerCase();
  let list = pickerSeries.value || [];

  // Filtrar por almacén de la venta (si existe)
  if (props.venta.almacen_id) {
    list = list.filter(s => String(s.almacen_id) === String(props.venta.almacen_id));
  }

  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list;
});

const nombreAlmacen = (id) => {
  if (!id) return 'N/D';
  const almacen = props.almacenes?.find(a => String(a.id) === String(id));
  return almacen ? almacen.nombre : `ID ${id}`;
};

// --- Cargar datos de la venta ---
// --- Cargar datos de la venta ---
onMounted(() => {
  console.log('Cargando venta desde base de datos:', props.venta);

  // ✅ Usamos items.ventable (relación polimórfica)
  // ✅ Identificar y mapear componentes de kits para evitar duplicados
  const kitComponentIds = new Set();
  const kitComponentSeriesMap = {}; // Map: kitId-componentId -> [series]

  // Primer paso: Buscar kits en la venta y sus componentes esperados
  props.venta.items.forEach(item => {
    if (item.ventable_type === 'App\\Models\\Producto') {
      const productoCatalogo = props.productos.find(p => p.id === item.ventable.id);
      if (productoCatalogo && productoCatalogo.tipo_producto === 'kit' && productoCatalogo.kit_items) {
        
        productoCatalogo.kit_items.forEach(kitItem => {
          if (kitItem.item_type === 'producto' && kitItem.item_id) {
             // Marcar este componente como "perteneciente a un kit"
             // Nota: Esto asume 1:1, si hay multiples kits del mismo tipo podría ser complejo,
             // pero el filtrado evitará que aparezcan como items sueltos.
             kitComponentIds.add(kitItem.item_id); // ID del producto componente

             // Necesitamos asociar las series de los items componentes a este kit
             // Buscaremos los items que correspondan a este componente
             const compItem = props.venta.items.find(i => 
               i.ventable_type === 'App\\Models\\Producto' && 
               i.ventable.id === kitItem.item_id &&
               // Heurística simple: Si el precio es 0, es muy probable que sea componente
               Number(i.precio) === 0
             );

             if (compItem && compItem.series && compItem.series.length > 0) {
                const key = `kit-${item.ventable.id}-component-${kitItem.item_id}`;
                kitComponentSeriesMap[key] = compItem.series.map(s => s.numero_serie);
             }
          }
        });
      }
    }
  });

  // Segundo paso: Procesar items, excluyendo componentes de kit con precio 0
  props.venta.items.forEach(item => {
    const tipo = item.ventable_type === 'App\\Models\\Producto' ? 'producto' : 'servicio';
    
    // Si es un producto que marcamos como componente de kit Y tiene precio 0, lo saltamos
    if (tipo === 'producto' && kitComponentIds.has(item.ventable.id) && Number(item.precio) === 0) {
      console.log(`Skipping component item: ${item.ventable.nombre} (ID: ${item.ventable.id})`);
      return; 
    }

    const key = `${tipo}-${item.ventable.id}`;

    selectedProducts.value.push({ id: item.ventable.id, tipo });
    quantities.value[key] = item.cantidad;
    prices.value[key] = item.precio; // viene de venta_items
    discounts.value[key] = item.descuento || 0;
    
    // ✅ FIX: Registrar price_list_id del item para trazabilidad
    if (item.price_list_id) {
      console.log(`Item ${key} usó price_list_id: ${item.price_list_id}`);
    }
    
    // ✅ Cargar series si existen
    if (item.series && Array.isArray(item.series) && item.series.length > 0) {
      serials.value[key] = item.series
        .map(s => (typeof s === 'string' ? s : s?.numero_serie))
        .filter(Boolean);
    }
  });

  // Fusionar series de componentes de kit recuperadas
  Object.assign(serials.value, kitComponentSeriesMap);

  calcularTotal(); // Aseguramos que los totales se calculen
  notyf.open({ type: 'info', message: 'Datos cargados desde la base de datos' });
  
  // ✅ FIX: Verificar si la venta está pagada y mostrar advertencia
  if (props.venta.pagado) {
    setTimeout(() => {
      notyf.open({ 
        type: 'error', 
        message: '⚠️ Esta venta ya fue marcada como PAGADA y no puede ser modificada.',
        duration: 10000 
      });
    }, 500);
  }

  // ✅ Inicializar estados de retención si la venta ya los tiene
  if (Number(props.venta.retencion_iva) > 0) {
    aplicarRetencionIva.value = true;
  }
  if (Number(props.venta.retencion_isr) > 0) {
    aplicarRetencionIsr.value = true;
  }
});



// --- Eventos de componentes ---
const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    notyf.info('Cliente eliminado');
    return;
  }
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;
  notyf.success(`Cliente: ${cliente.nombre_razon_social || cliente.nombre}`);
  saveToLocalStorage();
};

// --- Watcher para método de pago SAT ---
watch(() => form.metodo_pago, (newVal) => {
  if (newVal === 'credito') {
    form.metodo_pago_sat = 'PPD';
    form.forma_pago_sat = '99';
  } else {
    form.metodo_pago_sat = 'PUE';
    // Mapear forma de pago básica
    const map = {
      'efectivo': '01',
      'transferencia': '03',
      'cheque': '02',
      'tarjeta': '04',
      'otros': '99'
    };
    form.forma_pago_sat = map[newVal] || '01';
  }
});

const onProductosSeleccionados = (productos) => {
  selectedProducts.value = productos;
  saveToLocalStorage();
};

const eliminarProducto = (entry) => {
  if (!entry?.id || !entry?.tipo) {
    notyf.error('Producto inválido');
    return;
  }
  const key = `${entry.tipo}-${entry.id}`;
  selectedProducts.value = selectedProducts.value.filter(
    p => !(p.id === entry.id && p.tipo === entry.tipo)
  );
  delete quantities.value[key];
  delete prices.value[key];
  delete discounts.value[key];
  notyf.info('Producto eliminado');
  saveToLocalStorage();
};

// ✅ Corregido: agregarProducto ahora usa notyf directamente
const agregarProducto = (item) => {
  if (!item || !item.id || !item.tipo) {
    notyf.error('Producto inválido');
    return;
  }

  const key = `${item.tipo}-${item.id}`;
  const exists = selectedProducts.value.some(p => p.id === item.id && p.tipo === item.tipo);

  if (exists) {
    const nombre = item.nombre || item.descripcion || item.titulo || `Elemento ${item.id}`;
    notyf.info(`${nombre} ya está agregado`);
    return;
  }

  // Agregar nuevo producto
  selectedProducts.value.push({ id: item.id, tipo: item.tipo });
  quantities.value[key] = 1;

  // Determinar precio por defecto
  let precio = 0;
  if (item.tipo === 'producto') {
    precio = item.precio_venta || item.precio || 0;
  } else if (item.tipo === 'servicio') {
    precio = item.precio || item.precio_venta || 0;
  }
  prices.value[key] = precio;
  discounts.value[key] = 0;

  const nombre = item.nombre || item.descripcion || item.titulo || `Nuevo ${item.tipo}`;
  notyf.success(`✅ ${nombre} agregado`);

  // Actualizar totales y guardar
  calcularTotal();
  saveToLocalStorage();

  // Si el producto requiere serie, abrir el selector de series inmediatamente
  if (item.requiere_serie) {
    openSerials(item);
  }

  // Si es un kit, verificar si tiene componentes que requieren series
  if (item.tipo === 'producto' && item.tipo_producto === 'kit') {
    handleKitComponentsSeries(item);
  }
};

// --- Cálculos - ✅ FIX: Added ISR calculation for Persona Moral clients ---
const totales = computed(() => {
  let subtotal = 0;

  selectedProducts.value.forEach(item => {
    const key = `${item.tipo}-${item.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;

    const subtotalItem = cantidad * precio;
    subtotal += subtotalItem;
  });

  const descuentoGeneral = parseFloat(form.descuento_general) || 0;
  const subtotalDespuesGeneral = subtotal - descuentoGeneral;

  let descuentoItems = 0;

  selectedProducts.value.forEach(item => {
    const key = `${item.tipo}-${item.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;
    const descuento = parseFloat(discounts.value[key]) || 0;

    const subtotalItem = cantidad * precio;
    const descuentoItem = subtotalItem * (descuento / 100);

    descuentoItems += descuentoItem;
  });

  const subtotalConDescuentos = subtotalDespuesGeneral - descuentoItems;
  const subtotalFinal = subtotalConDescuentos;
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalFinal * ivaRate;

  // Calculate Retencion IVA
  let retencionIva = 0;
  if (aplicarRetencionIva.value) {
    const retIvaRate = retencionIvaDefault.value / 100;
    retencionIva = subtotalFinal * retIvaRate;
  }

  // Calculate ISR (Both legacy automatic and new manual retention)
  let isr = 0; // Legacy
  let retencionIsrMonto = 0; // New explicit

  // Caso 1: Manual
  if (aplicarRetencionIsr.value) {
    const retIsrRate = retencionIsrDefault.value / 100;
    retencionIsrMonto = subtotalFinal * retIsrRate;
  } 
  // Caso 2: Automático PM
  else if (props.defaults?.enableIsr && clienteSeleccionado.value && clienteSeleccionado.value.tipo_persona === 'moral') {
    const isrRate = (props.defaults?.isrPorcentaje ?? 1.25) / 100;
    isr = subtotalFinal * isrRate;
  }

  // Calculate final total
  const total = subtotalFinal + iva - isr - retencionIva - retencionIsrMonto;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuento_items: parseFloat(descuentoItems.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)), 
    descuento_general: parseFloat(descuentoGeneral.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    isr: parseFloat(isr.toFixed(2)), // Legacy
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsrMonto.toFixed(2)),
    total: parseFloat(total.toFixed(2))
  };
});

const calcularTotal = () => {
  const totals = totales.value;
  form.subtotal = totals.subtotal;
  form.iva = totals.iva;
  form.isr = totals.isr;
  form.retencion_iva = totals.retencion_iva;
  form.retencion_isr = totals.retencion_isr;
  form.total = totals.total;
  form.descuento_general = totals.descuento_general; // Ensure it's updated
};

// --- Guardar cambios ---
const actualizarVenta = () => {
  if (!form.cliente_id) {
    notyf.error('Selecciona un cliente');
    return;
  }
  if (selectedProducts.value.length === 0) {
    notyf.error('Agrega al menos un producto o servicio');
    return;
  }

  if (!form.metodo_pago) {
    notyf.error('Selecciona un método de pago');
    return;
  }

  // Validación de cantidades, precios y descuentos
  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const cantidad = parseFloat(quantities.value[key]);
    const precio = parseFloat(prices.value[key]);
    const descuento = parseFloat(discounts.value[key]) || 0;
    const producto = props.productos.find(p => p.id === entry.id);

    if (isNaN(cantidad) || cantidad <= 0) {
      notyf.error(`Cantidad inválida para el producto ID: ${entry.id}`);
      return;
    }
    if (isNaN(precio) || precio < 0) {
      notyf.error(`Precio inválido para el producto ID: ${entry.id}`);
      return;
    }
    if (isNaN(descuento) || descuento < 0 || descuento > 100) {
      notyf.error(`Descuento inválido para el producto ID: ${entry.id}`);
      return;
    }
    if (entry.tipo === "producto" && producto?.requiere_serie) {
      const seriesSeleccionadas = serials.value[key] || [];
      if (!Array.isArray(seriesSeleccionadas) || seriesSeleccionadas.length !== cantidad) {
        notyf.error("El producto " + producto.nombre + " requiere " + cantidad + " series.");
        return;
      }
      const limpias = seriesSeleccionadas.map(s => (s || "").trim()).filter(Boolean);
      if (new Set(limpias).size !== limpias.length) {
        notyf.error("Las series del producto " + producto.nombre + " deben ser unicas.");
        return;
      }
    }

  }

  // Preparar datos para enviar
  form.productos = selectedProducts.value.map(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    const series = (serials.value[key] || [])
      .map(s => (typeof s === 'string' ? s : String(s || '')).trim())
      .filter(Boolean);
    return {
      id: entry.id,
      tipo: entry.tipo,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
      series
    };
    
    // Incluir series de componentes para kits
    if (entry.tipo === 'producto' && (props.productos.find(p => p.id === entry.id)?.tipo_producto === 'kit')) {
      const componentSeries = {};
      Object.keys(serials.value).forEach(mapKey => {
        if (mapKey.startsWith(`kit-${entry.id}-component-`)) {
          const componentId = mapKey.split('-').pop();
          const s = serials.value[mapKey];
          
          // Solo agregar si hay series seleccionadas
          if (Array.isArray(s) && s.length > 0) {
            componentSeries[componentId] = s;
          }
        }
      });
      if (Object.keys(componentSeries).length > 0) {
        item.componentes_series = componentSeries;
      }
    }
    
    return item;
  });

  calcularTotal();

  form.put(route('ventas.update', props.venta.id), {
    onSuccess: () => {
      localStorage.removeItem(`venta_edit_${props.venta.id}`);
      notyf.success('✅ Venta actualizada correctamente');
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      const firstError = Object.values(errors)[0];
      notyf.error(Array.isArray(firstError) ? firstError[0] : firstError);
    }
  });
};

// --- Vista previa e impresión ---
const verVistaPrevia = () => {
  if (!clienteSeleccionado.value || selectedProducts.value.length === 0) {
    notyf.error('Completa cliente y productos');
    return;
  }
  mostrarVistaPrevia.value = true;
};

const imprimirVenta = async () => {
  // Handle null client case
  let clienteParaPDF = clienteSeleccionado.value;
  if (!clienteParaPDF) {
    clienteParaPDF = {
      id: null,
      nombre_razon_social: 'PÚBLICO EN GENERAL',
      email: 'cliente@general.com',
      telefono: 'S/N',
      rfc: 'XAXX010101000',
      calle: 'Calle Principal',
      numero_exterior: 'S/N',
      numero_interior: null,
      colonia: 'Centro',
      codigo_postal: '00000',
      municipio: 'Sin especificar',
      estado: 'Sin especificar',
      pais: 'México',
      regimen_fiscal: 'Sin régimen',
      uso_cfdi: 'Sin uso CFDI',
    };
  }

  const ventaParaPDF = {
    ...props.venta,
    cliente: clienteParaPDF,
    productos: selectedProducts.value.map(entry => {
      const key = `${entry.tipo}-${entry.id}`;
      return {
        ...entry,
        cantidad: quantities.value[key],
        precio: prices.value[key],
        descuento: discounts.value[key] || 0
      };
    }),
    subtotal: totales.value.subtotal,
    descuento_general: form.descuento_general,
    iva: totales.value.iva,
    total: totales.value.total,
    fecha: form.fecha,
    numero_venta: form.numero_venta,
    notas: form.notas
  };

  try {
    notyf.success('📄 Generando PDF...');
    await generarPDF('Venta', ventaParaPDF);
    notyf.success('✅ PDF generado');
  } catch (error) {
    notyf.error('❌ Error al generar PDF: ' + error.message);
    console.error(error);
  }
};

const closeShortcuts = () => {
  mostrarAtajos.value = false;
};

// --- Guardar en localStorage ---
const saveToLocalStorage = () => {
  const data = {
    cliente_id: form.cliente_id,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
    serials: serials.value,
    descuento_general: form.descuento_general,
    notas: form.notas
  };
  localStorage.setItem(`venta_edit_${props.venta.id}`, JSON.stringify(data));
};

// --- Métodos para actualizar cantidad y descuento ---
const updateQuantity = (key, quantity) => {
  quantities.value[key] = quantity;
  calcularTotal();
  saveToLocalStorage();
};

const updateDiscount = (key, discount) => {
  discounts.value[key] = discount;
  calcularTotal();
  saveToLocalStorage();
};

const updateSerials = (key, serialsArray) => {
  serials.value[key] = serialsArray;
  saveToLocalStorage();
};

// --- Selector de series ---
const openSerials = async (entry) => {
  try {
    pickerKey.value = `${entry.tipo}-${entry.id}`;
    pickerProducto.value = props.productos.find(p => p.id === entry.id) || { id: entry.id, nombre: entry.nombre || 'Producto' };

    let url = '';
    try {
      url = route('productos.series', entry.id);
    } catch (e) {
      const base = typeof window !== 'undefined' ? window.location.origin : '';
      url = `${base}/productos/${entry.id}/series`;
    }

    const res = await fetch(url, {
      method: 'GET',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    if (!res.ok) {
      notyf.error('No se pudieron cargar las series');
      return;
    }
    const data = await res.json();
    pickerSeries.value = data?.series?.en_stock || [];
    const prev = serials.value[pickerKey.value] || [];
    selectedSeries.value = Array.isArray(prev) ? prev.slice(0, pickerRequired.value) : [];
    showSeriesPicker.value = true;
  } catch (e) {
    console.error('Error al abrir selector de series:', e);
    notyf.error('Error al abrir selector de series');
  }
};

const closeSeriesPicker = () => {
  showSeriesPicker.value = false;
  pickerKey.value = '';
  pickerProducto.value = null;
  pickerSeries.value = [];
  pickerSearch.value = '';
  selectedSeries.value = [];
};

const toggleSerie = (numero) => {
  const idx = selectedSeries.value.indexOf(numero);
  if (idx >= 0) {
    selectedSeries.value.splice(idx, 1);
  } else if (selectedSeries.value.length < pickerRequired.value) {
    selectedSeries.value.push(numero);
  }
};

const confirmSeries = () => {
  if (!pickerKey.value) return;
  if (selectedSeries.value.length !== pickerRequired.value) {
    notyf.error(`Debes seleccionar ${pickerRequired.value} series`);
    return;
  }
  serials.value[pickerKey.value] = selectedSeries.value.slice();
  saveToLocalStorage();
  closeSeriesPicker();
  notyf.success('Series seleccionadas');
};

// --- Manejo de Kits (Portado de Create.vue con fixes) ---
const handleKitComponentsSeries = async (kit) => {
  try {
    // Obtener detalles del kit con componentes
    const response = await axios.get(`/kits/${kit.id}`);
    const kitData = response.data;

    if (kitData.kit_items && kitData.kit_items.length > 0) {
      // Procesar cada componente que requiere series
      for (const kitItem of kitData.kit_items) {
        // FIX: Usar relación polimórfica correcta y validar tipo
        const componente = kitItem.item;
        
        // Solo procesar si es producto y existe
        if (componente && kitItem.item_type === 'producto' && (componente.requiere_serie || componente.maneja_series || componente.expires)) {
          // Calcular cantidad necesaria para este componente
          const key = `producto-${kit.id}`;
          const cantidadKit = parseFloat(quantities.value[key]) || 1;
          const cantidadNecesaria = kitItem.cantidad * cantidadKit;

          // Abrir selector de series para este componente
          await openSerialsForKitComponent(kit, componente, cantidadNecesaria, kitItem);
        }
      }
    }
  } catch (error) {
    console.error('Error al cargar componentes del kit:', error);
    const mensaje = error.response?.data?.message || 'Error al verificar componentes del kit';
    notyf.error(mensaje);
  }
};

const openSerialsForKitComponent = async (kit, componente, cantidadNecesaria, kitItem) => {
  return new Promise((resolve) => {
    const componentKey = `kit-${kit.id}-component-${componente.id}`;

    // Configurar el picker para este componente
    pickerKey.value = componentKey;
    pickerProducto.value = {
      id: componente.id,
      nombre: `${componente.nombre} (Componente de ${kit.nombre})`,
      tipo: 'producto'
    };
    
    // Hack para override de cantidad requerida (necesitamos adaptar el computed pickerRequired)
    // En Edit.vue pickerRequired depende de quantities[pickerKey]. 
    // Como el componente no está en quantities, necesitamos simularlo o modificar pickerRequired.
    // Modificaremos pickerRequired para soportar un override temporal o usaremos una ref separada.
    // Dado que pickerRequired es computed, vamos a añadir una ref override.
    pickerRequiredOverride.value = cantidadNecesaria;

    // Cargar series del componente filtradas por almacén
    const almacenId = form.almacen_id || props.venta.almacen_id;
    fetch(`/productos/${componente.id}/series?almacen_id=${almacenId}`, {
      method: 'GET',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
      pickerSeries.value = data?.series?.en_stock || [];
      selectedSeries.value = serials.value[componentKey] || [];
      showSeriesPicker.value = true;

      // Esperar a que se cierre el picker
      const checkClosed = setInterval(() => {
        if (!showSeriesPicker.value) {
          clearInterval(checkClosed);
          pickerRequiredOverride.value = null; // Reset override
          resolve();
        }
      }, 100);
    })
    .catch(error => {
      console.error('Error al cargar series del componente:', error);
      notyf.error(`Error al cargar series de ${componente.nombre}`);
      pickerRequiredOverride.value = null;
      resolve();
    });
  });
};

const pickerRequiredOverride = ref(null);
</script>

<template>
  <Head title="Editar Venta" />
  <div class="ventas-edit min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <Header
        title="Editar Venta"
        description="Modifica los detalles de la venta"
        :can-preview="clienteSeleccionado && selectedProducts.length > 0"
        :back-url="route('ventas.index')"
        @preview="verVistaPrevia"
        :show-shortcuts="mostrarAtajos"
        @close-shortcuts="closeShortcuts"
      />

      <!-- ✅ FIX: Banner de advertencia para ventas pagadas -->
      <div 
        v-if="props.venta.pagado" 
        class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-lg shadow-sm"
      >
        <div class="flex items-center">
          <svg class="w-6 h-6 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <div>
            <h3 class="text-lg font-semibold text-red-800">Venta Pagada</h3>
            <p class="text-sm text-red-700 mt-1">
              Esta venta ya fue marcada como <strong>PAGADA</strong> y no puede ser modificada. 
              Si necesitas hacer cambios, primero debes cancelar la venta y crear una nueva.
            </p>
          </div>
        </div>
      </div>

      <form @submit.prevent="actualizarVenta" class="space-y-8">
        <!-- Cliente -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Cliente
            </h2>
          </div>
          <div class="p-6">
            <BuscarCliente
              :clientes="clientesList"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
            />
          </div>
        </div>

        <!-- Productos y Servicios -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
              Productos y Servicios
            </h2>
          </div>
          <div class="p-6">
            <BuscarProducto
              :productos="productos"
              :servicios="servicios"
              :almacen-id="form.almacen_id"
              @agregar-producto="agregarProducto"
            />
            <PySSeleccionados
              :selectedProducts="selectedProducts"
              :productos="productos"
              :servicios="servicios"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              :serials="serials"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
              @update-serials="updateSerials"
              @open-serials="openSerials"
            />
        </div>
</div>
        <!-- Pago -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 bg-white border-b">
            <h2 class="text-lg font-semibold text-gray-800">Pago</h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Lista de Precios -->
            <div>
              <label for="price_list_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Lista de Precios
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01m-.01-6.5h.01M12 2.25a.75.75 0 00-.75.75v1.5c0 .414.336.75.75.75h.75a.75.75 0 00.75-.75V3a.75.75 0 00-.75-.75H12zM12 9a.75.75 0 00-.75.75v1.5c0 .414.336.75.75.75h.75a.75.75 0 00.75-.75V9.75A.75.75 0 0012.75 9H12z"/>
                  </svg>
                  Opcional
                </span>
              </label>
              <div class="relative">
                <select
                  id="price_list_id"
                  v-model="form.price_list_id"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                >
                  <option value="">Lista general (público)</option>
                  <option
                    v-for="priceList in priceLists"
                    :key="priceList.id"
                    :value="priceList.id"
                  >
                    {{ priceList.nombre }}
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Selecciona una lista de precios específica. Si no seleccionas ninguna, se usarán los precios estándar.
              </p>
            </div>

            <div>
              <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2">Método de Pago *</label>
              <select
                id="metodo_pago"
                v-model="form.metodo_pago"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                required
              >
                <option value="">Selecciona un método</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="cheque">Cheque</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otros">Otros</option>
              </select>
              <p class="mt-1 text-xs text-gray-500">Requerido para registrar la venta y generar la entrega automática.</p>
            </div>

            <!-- Campos SAT -->
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
               <div>
                  <label for="metodo_pago_sat" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                    Método de Pago SAT
                    <span class="text-xs font-normal text-gray-400">(PUE / PPD)</span>
                  </label>
                  <select
                    id="metodo_pago_sat"
                    v-model="form.metodo_pago_sat"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="PUE">PUE - Pago en Una sola Exhibición</option>
                    <option value="PPD">PPD - Pago en Parcialidades o Diferido</option>
                  </select>
               </div>
               <div>
                  <label for="forma_pago_sat" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                    Forma de Pago SAT
                    <span class="text-xs font-normal text-gray-400">(Catalogo c_FormaPago)</span>
                  </label>
                  <select
                    id="forma_pago_sat"
                    v-model="form.forma_pago_sat"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="01">01 - Efectivo</option>
                    <option value="02">02 - Cheque nominativo</option>
                    <option value="03">03 - Transferencia electrónica de fondos</option>
                    <option value="04">04 - Tarjeta de crédito</option>
                    <option value="28">28 - Tarjeta de débito</option>
                    <option value="99">99 - Por definir (Obligatorio para PPD)</option>
                  </select>
               </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notas Adicionales
            </h2>
          </div>
          <div class="p-6">
            <textarea
              v-model="form.notas"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
              rows="4"
              placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la venta..."
            ></textarea>
          </div>
        </div>

        <!-- Totales -->
        <Totales
          :show-margin-calculator="false"
          :margin-data="{ costoTotal: 0, precioVenta: 0, ganancia: 0, margenPorcentaje: 0 }"
          :totals="totales"
          :item-count="selectedProducts.length"
          :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (parseFloat(qty) || 0), 0)"
          :iva-porcentaje="props.defaults?.ivaPorcentaje ?? 16"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="Number(props.defaults?.retencionIvaDefault || 0)"
          :retencion-isr-default="Number(props.defaults?.retencionIsrDefault || 0)"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('ventas.index')"
          :is-processing="form.processing"
          :can-submit="form.cliente_id && form.metodo_pago && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Actualizar Venta'"
        />
      </form>

      <!-- Atajos de teclado -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 bg-gray-600 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors duration-200"
        title="Mostrar atajos de teclado"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>

      <!-- Modal Vista Previa -->
      <VistaPreviaModal
        :show="mostrarVistaPrevia"
        type="venta"
        :cliente="clienteSeleccionado"
        :items="selectedProducts"
        :quantities="quantities"
        :prices="prices"
        :discounts="discounts"
        :totals="totales"
        :notas="form.notas"
        @close="mostrarVistaPrevia = false"
        @print="imprimirVenta"
      />

      <!-- Modal Seleccionar Series -->
      <div v-if="showSeriesPicker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeSeriesPicker">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Seleccionar series: {{ pickerProducto?.nombre || '' }}</h3>
            <button @click="closeSeriesPicker" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="p-6">
            <div class="mb-3 text-sm text-gray-700">
              Selecciona exactamente {{ pickerRequired }} {{ pickerRequired === 1 ? 'serie' : 'series' }}. Seleccionadas: {{ selectedSeries.length }}.
            </div>
            <div class="mb-3 grid grid-cols-1 md:grid-cols-2 gap-3">
              <input v-model.trim="pickerSearch" type="text" placeholder="Buscar numero de serie" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
              <div class="text-xs text-gray-500 self-center">
                <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded">En stock: {{ pickerSeries.length }}</span>
              </div>
            </div>
            <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Sel</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Numero de serie</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Almacen</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="s in filteredPickerSeries" :key="s.id">
                    <td class="px-4 py-2 text-sm">
                      <input type="checkbox" :checked="selectedSeries.includes(s.numero_serie)" @change="toggleSerie(s.numero_serie)" :disabled="!selectedSeries.includes(s.numero_serie) && selectedSeries.length >= pickerRequired" />
                    </td>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ s.numero_serie }}</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ nombreAlmacen(s.almacen_id) }}</td>
                  </tr>
                  <tr v-if="filteredPickerSeries.length === 0">
                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Sin series disponibles</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div class="px-6 py-4 bg-white border-t border-gray-200 flex justify-end gap-3">
            <button @click="closeSeriesPicker" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-white">Cancelar</button>
            <button @click="confirmSeries" :disabled="selectedSeries.length !== pickerRequired" class="px-4 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 disabled:opacity-50">Usar {{ selectedSeries.length }}/{{ pickerRequired }} series</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ventas-edit {
  min-height: 100vh;
  background: linear-gradient(to bottom right, #f8fafc, #e0f2fe);
}
</style>

