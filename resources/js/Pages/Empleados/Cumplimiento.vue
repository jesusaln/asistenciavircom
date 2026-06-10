<script setup>
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  matriz: Array,
  plantillas: Array
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const selectedEmployees = ref([])
const selectedPlantilla = ref('')
const processing = ref(false)

const toggleEmployee = (id) => {
  if (selectedEmployees.value.includes(id)) {
    selectedEmployees.value = selectedEmployees.value.filter(e => e !== id)
  } else {
    selectedEmployees.value.push(id)
  }
}

const selectAll = () => {
  if (selectedEmployees.value.length === props.matriz.length) {
    selectedEmployees.value = []
  } else {
    selectedEmployees.value = props.matriz.map(e => e.id)
  }
}

const generarMasivo = () => {
  if (selectedEmployees.value.length === 0 || !selectedPlantilla.value) {
    notyf.error('Selecciona empleados y una plantilla')
    return
  }

  processing.value = true
  router.post('/empleados/cumplimiento/generar-masivo', {
    empleado_ids: selectedEmployees.value,
    plantilla_id: selectedPlantilla.value
  }, {
    onSuccess: () => {
      selectedEmployees.value = []
      selectedPlantilla.value = ''
      processing.value = false
      notyf.success('Documentos generados y listos para firmar')
    },
    onError: () => {
      processing.value = false
      notyf.error('Error al generar documentos')
    }
  })
}

</script>

<template>
  <Head title="Matriz de Cumplimiento Legal" />

  <div class="min-h-screen bg-[var(--ui-surface)] dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
          <h1 class="text-3xl font-black text-slate-900 dark:text-white">Matriz de Cumplimiento</h1>
          <p class="text-sm font-bold text-slate-500 uppercase tracking-tighter">Control de firmas y reformas legales (Ley Silla, NOM-035)</p>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-slate-900 p-2 rounded-2xl shadow-sm border border-slate-100 dark:border-white/5">
            <select v-model="selectedPlantilla" class="bg-transparent border-none text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-400 focus:ring-0">
                <option value="">Seleccionar Plantilla Masiva</option>
                <option v-for="p in plantillas" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
            <button 
                @click="generarMasivo"
                :disabled="processing || selectedEmployees.length === 0"
                class="px-6 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase tracking-widest rounded-xl hover:scale-105 transition-all disabled:opacity-50"
            >
                {{ processing ? 'Procesando...' : `Generar para ${selectedEmployees.length} seleccionados` }}
            </button>
        </div>
      </div>

      <!-- Matriz Table -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-100 dark:border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50 dark:bg-white/5 border-b border-slate-100 dark:border-white/5">
                <th class="px-6 py-4">
                    <input type="checkbox" @change="selectAll" :checked="selectedEmployees.length === matriz.length" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                </th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Empleado</th>
                <th v-for="p in plantillas" :key="p.id" class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">
                    {{ p.nombre.split(' ')[0] }}...
                </th>
                <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Riesgo NOM-035</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-white/5">
              <tr v-for="e in matriz" :key="e.id" class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors group">
                <td class="px-6 py-4">
                    <input type="checkbox" @change="toggleEmployee(e.id)" :checked="selectedEmployees.includes(e.id)" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-[10px] font-black text-slate-600">
                        {{ e.name.charAt(0) }}
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ e.name }}</div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ e.puesto || 'Sin puesto' }}</div>
                    </div>
                  </div>
                </td>
                <td v-for="p in plantillas" :key="p.id" class="px-6 py-4 text-center">
                    <div v-if="e.status[p.id]" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600" title="Firmado">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <div v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-300" title="Pendiente">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <a 
                        v-if="e.nom035_url"
                        :href="e.nom035_url"
                        target="_blank"
                        :class="[
                            'text-[9px] font-black uppercase px-2 py-1 rounded-lg tracking-widest hover:scale-105 transition-all',
                            e.nom035_riesgo === 'Muy alto' || e.nom035_riesgo === 'Alto' ? 'bg-rose-100 text-rose-600' : 
                            e.nom035_riesgo === 'Medio' ? 'bg-amber-100 text-amber-600' :
                            e.nom035_riesgo === 'Bajo' || e.nom035_riesgo === 'Nulo' ? 'bg-emerald-100 text-emerald-600' : 
                            'bg-slate-100 text-slate-400'
                        ]"
                    >
                        {{ e.nom035_riesgo }}
                    </a>
                    <span 
                        v-else
                        class="text-[9px] font-black uppercase px-2 py-1 rounded-lg tracking-widest bg-slate-100 text-slate-400"
                    >
                        {{ e.nom035_riesgo }}
                    </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
