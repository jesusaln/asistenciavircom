<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick, toRef } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { useNotification } from '@/Composables/useNotification';
import { resolverPrecio } from '@/Utils/precioHelper';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import PaymentModal from './Partials/PaymentModal.vue';
import ExpenseModal from './Partials/ExpenseModal.vue';
import ConfirmationModal from './Partials/ConfirmationModal.vue';
import SeriesModal from './Partials/SeriesModal.vue';
import ClientModal from './Partials/ClientModal.vue';
import CajaAperturaModal from './Partials/CajaAperturaModal.vue';
import CajaCierreModal from './Partials/CajaCierreModal.vue';
import PosHeader from './Partials/PosHeader.vue';
import ItemsTable from './Partials/ItemsTable.vue';
import PosSidebar from './Partials/PosSidebar.vue';
import { usePosCalculations } from './Composables/usePosCalculations';
import { usePosScale } from './Composables/usePosScale';
import { usePosCaja } from './Composables/usePosCaja';
import { usePosSeries } from './Composables/usePosSeries';
import { usePosDraft } from './Composables/usePosDraft';
import { usePosSounds } from './Composables/usePosSounds';
import { usePosParkedSales } from './Composables/usePosParkedSales';
import { usePosOffline } from './Composables/usePosOffline';
import ParkedSalesModal from './Partials/ParkedSalesModal.vue';

const notify = useNotification();
useCompanyColors();

const props = defineProps({
    clientes: Array,
    productos: { type: Array, default: () => [] },
    servicios: { type: Array, default: () => [] },
    catalogs: { type: Object, default: () => ({}) },
    almacenes: { type: Array, default: () => [] },
    priceLists: { type: Array, default: () => [] },
    defaults: { type: Object, default: () => ({ ivaPorcentaje: 16 }) },
    user: Object,
    puedeVenderComponentesSueltos: { type: Boolean, default: false },
});

// Destructure purely for convenience in script, BUT use props.X for reactive computed
const {
    clientes,
    // productos, // Do not destructure to avoid losing reactivity if replaced
    servicios,
    catalogs,
    almacenes,
    priceLists,
    defaults,
    user,
    puedeVenderComponentesSueltos,
} = props;
const defaultsRef = toRef(props, 'defaults');

// POS State
const search = ref('');
const selectedItems = ref([]);
const clienteId = ref('');
const almacenId = ref(user?.almacen_venta_id || almacenes[0]?.id || '');
const priceListId = ref('');
const processing = ref(false);
const showPaymentModal = ref(false);
const showExpenseModal = ref(false);
const paymentMethod = ref('efectivo');
const amountReceived = ref(0);

const headerRef = ref(null);
const focusSearchInput = () => headerRef.value?.focusSearch?.();
const blurSearchInput = () => headerRef.value?.blurSearch?.();
const STORAGE_KEY = 'cdd_pos_premium_draft';
const showClearConfirm = ref(false);
const showDeleteConfirm = ref(false); // 🔥 NUEVO: Confirmar eliminar item
const pendingDeleteIndex = ref(-1);   // 🔥 NUEVO: Índice pendiente de eliminar
const { scaleWeight, scaleActive, tryWeight, isWeighable } = usePosScale(notify);
const { playBeep, playError, playSuccess, playDelete } = usePosSounds();
const { 
    isOnline, 
    pendingSalesCount, 
    saveSaleOffline,
    saveSeriesToCache,
    getSeriesFromCache
} = usePosOffline({ notify });

const { parkedSales, parkSale, removeParkedSale, getParkedSale } = usePosParkedSales(notify);
const showParkedModal = ref(false);

const { formatCurrency, round2, priceWithIva, getDisplayPrice, totals } = usePosCalculations({
    defaults: defaultsRef,
    priceListId,
    selectedItems,
    amountReceived,
});

const {
    cajaAbierta,
    showAperturaModal,
    showCierreModal,
    montoApertura,
    closingDetails,
    loadingCaja,
    denominaciones,
    updateDenominacion,
    totalDeclaradoCalculado,
    checkCajaStatus,
    abrirCaja,
    prepararCierreCaja,
    cerrarCaja,
} = usePosCaja({ almacenId, formatCurrency, notify, focusSearchInput });

