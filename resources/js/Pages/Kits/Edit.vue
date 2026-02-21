<script setup>
import { ref, onMounted, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

const props = defineProps({
    kit: Object,
    productosDisponibles: Array,
    serviciosDisponibles: Array,
    categorias: Array,
    almacenPrincipal: Object
})

defineOptions({ layout: AppLayout })

const notyf = new Notyf({
    duration: 5000,
    position: { x: 'right', y: 'top' },
    dismissible: true,
    types: [
        { type: 'success', background: '#3b82f6', icon: false },
        { type: 'error', background: '#f43f5e', icon: false }
    ]
})

// Tab System
const activeTab = ref('general')
const tabs = [
    { id: 'general', label: 'General', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'components', label: 'Estructura', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1' },
    { id: 'pricing', label: 'Rentabilidad', icon: 'M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' }
]

const form = useForm({
    nombre: props.kit.nombre || '',
    descripcion: props.kit.descripcion || '',
    codigo: props.kit.codigo || '',
    precio_venta: props.kit.precio_venta || 0,
    categoria_id: props.kit.categoria_id || '',
    estado: props.kit.estado || 'activo',
    componentes: []
})

const costoTotal = ref(0)
const margen = ref(0)
const searchResults = ref([])
const searching = ref(false)

// Logic
const addComponent = () => {
    form.componentes.push({
        item_type: 'producto',
        item_id: '',
        cantidad: 1,
        precio_unitario: 0,
        nombre: ''
    })
}

const removeComponent = (index) => {
    form.componentes.splice(index, 1)
    calculateCosts()
}

const fetchAvailableProductos = async (search = '') => {
    searching.value = true
    try {
        const response = await fetch(`/kits/api/productos-disponibles?search=${search}`)
        const data = await response.json()
        searchResults.value = data
    } catch (err) {
        console.error('Error fetching products:', err)
    } finally {
        searching.value = false
    }
}

const updateItemInfo = (index) => {
    const item = form.componentes[index]
    if (!item.item_id) return

    if (item.item_type === 'producto') {
        const p = searchResults.value.find(x => x.id === item.item_id)
        if (p) {
            item.nombre = p.nombre
            item.precio_unitario = p.precio_venta
        }
    } else {
        const s = props.serviciosDisponibles.find(x => x.id === item.item_id)
        if (s) {
            item.nombre = s.nombre
            item.precio_unitario = s.precio
        }
    }
    calculateCosts()
}

const calculateCosts = async () => {
    const validComponents = form.componentes.filter(c => c.item_id && c.cantidad > 0)
    
    if (validComponents.length === 0) {
        costoTotal.value = 0
        updateMargen()
        return
    }

    try {
        const response = await fetch('/kits/api/calcular-costo', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ 
                componentes: validComponents,
                almacen_id: props.almacenPrincipal?.id || 1 
            })
        })
        const data = await response.json()
        if (data.success) {
            costoTotal.value = data.costo_total
            updateMargen()
        }
    } catch (err) {
        console.error('Error calculating costs:', err)
    }
}

const updateMargen = () => {
    const pv = parseFloat(form.precio_venta) || 0
    const ct = parseFloat(costoTotal.value) || 0
    
    if (ct > 0 && pv > 0) {
        const pvSinIVA = pv / 1.16
        margen.value = (((pvSinIVA - ct) / ct) * 100).toFixed(1)
    } else if (ct === 0 && pv > 0) {
        margen.value = '100.0'
    } else {
        margen.value = 0
    }
}

const submit = () => {
    if (form.componentes.length === 0) {
        notyf.error('El kit debe tener al menos un componente')
        return
    }

    form.put(`/kits/${props.kit.id}`, {
        onSuccess: () => {
            notyf.success('Kit comercial actualizado con éxito')
            router.visit(`/kits/${props.kit.id}`)
        },
        onError: () => notyf.error('Verifica los datos del manifiesto')
    })
}

const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2 }).format(v || 0)

onMounted(() => {
    // Populate form with existing components
    if (props.kit.kit_items && props.kit.kit_items.length > 0) {
        form.componentes = props.kit.kit_items.map(item => {
            const isProd = item.item_type?.includes('Producto') || item.item_type === 'producto'
            
            // Add existing item to searchResults so it's visible in the select
            if (isProd && item.item) {
                if (!searchResults.value.find(p => p.id === item.item.id)) {
                    searchResults.value.push({
                        id: item.item.id,
                        nombre: item.item.nombre,
                        codigo: item.item.codigo,
                        precio_venta: item.item.precio_venta || 0
                    })
                }
            }

            return {
                item_type: isProd ? 'producto' : 'servicio',
                item_id: item.item_id,
                cantidad: item.cantidad,
                precio_unitario: item.precio_unitario || 0,
                nombre: item.item?.nombre || 'Sin nombre'
            }
        })
    } else {
        addComponent()
    }
    
    fetchAvailableProductos()
    calculateCosts()
})

