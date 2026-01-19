<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import VaultSection from '@/Components/VaultSection.vue';

const props = defineProps({
    poliza: Object,
    clientePoliza: Object,
    clientes: Array,
    servicios: Array,
    equipos: Array,
    planes: {
        type: Array,
        default: () => []
    },
});

const isEditing = computed(() => !!props.poliza);
const showHelpModal = ref(false);
const clienteSeleccionado = ref(null);

// Inicializar cliente seleccionado
onMounted(() => {
    if (props.clientePoliza) {
        clienteSeleccionado.value = props.clientePoliza;
    } else if (props.poliza?.cliente) {
        clienteSeleccionado.value = props.poliza.cliente;
    } else if (form.cliente_id) {
        clienteSeleccionado.value = props.clientes.find(c => c.id == form.cliente_id) || null;
    }
});

const handleClienteSeleccionado = (cliente) => {
    form.cliente_id = cliente ? cliente.id : '';
    clienteSeleccionado.value = cliente;
};

const form = useForm({
    cliente_id: props.poliza?.cliente_id || '',
    nombre: props.poliza?.nombre || '',
    descripcion: props.poliza?.descripcion || '',
    fecha_inicio: props.poliza?.fecha_inicio ? props.poliza.fecha_inicio.split('T')[0] : new Date().toISOString().split('T')[0],
    fecha_fin: props.poliza?.fecha_fin ? props.poliza.fecha_fin.split('T')[0] : '',
    monto_mensual: props.poliza?.monto_mensual || 0,
    dia_cobro: props.poliza?.dia_cobro || 1,
    estado: props.poliza?.estado || 'activa',
    limite_mensual_tickets: props.poliza?.limite_mensual_tickets || '',
    notificar_exceso_limite: props.poliza?.notificar_exceso_limite ?? true,
    renovacion_automatica: props.poliza?.renovacion_automatica ?? true,
    condiciones_especiales: props.poliza?.condiciones_especiales || null,
    notas: props.poliza?.notas || '',
    servicios: props.poliza?.servicios ? props.poliza.servicios.map(s => ({
        id: s.id,
        cantidad: s.pivot.cantidad,
        precio_especial: s.pivot.precio_especial,
    })) : [],
    equipos_cliente: props.poliza?.condiciones_especiales?.equipos_cliente || [],
    sla_horas_respuesta: props.poliza?.sla_horas_respuesta || '',
    // Phase 2
    horas_incluidas_mensual: props.poliza?.horas_incluidas_mensual || '',
    costo_hora_excedente: props.poliza?.costo_hora_excedente || '',
    dias_alerta_vencimiento: props.poliza?.dias_alerta_vencimiento || 30,
    mantenimiento_frecuencia_meses: props.poliza?.mantenimiento_frecuencia_meses || '',
    mantenimiento_dias_anticipacion: props.poliza?.mantenimiento_dias_anticipacion || 7,
    proximo_mantenimiento_at: props.poliza?.proximo_mantenimiento_at ? props.poliza.proximo_mantenimiento_at.split('T')[0] : '',
    generar_cita_automatica: props.poliza?.generar_cita_automatica ?? false,
    visitas_sitio_mensuales: props.poliza?.visitas_sitio_mensuales || '',
    costo_visita_sitio_extra: props.poliza?.costo_visita_sitio_extra || '',
});

const nuevoEquipo = ref({ nombre: '', serie: '' });
const agregarEquipoCliente = () => {
    if (!nuevoEquipo.value.nombre) return;
    form.equipos_cliente.push({ ...nuevoEquipo.value });
    nuevoEquipo.value = { nombre: '', serie: '' };
};

const eliminarEquipoCliente = (index) => {
    form.equipos_cliente.splice(index, 1);
};

const servicioSeleccionado = ref('');
const agregarServicio = () => {
    if (!servicioSeleccionado.value) return;
    const servicio = props.servicios.find(s => s.id === servicioSeleccionado.value);
    if (servicio && !form.servicios.find(s => s.id === servicio.id)) {
        form.servicios.push({
            id: servicio.id,
            nombre: servicio.nombre,
            cantidad: 1,
            precio_especial: Number(servicio.precio) || 0
        });
    }
    servicioSeleccionado.value = '';
};