// Cliente Modal State
const showClientModal = ref(false);
const clientSearch = ref('');
const filteredClientes = computed(() => {
    const q = clientSearch.value.trim().toLowerCase();
    if (!q) return clientes.slice(0, 50); // Mostrar top 50 por defecto
    return clientes.filter(c => 
        (c.nombre_razon_social || '').toLowerCase().includes(q) || 
        (c.rfc || '').toLowerCase().includes(q) ||
        (c.email || '').toLowerCase().includes(q)
    ).slice(0, 50);
});

const selectCliente = (cliente) => {
    clienteId.value = cliente?.id || '';
    showClientModal.value = false;
    clientSearch.value = '';
    
    // Si el cliente tiene una lista de precios preferida, aplicarla
    if (cliente?.price_list_id) {
        priceListId.value = cliente.price_list_id;
    }
};

const { loadDraft, persistDraft } = usePosDraft({
    storageKey: STORAGE_KEY,
    selectedItems,
    clienteId,
    almacenId,
    priceListId,
    paymentMethod,
    notify,
});

// Search and Results
const searchResults = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (q.length < 1) return [];
    
    // Ensure reactivity by using props.productos
    const list = props.productos || [];
    
    const terms = q.split(' ').filter(term => term.trim() !== '');
    
    return list.filter(p => {
        return terms.every(term => 
            (p.nombre || '').toLowerCase().includes(term) || 
            (p.codigo || '').toLowerCase().includes(term) ||
            (p.descripcion || '').toLowerCase().includes(term)
        );
    }).slice(0, 8); // Limit to top 8 results for speed
});

const selectedResultIndex = ref(0);
const selectedItemIndex = ref(-1); // Índice del producto seleccionado en la tabla
const isAddingItem = ref(false);

watch(search, () => {
    selectedResultIndex.value = 0;
});

// ← NUEVO: Obtener stock del almacén seleccionado
const getLocalStock = (item) => {
    if (!item) return 0;
    if (item.tipo_producto === 'kit') return 999;
    
    // Si no hay almacén seleccionado, stock es 0
    if (!almacenId.value) return 0;
    
    // Si la propiedad stock_almacenes no existe, fallback a global (o 0)
    if (!item.stock_almacenes) return Number(item.stock || 0);

    return Number(item.stock_almacenes[almacenId.value] || 0);
};

const {
    showSeriesModal,
    seriesSearch,
    selectedSeries,
    productForSeries,
    loadingSeries,
    seriesError,
    openSeriesModal,
    retrySeries,
    filteredSeries,
    toggleSerie,
    confirmSeriesSelection,
} = usePosSeries({ almacenId, notify, addItem, saveSeriesToCache, getSeriesFromCache });

// Selección de producto con manejo robusto
const confirmSelection = async (item) => {
    if (!item) return;
    
    if (isAddingItem.value) {
        console.warn('POS: Ya se está agregando un item, ignorando click');
        return;
    }

    // Bloquear si no hay stock local
    const stock = getLocalStock(item);
    
    if (stock <= 0) {
        notify.warning(`Sin stock de "${item.nombre}" en el almacén seleccionado.`);
        playError();
        return;
    }

    // Si requiere serie, abrir modal en lugar de agregar directo
    if (item.requiere_serie) {
        openSeriesModal(item);
        search.value = '';
        return;
    }

    isAddingItem.value = true;

    try {
        await addItem(item, 'producto');
        search.value = '';
        selectedResultIndex.value = 0;
        
        // Reset manual focus
        await nextTick();
        focusSearchInput();
        
    } catch (err) {
        console.error('POS: Error agregando item', err);
        notify.error('No se pudo agregar el producto');
    } finally {
        isAddingItem.value = false;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'F1') { e.preventDefault(); focusSearchInput(); }
    if (e.key === 'F2') { e.preventDefault(); showClientModal.value = true; } // 🔥 NUEVO: Atajo para clientes
    if (e.key === 'F5') { e.preventDefault(); openPayment(); }
    if (e.key === 'F7') { e.preventDefault(); tryWeight(); }
    if (e.key === 'F8') { e.preventDefault(); handleParkSale(); } // 🔥 NUEVO: Pausar venta
    if (e.key === 'F9') { e.preventDefault(); showParkedModal.value = true; } // 🔥 NUEVO: Ver en espera
    if (e.key === 'F10') { e.preventDefault(); showExpenseModal.value = true; } // 🔥 NUEVO: Registrar gastos
    if (e.key === 'F12') { e.preventDefault(); prepararCierreCaja(); }
    if (e.key === 'Delete') { e.preventDefault(); if (selectedItems.value.length > 0) showClearConfirm.value = true; }
    if (e.key === 'Escape') { 
        if (showPaymentModal.value) showPaymentModal.value = false;
        else if (showClearConfirm.value) showClearConfirm.value = false;
        else if (showDeleteConfirm.value) showDeleteConfirm.value = false; // 🔥 NUEVO
        else if (showExpenseModal.value) showExpenseModal.value = false; // 🔥 NUEVO
        else if (showClientModal.value) showClientModal.value = false;
        else if (showSeriesModal.value) showSeriesModal.value = false;
        else if (showParkedModal.value) showParkedModal.value = false;
        else { search.value = ''; focusSearchInput(); }
    }
    
    // 🔥 NUEVO: Manejo de cantidad y navegación en tabla
    handleTableNavigation(e);
};

