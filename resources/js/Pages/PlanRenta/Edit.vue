<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    plan: Object,
    tipos: Object,
});

const isEditing = computed(() => !!props.plan?.id);

const form = useForm({
    nombre: props.plan?.nombre || '',
    descripcion: props.plan?.descripcion || '',
    descripcion_corta: props.plan?.descripcion_corta || '',
    tipo: props.plan?.tipo || 'pdv',
    icono: props.plan?.icono || '🖥️',
    color: props.plan?.color || '#3b82f6',
    precio_mensual: props.plan?.precio_mensual || 0,
    deposito_garantia: props.plan?.deposito_garantia || 0,
    meses_minimos: props.plan?.meses_minimos || 12,
    beneficios: props.plan?.beneficios || [],
    equipamiento_incluido: props.plan?.equipamiento_incluido || [],
    activo: props.plan?.activo ?? true,
    destacado: props.plan?.destacado ?? false,
    visible_catalogo: props.plan?.visible_catalogo ?? true,
    orden: props.plan?.orden || 0,
});

const nuevoBeneficio = ref('');
const nuevoEquipo = ref('');

const agregarBeneficio = () => {
    if (nuevoBeneficio.value.trim() && !form.beneficios.includes(nuevoBeneficio.value.trim())) {
        form.beneficios.push(nuevoBeneficio.value.trim());
        nuevoBeneficio.value = '';
    }
};

const eliminarBeneficio = (index) => {
    form.beneficios.splice(index, 1);
};

const agregarEquipo = () => {
    if (nuevoEquipo.value.trim() && !form.equipamiento_incluido.includes(nuevoEquipo.value.trim())) {
        form.equipamiento_incluido.push(nuevoEquipo.value.trim());
        nuevoEquipo.value = '';
    }
};

const eliminarEquipo = (index) => {
    form.equipamiento_incluido.splice(index, 1);
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('planes-renta.update', props.plan.id));
    } else {
        form.post(route('planes-renta.store'));
    }
};

const iconosDisponibles = ['🖥️', '💻', '📠', '🖨️', '🛒', '📦', '📱', '🎮', '⚡', '🏢', '🏠', '🔒'];
const coloresPreset = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6b7280'];
</script>