const eliminarServicio = (index) => {
    form.servicios.splice(index, 1);
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('polizas-servicio.update', props.poliza.id));
    } else {
        form.post(route('polizas-servicio.store'));
    }
};

const selectedPlanId = ref('');

watch(selectedPlanId, (newId) => {
    if (!newId) return;
    
    const plan = props.planes.find(p => p.id === newId);
    if (plan) {
        // Autofill form with plan details
        form.nombre = plan.nombre;
        form.monto_mensual = plan.precio_mensual;
        form.descripcion = plan.descripcion || '';
        form.limite_mensual_tickets = plan.tickets_incluidos || '';
        form.sla_horas_respuesta = plan.sla_horas_respuesta || '';
        form.horas_incluidas_mensual = plan.horas_incluidas || '';
        form.costo_hora_excedente = plan.costo_hora_extra || '';
        
        // Mantenimiento
        form.mantenimiento_frecuencia_meses = plan.mantenimiento_frecuencia_meses || '';
        form.mantenimiento_dias_anticipacion = plan.mantenimiento_dias_anticipacion || 7;
        form.generar_cita_automatica = plan.generar_cita_automatica ? true : false;
        
        // Visitas
        form.visitas_sitio_mensuales = plan.visitas_sitio_mensuales || '';
        form.costo_visita_sitio_extra = plan.costo_visita_sitio_extra || '';
        
        // Configuración adicional
        form.renovacion_automatica = true;
        form.notificar_exceso_limite = true;

        // Autofill Services (Servicios Cubiertos)
        if (plan.incluye_servicios && Array.isArray(plan.incluye_servicios)) {
            const newServices = [];
            plan.incluye_servicios.forEach(item => {
                let serviceId, qty, price;
                
                // Handle both {id: 1, cantidad: 1} and just ID 1 formats
                if (typeof item === 'object' && item !== null) {
                    serviceId = item.id;
                    qty = item.cantidad || 1;
                    price = item.precio_especial ?? 0;
                } else {
                    serviceId = Number(item);
                    qty = 1;
                    price = 0; // Default to 0 (included in plan)
                }

                const catalogService = props.servicios.find(s => s.id === serviceId);
                if (catalogService) {
                    newServices.push({
                        id: serviceId,
                        nombre: catalogService.nombre,
                        cantidad: qty,
                        precio_especial: price
                    });
                }
            });
            form.servicios = newServices;
        }
    }
});

// Auto-calcular fecha fin (1 año) al cambiar fecha inicio
watch(() => form.fecha_inicio, (newDate) => {
    if (newDate && !form.fecha_fin && !isEditing.value) {
        const date = new Date(newDate);
        date.setFullYear(date.getFullYear() + 1);
        form.fecha_fin = date.toISOString().split('T')[0];
    }
});

// Calculadora de Valor Financiero
const resumenFinanciero = computed(() => {
    const costoServicios = form.servicios.reduce((acc, s) => acc + (Number(s.precio_especial) * Number(s.cantidad)), 0);
    // Si el precio especial es 0 (incluido), calculamos cuánto costaría normalmente
    const valorRealMercado = form.servicios.reduce((acc, s) => {
        if (s.precio_especial > 0) return acc + (Number(s.precio_especial) * Number(s.cantidad));
        // Si es 0, buscamos su precio de lista
        const catService = props.servicios.find(cs => cs.id === s.id);
        const precioLista = catService ? Number(catService.precio) : 0;
        return acc + (precioLista * Number(s.cantidad));
    }, 0);
    
    // Sumar costos extras estimados (mantenimientos, visitas)
    // Esto es subjetivo, mejor solo servicios explícitos.
    
    const costoMensualPoliza = Number(form.monto_mensual);
    const ahorroMensual = Math.max(0, valorRealMercado - costoMensualPoliza);
    
    return {
        valorReal: valorRealMercado,
        costoPoliza: costoMensualPoliza,
        ahorro: ahorroMensual,
        porcentajeAhorro: valorRealMercado > 0 ? Math.round((ahorroMensual / valorRealMercado) * 100) : 0
    };
});