const handleTableNavigation = (e) => {
    if (selectedItems.value.length === 0) return;

    if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (selectedItemIndex.value > 0) selectedItemIndex.value--;
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (selectedItemIndex.value < selectedItems.value.length - 1) selectedItemIndex.value++;
    } else if (e.key === '+' || e.key === 'Add') { // Integrar teclado numérico
        e.preventDefault();
        if (selectedItemIndex.value >= 0) {
            const item = selectedItems.value[selectedItemIndex.value];
            // 🔥 NUEVO: Si requiere serie, abrir modal para agregar otra
            if (item.requiere_serie) {
                openSeriesModal(item);
            } else {
                item.cantidad++;
                playBeep();
            }
        }
    } else if (e.key === '-' || e.key === 'Subtract') {
        e.preventDefault();
        if (selectedItemIndex.value >= 0) {
            const item = selectedItems.value[selectedItemIndex.value];
            if (!item) return; // Validación adicional
            if (item.cantidad > 1) {
                item.cantidad--;
                playBeep();
            } else {
                // Eliminar si llega a 0 (con confirmación)
                pendingDeleteIndex.value = selectedItemIndex.value;
                showDeleteConfirm.value = true;
            }
        }
    }
};

const confirmDeleteItem = () => {
    if (pendingDeleteIndex.value > -1) {
        removeItem(pendingDeleteIndex.value);
        // Ajustar índice si es necesario
        if (selectedItemIndex.value >= selectedItems.value.length) {
            selectedItemIndex.value = selectedItems.value.length - 1;
        }
        pendingDeleteIndex.value = -1;
    }
    showDeleteConfirm.value = false;
    focusSearchInput();
};

// 🔥 NUEVO: Función para limpiar venta
const clearSale = () => {
    selectedItems.value = [];
    showClearConfirm.value = false;
    localStorage.removeItem(STORAGE_KEY);
    playDelete();
    notify.success('Venta limpiada correctamente');
    focusSearchInput();
};

// Navegación por teclado blindada (async para soportar nextTick)
const handleSearchInputKey = async (e) => {

    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        const count = searchResults.value.length;
        if (count > 0) {
            e.preventDefault();
            const dir = e.key === 'ArrowDown' ? 1 : -1;
            selectedResultIndex.value = (selectedResultIndex.value + dir + count) % count;
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        
        // 🔥 FIX: Forzar sincronización del valor de búsqueda antes de verificar resultados
        const q = e.target.value.trim().toLowerCase();
        
        // Si el search reactivo aún no se actualizó, actualízalo manualmente
        if (search.value !== q) {
            search.value = q;
        }
        
        // Esperar un tick para que Vue actualice el computed
        await nextTick();
        
        const items = searchResults.value;

        if (items.length > 0) {
            // 🔥 FIX: Asegurar que el índice sea válido
            const safeIndex = Math.min(selectedResultIndex.value, items.length - 1);
            const itemToSelect = items[safeIndex] || items[0];
            confirmSelection(itemToSelect);
        } else if (q) {
            // Búsqueda directa por código exacto
            const list = props.productos || [];
            const exact = list.find(p => p.codigo?.toLowerCase() === q);
            if (exact) {
                confirmSelection(exact);
            } else {
                notify.info('Producto no encontrado: ' + q);
            }
        }
    } else if (e.key === 'Escape') {
        search.value = '';
        selectedResultIndex.value = 0;
        blurSearchInput();
        nextTick(() => focusSearchInput());
    }
};

