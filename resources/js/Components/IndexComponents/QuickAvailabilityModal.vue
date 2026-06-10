<template>
  <Transition name="modal-fade">
    <div v-if="show" class="fixed inset-0 z-[120] flex items-center justify-center p-4 md:p-8" @click.self="$emit('close')">
      <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md"></div>
      
      <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col transition-all">
        <!-- Header -->
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 bg-transparent/30 dark:bg-slate-900/50 flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-brand-600 flex items-center justify-center text-white shadow-lg">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Verificador de Disponibilidad</h3>
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide">Consulta rapida de agenda tecnica</p>
            </div>
          </div>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
          <!-- Sidebar: Configuracion -->
          <div class="w-full md:w-80 border-r border-slate-100 dark:border-slate-800 p-8 space-y-8 bg-transparent/20 dark:bg-slate-900/10 flex flex-col">
            <!-- Fecha -->
            <div>
              <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Fecha de Consulta</label>
              <input 
                type="date" 
                v-model="fecha" 
                class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl p-4 text-xs font-black uppercase text-slate-900 dark:text-white focus:ring-2 focus:ring-brand-500/50 outline-none transition-all"
              >
            </div>

            <!-- Tecnicos List -->
            <div class="flex-1 flex flex-col min-h-0">
              <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Especialista</label>
              <div class="space-y-2 overflow-y-auto custom-scrollbar pr-2 flex-1">
                <button 
                  v-for="t in tecnicos" 
                  :key="t.id"
                  @click="selectedTecnico = t"
                  :class="[
                    'w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left',
                    selectedTecnico?.id === t.id 
                      ? 'bg-brand-50 dark:bg-brand-900/20 border-brand-500 shadow-md' 
                      : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                  ]"
                >
                  <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black text-white uppercase shadow-sm" :style="{ backgroundColor: t.color || '#3b82f6' }">
                    {{ t.name?.charAt(0) }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center justify-between gap-2">
                      <p :class="['text-[11px] font-black truncate uppercase tracking-tight', selectedTecnico?.id === t.id ? 'text-brand-600 dark:text-brand-400' : 'text-slate-700 dark:text-slate-300']">
                        {{ t.name }}
                      </p>
                      <span v-if="t.citas_asignadas_count > 0" class="shrink-0 px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[7px] font-black text-slate-500 dark:text-slate-400 uppercase">
                        {{ t.citas_asignadas_count }} Serv
                      </span>
                    </div>
                    <p class="text-[8px] font-bold text-slate-400 uppercase">Ver agenda</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Main View: Grid de Horas -->
          <div class="flex-1 p-8 flex flex-col">
            <div v-if="selectedTecnico" class="flex-1 flex flex-col">
              <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                  <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                    Horario: {{ selectedTecnico.name }}
                  </h4>
                </div>
                <div class="flex gap-4">
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-emerald-500 border border-emerald-400"></div>
                    <span class="text-[9px] font-black text-slate-400 uppercase">Libre</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-rose-500 border border-rose-400"></div>
                    <span class="text-[9px] font-black text-slate-400 uppercase">Ocupado</span>
                  </div>
                </div>
              </div>

              <div v-if="loading" class="flex-1 flex flex-col items-center justify-center space-y-4">
                <div class="w-12 h-12 border-4 border-slate-100 dark:border-slate-800 border-t-brand-500 rounded-full animate-spin"></div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Consultando bloques...</p>
              </div>

              <div v-else class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                  v-for="hora in 13" 
                  :key="'slot-' + hora"
                  :class="getSlotClasses(hora + 7)"
                  @click="handleSlotClick(hora + 7)"
                >
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase text-white">
                      {{ formatearHora(hora + 7) }}
                    </span>
                    <div v-if="isSlotBusy(hora + 7)" class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center text-white">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                    </div>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <p class="text-[9px] font-bold uppercase tracking-tighter text-white/80">
                      {{ getSlotStatusText(hora + 7) }}
                    </p>
                    <span v-if="isSlotSelectable(hora + 7)" class="text-[8px] font-black text-white opacity-0 group-hover:opacity-100 transition-all uppercase">
                      Seleccionar
                    </span>
                  </div>
                </div>
              </div>

              <!-- Resumen de Seleccion (Espacio Reservado para evitar saltos bruscos) -->
              <div 
                class="mt-8 p-6 bg-slate-900 dark:bg-white rounded-3xl flex items-center justify-between shadow-2xl transition-all duration-500 ease-out transform"
                :class="selectedStart !== null ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none select-none'"
              >
                <div class="flex items-center gap-6">
                  <div class="flex flex-col">
                    <span class="text-[8px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Horario Seleccionado</span>
                    <p class="text-xs font-black text-white dark:text-slate-900 uppercase tracking-wider">
                      {{ selectedStart !== null ? formatearHoraSimple(selectedStart) : '--:--' }} 
                      <span class="text-slate-600 dark:text-slate-300 mx-2">-></span> 
                      {{ selectedEnd !== null ? formatearHoraSimple(selectedEnd) : '--:--' }}
                    </p>
                  </div>
                  <div class="h-8 w-px bg-slate-800 dark:bg-slate-200"></div>
                  <div class="flex flex-col">
                    <span class="text-[8px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Duracion Total</span>
                    <p class="text-xs font-black text-brand-400 dark:text-brand-600 uppercase tracking-wider">
                      {{ (selectedStart !== null && selectedEnd !== null) ? (selectedEnd - selectedStart) : 0 }} Hora(s)
                    </p>
                  </div>
                </div>
                <button @click="agendarFinal" class="px-8 py-3 bg-brand-500 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl shadow-brand-500/20">
                  Confirmar y Agendar
                </button>
              </div>
            </div>

            <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-12">
               <div class="w-24 h-24 bg-transparent dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center text-slate-300 dark:text-slate-700 mb-6 border-2 border-dashed border-slate-200 dark:border-slate-800">
                  <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
               </div>
               <h4 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Selecciona un Especialista</h4>
               <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest max-w-xs leading-loose">
                 Elige un tecnico de la lista lateral para visualizar su carga de trabajo y espacios disponibles.
               </p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-8 py-6 bg-transparent dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-3">
             <div class="w-2 h-2 rounded-full bg-brand-500 shadow-lg shadow-brand-500/50"></div>
             <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Sincronizacion en tiempo real con la base de datos operativa</p>
          </div>
          <button @click="$emit('close')" class="px-8 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl">
            Cerrar Panel
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  show: Boolean,
  tecnicos: Array,
})

