<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    cliente: Object,
    documentos: Array,
});

const uploadForm = useForm({
    documento: null,
    tipo: 'ine_frontal',
});

const isDragging = ref(false);
const fileInput = ref(null);

const documentTypes = [
    { value: 'ine_frontal', label: 'INE Frontal' },
    { value: 'ine_trasera', label: 'INE Trasera' },
    { value: 'comprobante_domicilio', label: 'Comprobante de Domicilio' },
    { value: 'contrato_firmado', label: 'Contrato Firmado' },
    { value: 'otro', label: 'Otro Documento' },
];

const onFileChange = (e) => {
    uploadForm.documento = e.target.files[0];
    submitUpload();
};

const onDrop = (e) => {
    isDragging.value = false;
    uploadForm.documento = e.dataTransfer.files[0];
    submitUpload();
};

const submitUpload = () => {
    uploadForm.post(route('clientes.documentos.store', props.cliente.id), {
        onSuccess: () => {
            uploadForm.reset();
        },
    });
};

const deleteDocumento = (docId) => {
    if (confirm('¿Estás seguro de eliminar este documento?')) {
        useForm({}).delete(route('clientes.documentos.destroy', docId));
    }
};

const getLabel = (tipo) => {
    return documentTypes.find(t => t.value === tipo)?.label || tipo;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-gray-100">Expediente de Crédito</h3>
            <div class="flex gap-2">
                <select v-model="uploadForm.tipo" class="text-sm border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-gray-200">
                    <option v-for="type in documentTypes" :key="type.value" :value="type.value">
                        {{ type.label }}
                    </option>
                </select>
                <a 
                    :href="route('clientes.contrato-credito', cliente.id)" 
                    target="_blank"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                >
                    <font-awesome-icon icon="file-contract" class="mr-2" />
                    Generar Contrato
                </a>
                <button 
                    @click="fileInput.click()" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition-colors"
                >
                    <font-awesome-icon icon="upload" class="mr-2" />
                    Subir Documento
                </button>
                <input type="file" ref="fileInput" class="hidden" @change="onFileChange" />
            </div>
        </div>

        <!-- Área de arrastrar y soltar -->
        <div 
            @dragover.prevent="isDragging = true" 
            @dragleave.prevent="isDragging = false" 
            @drop.prevent="onDrop"
            :class="[
                'border-2 border-dashed rounded-2xl p-8 transition-all flex flex-col items-center justify-center gap-4',
                isDragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800'
            ]"
        >
            <div class="w-16 h-16 bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-full shadow-sm flex items-center justify-center text-gray-400 dark:text-gray-500 dark:text-gray-400">
                <font-awesome-icon icon="file-alt" size="2x" />
            </div>
            <div class="text-center">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">Arrastra aquí tus archivos o haz clic en "Subir"</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">INE, Comprobante de Domicilio o Contratos (PDF, JPG, PNG)</p>
            </div>
            <div v-if="uploadForm.processing" class="w-full max-w-xs bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-4">
                <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
            </div>
        </div>

        <!-- Lista de documentos -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Firma Digital (Si existe) -->
            <div v-if="cliente.credito_firma" class="bg-gradient-to-br from-emerald-50 dark:from-emerald-900/20 to-white dark:to-gray-800 border border-emerald-200 dark:border-emerald-700 rounded-xl p-4 flex flex-col shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2">
                    <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-[10px] font-black rounded-full uppercase tracking-tighter">Firma Digital Validada</span>
                </div>
                
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-10 h-10 bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-lg flex items-center justify-center text-emerald-500 dark:text-emerald-400 shadow-sm">
                        <font-awesome-icon icon="signature" />
                    </div>
                    <div>
                        <p class="text-xs font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest">Solicitud Firmada por</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ cliente.credito_firmado_nombre }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-lg p-2 border border-emerald-100 dark:border-emerald-700 mb-3 select-none">
                    <img :src="cliente.credito_firma" class="max-h-24 mx-auto mix-blend-multiply" alt="Firma Digital">
                </div>

                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div class="bg-white dark:bg-slate-900/50 dark:bg-gray-800/50 p-2 rounded-lg border border-emerald-50 dark:border-emerald-700">
                        <p class="text-[9px] font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase">Monto Solicitado</p>
                        <p class="text-xs font-black text-emerald-700 dark:text-emerald-300">${{ Number(cliente.credito_solicitado_monto).toLocaleString() }}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900/50 dark:bg-gray-800/50 p-2 rounded-lg border border-emerald-50 dark:border-emerald-700">
                        <p class="text-[9px] font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase">Plazo Solicitado</p>
                        <p class="text-xs font-black text-blue-700 dark:text-blue-300">{{ cliente.credito_solicitado_dias }} días</p>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[9px] text-gray-400 dark:text-gray-500 dark:text-gray-400 font-mono mt-auto pt-2 border-t border-emerald-50 dark:border-emerald-700">
                    <span>IP: {{ cliente.credito_firmado_ip }}</span>
                    <span>{{ new Date(cliente.credito_firmado_at).toLocaleDateString() }}</span>
                </div>

                <!-- Action Button Overlay for Signature -->
                <div class="absolute inset-0 bg-emerald-600/90 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <a :href="route('clientes.descargar-solicitud-firmada', cliente.id)" target="_blank" class="px-4 py-2 bg-white dark:bg-slate-900 text-emerald-700 rounded-xl font-black text-xs uppercase tracking-widest hover:scale-105 transition-transform flex items-center gap-2">
                        <font-awesome-icon icon="file-pdf" /> Ver Documento Firmado
                    </a>
                </div>
            </div>

            <div 
                v-for="doc in documentos" :key="doc.id"
                class="bg-white dark:bg-slate-900 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl p-4 flex items-center justify-between group hover:border-blue-200 dark:hover:border-blue-500 transition-all shadow-sm"
            >
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-400 dark:text-gray-500 dark:text-gray-400">
                        <font-awesome-icon icon="file-pdf" v-if="doc.extension === 'pdf'" />
                        <font-awesome-icon icon="file-image" v-else />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white dark:text-gray-100">{{ getLabel(doc.tipo) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ doc.nombre_archivo }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a :href="doc.url" target="_blank" class="p-2 text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <font-awesome-icon icon="eye" />
                    </a>
                    <button @click="deleteDocumento(doc.id)" class="p-2 text-gray-400 dark:text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <font-awesome-icon icon="trash" />
                    </button>
                </div>
            </div>
        </div>

        <div v-if="documentos.length === 0 && !cliente.credito_firma" class="text-center py-10 text-gray-400 dark:text-gray-500 dark:text-gray-400 italic">
            No hay documentos cargados en el expediente.
        </div>
    </div>
</template>