const helpSections = [
    {
        title: 'Información General',
        icon: 'briefcase',
        points: [
            '**Nombre del Plan:** Use nombres que el cliente identifique fácilmente (ej. "Póliza Redes Platino").',
            '**Descripción/SLA:** Defina aquí los compromisos de tiempo de respuesta (ej. "Respuesta en < 4 horas").',
            '**Cliente:** Una vez creada la póliza, el cliente queda vinculado permanentemente.'
        ]
    },
    {
        title: 'Alcance Operativo',
        icon: 'check-circle',
        points: [
            '**Servicios Incluidos:** Agregue servicios específicos del catálogo para que el sistema descuente de su saldo mensual.',
            '**Precio Especial:** Puede ajustar el costo unitario de un servicio solo para este contrato.',
            '**Inventario:** Registre los números de serie específicos. Solo estos equipos podrán recibir soporte bajo garantía de póliza.'
        ]
    },
    {
        title: 'Ciclo de Facturación',
        icon: 'money-bill-wave',
        points: [
            '**Monto Mensual:** Es el cargo recurrente que se generará cada mes.',
            '**Día de Cobro:** Día del mes en que se debería emitir la factura o el cobro automático.',
            '**Límite de Tickets:** Si se excede este número, los tickets nuevos podrían marcarse como "Con Costo Excedente".'
        ]
    }
];
</script>

