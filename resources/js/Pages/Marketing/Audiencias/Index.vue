<template>
  <AppLayout title="Marketing - Audiencias">
    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
          <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Audiencias de Clientes</h2>
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Selecciona exactamente a quiénes sí les quieres mandar campañas y guarda grupos reutilizables.</p>
        </div>
        <div class="text-[10px] font-black uppercase tracking-widest text-emerald-600">
          {{ clientes.length }} clientes con consentimiento
        </div>
      </div>
    </template>

    <div class="py-10 px-6">
      <div class="grid grid-cols-1 xl:grid-cols-[360px_minmax(0,1fr)] gap-8">
        <section class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-[2rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">Audiencias Guardadas</h3>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">{{ audiencias.length }}</span>
          </div>

          <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
            <button
              type="button"
              @click="resetBuilder"
              class="w-full text-left p-4 rounded-2xl border-2 transition-all"
              :class="!selectedAudienceId ? 'border-amber-500 bg-amber-50/80 text-slate-900' : 'border-dashed border-slate-200 dark:border-slate-800 text-slate-500'"
            >
              <div class="text-xs font-black uppercase tracking-widest">Nueva Audiencia</div>
              <div class="text-[11px] mt-2">Arma una selección manual desde cero.</div>
            </button>

            <button
              v-for="audiencia in audiencias"
              :key="audiencia.id"
              type="button"
              @click="loadAudience(audiencia)"
              class="w-full text-left p-4 rounded-2xl border-2 transition-all"
              :class="selectedAudienceId === audiencia.id ? 'border-amber-500 bg-amber-50/80 dark:bg-amber-500/10' : 'border-slate-100 dark:border-slate-800 hover:border-amber-300 bg-slate-50/70 dark:bg-slate-950/30'"
            >
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">{{ audiencia.nombre }}</div>
                  <div class="text-[11px] text-slate-500 mt-2 line-clamp-2">{{ audiencia.descripcion || 'Sin descripción' }}</div>
                </div>
                <span class="px-2 py-1 rounded-lg bg-white dark:bg-slate-900 text-[10px] font-black text-emerald-600 border border-slate-200/50 dark:border-slate-800">
                  {{ audiencia.clientes_count }}
                </span>
              </div>

              <div class="mt-4 flex items-center justify-between text-[10px] uppercase tracking-widest">
                <span class="text-slate-400">Por {{ audiencia.creador?.name || 'Sistema' }}</span>
                <div class="flex items-center gap-3">
                  <Link
                    :href="route('marketing.campanias.create', { audiencia: audiencia.id })"
                    class="text-amber-600 hover:text-amber-700"
                  >
                    Usar
                  </Link>
                  <button type="button" class="text-rose-500 hover:text-rose-600" @click.stop="destroyAudience(audiencia)">
                    Eliminar
                  </button>
                </div>
              </div>
            </button>
          </div>
        </section>

        <section class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-[2rem] border border-slate-200/50 dark:border-slate-800/50 shadow-xl p-6">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
              <h3 class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">Constructor de Audiencia</h3>
              <p class="text-[11px] text-slate-500 mt-2">Solo aparecen clientes con consentimiento de marketing y WhatsApp.</p>
            </div>
            <div class="px-4 py-2 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10px] font-black uppercase tracking-widest">
              {{ selectedClientIds.length }} seleccionados
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)] gap-8">
            <div class="space-y-4">
              <div>
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Nombre</label>
                <input v-model="form.nombre" type="text" class="w-full rounded-2xl border-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/70 dark:bg-slate-950/30" placeholder="Ej. Clientes VIP Hermosillo">
              </div>

              <div>
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Descripción</label>
                <textarea v-model="form.descripcion" rows="4" class="w-full rounded-2xl border-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/70 dark:bg-slate-950/30" placeholder="Qué tienen en común y para qué la usarás"></textarea>
              </div>

              <div class="space-y-3">
                <button
                  type="button"
                  @click="saveAudience"
                  :disabled="form.processing || selectedClientIds.length === 0"
                  class="w-full px-5 py-4 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-white text-xs font-black uppercase tracking-widest disabled:opacity-50"
                >
                  {{ selectedAudienceId ? 'Actualizar Audiencia' : 'Guardar Audiencia' }}
                </button>
                <button
                  type="button"
                  @click="selectAllFiltered"
                  class="w-full px-5 py-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300"
                >
                  Seleccionar filtrados
                </button>
                <button
                  type="button"
                  @click="clearSelection"
                  class="w-full px-5 py-4 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300"
                >
                  Limpiar selección
                </button>
              </div>
            </div>

            <div>
              <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-5">
                <input
                  v-model="search"
                  type="text"
                  class="w-full md:max-w-md rounded-2xl border-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/70 dark:bg-slate-950/30"
                  placeholder="Buscar por nombre, teléfono o correo"
                >
                <div class="flex flex-wrap gap-3">
                  <label class="inline-flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-500">
                    <input v-model="onlyActive" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Solo activos
                  </label>
                  <label class="inline-flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-500">
                    <input v-model="onlyWithPurchases" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Con compras
                  </label>
                  <label class="inline-flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-500">
                    <input v-model="onlyWithPoliza" type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    Con póliza activa
                  </label>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <select v-model="selectedMunicipio" class="w-full rounded-2xl border-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/70 dark:bg-slate-950/30">
                  <option value="">Todas las ciudades</option>
                  <option v-for="municipio in municipios" :key="municipio" :value="municipio">{{ municipio }}</option>
                </select>

                <select v-model="selectedPriceListId" class="w-full rounded-2xl border-2 border-slate-100 dark:border-slate-800 px-4 py-3 bg-slate-50/70 dark:bg-slate-950/30">
                  <option value="">Todas las listas de precios</option>
                  <option v-for="priceList in priceLists" :key="priceList.id" :value="String(priceList.id)">{{ priceList.nombre }}</option>
                </select>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-1">
                <button
                  v-for="cliente in filteredClients"
                  :key="cliente.id"
                  type="button"
                  @click="toggleClient(cliente.id)"
                  class="text-left p-4 rounded-2xl border-2 transition-all"
                  :class="selectedClientIds.includes(cliente.id) ? 'border-emerald-500 bg-emerald-50/80 dark:bg-emerald-500/10' : 'border-slate-100 dark:border-slate-800 hover:border-amber-300 bg-slate-50/70 dark:bg-slate-950/30'"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <div class="text-xs font-black uppercase tracking-widest text-slate-900 dark:text-white">{{ cliente.nombre_razon_social }}</div>
                      <div class="text-[11px] text-slate-500 mt-2">{{ cliente.telefono || 'Sin teléfono' }}</div>
                      <div class="text-[11px] text-slate-400 mt-1">{{ cliente.email || 'Sin correo' }}</div>
                      <div class="mt-3 flex flex-wrap gap-2">
                        <span v-if="cliente.municipio" class="px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest text-slate-500">
                          {{ cliente.municipio }}
                        </span>
                        <span v-if="cliente.price_list_id" class="px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest text-indigo-600">
                          {{ cliente.price_list?.nombre || 'Lista asignada' }}
                        </span>
                        <span class="px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest text-emerald-600">
                          {{ cliente.ventas_count }} compras
                        </span>
                        <span class="px-2 py-1 rounded-lg bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 text-[10px] font-black uppercase tracking-widest text-amber-600">
                          {{ cliente.polizas_activas_count }} pólizas activas
                        </span>
                      </div>
                    </div>
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black"
                      :class="selectedClientIds.includes(cliente.id) ? 'bg-emerald-500 text-white' : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-400'">
                      {{ selectedClientIds.includes(cliente.id) ? '✓' : '+' }}
                    </span>
                  </div>
                </button>
              </div>

              <div v-if="filteredClients.length === 0" class="py-20 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl mt-4">
                <p class="text-slate-400 text-xs font-black uppercase tracking-widest">No hay clientes para ese filtro</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';

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
const selectedMunicipio = ref('');
const selectedPriceListId = ref('');

