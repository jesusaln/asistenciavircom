<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleado: Object,
  contratos: Array,
  plantillas: Array
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const showingAddModal = ref(false)
const showingGenerateModal = ref(false)
const selectedPlantilla = ref('')

const form = ref({
  tipo: 'contrato',
  titulo: '',
  archivo: null
})

const submitGenerate = () => {
  if (!selectedPlantilla.value) return
  
  router.post(`/empleados/${props.empleado.id}/contratos/generar`, {
    plantilla_id: selectedPlantilla.value
  }, {
    onSuccess: () => {
      showingGenerateModal.value = false
      selectedPlantilla.value = ''
      notyf.success('Documento generado correctamente')
    }
  })
}

const fileInput = ref(null)

const handleFileChange = (e) => {
  form.value.archivo = e.target.files[0]
}

const submitForm = () => {
  const formData = new FormData()
  formData.append('tipo', form.value.tipo)
  formData.append('titulo', form.value.titulo)
  if (form.value.archivo) {
    formData.append('archivo', form.value.archivo)
  }

  router.post(`/empleados/${props.empleado.id}/contratos`, formData, {
    onSuccess: () => {
      showingAddModal.value = false
      form.value = { tipo: 'contrato', titulo: '', archivo: null }
      notyf.success('Documento agregado al expediente')
    },
    onError: (errors) => {
      notyf.error(errors.error || 'Error al guardar el documento')
    }
  })
}

const getStatusClass = (estado) => {
  const colors = {
    'borrador': 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
    'pendiente_firma': 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
    'firmado': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
    'cancelado': 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-400',
  }
  return colors[estado] || 'bg-slate-100 text-slate-700'
}

const openFile = (id) => {
    window.open(`/contratos/${id}/archivo`, '_blank')
}

</script>

