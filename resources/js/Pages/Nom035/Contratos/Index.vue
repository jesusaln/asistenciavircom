<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    contratos: Array,
    clientes: Array,
    plantillas: Array,
    repse_contracts: Array
});

const notyf = new Notyf();
const showModal = ref(false);
const wizardStep = ref(1);

const form = useForm({
    cliente_id: '',
    plantilla_id: '',
    repse_contract_id: '',
    titulo: ''
});

const openNew = () => {
    form.reset()
    wizardStep.value = 1
    showModal.value = true
}

const nextStep = () => {
    if (wizardStep.value === 1 && !form.cliente_id) {
        notyf.error('Debes seleccionar un cliente para continuar.');
        return;
    }
    if (wizardStep.value === 2 && (!form.titulo || !form.plantilla_id)) {
        notyf.error('Debes ingresar un título y seleccionar una plantilla.');
        return;
    }
    wizardStep.value++;
}

const prevStep = () => {
    wizardStep.value--;
}

const getSelectedClient = () => {
    return props.clientes.find(c => c.id === form.cliente_id);
}

const submit = () => {
    form.post(route('contratos.clientes.generate'), {
        onSuccess: () => {
            showModal.value = false
            notyf.success('Contrato generado con éxito')
        }
    })
}

const copyLink = (token) => {
    const url = `${window.location.origin}/firmar/${token}`;
    navigator.clipboard.writeText(url);
    notyf.success('Enlace de firma copiado al portapapeles');
}

const getStatusBadge = (status) => {
    switch(status) {
        case 'firmado': return 'bg-emerald-500 text-white'
        case 'pendiente_firma': return 'bg-amber-500 text-white'
        default: return 'bg-slate-400 text-white'
    }
}
</script>

