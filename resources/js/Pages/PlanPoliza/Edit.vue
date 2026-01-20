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
                    <Link :href="route('planes-poliza.index')" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        ← Volver al listado
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isEditing ? `Editar: ${plan.nombre}` : 'Crear Nuevo Plan de Póliza' }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Información Básica -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">Información Básica</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre del Plan *</label>
                                <input 
                                    v-model="form.nombre" 
                                    type="text" 
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: Plan Básico, Plan Premium..."
                                />
                                <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">{{ form.errors.nombre }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tipo *</label>
                                <select v-model="form.tipo" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Icono</label>
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
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción Corta</label>
                                <input 
                                    v-model="form.descripcion_corta" 
                                    type="text" 
                                    maxlength="500"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Descripción breve para mostrar en las tarjetas"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Descripción Completa</label>
                                <textarea 
                                    v-model="form.descripcion" 
                                    rows="3"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Descripción detallada del plan..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Precios -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">💰 Precios</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Precio Mensual *</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.precio_mensual" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Precio Anual (con descuento)</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.precio_anual" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="Opcional"
                                    />
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Si se define, se mostrará el ahorro</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Costo de Activación</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                    <input 
                                        v-model.number="form.precio_instalacion" 
                                        type="number" 
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Características del Servicio -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">⚙️ Configuración del Servicio</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Horas Incluidas/Mes</label>
                                <input 
                                    v-model.number="form.horas_incluidas" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: 8"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Tickets Incluidos/Mes</label>
                                <input 
                                    v-model.number="form.tickets_incluidos" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: 5"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">SLA (hrs respuesta)</label>
                                <input 
                                    v-model.number="form.sla_horas_respuesta" 
                                    type="number" 
                                    min="1"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
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
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Visitas Sitio/Mes</label>
                                <input 
                                    v-model.number="form.visitas_sitio_mensuales" 
                                    type="number" 
                                    min="0"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
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
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
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
                                        class="w-full pl-8 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mantenimiento Automático -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">🛠️ Mantenimiento Automático</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Frecuencia (Meses)</label>
                                <input 
                                    v-model.number="form.mantenimiento_frecuencia_meses" 
                                    type="number" 
                                    min="1"
                                    max="24"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: 6"
                                />
                                <p class="text-xs text-gray-500 mt-1">Frecuencia recomendada</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Anticipación (Días)</label>
                                <input 
                                    v-model.number="form.mantenimiento_dias_anticipacion" 
                                    type="number" 
                                    min="1"
                                    max="60"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="Ej: 7"
                                />
                                <p class="text-xs text-gray-500 mt-1">Días antes para generar ticket</p>
                            </div>

                            <div class="flex items-center">
                                <label class="flex items-center gap-3 cursor-pointer p-3 bg-white rounded-xl border border-gray-100 hover:bg-blue-50 transition-all w-full">
                                    <input type="checkbox" v-model="form.generar_cita_automatica" class="w-5 h-5 rounded text-blue-600">
                                    <div>
                                        <span class="font-semibold text-sm">Autogenerar Citas</span>
                                        <p class="text-[11px] text-gray-500">Sugerir generación automática de tickets/citas</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Beneficios -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">✅ Beneficios del Plan</h3>
                        
                        <div class="flex gap-2 mb-4">
                            <input 
                                v-model="nuevoBeneficio" 
                                type="text" 
                                class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                placeholder="Escribe un beneficio y presiona Enter o el botón"
                                @keyup.enter="agregarBeneficio"
                            />
                            <button 
                                type="button"
                                @click="agregarBeneficio"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                            >
                                + Agregar
                            </button>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="(beneficio, index) in form.beneficios" 
                                :key="index"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-green-100 text-green-800 rounded-full text-sm font-bold"
                            >
                                ✓ {{ beneficio }}
                                <button type="button" @click="eliminarBeneficio(index)" class="text-green-600 hover:text-red-600">×</button>
                            </span>
                            <span v-if="!form.beneficios.length" class="text-gray-400 text-sm">
                                Agrega los beneficios que se mostrarán en el catálogo
                            </span>
                        </div>
                    </div>

                    <!-- Servicios Elegibles para Banco de Horas -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm p-6 border-2 border-blue-200">
                        <div class="flex items-center justify-between mb-4 border-b border-blue-200 pb-3">
                            <div>
                                <h3 class="font-bold text-blue-900 flex items-center gap-2 text-lg">
                                    <span class="text-xl">⏱️</span> Servicios Elegibles (Banco de Horas)
                                </h3>
                                <p class="text-xs text-blue-700 mt-1">
                                    Selecciona qué servicios pueden consumir las <strong>horas incluidas</strong> en el plan.
                                    <span class="text-amber-700 font-semibold">Los NO seleccionados se cobrarán tarifa completa.</span>
                                </p>
                            </div>
                            <Link :href="route('servicios.index')" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1 hover:underline bg-white px-3 py-1.5 rounded-lg shadow-sm">
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
                                    class="w-full md:w-80 px-4 py-2 pl-10 border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-400 focus:border-blue-400 text-sm bg-white"
                                >
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <button 
                                    v-if="busquedaServicio" 
                                    @click="busquedaServicio = ''"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tabla de Servicios -->
                        <div class="overflow-hidden rounded-xl border border-blue-200 bg-white max-h-96 overflow-y-auto">
                            <table class="w-full">
                                <thead class="sticky top-0 bg-blue-100/90 backdrop-blur-sm">
                                    <tr class="text-left">
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 uppercase tracking-wider w-12 text-center">
                                            <input 
                                                type="checkbox"
                                                :checked="form.servicios_elegibles.length === servicios.length && servicios.length > 0"
                                                @change="form.servicios_elegibles = $event.target.checked ? servicios.map(s => s.id) : []"
                                                class="w-5 h-5 rounded text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer"
                                                title="Seleccionar/deseleccionar todos"
                                            >
                                        </th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 uppercase tracking-wider">Servicio</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 uppercase tracking-wider text-right">Precio (si extra)</th>
                                        <th class="px-4 py-3 text-xs font-bold text-blue-900 uppercase tracking-wider text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr 
                                        v-for="servicio in serviciosFiltrados" 
                                        :key="servicio.id"
                                        class="hover:bg-blue-50/50 transition-colors cursor-pointer"
                                        :class="{ 'bg-blue-50/50': form.servicios_elegibles.includes(servicio.id) }"
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
                                            <span class="font-semibold text-gray-800">{{ servicio.nombre }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span v-if="servicio.precio > 0" class="text-sm font-mono text-green-600 font-bold">
                                                ${{ servicio.precio.toFixed(2) }}
                                            </span>
                                            <span v-else class="text-xs text-gray-400">—</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span 
                                                v-if="form.servicios_elegibles.includes(servicio.id)"
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold"
                                            >
                                                ⏱️ Usa Banco
                                            </span>
                                            <span 
                                                v-else
                                                class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold"
                                            >
                                                💵 Cobro Extra
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!serviciosFiltrados.length">
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400 italic">
                                            <template v-if="busquedaServicio">
                                                No se encontraron servicios para "{{ busquedaServicio }}".
                                            </template>
                                            <template v-else>
                                                No hay servicios activos en el catálogo. 
                                                <Link :href="route('servicios.index')" class="text-blue-500 hover:underline">Agregar servicios</Link>
                                            </template>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Resumen -->
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-4 p-3 bg-white/80 rounded-lg border border-blue-100">
                            <div class="flex gap-4 text-sm">
                                <span class="text-blue-700 font-bold flex items-center gap-1">
                                    ⏱️ {{ form.servicios_elegibles.length }} usan banco de horas
                                </span>
                                <span class="text-amber-700 font-bold flex items-center gap-1">
                                    💵 {{ (servicios?.length || 0) - form.servicios_elegibles.length }} cobro extra
                                </span>
                            </div>
                            <div class="flex gap-3">
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = (servicios || []).map(s => s.id)"
                                    class="text-xs px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition font-semibold"
                                >
                                    Todos usan banco
                                </button>
                                <button 
                                    type="button" 
                                    @click="form.servicios_elegibles = []"
                                    class="text-xs px-3 py-1.5 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition font-semibold"
                                >
                                    Ninguno (todos extra)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Cláusulas y Términos de Pago -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">⚖️ Cláusulas y Condiciones Legales</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Cláusulas del Contrato</label>
                                <textarea 
                                    v-model="form.clausulas" 
                                    rows="8"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm font-mono"
                                    placeholder="Escribe las cláusulas legales separadas por párrafos..."
                                ></textarea>
                                <p class="text-[10px] text-gray-400 mt-1">Estas cláusulas aparecerán en la impresión del contrato para el cliente.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Términos y Condiciones de Pago</label>
                                <textarea 
                                    v-model="form.terminos_pago" 
                                    rows="3"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"
                                    placeholder="Ej: El pago debe realizarse los primeros 5 días del mes..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Visualización -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-900 mb-4 border-b pb-2">👁️ Configuración de Visualización</h3>
                        
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.activo" class="w-5 h-5 rounded text-blue-600">
                                <div>
                                    <span class="font-semibold">Plan Activo</span>
                                    <p class="text-sm text-gray-500">Si está activo, el plan estará disponible para contratar</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.visible_catalogo" class="w-5 h-5 rounded text-blue-600">
                                <div>
                                    <span class="font-semibold">Visible en Catálogo</span>
                                    <p class="text-sm text-gray-500">Mostrar este plan en la página pública de planes</p>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.destacado" class="w-5 h-5 rounded text-yellow-500">
                                <div>
                                    <span class="font-semibold">⭐ Plan Destacado</span>
                                    <p class="text-sm text-gray-500">Resaltar este plan como recomendado</p>
                                </div>
                            </label>

                            <div class="pt-4 border-t">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Orden de visualización</label>
                                <input 
                                    v-model.number="form.orden" 
                                    type="number" 
                                    min="0"
                                    class="w-24 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                                <p class="text-xs text-gray-500 mt-1">Menor número = aparece primero</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3">
                        <Link :href="route('planes-poliza.index')" class="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-white transition">
                            Cancelar
                        </Link>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : (isEditing ? 'Guardar Cambios' : 'Crear Plan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