<template>
  <Head :title="`Expediente - ${empleado.name}`" />

  <div class="min-h-screen bg-[var(--ui-surface)] dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Breadcrumbs & Header -->
      <div class="mb-8">
        <nav class="flex mb-4 text-sm text-slate-500" aria-label="Breadcrumb">
          <Link href="/empleados" class="hover:text-slate-700 dark:hover:text-slate-300">RRHH / Empleados</Link>
          <span class="mx-2">/</span>
          <span class="text-slate-900 dark:text-white font-medium">Expediente Digital</span>
        </nav>
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white text-2xl font-black shadow-lg">
              {{ empleado.name?.charAt(0) }}
            </div>
            <div>
              <h1 class="text-3xl font-black text-slate-900 dark:text-white">{{ empleado.name }}</h1>
              <div class="flex items-center gap-3 mt-1">
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400">NSS: {{ empleado.nss || 'N/A' }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 uppercase tracking-tighter">{{ empleado.puesto || 'Sin puesto' }}</span>
              </div>
            </div>
          </div>
          <div class="flex gap-2">
            <button 
              @click="showingGenerateModal = true"
              class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white font-bold rounded-2xl shadow-xl hover:scale-105 active:scale-95 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
              Generar desde Plantilla
            </button>
            <button 
              @click="showingAddModal = true"
              class="inline-flex items-center px-5 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold rounded-2xl shadow-xl hover:scale-105 active:scale-95 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
              Adjuntar Documento
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar Info -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-white/5">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-6">Información General</h3>
            <div class="space-y-4">
              <div>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-tighter">RFC</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ empleado.rfc || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-tighter">CURP</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ empleado.curp || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-tighter">Fecha Contratación</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ empleado.fecha_contratacion || '—' }}</p>
              </div>
              <div>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-tighter">Departamento</p>
                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ empleado.departamento || 'Sin departamento' }}</p>
              </div>
            </div>
          </div>

          <!-- Compliance Progress (Coming soon in Phases) -->
          <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-[2.5rem] p-8 text-white shadow-xl relative overflow-hidden">
            <div class="relative z-10">
                <h3 class="text-xs font-black uppercase tracking-widest opacity-80 mb-2">Estado de Cumplimiento</h3>
                <p class="text-2xl font-black mb-4">40% Completado</p>
                <div class="w-full bg-white/20 h-2 rounded-full overflow-hidden">
                    <div class="bg-white h-full" style="width: 40%"></div>
                </div>
                <p class="text-[10px] mt-4 opacity-80 leading-relaxed font-bold">Faltan documentos de la NOM-035 y Ley Silla por firmar.</p>
            </div>
            <svg class="absolute -right-4 -bottom-4 w-32 h-32 opacity-10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
          </div>
        </div>

        <!-- Documents List -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
              <h2 class="text-xl font-black text-slate-900 dark:text-white">Documentos y Contratos</h2>
              <span class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ contratos.length }} archivos</span>
            </div>

            <div v-if="contratos.length > 0" class="divide-y divide-slate-50 dark:divide-white/5">
              <div v-for="c in contratos" :key="c.id" class="px-8 py-6 hover:bg-slate-50 dark:hover:bg-white/5 transition-all group">
                <div class="flex items-center justify-between gap-4">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors">
                      <svg v-if="c.tipo === 'contrato'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                      <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                      <h4 class="text-sm font-black text-slate-900 dark:text-white">{{ c.titulo }}</h4>
                      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">{{ c.tipo }} • Agregado el {{ c.created_at }}</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <span :class="['text-[10px] font-black uppercase px-2 py-1 rounded-lg tracking-widest', getStatusClass(c.estado)]">
                      {{ c.estado.replace('_', ' ') }}
                    </span>
                    
                    <button 
                      v-if="c.archivo_path"
                      @click="openFile(c.id)"
                      class="p-2 text-slate-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-xl transition-all"
                      title="Ver Documento"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                    
                    <!-- Botón para Firma (Coming soon Phase 2) -->
                    <button 
                      v-if="c.estado !== 'firmado'"
                      class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/30 rounded-xl transition-all"
                      title="Solicitar Firma"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="p-20 text-center">
              <div class="w-20 h-20 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-6 text-slate-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              </div>
              <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">Expediente Vacío</h3>
              <p class="text-sm text-slate-500 max-w-xs mx-auto font-bold">Aún no hay contratos o adendas registradas para este empleado.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Generar desde Plantilla -->
    <div v-if="showingGenerateModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showingGenerateModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 border-b border-slate-100 dark:border-white/5">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Generar Documento</h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter mt-1">Selecciona una plantilla legal</p>
            </div>
            
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Plantillas Disponibles</label>
                    <div v-if="plantillas.length > 0" class="space-y-3">
                        <button 
                            v-for="p in plantillas" 
                            :key="p.id"
                            @click="selectedPlantilla = p.id"
                            :class="['w-full text-left p-4 rounded-2xl border transition-all', selectedPlantilla === p.id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5']"
                        >
                            <p class="text-sm font-black text-slate-900 dark:text-white">{{ p.nombre }}</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">{{ p.tipo.replace('_', ' ') }}</p>
                        </button>
                    </div>
                    <div v-else class="text-center py-6 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                        <p class="text-xs font-bold text-slate-500">No hay plantillas creadas.</p>
                        <Link href="/empleados/plantillas" class="text-[10px] text-blue-500 font-black uppercase mt-2 inline-block">Crear Plantilla</Link>
                    </div>
                </div>
            </div>

            <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex gap-3">
                <button @click="showingGenerateModal = false" class="flex-1 py-4 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                <button :disabled="!selectedPlantilla" @click="submitGenerate" class="flex-1 py-4 bg-blue-600 disabled:opacity-50 text-white text-sm font-black rounded-2xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 transition-all">Generar Ahora</button>
            </div>
        </div>
    </div>

    <!-- Modal Adjuntar Documento -->
    <div v-if="showingAddModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="showingAddModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
            <div class="p-8 border-b border-slate-100 dark:border-white/5">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Adjuntar Documento</h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter mt-1">Expediente de {{ empleado.name }}</p>
            </div>
            
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Tipo de Documento</label>
                    <select v-model="form.tipo" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="contrato">Contrato Individual</option>
                        <option value="adenda">Adenda / Anexo</option>
                        <option value="aviso">Aviso de Privacidad</option>
                        <option value="nom035">NOM-035 (Evaluación)</option>
                        <option value="identificacion">Identificación Oficial</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Título del Documento</label>
                    <input v-model="form.titulo" type="text" placeholder="Ej: Contrato Indefinido 2026" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500" />
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Archivo (PDF o Imagen)</label>
                    <input type="file" @change="handleFileChange" class="w-full text-sm text-slate-500 file:mr-4 file:py-3 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-slate-800 dark:file:text-slate-200" accept=".pdf,image/*" />
                </div>
            </div>

            <div class="p-8 bg-slate-50 dark:bg-slate-800/50 flex gap-3">
                <button @click="showingAddModal = false" class="flex-1 py-4 text-sm font-black text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">Cancelar</button>
                <button @click="submitForm" class="flex-1 py-4 bg-blue-600 text-white text-sm font-black rounded-2xl shadow-lg shadow-blue-600/20 hover:bg-blue-700 transition-all">Guardar Archivo</button>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.min-h-screen {
  background-attachment: fixed;
}
</style>
