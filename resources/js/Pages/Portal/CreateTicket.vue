<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ClientLayout from './Layout/ClientLayout.vue';

const props = defineProps({
  categorias: Array,
  poliza: Object, // Póliza activa del cliente
});

const page = usePage();

// Colores corporativos desde la configuración de empresa
const empresaConfig = computed(() => page.props.empresa_config || {});

// cssVars removed to use global theme variables controlled by ClientLayout


const form = useForm({
  titulo: '',
  descripcion: '',
  categoria_id: '',
  prioridad: 'media',
});

const submit = () => {
    // Si hay advertencia pendiente y no ha sido aceptada, mostrar modal de nuevo o prevenir envío si es crítico
    if (cobroPendienteConfirmacion.value) {
        showCostoExtraModal.value = true;
        return;
    }
    form.post(route('portal.tickets.store'));
};

// Modal de advertencia para prioridad Urgente
const showUrgenciaModal = ref(false);

// Modal de Costo Extra (Póliza Agotada)
const showCostoExtraModal = ref(false);
const costoExtraData = ref({
    tipo: '', // 'horas' o 'tickets'
    costo: 0,
    mensaje: ''
});
const cobroPendienteConfirmacion = ref(false);

watch(() => form.prioridad, (newVal) => {
    if (newVal === 'urgente') {
        showUrgenciaModal.value = true;
    }
});

// Watcher para detectar si la categoría consume póliza y si esta está agotada
watch(() => form.categoria_id, (newVal) => {
    if (!newVal || !props.poliza) return;

    // Comparar como números para evitar problemas de tipo string vs int
    const cat = props.categorias.find(c => Number(c.id) === Number(newVal));
    if (!cat) return;

    // Reiniciar estado
    cobroPendienteConfirmacion.value = false;

    // Verificar si la categoría consume póliza
    if (cat.consume_poliza) {
        // Verificar Exceso de Horas (Prioridad 1)
        if (props.poliza.excede_horas) {
            costoExtraData.value = {
                tipo: 'horas',
                costo: props.poliza.costo_hora_extra_aplicable,
                mensaje: `Hola, notamos que has utilizado todas las horas incluidas en tu póliza mensual. Podemos atenderte con gusto bajo una tarifa preferencial de`
            };
            showCostoExtraModal.value = true;
            cobroPendienteConfirmacion.value = true;
            return;
        }

        // Verificar Exceso de Tickets (Prioridad 2)
        if (props.poliza.excede_limite) {
            costoExtraData.value = {
                tipo: 'tickets',
                costo: props.poliza.costo_ticket_extra_aplicable,
                mensaje: `Has alcanzado el límite de tickets incluidos en tu plan mensual. Este servicio generará un costo adicional de`
            };
            showCostoExtraModal.value = true;
            cobroPendienteConfirmacion.value = true;
            return;
        }

        // Verificar Exceso de Visitas (Prioridad 3)
        if (props.poliza.excede_limite_visitas) {
            costoExtraData.value = {
                tipo: 'visitas',
                costo: props.poliza.costo_visita_extra_aplicable,
                mensaje: `Has utilizado todas las visitas en sitio incluidas en tu plan mensual. Este servicio generará un costo adicional de`
            };
            showCostoExtraModal.value = true;
            cobroPendienteConfirmacion.value = true;
        }
    }
});

const confirmarUrgencia = () => {
    showUrgenciaModal.value = false;
    // Se mantiene en 'urgente'
};

const cambiarPrioridad = () => {
    form.prioridad = 'alta'; 
    showUrgenciaModal.value = false;
};

const aceptarCostoExtra = () => {
    showCostoExtraModal.value = false;
    cobroPendienteConfirmacion.value = false; // Usuario aceptó
};

const cancelarPorCosto = () => {
    showCostoExtraModal.value = false;
    form.categoria_id = ''; // Resetear categoría
    cobroPendienteConfirmacion.value = false;
};

// Formatear moneda
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(amount);
};
</script>

