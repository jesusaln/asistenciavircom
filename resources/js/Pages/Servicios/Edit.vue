<template>
    <Head title="Editar Servicio" />
    <div class="w-full">
        <div class="glass-panel shadow-sm rounded-xl">
            <!-- Header -->
            <div class="border-b border-slate-800 px-6 py-4">
                <h1 class="text-2xl font-semibold text-slate-100">Editar Servicio</h1>
                <p class="text-sm text-slate-400 mt-1">Modifique la información del servicio</p>
            </div>

            <!-- Navigation Tabs -->
            <div class="border-b border-slate-800">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button
                        @click="activeTab = 'general'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'general'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-brand-500'
                        ]"
                        type="button"
                    >
                        Información General
                    </button>
                    <button
                        @click="activeTab = 'pricing'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'pricing'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-brand-500'
                        ]"
                        type="button"
                    >
                        Precios y Duración
                    </button>
                    <button
                        @click="activeTab = 'additional'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'additional'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-brand-500'
                        ]"
                        type="button"
                    >
                        Información Adicional
                    </button>
                    <button
                        @click="activeTab = 'sat'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'sat'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-slate-400 hover:text-slate-300 hover:border-brand-500'
                        ]"
                        type="button"
                    >
                        Información Fiscal (SAT)
                    </button>
                </nav>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submit" class="p-6">
                <!-- Información General Tab -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-slate-300 mb-2">
                                Nombre del Servicio <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                id="nombre"
                                placeholder="Ingrese el nombre del servicio"
                                class="input-field"
                            />
                            <div v-if="form.errors.nombre" class="error-message">{{ form.errors.nombre }}</div>
                        </div>

                        <!-- Código -->
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-slate-300 mb-2">
                                Código <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.codigo"
                                type="text"
                                id="codigo"
                                placeholder="Código único del servicio"
                                class="input-field"
                            />
                            <div v-if="form.errors.codigo" class="error-message">{{ form.errors.codigo }}</div>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label for="categoria_id" class="block text-sm font-medium text-slate-300 mb-2">
                                Categoría <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="form.categoria_id" id="categoria_id" class="input-field">
                                <option value="">Seleccione una categoría</option>
                                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                                    {{ categoria.nombre }}
                                </option>
                            </select>
                            <div v-if="form.errors.categoria_id" class="error-message">{{ form.errors.categoria_id }}</div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-slate-300 mb-2">
                            Descripción
                        </label>
                        <textarea
                            v-model="form.descripcion"
                            id="descripcion"
                            rows="4"
                            placeholder="Descripción detallada del servicio"
                            class="input-field resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Precios y Duración Tab -->
                <div v-show="activeTab === 'pricing'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Precio -->
                        <div>
                            <label for="precio" class="block text-sm font-medium text-slate-300 mb-2">
                                Precio <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.precio"
                                    type="number"
                                    step="0.01"
                                    id="precio"
                                    placeholder="$ 0.00"
                                    class="input-field"
                                    min="0"
                                />
                            </div>
                            <div v-if="form.errors.precio" class="error-message">{{ form.errors.precio }}</div>
                        </div>

                        <!-- Duración -->
                        <div>
                            <label for="duracion" class="block text-sm font-medium text-slate-300 mb-2">
                                Duración (minutos) <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="form.duracion"
                                type="number"
                                id="duracion"
                                placeholder="60"
                                class="input-field"
                                min="1"
                            />
                            <div v-if="form.errors.duracion" class="error-message">{{ form.errors.duracion }}</div>
                        </div>
                    </div>

                    <!-- Vista Previa de Costos -->
                    <div v-if="form.precio && form.duracion" class="glass-panel p-4 rounded-xl">
                        <h4 class="text-sm font-medium text-slate-300 mb-2">Resumen del Servicio</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-slate-400">Precio por minuto:</span>
                                <span class="font-medium text-blue-600 ml-2">
                                    ${{ (parseFloat(form.precio) / parseInt(form.duracion)).toFixed(2) }}/min
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400">Duración estimada:</span>
                                <span class="font-medium text-slate-100 ml-2">
                                    {{ form.duracion }} minutos
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Comisión Vendedor -->
                    <div>
                        <label for="comision_vendedor" class="block text-sm font-medium text-slate-300 mb-2">
                            Comisión Vendedor ($)
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.comision_vendedor"
                                type="number"
                                step="0.01"
                                id="comision_vendedor"
                                placeholder="$ 0.00"
                                class="input-field"
                                min="0"
                            />
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            Monto fijo que recibe el vendedor por cada prestación de este servicio
                        </p>
                        <div v-if="form.errors.comision_vendedor" class="error-message">{{ form.errors.comision_vendedor }}</div>
                    </div>
                </div>

                <!-- Información Adicional Tab -->
                <div v-show="activeTab === 'additional'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-slate-300 mb-2">
                                Estado <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="form.estado" id="estado" class="input-field">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                            <div v-if="form.errors.estado" class="error-message">{{ form.errors.estado }}</div>
                        </div>

                        <!-- Es Instalación -->
                        <div>
                            <label for="es_instalacion" class="block text-sm font-medium text-slate-300 mb-2">
                                ¿Es servicio de instalación?
                            </label>
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.es_instalacion"
                                        type="radio"
                                        name="es_instalacion"
                                        :value="true"
                                        class="h-4 w-4 text-blue-600 focus:ring-brand-500 border-slate-300"
                                    />
                                    <span class="ml-2 text-sm text-slate-300">Sí</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.es_instalacion"
                                        type="radio"
                                        name="es_instalacion"
                                        :value="false"
                                        class="h-4 w-4 text-blue-600 focus:ring-brand-500 border-slate-300"
                                    />
                                    <span class="ml-2 text-sm text-slate-300">No</span>
                                </label>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Si es instalación, se aplicará comisión adicional al técnico
                            </p>
                            <div v-if="form.errors.es_instalacion" class="error-message">{{ form.errors.es_instalacion }}</div>
                        </div>

                        <!-- Tipo Comisión Técnica -->
                        <div class="md:col-span-2">
                            <label for="tipo_comision_tecnica" class="block text-sm font-medium text-slate-300 mb-2">
                                Categoría Técnica (Para Comisiones)
                            </label>
                            <select v-model="form.tipo_comision_tecnica" id="tipo_comision_tecnica" class="input-field">
                                <option value="otro">General (Aplica % de margen del técnico, ej. 30%)</option>
                                <option value="instalacion">Instalación (Paga monto fijo de instalación, ej. $300)</option>
                                <option value="refrigeracion">Refrigeración (Paga monto fijo de refrigeración, ej. $350)</option>
                                <option value="desinstalacion">Desinstalación (Paga monto fijo de desinstalación, ej. $100)</option>
                                <option value="tierra">Tierra Física (Paga monto fijo de tierra física, ej. $100)</option>
                                <option value="diagnostico">Diagnóstico (Aplica % de margen del técnico)</option>
                                <option value="preventivo">Mantenimiento Preventivo (Aplica % de margen del técnico)</option>
                            </select>
                            <p class="text-xs text-slate-400 mt-1">
                                Esta categoría determina cómo se le paga al técnico por realizar este servicio.
                            </p>
                            <div v-if="form.errors.tipo_comision_tecnica" class="error-message">{{ form.errors.tipo_comision_tecnica }}</div>
                        </div>
                    </div>
                </div>

                <!-- Información Fiscal Tab -->
                <div v-show="activeTab === 'sat'" class="space-y-6">
                    <div class="bg-sky-50 dark:bg-sky-900/20 border border-blue-100 p-4 rounded-xl mb-6 flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-sky-800 dark:text-sky-200 leading-relaxed">
                            Configure la información necesaria para el timbrado de facturas CFDI 4.0. Estos datos son obligatorios si planea emitir facturas electrónicas para este servicio.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Clave SAT -->
                        <div class="md:col-span-2">
                            <SatClaveProdServSearch 
                                v-model="form.sat_clave_prod_serv"
                                :error="form.errors.sat_clave_prod_serv"
                                :initial-description="props.satCatalogos?.claveProdServActual?.descripcion"
                            />
                        </div>

                        <!-- Clave Unidad SAT -->
                        <div>
                            <label for="sat_clave_unidad" class="block text-sm font-medium text-slate-300 mb-2">
                                Unidad SAT <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="form.sat_clave_unidad" id="sat_clave_unidad" class="input-field">
                                <option value="">Seleccione una unidad SAT</option>
                                <option v-for="unidad in satCatalogos.unidades" :key="unidad.clave" :value="unidad.clave">
                                    {{ unidad.nombre }} ({{ unidad.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_clave_unidad" class="error-message">{{ form.errors.sat_clave_unidad }}</div>
                        </div>

                        <!-- Objeto Impuesto -->
                        <div>
                            <label for="sat_objeto_imp" class="block text-sm font-medium text-slate-300 mb-2">
                                Objeto de Impuesto <span class="text-rose-500">*</span>
                            </label>
                            <select v-model="form.sat_objeto_imp" id="sat_objeto_imp" class="input-field">
                                <option v-for="obj in satCatalogos.objetosImp" :key="obj.clave" :value="obj.clave">
                                    {{ obj.nombre }} ({{ obj.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_objeto_imp" class="error-message">{{ form.errors.sat_objeto_imp }}</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-800">
                    <button
                        type="button"
                        @click="$inertia.visit(route('servicios.index'))"
                        class="px-4 py-2 text-sm font-medium text-slate-300 glass-panel border border-slate-300 rounded-xl hover:glass-panel focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">Actualizando...</span>
                        <span v-else>Actualizar Servicio</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Props del componente
const props = defineProps({
    servicio: {
        type: Object,
        required: true
    },
    categorias: {
        type: Array,
        default: () => []
    },
    satCatalogos: {
        type: Object,
        default: () => ({ unidades: [], objetosImp: [] })
    }
});

// Estado para las pestañas
const activeTab = ref('general');

// Formulario para editar un servicio
const form = useForm({
    nombre: props.servicio.nombre || '',
    descripcion: props.servicio.descripcion || '',
    codigo: props.servicio.codigo || '',
    categoria_id: props.servicio.categoria_id || '',
    precio: props.servicio.precio || '',
    duracion: props.servicio.duracion || '',
    estado: props.servicio.estado || 'activo',
    es_instalacion: props.servicio.es_instalacion || false,
    tipo_comision_tecnica: props.servicio.tipo_comision_tecnica || 'otro',
    comision_vendedor: props.servicio.comision_vendedor ?? 0,
    sat_clave_prod_serv: props.servicio.sat_clave_prod_serv || '',
    sat_clave_unidad: props.servicio.sat_clave_unidad || 'E48',
    sat_objeto_imp: props.servicio.sat_objeto_imp || '02',
});

// Validar formulario y navegar a la tab con errores
const validateAndNavigate = () => {
    // Validar campos de la tab 'general'
    if (!form.nombre || !form.codigo || !form.categoria_id) {
        activeTab.value = 'general';
        return false;
    }
    // Validar campos de la tab 'pricing'
    if (!form.precio || !form.duracion) {
        activeTab.value = 'pricing';
        return false;
    }
    // Validar campos de la tab 'additional'
    if (!form.estado) {
        activeTab.value = 'additional';
        return false;
    }
    return true;
};

// Enviar formulario
const submit = () => {
    if (!validateAndNavigate()) {
        return;
    }
    
    // Asegurar que comision_vendedor no sea null o vacío
    if (!form.comision_vendedor && form.comision_vendedor !== 0) {
        form.comision_vendedor = 0;
    }
    
    form.put(route('servicios.update', props.servicio.id), {
        onSuccess: () => {
            // El formulario se resetea automáticamente en caso de éxito
        },
    });
};
</script>

<style scoped>
.input-field {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  color: #111827;
}

.input-field::placeholder {
  color: #9ca3af;
}

.input-field:focus-visible {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.input-field option {
  color: #111827;
  background-color: white;
}

.error-message {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #dc2626;
}
</style>
