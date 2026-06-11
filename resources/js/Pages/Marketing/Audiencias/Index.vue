<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { 
  faUsers, faPlus, faFilter, faSearch, faSave, faTrash, 
  faBullhorn, faCheck, faChevronRight, faTimes, faUserTag,
  faShieldAlt, faExclamationTriangle
} from '@fortawesome/free-solid-svg-icons'
import debounce from 'lodash/debounce'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const props = defineProps({
  audiencias: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  priceLists: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
});

const selectedAudienceId = ref(null);
const selectedClientIds = ref([]);
const search = ref('');
const onlyActive = ref(true);
const onlyWithPurchases = ref(false);
const onlyWithPoliza = ref(false);
const onlyWithConsent = ref(false);
const onlyWithActiveWindow = ref(false);
const selectedMunicipio = ref('');
const selectedPriceListId = ref('');

const form = useForm({
  nombre: '',
  descripcion: '',
  cliente_ids: [],
});

const hasConsent = (cliente) => {
  return !!cliente.whatsapp_optin && !!cliente.whatsapp_consent_date && !!cliente.marketing_optin && !cliente.opt_out_at;
};

const filteredClients = computed(() => {
  const term = search.value.trim().toLowerCase();

  return props.clientes.filter((cliente) => {
    const matchesConsent = !onlyWithConsent.value || hasConsent(cliente);
    const matchesActive = !onlyActive.value || !!cliente.activo;
    const matchesPurchases = !onlyWithPurchases.value || Number(cliente.ventas_count || 0) > 0;
    const matchesPoliza = !onlyWithPoliza.value || Number(cliente.polizas_activas_count || 0) > 0;
    const matchesActiveWindow = !onlyWithActiveWindow.value || !!cliente.has_active_window;
    const matchesMunicipio = !selectedMunicipio.value || cliente.municipio === selectedMunicipio.value;
    const matchesPriceList = !selectedPriceListId.value || String(cliente.price_list_id || '') === selectedPriceListId.value;
    const matchesSearch = !term || [
      cliente.nombre_razon_social,
      cliente.telefono,
      cliente.email,
    ].filter(Boolean).some((value) => value.toLowerCase().includes(term));

    return matchesConsent && matchesActive && matchesPurchases && matchesPoliza && matchesActiveWindow && matchesMunicipio && matchesPriceList && matchesSearch;
  });
});

const syncFormClients = () => {
  form.cliente_ids = [...selectedClientIds.value];
};

const resetBuilder = () => {
  selectedAudienceId.value = null;
  selectedClientIds.value = [];
  form.reset();
  form.clearErrors();
  syncFormClients();
};

const loadAudience = (audiencia) => {
  selectedAudienceId.value = audiencia.id;
  form.nombre = audiencia.nombre;
  form.descripcion = audiencia.descripcion || '';
  selectedClientIds.value = (audiencia.clientes || []).map((cliente) => cliente.id);
  syncFormClients();
};

const toggleClient = (clienteId) => {
  if (selectedClientIds.value.includes(clienteId)) {
    selectedClientIds.value = selectedClientIds.value.filter((id) => id !== clienteId);
  } else {
    selectedClientIds.value = [...selectedClientIds.value, clienteId];
  }
  syncFormClients();
};

const selectAllFiltered = () => {
  const ids = filteredClients.value.map((cliente) => cliente.id);
  selectedClientIds.value = Array.from(new Set([...selectedClientIds.value, ...ids]));
  syncFormClients();
};

const clearSelection = () => {
  selectedClientIds.value = [];
  syncFormClients();
};

const saveAudience = () => {
  syncFormClients();

  if (selectedAudienceId.value) {
    form.put(route('marketing.audiencias.update', selectedAudienceId.value), {
      onSuccess: () => resetBuilder(),
    });
    return;
  }

  form.post(route('marketing.audiencias.store'), {
    onSuccess: () => resetBuilder(),
  });
};

