<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>
        
        <!-- Modal Content -->
        <Transition name="modal-scale" appear>
          <div class="relative w-full max-w-xl transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-slate-800 p-8 text-left shadow-2xl transition-all border border-white/20 flex flex-col max-h-[90vh]">
            
            <!-- Icon Header -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2">
              <div class="w-24 h-24 rounded-[2rem] bg-gradient-to-br from-brand-500 to-indigo-600 flex items-center justify-center shadow-2xl border-4 border-white dark:border-slate-800 rotate-6">
                <FontAwesomeIcon :icon="['fas', 'list-ul']" class="h-10 w-10 text-white" />
              </div>
            </div>

            <div class="mt-8 text-center">
              <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                Tus Pendientes
              </h3>
              <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">
                Tienes {{ tareas?.length || 0 }} tareas esperando tu atención.
              </p>
            </div>

            <!-- Task List Area -->
            <div class="mt-8 overflow-y-auto custom-scrollbar pr-2 space-y-4 flex-1">
              <div v-for="t in tareas" :key="t.id + t.tipo" class="group relative p-5 rounded-3xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-700 hover:border-brand-500/30 transition-all">
                <div class="flex items-start gap-4">
                  <!-- Type Icon -->
                  <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <FontAwesomeIcon :icon="['fas', t.icon]" :class="t.vencida ? 'text-rose-500' : 'text-brand-500'" class="h-5 w-5" />
                  </div>

                  <!-- Details -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                      <h4 class="text-sm font-black text-slate-900 dark:text-white truncate uppercase tracking-wide">
                        {{ t.titulo }}
                      </h4>
                      <span v-if="t.vencida" class="px-2 py-0.5 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 text-[8px] font-black uppercase animate-pulse">Vencida</span>
                    </div>
                    <p v-if="t.descripcion" class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-1 italic">
                      {{ t.descripcion }}
                    </p>

                    <div class="mt-3 flex items-center gap-4">
                      <div class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase">
                        <FontAwesomeIcon :icon="['fas', 'calendar']" />
                        {{ t.fecha }}
                      </div>
                      <div class="flex items-center gap-1.5 text-[9px] font-bold uppercase" :class="t.prioridad === 1 ? 'text-rose-500' : 'text-brand-500'">
                        <FontAwesomeIcon :icon="['fas', 'circle']" class="text-[6px]" />
                        Prioridad {{ t.prioridad === 1 ? 'Alta' : 'Normal' }}
                      </div>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="flex items-center gap-2 shrink-0">
                    <button
                      @click="deleteTodo(t)"
                      class="w-10 h-10 rounded-xl bg-rose-500/10 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all"
                      title="Eliminar tarea"
                    >
                      <FontAwesomeIcon :icon="['fas', 'trash']" />
                    </button>
                    <PanLink
                      :href="t.url"
                      class="w-10 h-10 rounded-xl bg-brand-500/10 text-brand-500 flex items-center justify-center hover:bg-brand-500 hover:text-white transition-all"
                      @click="close"
                    >
                      <FontAwesomeIcon :icon="['fas', 'chevron-right']" />
                    </PanLink>
                  </div>
                </div>
              </div>
            </div>

            <!-- Global Actions -->
            <div class="mt-8 grid grid-cols-2 gap-4">
              <button
                type="button"
                class="flex items-center justify-center gap-2 px-6 py-4 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-black uppercase text-[10px] tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-600 transition-all"
                @click="close"
              >
                Cerrar Lista
              </button>
              <PanLink
                href="/mis-pendientes"
                class="flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-brand-500 to-indigo-600 text-white font-black uppercase text-[10px] tracking-widest rounded-2xl shadow-xl shadow-brand-500/20 hover:shadow-2xl hover:scale-[1.02] transition-all"
                @click="close"
              >
                Ver Todo
              </PanLink>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import PanLink from './PanLink.vue'
import { router } from '@inertiajs/vue3'
import Swal from 'sweetalert2'

defineProps({
  show: Boolean,
  tareas: Array
})

const emit = defineEmits(['close'])

const close = () => {
  emit('close')
}

const deleteTodo = async (t) => {
  const { isConfirmed } = await Swal.fire({
    title: 'Eliminar tarea',
    text: '¿Estás seguro de eliminar esta tarea?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#ef4444',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'No',
    background: '#1e293b',
    color: '#ffffff'
  });

  if (isConfirmed) {
    const id = t.tipo === 'bitacora' ? 'B' + t.id : t.id;
    router.delete(`/mis-pendientes/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // La lista se actualizará automáticamente vía Inertia props
      }
    });
  }
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

.modal-scale-enter-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-scale-leave-active {
  transition: all 0.3s ease-in;
}
.modal-scale-enter-from {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}
.modal-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