watch(() => form.precio_venta, updateMargen)
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 pb-20">
        <Head :title="`Edición: ${kit.nombre}`" />

        <!-- Ambient Background Effects -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/5 rounded-full blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative z-10 w-full px-6 lg:px-12 py-10">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 animate-fade-in-up">
                <div class="space-y-2">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Editar Kit Comercial</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Expediente #{{ kit.id }}</span>
                        <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">{{ kit.codigo }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="router.visit('/kits')" class="px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-3xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all active:scale-95">Descartar</button>
                    <button @click="submit" :disabled="form.processing" class="px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all active:scale-95">
                        {{ form.processing ? 'Actualizando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                <!-- Sidebar Layout -->
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus Operativo</label>
                                    <select v-model="form.estado" class="premium-input appearance-none py-4">
                                        <option value="activo">VIGENTE / EN VENTA</option>
                                        <option value="inactivo">SUSPENDIDO / DESHABILITADO</option>
                                    </select>
                                </div>
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
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Tipo</label>
                                         <select v-model="c.item_type" @change="c.item_id = ''; calculateCosts()" class="premium-input appearance-none py-4">
                                             <option value="producto">PRODUCTO</option>
                                             <option value="servicio">SERVICIO</option>
                                         </select>
                                     </div>
                                     <div class="md:col-span-4 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Seleccionar Item</label>
                                         <div v-if="c.item_type === 'producto'" class="relative">
                                             <select v-model="c.item_id" @change="updateItemInfo(idx)" class="premium-input appearance-none py-4 pr-10">
                                                 <option value="">{{ searching ? 'BUSCANDO...' : 'ELEGIR PRODUCTO...' }}</option>
                                                 <option v-for="p in searchResults" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.nombre }}</option>
                                             </select>
                                             <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                             </div>
                                         </div>
                                         <div v-else class="relative">
                                             <select v-model="c.item_id" @change="updateItemInfo(idx)" class="premium-input appearance-none py-4 pr-10">
                                                 <option value="">ELEGIR SERVICIO...</option>
                                                 <option v-for="s in serviciosDisponibles" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                             </select>
                                             <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                             </div>
                                         </div>
                                     </div>
                                     <div class="md:col-span-2 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Cant.</label>
                                         <input v-model.number="c.cantidad" @input="calculateCosts" type="number" min="1" class="premium-input py-4 text-center" />
                                     </div>
                                     <div class="md:col-span-2 space-y-2">
                                         <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Costo Unit.</label>
                                         <div class="relative">
                                             <input v-model.number="c.precio_unitario" @input="calculateCosts" type="number" step="0.01" class="premium-input py-4 pl-8" />
                                             <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">$</span>
                                         </div>
                                     </div>
                                     <div class="md:col-span-1 flex justify-center">
                                         <button @click="removeComponent(idx)" class="w-12 h-12 flex items-center justify-center bg-rose-500/10 text-rose-500 rounded-2xl hover:bg-rose-500 hover:text-white transition-all active:scale-95">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                         </button>
                                     </div>
                                 </div>
                            </div>
                        </div>

                        <!-- Quick Filter for products -->
                        <div v-if="activeTab === 'components'" class="px-4">
                             <div class="max-w-xs space-y-2">
                                 <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Buscador de Productos</label>
                                 <div class="relative">
                                     <input 
                                         type="text" 
                                         @input="e => fetchAvailableProductos(e.target.value)" 
                                         placeholder="Filtrar catálogo..." 
                                         class="premium-input py-3 text-[11px]"
                                     />
                                     <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Pricing Tab -->
                    <div v-if="activeTab === 'pricing'" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <!-- Costs Column -->
                            <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-6">
                                <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-4">Análisis de Costos</h3>
                                <div class="space-y-4">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-950 rounded-3xl border border-slate-100 dark:border-slate-800 flex justify-between items-center">
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Costo Bruto Estimado</span>
                                        <span class="text-xl font-black text-slate-900 dark:text-white">${{ formatCurrency(costoTotal) }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Categoría</label>
                                        <select v-model="form.categoria_id" class="premium-input appearance-none py-4">
                                            <option value="">ELEGIR CATEGORÍA...</option>
                                            <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombre.toUpperCase() }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Column -->
                            <div class="bg-blue-600 p-10 rounded-[3rem] text-white shadow-2xl shadow-blue-600/40 space-y-6">
                                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">Valorización del Kit</h3>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest opacity-80 ml-1">Precio de Venta (Con IVA)</label>
                                    <div class="relative">
                                        <input v-model.number="form.precio_venta" type="number" step="0.01" class="w-full bg-white/10 border-none rounded-2xl p-5 text-2xl font-black focus:ring-0 placeholder:text-white/30 text-white" />
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 font-black">$</span>
                                    </div>
                                </div>
                                <div class="pt-4 grid grid-cols-2 gap-4">
                                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10 backdrop-blur-sm">
                                        <span class="text-[9px] font-black uppercase opacity-60 block mb-1">Margen S/IVA</span>
                                        <div class="text-xl font-black tracking-tighter" :class="margen >= 20 ? 'text-emerald-300' : 'text-amber-300'">{{ margen }}%</div>
                                    </div>
                                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10 backdrop-blur-sm">
                                        <span class="text-[9px] font-black uppercase opacity-60 block mb-1">Utilidad</span>
                                        <div class="text-xl font-black text-emerald-300 tracking-tighter">${{ formatCurrency((form.precio_venta / 1.16) - costoTotal) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.premium-input {
    width: 100%;
    padding: 1rem 1.25rem;
    background: rgba(241, 245, 249, 0.5);
    border: none;
    border-radius: 1.25rem;
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

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInLeft {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
}

::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark ::-webkit-scrollbar-thumb { background: #334155; }
</style>
