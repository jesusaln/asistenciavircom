<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-slate-900 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden border border-slate-800 flex flex-col" role="dialog" :aria-labelledby="modalTitleId" aria-modal="true">
          
          <!-- Header del modal con Gradiente Premium -->
          <div class="flex justify-between items-center px-8 py-6 bg-gradient-to-r from-slate-900 to-slate-800 border-b border-slate-800 relative overflow-hidden">
             <!-- Efecto decorativo -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-16 -mt-16 pointer-events-none"></div>

            <div class="flex items-center gap-4 relative z-10">
              <div class="w-12 h-12 rounded-2xl bg-slate-800 border border-slate-700 flex items-center justify-center shadow-lg">
                <span class="text-xl font-black text-indigo-400">
                  {{ cliente.nombre_razon_social ? cliente.nombre_razon_social.substring(0,2).toUpperCase() : 'CL' }}
                </span>
              </div>
              <div>
                <h2 :id="modalTitleId" class="text-xl font-black text-white tracking-tight">
                  Detalles del Cliente
                </h2>
                <div class="flex items-center gap-2 mt-1">
                   <span class="w-1.5 h-1.5 rounded-full" :class="cliente.activo !== false ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500'"></span>
                   <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                     {{ cliente.activo !== false ? 'Cliente Activo' : 'Cliente Inactivo' }}
                   </p>
                </div>
              </div>
            </div>

            <button @click="closeModal" class="text-slate-500 hover:text-white bg-slate-800/50 hover:bg-slate-700 p-2.5 rounded-xl transition-all border border-transparent hover:border-slate-600 relative z-10" aria-label="Cerrar modal">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido con Scroll Personalizado -->
          <div class="p-8 overflow-y-auto custom-scrollbar bg-slate-950/50 flex-1">
            <div v-if="hasClientData" class="space-y-8">
              
              <!-- Tarjeta 1: Información General -->
              <div class="bg-slate-900/50 rounded-3xl border border-slate-800 overflow-hidden shadow-sm hover:border-indigo-500/30 transition-colors duration-300">
                <div class="px-6 py-4 border-b border-slate-800 bg-slate-900 flex items-center gap-3">
                  <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                  </div>
                  <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest">Identidad Corporativa</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                  <ClientField label="Razón Social" :value="cliente.nombre_razon_social" />
                  <ClientField label="RFC / Tax ID" :value="cliente.rfc" :is-highlight="true" />
                </div>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Tarjeta 2: Contacto -->
                <div class="bg-slate-900/50 rounded-3xl border border-slate-800 overflow-hidden shadow-sm hover:border-emerald-500/30 transition-colors duration-300 h-full">
                  <div class="px-6 py-4 border-b border-slate-800 bg-slate-900 flex items-center gap-3">
                    <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-400">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest">Medios de Contacto</h3>
                  </div>
                  <div class="p-6 space-y-6">
                    <ClientField label="Correo Electrónico" :value="cliente.email" type="email" />
                    <ClientField label="Teléfono / Móvil" :value="cliente.telefono" type="phone" />
                  </div>
                </div>

                <!-- Tarjeta 3: Fiscal -->
                <div class="bg-slate-900/50 rounded-3xl border border-slate-800 overflow-hidden shadow-sm hover:border-purple-500/30 transition-colors duration-300 h-full">
                  <div class="px-6 py-4 border-b border-slate-800 bg-slate-900 flex items-center gap-3">
                    <div class="p-2 bg-purple-500/10 rounded-lg text-purple-400">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest">Datos Fiscales (SAT)</h3>
                  </div>
                  <div class="p-6 space-y-6">
                    <ClientField label="Régimen Fiscal" :value="cliente.regimen_fiscal" />
                    <ClientField label="Uso de CFDI" :value="cliente.uso_cfdi" />
                  </div>
                </div>
              </div>

              <!-- Tarjeta 4: Dirección -->
              <div class="bg-slate-900/50 rounded-3xl border border-slate-800 overflow-hidden shadow-sm hover:border-amber-500/30 transition-colors duration-300">
                <div class="px-6 py-4 border-b border-slate-800 bg-slate-900 flex items-center gap-3">
                  <div class="p-2 bg-amber-500/10 rounded-lg text-amber-400">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                  </div>
                  <h3 class="text-xs font-black text-slate-300 uppercase tracking-widest">Ubicación Física</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  <ClientField label="Dirección Completa" :value="formatAddress" class="md:col-span-2 lg:col-span-3" :is-address="true" />
                  <ClientField label="Colonia" :value="cliente.colonia" />
                  <ClientField label="Ciudad / Municipio" :value="cliente.municipio" />
                  <ClientField label="Código Postal" :value="cliente.codigo_postal" :is-mono="true" />
                  <ClientField label="Estado / Provincia" :value="cliente.estado" />
                  <ClientField label="País" :value="cliente.pais" />
                </div>
              </div>

            </div>

            <!-- Estado vacío mejorado -->
            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
              <div class="w-20 h-20 bg-slate-900 rounded-full border-2 border-dashed border-slate-700 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <h3 class="text-lg font-black text-white uppercase tracking-wider mb-2">Sin Información</h3>
              <p class="text-sm text-slate-500 font-medium">No hay datos disponibles para mostrar.</p>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="px-8 py-5 bg-slate-900 border-t border-slate-800 flex justify-end">
            <button @click="closeModal" class="px-8 py-3 bg-slate-800 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-700 hover:shadow-lg transition-all border border-slate-700">
              Cerrar Detalle
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ClientField from './ClientField.vue';

const props = defineProps({
  cliente: { type: Object, default: () => ({}) },
  isOpen: { type: Boolean, default: false }
});

const emit = defineEmits(['close']);

const modalTitleId = ref(`modal-title-${Math.random().toString(36).substr(2, 9)}`);

const hasClientData = computed(() => {
  return props.cliente && Object.keys(props.cliente).length > 0;
});

const formatAddress = computed(() => {
  const { calle, numero_exterior, numero_interior } = props.cliente;
  const parts = [
    calle, 
    numero_exterior ? `Num. ${numero_exterior}` : null, 
    numero_interior ? `Int. ${numero_interior}` : null
  ].filter(Boolean);
  return parts.length > 0 ? parts.join(', ') : 'Dirección no especificada';
});

const closeModal = () => emit('close');

const handleEscapeKey = (event) => {
  if (event.key === 'Escape' && props.isOpen) closeModal();
};

onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey);
  if (props.isOpen) document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscapeKey);
  document.body.style.overflow = '';
});

watch(() => props.isOpen, (newVal) => {
  document.body.style.overflow = newVal ? 'hidden' : '';
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }

/* Scrollbar personalizado dark */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #0f172a; } /* slate-900 */
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; } /* slate-700 */
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; } /* slate-600 */
</style>
