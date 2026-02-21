<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  kit: Object,
  costoEstimado: { type: Number, default: 0 }
})

defineOptions({ layout: AppLayout })

const costoActual = ref(null)
const loadingCosto = ref(false)

const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2 }).format(v || 0)
const formatDate = (d) => new Date(d).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' })

const margen = computed(() => {
  const pv = props.kit.precio_venta / 1.16
  const costo = costoActual.value || props.costoEstimado || 0
  if (costo <= 0) return 100
  return ((pv - costo) / costo * 100).toFixed(1)
})

const calcularCostoActual = async () => {
  if (!props.kit.kit_items?.length) return
  loadingCosto.value = true
  try {
    const comp = props.kit.kit_items.map(item => ({
        item_type: item.item_type?.includes('Producto') ? 'producto' : 'servicio',
        item_id: Number(item.item_id),
        cantidad: Number(item.cantidad),
        precio_unitario: item.precio_unitario
    }))
    const res = await fetch('/kits/api/calcular-costo', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
      body: JSON.stringify({ componentes: comp, almacen_id: 1 })
    })
    const data = await res.json()
    if (data.success) costoActual.value = data.costo_total
  } finally { loadingCosto.value = false }
}

onMounted(() => calcularCostoActual())
</script>

<template>
  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 pb-20 overflow-x-hidden relative">
    <Head :title="`Detalles: ${kit.nombre}`" />

    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/10 rounded-full blur-[100px] animate-pulse-slow px-2" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-12">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 animate-fade-in-up">
            <div class="space-y-2">
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">{{ kit.nombre }}</h1>
                <div class="flex items-center gap-4 text-xs font-black uppercase tracking-widest text-slate-400">
                    <span class="text-blue-600 dark:text-blue-400">Expediente #{{ kit.id }}</span>
                    <div class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                    <span>Código Operativo: {{ kit.codigo }}</span>
                    <div class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                    <span :class="kit.estado === 'activo' ? 'text-emerald-500' : 'text-rose-500'">{{ kit.estado === 'activo' ? 'Vigente' : 'Suspendido' }}</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <Link href="/kits" class="px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-3xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all active:scale-95">Volver al Listado</Link>
                <Link :href="`/kits/${kit.id}/edit`" class="px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all active:scale-95 flex items-center gap-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    Editar Configuración
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            <!-- Left Side: Engineering & Price Details -->
            <div class="lg:col-span-8 space-y-10">
                <!-- Data Overview -->
                <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-950/5 animate-fade-in-up">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Denominación Técnica</span>
                                <p class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ kit.nombre }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Resumen Operativo</span>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-400 leading-relaxed uppercase">{{ kit.descripcion || 'Sin descripción técnica registrada en el expediente' }}</p>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Categoría</span>
                                    <p class="text-xs font-black text-blue-600 uppercase tracking-widest">{{ kit.categoria?.nombre || 'General' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Fecha Registro</span>
                                    <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ formatDate(kit.created_at) }}</p>
                                </div>
                            </div>
                            <div class="p-8 bg-blue-600 rounded-[2.5rem] text-white shadow-xl shadow-blue-600/30">
                                <span class="text-[9px] font-black uppercase tracking-widest opacity-60 block mb-2">Precio de Venta (IVA Incluido)</span>
                                <div class="text-4xl font-black tracking-tighter">${{ formatCurrency(kit.precio_venta) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Components Architecture -->
                <div class="space-y-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                    <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] ml-4">Ingeniería de Componentes ({{ kit.kit_items?.length || 0 }})</h3>
                    <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-950/5 overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50 dark:bg-slate-950/50">
                                    <th class="px-8 py-6 text-[9px] font-black text-slate-400 uppercase tracking-widest">Activo Específico</th>
                                    <th class="px-8 py-6 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Tipo</th>
                                    <th class="px-8 py-6 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Cant.</th>
                                    <th class="px-8 py-6 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Unitario</th>
                                    <th class="px-8 py-6 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in kit.kit_items" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight group-hover:text-blue-500 transition-colors">{{ item.producto?.nombre || item.servicio?.nombre }}</span>
                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ item.producto?.codigo || item.servicio?.codigo }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full">
                                            <div class="w-1 h-1 rounded-full" :class="item.item_type?.includes('Producto') ? 'bg-blue-500' : 'bg-emerald-500'"></div>
                                            <span class="text-[8px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">{{ item.item_type?.includes('Producto') ? 'Producto' : 'Servicio' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center text-xs font-black text-slate-600 dark:text-slate-300 tracking-widest">{{ item.cantidad }}</td>
                                    <td class="px-8 py-6 text-right text-xs font-black text-slate-600 dark:text-slate-300 tracking-widest">${{ formatCurrency(item.precio_unitario) }}</td>
                                    <td class="px-8 py-6 text-right text-xs font-black text-blue-600 tracking-widest">${{ formatCurrency(item.precio_unitario * item.cantidad) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Real Time Analytics -->
            <div class="lg:col-span-4 space-y-8 animate-fade-in-left">
                <!-- Real Cost Card -->
                <div class="bg-blue-600 p-10 rounded-[3.5rem] text-white shadow-2xl shadow-blue-600/40 relative overflow-hidden group hover:-translate-y-2 transition-all duration-500">
                     <div class="absolute -right-20 -top-20 w-60 h-60 bg-white/10 rounded-full blur-[80px] group-hover:bg-white/20 transition-all duration-700"></div>
                     <div class="relative z-10 space-y-6">
                         <div class="flex items-center gap-4">
                             <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center backdrop-blur-md">
                                 <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                             </div>
                             <div>
                                 <span class="text-[9px] font-black uppercase tracking-widest opacity-60">Análisis Financiero</span>
                                 <h4 class="text-xs font-black uppercase tracking-[0.2em]">Costo en Tiempo Real</h4>
                             </div>
                         </div>
                         <div class="space-y-1">
                             <div v-if="loadingCosto" class="h-12 flex items-center gap-4">
                                 <div class="w-8 h-8 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                 <span class="text-xs font-black uppercase tracking-[0.2em] opacity-40">CALCULANDO...</span>
                             </div>
                             <div v-else class="text-5xl font-black tracking-tighter uppercase leading-none scale-110 origin-left transition-all duration-500">${{ formatCurrency(costoActual) }}</div>
                             <p class="text-[8px] font-black uppercase tracking-widest opacity-40 italic">Valores basados en inventario actual</p>
                         </div>
                         <div class="pt-8 grid grid-cols-2 gap-4 border-t border-white/10">
                             <div class="space-y-1">
                                 <span class="text-[8px] font-black uppercase opacity-60">Margen Bruto</span>
                                 <div class="text-2xl font-black text-emerald-300 tracking-tighter">{{ margen }}%</div>
                             </div>
                             <div class="space-y-1">
                                 <span class="text-[8px] font-black uppercase opacity-60">Utilidad Net.</span>
                                 <div class="text-2xl font-black text-amber-300 tracking-tighter">${{ formatCurrency((kit.precio_venta / 1.16) - costoActual) }}</div>
                             </div>
                         </div>
                     </div>
                </div>

                <!-- Trazabilidad Card -->
                <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-6">
                    <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Registro de Actividad</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Creación del Expediente</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase">{{ formatDate(kit.created_at) }}</span>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 rounded-2xl transition-all">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Última Actualización</span>
                                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase">{{ formatDate(kit.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .animate-pulse-slow { animation: pulse-slow 8s ease-in-out infinite; }
    @keyframes pulse-slow { 0%, 100% { opacity: 0.1; transform: scale(1); } 50% { opacity: 0.15; transform: scale(1.1); } }
    .animate-fade-in-up { animation: fadeInUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-fade-in-left { animation: fadeInLeft 1s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInLeft { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
    </style>
  </div>
</template>
