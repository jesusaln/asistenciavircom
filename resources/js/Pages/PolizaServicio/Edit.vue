<script setup>
import { ref, computed, onMounted } from 'vue';
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
});

const isEditing = computed(() => !!props.poliza);
const showHelpModal = ref(false);
const clienteSeleccionado = ref(null);

// Debug para desarrollo
onMounted(() => {
    console.log('Edit Poliza Mounted');
    console.log('Cliente from Prop:', props.clientePoliza);
    
    // Inicializar cliente seleccionado
    if (props.clientePoliza) {
        clienteSeleccionado.value = props.clientePoliza;
    } else if (props.poliza?.cliente) {
        clienteSeleccionado.value = props.poliza.cliente;
    } else if (form.cliente_id) {
        clienteSeleccionado.value = props.clientes.find(c => c.id == form.cliente_id) || null;
    }
    
    console.log('Cliente Seleccionado Final:', clienteSeleccionado.value);
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
            precio_especial: servicio.precio
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

        <div class="py-6 bg-gray-50/50 min-h-screen">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header con Botón de Ayuda -->
                <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <Link :href="route('polizas-servicio.index')" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center gap-2 mb-4 transition-all w-fit">
                            <font-awesome-icon icon="arrow-left" /> Volver al listado
                        </Link>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-100">
                                 <font-awesome-icon icon="shield-halved" class="text-xl" />
                            </div>
                            <div>
                                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                                    {{ isEditing ? 'Optimizar Contrato' : 'Nueva Póliza de Servicio' }}
                                </h1>
                                <p class="text-gray-500 text-sm mt-0.5 font-medium">Configure los parámetros operativos y comerciales.</p>
                            </div>
                        </div>
                    </div>
                    
                    <button 
                        @click="showHelpModal = true" 
                        type="button"
                        class="flex items-center gap-2 px-5 py-2.5 bg-white border-2 border-blue-100 text-blue-600 rounded-xl font-bold hover:bg-blue-50 transition-all shadow-sm group"
                    >
                        <font-awesome-icon icon="circle-info" class="group-hover:scale-110 transition-transform" />
                        Guía de Llenado Profesional
                    </button>
                </div>

                <!-- Formulario Principal -->
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Columna Izquierda: Configuración Base -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Sección 1: Datos Base -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-8 py-5 border-b border-gray-100 bg-gray-50/30">
                                <h2 class="font-bold text-gray-800 flex items-center gap-2">
                                    <font-awesome-icon icon="briefcase" class="text-gray-400" />
                                    Configuración General
                                </h2>
                            </div>
                            <div class="p-8 space-y-5">
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
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Nombre de la Póliza *</label>
                                        <input v-model="form.nombre" type="text" placeholder="Ej: Póliza Gold Mantenimiento" required class="w-full border-gray-200 rounded-xl h-12 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold">
                                        <p v-if="form.errors.nombre" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.nombre }}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Estado de Póliza</label>
                                        <select v-model="form.estado" class="w-full border-gray-200 rounded-xl h-12 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold">
                                            <option value="pendiente_pago">Pendiente de Pago 💳</option>
                                            <option value="activa">Activa ✅</option>
                                            <option value="inactiva">Inactiva ⏳</option>
                                            <option value="vencida">Vencida 🛑</option>
                                            <option value="cancelada">Cancelada ✖️</option>
                                        </select>
                                        <p v-if="form.errors.estado" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.estado }}</p>
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">SLA Respuesta (Horas)</label>
                                        <input v-model="form.sla_horas_respuesta" type="number" placeholder="Ej: 4" class="w-full border-gray-200 rounded-xl h-12 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-mono">
                                        <p v-if="form.errors.sla_horas_respuesta" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.sla_horas_respuesta }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Alcance y Condiciones del SLA</label>
                                    <textarea v-model="form.descripcion" rows="4" placeholder="Detalle qué incluye la póliza (ej. mantenimientos preventivos, tiempos de respuesta, etc.)" class="w-full border-gray-200 rounded-xl p-4 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"></textarea>
                                    <p v-if="form.errors.descripcion" class="text-red-500 text-xs mt-1 font-bold">{{ form.errors.descripcion }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Servicios y Equipos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Servicios -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h2 class="font-bold text-gray-800 text-sm">Servicios Cubiertos</h2>
                                    <font-awesome-icon icon="check-circle" class="text-green-500" />
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="flex gap-2">
                                        <select v-model="servicioSeleccionado" class="flex-1 border-gray-200 rounded-lg text-sm h-10">
                                            <option value="">Agregar servicio...</option>
                                            <option v-for="s in servicios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                        </select>
                                        <button @click="agregarServicio" type="button" class="px-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">+</button>
                                    </div>
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                        <div v-for="(item, index) in form.servicios" :key="item.id" class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-xs">
                                            <div class="flex justify-between font-bold text-gray-700 mb-2">
                                                <span>{{ item.nombre }}</span>
                                                <button @click="eliminarServicio(index)" type="button" class="text-red-400 hover:text-red-600">✕</button>
                                            </div>
                                            <div class="flex gap-2">
                                                <div class="flex-1">
                                                    <label class="text-[9px] text-gray-400 uppercase font-black block mb-0.5">Cant/Mes</label>
                                                    <input v-model="item.cantidad" type="number" class="w-full border-gray-200 rounded h-7 p-1 text-center font-bold" />
                                                </div>
                                                <div class="flex-1">
                                                    <label class="text-[9px] text-gray-400 uppercase font-black block mb-0.5">Precio Esp.</label>
                                                    <input v-model="item.precio_especial" type="number" class="w-full border-gray-200 rounded h-7 p-1 text-right font-bold" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipos del Cliente -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h2 class="font-bold text-gray-800 text-sm">Equipos Cubiertos</h2>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-50 text-blue-600 rounded-full">
                                        {{ form.equipos_cliente.length }} equipos
                                    </span>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="space-y-3">
                                        <input v-model="nuevoEquipo.nombre" type="text" placeholder="Nombre (ej: PC Recepción)" class="w-full border-gray-200 rounded-lg text-xs h-9" />
                                        <div class="flex gap-2">
                                            <input v-model="nuevoEquipo.serie" type="text" placeholder="Serie / ID" class="flex-1 border-gray-200 rounded-lg text-xs h-9 font-mono" />
                                            <button @click="agregarEquipoCliente" type="button" class="px-4 bg-gray-800 text-white rounded-lg hover:bg-black transition-all text-xs font-bold">+</button>
                                        </div>
                                    </div>

                                    <div class="space-y-2 max-h-48 overflow-y-auto pt-2">
                                        <div v-for="(e, index) in form.equipos_cliente" :key="index" class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div>
                                                <div class="text-[11px] font-bold text-gray-700">{{ e.nombre }}</div>
                                                <div class="text-[9px] text-gray-400 font-mono italic">S/N: {{ e.serie || 'N/A' }}</div>
                                            </div>
                                            <button @click="eliminarEquipoCliente(index)" type="button" class="text-red-400 hover:text-red-600 p-1">
                                                <font-awesome-icon icon="trash-can" />
                                            </button>
                                        </div>
                                        <div v-if="form.equipos_cliente.length === 0" class="text-center py-6 border-2 border-dashed border-gray-100 rounded-xl text-gray-300 text-xs font-medium">
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
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-6">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h2 class="font-bold text-gray-800 text-sm">Administración y Ciclos</h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1.5">Monto Recurring (MXN) *</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3 text-gray-400 font-bold">$</span>
                                        <input v-model="form.monto_mensual" type="number" step="0.01" required class="w-full pl-8 h-12 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 font-mono font-bold text-blue-600" />
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1.5">Día de Cobro</label>
                                        <select v-model="form.dia_cobro" required class="w-full border-gray-200 rounded-xl h-11 text-sm font-bold">
                                            <option v-for="d in 31" :key="d" :value="d">Día {{ d }}</option>
                                        </select>
                                        <p v-if="form.errors.dia_cobro" class="text-red-500 text-[10px] mt-1">{{ form.errors.dia_cobro }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1.5">Límite Tkt</label>
                                        <input v-model="form.limite_mensual_tickets" type="number" placeholder="∞" class="w-full border-gray-200 rounded-xl h-11 text-center font-bold" />
                                        <p v-if="form.errors.limite_mensual_tickets" class="text-red-500 text-[10px] mt-1">{{ form.errors.limite_mensual_tickets }}</p>
                                    </div>
                                </div>

                                <!-- Phase 2: Control de Horas -->
                                <div class="pt-3 border-t border-dashed">
                                    <div class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1">
                                        <font-awesome-icon icon="clock" />
                                        Control por Horas
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">Hrs. Incluidas/Mes</label>
                                            <input v-model="form.horas_incluidas_mensual" type="number" placeholder="Sin límite" class="w-full border-gray-200 rounded-lg h-10 text-xs text-center font-bold" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">$ Hora Extra</label>
                                            <input v-model="form.costo_hora_excedente" type="number" step="0.01" placeholder="0.00" class="w-full border-gray-200 rounded-lg h-10 text-xs text-center font-mono" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Días para alertar vencimiento -->
                                <div>
                                    <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">Alertar antes de vencer (días)</label>
                                    <select v-model="form.dias_alerta_vencimiento" class="w-full border-gray-200 rounded-lg h-10 text-xs font-bold">
                                        <option :value="7">7 días</option>
                                        <option :value="15">15 días</option>
                                        <option :value="30">30 días</option>
                                        <option :value="45">45 días</option>
                                        <option :value="60">60 días</option>
                                    </select>
                                </div>
                                
                                <!-- Mantenimiento Preventivo -->
                                <div class="pt-3 border-t border-dashed">
                                    <div class="text-[10px] font-black text-green-600 uppercase tracking-widest mb-3 flex items-center gap-1">
                                        <font-awesome-icon icon="toolbox" />
                                        Mantenimiento Preventivo
                                    </div>
                                    <div class="grid grid-cols-3 gap-3 mb-3">
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">Frec. (Meses)</label>
                                            <input v-model="form.mantenimiento_frecuencia_meses" type="number" placeholder="Ej: 6" class="w-full border-gray-200 rounded-lg h-10 text-xs text-center font-bold" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">Antic. (Días)</label>
                                            <input v-model="form.mantenimiento_dias_anticipacion" type="number" placeholder="7" class="w-full border-gray-200 rounded-lg h-10 text-xs text-center font-bold" />
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-gray-400 uppercase mb-1">Próxima Visita</label>
                                            <input v-model="form.proximo_mantenimiento_at" type="date" class="w-full border-gray-200 rounded-lg h-10 text-xs font-bold" />
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-3 p-3 bg-green-50 rounded-xl border border-green-100 cursor-pointer hover:bg-green-100 transition-all mb-3" @click="form.generar_cita_automatica = !form.generar_cita_automatica">
                                        <input type="checkbox" v-model="form.generar_cita_automatica" class="rounded text-green-600 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-green-700 uppercase tracking-wide">Autogenerar Ticket/Cita</span>
                                    </div>
                                </div>

                                <div class="space-y-3 pt-2">
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 cursor-pointer hover:bg-blue-50 transition-all" @click="form.notificar_exceso_limite = !form.notificar_exceso_limite">
                                        <input type="checkbox" v-model="form.notificar_exceso_limite" class="rounded text-blue-600 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">Notificar excesos</span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 cursor-pointer hover:bg-blue-50 transition-all" @click="form.renovacion_automatica = !form.renovacion_automatica">
                                        <input type="checkbox" v-model="form.renovacion_automatica" class="rounded text-blue-600 cursor-pointer" />
                                        <span class="text-[10px] font-bold text-gray-600 uppercase tracking-wide">Renovación Auto</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-3 pt-2">
                                    <div class="flex flex-col">
                                        <label class="text-[9px] font-black text-gray-400 uppercase mb-1">Fecha de Inicio</label>
                                        <input v-model="form.fecha_inicio" type="date" required class="w-full border-gray-200 rounded-xl h-10 text-xs font-bold" />
                                        <p v-if="form.errors.fecha_inicio" class="text-red-500 text-[9px] mt-1">{{ form.errors.fecha_inicio }}</p>
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[9px] font-black text-gray-400 uppercase mb-1">Fecha de Término</label>
                                        <input v-model="form.fecha_fin" type="date" class="w-full border-gray-200 rounded-xl h-10 text-xs font-bold" />
                                        <p v-if="form.errors.fecha_fin" class="text-red-500 text-[9px] mt-1">{{ form.errors.fecha_fin }}</p>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" :disabled="form.processing" class="w-full py-4 bg-blue-600 text-white rounded-xl font-black text-sm hover:bg-blue-700 shadow-xl shadow-blue-100 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
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
    background: #e5e7eb;
    border-radius: 10px;
}
</style>