const handleMouseEnterResult = (index) => {
    selectedResultIndex.value = index;
};

// Removed handleMouseDownResult - using standard click+stop

watch(
    () => almacenes,
    (arr) => {
        if (!almacenId.value && arr?.length) {
            almacenId.value = user?.almacen_venta_id || arr[0]?.id || '';
        }
    },
    { immediate: true }
);

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    window.addEventListener('beforeunload', persistDraft);
    window.addEventListener('pagehide', persistDraft);
    
    // 🔥 Restore draft immediately
    loadDraft();
    
    // ← MEJORADO: Asegurar que el componente esté listo y enfocado
    nextTick(() => {
        // Seleccionar cliente por defecto si no hay uno
        if (!clienteId.value && clientes.length > 0) {
            const publicGeneral = clientes.find(c => 
                c.nombre_razon_social?.toUpperCase().includes('PÚBLICO') || 
                c.id === 10
            );
            if (publicGeneral) clienteId.value = publicGeneral.id;
        }

        // Seleccionar primera lista de precios por defecto
        if (!priceListId.value && priceLists.length > 0) {
            priceListId.value = priceLists[0].id;
        }

        // Verificar estado de caja
        checkCajaStatus();

        // Enfocar búsqueda después de inicialización
        setTimeout(focusSearchInput, 150);
    });});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
    window.removeEventListener('beforeunload', persistDraft);
    window.removeEventListener('pagehide', persistDraft);
});

async function addItem(item, tipo, series = []) {
    if (!item) return;
    const key = `${tipo}-${item.id}`;
    const existing = selectedItems.value.find(i => i.key === key);

    let quantity = 1;
    if (tipo === 'producto' && isWeighable(item)) {
        const weight = await tryWeight();
        if (weight !== null && weight > 0) quantity = weight;
    }

    if (existing && series.length === 0) {
        existing.cantidad += quantity;
        playBeep(); // Beep al incrementar cantidad
    } else {
        const precio = resolverPrecio(item, priceListId.value, { 
            serviciosUsanListasPrecios: defaults?.serviciosUsanListasPrecios 
        });
        
        // Si hay series, la cantidad es el número de series
        if (series.length > 0) quantity = series.length;

        selectedItems.value.push({
            key: series.length > 0 ? `${key}-${Date.now()}` : key,
            id: item.id,
            tipo,
            nombre: item.nombre,
            codigo: item.codigo,
            precio,
            cantidad: quantity,
            unidad: item.unidad_medida || 'PZA',
            unidad_medida: item.unidad_medida, // Persistir para reuso
            requiere_serie: item.requiere_serie, // Persistir para lógica de UI
            descuento: 0,
            series: series.map(s => s.numero_serie)
        });
        playBeep(); // Beep al agregar nuevo
    }
    
    // 🔥 Auto-seleccionar el último item agregado
    selectedItemIndex.value = selectedItems.value.length - 1;
    
    nextTick(() => { focusSearchInput(); });
}

const removeItem = (index) => {
    selectedItems.value.splice(index, 1);
    playDelete(); // Sonido al eliminar
};

const clearCart = () => {
    selectedItems.value = [];
    localStorage.removeItem(STORAGE_KEY);
    showClearConfirm.value = false;
    notify.success('Caja limpia');
    focusSearchInput();
};

const handleParkSale = () => {
    if (selectedItems.value.length === 0) {
        notify.error('No hay productos para poner en espera');
        return;
    }

    const clientName = clientes.find(c => c.id === clienteId.value)?.nombre_razon_social;
    
    if (parkSale(selectedItems.value, clienteId.value, priceListId.value, clientName, totals.value, user)) {
        selectedItems.value = []; // Limpiar carrito sin borrar localstorage (se sobreescribirá)
        localStorage.removeItem(STORAGE_KEY);
        // Opcional: Sonido de éxito diferente
        playSuccess(); 
        focusSearchInput();
    }
};

