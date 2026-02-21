<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import UnidadMedidaModal from '@/Components/Modals/UnidadMedidaModal.vue';
import PriceListManager from '@/Components/PriceListManager.vue';
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

defineOptions({ layout: AppLayout });

const props = defineProps({
    categorias: Array,
    marcas: Array,
    proveedores: Array,
    almacenes: Array,
    priceLists: { type: Array, default: () => [] },
    defaults: { type: Object, default: () => ({ ivaPorcentaje: 16 }) },
    satCatalogos: { type: Object, default: () => ({ unidades: [], objetosImp: [] }) }
});

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [{ type: 'success', background: '#10b981', icon: false }, { type: 'error', background: '#ef4444', icon: false }]
});

const activeTab = ref('general');
const imagePreview = ref(null);
const siguienteCodigo = ref('GENERANDO...');
const cargandoCodigo = ref(false);
const unidadesMedida = ref([]);
const stockMinimoPorAlmacen = ref({});

const form = useForm({
    nombre: '',
    descripcion: '',
    codigo: '',
    codigo_barras: '',
    categoria_id: '',
    marca_id: '',
    proveedor_id: '',
    almacen_id: '',
    precio_compra: '',
    precio_venta: '',
    tipo_producto: 'fisico',
    requiere_serie: false,
    imagen: null,
    estado: 'activo',
    comision_vendedor: '',
    unidad_medida: 'Pieza',
    sat_clave_prod_serv: '',
    sat_clave_unidad: 'H87',
    sat_objeto_imp: '02',
    stock_minimo_por_almacen: {},
    prices: [],
});

const obtenerSiguienteCodigo = async () => {
    cargandoCodigo.value = true;
    try {
        const response = await fetch(`${window.location.origin}/api/productos/next-codigo`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (response.ok) {
            const data = await response.json();
            siguienteCodigo.value = data.data.siguiente_codigo;
        }
    } finally { cargandoCodigo.value = false; }
};

const cargarUnidadesMedida = async () => {
    try {
        const response = await fetch(`${window.location.origin}/api/unidades-medida`);
        if (response.ok) unidadesMedida.value = await response.json();
    } catch (e) {}
};

onMounted(() => {
    obtenerSiguienteCodigo();
    cargarUnidadesMedida();
});

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.imagen = file;
        const reader = new FileReader();
        reader.onload = (e) => imagePreview.value = e.target.result;
        reader.readAsDataURL(file);
    }
};

const submit = () => {
    form.stock_minimo_por_almacen = stockMinimoPorAlmacen.value;
    form.post(route('productos.store'), {
        onSuccess: () => notyf.success('Producto catalogado exitosamente'),
        onError: () => notyf.error('Error al registrar el producto')
    });
};