const form = useForm({
  nombre: '',
  descripcion: '',
  cliente_ids: [],
});

const filteredClients = computed(() => {
  const term = search.value.trim().toLowerCase();

  return props.clientes.filter((cliente) => {
    const matchesActive = !onlyActive.value || !!cliente.activo;
    const matchesPurchases = !onlyWithPurchases.value || Number(cliente.ventas_count || 0) > 0;
    const matchesPoliza = !onlyWithPoliza.value || Number(cliente.polizas_activas_count || 0) > 0;
    const matchesMunicipio = !selectedMunicipio.value || cliente.municipio === selectedMunicipio.value;
    const matchesPriceList = !selectedPriceListId.value || String(cliente.price_list_id || '') === selectedPriceListId.value;
    const matchesSearch = !term || [
      cliente.nombre_razon_social,
      cliente.telefono,
      cliente.email,
    ].filter(Boolean).some((value) => value.toLowerCase().includes(term));

    return matchesActive && matchesPurchases && matchesPoliza && matchesMunicipio && matchesPriceList && matchesSearch;
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
    form.put(route('marketing.audiencias.update', selectedAudienceId.value));
    return;
  }

  form.post(route('marketing.audiencias.store'), {
    onSuccess: () => resetBuilder(),
  });
};

const destroyAudience = (audiencia) => {
  if (!confirm(`¿Eliminar la audiencia "${audiencia.nombre}"?`)) {
    return;
  }

  router.delete(route('marketing.audiencias.destroy', audiencia.id), {
    onSuccess: () => {
      if (selectedAudienceId.value === audiencia.id) {
        resetBuilder();
      }
    },
  });
};
</script>
