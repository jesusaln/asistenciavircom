<template>
  <Head :title="headerTitle" />
  
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-12 px-4 sm:px-6 lg:px-8" :style="cssVars">
    <div class="max-w-4xl mx-auto">
      <!-- Header Premium -->
      <div class="text-center mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-emerald-500 rounded-[2rem] mb-6 shadow-2xl shadow-emerald-500/20 text-white transform hover:rotate-12 transition-transform duration-500">
          <FontAwesomeIcon icon="umbrella-beach" size="2x" />
        </div>
        <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3">
          {{ headerTitle }}
        </h1>
        <p class="text-slate-500 dark:text-slate-400 font-medium max-w-lg mx-auto">
          {{ headerSubtitle }}
        </p>
      </div>

      <!-- Form Card Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-1000">
        <form @submit.prevent="submit" class="p-10 lg:p-14 space-y-12">
          
          <!-- Sección: Identidad del Colaborador -->
          <div class="space-y-8">
            <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-800/60 pb-6">
              <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                <FontAwesomeIcon icon="user-tie" />
              </div>
              <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Identidad del Colaborador</h2>
            </div>

            <!-- Información fija para auto-solicitud -->
            <div v-if="isSelfRequest" class="group bg-blue-500/5 dark:bg-blue-500/10 p-6 rounded-3xl border border-blue-200/30 dark:border-blue-500/20 transition-all hover:shadow-lg">
              <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-900 flex items-center justify-center border border-blue-100 dark:border-blue-500/30 overflow-hidden shadow-sm">
                   <img v-if="props.empleadoSeleccionado?.profile_photo_url" :src="props.empleadoSeleccionado.profile_photo_url" class="w-full h-full object-cover">
                   <span v-else class="text-xl font-black text-blue-500">{{ props.empleadoSeleccionado?.name?.charAt(0) }}</span>
                </div>
                <div>
                  <div class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">Solicitante Identificado</div>
                  <div class="text-lg font-black text-slate-900 dark:text-white">{{ props.empleadoSeleccionado?.name }}</div>
                  <div class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">{{ props.empleadoSeleccionado?.puesto }}</div>
                </div>
              </div>
              <input type="hidden" v-model="form.user_id" />
            </div>

            <!-- Selector para administradores -->
            <div v-else class="space-y-3">
              <label for="user_id" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-4">
                Colaborador Destino <span class="text-rose-500">*</span>
              </label>
              <div class="relative group">
                <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none">
                   <FontAwesomeIcon icon="users" />
                </div>
                <select
                  v-model="form.user_id"
                  id="user_id"
                  class="block w-full pl-16 pr-12 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-sm font-bold text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
                  :class="{'border-rose-500/50 bg-rose-500/5': form.errors.user_id}"
                >
                  <option value="" disabled>Elegir integrante del equipo...</option>
                  <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                    {{ empleado.name }} — {{ empleado.puesto }}
                  </option>
                </select>
                <div class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                  <FontAwesomeIcon icon="chevron-down" size="xs" />
                </div>
              </div>
              <InputError class="ml-4" :message="form.errors.user_id" />
            </div>
          </div>

          <!-- Sección: Cronograma Operativo -->
          <div class="space-y-8">
            <div class="flex items-center gap-4 border-b border-slate-100 dark:border-slate-800/60 pb-6">
              <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-500">
                <FontAwesomeIcon icon="calendar-alt" />
              </div>
              <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Cronograma Operativo</h2>
            </div>

            <!-- Grid de Fechas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="space-y-3">
                <label for="fecha_inicio" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-4">Fecha de Inicio <span class="text-rose-500">*</span></label>
                <div class="relative group">
                  <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none">
                    <FontAwesomeIcon icon="plane-departure" />
                  </div>
                  <input
                    v-model="form.fecha_inicio"
                    type="date"
                    id="fecha_inicio"
                    :min="minDate"
                    class="block w-full pl-16 pr-8 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-sm font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
                    :class="{'border-rose-500/50 bg-rose-500/5': form.errors.fecha_inicio}"
                  />
                </div>
                <InputError class="ml-4" :message="form.errors.fecha_inicio" />
              </div>

              <div class="space-y-3">
                <label for="fecha_fin" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-4">Fecha de Retorno <span class="text-rose-500">*</span></label>
                <div class="relative group">
                  <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none">
                    <FontAwesomeIcon icon="plane-arrival" />
                  </div>
                  <input
                    v-model="form.fecha_fin"
                    type="date"
                    id="fecha_fin"
                    :min="form.fecha_inicio || minDate"
                    class="block w-full pl-16 pr-8 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[1.5rem] text-sm font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
                    :class="{'border-rose-500/50 bg-rose-500/5': form.errors.fecha_fin}"
                  />
                </div>
                <InputError class="ml-4" :message="form.errors.fecha_fin" />
              </div>
            </div>

            <!-- Bloque de Resumen de Tiempo -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
               <!-- Disponibilidad -->
               <div v-if="props.registroVacaciones" class="bg-emerald-500/5 dark:bg-emerald-500/10 p-6 rounded-3xl border border-emerald-200/30 dark:border-emerald-500/20 flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                       <FontAwesomeIcon icon="award" />
                    </div>
                    <div>
                        <div class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Saldo Disponible</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ props.registroVacaciones.dias_disponibles }} <span class="text-xs font-bold text-slate-400">Días</span></div>
                    </div>
                  </div>
                  <div class="text-right">
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Periodo {{ props.registroVacaciones.anio }}</div>
                    <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-500">+{{ props.registroVacaciones.dias_correspondientes }} Totales</div>
                  </div>
               </div>

               <!-- Solicitados -->
               <div v-if="form.fecha_inicio && form.fecha_fin" 
                    class="p-6 rounded-3xl border flex items-center justify-between transition-all duration-500"
                    :class="canApply ? 'bg-indigo-500/5 dark:bg-indigo-500/10 border-indigo-200/30 dark:border-indigo-500/20' : 'bg-rose-500/5 dark:bg-rose-500/10 border-rose-200/30 dark:border-rose-500/20'"
               >
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" :class="canApply ? 'bg-indigo-500/10 text-indigo-600' : 'bg-rose-500/10 text-rose-600'">
                       <FontAwesomeIcon :icon="canApply ? 'check-circle' : 'exclamation-circle'" />
                    </div>
                    <div>
                        <div class="text-[9px] font-black uppercase tracking-widest" :class="canApply ? 'text-indigo-600' : 'text-rose-600'">Impacto en Saldo</div>
                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ diasSolicitados }} <span class="text-xs font-bold text-slate-400">Días</span></div>
                    </div>
                  </div>
                  <div class="text-right">
                      <div class="text-[10px] font-black uppercase tracking-widest" :class="canApply ? 'text-emerald-500' : 'text-rose-500'">
                         {{ canApply ? 'Factible' : 'Excede Saldo' }}
                      </div>
                      <div v-if="!canApply" class="text-[10px] font-bold text-rose-400">Faltan {{ diasSolicitados - props.registroVacaciones.dias_disponibles }} días</div>
                  </div>
               </div>
            </div>

            <!-- Motivo -->
            <div class="space-y-3">
              <label for="motivo" class="text-[11px] font-black text-slate-400 uppercase tracking-widest ml-4">Explica tu requerimiento (Opcional)</label>
              <div class="relative group">
                <div class="absolute left-6 top-6 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none">
                  <FontAwesomeIcon icon="message" />
                </div>
                <textarea
                  v-model="form.motivo"
                  id="motivo"
                  rows="4"
                  class="block w-full pl-16 pr-8 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-[2.5rem] text-sm font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
                  placeholder="Utiliza este espacio para añadir detalles relevantes sobre tu solicitud..."
                ></textarea>
              </div>
              <InputError class="ml-4" :message="form.errors.motivo" />
            </div>
          </div>

          <!-- Acciones Finales Premium -->
          <div class="pt-10 flex flex-col sm:flex-row items-center justify-center gap-6 border-t border-slate-100 dark:border-slate-800/60">
            <Link :href="backRoute"
                  class="w-full sm:w-auto px-10 py-5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-3xl font-black text-[10px] uppercase tracking-[0.2em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center justify-center">
              <FontAwesomeIcon icon="times" class="mr-3" />
              Descartar
            </Link>
            
            <button
              type="submit"
              class="w-full sm:w-auto px-16 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-3xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-2xl shadow-emerald-500/20 flex items-center justify-center disabled:opacity-50 disabled:grayscale disabled:scale-100"
              :disabled="form.processing || !isFormValid"
            >
              <template v-if="form.processing">
                <FontAwesomeIcon icon="circle-notch" spin class="mr-3" />
                Procesando...
              </template>
              <template v-else>
                <FontAwesomeIcon icon="paper-plane" class="mr-3" />
                Formalizar Solicitud
              </template>
            </button>
          </div>
        </form>
      </div>
      
      <!-- Pie de página informativo -->
      <div class="mt-12 text-center text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 animate-in fade-in duration-1000 delay-500">
          * Gestión Automatizada de Recursos Humanos — Vircom System
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  empleados: Array,
  empleadoSeleccionado: Object,
  registroVacaciones: Object,
})

