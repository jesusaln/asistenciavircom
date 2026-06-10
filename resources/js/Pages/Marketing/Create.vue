<template>
  <AppLayout title="Crear Campaña">
    <template #header>
      <div class="flex items-center gap-4">
        <Link 
          :href="route('marketing.campanias.index')"
          class="p-3 rounded-2xl bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 text-slate-400 hover:text-brand-500 transition-all duration-200 shadow-sm shadow-black/5"
        >
          <FontAwesomeIcon icon="arrow-left" />
        </Link>
        <div>
          <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Nueva Campaña</h2>
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mt-1">Configura tu mensaje y selecciona a tus destinatarios.</p>
        </div>
      </div>
    </template>

    <div class="py-12 px-6 max-w-5xl mx-auto">
      <!-- Steps Indicator -->
      <div class="flex items-center justify-between mb-12 relative px-10">
        <div class="absolute top-1/2 left-10 right-10 h-0.5 bg-slate-100 dark:bg-slate-800/50 -translate-y-1/2 z-0"></div>
        <div 
          class="absolute top-1/2 left-10 h-0.5 bg-gradient-to-r from-brand-500 to-brand-600 -translate-y-1/2 z-0 transition-all duration-500" 
          :style="{ width: ((step - 1) / 2 * 100) + '%' }"
        ></div>
        
        <div v-for="s in 3" :key="s" class="relative z-10">
          <div 
            class="w-10 h-10 rounded-2xl flex items-center justify-center border-4 border-slate-50 dark:border-slate-950 transition-all duration-500"
            :class="step >= s ? 'bg-brand-500 text-white shadow-xl shadow-brand-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500'"
          >
            <FontAwesomeIcon v-if="s < step" icon="check" class="text-sm font-black" />
            <span v-else class="text-sm font-black">{{ s }}</span>
          </div>
          <span class="absolute -bottom-8 left-1/2 -translate-x-1/2 text-[10px] font-black uppercase tracking-[0.2em] whitespace-nowrap" :class="step >= s ? 'text-brand-500' : 'text-slate-400 dark:text-slate-500'">
            {{ stepLabels[s-1] }}
          </span>
        </div>
      </div>

      <!-- Step 1: Basic Info -->
      <Transition mode="out-in" enter-active-class="duration-200 ease-out" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-4">
        <div v-if="step === 1" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-10 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 block">Nombre de la Campaña</label>
              <input 
                v-model="form.nombre"
                type="text" 
                placeholder="Ej. Promoción Verano 2026"
                class="w-full bg-[var(--ui-surface)] dark:bg-slate-800/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl px-6 py-4 text-sm font-black text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-700 focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/5 transition-all outline-none"
              >
            </div>
            <div class="space-y-2">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 block">Canal de Comunicación</label>
              <div class="grid grid-cols-2 gap-4">
                <button
                  type="button"
                  @click="form.tipo = 'whatsapp'"
                  class="flex items-center justify-center gap-3 p-4 rounded-2xl border-2 transition-all duration-200 shadow-sm"
                  :class="form.tipo === 'whatsapp' ? 'bg-brand-500/10 border-emerald-500 text-emerald-600 dark:text-slate-400 shadow-emerald-500/10' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800 text-slate-400'"
                >
                  <FontAwesomeIcon icon="fa-brands fa-whatsapp" class="text-lg" />
                  <span class="text-xs font-black uppercase tracking-wide">WhatsApp</span>
                </button>
                <button
                  type="button"
                  @click="form.tipo = 'sms'"
                  disabled
                  class="flex items-center justify-center gap-3 p-4 rounded-2xl border-2 transition-all duration-200 shadow-sm"
                  :class="form.tipo === 'sms' ? 'bg-brand-500/10 border-blue-500 text-blue-600 dark:text-blue-400 shadow-blue-500/10' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-100 dark:border-slate-800 text-slate-400 opacity-50 cursor-not-allowed'"
                >
                  <FontAwesomeIcon icon="comment-alt" />
                  <span class="text-xs font-black uppercase tracking-wide">SMS</span>
                </button>
              </div>
              <p class="text-[10px] text-slate-400 dark:text-slate-500">SMS aún no está disponible; para campañas se usará WhatsApp.</p>
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 block">Descripción (Interna)</label>
            <textarea 
              v-model="form.descripcion"
              rows="3" 
              placeholder="Objetivo: Fidelización de clientes actuales..."
              class="w-full bg-[var(--ui-surface)] dark:bg-slate-800/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl px-6 py-4 text-sm font-medium text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-700 focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/5 transition-all outline-none"
            ></textarea>
          </div>
        </div>

        <!-- Step 2: Template Selection -->
        <div v-else-if="step === 2" class="space-y-6">
           <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-10 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl">
              <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide mb-6">Selecciona una Plantilla Aprobada</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div 
                  v-for="template in templates" 
                  :key="template.id"
                  @click="selectTemplate(template)"
                  class="group p-6 rounded-[2rem] border-2 cursor-pointer transition-all duration-200 relative overflow-hidden h-fit"
                  :class="form.plantilla_id === template.name ? 'border-brand-500 bg-brand-500/5 shadow-xl shadow-brand-500/10' : 'border-slate-100 dark:border-slate-800 hover:border-brand-500/30 bg-slate-50/50 dark:bg-slate-950/20'"
                >
                  <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] font-black uppercase tracking-[0.15em] py-1 px-3 rounded-xl" :class="template.status === 'APPROVED' ? 'bg-brand-500/20 text-emerald-600' : 'bg-brand-500/20 text-amber-600'">
                      {{ template.status === 'APPROVED' ? 'Aprobada' : template.status }}
                    </span>
                    <div v-if="form.plantilla_id === template.name" class="w-10 h-10 rounded-full bg-brand-500 flex items-center justify-center text-white shadow-xl">
                      <FontAwesomeIcon icon="check" class="text-[8px]" />
                    </div>
                  </div>
                  <h4 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide mb-3">{{ template.name.replace(/_/g, ' ') }}</h4>
                  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/50 dark:border-slate-800/50 relative">
                     <p class="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400 whitespace-pre-wrap italic">
                        {{ getTemplateBody(template) }}
                     </p>
                  </div>
                  <!-- Mapping variables if variables exist in template -->
                  <div v-if="form.plantilla_id === template.name && hasVariables(template)" class="mt-6 space-y-6 pt-6 border-t border-slate-200 dark:border-slate-800/50 animate-fade-in">
                     <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Mapeo de Variables</p>
                     <div v-for="v in getVariableCount(template)" :key="v" class="flex items-center gap-2">
                        <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-bold" v-text="'{{' + v + '}}'"></div>
                        <select 
                          v-model="form.data_plantilla.mapping[v-1]"
                          class="flex-1 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-2 text-[10px] font-black uppercase tracking-wide text-slate-700 dark:text-slate-200 focus:border-brand-500 focus:ring-0 outline-none transition-all"
                        >
                           <option value="nombre_razon_social">Nombre del Cliente</option>
                           <option value="email">Correo Electrónico</option>
                           <option value="telefono">Teléfono</option>
                           <option value="custom">Texto Personalizado</option>
                        </select>
                     </div>
                  </div>
                </div>
              </div>

              <div v-if="templates.length === 0" class="text-center py-10 opacity-50">
                 <FontAwesomeIcon icon="exclamation-circle" class="text-2xl mb-4" />
                 <p class="text-xs font-black uppercase tracking-wide">No hay plantillas aprobadas disponibles en Meta.</p>
              </div>
           </div>
        </div>

        <!-- Step 3: Audience Filtering -->
        <div v-else-if="step === 3" class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl p-10 rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl space-y-6">
           <div class="flex items-center justify-between">
              <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Segmentar Destinatarios</h3>
              <div class="px-5 py-2 rounded-2xl bg-brand-500/10 border border-emerald-500/20 text-emerald-600 text-[10px] font-black uppercase tracking-[0.1em]">
                {{ estimatedAudience }} Clientes Calificados
              </div>
           </div>

           <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div class="p-8 rounded-3xl bg-slate-50/50 dark:bg-slate-950/20 border border-slate-100 dark:border-slate-800">
                 <div class="flex items-center gap-2 mb-6">
                    <FontAwesomeIcon icon="shield-alt" class="text-indigo-500" />
                    <span class="text-[10px] font-black uppercase tracking-wide">Garantía de Consentimiento</span>
                 </div>
                 <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/50 dark:border-slate-800/50">
                       <span class="text-[10px] font-bold uppercase tracking-wide">Opt-in Marketing</span>
                       <div class="w-10 h-10 rounded-full bg-brand-500/20 text-emerald-600 flex items-center justify-center">
                          <FontAwesomeIcon icon="check" class="text-xs" />
                       </div>
                    </div>
                    <p class="text-[10px] font-medium text-slate-500 leading-relaxed italic">
                      Todos los destinatarios deben tener la autorización de marketing activa. Aquellos que se hayan dado de baja no serán incluidos.
                    </p>
                 </div>
              </div>

              <div class="space-y-6">
                 <div v-if="props.selectedAudience" class="p-5 rounded-3xl bg-brand-50 dark:bg-brand-900/20/80 border border-brand-200 dark:border-brand-800/30">
                    <p class="text-[10px] font-black uppercase tracking-wide text-brand-800 dark:text-brand-200 dark:text-amber-200">Audiencia seleccionada</p>
                    <p class="text-sm font-black text-slate-900 mt-2">{{ props.selectedAudience.nombre }}</p>
                    <p class="text-[11px] text-slate-500 mt-2">{{ props.selectedAudience.descripcion || 'Selección manual guardada desde audiencias.' }}</p>
                    <p class="text-[10px] font-black uppercase tracking-wide text-emerald-600 mt-3">
                      {{ props.selectedAudience.clientes?.length || 0 }} clientes incluidos
                    </p>
                 </div>
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 block">Base de Clientes</label>
                    <div class="w-full bg-[var(--ui-surface)] dark:bg-slate-800/50 border-2 border-slate-100 dark:border-slate-800 rounded-2xl px-6 py-4 text-sm font-black text-slate-900 dark:text-white">
                      {{ props.selectedAudience ? 'Clientes definidos por la audiencia elegida' : 'Clientes con consentimiento de marketing y WhatsApp' }}
                    </div>
                    <label v-if="!props.selectedAudience" class="mt-4 inline-flex items-center gap-2">
                      <input v-model="form.filtros.solo_activos" type="checkbox" class="rounded-xl border-slate-300 text-brand-500 focus:ring-brand-500" />
                      <span class="text-[10px] font-black uppercase tracking-wide text-slate-500">Solo clientes activos</span>
                    </label>
                 </div>
              </div>
           </div>
        </div>
      </Transition>

      <!-- Navigation Footer -->
      <div class="mt-12 flex items-center justify-between">
        <button 
          v-if="step > 1"
          @click="step--"
          class="px-10 py-5 text-xs font-black uppercase tracking-wide text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
        >
          Anterior
        </button>
        <div v-else></div>

        <button 
          v-if="step < 3"
          @click="step++"
          :disabled="!canContinue"
          class="px-12 py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-wide rounded-3xl shadow-2xl transition-all duration-200 transform active:scale-90 disabled:opacity-30"
        >
          Continuar
          <FontAwesomeIcon icon="arrow-right" class="ml-3 transition-transform group-hover:translate-x-1" />
        </button>

        <button 
          v-else
          @click="submit"
          :disabled="form.processing"
          class="px-12 py-5 bg-gradient-to-br from-brand-500 to-brand-600 text-white text-xs font-black uppercase tracking-wide rounded-3xl shadow-2xl shadow-brand-500/30 transition-all duration-200 transform active:scale-90"
        >
          <FontAwesomeIcon v-if="form.processing" icon="circle-notch" class="animate-spin mr-3" />
          <span v-else>Lanzar Campaña</span>
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
  faArrowLeft, faArrowRight, faCheck, faShieldAlt, 
  faExclamationCircle, faCommentAlt, faCircleNotch 
} from '@fortawesome/free-solid-svg-icons';
import { faWhatsapp } from '@fortawesome/free-brands-svg-icons';

