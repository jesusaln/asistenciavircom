<script setup>
import { ref } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  plantillas: Array
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const showingModal = ref(false)
const form = useForm({
  nombre: '',
  tipo: 'contrato',
  vigencia_meses: null,
  contenido: '',
})

const submitForm = () => {
  router.post('/empleados/plantillas', form.value, {
    onSuccess: () => {
      showingModal.value = false
      form.value = { nombre: '', tipo: 'contrato_inicial', contenido: '' }
      notyf.success('Plantilla guardada')
    }
  })
}

const variables = [
  { name: '{{nombre}}', desc: 'Nombre completo del empleado' },
  { name: '{{rfc}}', desc: 'RFC del empleado' },
  { name: '{{nss}}', desc: 'NSS del empleado' },
  { name: '{{puesto}}', desc: 'Puesto de trabajo' },
  { name: '{{sueldo}}', desc: 'Sueldo base mensual' },
  { name: '{{fecha_contratacion}}', desc: 'Fecha de ingreso' },
]

const insertarVariable = (variable) => {
    form.value.contenido += variable
}

</script>

<template>
  <Head title="Plantillas de Contratos" />

  <div class="min-h-screen bg-[var(--ui-surface)] dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-black text-slate-900 dark:text-white">Plantillas de Contratos</h1>
          <p class="text-sm font-bold text-slate-500 uppercase tracking-tighter">Gestión de textos legales dinámicos</p>
        </div>
        <button 
          @click="showingModal = true"
          class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-black rounded-2xl shadow-xl hover:bg-blue-700 transition-all"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
          Nueva Plantilla
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="p in plantillas" :key="p.id" class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-white/5 group hover:border-blue-500/50 transition-all">
          <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 mb-6">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          </div>
          <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">{{ p.nombre }}</h3>
          <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-6">{{ p.tipo.replace('_', ' ') }}</p>
          
          <div class="flex gap-2">
            <button class="flex-1 py-2 text-xs font-black bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl hover:bg-slate-200 transition-colors">Editar</button>
            <button class="px-4 py-2 text-xs font-black text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors">Eliminar</button>
          </div>
        </div>

        <div v-if="plantillas.length === 0" class="col-span-full py-20 text-center bg-white dark:bg-slate-900 rounded-[2.5rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
            <p class="text-slate-400 font-bold">No hay plantillas creadas todavía.</p>
        </div>
      </div>
    </div>

    <!-- Modal Nueva Plantilla -->
    <div v-if="showingModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showingModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 w-full max-w-4xl rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white">Crear Plantilla Legal</h2>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter mt-1">Define el texto base para tus contratos</p>
                </div>
                <button @click="showingModal = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            
            <div class="p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Nombre de la Plantilla</label>
                        <input v-model="form.nombre" type="text" placeholder="Ej: Contrato Individual de Trabajo - Sonora" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Contenido Legal</label>
                        <textarea v-model="form.contenido" rows="12" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-serif leading-relaxed" placeholder="Escribe aquí el cuerpo del contrato..."></textarea>
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Tipo de Documento</label>
                            <select v-model="form.tipo" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold p-4 focus:ring-2 focus:ring-blue-500 transition-all">
                                <option value="contrato">Contrato Inicial</option>
                                <option value="adenda">Adenda / Modificación</option>
                                <option value="nom_035">NOM-035 (Política)</option>
                                <option value="nom_019">NOM-019 (Seguridad e Higiene)</option>
                                <option value="reglamento">Reglamento Interior</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Vigencia (Meses)</label>
                            <input v-model="form.vigencia_meses" type="number" placeholder="Ej: 12" class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-2xl text-sm font-bold p-4 focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Variables Disponibles</label>
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-4 space-y-3">
                            <button 
                                v-for="v in variables" 
                                :key="v.name"
                                @click="insertarVariable(v.name)"
                                class="w-full flex items-center justify-between text-left group"
                            >
                                <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-800 px-2 py-1 rounded-lg border border-blue-100 dark:border-blue-900/50 group-hover:scale-105 transition-transform">{{ v.name }}</span>
                                <span class="text-[9px] font-bold text-slate-400 truncate ml-2">{{ v.desc }}</span>
                            </button>
                        </div>
                        <p class="text-[9px] text-slate-400 mt-4 font-bold leading-relaxed italic">Haz clic en una variable para insertarla al final del contenido.</p>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex justify-end">
                <button @click="submitForm" class="px-10 py-4 bg-blue-600 text-white text-sm font-black rounded-2xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 transition-all">Guardar Plantilla</button>
            </div>
        </div>
    </div>
  </div>
</template>