const emit = defineEmits(['close'])

const fecha = ref(new Date().toISOString().split('T')[0])
const selectedTecnico = ref(null)
const busySlots = ref([])
const loading = ref(false)

const selectedStart = ref(null)
const selectedEnd = ref(null)
const clickStep = ref(0)

const formatearHora = (h) => {
  const h12 = h > 12 ? h - 12 : (h === 0 ? 12 : h)
  const nextH = h + 1
  const nextH12 = nextH > 12 ? nextH - 12 : nextH
  const ampm = nextH >= 12 ? 'PM' : 'AM'
  return `${h12}:00 - ${nextH12}:00 ${ampm}`
}

const formatearHoraSimple = (h) => {
  const ampm = h >= 12 ? 'PM' : 'AM'
  const h12 = h > 12 ? h - 12 : (h === 0 ? 12 : h)
  return `${h12}:00 ${ampm}`
}

const isSlotBusy = (h) => {
  if (!busySlots.value.length) return false
  return busySlots.value.some(slot => {
    const startH = parseInt(slot.start.split(':')[0])
    const endH = parseInt(slot.end.split(':')[0])
    return h >= startH && h < endH
  })
}

const isSlotSelected = (h) => {
  if (selectedStart.value === null) return false
  if (selectedEnd.value === null) return h === selectedStart.value
  return h >= selectedStart.value && h < selectedEnd.value
}

const isSlotSelectable = (h) => {
  return !isSlotBusy(h) && !isSlotSelected(h)
}

const getSlotStatusText = (h) => {
  if (isSlotBusy(h)) return 'No Disponible'
  if (isSlotSelected(h)) return 'Seleccionado'
  return 'Espacio Libre'
}

const getSlotClasses = (h) => {
  const base = 'relative p-4 rounded-2xl border-2 transition-all group overflow-hidden'
  if (isSlotBusy(h)) return `${base} bg-rose-500 border-rose-400 shadow-sm`
  if (isSlotSelected(h)) return `${base} bg-brand-600 border-brand-600 shadow-xl scale-[1.02] z-10`
  return `${base} bg-emerald-500 border-emerald-400 hover:bg-emerald-600 hover:shadow-lg cursor-pointer`
}

const fetchBusySlots = async () => {
  if (!selectedTecnico.value || !fecha.value) return
  loading.value = true
  selectedStart.value = null
  selectedEnd.value = null
  clickStep.value = 0
  try {
    // Usamos window.route si esta disponible, sino una ruta manual
    const url = typeof route !== 'undefined' 
      ? route('api.citas.busy-slots', { tecnico_id: selectedTecnico.value.id, fecha: fecha.value })
      : `/api/citas/busy-slots?tecnico_id=${selectedTecnico.value.id}&fecha=${fecha.value}`
    
    const res = await fetch(url)
    const data = await res.json()
    if (data.success) {
      busySlots.value = data.slots
    }
  } catch (e) {
    console.error('Error:', e)
  } finally {
    loading.value = false
  }
}

watch([fecha, selectedTecnico], fetchBusySlots)

onMounted(() => {
  if (props.tecnicos && props.tecnicos.length > 0) {
    selectedTecnico.value = props.tecnicos[0]
  }
})

const handleSlotClick = (h) => {
  if (isSlotBusy(h)) return

  if (selectedStart.value === h) {
    selectedStart.value = null
    selectedEnd.value = null
    clickStep.value = 0
    return
  }

  if (clickStep.value === 0) {
    selectedStart.value = h
    selectedEnd.value = h + 1
    clickStep.value = 1
  } else {
    if (h < selectedStart.value) {
      selectedStart.value = h
      selectedEnd.value = h + 1
    } else {
      for (let i = selectedStart.value; i < h; i++) {
        if (isSlotBusy(i)) {
          alert('Conflicto detectado')
          return
        }
      }
      selectedEnd.value = h + 1
      clickStep.value = 0
    }
  }
}

const agendarFinal = () => {
  if (selectedStart.value === null || selectedEnd.value === null) return

  const start = `${fecha.value}T${String(selectedStart.value).padStart(2, '0')}:00`
  const end = `${fecha.value}T${String(selectedEnd.value).padStart(2, '0')}:00`
  
  const target = typeof route !== 'undefined' ? route('citas.create') : '/citas/create'
  
  router.visit(target, {
    data: {
      tecnico_id: selectedTecnico.value.id,
      fecha_hora: start,
      fecha_hora_fin: end
    }
  })
  emit('close')
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
</style>
