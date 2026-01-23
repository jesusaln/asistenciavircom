<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const activeSection = ref('bancos');

const sections = [
    {
        id: 'bancos',
        title: 'Bancos y Cuentas',
        icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        description: 'Gestiona tus cuentas bancarias, saldos y movimientos.',
        links: [
            { label: 'Cuentas Bancarias', route: 'cuentas-bancarias.index', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
            { label: 'Traspasos entre Cuentas', route: 'traspasos-bancarios.index', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
        ]
    },
    {
        id: 'cajachica',
        title: 'Caja Chica',
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z',
        description: 'Control de gastos menores y reposiciones de caja chica.',
        links: [
            { label: 'Gestionar Caja Chica', route: 'caja-chica.index', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
        ]
    },
    {
        id: 'conciliacion',
        title: 'Conciliación Bancaria',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        description: 'Herramientas para conciliar tus registros con el banco.',
        links: [
            { label: 'Panel de Conciliación', route: 'conciliacion.index', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        ]
    },
    {
        id: 'cxp_cxc',
        title: 'Cuentas por Pagar y Cobrar',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        description: 'Seguimiento de deudas y cobros pendientes.',
        links: [
            { label: 'Cuentas por Pagar', route: 'cuentas-por-pagar.index', icon: 'M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
            { label: 'Cuentas por Cobrar', route: 'cuentas-por-cobrar.index', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
             { label: 'Entregas de Dinero', route: 'entregas-dinero.index', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z' },
        ]
    }
];

const toggleSection = (id) => {
    activeSection.value = activeSection.value === id ? null : id;
};
</script>

<template>
    <AppLayout title="Finanzas">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 dark:text-gray-200 leading-tight">
                Panel Financiero
            </h2>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 lg:p-8 bg-white dark:bg-slate-900 dark:bg-gray-800 border-b border-gray-200 dark:border-slate-800 dark:border-gray-700">
                        <h1 class="mt-2 text-2xl font-medium text-gray-900 dark:text-white dark:text-white">
                            Gestión Financiera Unificada
                        </h1>
                        <p class="mt-4 text-gray-500 dark:text-gray-400 dark:text-gray-400 leading-relaxed">
                            Accede a todos los módulos financieros desde un solo lugar. Gestiona bancos, caja chica, conciliaciones y cuentas pendientes de forma eficiente.
                        </p>
                    </div>

                    <div class="bg-gray-200 dark:bg-gray-800 bg-opacity-25 grid grid-cols-1 gap-6 lg:gap-8 p-6 lg:p-8">
                        
                        <!-- Accordion Container -->
                        <div class="space-y-4">
                            <div v-for="section in sections" :key="section.id" 
                                class="border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-300 shadow-sm hover:shadow-md bg-white dark:bg-slate-900 dark:bg-gray-900">
                                
                                <!-- Header -->
                                <button @click="toggleSection(section.id)" 
                                    class="w-full px-6 py-4 flex items-center justify-between text-left focus:outline-none transition-colors duration-200"
                                    :class="activeSection === section.id ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-800'">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-2 rounded-full" 
                                            :class="activeSection === section.id ? 'bg-indigo-100 text-amber-600 dark:bg-indigo-800 dark:text-indigo-300' : 'bg-gray-100 text-gray-500 dark:text-gray-400 dark:bg-gray-800 dark:text-gray-400'">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-gray-100">{{ section.title }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ section.description }}</p>
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 transform transition-transform duration-300 text-gray-400"
                                        :class="activeSection === section.id ? 'rotate-180 text-amber-500' : ''"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Content -->
                                <div v-show="activeSection === section.id" 
                                    class="border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800/50">
                                    <div class="p-6 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                        <Link v-for="link in section.links" :key="link.route" 
                                            :href="route(link.route)"
                                            class="flex items-center p-4 rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-600 bg-white dark:bg-slate-900 dark:bg-gray-800 hover:border-indigo-300 dark:hover:border-amber-500 hover:shadow-lg transition-all duration-300 group">
                                            <div class="flex-shrink-0 p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-amber-600 dark:text-indigo-400 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-base font-semibold text-gray-900 dark:text-white dark:text-gray-200 group-hover:text-amber-600 dark:group-hover:text-indigo-300 transition-colors">
                                                    {{ link.label }}
                                                </h4>
                                                <span class="text-xs text-gray-400 group-hover:text-indigo-400 transition-colors">Ir al módulo &rarr;</span>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    
                </div>
            </div>
        </div>
    </AppLayout>
</template>




