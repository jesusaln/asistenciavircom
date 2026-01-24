<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    plan: Object,
    tipos: Object,
    servicios: Array,
    serviciosElegiblesIds: {
        type: Array,
        default: () => []
    },
});

const isEditing = computed(() => !!props.plan?.id);

const form = useForm({
    nombre: props.plan?.nombre || '',
    descripcion: props.plan?.descripcion || '',
    descripcion_corta: props.plan?.descripcion_corta || '',
    tipo: props.plan?.tipo || 'mantenimiento',
    icono: props.plan?.icono || '',
    color: props.plan?.color || '',
    precio_mensual: props.plan?.precio_mensual || 0,
    precio_anual: props.plan?.precio_anual || null,
    precio_instalacion: props.plan?.precio_instalacion || 0,
    horas_incluidas: props.plan?.horas_incluidas || null,
    tickets_incluidos: props.plan?.tickets_incluidos || null,
    sla_horas_respuesta: props.plan?.sla_horas_respuesta || null,
    costo_hora_extra: props.plan?.costo_hora_extra || null,
    beneficios: props.plan?.beneficios || [],
    incluye_servicios: props.plan?.incluye_servicios || [],
    activo: props.plan?.activo ?? true,
    destacado: props.plan?.destacado ?? false,
    visible_catalogo: props.plan?.visible_catalogo ?? true,
    orden: props.plan?.orden || 0,
    max_equipos: props.plan?.max_equipos || null,
    mantenimiento_frecuencia_meses: props.plan?.mantenimiento_frecuencia_meses || null,
    mantenimiento_dias_anticipacion: props.plan?.mantenimiento_dias_anticipacion || 7,
    generar_cita_automatica: props.plan?.generar_cita_automatica ?? false,
    visitas_sitio_mensuales: props.plan?.visitas_sitio_mensuales || null,
    costo_visita_sitio_extra: props.plan?.costo_visita_sitio_extra || null,
    costo_ticket_extra: props.plan?.costo_ticket_extra || null,
    clausulas: props.plan?.clausulas || '',
    terminos_pago: props.plan?.terminos_pago || '',
    servicios_elegibles: props.serviciosElegiblesIds || [],

    // Nuevos Campos Financieros y Cobranza
    moneda: props.plan?.moneda || 'MXN',
    precio_trimestral: props.plan?.precio_trimestral || null,
    precio_semestral: props.plan?.precio_semestral || null,
    iva_tasa: props.plan?.iva_tasa || 16,
    iva_incluido: props.plan?.iva_incluido ?? false,
    limit_dia_pago: props.plan?.limit_dia_pago || 5,
    dias_gracia_cobranza: props.plan?.dias_gracia_cobranza || 3,
    recargo_pago_tardio: props.plan?.recargo_pago_tardio || 0,
    tipo_recargo: props.plan?.tipo_recargo || 'fijo',
    limite_usuarios_soporte: props.plan?.limite_usuarios_soporte || null,
    limite_ubicaciones: props.plan?.limite_ubicaciones || 1,
    soporte_remoto_ilimitado: props.plan?.soporte_remoto_ilimitado ?? true,
    soporte_presencial_incluido: props.plan?.soporte_presencial_incluido ?? false,
    requiere_orden_compra: props.plan?.requiere_orden_compra ?? false,
    metodo_pago_sugerido: props.plan?.metodo_pago_sugerido || '',
});

const nuevoBeneficio = ref('');
const busquedaServicio = ref('');

const agregarBeneficio = () => {
    if (nuevoBeneficio.value.trim() && !form.beneficios.includes(nuevoBeneficio.value.trim())) {
        form.beneficios.push(nuevoBeneficio.value.trim());
        nuevoBeneficio.value = '';
    }
};

const eliminarBeneficio = (index) => {
    form.beneficios.splice(index, 1);
};

