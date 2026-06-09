<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    planes: Object,
    tipos: Object,
    filters: Object,
});

const filtros = ref({
    search: props.filters?.search || '',
    tipo: props.filters?.tipo || '',
});

const aplicarFiltros = () => {
    router.get(route('planes-poliza.index'), filtros.value, { preserveState: true });
};

const limpiarFiltros = () => {
    filtros.value = { search: '', tipo: '' };
    aplicarFiltros();
};

const toggleActivo = (plan) => {
    router.put(route('planes-poliza.toggle', plan.id), {}, { preserveScroll: true });
};

const toggleDestacado = (plan) => {
    router.put(route('planes-poliza.toggle-destacado', plan.id), {}, { preserveScroll: true });
};

const eliminarPlan = (plan) => {
    if (confirm(`¿Eliminar el plan "${plan.nombre}"?`)) {
        router.delete(route('planes-poliza.destroy', plan.id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getTipoBadge = (tipo) => {
    const colores = {
        mantenimiento: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        soporte: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
        garantia: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
        premium: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
        personalizado: 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300',
    };
    return colores[tipo] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>

<template>
    <AppLayout title="Planes de Póliza">
        <Head title="Planes de Póliza" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Planes de Póliza</h1>
                        <p class="text-gray-600 dark:text-gray-400">Gestiona los planes que se muestran en el catálogo público</p>
                    </div>
                    <div class="flex gap-3">
                        <a :href="route('catalogo.polizas')" target="_blank" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition font-semibold border border-gray-200 dark:border-gray-700">
                            👁️ Ver Catálogo
                        </a>
                        <Link :href="route('planes-poliza.create')" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition shadow-lg shadow-blue-500/30 flex items-center gap-2 font-semibold">
                            <span>+</span> Nuevo Plan
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 mb-6 border border-gray-100 dark:border-gray-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input 
                            v-model="filtros.search"
                            type="text"
                            placeholder="🔍 Buscar por nombre..."
                            class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                            @keyup.enter="aplicarFiltros"
                        />
                        <select v-model="filtros.tipo" class="w-full px-4 py-2 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-gray-900 text-gray-900 dark:text-white" @change="aplicarFiltros">
                            <option value="">Todos los tipos</option>
                            <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                        </select>
                        <div class="flex items-center gap-2">
                            <button @click="aplicarFiltros" class="px-4 py-2 bg-gray-800 dark:bg-gray-700 text-white rounded-lg hover:bg-gray-900 dark:hover:bg-gray-600 transition font-semibold">
                                Filtrar
                            </button>
                            <button @click="limpiarFiltros" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Planes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg shadow-gray-200/50 dark:shadow-none overflow-hidden border border-gray-100 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Precio/Mes</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Horas Inc.</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">En Index</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="plan in planes.data" :key="plan.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ plan.icono || '🛡️' }}</span>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ plan.nombre }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ plan.descripcion_corta }}</div>
                                        </div>
                                        <span v-if="plan.destacado" class="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 text-xs rounded-full font-semibold">
                                            ⭐ Destacado
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2 py-1 text-xs font-semibold rounded-full', getTipoBadge(plan.tipo)]">
                                        {{ tipos[plan.tipo] || plan.tipo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(plan.precio_mensual) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="plan.horas_incluidas" class="font-semibold text-blue-600 dark:text-blue-400">{{ plan.horas_incluidas }}h</span>
                                    <span v-else class="text-gray-400 dark:text-gray-500">-</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="toggleActivo(plan)"
                                        :class="[
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800',
                                            plan.activo ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'
                                        ]"
                                    >
                                        <span 
                                            :class="[
                                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                plan.activo ? 'translate-x-5' : 'translate-x-0'
                                            ]"
                                        ></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="toggleDestacado(plan)"
                                        :class="[
                                            'px-3 py-1 rounded-lg text-xs font-bold transition-all border',
                                            plan.destacado 
                                                ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 border-amber-300 dark:border-amber-600' 
                                                : 'bg-gray-50 dark:bg-gray-700 text-gray-400 dark:text-gray-500 border-gray-100 dark:border-gray-600 hover:border-amber-200 dark:hover:border-amber-500'
                                        ]"
                                    >
                                        {{ plan.destacado ? '⭐ En Index' : 'Mostrar' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('planes-poliza.edit', plan.id)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition">
                                            ✏️
                                        </Link>
                                        <button @click="eliminarPlan(plan)" class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!planes.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-400 dark:text-gray-500">
                                    <div class="text-4xl mb-2">📋</div>
                                    No hay planes de póliza. ¡Crea el primero!
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="planes.links?.length > 3" class="mt-4 flex justify-center gap-1">
                    <Link 
                        v-for="link in planes.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
                            link.active 
                                ? 'bg-blue-600 text-white shadow-md' 
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
