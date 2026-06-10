<template>
  <AppLayout title="Nueva Auditoría">
    <template #header>
      <div class="mb-8">
        <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-wider">Nueva Sesión de Inventario</h2>
        <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-wide mt-1">Configura el punto de inicio para el conteo físico</p>
      </div>
    </template>

    <div class="px-6 pb-12">
      <div class="max-w-3xl mx-auto">
        <form @submit.prevent="submit" class="glass-panel rounded-[3rem] p-12 border border-[var(--ui-border)] shadow-2xl relative overflow-hidden group">
          <!-- Decorative Background Elements -->
          <div class="absolute -top-24 -right-24 w-64 h-64 bg-[var(--ui-accent)]/5 blur-[100px] rounded-full group-hover:bg-[var(--ui-accent)]/10 transition-colors duration-700"></div>
          
          <div class="grid grid-cols-1 gap-10 relative z-10">
            <!-- Nombre de la Auditoría -->
            <div class="space-y-6">
              <label class="text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em] ml-2">Nombre de la Auditoría</label>
              <div class="relative group/field">
                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                  <font-awesome-icon icon="tag" class="text-[var(--ui-text-muted)] text-sm group-focus-within/field:text-[var(--ui-accent)] transition-colors" />
                </div>
                <input 
                  v-model="form.nombre"
                  type="text"
                  placeholder="EJ: INVENTARIO ANUAL ABRIL 2026"
                  class="w-full pl-14 pr-8 py-5 bg-[var(--ui-surface-soft)] border-2 border-[var(--ui-border)] rounded-2xl text-sm font-bold text-[var(--ui-text)] uppercase tracking-wider focus:border-[var(--ui-accent)] focus:ring-2 focus:ring-[var(--ui-accent)]/10 transition-all outline-none"
                  required
                >
              </div>
              <p v-if="form.errors.nombre" class="text-[10px] font-black text-rose-500 uppercase tracking-wide ml-2">{{ form.errors.nombre }}</p>
            </div>

            <!-- Selección de Almacén -->
            <div class="space-y-6">
              <label class="text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em] ml-2">Almacén a Auditar</label>
              <div class="relative group/field">
                <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                  <font-awesome-icon icon="warehouse" class="text-[var(--ui-text-muted)] text-sm group-focus-within/field:text-[var(--ui-accent)] transition-colors" />
                </div>
                <select 
                  v-model="form.almacen_id"
                  class="w-full pl-14 pr-8 py-5 bg-[var(--ui-surface-soft)] border-2 border-[var(--ui-border)] rounded-2xl text-sm font-bold text-[var(--ui-text)] uppercase tracking-wider focus:border-[var(--ui-accent)] focus:ring-2 focus:ring-[var(--ui-accent)]/10 transition-all outline-none appearance-none"
                  required
                >
                  <option value="" disabled>Selecciona un almacén...</option>
                  <option v-for="alm in almacenes" :key="alm.id" :value="alm.id">{{ alm.nombre }}</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                  <font-awesome-icon icon="chevron-down" class="text-[var(--ui-text-muted)] text-xs" />
                </div>
              </div>
              <p v-if="form.errors.almacen_id" class="text-[10px] font-black text-rose-500 uppercase tracking-wide ml-2">{{ form.errors.almacen_id }}</p>
            </div>

            <!-- Notas -->
            <div class="space-y-6">
              <label class="text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em] ml-2">Observaciones / Notas</label>
              <textarea 
                v-model="form.notas"
                rows="4"
                placeholder="NOTAS ADICIONALES PARA EL EQUIPO DE CONTEO..."
                class="w-full p-8 bg-[var(--ui-surface-soft)] border-2 border-[var(--ui-border)] rounded-3xl text-sm font-bold text-[var(--ui-text)] uppercase tracking-wider focus:border-[var(--ui-accent)] focus:ring-2 focus:ring-[var(--ui-accent)]/10 transition-all outline-none"
              ></textarea>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end gap-6 pt-6">
              <Link 
                :href="route('inventarios-fisicos.index')"
                class="px-8 py-4 text-[10px] font-black text-[var(--ui-text-muted)] hover:text-[var(--ui-text)] uppercase tracking-[0.2em] transition-colors"
              >
                Cancelar
              </Link>
              <button 
                type="submit"
                :disabled="form.processing"
                class="premium-button flex items-center gap-2 px-10 py-5 rounded-2xl bg-gradient-to-br from-[var(--ui-accent)] to-brand-600 text-white font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-brand-500/20 hover:scale-105 active:scale-95 disabled:opacity-50 transition-all duration-200"
              >
                <font-awesome-icon v-if="form.processing" icon="sync-alt" spin />
                <template v-else>
                  Iniciar Auditoría
                  <font-awesome-icon icon="arrow-right" class="ml-2" />
                </template>
              </button>
            </div>
          </div>
        </form>

        <!-- Información Útil -->
        <div class="mt-12 flex items-start gap-6 px-12 py-8 rounded-[2rem] bg-brand-500/5 border border-blue-500/20">
          <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shrink-0">
            <font-awesome-icon icon="circle-info" />
          </div>
          <div>
            <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-wide mb-1">Nota sobre el proceso</h4>
            <p class="text-xs font-bold text-[var(--ui-text-soft)] uppercase leading-relaxed tracking-tight">
              Al iniciar la auditoría, el sistema tomará una captura instantánea de las existencias actuales. 
              Podrás realizar el conteo físico desde la aplicación móvil o desde este panel. 
              Los ajustes finales se aplicarán solo cuando decidas "Procesar" la sesión.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTag, faWarehouse, faChevronDown, faArrowRight, faCircleInfo, faSyncAlt } from '@fortawesome/free-solid-svg-icons';
import { library } from '@fortawesome/fontawesome-svg-core';

library.add(faTag, faWarehouse, faChevronDown, faArrowRight, faCircleInfo, faSyncAlt);

defineProps({
  almacenes: Array
});

const form = useForm({
  nombre: '',
  almacen_id: '',
  notas: ''
});

const submit = () => {
  form.post(route('inventarios-fisicos.store'));
};
</script>