const { cssVars } = useCompanyColors()
const page = usePage()

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const minDate = new Date().toISOString().split('T')[0]

const form = useForm({
  user_id: props.empleadoSeleccionado?.id || '',
  fecha_inicio: '',
  fecha_fin: '',
  motivo: '',
})

// Lógica de Títulos
const isSelfRequest = computed(() => 
  props.empleadoSeleccionado && 
  page.props.auth.user && 
  props.empleadoSeleccionado.id === page.props.auth.user.id
)

const headerTitle = computed(() => 
  isSelfRequest.value 
    ? 'Solicitar Vacaciones' 
    : (props.empleadoSeleccionado ? `Gestión para ${props.empleadoSeleccionado.name}` : 'Nuevo Registro de Vacaciones')
)

const headerSubtitle = computed(() => 
  isSelfRequest.value 
    ? 'Completa el cronograma para formalizar tu periodo de descanso ante capital humano.' 
    : 'Registra y valida periodos vacacionales para los integrantes de tu equipo asignado.'
)

const backRoute = computed(() => 
  isSelfRequest.value ? route('vacaciones.mis-vacaciones') : route('vacaciones.index')
)

// Cálculos Operativos
const diasSolicitados = computed(() => {
  if (!form.fecha_inicio || !form.fecha_fin) return 0
  try {
    const inicio = new Date(form.fecha_inicio)
    const fin = new Date(form.fecha_fin)
    const diffTime = Math.abs(fin - inicio)
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  } catch { return 0 }
})

const canApply = computed(() => {
  if (!props.registroVacaciones) return true
  return diasSolicitados.value <= props.registroVacaciones.dias_disponibles
})

const isFormValid = computed(() => {
  return form.user_id &&
         form.fecha_inicio &&
         form.fecha_fin &&
         form.fecha_inicio <= form.fecha_fin &&
         canApply.value
})

const submit = () => {
    form.post(route('vacaciones.store'), {
      onSuccess: () => {
        notyf.success(`Solicitud procesada correctamente en los registros corporativos.`)
        form.reset()
      },
      onError: (errors) => {
        notyf.error('Error de validación. Verifica los campos resaltados en rojo.')
        const firstErrorField = Object.keys(errors)[0]
        if (firstErrorField) {
          const element = document.getElementById(firstErrorField)
          if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'center' })
            element.focus()
          }
        }
      }
    })
}
</script>

<style scoped>
/* Eliminar flecha nativa de select */
select {
  background-image: none !important;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
    cursor: pointer;
    opacity: 0.1;
    position: absolute;
    right: 1.5rem;
    width: 2rem;
    height: 2rem;
}

.dark input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

textarea {
  resize: none;
}
</style>
