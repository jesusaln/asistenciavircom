<script setup>
import { ref, onMounted, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

const props = defineProps({
  categorias: Array,
  serviciosDisponibles: Array, // Cambiado para ser consistente con lo que espera el código original
  almacenPrincipal: Object
})

defineOptions({ layout: AppLayout })

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#4f46e5', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const activeTab = ref('general')
const searchResults = ref([])
const searching = ref(false)
const costoTotal = ref(0)
const margen = ref(0)

const form = useForm({
  nombre: '',
  descripcion: '',
  codigo: '',
  precio_venta: null,
  categoria_id: '',
  componentes: []
})

// Methods
const addComponent = () => {
    form.componentes.push({
        item_type: '',
        item_id: '',
        cantidad: 1,
        precio_unitario: null,
        productoNombre: ''
    })
}

const removeComponent = (index) => {
    form.componentes.splice(index, 1)
    calculateCosts()
}

const fetchAvailableProductos = async (search = '') => {
    searching.value = true;
    try {
        const response = await fetch(`/kits/api/productos-disponibles?search=${search}`);
        const data = await response.json();
        searchResults.value = data;
    } catch (err) {
        console.error('Error fetching products:', err);
    } finally {
        searching.value = false;
    }
};

const updateItemInfo = (index) => {
  const componente = form.componentes[index]
  if (!componente.item_type || !componente.item_id) return

  if (componente.item_type === 'producto') {
    const p = searchResults.value.find(x => x.id == componente.item_id)
    componente.productoNombre = p?.nombre || 'Producto'
    if (!componente.precio_unitario) componente.precio_unitario = p?.precio_venta
  } else {
    const s = props.serviciosDisponibles.find(x => x.id == componente.item_id)
    componente.productoNombre = s?.nombre || 'Servicio'
    if (!componente.precio_unitario) componente.precio_unitario = s?.precio
  }
  calculateCosts()
}

const calculateCosts = async () => {
  const comp = form.componentes
    .filter(c => c.item_type && c.item_id && c.cantidad > 0)
    .map(c => ({
      item_type: c.item_type,
      item_id: Number(c.item_id),
      cantidad: Number(c.cantidad),
      precio_unitario: c.precio_unitario
    }))

  if (comp.length === 0) {
    costoTotal.value = 0
    margen.value = 0
    return
  }

  try {
    const response = await fetch('/kits/api/calcular-costo', {
      method: 'POST',
      headers: { 
          'Content-Type': 'application/json', 
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') 
      },
      body: JSON.stringify({ componentes: comp, almacen_id: props.almacenPrincipal?.id || 1 })
    })
    const data = await response.json()
    if (data.success) {
      costoTotal.value = data.costo_total
      updateMargen()
    }
  } catch (err) {}
}

const updateMargen = () => {
  const pv = form.precio_venta || 0
  if (costoTotal.value > 0 && pv > 0) {
    const pvSinIVA = pv / 1.16
    margen.value = (((pvSinIVA - costoTotal.value) / costoTotal.value) * 100).toFixed(1)
  } else if (costoTotal.value === 0 && pv > 0) {
    margen.value = '100.0'
  } else {
    margen.value = 0
  }
}

const submit = () => {
    if (form.componentes.length === 0) return notyf.error('Añade al menos un componente')
    
    // Convertir el form a una petición POST normal vía Inertia si la ruta existe
    // Originalmente Kits/Create.vue usaba fetch('/kits', ...), mantendré la funcionalidad
    // pero usando form.post(route('kits.store')) si es posible, o adaptado.
    form.post('/kits', {
        onSuccess: () => notyf.success('Kit comercial publicado con éxito'),
        onError: () => notyf.error('Verifica los datos del manifiesto')
    })
}

const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2 }).format(v || 0)