const restoreParkedSale = (index) => {
    if (selectedItems.value.length > 0) {
        if (!confirm('Hay productos en la venta actual. ¿Deseas reemplazarlos por la venta en espera?')) {
            return;
        }
    }

    const sale = getParkedSale(index);
    if (!sale) return;

    // Restaurar estado
    selectedItems.value = [...sale.items];
    clienteId.value = sale.clientId;
    priceListId.value = sale.priceListId;
    
    // Eliminar de espera y cerrar modal
    removeParkedSale(index);
    showParkedModal.value = false;
    
    notify.success('Venta recuperada exitosamente');
    playBeep();
    focusSearchInput();
};

// Actions
const openPayment = () => {
    if (selectedItems.value.length === 0) {
        notify.error('Agrega productos antes de cobrar.');
        return;
    }
    showPaymentModal.value = true;
    amountReceived.value = round2(totals.value.total);
};

const finalizeSale = async (paymentData = null) => {
    if (processing.value) return;
    if (!almacenId.value) {
        notify.error('Selecciona un almacén válido antes de cobrar.');
        return;
    }
    if (selectedItems.value.length === 0) {
        notify.error('Agrega productos antes de cobrar.');
        return;
    }
    
    // Validación de stock pre-checkout (USANDO PRODUCTO ORIGINAL)
    try {
        const itemsProducto = selectedItems.value.filter(i => i.tipo === 'producto');
        for (const item of itemsProducto) {
            const original = (props.productos || []).find(p => p.id === item.id);
            const currentStock = getLocalStock(original);
            
            if (currentStock < item.cantidad) {
                notify.error(`Stock insuficiente de "${item.nombre}". Disponible: ${currentStock}, Solicitado: ${item.cantidad}`);
                playError();
                return;
            }
        }
    } catch (e) {
        console.error('POS: Error validadndo stock:', e);
    }
    
    processing.value = true;

    // Si viene de PaymentModal, paymentData tiene { payments, mainMethod, withTicket }
    const mainMethod = paymentData?.mainMethod || paymentMethod.value;
    const shouldPrint = paymentData?.withTicket || false;
    
    // Preparar desglose de pagos para notas
    let notasAdicionales = '';
    if (paymentData?.payments?.length > 1) {
        const fmt = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v);
        const desgloseStr = paymentData.payments
            .map(p => `${p.method.toUpperCase()}: ${fmt(p.amount)}`)
            .join(' | ');
        notasAdicionales = `\n[PAGOS: ${desgloseStr}]`;
    }

    const form = {
        cliente_id: clienteId.value || null,
        almacen_id: almacenId.value,
        price_list_id: priceListId.value || null,
        metodo_pago: mainMethod,
        source: 'pos', 
        productos: selectedItems.value.filter(i => i.tipo === 'producto').map(i => ({
            id: i.id,
            cantidad: i.cantidad,
            precio: i.precio,
            descuento: i.descuento || 0,
            series: i.series || []
        })),
        servicios: selectedItems.value.filter(i => i.tipo === 'servicio').map(i => ({
            id: i.id,
            cantidad: i.cantidad,
            precio: i.precio,
            descuento: i.descuento || 0
        })),
        total: totals.value.total,
        estado: 'aprobada',
        notas: (props.defaults?.notas_default || '') + notasAdicionales
    };

    try {
        const response = await axios.post(route('pos.checkout'), form, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        console.log('POS Index [10]: Respuesta PHP:', response.status, response.data);
        
        if (response.data?.success === true || response.status === 201) {
            notify.success('Venta realizada con éxito - Folio: ' + (response.data?.data?.numero_venta || 'Generado'));
            
            // 🔥 Lógica de Impresión de Ticket
            if (shouldPrint && response.data?.data?.id) {
                window.open(route('ventas.ticket', response.data.data.id), '_blank');
            }

            selectedItems.value = [];
            localStorage.removeItem(STORAGE_KEY);
            showPaymentModal.value = false;
            search.value = '';
            playSuccess();
            focusSearchInput();
        } else {
            console.error('POS: Respuesta inesperada del servidor:', response);
            notify.error('Respuesta inesperada del servidor');
        }
    } catch (error) {
        console.error('POS: Error en checkout:', error);
        
        const errorMsg = error.response?.data?.message || error.message || 'Error desconocido';
        
        if (!isOnline.value || error.code === 'ERR_NETWORK' || errorMsg.includes('Network Error')) {
            const saved = await saveSaleOffline(form);
            if (saved) {
                selectedItems.value = [];
                localStorage.removeItem(STORAGE_KEY);
                showPaymentModal.value = false;
                search.value = '';
                playSuccess(); // Sonido de éxito (venta guardada)
                focusSearchInput();
                return; // Salir, ya se manejó
            }
        }
        
        // Mostrar el error de forma agresiva para que el usuario lo vea
        notify.error(errorMsg);
        
        // Si es error de stock, tal vez sea útil loggearlo específicamente
        if (errorMsg.includes('Stock insuficiente')) {
            console.warn('Alerta de Inventario:', errorMsg);
        }
    } finally {
        processing.value = false;
    }
};