<template>
    <AppLayout :title="isEditing ? 'Editar Póliza' : 'Nueva Póliza'">
        <Head :title="isEditing ? 'Editar Póliza' : 'Nueva Póliza'" />

        <div class="py-6 bg-[#0F172A] min-h-screen text-slate-300">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header con Botón de Ayuda -->
                <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <Link :href="route('polizas-servicio.index')" class="text-blue-400 hover:text-blue-300 text-sm font-semibold flex items-center gap-2 mb-4 transition-all w-fit">
                            <font-awesome-icon icon="arrow-left" /> Volver al listado
                        </Link>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center text-blue-400 shadow-lg shadow-blue-900/40 border border-blue-500/20">
                                 <font-awesome-icon icon="shield-halved" class="text-xl" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-white to-slate-400 bg-clip-text text-transparent">
                                    {{ isEditing ? 'Optimizar Contrato' : 'Nueva Póliza de Servicio' }}
                                </h1>
                                <p class="text-slate-400 text-sm mt-0.5 font-medium">Configure los parámetros operativos y comerciales.</p>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        @click="showHelpModal = true" 
                        type="button"
                        class="flex items-center gap-2 px-5 py-2.5 bg-slate-800 border border-slate-700 hover:border-blue-500/50 text-blue-400 rounded-xl font-bold hover:bg-slate-700 transition-all shadow-lg group"
                    >
                        <font-awesome-icon icon="circle-info" class="group-hover:scale-110 transition-transform" />
                        Guía de Llenado Premium
                    </button>
                </div>

                <!-- Formulario Principal -->
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Columna Izquierda: Configuración Base -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Sección 1: Datos Base -->
                        <div class="bg-slate-800/40 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden">
                            <div class="px-8 py-5 border-b border-slate-700/50 bg-slate-900/30">
                                <h2 class="font-bold text-slate-200 flex items-center gap-2">
                                    <font-awesome-icon icon="briefcase" class="text-blue-500" />
                                    Configuración General
                                </h2>
                            </div>
                            <div class="p-8 space-y-6">
                                <!-- Selector de Plan -->
                                <div v-if="planes && planes.length > 0" class="md:col-span-2 bg-blue-900/10 p-5 rounded-xl border border-blue-500/20 mb-2">
                                    <label class="block text-xs font-black text-blue-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                        <font-awesome-icon icon="wand-magic-sparkles" />
                                        Cargar desde Plantilla (Plan)
                                    </label>
                                    <select v-model="selectedPlanId" class="w-full bg-slate-900/80 border-blue-500/30 rounded-xl h-11 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-semibold text-slate-300">
                                        <option value="" class="bg-slate-900">-- Seleccionar un Plan para autocompletar --</option>
                                        <option v-for="plan in planes" :key="plan.id" :value="plan.id" class="bg-slate-900">
                                            {{ plan.nombre }} ({{ plan.tipo_label }}) - ${{ plan.precio_mensual }}
                                            <template v-if="plan.incluye_servicios && plan.incluye_servicios.length">
                                                [Incluye: {{ plan.incluye_servicios.length }} servicios]
                                            </template>
                                        </option>
                                    </select>
                                    <p class="text-[11px] text-blue-400/80 mt-2 font-medium">
                                        💡 Al seleccionar un plan, se llenarán automáticamente los costos, límites y condiciones.
                                    </p>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <BuscarCliente
                                        :clientes="clientes"
                                        :cliente-seleccionado="clienteSeleccionado"
                                        @cliente-seleccionado="handleClienteSeleccionado"
                                        label-busqueda="Cliente Asociado *"
                                        placeholder-busqueda="Busque por nombre o RFC..."
                                        :deshabilitado="isEditing" 
                                    />
                                    <p v-if="form.errors.cliente_id" class="text-red-500 text-xs mt-2">{{ form.errors.cliente_id }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Nombre de la Póliza *</label>
                                        <input v-model="form.nombre" type="text" placeholder="Ej: Póliza Gold Mantenimiento" required class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-12 text-slate-200 placeholder-slate-600 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-bold">
                                        <p v-if="form.errors.nombre" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.nombre }}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Estado de Póliza</label>
                                        <select v-model="form.estado" class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-12 text-slate-200 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-bold">
                                            <option value="pendiente_pago" class="bg-slate-900">Pendiente de Pago 💳</option>
                                            <option value="activa" class="bg-slate-900">Activa ✅</option>
                                            <option value="inactiva" class="bg-slate-900">Inactiva ⏳</option>
                                            <option value="vencida" class="bg-slate-900">Vencida 🛑</option>
                                            <option value="cancelada" class="bg-slate-900">Cancelada ✖️</option>
                                        </select>
                                        <p v-if="form.errors.estado" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.estado }}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">SLA Respuesta (Horas)</label>
                                        <input v-model="form.sla_horas_respuesta" type="number" placeholder="Ej: 4" class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-12 text-slate-200 placeholder-slate-600 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                                        <p v-if="form.errors.sla_horas_respuesta" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.sla_horas_respuesta }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-2">Alcance y Condiciones del SLA</label>
                                    <textarea v-model="form.descripcion" rows="4" placeholder="Detalle qué incluye la póliza (ej. mantenimientos preventivos, tiempos de respuesta, etc.)" class="w-full bg-slate-900/50 border-slate-700 rounded-xl p-4 text-slate-200 placeholder-slate-600 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all"></textarea>
                                    <p v-if="form.errors.descripcion" class="text-red-400 text-xs mt-1 font-bold">{{ form.errors.descripcion }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Servicios y Equipos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Servicios -->
                            <div class="bg-slate-800/40 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                                    <h2 class="font-bold text-slate-200 text-sm">Servicios Cubiertos</h2>
                                    <font-awesome-icon icon="check-circle" class="text-emerald-500" />
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex gap-2">
                                        <select v-model="servicioSeleccionado" class="flex-1 bg-slate-900/50 border-slate-700 rounded-lg text-sm h-10 text-slate-300">
                                            <option value="" class="bg-slate-900">Agregar servicio...</option>
                                            <option v-for="s in servicios" :key="s.id" :value="s.id" class="bg-slate-900">{{ s.nombre }}</option>
                                        </select>
                                        <button @click="agregarServicio" type="button" class="px-3 bg-blue-600 text-white rounded-lg hover:bg-blue-500 transition-all shadow-lg shadow-blue-900/50">+</button>
                                    </div>
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                        <div v-for="(item, index) in form.servicios" :key="item.id" class="p-3 bg-slate-900/50 rounded-xl border border-slate-700/50 text-xs hover:border-blue-500/30 transition-colors">
                                            <div class="flex justify-between font-bold text-slate-300 mb-2">
                                                <span>{{ item.nombre }}</span>
                                                <button @click="eliminarServicio(index)" type="button" class="text-red-400 hover:text-red-300">✕</button>
                                            </div>
                                            <div class="flex gap-2">
                                                <div class="flex-1">
                                                    <label class="text-[9px] text-slate-500 uppercase font-black block mb-0.5">Cant/Mes</label>
                                                    <input v-model="item.cantidad" type="number" class="w-full bg-slate-800 border-slate-700 rounded h-7 p-1 text-center font-bold text-slate-200" />
                                                </div>
                                                <div class="flex-1">
                                                    <label class="text-[9px] text-slate-500 uppercase font-black block mb-0.5">Precio Esp.</label>
                                                    <input v-model="item.precio_especial" type="number" class="w-full bg-slate-800 border-slate-700 rounded h-7 p-1 text-right font-bold text-slate-200" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipos del Cliente -->
                            <div class="bg-slate-800/40 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden">
                            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                                    <h2 class="font-bold text-slate-200 text-sm">Equipos Cubiertos</h2>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-900/30 text-blue-400 border border-blue-500/20 rounded-full">
                                        {{ form.equipos_cliente.length }} equipos
                                    </span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="space-y-3">
                                        <input v-model="nuevoEquipo.nombre" type="text" placeholder="Nombre (ej: PC Recepción)" class="w-full bg-slate-900/50 border-slate-700 rounded-lg text-xs h-9 text-slate-300 placeholder-slate-600" />
                                        <div class="flex gap-2">
                                            <input v-model="nuevoEquipo.serie" type="text" placeholder="Serie / ID" class="flex-1 bg-slate-900/50 border-slate-700 rounded-lg text-xs h-9 text-slate-300 placeholder-slate-600 font-mono" />
                                            <button @click="agregarEquipoCliente" type="button" class="px-4 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-all text-xs font-bold border border-slate-600 shadow-lg">+</button>
                                        </div>
                                    </div>

                                    <div class="space-y-2 max-h-48 overflow-y-auto pt-2">
                                        <div v-for="(e, index) in form.equipos_cliente" :key="index" class="flex items-center justify-between p-3 bg-slate-900/50 rounded-xl border border-slate-700/50">
                                            <div>
                                                <div class="text-[11px] font-bold text-slate-300">{{ e.nombre }}</div>
                                                <div class="text-[9px] text-slate-500 font-mono italic">S/N: {{ e.serie || 'N/A' }}</div>
                                            </div>
                                            <button @click="eliminarEquipoCliente(index)" type="button" class="text-red-400 hover:text-red-300 p-1">
                                                <font-awesome-icon icon="trash-can" />
                                            </button>
                                        </div>
                                        <div v-if="form.equipos_cliente.length === 0" class="text-center py-6 border-2 border-dashed border-slate-700/50 rounded-xl text-slate-600 text-xs font-medium">
                                            No hay equipos registrados
                                        </div>
                                    </div>
                                </div>
                                
                                <VaultSection 
                                    v-if="props.poliza?.id"
                                    :credentialable-id="props.poliza.id"
                                    credentialable-type="App\Models\PolizaServicio"
                                    :items="props.poliza.credenciales || []"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Administrativo -->
                    <div class="space-y-6">
                        <div class="bg-slate-800/40 backdrop-blur-xl rounded-2xl shadow-xl border border-slate-700/50 overflow-hidden sticky top-6">
                            <div class="px-6 py-4 border-b border-slate-700/50 bg-slate-900/30">
                                <h2 class="font-bold text-slate-200 text-sm">Administración y Ciclos</h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Monto Recurring (MXN) *</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3 text-slate-500 font-bold">$</span>
                                        <input v-model="form.monto_mensual" type="number" step="0.01" required class="w-full pl-8 h-12 bg-slate-900/50 border-slate-700 rounded-xl focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 font-mono font-bold text-emerald-400" />
                                    </div>
                                    
                                    <!-- Widget Ahorro -->
                                    <div v-if="resumenFinanciero.ahorro > 0" class="mt-3 p-3 bg-emerald-900/10 border border-emerald-500/20 rounded-lg">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-[10px] font-bold text-emerald-500 uppercase">Ahorro Cliente</span>
                                            <span class="text-xs font-black text-emerald-400">-{{ resumenFinanciero.porcentajeAhorro }}%</span>
                                        </div>
                                        <div class="flex justify-between items-end">
                                            <div class="text-[10px] text-slate-500">Valor real: <strike>${{ resumenFinanciero.valorReal.toFixed(2) }}</strike></div>
                                            <div class="text-xs font-bold text-emerald-400">Ahorra: ${{ resumenFinanciero.ahorro.toFixed(2) }}/mes</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1.5">Día de Cobro</label>
                                        <select v-model="form.dia_cobro" required class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-11 text-sm font-bold text-slate-300">
                                            <option v-for="d in 31" :key="d" :value="d" class="bg-slate-900">Día {{ d }}</option>
                                        </select>
                                        <p v-if="form.errors.dia_cobro" class="text-red-400 text-[10px] mt-1">{{ form.errors.dia_cobro }}</p>
                                    </div>
                                </div>

                                <!-- Phase 2: Control de Horas -->
                                <div class="pt-3 border-t border-dashed border-slate-700/50">
                                    <div class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                                        <font-awesome-icon icon="clock" />
                                        Control de Servicios
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Soporte Remoto/Mes</label>
                                            <input v-model="form.limite_mensual_tickets" type="number" placeholder="Sin límite" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-bold text-slate-300 placeholder-slate-600" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Visitas Sitio/Mes</label>
                                            <input v-model="form.visitas_sitio_mensuales" type="number" placeholder="0" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-bold text-slate-300 placeholder-slate-600" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Hrs. Incluidas/Mes</label>
                                            <input v-model="form.horas_incluidas_mensual" type="number" placeholder="Sin límite" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-bold text-slate-300 placeholder-slate-600" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Costo Visita Extra</label>
                                            <input v-model="form.costo_visita_sitio_extra" type="number" step="0.01" placeholder="0.00" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-mono text-slate-300 placeholder-slate-600" />
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 mb-1">
                                         <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Costo Hora Excedente</label>
                                         <input v-model="form.costo_hora_excedente" type="number" step="0.01" placeholder="0.00" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-mono text-slate-300 placeholder-slate-600" />
                                    </div>
                                </div>

                                <!-- Días para alertar vencimiento -->
                                <div>
                                    <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Alertar antes de vencer (días)</label>
                                    <select v-model="form.dias_alerta_vencimiento" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs font-bold text-slate-300">
                                        <option :value="7" class="bg-slate-900">7 días</option>
                                        <option :value="15" class="bg-slate-900">15 días</option>
                                        <option :value="30" class="bg-slate-900">30 días</option>
                                        <option :value="45" class="bg-slate-900">45 días</option>
                                        <option :value="60" class="bg-slate-900">60 días</option>
                                    </select>
                                </div>
                                
                                <!-- Mantenimiento Preventivo -->
                                <div class="pt-3 border-t border-dashed border-slate-700/50">
                                    <div class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-3 flex items-center gap-1">
                                        <font-awesome-icon icon="toolbox" />
                                        Mantenimiento Preventivo
                                    </div>
                                    <div class="grid grid-cols-3 gap-3 mb-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Frec. (Meses)</label>
                                            <input v-model="form.mantenimiento_frecuencia_meses" type="number" placeholder="Ej: 6" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-bold text-slate-300 placeholder-slate-600" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Antic. (Días)</label>
                                            <input v-model="form.mantenimiento_dias_anticipacion" type="number" placeholder="7" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs text-center font-bold text-slate-300 placeholder-slate-600" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-500 uppercase mb-1">Próxima Visita</label>
                                            <input v-model="form.proximo_mantenimiento_at" type="date" class="w-full bg-slate-900/50 border-slate-700 rounded-lg h-10 text-xs font-bold text-slate-300 dark-date-input" />
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 p-3 bg-emerald-900/20 rounded-xl border border-emerald-500/20 cursor-pointer hover:bg-emerald-900/30 transition-all mb-3" @click="form.generar_cita_automatica = !form.generar_cita_automatica">
                                        <input type="checkbox" v-model="form.generar_cita_automatica" class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wide">Autogenerar Ticket/Cita</span>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-2">
                                    <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-xl border border-slate-700/50 cursor-pointer hover:bg-blue-900/20 hover:border-blue-500/30 transition-all" @click="form.notificar_exceso_limite = !form.notificar_exceso_limite">
                                        <input type="checkbox" v-model="form.notificar_exceso_limite" class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Notificar excesos</span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-slate-800/50 rounded-xl border border-slate-700/50 cursor-pointer hover:bg-blue-900/20 hover:border-blue-500/30 transition-all" @click="form.renovacion_automatica = !form.renovacion_automatica">
                                        <input type="checkbox" v-model="form.renovacion_automatica" class="rounded bg-slate-900 border-slate-700 text-blue-500 focus:ring-blue-500 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Renovación Auto</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 pt-2">
                                    <div class="flex flex-col">
                                        <label class="text-[9px] font-black text-slate-500 uppercase mb-1">Fecha de Inicio</label>
                                        <input v-model="form.fecha_inicio" type="date" required class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-10 text-xs font-bold text-slate-300 dark-date-input" />
                                        <p v-if="form.errors.fecha_inicio" class="text-red-400 text-[9px] mt-1">{{ form.errors.fecha_inicio }}</p>
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[9px] font-black text-slate-500 uppercase mb-1">Fecha de Término</label>
                                        <input v-model="form.fecha_fin" type="date" class="w-full bg-slate-900/50 border-slate-700 rounded-xl h-10 text-xs font-bold text-slate-300 dark-date-input" />
                                        <p v-if="form.errors.fecha_fin" class="text-red-400 text-[9px] mt-1">{{ form.errors.fecha_fin }}</p>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" :disabled="form.processing" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-black text-sm hover:from-blue-500 hover:to-indigo-500 shadow-xl shadow-blue-900/40 transition-all disabled:opacity-50 flex items-center justify-center gap-2 hover:translate-y-[-1px]">
                                        <font-awesome-icon v-if="form.processing" icon="spinner" spin />
                                        {{ isEditing ? 'ACTUALIZAR CONTRATO' : 'LEGALIZAR PÓLIZA' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal de Ayuda Profesional -->
        <Modal :show="showHelpModal" @close="showHelpModal = false" maxWidth="3xl">
            <div class="p-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center">
                        <font-awesome-icon icon="circle-info" class="text-2xl" />
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-gray-900">Guía de Configuración de Pólizas</h3>
                        <p class="text-gray-500 text-sm">Siga estos estándares para garantizar la rentabilidad y claridad operativa.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-for="section in helpSections" :key="section.title" class="space-y-4">
                        <div class="flex items-center gap-2 text-gray-800">
                             <font-awesome-icon :icon="section.icon" class="text-blue-500" />
                             <h4 class="font-bold uppercase tracking-widest text-xs">{{ section.title }}</h4>
                        </div>
                        <ul class="space-y-3">
                            <li v-for="point in section.points" :key="point" class="text-xs text-gray-600 leading-relaxed flex gap-2">
                                <span class="text-blue-400 font-bold">•</span>
                                <span v-html="point.replace(/\*\*(.*?)\*\*/g, '<b class=\'text-gray-900\'>$1</b>')"></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 p-5 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-yellow-300 text-xl">💡</span>
                        <h4 class="font-bold text-sm tracking-tight">Regla de Oro: Transparencia</h4>
                    </div>
                    <p class="text-xs opacity-90 leading-relaxed">
                        Una póliza bien configurada evita confusiones sobre qué servicios generan costos extra. Asegúrese de que el **SLA** refleje exactamente lo que su equipo técnico puede cumplir de manera realista.
                    </p>
                </div>

                <div class="mt-8 flex justify-end">
                    <button @click="showHelpModal = false" class="px-8 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">
                        Entendido, cerrar guía
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 10px;
}

[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