<template>
  <ClientLayout>
    <div class="w-full transition-colors duration-200">
      <div class="bg-white dark:bg-slate-800/50 shadow-xl dark:shadow-2xl sm:rounded-2xl border border-slate-100 dark:border-white/10 dark:backdrop-blur-xl transition-all duration-200">
        <div class="px-6 py-8 sm:p-10">
          <!-- Header con icono -->
          <div class="flex items-center gap-4 mb-6">
            <div class="w-10 h-10 rounded-xl bg-[var(--color-primary-soft)] dark:bg-[var(--color-primary)]/20 flex items-center justify-center">
              <svg class="w-10 h-10 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-slate-900 dark:text-white">Crear Nuevo Ticket</h3>
              <p class="text-sm text-slate-500 dark:text-slate-400">Describe tu problema y nuestro equipo te atenderá lo antes posible.</p>
            </div>
          </div>
          
          <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Título -->
            <div>
              <label for="titulo" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Asunto</label>
              <input
                type="text"
                name="titulo"
                id="titulo"
                v-model="form.titulo"
                class="w-full px-4 py-3 border border-slate-200 dark:border-white/10 dark:bg-slate-950/50 dark:text-white rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] dark:focus:ring-[var(--color-primary)]/30 focus:border-[var(--color-primary)] transition-all placeholder-slate-400 dark:placeholder-slate-600"
                placeholder="Ej: No puedo acceder al sistema"
              />
              <div v-if="form.errors.titulo" class="text-rose-500 text-xs mt-1">{{ form.errors.titulo }}</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Categoría -->
              <div>
                <label for="categoria" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Categoría</label>
                <select
                  id="categoria"
                  name="categoria"
                  v-model="form.categoria_id"
                  class="w-full px-4 py-3 border border-slate-200 dark:border-white/10 dark:bg-slate-950/50 dark:text-white rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] dark:focus:ring-[var(--color-primary)]/30 focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="" disabled class="dark:bg-slate-800">Selecciona una categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id" class="dark:bg-slate-800">
                    {{ cat.nombre }}
                  </option>
                </select>
                <div v-if="form.errors.categoria_id" class="text-rose-500 text-xs mt-1">{{ form.errors.categoria_id }}</div>
              </div>

              <!-- Prioridad -->
              <div>
                <label for="prioridad" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Prioridad</label>
                <select
                  id="prioridad"
                  name="prioridad"
                  v-model="form.prioridad"
                  class="w-full px-4 py-3 border border-slate-200 dark:border-white/10 dark:bg-slate-950/50 dark:text-white rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] dark:focus:ring-[var(--color-primary)]/30 focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="baja" class="dark:bg-slate-800">🟢 Baja (Consultas generales)</option>
                  <option value="media" class="dark:bg-slate-800">🟡 Media (Problemas funcionales)</option>
                  <option value="alta" class="dark:bg-slate-800">🟠 Alta (Bloqueo de trabajo)</option>
                  <option value="urgente" class="dark:bg-slate-800">🔴 Urgente (Sistema caído)</option>
                </select>
                <div v-if="form.errors.prioridad" class="text-rose-500 text-xs mt-1">{{ form.errors.prioridad }}</div>
              </div>
            </div>
              
            <!-- Descripción -->
            <div>
              <label for="descripcion" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Descripción detallada</label>
              <textarea
                id="descripcion"
                name="descripcion"
                rows="5"
                v-model="form.descripcion"
                class="w-full px-4 py-3 border border-slate-200 dark:border-white/10 dark:bg-slate-950/50 dark:text-white rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] dark:focus:ring-[var(--color-primary)]/30 focus:border-[var(--color-primary)] transition-all resize-none placeholder-slate-400 dark:placeholder-slate-600"
                placeholder="Describe tu problema con el mayor detalle posible..."
              ></textarea>
              <div v-if="form.errors.descripcion" class="text-rose-500 text-xs mt-1">{{ form.errors.descripcion }}</div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4">
              <Link 
                :href="route('portal.dashboard')" 
                class="px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl font-semibold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-all border border-transparent dark:border-white/5"
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

  <!-- Modales -->
  <Teleport to="body">
    <!-- Modal Urgencia -->
    <div v-if="showUrgenciaModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="cambiarPrioridad"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border dark:border-white/10">
                <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-rose-50 dark:bg-rose-900/20/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-white" id="modal-title">
                                ¿Es realmente una Urgencia Crítica?
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    La prioridad <strong>Urgente</strong> está reservada para casos donde la operación está totalmente detenida.
                                </p>
                                <div class="mt-4 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/10 p-3 rounded-xl border border-rose-100 dark:border-rose-900/20">
                                    <p class="text-xs font-bold text-rose-800 dark:text-rose-200 dark:text-rose-300 mb-1">EJEMPLO DE URGENCIA:</p>
                                    <p class="text-xs text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400">
                                        "El servidor principal está apagado y nadie en la empresa puede trabajar." o "El sistema de facturación está caído y no podemos cobrar."
                                    </p>
                                </div>
                                <p class="mt-4 text-sm text-slate-500 dark:text-slate-400 italic">
                                    ⚠️ <strong>Nota Importante:</strong> Un técnico analizará su solicitud. Si el reporte no cumple con los criterios de urgencia crítica, la prioridad será ajustada automáticamente a su nivel correspondiente.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-slate-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t dark:border-white/5">
                    <button 
                        type="button" 
                        class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-base font-medium text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="confirmarUrgencia"
                    >
                        Sí, es Urgente
                    </button>
                    <button 
                        type="button" 
                        class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 dark:border-slate-700 shadow-sm px-4 py-2 bg-white dark:bg-slate-700 text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        @click="cambiarPrioridad"
                    >
                        Cambiar Prioridad
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Costo Extra (Póliza Agotada) -->
    <div v-if="showCostoExtraModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="cancelarPorCosto"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="fast-fade-in inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border dark:border-white/10">
                <!-- Header Amable -->
                <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-4 sm:px-6">
                    <div class="flex items-center gap-2">
                        <div class="bg-white/20 p-2 rounded-xl text-white">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">
                            ¡Queremos ayudarte!
                        </h3>
                    </div>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <p class="text-slate-500 dark:text-slate-200 text-base leading-relaxed">
                        {{ costoExtraData.mensaje }} 
                        <span class="font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-sky-900/30 px-2 py-0.5 rounded-xl">{{ formatCurrency(costoExtraData.costo) }}</span>
                        {{ costoExtraData.tipo === 'horas' ? '/ hora extra' : '/ ticket extra' }}.
                    </p>
                    
                    <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border border-blue-100 dark:border-blue-900/30 rounded-xl p-4 flex gap-3">
                        <span class="text-2xl">💡</span>
                        <div class="text-sm text-sky-800 dark:text-sky-200 dark:text-blue-300">
                            <strong>¿Sabías que?</strong> 
                            Continuar con el servicio asegura que tu reporte sea atendido de inmediato por nuestros expertos. El cargo se añadirá a tu próximo estado de cuenta.
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-black/50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 border-t dark:border-white/5">
                    <button 
                        type="button" 
                        class="w-full sm:w-auto px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-600 transition-colors"
                        @click="cancelarPorCosto"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 shadow-xl shadow-indigo-200 transition-all transform hover:scale-105"
                        @click="aceptarCostoExtra"
                    >
                        Entendido, continuar con el servicio
                    </button>
                </div>
            </div>
        </div>
    </div>
  </Teleport>
</template>
