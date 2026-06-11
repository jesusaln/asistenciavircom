<script setup>
import { ref, computed } from 'vue'
import { router, Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import Swal from 'sweetalert2'

const notyf = new Notyf({ duration: 4000 })

const props = defineProps({
    periodo: Object,
    respondents: Array,
    empleados_disponibles: Array,
})

const showSeguimiento = ref(false)
const selectedRespondent = ref(null)

const formSeguimiento = useForm({
    clinical_valuation_status: 'pending',
    clinical_valuation_notes: '',
    clinical_valuation_date: '',
    clinical_valuation_evidence: null
})

const openSeguimiento = (res) => {
    selectedRespondent.value = res
    formSeguimiento.clinical_valuation_status = res.clinical_valuation_status || 'pending'
    formSeguimiento.clinical_valuation_notes = res.clinical_valuation_notes || ''
    formSeguimiento.clinical_valuation_date = res.clinical_valuation_date || ''
    formSeguimiento.clinical_valuation_evidence = null
    showSeguimiento.value = true
}

const saveSeguimiento = () => {
    formSeguimiento.post(route('nom035.seguimiento', selectedRespondent.value.uuid), {
        forceFormData: true,
        onSuccess: () => {
            showSeguimiento.value = false
            notyf.success('Seguimiento guardado correctamente')
        }
    })
}

const searchTerm = ref('')
const selectedEmpleados = ref([])

const filteredEmpleados = computed(() => {
    if (!searchTerm.value) return props.empleados_disponibles
    return props.empleados_disponibles.filter(e => 
        e.nombre?.toLowerCase().includes(searchTerm.value.toLowerCase()) || 
        e.email?.toLowerCase().includes(searchTerm.value.toLowerCase())
    )
})

const toggleEmpleado = (id) => {
    const index = selectedEmpleados.value.indexOf(id)
    if (index > -1) selectedEmpleados.value.splice(index, 1)
    else selectedEmpleados.value.push(id)
}

const toggleAll = () => {
    if (selectedEmpleados.value.length === filteredEmpleados.value.length) {
        selectedEmpleados.value = []
    } else {
        selectedEmpleados.value = filteredEmpleados.value.map(e => e.id)
    }
}

const adding = ref(false)
const agregarEmpleados = () => {
    if (!selectedEmpleados.value.length) { 
        notyf.error('Selecciona al menos un colaborador'); return 
    }
    adding.value = true
    router.post(route('nom035.periodos.empleados', props.periodo.id), {
        empleado_ids: selectedEmpleados.value
    }, {
        onSuccess: () => { 
            selectedEmpleados.value = []
            adding.value = false
            notyf.success('Colaboradores agregados exitosamente') 
        },
        onError: () => adding.value = false
    })
}

const searchRespondent = ref('')
const filteredRespondents = computed(() => {
    if (!searchRespondent.value) return props.respondents
    return props.respondents.filter(r => 
        r.name?.toLowerCase().includes(searchRespondent.value.toLowerCase()) || 
        r.email?.toLowerCase().includes(searchRespondent.value.toLowerCase()) ||
        r.department?.toLowerCase().includes(searchRespondent.value.toLowerCase())
    )
})

const copyAllLinks = () => {
    const pending = props.respondents.filter(r => r.status !== 'completed');
    if (!pending.length) {
        notyf.error('No hay encuestas pendientes para copiar');
        return;
    }
    const text = pending.map(r => `${r.name}: ${route('nom035.questionnaire.show', r.uuid)}`).join('\n');
    navigator.clipboard.writeText(text);
    notyf.success('Enlaces copiados al portapapeles');
}

const notifying = ref(false)
const notificarTodos = () => {
    notifying.value = true
    router.post(route('nom035.periodos.notificar', props.periodo.id), {}, {
        onSuccess: () => {
            notifying.value = false
            notyf.success('Notificaciones enviadas exitosamente')
        },
        onError: () => notifying.value = false
    })
}

const closing = ref(false)
const cerrarPeriodo = () => {
    Swal.fire({
        title: '¿Finalizar Periodo?',
        text: 'Al cerrar el periodo ya no se podrán recibir más respuestas de los colaboradores.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, finalizar periodo',
        cancelButtonText: 'Cancelar',
        background: '#1e293b',
        color: '#f1f5f9',
        customClass: {
            title: 'font-bold text-slate-100',
            htmlContainer: 'text-slate-400',
            confirmButton: 'rounded-xl px-4 py-2 font-bold',
            cancelButton: 'rounded-xl px-4 py-2 font-bold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            closing.value = true
            router.post(route('nom035.periodos.cerrar', props.periodo.id), {}, {
                onSuccess: () => {
                    closing.value = false
                    Swal.fire({
                        title: '¡Cerrado!',
                        text: 'El periodo ha sido finalizado correctamente.',
                        icon: 'success',
                        confirmButtonColor: '#8b5cf6',
                        background: '#1e293b',
                        color: '#f1f5f9'
                    })
                },
                onError: () => {
                    closing.value = false
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo cerrar el periodo',
                        icon: 'error',
                        background: '#1e293b',
                        color: '#f1f5f9'
                    })
                }
            })
        }
    })
}