// Toggle servicio elegible al hacer clic en la fila
const toggleServicioElegible = (servicioId) => {
    const index = form.servicios_elegibles.indexOf(servicioId);
    if (index === -1) {
        form.servicios_elegibles.push(servicioId);
    } else {
        form.servicios_elegibles.splice(index, 1);
    }
};

// Servicios filtrados por búsqueda
const serviciosFiltrados = computed(() => {
    const servicios = props.servicios || [];
    if (!busquedaServicio.value.trim()) {
        return servicios;
    }
    const termino = busquedaServicio.value.toLowerCase();
    return servicios.filter(s => 
        s.nombre.toLowerCase().includes(termino)
    );
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('planes-poliza.update', props.plan.id));
    } else {
        form.post(route('planes-poliza.store'));
    }
};

const iconosDisponibles = ['🛡️', '🔧', '🛠️', '✅', '⭐', '🎯', '💎', '🚀', '⚡', '🏆', '🔒', '📊'];

// Smart Pricing Wizard (MEJORA SOLICITADA)
const wizard = ref({
    show: false,
    num_pcs: form.max_equipos || 5,
    selected_servicios: [], // IDs de servicios seleccionados
    descuento_preferencial: 20, // % de descuento sobre el precio base
});

const costoSimuladoBase = computed(() => {
    let base = 1500;
    if (wizard.value.num_pcs > 5) {
        let p = wizard.value.num_pcs > 20 ? 200 : 250;
        base = wizard.value.num_pcs * p;
    }
    return base;
});

const adicionalesWizard = computed(() => {
    let add = 0;
    props.servicios.forEach(s => {
        if (wizard.value.selected_servicios.includes(s.id) && parseFloat(s.precio) > 800) {
            add += Math.round(parseFloat(s.precio) * 0.7);
        }
    });
    return add;
});

const calculateWizard = () => {
    // Lógica Base por PCs
    let precioBase = 1500;
    let horas = 3;
    
    if (wizard.value.num_pcs > 5) {
        let precioPorPC = 250;
        if (wizard.value.num_pcs > 20) precioPorPC = 200;
        precioBase = wizard.value.num_pcs * precioPorPC;
        horas = Math.ceil(wizard.value.num_pcs / 2);
    }
    
    // Sumar valor de servicios especiales (ej. si un servicio es muy caro, sube la base)
    let adicionales = 0;
    const serviciosSeleccionados = props.servicios.filter(s => wizard.value.selected_servicios.includes(s.id));
    
    // Generar Beneficios y Precios Preferenciales
    const nuevosBeneficios = [
        `${horas} Horas de Soporte Incluidas`,
        `Soporte para ${wizard.value.num_pcs} Equipos`
    ];

    serviciosSeleccionados.forEach(s => {
        const precioNormal = parseFloat(s.precio);
        const precioPlan = Math.round(precioNormal * (1 - (wizard.value.descuento_preferencial / 100)));
        
        nuevosBeneficios.push(`Tarifa Pref. ${s.nombre}: $${precioPlan} (Desc. ${wizard.value.descuento_preferencial}%)`);
        
        // LÓGICA DE PROTECCIÓN (MEJORA SOLICITADA):
        // Si el servicio es caro (> 800), es una "Especialidad".
        // Le preguntamos al usuario si desea subir la mensualidad o dejarlo como cobro extra preferente.
        // Por ahora, para asegurar rentabilidad, sumamos el 50% de su valor a la mensualidad si se incluye.
        if (precioNormal > 800) {
            adicionales += Math.round(precioNormal * 0.7); // Sargo de seguridad
        }
    });

    // Filtro de Seguridad: Si es Póliza Mini, limitamos los servicios que consumen horas
    if (wizard.value.num_pcs <= 5) {
        // Solo dejamos como "Elegibles" los servicios baratos (< 800)
        form.servicios_elegibles = serviciosSeleccionados
            .filter(s => parseFloat(s.precio) <= 800)
            .map(s => s.id);
    } else {
        form.servicios_elegibles = [...wizard.value.selected_servicios];
    }
    
    // Usar el precio preferencial del primer servicio seleccionado como "General"
    if (serviciosSeleccionados.length > 0) {
        form.costo_hora_extra = Math.round(parseFloat(serviciosSeleccionados[0].precio) * (1 - (wizard.value.descuento_preferencial / 100)));
    }

    wizard.value.show = false;
};
</script>

