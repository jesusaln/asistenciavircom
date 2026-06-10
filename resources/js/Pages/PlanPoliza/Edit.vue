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
    precio_por_equipo: props.plan?.precio_por_equipo || 0,
    precios_por_tipo: props.plan?.precios_por_tipo || [],
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
</script>

<template>
    <AppLayout :title="isEditing ? 'Editar Plan' : 'Nuevo Plan'">
        <Head :title="isEditing ? 'Editar Plan de Póliza' : 'Nuevo Plan de Póliza'" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('planes-poliza.index')" class="text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 text-sm mb-2 inline-block font-semibold">
                        ← Volver al listado
                    </Link>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ isEditing ? `Editar: ${plan.nombre}` : 'Crear Nuevo Plan de Póliza' }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Información Básica -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">Información Básica</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nombre del Plan *</label>
                                <input 
                                    v-model="form.nombre" 
                                    type="text" 
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: Plan Básico, Plan Premium..."
                                />
                                <p v-if="form.errors.nombre" class="text-rose-500 text-sm mt-1">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tipo *</label>
                                <select v-model="form.tipo" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white">
                                    <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Icono</label>
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        type="button"
                                        v-for="icono in iconosDisponibles" 
                                        :key="icono"
                                        @click="form.icono = icono"
                                        :class="[
                                            'w-10 h-10 rounded-xl text-xl flex items-center justify-center transition',
                                            form.icono === icono ? 'bg-sky-100 ring-2 ring-blue-500' : 'bg-slate-100 hover:bg-slate-200'
                                        ]"
                                    >
                                        {{ icono }}
                                    </button>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Descripción Corta</label>
                                <input 
                                    v-model="form.descripcion_corta" 
                                    type="text" 
                                    maxlength="500"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Descripción breve para mostrar en las tarjetas"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Descripción Completa</label>
                                <textarea 
                                    v-model="form.descripcion" 
                                    rows="3"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Descripción detallada del plan..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Precios -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">💰 Precios</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Mensual *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.precio_mensual" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                            <div class="md:col-span-2 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800/30">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
                                    <font-awesome-icon icon="calculator" class="text-blue-500 mr-1" />
                                    Precio Por Equipo (unitario simple)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.precio_por_equipo" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-blue-200 dark:border-blue-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-white dark:bg-slate-800 text-slate-900 dark:text-white"
                                        placeholder="0.00"
                                    />
                                </div>
                                <p class="text-[11px] text-blue-600 dark:text-blue-400 mt-1">Si todos los equipos cuestan lo mismo. Déjalo en 0 si usas precios por tipo.</p>
                            </div>

                            <!-- Precios Por Tipo de Equipo -->
                            <div class="md:col-span-2 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-200 dark:border-indigo-800/30">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
                                        <font-awesome-icon icon="microchip" class="text-indigo-500 mr-1" />
                                        Precios Por Tipo de Equipo
                                    </label>
                                    <button @click="form.precios_por_tipo.push({ nombre: '', precio: 0, icono: 'snowflake', descripcion: '' })" type="button" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 px-2.5 py-1.5 rounded-xl transition-all">
                                        + Agregar Tipo
                                    </button>
                                </div>
                                <p class="text-[10px] text-indigo-500 dark:text-indigo-400 mb-3 italic">Define los tipos de equipo que cubre este plan con su precio individual. El cliente seleccionará cuántos de cada tipo necesita.</p>
                                <div class="space-y-3">
                                    <div v-for="(tipo, i) in form.precios_por_tipo" :key="i"
                                        class="p-3 bg-white dark:bg-slate-800 rounded-xl border border-indigo-200 dark:border-indigo-700/50">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-[10px] font-bold text-indigo-500 uppercase">Tipo #{{ i+1 }}</span>
                                            <button @click="form.precios_por_tipo.splice(i, 1)" type="button" class="text-rose-500 hover:text-rose-600 text-xs">✕</button>
                                        </div>
                                        <div class="grid grid-cols-4 gap-2 mb-2">
                                            <div>
                                                <label class="text-[8px] text-slate-400 uppercase font-black">Ícono</label>
                                                <select v-model="tipo.icono" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-8 text-xs">
                                                    <option value="snowflake">Minisplit</option>
                                                    <option value="industry">Industrial</option>
                                                    <option value="fan">Ventilador</option>
                                                    <option value="wind">Aire</option>
                                                    <option value="microchip">Equipo</option>
                                                    <option value="server">Servidor</option>
                                                </select>
                                            </div>
                                            <div class="col-span-2">
                                                <label class="text-[8px] text-slate-400 uppercase font-black">Nombre</label>
                                                <input v-model="tipo.nombre" type="text" placeholder="Ej: Minisplit 2 Ton" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-8 text-xs font-bold" />
                                            </div>
                                            <div>
                                                <label class="text-[8px] text-slate-400 uppercase font-black">Precio/año</label>
                                                <div class="relative">
                                                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-xs">$</span>
                                                    <input v-model.number="tipo.precio" type="number" step="0.01" min="0" class="w-full pl-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-8 text-xs font-bold text-right" />
                                                </div>
                                                <p class="text-[7px] text-slate-400 mt-0.5">Mensual: ${{ ((tipo.precio || 0) / 12).toFixed(2) }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-[8px] text-slate-400 uppercase font-black">Descripción (opcional)</label>
                                            <input v-model="tipo.descripcion" type="text" placeholder="Ej: Equipo de 2.0 Toneladas" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg h-8 text-xs" />
                                        </div>
                                        <div v-if="i > 0 && form.precios_por_tipo[i-1]" class="mt-2 text-[9px] text-indigo-500">
                                            → Total con {{ i+1 }} tipos: ${{ form.precios_por_tipo.slice(0, i+1).reduce((a, b) => a + (b.precio || 0) * 1, 0).toFixed(2) }}
                                        </div>
                                    </div>
                                    <div v-if="form.precios_por_tipo.length === 0" class="text-center py-6 border-2 border-dashed border-indigo-200 dark:border-indigo-700/50 rounded-xl text-indigo-400 text-xs">
                                        Sin tipos de equipo definidos. Todos los equipos usarán el precio unitario simple.
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Anual (con descuento)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.precio_anual" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                        placeholder="Opcional"
                                    />
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Si se define, se mostrará el ahorro</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Costo de Activación</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.precio_instalacion" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Características del Servicio -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">⚙️ Configuración del Servicio</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Horas Incluidas/Mes</label>
                                <input 
                                    v-model.number="form.horas_incluidas" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 8"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tickets Incluidos/Mes</label>
                                <input 
                                    v-model.number="form.tickets_incluidos" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">SLA (hrs respuesta)</label>
                                <input 
                                    v-model.number="form.sla_horas_respuesta" 
                                    type="number" 
                                    min="1"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 4"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Costo Hora Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.costo_hora_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Visitas Sitio/Mes</label>
                                <input 
                                    v-model.number="form.visitas_sitio_mensuales" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 1"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Costo Visita Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.costo_visita_sitio_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Costo Ticket Extra</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">$</span>
                                    <input 
                                        v-model.number="form.costo_ticket_extra" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mantenimiento Automático -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">🛠️ Mantenimiento Automático</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Frecuencia (Meses)</label>
                                <input 
                                    v-model.number="form.mantenimiento_frecuencia_meses" 
                                    type="number" 
                                    min="1"
                                    max="24"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 6"
                                />
                                <p class="text-xs text-slate-500 mt-1">Frecuencia recomendada</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Anticipación (Días)</label>
                                <input 
                                    v-model.number="form.mantenimiento_dias_anticipacion" 
                                    type="number" 
                                    min="1"
                                    max="60"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                    placeholder="Ej: 7"
                                />
                                <p class="text-xs text-slate-500 mt-1">Días antes para generar ticket</p>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-2 cursor-pointer p-3 bg-white dark:bg-slate-700/50 rounded-xl border border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-blue-900/30 transition-all w-full">
                                    <input type="checkbox" v-model="form.generar_cita_automatica" class="w-4 h-4 rounded-xl text-blue-600 dark:bg-slate-600 dark:border-slate-500">
                                    <div>
                                        <span class="font-semibold text-sm text-slate-900 dark:text-white">Autogenerar Citas</span>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Sugerir generación automática de tickets/citas</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Beneficios -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">✅ Beneficios del Plan</h3>
                        
                        <div class="flex gap-2 mb-4">
                            <input 
                                v-model="nuevoBeneficio" 
                                type="text" 
                                class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400"
                                placeholder="Escribe un beneficio y presiona Enter o el botón"
                                @keyup.enter="agregarBeneficio"
                            />
                            <button 
                                type="button"
                                @click="agregarBeneficio"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition font-semibold"
                            >
                                + Agregar
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="(beneficio, index) in form.beneficios" 
                                :key="index"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300 rounded-full text-sm font-bold"
                            >
                                ✓ {{ beneficio }}
                                <button type="button" @click="eliminarBeneficio(index)" class="text-emerald-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400">×</button>
                            </span>
                            <span v-if="!form.beneficios.length" class="text-slate-400 dark:text-slate-500 text-sm">
                                Agrega los beneficios que se mostrarán en el catálogo
                            </span>
                        </div>
                    </div>

                    <!-- Servicios Elegibles para Banco de Horas -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-2xl shadow-xl shadow-blue-200/30 dark:shadow-none p-6 border-2 border-sky-200 dark:border-sky-800/30 dark:border-blue-700">
                        <div class="flex items-center justify-between mb-4 border-b border-sky-200 dark:border-sky-800/30 dark:border-blue-700 pb-3">
                            <div>
                                <h3 class="font-bold text-blue-900 dark:text-blue-200 flex items-center gap-2 text-lg">
                                    <span class="text-xl">⏱️</span> Servicios Elegibles (Banco de Horas)
                                </h3>
                                <p class="text-xs text-sky-800 dark:text-sky-200 dark:text-blue-300 mt-1">
                                    Selecciona qué servicios pueden consumir las <strong>horas incluidas</strong> en el plan.
                                    <span class="text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 font-semibold">Los NO seleccionados se cobrarán tarifa completa.</span>
                                </p>
                            </div>
                            <Link :href="route('servicios.index')" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 flex items-center gap-1 hover:underline bg-white dark:bg-slate-800 px-3 py-1.5 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700">
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
                                    class="w-full md:w-80 px-4 py-2 pl-10 border border-sky-200 dark:border-sky-800/30 dark:border-blue-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-sky-400 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400"
                                >
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <button 
                                    v-if="busquedaServicio" 
                                    @click="busquedaServicio = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-600 dark:hover:text-slate-300"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tabla de Servicios -->
                        <div class="overflow-hidden rounded-xl border border-sky-200 dark:border-sky-800/30 dark:border-blue-700 bg-white dark:bg-slate-800 max-h-96 overflow-y-auto custom-scrollbar">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead class="sticky top-0 bg-sky-100/90 dark:bg-blue-900/50 backdrop-blur-sm">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider w-12 text-center">
                                            <input 
                                                type="checkbox"
                                                :checked="form.servicios_elegibles.length === servicios.length && servicios.length > 0"
                                                @change="form.servicios_elegibles = $event.target.checked ? servicios.map(s => s.id) : []"
                                                class="w-4 h-4 rounded-xl text-blue-600 border-slate-300 dark:border-slate-700 focus:ring-brand-500 cursor-pointer dark:bg-slate-700"
                                                title="Seleccionar/deseleccionar todos"
                                            >
                                        </th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider">Servicio</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider text-right">Precio (si extra)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 dark:text-blue-200 uppercase tracking-wider text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                    <tr 
                                        v-for="servicio in serviciosFiltrados" 
                                        :key="servicio.id"
                                        class="hover:bg-slate-50/50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer"
                                        :class="{ 'bg-sky-50 dark:bg-sky-900/20/50 dark:bg-sky-900/30': form.servicios_elegibles.includes(servicio.id) }"
                                        @click="toggleServicioElegible(servicio.id)"
                                    >
                                        <td class="px-4 py-3 text-center" @click.stop>
                                            <label class="flex items-center justify-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    :value="servicio.id" 
                                                    v-model="form.servicios_elegibles"
                                                    class="w-4 h-4 rounded-xl text-blue-600 border-slate-300 focus:ring-brand-500 cursor-pointer"
                                                >
                                            </label>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ servicio.nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span v-if="Number(servicio.precio) > 0" class="text-sm font-mono text-emerald-600 dark:text-slate-400 font-bold">
                                                ${{ Number(servicio.precio).toFixed(2) }}
                                            </span>
                                            <span v-else class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span 
                                                v-if="form.servicios_elegibles.includes(servicio.id)"
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 dark:bg-sky-900/20/50 text-sky-800 dark:text-sky-200 dark:text-blue-300 rounded-xl text-xs font-bold"
                                            >
                                                ⏱️ Usa Banco
                                            </span>
                                            <span 
                                                v-else
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 rounded-xl text-xs font-bold"
                                            >
                                                💵 Cobro Extra
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!serviciosFiltrados.length">
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500 italic">
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
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 p-3 bg-white/80 dark:bg-slate-800/80 rounded-xl border border-blue-100 dark:border-blue-800">
                            <div class="flex gap-4 text-sm">
                                <span class="text-sky-800 dark:text-sky-200 dark:text-blue-300 font-bold flex items-center gap-1">
                                    ⏱️ {{ form.servicios_elegibles.length }} usan banco de horas
                                </span>
                                <span class="text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 font-bold flex items-center gap-1">
                                    💵 {{ (servicios?.length || 0) - form.servicios_elegibles.length }} cobro extra
                                </span>
                            </div>
                            <div class="flex gap-3">
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = (servicios || []).map(s => s.id)"
                                    class="text-xs px-3 py-1.5 bg-blue-50 dark:bg-sky-900/20/50 text-sky-800 dark:text-sky-200 dark:text-blue-300 rounded-xl hover:bg-blue-200 dark:hover:bg-blue-800 transition font-semibold"
                                >
                                    Todos usan banco
                                </button>
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = []"
                                    class="text-xs px-3 py-1.5 bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 rounded-xl hover:bg-brand-200 dark:hover:bg-brand-800 transition font-semibold"
                                >
                                    Ninguno (todos extra)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cláusulas y Términos de Pago -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">⚖️ Cláusulas y Condiciones Legales</h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Cláusulas del Contrato</label>
                                <textarea 
                                    v-model="form.clausulas" 
                                    rows="8"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm font-mono bg-[var(--ui-surface)] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400"
                                    placeholder="Escribe las cláusulas legales separadas por párrafos..."
                                ></textarea>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Estas cláusulas aparecerán en la impresión del contrato para el cliente.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Términos y Condiciones de Pago</label>
                                <textarea 
                                    v-model="form.terminos_pago" 
                                    rows="3"
                                    class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 text-sm bg-[var(--ui-surface)] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400"
                                    placeholder="Ej: El pago debe realizarse los primeros 5 días del mes..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Visualización -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none p-6 border border-slate-100 dark:border-slate-700">
                        <h3 class="font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-200 dark:border-slate-700 pb-2">👁️ Configuración de Visualización</h3>
                        
                        <div class="space-y-6">
                            <label class="flex items-center gap-2 cursor-pointer p-3 rounded-xl bg-[var(--ui-surface)] dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <input type="checkbox" v-model="form.activo" class="w-4 h-4 rounded-xl text-blue-600 dark:bg-slate-600 dark:border-slate-500">
                                <div>
                                    <span class="font-semibold text-slate-900 dark:text-white">Plan Activo</span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Si está activo, el plan estará disponible para contratar</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer p-3 rounded-xl bg-[var(--ui-surface)] dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                                <input type="checkbox" v-model="form.visible_catalogo" class="w-4 h-4 rounded-xl text-blue-600 dark:bg-slate-600 dark:border-slate-500">
                                <div>
                                    <span class="font-semibold text-slate-900 dark:text-white">Visible en Catálogo</span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Mostrar este plan en la página pública de planes</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer p-3 rounded-xl bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30 hover:bg-brand-100 dark:hover:bg-brand-900/50 transition-colors">
                                <input type="checkbox" v-model="form.destacado" class="w-4 h-4 rounded-xl text-brand-500 dark:bg-slate-600 dark:border-slate-500">
                                <div>
                                    <span class="font-semibold text-slate-900 dark:text-white">⭐ Plan Destacado</span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Resaltar este plan como recomendado</p>
                                </div>
                            </label>

                            <div class="pt-4 border-t border-slate-200 dark:border-slate-700">
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Orden de visualización</label>
                                <input 
                                    v-model.number="form.orden" 
                                    type="number" 
                                    min="0"
                                    class="w-24 px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white"
                                />
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Menor número = aparece primero</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Link :href="route('planes-poliza.index')" class="px-6 py-3 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-700 transition">
                            Cancelar
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition shadow-xl shadow-blue-500/30 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