<template>
    <AppLayout :title="isEditing ? 'Editar Plan de Renta' : 'Nuevo Plan de Renta'">
        <Head :title="isEditing ? 'Editar Plan de Renta' : 'Nuevo Plan de Renta'" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('planes-renta.index')" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 text-sm mb-2 inline-block font-semibold">
                        ← Volver al listado
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">
                        {{ isEditing ? `Editar: ${plan.nombre}` : 'Crear Nuevo Plan de Renta' }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Columna Principal -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Información Básica -->
                            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2 flex items-center gap-2">
                                    <span class="text-xl">📄</span> Información del Paquete
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Nombre del Plan *</label>
                                        <input 
                                            v-model="form.nombre" 
                                            type="text" 
                                            class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                            placeholder="Ej: Kit Punto de Venta Básico, Renta Laptop Core i7..."
                                        />
                                        <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">{{ form.errors.nombre }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Tipo de Plan *</label>
                                        <select v-model="form.tipo" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white">
                                            <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Color Distintivo</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" v-model="form.color" class="w-10 h-10 border-none rounded-lg cursor-pointer">
                                            <div class="flex flex-wrap gap-1">
                                                <button 
                                                    type="button" 
                                                    v-for="c in coloresPreset" 
                                                    :key="c" 
                                                    @click="form.color = c"
                                                    class="w-6 h-6 rounded-full border border-gray-200 dark:border-slate-800"
                                                    :style="{ backgroundColor: c }"
                                                ></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Slogan o Descripción Corta</label>
                                        <input 
                                            v-model="form.descripcion_corta" 
                                            type="text" 
                                            maxlength="500"
                                            class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                            placeholder="Ej: Todo lo que necesitas para tu negocio"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Descripción Detallada</label>
                                        <textarea 
                                            v-model="form.descripcion" 
                                            rows="4"
                                            class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                            placeholder="Detalles sobre el servicio, soporte técnico incluido, etc."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Equipamiento Incluido -->
                            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2 flex items-center gap-2">
                                    <span class="text-xl">🛠️</span> Equipamiento Incluido
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Lista los equipos físicos que contempla este paquete de renta.</p>
                                
                                <div class="flex gap-2 mb-4">
                                    <input 
                                        v-model="nuevoEquipo" 
                                        type="text" 
                                        class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                        placeholder="Ej: CPU Dell Optiplex i5, Monitor 22 pulgadas, Impresora Térmica..."
                                        @keyup.enter="agregarEquipo"
                                    />
                                    <button 
                                        type="button"
                                        @click="agregarEquipo"
                                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-semibold"
                                    >
                                        + Añadir
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <div 
                                        v-for="(equipo, index) in form.equipamiento_incluido" 
                                        :key="index"
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-700 group transition-all"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ equipo }}</span>
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="eliminarEquipo(index)" 
                                            class="text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                    <p v-if="!form.equipamiento_incluido.length" class="text-center py-4 text-gray-400 dark:text-gray-500 dark:text-gray-400 italic text-sm">
                                        No se han definido equipos para este plan.
                                    </p>
                                </div>
                            </div>

                            <!-- Beneficios -->
                            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2 flex items-center gap-2">
                                    <span class="text-xl">✅</span> Beneficios y Servicios Plus
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Que otras ventajas ofrece este plan (Soporte, Mantenimiento, etc.)</p>
                                
                                <div class="flex gap-2 mb-4">
                                    <input 
                                        v-model="nuevoBeneficio" 
                                        type="text" 
                                        class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                        placeholder="Ej: Mantenimiento incluido cada 6 meses, Soporte remoto ilimitado..."
                                        @keyup.enter="agregarBeneficio"
                                    />
                                    <button 
                                        type="button"
                                        @click="agregarBeneficio"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold"
                                    >
                                        + Añadir
                                    </button>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span 
                                        v-for="(beneficio, index) in form.beneficios" 
                                        :key="index"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-xs font-black uppercase tracking-wider border border-blue-100 dark:border-blue-800"
                                    >
                                        {{ beneficio }}
                                        <button type="button" @click="eliminarBeneficio(index)" class="hover:text-red-500">✕</button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Sidebar -->
                        <div class="space-y-6">
                            <!-- Precios y Condiciones -->
                            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">💰 Costos y Contrato</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Precio Mensual *</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                            <input 
                                                v-model.number="form.precio_mensual" 
                                                type="number" 
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-8 pr-4 py-3 border-2 border-gray-100 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-emerald-500 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white text-xl font-black"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Depósito en Garantía</label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold">$</span>
                                            <input 
                                                v-model.number="form.deposito_garantia" 
                                                type="number" 
                                                step="0.01"
                                                class="w-full pl-8 pr-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Periodo Mínimo (Meses)</label>
                                        <input 
                                            v-model.number="form.meses_minimos" 
                                            type="number" 
                                            min="1"
                                            class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Configuración Visual -->
                            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                                <h3 class="font-bold text-gray-900 dark:text-white dark:text-white mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-gray-600 pb-2">👁️ Configuración</h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2">Icono Principal</label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button 
                                                type="button"
                                                v-for="icono in iconosDisponibles" 
                                                :key="icono"
                                                @click="form.icono = icono"
                                                :class="[
                                                    'w-full aspect-square rounded-xl text-xl flex items-center justify-center transition border-2',
                                                    form.icono === icono ? 'bg-emerald-50 border-emerald-500 scale-105' : 'bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 border-transparent hover:bg-gray-100'
                                                ]"
                                            >
                                                {{ icono }}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="pt-4 space-y-3">
                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" v-model="form.activo" class="w-5 h-5 rounded text-emerald-600">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-emerald-600">Publicado</span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" v-model="form.destacado" class="w-5 h-5 rounded text-amber-500">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-amber-500 font-star">⭐ Destacado</span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer group">
                                            <input type="checkbox" v-model="form.visible_catalogo" class="w-5 h-5 rounded text-emerald-600">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Mostrar en Catálogo</span>
                                        </label>
                                    </div>

                                    <div class="pt-4">
                                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Orden de Aparición</label>
                                        <input 
                                            v-model.number="form.orden" 
                                            type="number" 
                                            class="w-full px-4 py-2 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white dark:bg-slate-900 dark:bg-gray-900 text-gray-900 dark:text-white dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Botones Acción -->
                            <div class="space-y-3">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl font-black uppercase tracking-widest shadow-xl shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-1 transition-all disabled:opacity-50"
                                >
                                    {{ form.processing ? 'Guardando...' : (isEditing ? 'Actualizar Plan' : 'Crear Plan de Renta') }}
                                </button>
                                <Link 
                                    :href="route('planes-renta.index')" 
                                    class="block w-full py-3 text-center text-gray-400 font-bold uppercase tracking-widest text-xs hover:text-gray-600 dark:text-gray-300"
                                >
                                    Cancelar
                                </Link>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