library.add(faArrowLeft, faArrowRight, faCheck, faShieldAlt, faExclamationCircle, faCommentAlt, faCircleNotch, faWhatsapp);

const props = defineProps({
  templates: Array,
  selectedAudience: Object,
});

const step = ref(1);
const stepLabels = ['General', 'Contenido', 'Audiencia'];

const form = useForm({
  nombre: '',
  descripcion: '',
  tipo: 'whatsapp',
  plantilla_id: '',
  data_plantilla: {
    mapping: []
  },
  filtros: {
    solo_activos: true,
    cliente_ids: props.selectedAudience?.clientes?.map((cliente) => cliente.id) || [],
  }
});

const estimatedAudience = computed(() => {
  if (props.selectedAudience?.clientes?.length) {
    return `${props.selectedAudience.clientes.length} definidos`;
  }

  return 'Segmentación dinámica';
});

const canContinue = computed(() => {
  if (step.value === 1) return form.nombre.length > 3;
  if (step.value === 2) return form.plantilla_id !== '';
  return true;
});

const selectTemplate = (template) => {
  form.plantilla_id = template.name;
  // Initialize mapping based on variables
  const count = getVariableCount(template);
  form.data_plantilla.mapping = Array(count).fill('nombre_razon_social');
};

const getTemplateBody = (template) => {
  const body = template.components.find(c => c.type === 'BODY');
  return body ? body.text : '';
};

const hasVariables = (template) => {
  return getVariableCount(template) > 0;
};

const getVariableCount = (template) => {
  const body = getTemplateBody(template);
  const matches = body.match(/\{{[\d]+\}\}/g);
  return matches ? matches.length : 0;
};

const submit = () => {
  form.post(route('marketing.campanias.store'));
};
</script>

<style>
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
