<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCompanyColors } from '@/Composables/useCompanyColors';

const { cssVars, colors } = useCompanyColors();

// Dark Mode Logic
const isDark = ref(false);
let observer = null;

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark');
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark');
      }
    });
  });
  observer.observe(document.documentElement, { attributes: true });
});

onBeforeUnmount(() => {
  if (observer) observer.disconnect();
});

const activeSection = ref('bancos');

const sections = [
    {
        id: 'bancos',
        title: 'Bancos y Tesorería',
        subtitle: 'Gestión de Capital',
        icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        color: 'emerald',
        links: [
            { label: 'Cuentas Bancarias', route: 'cuentas-bancarias.index', desc: 'Saldos y movimientos', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
            { label: 'Traspasos', route: 'traspasos-bancarios.index', desc: 'Transferencias internas', icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' },
        ]
    },
    {
        id: 'cajachica',
        title: 'Gastos Menores',
        subtitle: 'Caja Chica',
        icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z',
        color: 'amber',
        links: [
            { label: 'Control de Caja', route: 'caja-chica.index', desc: 'Reposiciones y arqueos', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
        ]
    },
    {
        id: 'conciliacion',
        title: 'Auditoría',
        subtitle: 'Conciliación Bancaria',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        color: 'blue',
        links: [
            { label: 'Conciliador', route: 'conciliacion.index', desc: 'Cruce de transacciones', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
        ]
    },
    {
        id: 'cxp_cxc',
        title: 'Flujo de Efectivo',
        subtitle: 'CxC / CxP',
        icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
        color: 'purple',
        links: [
            { label: 'Cuentas por Pagar', route: 'cuentas-por-pagar.index', desc: 'Proveedores y servicios', icon: 'M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z' },
            { label: 'Cuentas por Cobrar', route: 'cuentas-por-cobrar.index', desc: 'Cartera de clientes', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
             { label: 'Entregas de Dinero', route: 'entregas-dinero.index', desc: 'Cortes y retiros', icon: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z' },
        ]
    }
];

const toggleSection = (id) => {
    activeSection.value = activeSection.value === id ? null : id;
};
</script>

<template>
    <AppLayout title="Finanzas">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-500" :style="cssVars">
            <div class="max-w-7xl mx-auto space-y-12">
                
                <!-- Corporate Header -->
                <div class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 overflow-hidden shadow-2xl shadow-slate-200/50 dark:shadow-slate-950/50 border border-slate-100 dark:border-slate-800 transition-all">
                    <!-- Background Decor -->
                    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-[var(--color-primary)] opacity-[0.03] dark:opacity-[0.05] rounded-full blur-[100px] -translate-y-1/2 translate-x-1/4"></div>
                    
                    <div class="relative flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                        <div class="space-y-4">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-[var(--color-primary)] bg-opacity-10 border border-[var(--color-primary)] border-opacity-20">
                                <span class="w-2 h-2 rounded-full bg-[var(--color-primary)] animate-pulse"></span>
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)]">Módulo Financiero</span>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-none">
                                Centro de <br/>
                                <span class="text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400">Finanzas & Control</span>
                            </h1>
                            <p class="text-lg text-slate-500 dark:text-slate-400 font-medium max-w-2xl leading-relaxed">
                                Plataforma unificada para la gestión de tesorería, conciliación bancaria y flujo de efectivo corporativo.
                            </p>
                        </div>
                        
                         <div class="flex gap-3">
                             <div class="text-right hidden md:block">
                                 <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Estado del Sistema</p>
                                 <p class="text-emerald-500 font-bold flex items-center justify-end gap-2">
                                     <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                     Operativo
                                 </p>
                             </div>
                         </div>
                    </div>
                </div>

                <!-- Bento Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div v-for="section in sections" :key="section.id" 
                         class="group relative bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 overflow-hidden"
                         :class="{ 'ring-2 ring-[var(--color-primary)] ring-offset-4 ring-offset-slate-50 dark:ring-offset-slate-950': activeSection === section.id }">
                        
                        <!-- Hover Action -->
                         <div class="absolute top-0 right-0 w-64 h-64 bg-slate-50 dark:bg-slate-800 rounded-full blur-[80px] opacity-0 group-hover:opacity-50 transition-opacity duration-700 translate-x-1/3 -translate-y-1/3"></div>

                        <!-- Card Header -->
                         <div class="relative flex items-start justify-between mb-8 z-10">
                             <div>
                                 <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight mb-1">{{ section.title }}</h3>
                                 <p class="text-xs font-bold uppercase tracking-widest text-[var(--color-primary)] opacity-80">{{ section.subtitle }}</p>
                             </div>
                             <div class="w-14 h-14 rounded-2xl flex items-center justify-center transition-colors duration-300"
                                  :class="`bg-${section.color}-500/10 text-${section.color}-500`">
                                 <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="section.icon" />
                                 </svg>
                             </div>
                         </div>

                         <!-- Content & Links -->
                         <div class="relative z-10 space-y-4">
                             <Link v-for="link in section.links" :key="link.route" :href="route(link.route)" 
                                   class="flex items-center gap-5 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 hover:border-[var(--color-primary)] hover:bg-white dark:hover:bg-slate-900 group/link transition-all duration-300">
                                 
                                 <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-400 group-hover/link:text-[var(--color-primary)] group-hover/link:scale-110 transition-all">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="link.icon" />
                                    </svg>
                                 </div>
                                 
                                 <div class="flex-1">
                                     <h4 class="text-sm font-bold text-slate-900 dark:text-white group-hover/link:text-[var(--color-primary)] transition-colors">{{ link.label }}</h4>
                                     <p class="text-[10px] text-slate-500 font-medium">{{ link.desc }}</p>
                                 </div>

                                 <div class="w-8 h-8 rounded-full flex items-center justify-center opacity-0 group-hover/link:opacity-100 -translate-x-2 group-hover/link:translate-x-0 transition-all text-[var(--color-primary)]">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                 </div>
                             </Link>
                         </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>