<template>
    <AppLayout :title="isEditing ? 'Editar Plan' : 'Nuevo Plan'">
        <Head :title="isEditing ? 'Editar Plan de Póliza' : 'Nuevo Plan de Póliza'" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <Link :href="route('planes-poliza.index')" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm mb-2 inline-block font-semibold">
                            ← Volver al listado
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">
                            {{ isEditing ? `Editar: ${plan.nombre}` : 'Crear Nuevo Plan de Póliza' }}
                        </h1>
                    </div>
                    <button 
                        @click="wizard.show = !wizard.show"
                        type="button"
                        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 transition-all flex items-center gap-2"
                    >
                        <span class="text-lg">⚡</span>
                        {{ wizard.show ? 'Cerrar Asistente' : 'Asistente de Precios Pro' }}
                    </button>
                </div>

                <!-- WIZARD: ASISTENTE DE PRECIOS INTELIGENTE (MEJORA SOLICITADA) -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <div v-if="wizard.show" class="mb-8 bg-gradient-to-br from-indigo-600 to-blue-700 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                        
                        <div class="relative z-10">
                            <h2 class="text-2xl font-black mb-2 flex items-center gap-2">
                                <span class="bg-white/20 p-2 rounded-lg">🧙‍♂️</span>
                                Asistente de Configuración de Póliza
                            </h2>
                            <p class="text-indigo-100 text-sm mb-8">El asistente calculará el precio, horas y beneficios basándose en tus costos reales por hora.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-200">Configuración Comercial</label>
                                    <div class="space-y-3">
                                        <div class="bg-white/10 p-3 rounded-xl border border-indigo-400/30">
                                            <p class="text-[9px] uppercase font-bold opacity-70">Descuento en Horas Extras (%)</p>
                                            <div class="flex items-center gap-2">
                                                <input v-model.number="wizard.descuento_preferencial" type="number" class="w-full bg-transparent border-none text-xl font-black focus:ring-0 p-0" />
                                                <span class="font-black">%</span>
                                            </div>
                                        </div>
                                        <div class="bg-indigo-900/40 p-3 rounded-xl">
                                            <p class="text-[10px] uppercase font-bold text-indigo-200 mb-2">Resumen de Equipos</p>
                                            <input type="range" v-model="wizard.num_pcs" min="1" max="100" class="w-full accent-white" />
                                            <p class="text-3xl font-black text-center mt-1">{{ wizard.num_pcs }} <span class="text-xs">PCs</span></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-2 space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-200">Servicios/Especialidades a Incluir</label>
                                    <div class="bg-white/10 rounded-2xl p-4 max-h-64 overflow-y-auto border border-white/10">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-white">
                                            <label v-for="servicio in servicios" :key="servicio.id" 
                                                   class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10 cursor-pointer transition-colors"
                                                   :class="{'bg-white/20': wizard.selected_servicios.includes(servicio.id)}">
                                                <input type="checkbox" :value="servicio.id" v-model="wizard.selected_servicios" class="w-5 h-5 rounded border-white/30 bg-transparent text-indigo-500">
                                                <div class="flex-grow">
                                                    <p class="text-xs font-bold leading-none mb-1">{{ servicio.nombre }}</p>
                                                    <p class="text-[9px] opacity-60">Base: ${{ servicio.precio }} / Pref: ${{ Math.round(servicio.precio * (1 - (wizard.descuento_preferencial/100))) }}</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-indigo-900/40 p-5 rounded-2xl border border-white/10">
                                        <p class="text-[10px] font-black uppercase mb-4 text-emerald-300">Resumen de Rentabilidad</p>
                                        <div class="space-y-2 text-xs">
                                            <div class="flex justify-between">
                                                <span>Base por PCs ({{wizard.num_pcs}}):</span>
                                                <span class="font-bold">${{ costoSimuladoBase }}</span>
                                            </div>
                                            <div class="flex justify-between text-amber-300">
                                                <span>Cargos Especialidad:</span>
                                                <span class="font-bold">+${{ adicionalesWizard }}</span>
                                            </div>
                                            <div class="border-t border-white/10 pt-2 flex justify-between text-lg font-black text-white">
                                                <span>Total Sugerido:</span>
                                                <span>${{ costoSimuladoBase + adicionalesWizard }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-4">
                                        <button 
                                            type="button"
                                            @click="calculateWizard"
                                            class="flex-grow py-4 bg-emerald-400 hover:bg-emerald-500 text-emerald-950 rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-xl transition-all"
                                        >
                                            ✨ Aplicar y Proteger Rentabilidad
                                        </button>
                                        <button 
                                            type="button" 
                                            @click="wizard.show = false"
                                            class="px-6 py-4 bg-white/10 hover:bg-white/20 rounded-2xl text-xs font-black uppercase"
                                        >
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Información Básica -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">Información Básica</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre del Plan *</label>
                                <input 
                                    v-model="form.nombre" 
                                    type="text" 
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: Plan Básico, Plan Premium..."
                                />
                                <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo *</label>
                                <select v-model="form.tipo" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white">
                                    <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Icono</label>
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        type="button"
                                        v-for="icono in iconosDisponibles" 
                                        :key="icono"
                                        @click="form.icono = icono"
                                        :class="[
                                            'w-10 h-10 rounded-lg text-xl flex items-center justify-center transition',
                                            form.icono === icono ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-100 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ icono }}
                                    </button>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción Corta</label>
                                <input 
                                    v-model="form.descripcion_corta" 
                                    type="text" 
                                    maxlength="500"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Descripción breve para mostrar en las tarjetas"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción Completa</label>
                                <textarea 
                                    v-model="form.descripcion" 
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Descripción detallada del plan..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Precios y Estructura Financiera -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-slate-800">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                            <span class="text-xl">💰</span> Precios y Estructura Financiera
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Moneda</label>
                                <select v-model="form.moneda" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg bg-gray-50 dark:bg-slate-950 text-sm font-bold">
                                    <option value="MXN">Pesos (MXN)</option>
                                    <option value="USD">Dólares (USD)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">IVA (%)</label>
                                <input v-model.number="form.iva_tasa" type="number" step="0.01" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg bg-gray-50 dark:bg-slate-950 text-sm" />
                            </div>

                            <div class="md:col-span-2 flex items-center gap-4 pt-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.iva_incluido" class="w-5 h-5 rounded text-blue-600">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Precio incluye impuestos</span>
                                </label>
                            </div>

                            <div class="border-t border-gray-50 dark:border-slate-800 md:col-span-4 pt-4">
                                <p class="text-xs font-bold text-blue-600 uppercase mb-4">Modalidades de Pago</p>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">Pago Mensual</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                            <input v-model.number="form.precio_mensual" type="number" step="0.01" class="w-full pl-8 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">Pago Trimestral</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                            <input v-model.number="form.precio_trimestral" type="number" step="0.01" class="w-full pl-8 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">Pago Semestral</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                            <input v-model.number="form.precio_semestral" type="number" step="0.01" class="w-full pl-8 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 mb-1">Pago Anual</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                            <input v-model.number="form.precio_anual" type="number" step="0.01" class="w-full pl-8 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cobranza y Facturación -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-slate-800">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                            <span class="text-xl">💳</span> Cobranza y Facturación
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Límite Día de Pago</label>
                                <input v-model.number="form.limit_dia_pago" type="number" min="1" max="31" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" placeholder="Ej: 5" />
                                <p class="text-[10px] text-gray-400 mt-1">Día del mes para recibir el pago.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Días de Gracia</label>
                                <input v-model.number="form.dias_gracia_cobranza" type="number" min="0" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" placeholder="Ej: 3" />
                                <p class="text-[10px] text-gray-400 mt-1">Días antes de marcar la cuenta en riesgo.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Recargo por Mora</label>
                                <div class="flex gap-2">
                                    <input v-model.number="form.recargo_pago_tardio" type="number" step="0.01" class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" />
                                    <select v-model="form.tipo_recargo" class="px-2 border border-gray-200 dark:border-slate-800 rounded-lg text-xs font-bold">
                                        <option value="fijo">$</option>
                                        <option value="porcentaje">%</option>
                                    </select>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Método de Pago Sugerido</label>
                                <input v-model="form.metodo_pago_sugerido" type="text" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" placeholder="Ej: Transferencia SPEI, Domiciliación..." />
                            </div>

                            <div class="flex items-center pt-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.requiere_orden_compra" class="w-5 h-5 rounded text-blue-600">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Requiere Orden de Compra</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Límites de Servicio y Cobertura -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-slate-800">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 pb-2 flex items-center gap-2">
                            <span class="text-xl">📍</span> Límites de Servicio y Cobertura
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Usuarios Soporte</label>
                                <input v-model.number="form.limite_usuarios_soporte" type="number" min="1" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" placeholder="Ej: 50" />
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Ubicaciones/Sedes</label>
                                <input v-model.number="form.limite_ubicaciones" type="number" min="1" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 rounded-lg" placeholder="Ej: 1" />
                            </div>

                            <div class="lg:col-span-2 space-y-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.soporte_remoto_ilimitado" class="w-5 h-5 rounded text-blue-600">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Soporte Remoto Ilimitado</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.soporte_presencial_incluido" class="w-5 h-5 rounded text-indigo-600">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Incluye Soporte Presencial</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Características del Servicio -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">⚙️ Consumibles Técnicos</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Horas Incluidas/Mes</label>
                                <input 
                                    v-model.number="form.horas_incluidas" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 8"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tickets Incluidos/Mes</label>
                                <input 
                                    v-model.number="form.tickets_incluidos" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">SLA (hrs respuesta)</label>
                                <input 
                                    v-model.number="form.sla_horas_respuesta" 
                                    type="number" 
                                    min="1"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 4"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Costo Hora Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.costo_hora_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Visitas Sitio/Mes</label>
                                <input 
                                    v-model.number="form.visitas_sitio_mensuales" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 1"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Costo Visita Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.costo_visita_sitio_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Costo Ticket Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.costo_ticket_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mantenimiento Automático -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">🛠️ Mantenimiento Automático</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Frecuencia (Meses)</label>
                                <input 
                                    v-model.number="form.mantenimiento_frecuencia_meses" 
                                    type="number" 
                                    min="1"
                                    max="24"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 6"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Frecuencia recomendada</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Anticipación (Días)</label>
                                <input 
                                    v-model.number="form.mantenimiento_dias_anticipacion" 
                                    type="number" 
                                    min="1"
                                    max="60"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                    placeholder="Ej: 7"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Días antes para generar ticket</p>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-3 cursor-pointer p-3 bg-white dark:bg-slate-900 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all w-full">
                                    <input type="checkbox" v-model="form.generar_cita_automatica" class="w-5 h-5 rounded text-blue-600 dark:bg-gray-600 dark:border-gray-500">
                                    <div>
                                        <span class="font-semibold text-sm text-gray-900 dark:text-white dark:text-white">Autogenerar Citas</span>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 dark:text-gray-400">Sugerir generación automática de tickets/citas</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Beneficios -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">✅ Beneficios del Plan</h3>
                        
                        <div class="flex gap-2 mb-4">
                            <input 
                                v-model="nuevoBeneficio" 
                                type="text" 
                                class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                placeholder="Escribe un beneficio y presiona Enter o el botón"
                                @keyup.enter="agregarBeneficio"
                            />
                            <button 
                                type="button"
                                @click="agregarBeneficio"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold"
                            >
                                + Agregar
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="(beneficio, index) in form.beneficios" 
                                :key="index"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300 rounded-full text-sm font-bold"
                            >
                                ✓ {{ beneficio }}
                                <button type="button" @click="eliminarBeneficio(index)" class="text-green-600 dark:text-green-400 hover:text-red-600 dark:hover:text-red-400">×</button>
                            </span>
                            <span v-if="!form.beneficios.length" class="text-gray-400 dark:text-gray-500 dark:text-gray-400 text-sm">
                                Agrega los beneficios que se mostrarán en el catálogo
                            </span>
                        </div>
                    </div>

                    <!-- Servicios Elegibles para Banco de Horas -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl shadow-lg shadow-blue-200/30 dark:shadow-none p-6 border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between mb-4 border-b border-blue-200 dark:border-blue-700 pb-3">
                            <div>
                                <h3 class="font-bold text-blue-900 dark:text-blue-200 flex items-center gap-2 text-lg">
                                    <span class="text-xl">⏱️</span> Servicios Elegibles (Banco de Horas)
                                </h3>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                                    Selecciona qué servicios pueden consumir las <strong>horas incluidas</strong> en el plan.
                                    <span class="text-amber-700 dark:text-amber-400 font-semibold">Los NO seleccionados se cobrarán tarifa completa.</span>
                                </p>
                            </div>
                            <Link :href="route('servicios.index')" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 flex items-center gap-1 hover:underline bg-white dark:bg-slate-900 dark:bg-gray-800 px-3 py-1.5 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 dark:border-gray-600">
                                <font-awesome-icon icon="external-link-alt" /> Gestionar Catálogo
                            </Link>
                        </div>

                        <!-- Buscador -->
                        <div class="mb-4">
                            <div class="relative">
                                <input 
                                    v-model="busquedaServicio"
                                    type="text"
                                    placeholder="🔍 Buscar servicio..."
                                    class="w-full md:w-80 px-4 py-2 pl-10 border border-blue-200 dark:border-blue-700 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 text-sm bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-900 dark:text-white dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                >
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <button 
                                    v-if="busquedaServicio" 
                                    @click="busquedaServicio = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tabla de Servicios -->
                        <div class="overflow-hidden rounded-xl border border-blue-200 dark:border-blue-700 bg-white dark:bg-slate-900 dark:bg-gray-800 max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="sticky top-0 bg-blue-100/90 dark:bg-blue-900/50 backdrop-blur-sm">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider w-12 text-center">
                                            <input 
                                                type="checkbox"
                                                :checked="form.servicios_elegibles.length === servicios.length && servicios.length > 0"
                                                @change="form.servicios_elegibles = $event.target.checked ? servicios.map(s => s.id) : []"
                                                class="w-5 h-5 rounded text-blue-600 border-gray-300 dark:border-gray-600 focus:ring-blue-500 cursor-pointer dark:bg-gray-700"
                                                title="Seleccionar/deseleccionar todos"
                                            >
                                        </th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider">Servicio</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider text-right">Precio (si extra)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr 
                                        v-for="servicio in serviciosFiltrados" 
                                        :key="servicio.id"
                                        class="hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer"
                                        :class="{ 'bg-blue-50/50 dark:bg-blue-900/30': form.servicios_elegibles.includes(servicio.id) }"
                                        @click="toggleServicioElegible(servicio.id)"
                                    >
                                        <td class="px-4 py-3 text-center" @click.stop>
                                            <label class="flex items-center justify-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    :value="servicio.id" 
                                                    v-model="form.servicios_elegibles"
                                                    class="w-5 h-5 rounded text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer"
                                                >
                                            </label>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-200">{{ servicio.nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span v-if="Number(servicio.precio) > 0" class="text-sm font-mono text-green-600 dark:text-green-400 font-bold">
                                                ${{ Number(servicio.precio).toFixed(2) }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span 
                                                v-if="form.servicios_elegibles.includes(servicio.id)"
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full text-xs font-bold"
                                            >
                                                ⏱️ Usa Banco
                                            </span>
                                            <span 
                                                v-else
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-full text-xs font-bold"
                                            >
                                                💵 Cobro Extra
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!serviciosFiltrados.length">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500 dark:text-gray-400 italic">
                                            <template v-if="busquedaServicio">
                                                No se encontraron servicios para "{{ busquedaServicio }}".
                                            </template>
                                            <template v-else>
                                                No hay servicios activos en el catálogo. 
                                                <Link :href="route('servicios.index')" class="text-blue-500 dark:text-blue-400 hover:underline">Agregar servicios</Link>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Resumen -->
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 p-3 bg-white dark:bg-slate-900/80 dark:bg-gray-800/80 rounded-lg border border-blue-100 dark:border-blue-800">
                            <div class="flex gap-4 text-sm">
                                <span class="text-blue-700 dark:text-blue-300 font-bold flex items-center gap-1">
                                    ⏱️ {{ form.servicios_elegibles.length }} usan banco de horas
                                </span>
                                <span class="text-amber-700 dark:text-amber-300 font-bold flex items-center gap-1">
                                    💵 {{ (servicios?.length || 0) - form.servicios_elegibles.length }} cobro extra
                                </span>
                            </div>
                            <div class="flex gap-3">
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = (servicios || []).map(s => s.id)"
                                    class="text-xs px-3 py-1.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition font-semibold"
                                >
                                    Todos usan banco
                                </button>
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = []"
                                    class="text-xs px-3 py-1.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-800 transition font-semibold"
                                >
                                    Ninguno (todos extra)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cláusulas y Términos de Pago -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">⚖️ Cláusulas y Condiciones Legales</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Cláusulas del Contrato</label>
                                <textarea 
                                    v-model="form.clausulas" 
                                    rows="8"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-mono bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                    placeholder="Escribe las cláusulas legales separadas por párrafos..."
                                ></textarea>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 dark:text-gray-400 mt-1">Estas cláusulas aparecerán en la impresión del contrato para el cliente.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Términos y Condiciones de Pago</label>
                                <textarea 
                                    v-model="form.terminos_pago" 
                                    rows="3"
                                    class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                    placeholder="Ej: El pago debe realizarse los primeros 5 días del mes..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Visualización -->
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">👁️ Configuración de Visualización</h3>
                        
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <input type="checkbox" v-model="form.activo" class="w-5 h-5 rounded text-blue-600 dark:bg-gray-600 dark:border-gray-500">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white dark:text-white">Plan Activo</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Si está activo, el plan estará disponible para contratar</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <input type="checkbox" v-model="form.visible_catalogo" class="w-5 h-5 rounded text-blue-600 dark:bg-gray-600 dark:border-gray-500">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white dark:text-white">Visible en Catálogo</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Mostrar este plan en la página pública de planes</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl bg-amber-50 dark:bg-amber-900/30 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                <input type="checkbox" v-model="form.destacado" class="w-5 h-5 rounded text-yellow-500 dark:bg-gray-600 dark:border-gray-500">
                                <div>
                                    <span class="font-semibold text-gray-900 dark:text-white dark:text-white">⭐ Plan Destacado</span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Resaltar este plan como recomendado</p>
                                </div>
                            </label>

                            <div class="pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-600">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Orden de visualización</label>
                                <input 
                                    v-model.number="form.orden" 
                                    type="number" 
                                    min="0"
                                    class="w-24 px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Menor número = aparece primero</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Link :href="route('planes-poliza.index')" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg font-semibold text-gray-700 dark:text-gray-300 hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700 transition">
                            Cancelar
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 transition shadow-lg shadow-blue-500/30 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