const tabs = [
    { id: 'general', label: 'General', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'components', label: 'Estructura', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1' },
    { id: 'pricing', label: 'Rentabilidad', icon: 'M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' }
];

onMounted(() => {
    if (form.componentes.length === 0) addComponent()
    fetchAvailableProductos()
})

watch(() => form.precio_venta, updateMargen)
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 pb-20">
        <Head title="Ingeniería de Kit Comercial" />

        <!-- Ambient Background Effects -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/5 rounded-full blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative z-10 w-full px-6 lg:px-12 py-10">
            <!-- Header Section (Identical to Products) -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 animate-fade-in-up">
                <div class="space-y-2">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Nuevo Kit Comercial</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Ingeniería de Producto</span>
                        <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Configuración de Paquete</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="router.visit('/kits')" class="px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-3xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all active:scale-95">Descartar</button>
                    <button @click="submit" :disabled="form.processing" class="px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all active:scale-95">
                        {{ form.processing ? 'Sincronizando...' : 'Publicar Kit' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                <!-- Sidebar Layout (Identical to Products) -->
                <div class="lg:col-span-3 space-y-3 animate-fade-in-left">
                    <button 
                        v-for="tab in tabs" :key="tab.id"
                        @click="activeTab = tab.id"
                        class="w-full flex items-center gap-4 p-5 rounded-[2rem] transition-all duration-500 group relative overflow-hidden"
                        :class="activeTab === tab.id ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/30 -translate-r-2' : 'hover:bg-slate-100 dark:hover:bg-slate-900 text-slate-400'"
                    >
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors" :class="activeTab === tab.id ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-800 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" :d="tab.icon" /></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-widest">{{ tab.label }}</span>
                        <div v-if="activeTab === tab.id" class="absolute right-6 w-1.5 h-1.5 rounded-full bg-white opacity-50"></div>
                    </button>

                    <!-- Status Card -->
                    <div class="mt-10 p-8 bg-indigo-600 rounded-[2.5rem] text-white shadow-2xl shadow-indigo-600/30 space-y-4">
                        <span class="text-[9px] font-black uppercase tracking-widest opacity-60">Rentabilidad Estimada</span>
                        <div class="text-3xl font-black tracking-tighter">{{ margen }}%</div>
                        <div class="h-1 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-400 transition-all duration-1000" :style="{ width: Math.min(100, margen) + '%' }"></div>
                        </div>
                        <p class="text-[8px] font-bold uppercase tracking-widest opacity-80 italic leading-tight">Referencia: Precio Venta sin IVA vs Costo Bruto</p>
                    </div>
                </div>

                <!-- Main Area -->
                <div class="lg:col-span-9 animate-fade-in-up" style="animation-delay: 0.1s;">
                    
                    <!-- General Tab -->
                    <div v-if="activeTab === 'general'" class="space-y-8">
                        <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Comercial del Kit</label>
                                    <input v-model="form.nombre" type="text" placeholder="EJ: PAQUETE EMPRESARIAL X-SERIES..." class="premium-input" />
                                    <span v-if="form.errors.nombre" class="text-[9px] font-black text-rose-500 uppercase tracking-widest ml-1">{{ form.errors.nombre }}</span>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código de Referencia</label>
                                    <input v-model="form.codigo" type="text" placeholder="GENERACIÓN AUTOMÁTICA..." class="premium-input" />
                                    <span v-if="form.errors.codigo" class="text-[9px] font-black text-rose-500 uppercase tracking-widest ml-1">{{ form.errors.codigo }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Propuesta de Valor / Descripción</label>
                                <textarea v-model="form.descripcion" rows="4" class="premium-input resize-none" placeholder="INDICA LOS BENEFICIOS Y ALCANCE DE ESTE PAQUETE..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Structure Tab -->
                    <div v-if="activeTab === 'components'" class="space-y-6">
                        <div class="flex items-center justify-between px-4">
                            <h3 class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Desglose de Estructura</h3>
                            <button @click="addComponent" class="px-6 py-3 bg-emerald-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition-all active:scale-95 shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Agregar Elemento
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(c, idx) in form.componentes" :key="idx" class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 hover:border-blue-500/30 transition-all duration-500">
                                 <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                                     <div class="md:col-span-3 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Naturaleza</label>
                                         <select v-model="c.item_type" @change="c.item_id = ''; if(c.item_type === 'producto') fetchAvailableProductos(); calculateCosts()" class="premium-input appearance-none py-3.5">
                                             <option value="">SELECCIONAR...</option>
                                             <option value="producto">PRODUCTO FÍSICO</option>
                                             <option value="servicio">SERVICIO TÉCNICO</option>
                                         </select>
                                     </div>
                                     <div class="md:col-span-4 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Activo Específico</label>
                                         <div class="space-y-2">
                                             <select v-if="c.item_type === 'servicio'" v-model="c.item_id" @change="updateItemInfo(idx)" class="premium-input appearance-none py-3.5">
                                                 <option value="">SELECCIONAR SERVICIO...</option>
                                                 <option v-for="s in serviciosDisponibles" :key="s.id" :value="s.id">{{ s.codigo }} - {{ s.nombre.toUpperCase() }}</option>
                                             </select>
                                             <div v-else-if="c.item_type === 'producto'" class="relative">
                                                 <select v-model="c.item_id" @change="updateItemInfo(idx)" class="premium-input appearance-none py-3.5 pr-10 text-xs font-bold">
                                                     <option value="">{{ searching ? 'BUSCANDO...' : 'SELECCIONAR PRODUCTO...' }}</option>
                                                     <option v-for="p in searchResults" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.nombre.toUpperCase() }}</option>
                                                 </select>
                                                 <button @click="fetchAvailableProductos('')" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500">
                                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                                 </button>
                                             </div>
                                             <input v-else disabled placeholder="NATURALEZA..." class="premium-input py-3.5 opacity-50 cursor-not-allowed" />
                                             
                                             <div v-if="c.item_type === 'producto' && searchResults.length" class="px-2">
                                                 <input 
                                                     type="text" 
                                                     @input="e => fetchAvailableProductos(e.target.value)" 
                                                     placeholder="Filtro rápido..." 
                                                     class="text-[9px] font-bold uppercase tracking-widest bg-transparent border-b border-slate-200 dark:border-slate-800 focus:border-blue-500 outline-none w-full py-1 text-slate-500"
                                                 />
                                             </div>
                                         </div>
                                     </div>
                                     <div class="md:col-span-2 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Cant.</label>
                                         <input v-model.number="c.cantidad" @input="calculateCosts" type="number" step="1" min="1" class="premium-input py-3.5 text-center" />
                                     </div>
                                     <div class="md:col-span-2 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Costo Unit.</label>
                                         <div class="relative">
                                             <input v-model.number="c.precio_unitario" @input="calculateCosts" type="number" step="0.01" class="premium-input py-3.5 pl-8" />
                                             <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">$</span>
                                         </div>
                                     </div>
                                     <div class="md:col-span-1 flex justify-center">
                                         <button @click="removeComponent(idx)" class="w-12 h-12 flex items-center justify-center bg-rose-500/10 text-rose-500 rounded-2xl hover:bg-rose-500 hover:text-white transition-all active:scale-90">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                         </button>
                                     </div>
                                 </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Tab -->
                    <div v-if="activeTab === 'pricing'" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-6">
                                <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-4">Análisis de Costos</h3>
                                <div class="space-y-4">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-950 rounded-3xl border border-slate-100 dark:border-slate-800 flex justify-between items-center transition-all hover:border-blue-500/20">
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Costo Bruto de Estructura</span>
                                        <span class="text-xl font-black text-slate-900 dark:text-white tracking-widest">${{ formatCurrency(costoTotal) }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Categorización Comercial</label>
                                        <select v-model="form.categoria_id" class="premium-input appearance-none">
                                            <option value="">SIN CATEGORÍA</option>
                                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre.toUpperCase() }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-600 p-10 rounded-[3rem] text-white shadow-2xl shadow-blue-600/40 space-y-6">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">Valorización Estratégica</h3>
                                <div class="space-y-2 text-white">
                                    <label class="text-[10px] font-black uppercase tracking-widest opacity-80">Precio Base de Venta (CON IVA)</label>
                                    <div class="relative">
                                        <input v-model.number="form.precio_venta" type="number" step="0.01" class="w-full bg-white/10 border-none rounded-2xl p-5 text-2xl font-black focus:ring-2 focus:ring-white/20 placeholder:text-white/30" placeholder="0.00" />
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black">$</span>
                                    </div>
                                </div>
                                <div class="pt-4 grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-[9px] font-black uppercase opacity-60">Utilidad Proyectada</span>
                                        <div class="text-2xl font-black text-emerald-300 tracking-tighter">${{ formatCurrency((form.precio_venta / 1.16) - costoTotal) }}</div>
                                    </div>
                                    <div>
                                        <span class="text-[9px] font-black uppercase opacity-60">Estado de Margen</span>
                                        <div class="text-2xl font-black tracking-tighter" :class="margen >= 20 ? 'text-emerald-300' : 'text-amber-300'">{{ margen }}%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Global Styles (Identical to Products) -->
        <component is="style">
            .premium-input {
                width: 100%;
                padding: 1rem 1.5rem;
                background: rgba(241, 245, 249, 0.5);
                border: none;
                border-radius: 1.5rem;
                font-size: 0.875rem;
                font-weight: 700;
                color: #0f172a;
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .dark .premium-input {
                background: rgba(15, 23, 42, 0.5);
                color: #ffffff;
            }
            .premium-input:focus {
                background: white;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1), 0 20px 25px -5px rgba(0, 0, 0, 0.05);
                transform: translateY(-2px);
            }
            .dark .premium-input:focus {
                background: #0f172a;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            }
            .animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
            .animate-fade-in-left { animation: fadeInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
            @keyframes fadeInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
        </component>
    </div>
</template>

