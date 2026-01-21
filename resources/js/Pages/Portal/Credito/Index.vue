<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref, computed } from 'vue';

const props = defineProps({
    cliente: Object,
    empresa: Object,
});

const uploadForm = useForm({
    documento: null,
    tipo: 'ine_frontal',
});

const fileInput = ref(null);
const isDragging = ref(false);

const documentTypes = [
    { value: 'ine_frontal', label: 'INE Frontal' },
    { value: 'ine_trasera', label: 'INE Trasera' },
    { value: 'comprobante_domicilio', label: 'Comprobante de Domicilio' },
    { value: 'solicitud_credito', label: 'Carga de Solicitud (Firma autógrafa)' },
];

const creditStatus = computed(() => {
    const status = props.cliente.estado_credito;
    const maps = {
        'sin_credito': { label: 'Sin Crédito', class: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400', icon: 'info-circle' },
        'en_revision': { label: 'En Revisión (Documentación)', class: 'bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400', icon: 'search' },
        'autorizado': { label: 'Crédito Autorizado', class: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400', icon: 'check-circle' },
        'suspendido': { label: 'Crédito Suspendido', class: 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400', icon: 'ban' },
    };
    return maps[status] || maps.sin_credito;
});

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
    uploadForm.post(route('portal.credito.documentos.store'), {
        onSuccess: () => {
            uploadForm.reset('documento');
            window.$toast.success('Documento enviado. Nuestro equipo lo revisará.', '¡Éxito!');
        },
        onError: () => window.$toast.error('Hubo un error al subir el archivo.')
    });
};

const deleteDocumento = (id) => {
    if (confirm('¿Deseas eliminar este documento?')) {
        useForm({}).delete(route('portal.credito.documentos.destroy', id), {
            onSuccess: () => window.$toast.success('Documento eliminado correctamente.'),
            onError: () => window.$toast.error('No se pudo eliminar el documento.')
        });
    }
};

const getDocLabel = (tipo) => {
    return documentTypes.find(t => t.value === tipo)?.label || tipo;
};
</script>

<template>
    <Head title="Mi Crédito" />

    <ClientLayout :empresa="empresa">
        <div class="w-full px-4 sm:px-0">
            <!-- Header -->
            <div class="mb-10">
                <Link :href="route('portal.dashboard')" class="text-sm font-bold text-gray-400 dark:text-gray-500 hover:text-[var(--color-primary)] transition-colors inline-flex items-center gap-2 mb-4">
                    <font-awesome-icon icon="arrow-left" /> Volver al Inicio
                </Link>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Gestión de <span class="text-[var(--color-primary)]">Crédito</span></h1>
                <p class="text-gray-500 dark:text-gray-400 font-medium">Consulte su saldo disponible y administre su documentación para compras a crédito.</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Columna Izquierda: Resumen y Estado -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Estado Card -->
                    <div class="bg-white dark:bg-slate-900/60 rounded-[2rem] p-8 shadow-xl dark:shadow-2xl shadow-gray-200 dark:shadow-black/50 border border-gray-100 dark:border-white/10 dark:backdrop-blur-xl transition-all">
                        <div class="flex items-center gap-4 mb-6">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-xl transition-colors', creditStatus.class]">
                                <font-awesome-icon :icon="creditStatus.icon" />
                            </div>
                            <div>
                                <p class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Estado Actual</p>
                                <h3 class="font-black text-gray-900 dark:text-white">{{ creditStatus.label }}</h3>
                            </div>
                        </div>

                        <div v-if="cliente.credito_activo" class="space-y-4">
                            <div>
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 mb-1">Límite de Crédito</p>
                                <p class="text-2xl font-black text-gray-900 dark:text-white">
                                    ${{ Number(cliente.limite_credito).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-4 border border-emerald-100 dark:border-emerald-800/30">
                                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 mb-1">Crédito Disponible</p>
                                <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">
                                    ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 mb-1">Saldo en Uso</p>
                                <p class="text-xl font-bold text-red-500 dark:text-red-400">
                                    ${{ Number(cliente.saldo_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                                </p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-white/10">
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic">Días de crédito autorizados: {{ cliente.dias_credito }} días.</p>
                            </div>
                        </div>
                        <div v-else class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6 border border-blue-100 dark:border-blue-900/30">
                            <p class="text-sm text-blue-700 dark:text-blue-300 font-medium leading-relaxed">
                                Para habilitar compras a crédito, por favor suba la documentación requerida. Nuestro equipo analizará su perfil en un plazo de 24 a 48 horas.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Documentación -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Upload Box -->
                    <div class="bg-white dark:bg-slate-900/60 rounded-[2rem] p-8 shadow-xl dark:shadow-2xl border border-gray-100 dark:border-white/10 dark:backdrop-blur-xl transition-colors">
                        <div class="flex flex-col sm:flex-row items-center gap-4 mb-8">
                            <!-- Nueva Opción: Firma Digital -->
                            <div v-if="!cliente.credito_firma" class="flex-1 w-full">
                                <Link :href="route('portal.credito.solicitud.firmar')" class="flex flex-col items-center justify-center p-6 rounded-[2rem] bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-xl shadow-emerald-500/20 hover:scale-[1.02] transition-all group">
                                    <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-2xl mb-3 group-hover:rotate-12 transition-transform">
                                        🖋️
                                    </div>
                                    <span class="font-black uppercase tracking-widest text-xs">Firmar Digitalmente</span>
                                    <span class="text-[10px] opacity-80 mt-1 font-bold">Recomendado - Sin papeles</span>
                                </Link>
                            </div>
                            <!-- Si ya firmó: Descargar Firmada -->
                            <!-- Si ya firmó: Descargar Firmada -->
                            <div v-else class="flex-1 w-full">
                                <a :href="route('portal.credito.solicitud.descargar')" target="_blank" class="flex flex-col items-center justify-center p-6 rounded-[2rem] bg-white dark:bg-slate-800 border-2 border-emerald-100 dark:border-emerald-900/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-all group">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-2xl mb-3 group-hover:bounce transition-transform">
                                        ✅
                                    </div>
                                    <span class="font-black uppercase tracking-widest text-xs">Descargar Solicitud Firmada</span>
                                    <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-bold">Documento Legal Generado</span>
                                </a>
                            </div>

                            <div class="flex-1 w-full">
                                <a :href="route('portal.credito.solicitud.descargar')" target="_blank" class="flex flex-col items-center justify-center p-6 rounded-[2rem] bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-all group border border-orange-100 dark:border-orange-900/30">
                                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-orange-900/30 flex items-center justify-center text-2xl mb-3 group-hover:-translate-y-1 transition-transform">
                                        📥
                                    </div>
                                    <span class="font-black uppercase tracking-widest text-xs">Descargar Para Firma Autógrafa</span>
                                    <span class="text-[10px] text-orange-400 mt-1 font-bold">Método Tradicional</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="grid sm:grid-cols-2 gap-4 mb-6">
                            <div v-for="type in documentTypes" :key="type.value">
                                <button 
                                    @click="uploadForm.tipo = type.value; fileInput.click()"
                                    class="w-full flex items-center justify-between p-4 rounded-2xl border border-gray-100 dark:border-white/10 dark:bg-slate-800/50 hover:border-[var(--color-primary)] dark:hover:border-[var(--color-primary)] hover:bg-orange-50 dark:hover:bg-[var(--color-primary)]/10 transition-all text-left group"
                                >
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-[var(--color-primary)]">{{ type.label }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">PDF o Imagen (Máx 5MB)</p>
                                    </div>
                                    <font-awesome-icon icon="plus-circle" class="text-gray-300 dark:text-gray-600 group-hover:text-[var(--color-primary)]" />
                                </button>
                            </div>
                        </div>

                        <div 
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="onDrop"
                            :class="[
                                'border-2 border-dashed rounded-[2rem] p-12 text-center transition-all',
                                isDragging 
                                    ? 'border-[var(--color-primary)] bg-orange-50 dark:bg-[var(--color-primary)]/10' 
                                    : 'border-gray-100 dark:border-white/10 bg-white dark:bg-slate-800/30'
                            ]"
                        >
                            <font-awesome-icon icon="cloud-upload-alt" class="text-4xl text-gray-200 dark:text-gray-600 mb-4 transition-colors" />
                            <p class="text-gray-400 dark:text-gray-500 font-bold">O arrastre sus archivos aquí para subirlos rápidamente</p>
                            <input type="file" ref="fileInput" class="hidden" @change="onFileChange" />
                        </div>

                        <div v-if="uploadForm.processing" class="mt-6">
                             <div class="w-full bg-gray-100 rounded-full h-2">
                                 <div class="bg-[var(--color-primary)] h-2 rounded-full animate-pulse" style="width: 100%"></div>
                             </div>
                             <p class="text-center text-xs font-bold text-gray-400 mt-2 uppercase tracking-widest">Subiendo Documento...</p>
                        </div>
                    </div>

                    <!-- Expediente Actual -->
                    <div v-if="cliente.documentos?.length > 0" class="bg-white dark:bg-slate-900/60 rounded-[2rem] p-8 shadow-xl dark:shadow-2xl border border-gray-100 dark:border-white/10 dark:backdrop-blur-xl transition-colors">
                        <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6">Documentos Enviados</h2>
                        <div class="grid gap-4">
                            <div 
                                v-for="doc in cliente.documentos" :key="doc.id"
                                class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-800/50 border border-gray-100 dark:border-white/10 hover:border-blue-200 dark:hover:border-blue-500/30 transition-all"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white dark:bg-slate-700/50 rounded-xl flex items-center justify-center text-gray-400 dark:text-gray-300 shadow-sm dark:shadow-none">
                                        <font-awesome-icon icon="file-pdf" v-if="doc.extension === 'pdf'" />
                                        <font-awesome-icon icon="file-image" v-else />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ getDocLabel(doc.tipo) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ doc.nombre_archivo }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a :href="doc.url" target="_blank" class="p-3 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                        <font-awesome-icon icon="eye" />
                                    </a>
                                    <button 
                                        v-if="['sin_credito', 'en_revision', 'suspendido'].includes(cliente.estado_credito)"
                                        @click="deleteDocumento(doc.id)" 
                                        class="p-3 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                                    >
                                        <font-awesome-icon icon="trash" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
