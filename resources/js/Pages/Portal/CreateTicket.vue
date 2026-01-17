<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ClientLayout from './Layout/ClientLayout.vue';

const props = defineProps({
  categorias: Array,
});

const page = usePage();

// Colores corporativos desde la configuración de empresa
const empresaConfig = computed(() => page.props.empresa_config || {});

const cssVars = computed(() => ({
  '--color-primary': empresaConfig.value.color_principal || '#F59E0B',
  '--color-primary-dark': (empresaConfig.value.color_principal || '#F59E0B') + 'dd',
  '--color-primary-soft': (empresaConfig.value.color_principal || '#F59E0B') + '20',
}));

const form = useForm({
  titulo: '',
  descripcion: '',
  categoria_id: '',
  prioridad: 'media',
});

const submit = () => {
  form.post(route('portal.tickets.store'));
};

// Modal de advertencia para prioridad Urgente
import { ref, watch } from 'vue';

const showUrgenciaModal = ref(false);

watch(() => form.prioridad, (newVal) => {
    if (newVal === 'urgente') {
        showUrgenciaModal.value = true;
    }
});

const confirmarUrgencia = () => {
    showUrgenciaModal.value = false;
    // Se mantiene en 'urgente'
};

const cambiarPrioridad = () => {
    form.prioridad = 'alta'; // O media, según preferencia. Alta es un buen fallback.
    showUrgenciaModal.value = false;
};
</script>

<template>
  <ClientLayout>
    <div class="w-full" :style="cssVars">
      <div class="bg-white shadow-lg sm:rounded-2xl border border-gray-100">
        <div class="px-6 py-8 sm:p-10">
          <!-- Header con icono -->
          <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-xl bg-[var(--color-primary-soft)] flex items-center justify-center">
              <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Ticket</h3>
              <p class="text-sm text-gray-500">Describe tu problema y nuestro equipo te atenderá lo antes posible.</p>
            </div>
          </div>
          
          <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Título -->
            <div>
              <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Asunto</label>
              <input
                type="text"
                name="titulo"
                id="titulo"
                v-model="form.titulo"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                placeholder="Ej: No puedo acceder al sistema"
              />
              <div v-if="form.errors.titulo" class="text-red-500 text-xs mt-1">{{ form.errors.titulo }}</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Categoría -->
              <div>
                <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoría</label>
                <select
                  id="categoria"
                  name="categoria"
                  v-model="form.categoria_id"
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="" disabled>Selecciona una categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
                <div v-if="form.errors.categoria_id" class="text-red-500 text-xs mt-1">{{ form.errors.categoria_id }}</div>
              </div>

              <!-- Prioridad -->
              <div>
                <label for="prioridad" class="block text-sm font-semibold text-gray-700 mb-2">Prioridad</label>
                <select
                  id="prioridad"
                  name="prioridad"
                  v-model="form.prioridad"
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="baja">🟢 Baja (Consultas generales)</option>
                  <option value="media">🟡 Media (Problemas funcionales)</option>
                  <option value="alta">🟠 Alta (Bloqueo de trabajo)</option>
                  <option value="urgente">🔴 Urgente (Sistema caído)</option>
                </select>
                <div v-if="form.errors.prioridad" class="text-red-500 text-xs mt-1">{{ form.errors.prioridad }}</div>
              </div>
            </div>
              
            <!-- Descripción -->
            <div>
              <label for="descripcion" class="block text-sm font-semibold text-gray-700 mb-2">Descripción detallada</label>
              <textarea
                id="descripcion"
                name="descripcion"
                rows="5"
                v-model="form.descripcion"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all resize-none"
                placeholder="Describe tu problema con el mayor detalle posible..."
              ></textarea>
              <div v-if="form.errors.descripcion" class="text-red-500 text-xs mt-1">{{ form.errors.descripcion }}</div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4">
              <Link 
                :href="route('portal.dashboard')" 
                class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 transition-all"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-semibold text-sm hover:opacity-90 transition-all disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Crear Ticket
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ClientLayout>

  <Teleport to="body">
    <div v-if="showUrgenciaModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-white0 bg-opacity-75 transition-opacity" aria-hidden="true" @click="cambiarPrioridad"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                ¿Es realmente una Urgencia Crítica?
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    La prioridad <strong>Urgente</strong> está reservada para casos donde la operación está totalmente detenida.
                                </p>
                                <div class="mt-4 bg-red-50 p-3 rounded-md border border-red-100">
                                    <p class="text-xs font-bold text-red-800 mb-1">EJEMPLO DE URGENCIA:</p>
                                    <p class="text-xs text-red-700">
                                        "El servidor principal está apagado y nadie en la empresa puede trabajar." o "El sistema de facturación está caído y no podemos cobrar."
                                    </p>
                                </div>
                                <p class="mt-4 text-sm text-gray-600 italic">
                                    ⚠️ <strong>Nota Importante:</strong> Un técnico analizará su solicitud. Si el reporte no cumple con los criterios de urgencia crítica, la prioridad será ajustada automáticamente a su nivel correspondiente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button 
                        type="button" 
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="confirmarUrgencia"
                    >
                        Sí, es Urgente
                    </button>
                    <button 
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="cambiarPrioridad"
                    >
                        Cambiar Prioridad
                    </button>
                </div>
            </div>
        </div>
    </div>
  </Teleport>
</template>