const destroyAudience = async (audiencia) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar audiencia',
    text: `¿Estás seguro de eliminar la audiencia "${audiencia.nombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!isConfirmed) return;

  router.delete(route('marketing.audiencias.destroy', audiencia.id), {
    onSuccess: () => {
      if (selectedAudienceId.value === audiencia.id) {
        resetBuilder();
      }
    },
  });
};
</script>

<template>
  <Head title="Marketing - Audiencias" />

  <div class="py-6 px-4 sm:px-6 lg:px-8">
    <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12">
      
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30 shadow-[0_0_20px_rgba(99,102,241,0.15)]">
              <FontAwesomeIcon :icon="faUsers" class="text-indigo-400 text-xl" />
            </div>
            Audiencias de Marketing
          </h1>
          <p class="mt-2 text-slate-400 font-medium">Segmentación estratégica para campañas de impacto.</p>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase tracking-wide">
              {{ clientes.length }} Clientes Elegibles
            </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-[380px_minmax(0,1fr)] gap-8 items-start">
        
        <!-- Sidebar: Saved Audiences -->
        <section class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] p-6 backdrop-blur-3xl shadow-2xl flex flex-col h-full max-h-[calc(100vh-200px)]">
          <div class="flex items-center justify-between mb-6 px-2">
            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-indigo-400 flex items-center gap-2">
              <FontAwesomeIcon :icon="faUserTag" />
              Guardadas
            </h3>
            <span class="px-2 py-0.5 rounded-xl bg-white/[0.05] text-[10px] font-black text-slate-500 border border-white/10">{{ audiencias.length }}</span>
          </div>

          <div class="space-y-4 overflow-y-auto pr-2 custom-scrollbar">
            <button
              type="button"
              @click="resetBuilder"
              class="w-full text-left p-5 rounded-2xl border-2 transition-all group"
              :class="!selectedAudienceId ? 'border-indigo-500 bg-indigo-500/10 shadow-lg shadow-indigo-500/10' : 'border-dashed border-white/10 text-slate-500 hover:border-indigo-500/50'"
            >
              <div class="flex items-center justify-between">
                <div class="text-[11px] font-black uppercase tracking-wide" :class="!selectedAudienceId ? 'text-indigo-400' : 'text-slate-400'">Nueva Audiencia</div>
                <FontAwesomeIcon :icon="faPlus" v-if="selectedAudienceId" class="text-xs opacity-50 group-hover:opacity-100" />
              </div>
              <div class="text-[10px] mt-2 font-medium opacity-60">Crear segmentación desde cero.</div>
            </button>

            <div v-for="audiencia in audiencias" :key="audiencia.id" class="relative group">
              <button
                type="button"
                @click="loadAudience(audiencia)"
                class="w-full text-left p-5 rounded-2xl border-2 transition-all pr-12"
                :class="selectedAudienceId === audiencia.id ? 'border-indigo-500 bg-indigo-500/10' : 'border-white/[0.05] bg-white/[0.01] hover:border-indigo-500/30'"
              >
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-[11px] font-black uppercase tracking-wide" :class="selectedAudienceId === audiencia.id ? 'text-indigo-400' : 'text-white'">{{ audiencia.nombre }}</div>
                    <div class="text-[10px] text-slate-500 mt-2 line-clamp-2 font-medium">{{ audiencia.descripcion || 'Sin descripción' }}</div>
                  </div>
                </div>

                <div class="mt-4 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-xl bg-emerald-500/10 text-[9px] font-black text-emerald-500 border border-emerald-500/20">
                      {{ audiencia.clientes_count }} pts
                    </span>
                  </div>
                  <div class="flex items-center gap-3">
                    <Link
                      :href="route('marketing.campanias.create', { audiencia: audiencia.id })"
                      class="text-[9px] font-black uppercase tracking-wide text-indigo-400 hover:text-indigo-300 flex items-center gap-1"
                      @click.stop
                    >
                      <FontAwesomeIcon :icon="faBullhorn" />
                      Lanzar
                    </Link>
                  </div>
                </div>
              </button>
              
              <button 
                type="button" 
                @click.stop="destroyAudience(audiencia)"
                class="absolute top-4 right-4 w-8 h-8 rounded-xl bg-rose-500/10 text-rose-500 border border-rose-500/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-rose-500 hover:text-white"
              >
                <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
              </button>
            </div>
          </div>
        </section>

        <!-- Main Content: Builder -->
        <section class="bg-white/[0.02] border border-white/[0.06] rounded-[2.5rem] p-8 backdrop-blur-3xl shadow-2xl overflow-hidden">
          
          <!-- Builder Header -->
          <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between mb-10 pb-10 border-b border-white/[0.06]">
            <div>
              <h3 class="text-sm font-black uppercase tracking-[0.2em] text-indigo-400 flex items-center gap-2">
                <div class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,1)]"></div>
                Constructor de Audiencia
              </h3>
              <p class="text-[10px] text-slate-500 mt-2 font-bold uppercase tracking-wide">Filtrado inteligente por comportamiento y ubicación.</p>
            </div>
            
            <div class="flex items-center gap-4">
               <div class="flex flex-col items-end">
                 <span class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-1">Selección Actual</span>
                 <div class="px-4 py-2 rounded-2xl bg-brand-500 text-slate-900 text-sm font-black shadow-lg shadow-brand-500/20">
                    {{ selectedClientIds.length }} Clientes
                 </div>
               </div>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-[340px_minmax(0,1fr)] gap-10">
            
            <!-- Left: Form & Controls -->
            <div class="space-y-8">
              <div class="space-y-6">
                <div>
                  <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-3 ml-1">Identificador</label>
                  <input v-model="form.nombre" type="text" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl px-5 py-4 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-brand-500/50 transition-all text-sm font-bold" placeholder="Ej. Clientes Premium Sonora">
                </div>

                <div>
                  <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-3 ml-1">Notas de Segmento</label>
                  <textarea v-model="form.descripcion" rows="4" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl px-5 py-4 text-white placeholder:text-slate-600 focus:ring-2 focus:ring-brand-500/50 transition-all text-sm font-medium leading-relaxed" placeholder="Describe los criterios de esta audiencia..."></textarea>
                </div>
              </div>

              <div class="space-y-3 pt-6 border-t border-white/[0.06]">
                <button
                  type="button"
                  @click="saveAudience"
                  :disabled="form.processing || selectedClientIds.length === 0"
                  class="w-full py-4 rounded-2xl bg-indigo-500 text-white text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-indigo-500/20 hover:bg-indigo-400 transition-all disabled:opacity-50 active:scale-95"
                >
                  <FontAwesomeIcon :icon="faSave" class="mr-2" />
                  {{ selectedAudienceId ? 'Actualizar Audiencia' : 'Guardar Audiencia' }}
                </button>
                
                <div class="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="selectAllFiltered"
                    class="py-3 rounded-xl border border-white/[0.1] bg-white/[0.02] text-[10px] font-black uppercase tracking-wide text-slate-400 hover:text-white hover:bg-white/[0.05] transition-all"
                  >
                    Todo el Filtro
                  </button>
                  <button
                    type="button"
                    @click="clearSelection"
                    class="py-3 rounded-xl border border-white/[0.1] bg-white/[0.02] text-[10px] font-black uppercase tracking-wide text-slate-400 hover:text-white hover:bg-white/[0.05] transition-all"
                  >
                    Deseleccionar
                  </button>
                </div>
              </div>
            </div>

            <!-- Right: Filters & Results -->
            <div class="space-y-6">
              
              <!-- Search & Quick Toggles -->
              <div class="flex flex-col gap-6 p-6 bg-white/[0.01] border border-white/[0.05] rounded-3xl">
                <div class="relative group">
                  <FontAwesomeIcon :icon="faSearch" class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-indigo-500 transition-colors" />
                  <input
                    v-model="search"
                    type="text"
                    class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl py-4 pl-14 pr-6 text-white text-sm font-bold placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-500/50 transition-all"
                    placeholder="Buscar por nombre, teléfono o correo..."
                  >
                </div>
                
                <div class="flex flex-wrap gap-6 px-2">
                  <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-10 h-5 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700" :class="onlyActive ? 'bg-indigo-500/50' : ''">
                      <input v-model="onlyActive" type="checkbox" class="hidden">
                      <div class="absolute top-1 left-1 w-3 h-3 bg-white rounded-full transition-all" :class="onlyActive ? 'translate-x-5 bg-indigo-400' : 'bg-slate-500'"></div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide" :class="onlyActive ? 'text-indigo-400' : 'text-slate-500'">Solo Activos</span>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-10 h-5 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700" :class="onlyWithPurchases ? 'bg-indigo-500/50' : ''">
                      <input v-model="onlyWithPurchases" type="checkbox" class="hidden">
                      <div class="absolute top-1 left-1 w-3 h-3 bg-white rounded-full transition-all" :class="onlyWithPurchases ? 'translate-x-5 bg-indigo-400' : 'bg-slate-500'"></div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide" :class="onlyWithPurchases ? 'text-indigo-400' : 'text-slate-500'">Con Compras</span>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-10 h-5 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700" :class="onlyWithPoliza ? 'bg-indigo-500/50' : ''">
                      <input v-model="onlyWithPoliza" type="checkbox" class="hidden">
                      <div class="absolute top-1 left-1 w-3 h-3 bg-white rounded-full transition-all" :class="onlyWithPoliza ? 'translate-x-5 bg-indigo-400' : 'bg-slate-500'"></div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide" :class="onlyWithPoliza ? 'text-indigo-400' : 'text-slate-500'">Con Póliza</span>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-10 h-5 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700" :class="onlyWithConsent ? 'bg-indigo-500/50' : ''">
                      <input v-model="onlyWithConsent" type="checkbox" class="hidden">
                      <div class="absolute top-1 left-1 w-3 h-3 bg-white rounded-full transition-all" :class="onlyWithConsent ? 'translate-x-5 bg-indigo-400' : 'bg-slate-500'"></div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide" :class="onlyWithConsent ? 'text-indigo-400' : 'text-slate-500'">Con Consentimiento WA</span>
                  </label>

                  <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative w-10 h-5 bg-slate-800 rounded-full transition-colors group-hover:bg-slate-700" :class="onlyWithActiveWindow ? 'bg-indigo-500/50' : ''">
                      <input v-model="onlyWithActiveWindow" type="checkbox" class="hidden">
                      <div class="absolute top-1 left-1 w-3 h-3 bg-white rounded-full transition-all" :class="onlyWithActiveWindow ? 'translate-x-5 bg-indigo-400' : 'bg-slate-500'"></div>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wide" :class="onlyWithActiveWindow ? 'text-indigo-400' : 'text-slate-500'">Ventana Activa (24h)</span>
                  </label>
                </div>
              </div>

              <!-- Location & List Selectors -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <select v-model="selectedMunicipio" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl px-5 py-4 text-white text-sm font-bold focus:ring-2 focus:ring-brand-500/50 transition-all appearance-none">
                  <option value="" class="bg-slate-900">Todas las ciudades</option>
                  <option v-for="municipio in municipios" :key="municipio" :value="municipio" class="bg-slate-900">{{ municipio }}</option>
                </select>

                <select v-model="selectedPriceListId" class="w-full bg-white/[0.03] border border-white/[0.08] rounded-2xl px-5 py-4 text-white text-sm font-bold focus:ring-2 focus:ring-brand-500/50 transition-all appearance-none">
                  <option value="" class="bg-slate-900">Todas las listas de precios</option>
                  <option v-for="priceList in priceLists" :key="priceList.id" :value="String(priceList.id)" class="bg-slate-900">{{ priceList.nombre }}</option>
                </select>
              </div>

              <!-- Clients Grid -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[55vh] overflow-y-auto pr-2 custom-scrollbar">
                <button
                  v-for="cliente in filteredClients"
                  :key="cliente.id"
                  type="button"
                  @click="toggleClient(cliente.id)"
                  class="text-left p-6 rounded-3xl border-2 transition-all relative group overflow-hidden"
                  :class="selectedClientIds.includes(cliente.id) ? 'border-brand-500 bg-brand-500/10' : 'border-white/[0.05] bg-white/[0.01] hover:border-white/20'"
                >
                  <div class="relative z-10">
                    <div class="flex items-start justify-between gap-4">
                      <div class="flex-1">
                        <div class="text-[11px] font-black uppercase tracking-wide" :class="selectedClientIds.includes(cliente.id) ? 'text-brand-500' : 'text-white'">{{ cliente.nombre_razon_social }}</div>
                        <div class="flex flex-col mt-2 space-y-1">
                          <span class="text-[10px] text-slate-500 font-bold">{{ cliente.telefono || 'Sin teléfono' }}</span>
                          <span class="text-[10px] text-slate-600 font-medium truncate">{{ cliente.email || 'Sin correo' }}</span>
                        </div>
                      </div>
                      
                      <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all"
                        :class="selectedClientIds.includes(cliente.id) ? 'bg-brand-500 text-slate-900 shadow-lg shadow-brand-500/30 scale-110' : 'bg-white/5 text-slate-600 border border-white/10'">
                        <FontAwesomeIcon :icon="selectedClientIds.includes(cliente.id) ? faCheck : faPlus" class="text-[10px]" />
                      </div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2">
                      <span v-if="cliente.municipio" class="px-2 py-1 rounded-xl bg-white/5 border border-white/5 text-[8px] font-black uppercase tracking-wide text-slate-500">
                        {{ cliente.municipio }}
                      </span>
                      <span v-if="cliente.ventas_count > 0" class="px-2 py-1 rounded-xl bg-indigo-500/10 border border-indigo-500/10 text-[8px] font-black uppercase tracking-wide text-indigo-400">
                        {{ cliente.ventas_count }} Compras
                      </span>
                      <span v-if="cliente.polizas_activas_count > 0" class="px-2 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/10 text-[8px] font-black uppercase tracking-wide text-emerald-400">
                        Póliza Activa
                      </span>
                      <span v-if="cliente.has_active_window" class="px-2 py-1 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-[8px] font-black uppercase tracking-wide text-indigo-400 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                        Ventana 24h Activa
                      </span>
                      <span v-if="hasConsent(cliente)" class="px-2 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-[8px] font-black uppercase tracking-wide text-emerald-400 flex items-center gap-1">
                        <FontAwesomeIcon :icon="faShieldAlt" class="text-[8px]" />
                        Opt-in WA
                      </span>
                      <span v-else class="px-2 py-1 rounded-xl bg-rose-500/10 border border-rose-500/20 text-[8px] font-black uppercase tracking-wide text-rose-400 flex items-center gap-1">
                        <FontAwesomeIcon :icon="faExclamationTriangle" class="text-[8px]" />
                        Sin Opt-in
                      </span>
                    </div>
                  </div>
                  
                  <!-- Selection Glow -->
                  <div v-if="selectedClientIds.includes(cliente.id)" class="absolute -right-4 -bottom-4 w-12 h-12 bg-brand-500/20 blur-2xl rounded-full"></div>
                </button>
              </div>

              <div v-if="filteredClients.length === 0" class="py-24 text-center bg-white/[0.01] border-2 border-dashed border-white/5 rounded-[2rem]">
                <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="faFilter" class="text-slate-700 text-2xl" />
                </div>
                <p class="text-slate-500 text-xs font-black uppercase tracking-wide">Sin resultados</p>
                <p class="text-[10px] text-slate-600 mt-2 font-medium">Ajusta los filtros para encontrar más clientes.</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.02);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.2);
}
</style>
