<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const props = defineProps({
    template: Object,
    repse_contracts: Array
});

const notyf = new Notyf();
const showPreview = ref(false);
const previewContractId = ref('');
const quillEditor = ref(null);
let quill = null;

const form = useForm({
    nombre: props.template?.nombre ?? '',
    tipo: props.template?.tipo ?? 'contrato_repse',
    contenido: props.template?.contenido ?? '',
    vigencia_meses: props.template?.vigencia_meses ?? 12
});

onMounted(() => {
    quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Comienza a redactar el contrato...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['clean']
            ]
        }
    });

    if (form.contenido) {
        quill.root.innerHTML = form.contenido;
    }

    quill.on('text-change', () => {
        form.contenido = quill.root.innerHTML;
    });
});

const submit = () => {
    if (props.template) {
        form.patch(route('contratos.plantillas.update', props.template.id), {
            onSuccess: () => notyf.success('Plantilla actualizada con éxito')
        });
    } else {
        form.post(route('contratos.plantillas.store'), {
            onSuccess: () => notyf.success('Plantilla creada con éxito')
        });
    }
};

const insertVar = (variable) => {
    if (!quill) return;
    const range = quill.getSelection(true);
    const insertion = `{{${variable}}}`;
    quill.insertText(range.index, insertion, 'bold', true);
    quill.setSelection(range.index + insertion.length);
};

const previewContent = computed(() => {
    let content = form.contenido;
    
    // Si hay un contrato seleccionado para previsualizar, usamos sus datos reales
    let data = {
        'cliente_nombre': 'HOSPITAL GENERAL (EJEMPLO)',
        'cliente_rfc': 'RFC000000AAA',
        'cliente_domicilio': 'Calle Ejemplo #123, Hermosillo, Son.',
        'contratante_nombre': 'CLIMAS DEL DESIERTO S.A. DE C.V.',
        'contratante_rfc': 'CDD900101AAA',
        'contrato_numero': 'S/N',
        'contrato_objeto': 'SERVICIOS ESPECIALIZADOS DE CLIMATIZACIÓN',
        'fecha_inicio': '01/01/2026',
        'fecha_fin': '31/12/2026',
        'monto': '0.00',
        'empresa_nombre': 'CLIMAS DEL DESIERTO',
        'empresa_repse': 'REPSE-STPS-123456',
    };

    if (previewContractId.value) {
        const rc = props.repse_contracts.find(r => r.id === previewContractId.value);
        if (rc) data = { ...data, ...rc.data };
    }

    for (const [key, val] of Object.entries(data)) {
        content = content.replaceAll(`{{${key}}}`, `<mark class="bg-indigo-500/20 text-indigo-700 px-1 rounded font-bold underline decoration-indigo-500/50">${val}</mark>`);
    }
    return content;
});

const variableGroups = [
    {
        title: 'Datos del Cliente',
        icon: 'user-tie',
        items: [
            { label: 'Razón Social', var: 'cliente_nombre' },
            { label: 'RFC', var: 'cliente_rfc' },
            { label: 'Domicilio Fiscal', var: 'cliente_domicilio' },
        ]
    },
    {
        title: 'Datos del Contrato',
        icon: 'file-contract',
        items: [
            { label: 'Número de Contrato', var: 'contrato_numero' },
            { label: 'Objeto del Servicio', var: 'contrato_objeto' },
            { label: 'Fecha de Inicio', var: 'fecha_inicio' },
            { label: 'Fecha de Término', var: 'fecha_fin' },
            { label: 'Monto Total', var: 'monto' },
        ]
    },
    {
        title: 'Datos del Prestador',
        icon: 'building',
        items: [
            { label: 'Nombre Empresa', var: 'empresa_nombre' },
            { label: 'Registro REPSE', var: 'empresa_repse' },
        ]
    },
];
</script>