const getRiskClass = (level) => {
    const levels = {
        'Nulo': 'bg-blue-100 text-blue-700',
        'Bajo': 'bg-green-100 text-green-700',
        'Medio': 'bg-yellow-100 text-yellow-700',
        'Alto': 'bg-orange-100 text-orange-700',
        'Muy Alto': 'bg-red-100 text-red-700',
        'Sin hallazgos críticos detectados': 'bg-emerald-100 text-emerald-700',
        'Se sugiere seguimiento según protocolo': 'bg-purple-100 text-purple-700',
    };
    return levels[level] || 'bg-gray-100 text-gray-700';
};
</script>

<template>
    <AppLayout :title="'Gestión de Periodo: ' + periodo.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('nom035.index')" class="p-2 bg-[var(--ui-surface-soft)] rounded-lg text-[var(--ui-text-soft)] hover:text-purple-500 transition-colors">
                        <font-awesome-icon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="font-bold text-xl text-[var(--ui-text-main)]">{{ periodo.name }}</h2>
                        <div class="flex items-center gap-2 text-xs text-[var(--ui-text-soft)] mt-0.5">
                            <font-awesome-icon icon="calendar-alt" />
                            {{ periodo.start_date }} al {{ periodo.end_date }}
                            <span :class="['ml-2 px-2 py-0.5 rounded text-[10px] font-black uppercase', periodo.active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                                {{ periodo.active ? 'Activo' : 'Cerrado' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button v-if="periodo.active" @click="cerrarPeriodo" :disabled="closing" class="px-4 py-2 bg-red-600 text-white rounded-xl text-xs font-bold hover:bg-red-700 transition-all flex items-center gap-2 shadow-lg shadow-red-500/20 disabled:opacity-50">
                        <font-awesome-icon icon="times-circle" />
                        {{ closing ? 'Cerrando...' : 'Finalizar Periodo' }}
                    </button>
                    
                    <a :href="route('nom035.periodos.pdf', periodo.id)" target="_blank" class="px-4 py-2 bg-white text-slate-900 rounded-xl text-xs font-bold hover:bg-slate-50 border border-slate-200 shadow-sm transition-all flex items-center gap-2">
                        <font-awesome-icon icon="file-pdf" />
                        Reporte General
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left: Add Employees -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl shadow-sm overflow-hidden sticky top-24">
                        <div class="p-6 border-b border-[var(--ui-border)]">
                            <h3 class="font-bold text-[var(--ui-text-main)] flex items-center gap-2">
                                <font-awesome-icon icon="user-plus" class="text-purple-500" />
                                Asignar Colaboradores
                            </h3>
                            <p class="text-[10px] text-[var(--ui-text-soft)] uppercase font-bold mt-1 tracking-wider">Añadir a la evaluación actual</p>
                        </div>
                        
                        <div class="p-4 border-b border-[var(--ui-border)] bg-[var(--ui-surface-soft)]/30">
                            <div class="relative">
                                <font-awesome-icon icon="search" class="absolute left-3 top-3 text-[var(--ui-text-soft)] text-xs" />
                                <input v-model="searchTerm" type="text" placeholder="Buscar por nombre o correo..." 
                                    class="w-full pl-9 pr-4 py-2 bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-xl text-xs focus:ring-purple-500/30 transition-all" />
                            </div>
                        </div>

                        <div class="max-h-[400px] overflow-y-auto p-2 space-y-1">
                            <div v-if="filteredEmpleados.length" class="p-2 border-b border-[var(--ui-border)] flex items-center justify-between">
                                <span class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase ml-2">Seleccionar Todos</span>
                                <input type="checkbox" @change="toggleAll" :checked="selectedEmpleados.length === filteredEmpleados.length && filteredEmpleados.length > 0" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500" />
                            </div>
                            
                            <div v-for="e in filteredEmpleados" :key="e.id" 
                                @click="toggleEmpleado(e.id)"
                                :class="['p-3 rounded-xl flex items-center justify-between cursor-pointer transition-all', selectedEmpleados.includes(e.id) ? 'bg-purple-50 border-purple-100' : 'hover:bg-[var(--ui-surface-soft)]']"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['h-8 w-8 rounded-lg flex items-center justify-center font-bold text-xs', selectedEmpleados.includes(e.id) ? 'bg-purple-600 text-white' : 'bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)]']">
                                        {{ e.nombre?.charAt(0) || '?' }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-[var(--ui-text-main)] truncate max-w-[150px]">{{ e.nombre }}</p>
                                        <p class="text-[9px] text-[var(--ui-text-soft)]">{{ e.email }}</p>
                                    </div>
                                </div>
                                <input type="checkbox" :checked="selectedEmpleados.includes(e.id)" @click.stop="toggleEmpleado(e.id)" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500" />
                            </div>

                            <div v-if="!filteredEmpleados.length" class="p-8 text-center text-[var(--ui-text-soft)] text-xs italic">
                                No se encontraron colaboradores disponibles.
                            </div>
                        </div>

                        <div class="p-4 bg-[var(--ui-surface-soft)] border-t border-[var(--ui-border)]">
                            <button @click="agregarEmpleados" :disabled="adding || !selectedEmpleados.length" class="w-full py-2.5 bg-purple-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-purple-500/20 hover:bg-purple-700 transition-all disabled:opacity-50">
                                {{ adding ? 'Asignando...' : `Asignar ${selectedEmpleados.length} Seleccionados` }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right: Respondents Table -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-[var(--ui-border)] flex flex-col md:flex-row md:items-center justify-between gap-4 bg-[var(--ui-surface-soft)]/20">
                            <div>
                                <h3 class="font-bold text-[var(--ui-text-main)]">Colaboradores en Evaluación</h3>
                                <p class="text-xs text-[var(--ui-text-soft)] mt-0.5">Seguimiento de progreso para este periodo</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <font-awesome-icon icon="search" class="absolute left-3 top-2.5 text-[var(--ui-text-soft)] text-xs" />
                                    <input v-model="searchRespondent" type="text" placeholder="Buscar en lista..." 
                                        class="pl-9 pr-4 py-2 bg-[var(--ui-surface)] border-[var(--ui-border)] rounded-xl text-xs w-48 focus:ring-purple-500/30 transition-all" />
                                </div>
                                <button @click="copyAllLinks" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all flex items-center gap-2">
                                    <font-awesome-icon icon="copy" />
                                    Enlaces
                                </button>
                                <button @click="notificarTodos" :disabled="notifying" class="px-4 py-2 bg-purple-600 text-white rounded-xl text-xs font-bold hover:bg-purple-700 transition-all flex items-center gap-2 disabled:opacity-50 shadow-lg shadow-purple-500/20">
                                    <font-awesome-icon :icon="notifying ? 'spinner' : 'mobile-alt'" :spin="notifying" />
                                    Notificar Todos (App)
                                </button>
                                <div class="text-right border-l pl-4 border-[var(--ui-border)]">
                                    <p class="text-xl font-black text-purple-600">{{ respondents.filter(r => r.status === 'completed').length }} / {{ respondents.length }}</p>
                                    <p class="text-[9px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wider">Completados</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left border-collapse">
                                <thead>
                                    <tr class="bg-[var(--ui-surface-soft)] text-[var(--ui-text-soft)] uppercase text-[10px] tracking-widest border-b border-[var(--ui-border)]">
                                        <th class="px-6 py-4 font-bold">Colaborador</th>
                                        <th class="px-6 py-4 font-bold text-center">Riesgo</th>
                                        <th class="px-6 py-4 font-bold text-center">Trauma</th>
                                        <th class="px-6 py-4 font-bold">Estado</th>
                                        <th class="px-6 py-4 font-bold text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[var(--ui-border)]">
                                    <tr v-for="res in filteredRespondents" :key="res.id" class="hover:bg-[var(--ui-surface-soft)]/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-[var(--ui-text-main)]">{{ res.empleado?.name || res.name || 'Sin Nombre' }}</div>
                                            <div class="flex flex-col gap-0.5 mt-1">
                                                <div class="text-[10px] text-[var(--ui-text-soft)] font-mono">{{ res.email }}</div>
                                                <div class="text-[10px] text-purple-500 font-black uppercase tracking-widest">{{ res.empleado?.departamento || res.department }}</div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span v-if="res.status === 'completed'" :class="['px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tight', getRiskClass(res.risk_level)]">
                                                {{ res.risk_level }}
                                            </span>
                                            <span v-else class="text-[10px] font-bold text-slate-300 uppercase italic">Pendiente</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <template v-if="res.requires_clinical_valuation">
                                                    <div class="flex items-center gap-1 px-2 py-0.5 bg-red-50 border border-red-100 rounded-md" title="Requiere Valoración Clínica">
                                                        <font-awesome-icon icon="exclamation-triangle" class="text-red-600 text-[10px]" />
                                                        <span class="text-[9px] font-black text-red-600 uppercase tracking-tighter">SÍ</span>
                                                    </div>
                                                </template>
                                                <template v-else-if="res.results?.counts?.section_i">
                                                    <div class="flex items-center gap-1 px-2 py-0.5 bg-orange-50 border border-orange-100 rounded-md" title="Trauma Detectado">
                                                        <font-awesome-icon icon="exclamation-circle" class="text-orange-600 text-[10px]" />
                                                        <span class="text-[9px] font-black text-orange-600 uppercase tracking-tighter">SÍ</span>
                                                    </div>
                                                </template>
                                                <template v-else>
                                                    <span class="text-slate-300 font-bold">-</span>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span :class="['text-[10px] font-bold', res.status === 'completed' ? 'text-green-500' : 'text-orange-500']">
                                                    {{ res.status === 'completed' ? 'Completado' : 'Pendiente' }}
                                                </span>
                                                <div class="w-16 bg-slate-100 rounded-full h-1 overflow-hidden">
                                                    <div :class="['h-1 rounded-full', res.status === 'completed' ? 'bg-green-500' : 'bg-orange-500']" :style="{ width: res.status === 'completed' ? '100%' : '20%' }"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <template v-if="res.status !== 'completed'">
                                                    <a :href="route('nom035.questionnaire.show', res.uuid)" target="_blank" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all" title="Enlace Directo">
                                                        <font-awesome-icon icon="link" />
                                                    </a>
                                                    <div v-if="res.empleado?.fcm_token" class="p-2 text-emerald-600 bg-emerald-50 rounded-lg" title="App Vinculada">
                                                        <font-awesome-icon icon="mobile-alt" />
                                                    </div>
                                                </template>
                                                <button v-if="res.requires_clinical_valuation || res.results?.counts?.section_i" 
                                                        @click="openSeguimiento(res)"
                                                        class="p-2 text-orange-500 hover:bg-orange-50 rounded-lg transition-all" 
                                                        title="Registrar Seguimiento Médico">
                                                    <font-awesome-icon icon="file-medical" />
                                                </button>
                                                <Link :href="route('nom035.respuestas', res.uuid)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Respuestas">
                                                    <font-awesome-icon icon="eye" />
                                                </Link>
                                                <a v-if="res.status === 'completed'" :href="route('nom035.resultados.pdf', res.uuid)" target="_blank" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Descargar Constancia">
                                                    <font-awesome-icon icon="file-pdf" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!respondents.length">
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-2 opacity-30">
                                                <font-awesome-icon icon="users-slash" class="text-4xl" />
                                                <p class="text-xs font-bold uppercase tracking-widest">No hay colaboradores asignados</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Seguimiento Médico -->
        <div v-if="showSeguimiento" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm animate-in fade-in duration-200">
            <div class="bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-3xl w-full max-w-lg shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="p-6 border-b border-[var(--ui-border)] flex items-center justify-between bg-[var(--ui-surface-soft)]/50">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-orange-500/10 rounded-xl flex items-center justify-center text-orange-600">
                            <font-awesome-icon icon="file-medical" />
                        </div>
                        <div>
                            <h3 class="font-bold text-[var(--ui-text-main)]">Seguimiento Médico</h3>
                            <p class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-wider">{{ selectedRespondent?.name }}</p>
                        </div>
                    </div>
                    <button @click="showSeguimiento = false" class="text-[var(--ui-text-soft)] hover:text-red-500 transition-colors">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Estado de la Canalización</label>
                        <select v-model="formSeguimiento.clinical_valuation_status" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-orange-500/30 transition-all">
                            <option value="pending">Pendiente de enviar</option>
                            <option value="referred">Enviado a valoración (En proceso)</option>
                            <option value="completed">Valoración concluida / Atendido</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Fecha de Atención</label>
                            <input v-model="formSeguimiento.clinical_valuation_date" type="date" class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-xl px-4 py-3 text-sm focus:ring-orange-500/30 transition-all" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Evidencia (PDF/Foto)</label>
                            <input type="file" @input="formSeguimiento.clinical_valuation_evidence = $event.target.files[0]" class="w-full text-xs text-[var(--ui-text-soft)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition-all" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-widest ml-1">Notas de Seguimiento</label>
                        <textarea v-model="formSeguimiento.clinical_valuation_notes" rows="4" placeholder="Ej. Se canalizó al trabajador a su clínica del IMSS correspondiente por detección de trauma severo..." class="w-full bg-[var(--ui-surface-soft)] border-[var(--ui-border)] rounded-2xl px-4 py-3 text-sm focus:ring-orange-500/30 transition-all"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button @click="showSeguimiento = false" class="px-6 py-2.5 bg-[var(--ui-surface-soft)] text-[var(--ui-text-main)] rounded-xl text-xs font-bold hover:bg-[var(--ui-border)] transition-all">
                            Cancelar
                        </button>
                        <button @click="saveSeguimiento" :disabled="formSeguimiento.processing" class="px-8 py-2.5 bg-orange-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-orange-500/20 hover:bg-orange-700 transition-all disabled:opacity-50">
                            {{ formSeguimiento.processing ? 'Guardando...' : 'Guardar Seguimiento' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