<template>
    <AppLayout title="Gestión de Contratos">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Contratos de Clientes</h2>
                    <p class="text-sm text-slate-500 mt-1">Genera y envía contratos para firma electrónica.</p>
                </div>
                <div class="flex gap-4">
                    <Link :href="route('contratos.plantillas.index')" class="px-6 py-3 bg-white border border-slate-200 text-slate-600 rounded-2xl font-black text-xs hover:bg-slate-50 transition-all">
                        GESTIONAR PLANTILLAS
                    </Link>
                    <button @click="openNew" class="px-6 py-3 bg-indigo-600 text-white rounded-2xl font-black text-xs shadow-xl shadow-indigo-500/20 hover:bg-indigo-700 transition-all">
                        GENERAR NUEVO CONTRATO
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 xl:px-12 w-full space-y-8">
            <!-- Compliance Alerts Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-rose-50 border border-rose-100 rounded-[2rem] p-6 flex items-center gap-5">
                    <div class="w-12 h-12 bg-rose-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <font-awesome-icon icon="exclamation-triangle" class="text-xl" />
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-rose-800 uppercase tracking-tight">Vencimientos Próximos</h4>
                        <p class="text-2xl font-black text-rose-600">3</p>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-[2rem] p-6 flex items-center gap-5">
                    <div class="w-12 h-12 bg-amber-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <font-awesome-icon icon="clock" class="text-xl" />
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-800 uppercase tracking-tight">Firmas Pendientes</h4>
                        <p class="text-2xl font-black text-amber-600">5</p>
                    </div>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-[2rem] p-6 flex items-center gap-5">
                    <div class="w-12 h-12 bg-indigo-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
                        <font-awesome-icon icon="file-export" class="text-xl" />
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-indigo-800 uppercase tracking-tight">Próximo ICSOE</h4>
                        <p class="text-[10px] font-bold text-indigo-600 uppercase mt-1 italic">Vence Septiembre 2026</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Título / Tipo</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha Gen.</th>
                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="c in contratos" :key="c.id" class="hover:bg-indigo-50/30 transition-all group">
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold text-slate-800">{{ c.cliente?.nombre_razon_social }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ c.cliente?.rfc }}</p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-sm font-bold text-slate-700">{{ c.titulo }}</p>
                                <span class="text-[9px] px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full font-bold uppercase">{{ c.tipo }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span :class="getStatusBadge(c.estado)" class="text-[9px] px-3 py-1 rounded-full font-black uppercase tracking-wider">
                                    {{ c.estado.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-sm text-slate-500">
                                {{ new Date(c.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="copyLink(c.signing_token)" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition-all" title="Copiar enlace de firma">
                                        <font-awesome-icon icon="link" />
                                    </button>
                                    <Link :href="route('contratos.public.sign', c.signing_token)" target="_blank" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-600 hover:text-white transition-all" title="Ver/Firmar">
                                        <font-awesome-icon icon="file-signature" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="contratos.length === 0" class="py-20 text-center opacity-30 italic">
                    <font-awesome-icon icon="file-contract" class="text-6xl mb-4" />
                    <p>No se han generado contratos para clientes aún.</p>
                </div>
            </div>
        </div>

        <!-- Modal Generar (Wizard) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
            <div class="bg-white w-full max-w-xl rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-200">
                <!-- Wizard Header -->
                <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">Nuevo Contrato</h3>
                        <p class="text-xs text-slate-500 font-medium mt-1">Paso {{ wizardStep }} de 3</p>
                    </div>
                    <button @click="showModal = false" class="text-slate-400 hover:text-rose-500 w-8 h-8 flex items-center justify-center rounded-full bg-white shadow-sm border border-slate-200">
                        <font-awesome-icon icon="times" />
                    </button>
                </div>

                <!-- Progress Bar -->
                <div class="h-1.5 w-full bg-slate-100">
                    <div :class="{'w-1/3': wizardStep === 1, 'w-2/3': wizardStep === 2, 'w-full': wizardStep === 3}" class="h-full bg-indigo-600 transition-all duration-500 ease-out"></div>
                </div>

                <div class="p-8 space-y-6 min-h-[300px]">
                    <!-- Step 1: Selección de Cliente -->
                    <div v-if="wizardStep === 1" class="animate-in fade-in slide-in-from-right-4 duration-300">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <font-awesome-icon icon="building" class="text-indigo-500" /> Datos Base
                        </h4>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">1. Seleccionar Cliente</label>
                                <select v-model="form.cliente_id" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="">Selecciona un cliente...</option>
                                    <option v-for="cl in clientes" :key="cl.id" :value="cl.id">{{ cl.nombre_razon_social }}</option>
                                </select>
                            </div>

                            <!-- Warning if Client is missing RFC -->
                            <div v-if="form.cliente_id && !getSelectedClient()?.rfc" class="p-4 bg-rose-50 rounded-2xl border border-rose-100 flex items-start gap-3">
                                <font-awesome-icon icon="exclamation-circle" class="text-rose-500 mt-0.5" />
                                <div>
                                    <p class="text-xs font-bold text-rose-800">Cliente sin RFC detectado</p>
                                    <p class="text-[10px] text-rose-600 leading-relaxed mt-1">El cliente seleccionado no tiene un RFC registrado. Para que la firma electrónica (FIEL) tenga validez oficial, necesitas ir al catálogo de clientes y agregarle su RFC antes de enviarlo a firmar.</p>
                                </div>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">2. Vincular a Servicio REPSE (Opcional)</label>
                                <select v-model="form.repse_contract_id" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="">No vincular (Manual)</option>
                                    <option v-for="rc in repse_contracts" :key="rc.id" :value="rc.id">{{ rc.label }}</option>
                                </select>
                                <p class="text-[9px] text-slate-400 mt-2 italic">Vincula un servicio para auto-llenar fechas, montos y descripciones.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Documento -->
                    <div v-if="wizardStep === 2" class="animate-in fade-in slide-in-from-right-4 duration-300">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <font-awesome-icon icon="file-contract" class="text-indigo-500" /> Configuración del Documento
                        </h4>

                        <div v-if="plantillas.length === 0" class="p-5 bg-amber-50 rounded-2xl border border-amber-200 text-center">
                            <font-awesome-icon icon="exclamation-triangle" class="text-amber-500 text-2xl mb-2" />
                            <p class="text-xs font-bold text-amber-800">No tienes plantillas legales creadas.</p>
                            <p class="text-[10px] text-amber-600 mt-1">Cierra esta ventana y ve a "Gestionar Plantillas" para crear tu primer machote de contrato.</p>
                        </div>

                        <div v-else class="space-y-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">1. Plantilla Legal</label>
                                <select v-model="form.plantilla_id" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20">
                                    <option value="">Selecciona una plantilla base...</option>
                                    <option v-for="p in plantillas" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">2. Título Específico para este cliente</label>
                                <input v-model="form.titulo" type="text" class="w-full bg-slate-50 border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20" placeholder="Ej. Contrato Mantenimiento Preventivo 2026" />
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Resumen -->
                    <div v-if="wizardStep === 3" class="animate-in fade-in slide-in-from-right-4 duration-300">
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <font-awesome-icon icon="check-double" class="text-emerald-500" /> Revisión Final
                        </h4>

                        <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente Destino</p>
                                <p class="text-xs font-bold text-slate-800">{{ getSelectedClient()?.nombre_razon_social }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Documento</p>
                                <p class="text-xs font-bold text-slate-800">{{ form.titulo }}</p>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100 mt-6">
                            <p class="text-[11px] text-indigo-800 leading-relaxed font-medium">
                                <font-awesome-icon icon="info-circle" class="mr-2" />
                                Al generar el contrato, el sistema inyectará todos los datos del cliente (Nombre, Dirección, RFC, Fechas y Montos) automáticamente en el machote. Quedará listo para enviarse a firmar.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Wizard Footer -->
                <div class="p-6 border-t border-slate-100 bg-white flex justify-between items-center">
                    <button v-if="wizardStep > 1" @click="prevStep" class="px-6 py-3 text-slate-500 hover:text-slate-800 font-black text-xs uppercase tracking-widest transition-all">
                        <font-awesome-icon icon="arrow-left" class="mr-2" /> Atrás
                    </button>
                    <div v-else></div> <!-- Spacer -->

                    <button v-if="wizardStep < 3" @click="nextStep" class="px-8 py-3 bg-slate-800 text-white rounded-xl font-black text-xs shadow-lg hover:bg-slate-900 transition-all">
                        Siguiente Paso <font-awesome-icon icon="arrow-right" class="ml-2" />
                    </button>

                    <button v-if="wizardStep === 3" @click="submit" :disabled="form.processing" class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-black text-xs shadow-lg shadow-emerald-500/20 hover:bg-emerald-700 transition-all disabled:opacity-50 flex items-center gap-2">
                        <font-awesome-icon v-if="form.processing" icon="spinner" spin />
                        GENERAR AHORA <font-awesome-icon icon="magic" />
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