<template>
    <AppLayout :title="template ? 'Editar Plantilla' : 'Nueva Plantilla'">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('contratos.plantillas.index')" class="w-10 h-10 flex items-center justify-center bg-slate-800 rounded-xl text-slate-400 hover:bg-slate-700 hover:text-white transition-all border border-slate-700">
                        <font-awesome-icon icon="arrow-left" />
                    </Link>
                    <div>
                        <h2 class="text-xl font-black text-white uppercase tracking-tight">
                            {{ template ? 'Editor Legal' : 'Nueva Plantilla' }}
                        </h2>
                        <p class="text-[10px] text-slate-500 mt-0.5 font-bold uppercase tracking-widest">
                            Procesador de textos enriquecido
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div v-if="showPreview" class="mr-4 flex items-center gap-3 bg-slate-800 p-1.5 rounded-xl border border-slate-700">
                        <span class="text-[9px] font-black text-slate-500 uppercase px-3">Ver con datos de:</span>
                        <select v-model="previewContractId" class="bg-slate-900 border-none text-[10px] font-bold text-white rounded-lg focus:ring-0 py-1.5 pr-8">
                            <option value="">Hospital General (Ejemplo)</option>
                            <option v-for="rc in repse_contracts" :key="rc.id" :value="rc.id">{{ rc.label }}</option>
                        </select>
                    </div>

                    <button @click="showPreview = !showPreview" :class="showPreview ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700'" class="px-5 py-2.5 border rounded-xl font-bold text-[10px] uppercase tracking-widest transition-all flex items-center gap-2 shadow-lg">
                        <font-awesome-icon :icon="showPreview ? 'eye-slash' : 'eye'" />
                        {{ showPreview ? 'VOLVER AL EDITOR' : 'VISTA PREVIA' }}
                    </button>
                    <button @click="submit" :disabled="form.processing || !form.nombre || !form.contenido" class="px-8 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-indigo-500/30 hover:bg-indigo-500 transition-all disabled:opacity-40 flex items-center gap-2">
                        <font-awesome-icon :icon="form.processing ? 'spinner' : 'save'" :spin="form.processing" />
                        GUARDAR PLANTILLA
                    </button>
                </div>
            </div>
        </template>

        <div class="min-h-[calc(100vh-140px)] bg-[#0F172A]">
            <div class="max-w-[1700px] mx-auto px-6 py-8">
                <div class="flex gap-8">

                    <!-- Left Sidebar (DARK) -->
                    <div class="w-[320px] shrink-0 space-y-6 sticky top-6 self-start">
                        <div class="bg-slate-900 rounded-[2rem] border border-slate-800 shadow-2xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-slate-800 bg-slate-900/50">
                                <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em]">Configuración</h3>
                            </div>
                            <div class="p-6 space-y-5">
                                <div>
                                    <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Nombre del Documento</label>
                                    <input v-model="form.nombre" type="text" class="w-full bg-slate-800 border-slate-700 rounded-xl px-4 py-3 text-sm font-bold text-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all" />
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Tipo</label>
                                        <select v-model="form.tipo" class="w-full bg-slate-800 border-slate-700 rounded-xl px-3 py-3 text-[11px] font-bold text-slate-300">
                                            <option value="contrato_repse">REPSE</option>
                                            <option value="adenda">ADENDA</option>
                                            <option value="aviso">NOM-035</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Vigencia</label>
                                        <input v-model="form.vigencia_meses" type="number" class="w-full bg-slate-800 border-slate-700 rounded-xl px-3 py-3 text-[11px] font-bold text-slate-300" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variables Panel -->
                        <div class="bg-slate-900 rounded-[2rem] border border-slate-800 shadow-2xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-slate-800 bg-slate-900/50">
                                <h3 class="text-[10px] font-black text-indigo-400 uppercase tracking-[0.2em]">Insertar Variables</h3>
                            </div>
                            <div class="p-4 space-y-5 max-h-[400px] overflow-y-auto custom-scrollbar">
                                <div v-for="group in variableGroups" :key="group.title">
                                    <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block px-2">{{ group.title }}</span>
                                    <div class="space-y-1">
                                        <button v-for="v in group.items" :key="v.var" @click="insertVar(v.var)" class="w-full text-left px-4 py-2 rounded-xl text-[10px] font-bold text-slate-400 bg-slate-800/30 hover:bg-indigo-600 hover:text-white transition-all flex justify-between">
                                            {{ v.label }}
                                            <span class="opacity-30">&#123;&#123;{{ v.var }}&#125;&#125;</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Editor Area -->
                    <div class="flex-1 min-w-0">
                        <div class="bg-white rounded-[3rem] shadow-2xl overflow-hidden flex flex-col min-h-[1000px]">
                            <!-- Quill Editor Container -->
                            <div v-show="!showPreview" class="flex flex-col flex-1">
                                <div id="quill-editor" class="quill-word-style flex-1"></div>
                            </div>

                            <!-- Preview -->
                            <div v-if="showPreview" class="p-24 bg-slate-50 min-h-[1000px]">
                                <div class="preview-content-rich" v-html="previewContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Estilos globales para Quill para que parezca Word y combine con el Dark Mode */
.ql-toolbar.ql-snow {
    border: none !important;
    background: #0f172a !important; /* MATCH SIDEBAR DARK */
    padding: 1.25rem 2.5rem !important;
    border-bottom: 1px solid #1e293b !important;
    position: sticky;
    top: 0;
    z-index: 10;
}

.ql-snow .ql-stroke { stroke: #94a3b8 !important; }
.ql-snow .ql-fill { fill: #94a3b8 !important; }
.ql-snow .ql-picker { color: #94a3b8 !important; }
.ql-snow.ql-toolbar button:hover .ql-stroke, .ql-snow.ql-toolbar button.ql-active .ql-stroke { stroke: #6366f1 !important; }

.ql-container.ql-snow {
    border: none !important;
    font-family: 'Georgia', serif !important;
    background: white;
}

.ql-editor {
    padding: 5rem 6rem !important;
    font-size: 1.15rem !important;
    line-height: 2 !important;
    color: #1e293b !important;
    min-height: 900px !important;
}

.ql-editor.ql-blank::before {
    left: 6rem !important;
    top: 5rem !important;
    font-style: italic !important;
    color: #cbd5e1 !important;
}

.preview-content-rich {
    font-family: 'Georgia', serif;
    font-size: 1.15rem;
    line-height: 2;
    color: #1e293b;
}

.preview-content-rich p { margin-bottom: 1.5rem; }

/* Scrollbar Dark */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #1E293B; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>