// Utils
const tabs = [
    { id: 'general', label: 'General', icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'pricing', label: 'Costos & Stock', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1' },
    { id: 'additional', label: 'Específicos', icon: 'M4 6h16M4 12h16m-7 6h7' },
    { id: 'sat', label: 'Fiscal (SAT)', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' }
];

const showCategoriaModal = ref(false);
const showMarcaModal = ref(false);
const showAlmacenModal = ref(false);
const showUnidadMedidaModal = ref(false);
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 pb-20">
        <Head title="Ingreso de Nuevo Activo" />

        <!-- Ambient Background Effects -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/5 rounded-full blur-[120px]"></div>
            <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-emerald-600/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative z-10 w-full px-6 lg:px-12 py-10">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 animate-fade-in-up">
                <div class="space-y-2">
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Catalogar Producto</h1>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Nuevo Registro de Inventario</span>
                        <div class="h-1 w-1 rounded-full bg-slate-200 dark:bg-slate-800"></div>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">Expediente Digital</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="router.visit(route('productos.index'))" class="px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-400 rounded-3xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all active:scale-95">Volver al Dashboard</button>
                    <button @click="submit" :disabled="form.processing" class="px-10 py-5 bg-blue-600 text-white rounded-3xl text-[11px] font-black uppercase tracking-widest shadow-2xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all active:scale-95">
                        {{ form.processing ? 'Sincronizando...' : 'Publicar Producto' }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                <!-- Navigation Tabs (Sidebar Layout) -->
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

                    <!-- Code Preview Card -->
                    <div class="mt-10 p-8 bg-slate-100/50 dark:bg-slate-900/50 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 text-center">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Siguiente Código</span>
                        <div class="text-2xl font-black text-blue-600 mt-2 tracking-tighter">{{ siguienteCodigo }}</div>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-3 leading-relaxed">Asignación automática controlada por el sistema central</p>
                    </div>
                </div>

                <!-- Main Form Area -->
                <div class="lg:col-span-9 animate-fade-in-up" style="animation-delay: 0.1s;">
                    <form @submit.prevent="submit" class="space-y-10">
                        
                        <!-- General Info -->
                        <div v-if="activeTab === 'general'" class="space-y-8">
                            <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Denominación del Producto</label>
                                        <input v-model="form.nombre" type="text" placeholder="EJ: EQUIPO DE COMPUTACIÓN X-SERIES..." class="premium-input" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Código de Barras (EAN/UPC)</label>
                                        <input v-model="form.codigo_barras" type="text" placeholder="ESCANEAR O DIGITAR..." class="premium-input" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Categorización</label>
                                        <select v-model="form.categoria_id" class="premium-input appearance-none">
                                            <option value="">SELECCIONAR...</option>
                                            <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre.toUpperCase() }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Marca / OEM</label>
                                        <select v-model="form.marca_id" class="premium-input appearance-none">
                                            <option value="">SELECCIONAR...</option>
                                            <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombre.toUpperCase() }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Descripción Técnica Extensa</label>
                                    <textarea v-model="form.descripcion" rows="4" class="premium-input resize-none" placeholder="ESPECIFICACIONES, MATERIALES, USO..."></textarea>
                                </div>
                                <div class="flex items-center gap-4 bg-blue-600/5 p-6 rounded-3xl border border-blue-600/10">
                                    <input id="req_serie" type="checkbox" v-model="form.requiere_serie" class="w-5 h-5 rounded-md border-none ring-2 ring-blue-600/20 text-blue-600" />
                                    <label for="req_serie" class="text-[10px] font-black uppercase text-blue-600 tracking-widest cursor-pointer">Requerir captura de número de serie en control de stock</label>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory -->
                        <div v-if="activeTab === 'pricing'" class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-6">
                                    <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-4">Parámetros de Adquisición</h3>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Costo Unitario (SIN IVA)</label>
                                        <div class="relative">
                                            <input v-model="form.precio_compra" type="number" step="0.01" class="premium-input pl-10" placeholder="0.00" />
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-xs">$</span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unidad de Medida Operativa</label>
                                        <select v-model="form.unidad_medida" class="premium-input appearance-none">
                                            <option v-for="u in unidadesMedida" :key="u.id" :value="u.nombre">{{ u.nombre.toUpperCase() }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="bg-blue-600 p-10 rounded-[3rem] text-white shadow-2xl shadow-blue-600/40 space-y-6">
                                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] opacity-60">Valorización Comercial</h3>
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black uppercase tracking-widest opacity-80">Precio Base de Venta (SIN IVA)</label>
                                        <div class="relative text-white">
                                            <input v-model="form.precio_venta" type="number" step="0.01" class="w-full bg-white/10 border-none rounded-2xl p-4 text-xl font-black focus:ring-2 focus:ring-white/20 placeholder:text-white/30" placeholder="0.00" />
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-black">$</span>
                                        </div>
                                    </div>
                                    <div class="pt-4 grid grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-[9px] font-black uppercase opacity-60">Utilidad Proyectada</span>
                                            <div class="text-xl font-black">${{ (form.precio_venta - form.precio_compra).toFixed(2) }}</div>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-black uppercase opacity-60">Precio Sugerido Final</span>
                                            <div class="text-xl font-black text-emerald-300">${{ (form.precio_venta * 1.16).toFixed(2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-100/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50">
                                <h3 class="text-[10px] font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-8">Niveles de Seguridad Stock (Mínimos)</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                                    <div v-for="a in almacenes" :key="a.id" class="p-5 bg-white dark:bg-slate-950 rounded-3xl flex items-center justify-between border border-slate-100 dark:border-slate-800 shadow-sm">
                                        <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ a.nombre }}</span>
                                        <input v-model="stockMinimoPorAlmacen[a.id]" type="number" class="w-20 bg-slate-50 dark:bg-slate-900 border-none rounded-xl p-3 text-xs font-black text-center" placeholder="0" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Specifics -->
                        <div v-if="activeTab === 'additional'" class="space-y-8">
                            <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-10">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Naturaleza Comercial</label>
                                            <select v-model="form.tipo_producto" class="premium-input appearance-none">
                                                <option value="fisico">BIEN FÍSICO / INVENTARIABLE</option>
                                                <option value="digital">SERVICIO / BIEN DIGITAL</option>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Estatus del Registro</label>
                                            <select v-model="form.estado" class="premium-input appearance-none">
                                                <option value="activo">VIGENTE / ACTIVO</option>
                                                <option value="inactivo">SUSPENDIDO / INACTIVO</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Image Upload Section -->
                                    <div class="relative group cursor-pointer" @click="$refs.imgInput.click()">
                                        <input type="file" ref="imgInput" @change="handleImageUpload" class="hidden" accept="image/*" />
                                        <div v-if="!imagePreview" class="h-full min-h-[200px] border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2.5rem] flex flex-col items-center justify-center p-8 transition-all group-hover:border-blue-500 group-hover:bg-blue-500/5">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 mb-4 group-hover:scale-110 transition-transform"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Cargar Activo Visual (Multímedia)</span>
                                        </div>
                                        <div v-else class="relative h-full min-h-[200px] rounded-[2.5rem] overflow-hidden border border-slate-200 dark:border-slate-800 animate-fade-in">
                                            <img :src="imagePreview" class="absolute inset-0 w-full h-full object-cover" />
                                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                <span class="text-[9px] font-black text-white uppercase tracking-widest">Reemplazar Imagen</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SAT/Fiscal -->
                        <div v-if="activeTab === 'sat'" class="animate-fade-in-up">
                            <div class="bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl p-10 rounded-[3rem] border border-slate-200/50 dark:border-slate-800/50 space-y-10">
                                <div class="bg-amber-500/10 border border-amber-500/20 p-6 rounded-3xl flex items-start gap-4">
                                     <svg class="w-6 h-6 text-amber-600 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                     <p class="text-[10px] font-bold text-amber-700 uppercase leading-relaxed tracking-wider">Cumplimiento Fiscal Mexicano (CFDI 4.0). Esta información es crítica para la emisión de comprobantes fiscales digitales.</p>
                                </div>
                                <div class="space-y-8">
                                    <div class="space-y-2">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Clave de Producto/Servicio (SAT)</label>
                                        <SatClaveProdServSearch v-model="form.sat_clave_prod_serv" :error="form.errors.sat_clave_prod_serv" />
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Unidad SAT</label>
                                            <select v-model="form.sat_clave_unidad" class="premium-input appearance-none">
                                                <option value="">SELECCIONAR...</option>
                                                <option v-for="u in satCatalogos.unidades" :key="u.clave" :value="u.clave">{{ u.nombre.toUpperCase() }} ({{ u.clave }})</option>
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Objeto de Impuesto</label>
                                            <select v-model="form.sat_objeto_imp" class="premium-input appearance-none">
                                                <option v-for="o in satCatalogos.objetosImp" :key="o.clave" :value="o.clave">{{ o.nombre.toUpperCase() }} ({{ o.clave }})</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Dynamic Style Injection for Premium Inputs -->
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
        </component>

        <!-- Quick Modals Placeholders -->
         <UnidadMedidaModal :show="showUnidadMedidaModal" :unidades="unidadesMedida" @close="showUnidadMedidaModal = false" />
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.animate-fade-in-left { animation: fadeInLeft 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
</style>
