<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';
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

const eliminarPlan = async (plan) => {
    const result = await Swal.fire({
        title: 'Eliminar plan',
        text: `¿Eliminar el plan "${plan.nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('planes-poliza.destroy', plan.id));
    }
};

const { formatCurrency } = useFormatters();

const getTipoBadge = (tipo) => {
    const colores = {
        mantenimiento: 'bg-sky-100 text-sky-800 dark:text-sky-200 dark:bg-blue-900/50 dark:text-blue-300',
        soporte: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:bg-slate-800/50 dark:text-emerald-300',
        garantia: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
        premium: 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:bg-brand-900/50 dark:text-amber-300',
        personalizado: 'bg-pink-100 text-pink-800 dark:bg-pink-900/50 dark:text-pink-300',
    };
    return colores[tipo] || 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200';
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
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Planes de Póliza</h1>
                        <p class="text-slate-500 dark:text-slate-400">Gestiona los planes que se muestran en el catálogo público</p>
                    </div>
                    <div class="flex gap-3">
                        <a :href="route('catalogo.polizas')" target="_blank" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition font-semibold border border-slate-200 dark:border-slate-700">
                            👁️ Ver Catálogo
                        </a>
                        <Link :href="route('planes-poliza.create')" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition shadow-xl shadow-sky-500/30 flex items-center gap-2 font-semibold">
                            <span>+</span> Nuevo Plan
                        </Link>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 mb-6 border border-slate-100 dark:border-slate-700">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <input 
                            v-model="filtros.search"
                            type="text"
                            placeholder="🔍 Buscar por nombre..."
                            class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-400"
                            @keyup.enter="aplicarFiltros"
                        />
                        <select v-model="filtros.tipo" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 bg-[var(--ui-surface)] text-slate-900 dark:text-white" @change="aplicarFiltros">
                            <option value="">Todos los tipos</option>
                            <option v-for="(nombre, key) in tipos" :key="key" :value="key">{{ nombre }}</option>
                        </select>
                        <div class="flex items-center gap-2">
                            <button @click="aplicarFiltros" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 text-white rounded-xl hover:bg-slate-900 dark:hover:bg-slate-600 transition font-semibold">
                                Filtrar
                            </button>
                            <button @click="limpiarFiltros" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Planes -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none overflow-hidden border border-slate-100 dark:border-slate-700">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio/Mes</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Horas Inc.</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">En Index</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            <tr v-for="plan in planes.data" :key="plan.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl">{{ plan.icono || '🛡️' }}</span>
                                        <div>
                                            <div class="font-semibold text-slate-900 dark:text-white">{{ plan.nombre }}</div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ plan.descripcion_corta }}</div>
                                        </div>
                                        <span v-if="plan.destacado" class="px-2 py-0.5 bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-300 text-xs rounded-full font-semibold">
                                            ⭐ Destacado
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="['px-2.5 py-0.5 text-xs font-medium rounded-full', getTipoBadge(plan.tipo)]">
                                        {{ tipos[plan.tipo] || plan.tipo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-slate-900 dark:text-white">
                                    {{ formatCurrency(plan.precio_mensual) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="plan.horas_incluidas" class="font-semibold text-blue-600 dark:text-blue-400">{{ plan.horas_incluidas }}h</span>
                                    <span v-else class="text-slate-400 dark:text-slate-500">-</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        @click="toggleActivo(plan)"
                                        :class="[
                                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800',
                                            plan.activo ? 'bg-brand-500' : 'bg-slate-300 dark:bg-slate-600'
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
                                            'px-3 py-1 rounded-xl text-xs font-bold transition-all border',
                                            plan.destacado 
                                                ? 'bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 border-brand-300 dark:border-amber-600' 
                                                : 'bg-slate-50 dark:bg-slate-700 text-slate-400 dark:text-slate-500 border-slate-100 dark:border-slate-700 hover:border-brand-200 dark:border-brand-800/30 dark:hover:border-brand-500'
                                        ]"
                                    >
                                        {{ plan.destacado ? '⭐ En Index' : 'Mostrar' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('planes-poliza.edit', plan.id)" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-slate-50 dark:hover:bg-blue-900/30 rounded-xl transition">
                                            ✏️
                                        </Link>
                                        <button @click="eliminarPlan(plan)" class="p-2 text-rose-600 dark:text-rose-400 hover:bg-slate-50 dark:hover:bg-rose-900/30 rounded-xl transition">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!planes.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
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
                            'px-3 py-1.5 rounded-xl text-sm font-medium transition-colors',
                            link.active 
                                ? 'bg-blue-600 text-white shadow-md' 
                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