const saveExpense = async (expenseData) => {
    if (processing.value) return;
    processing.value = true;

    try {
        const payload = {
            ...expenseData,
            fecha: new Date().toISOString().split('T')[0],
        };

        await axios.post(route('caja-chica.store'), payload);
        
        notify.success('Movimiento registrado correctamente.');
        showExpenseModal.value = false;
        
        // Actualizar detalles de cierre si el modal está abierto o para tener datos frescos
        checkCajaStatus();
    } catch (error) {
        console.error('Error al registrar movimiento:', error);
        notify.error('No se pudo registrar el movimiento.');
    } finally {
        processing.value = false;
    }
};

</script>

<template>
    <Head title="Caja POS Premium" />
    
    <div class="h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-black text-white flex flex-col overflow-hidden font-sans">
        <div class="pos-container flex-1 flex flex-col overflow-hidden min-h-0">
        
        <PosHeader
            ref="headerRef"
            :almacenes="almacenes"
            :almacen-id="almacenId"
            :caja-abierta="cajaAbierta"
            :is-online="isOnline"
            :pending-sales-count="pendingSalesCount"
            :search="search"
            :search-results="searchResults"
            :selected-result-index="selectedResultIndex"
            :is-adding-item="isAddingItem"
            :price-lists="priceLists"
            :price-list-id="priceListId"
            :clientes="clientes"
            :cliente-id="clienteId"
            :user="user"
            :puede-vender-componentes-sueltos="puedeVenderComponentesSueltos"
            :get-local-stock="getLocalStock"
            :format-currency="formatCurrency"
            :get-display-price="getDisplayPrice"
            @update:search="(val) => { search = val; }"
            @update:priceListId="(val) => { priceListId = val; }"
            @open-client-modal="showClientModal = true"
            @prepare-cierre="prepararCierreCaja"
            @search-keydown="handleSearchInputKey"
            @select-result="confirmSelection"
            @hover-result="handleMouseEnterResult"
        />

        <!-- Main Content Area -->
        <main class="flex-1 flex overflow-hidden min-h-0">
            
            <ItemsTable
                :selected-items="selectedItems"
                :selected-index="selectedItemIndex"
                :format-currency="formatCurrency"
                :price-with-iva="priceWithIva"
                :round2="round2"
                @remove="removeItem"
                @select="(index) => selectedItemIndex = index"
            />

            <PosSidebar
                :totals="totals"
                :selected-items-count="selectedItems.length"
                :defaults="defaults"
                :format-currency="formatCurrency"
                :scale-weight="scaleWeight"
                :scale-active="scaleActive"
                :parked-sales-count="parkedSales.length"
                @try-weight="tryWeight"
                @open-payment="openPayment"
                @prepare-cierre="prepararCierreCaja"
                @park-sale="handleParkSale"
                @open-parked="showParkedModal = true"
            />
        </main>
        </div> <!-- End pos-container -->

        <footer class="shrink-0 border-t border-white/10 bg-slate-950/90 backdrop-blur-md px-4 py-2 flex flex-wrap items-center justify-center gap-x-6 gap-y-1 text-[10px] text-slate-500 tracking-wide">
            <span class="inline-flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded-md bg-slate-800/90 border border-white/10 text-slate-300 font-mono text-[9px] shadow-inner">F1</kbd> Buscar</span>
            <span class="inline-flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded-md bg-slate-800/90 border border-white/10 text-slate-300 font-mono text-[9px] shadow-inner">F2</kbd> Cliente</span>
            <span class="inline-flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded-md bg-purple-900/50 border border-purple-500/30 text-purple-300 font-mono text-[9px] shadow-inner">F5</kbd> Cobrar</span>
            <span class="inline-flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded-md bg-slate-800/90 border border-white/10 text-slate-300 font-mono text-[9px] shadow-inner">F8</kbd> Pausar</span>
            <span class="inline-flex items-center gap-1.5"><kbd class="px-1.5 py-0.5 rounded-md bg-slate-800/90 border border-white/10 text-slate-300 font-mono text-[9px] shadow-inner">F12</kbd> Corte</span>
        </footer>
        
        <!-- Payment Modal (Modularized) -->
        <PaymentModal 
            v-model:show="showPaymentModal"
            :total="totals.total"
            :processing="processing"
            @confirm="finalizeSale"
        />

        <ExpenseModal 
            v-model:show="showExpenseModal"
            :processing="processing"
            @confirm="saveExpense"
        />

        <!-- Confirmation Modal for Clearing Cart -->
        <ConfirmationModal
            :show="showClearConfirm"
            title="¿Limpiar Caja?"
            message="Se eliminarán todos los productos de la lista actual. Esta acción no se puede deshacer."
            confirm-text="Sí, limpiar todo"
            cancel-text="Cancelar"
            variant="danger"
            @confirm="clearCart"
            @cancel="showClearConfirm = false"
        />

        <SeriesModal
            v-model:show="showSeriesModal"
            v-model:series-search="seriesSearch"
            :product-name="productForSeries?.nombre || ''"
            :loading="loadingSeries"
            :error="seriesError"
            :filtered-series="filteredSeries"
            :selected-series="selectedSeries"
            @toggle="toggleSerie"
            @confirm="confirmSeriesSelection"
            @retry="retrySeries"
        />

        <ClientModal
            v-model:show="showClientModal"
            v-model:client-search="clientSearch"
            :clientes="clientes"
            :filtered-clientes="filteredClientes"
            :selected-cliente-id="clienteId"
            @select="selectCliente"
        />
    </div>

    <CajaAperturaModal
        :show="showAperturaModal"
        v-model:monto-apertura="montoApertura"
        :loading="loadingCaja"
        @open="abrirCaja"
    />

    <CajaCierreModal
        v-model:show="showCierreModal"
        :loading="loadingCaja"
        :denominaciones="denominaciones"
        :closing-details="closingDetails"
        :total-declarado="totalDeclaradoCalculado"
        :format-currency="formatCurrency"
        @update-denominacion="updateDenominacion"
        @confirm="cerrarCaja(false)"
    />

    <ParkedSalesModal
        v-model:show="showParkedModal"
        :parked-sales="parkedSales"
        :format-currency="formatCurrency"
        @restore="restoreParkedSale"
        @delete="removeParkedSale"
    />

    <ConfirmationModal
        :show="showClearConfirm"
        title="¿Limpiar venta?"
        message="Se eliminarán todos los productos de la venta actual. Esta acción no se puede deshacer."
        confirm-text="Sí, limpiar todo"
        variant="danger"
        @cancel="showClearConfirm = false"
        @confirm="clearSale"
    />

    <ConfirmationModal
        :show="showDeleteConfirm"
        title="¿Eliminar producto?"
        message="El producto se eliminará de la venta actual."
        confirm-text="Sí, eliminar"
        variant="danger"
        @cancel="showDeleteConfirm = false"
        @confirm="confirmDeleteItem"
    />
</template>

<style scoped>
/* Custom keyframes for premium transitions */
.pos-container {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.5);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(71, 85, 105, 0.5);
    border-radius: 99px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.8);
}
</style>
