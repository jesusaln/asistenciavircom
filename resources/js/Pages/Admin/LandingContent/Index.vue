<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, useForm, router } from '@inertiajs/vue3';
import Swal from '@/Utils/Swal';
import * as Vue from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    faqs: Array,
    testimonios: Array,
    logos: Array,
    marcas: Array,
    procesos: Array,
    ofertas: Array,
    config: Object,
});

// Tabs
const activeTab = Vue.ref('hero');

// ================== HERO ==================
const heroForm = useForm({
    hero_titulo: props.config?.hero_titulo || '',
    hero_subtitulo: props.config?.hero_subtitulo || '',
    hero_descripcion: props.config?.hero_descripcion || '',
    hero_cta_primario: props.config?.hero_cta_primario || '',
    hero_cta_secundario: props.config?.hero_cta_secundario || '',
    hero_badge_texto: props.config?.hero_badge_texto || '',
});

const saveHero = () => {
    heroForm.put(route('empresa-configuracion.landing-content.hero.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Éxito
        },
    });
};

// ================== FAQs ==================
const showFaqModal = Vue.ref(false);
const editingFaq = Vue.ref(null);
const faqForm = useForm({
    pregunta: '',
    respuesta: '',
    orden: 0,
    activo: true,
});

const openFaqModal = (faq = null) => {
    editingFaq.value = faq;
    if (faq) {
        faqForm.pregunta = faq.pregunta;
        faqForm.respuesta = faq.respuesta;
        faqForm.orden = faq.orden;
        faqForm.activo = faq.activo;
    } else {
        faqForm.reset();
        faqForm.orden = props.faqs?.length || 0;
    }
    showFaqModal.value = true;
};

const saveFaq = () => {
    if (editingFaq.value) {
        faqForm.put(route('empresa-configuracion.landing-content.faqs.update', editingFaq.value.id), {
            onSuccess: () => { showFaqModal.value = false; faqForm.reset(); },
        });
    } else {
        faqForm.post(route('empresa-configuracion.landing-content.faqs.store'), {
            onSuccess: () => { showFaqModal.value = false; faqForm.reset(); },
        });
    }
};

const deleteFaq = async (faq) => {
    const result = await Swal.fire({
        title: 'Eliminar pregunta',
        text: '¿Eliminar esta pregunta frecuente?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.faqs.destroy', faq.id));
    }
};

// ================== TESTIMONIOS ==================
const showTestimonioModal = Vue.ref(false);
const editingTestimonio = Vue.ref(null);
const testimonioForm = useForm({
    nombre: '',
    cargo: '',
    empresa_cliente: '',
    comentario: '',
    calificacion: 5,
    foto: null,
    orden: 0,
    activo: true,
});

const openTestimonioModal = (testimonio = null) => {
    editingTestimonio.value = testimonio;
    if (testimonio) {
        testimonioForm.nombre = testimonio.nombre;
        testimonioForm.cargo = testimonio.cargo;
        testimonioForm.empresa_cliente = testimonio.empresa_cliente;
        testimonioForm.comentario = testimonio.comentario;
        testimonioForm.calificacion = testimonio.calificacion;
        testimonioForm.orden = testimonio.orden;
        testimonioForm.activo = testimonio.activo;
        testimonioForm.foto = null;
    } else {
        testimonioForm.reset();
        testimonioForm.orden = props.testimonios?.length || 0;
    }
    showTestimonioModal.value = true;
};

const saveTestimonio = () => {
    const options = {
        onSuccess: () => { showTestimonioModal.value = false; testimonioForm.reset(); },
        forceFormData: true,
    };
    
    if (editingTestimonio.value) {
        testimonioForm
            .transform((data) => ({ ...data, _method: 'PUT' }))
            .post(route('empresa-configuracion.landing-content.testimonios.update', editingTestimonio.value.id), options);
    } else {
        testimonioForm.post(route('empresa-configuracion.landing-content.testimonios.store'), options);
    }
};

const deleteTestimonio = async (testimonio) => {
    const result = await Swal.fire({
        title: 'Eliminar testimonio',
        text: '¿Eliminar este testimonio?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.testimonios.destroy', testimonio.id));
    }
};

// ================== LOGOS ==================
const showLogoModal = Vue.ref(false);
const editingLogo = Vue.ref(null);
const logoForm = useForm({
    nombre_empresa: '',
    logo: null,
    url: '',
    orden: 0,
    activo: true,
});

const openLogoModal = (logo = null) => {
    editingLogo.value = logo;
    if (logo) {
        logoForm.nombre_empresa = logo.nombre_empresa;
        logoForm.url = logo.url || '';
        logoForm.orden = logo.orden;
        logoForm.activo = logo.activo;
        logoForm.logo = null;
    } else {
        logoForm.reset();
        logoForm.orden = props.logos?.length || 0;
    }
    showLogoModal.value = true;
};

const saveLogo = () => {
    const options = {
        onSuccess: () => { showLogoModal.value = false; logoForm.reset(); },
        forceFormData: true,
    };
    
    if (editingLogo.value) {
        // La ruta ya es POST, no necesita _method
        logoForm.post(route('empresa-configuracion.landing-content.logos.update', editingLogo.value.id), options);
    } else {
        logoForm.post(route('empresa-configuracion.landing-content.logos.store'), options);
    }
};

const deleteLogo = async (logo) => {
    const result = await Swal.fire({
        title: 'Eliminar logo',
        text: '¿Eliminar este logo?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.logos.destroy', logo.id));
    }
};

// ================== MARCAS CSAM ==================
const showMarcaModal = Vue.ref(false);
const editingMarca = Vue.ref(null);
const marcaForm = useForm({
    nombre: '',
    logo: null,
    tipo: 'oficial',
    texto_auxiliar: '',
    url: '',
    orden: 0,
    activo: true,
});

const openMarcaModal = (marca = null) => {
    editingMarca.value = marca;
    if (marca) {
        marcaForm.nombre = marca.nombre;
        marcaForm.tipo = marca.tipo;
        marcaForm.texto_auxiliar = marca.texto_auxiliar || '';
        marcaForm.url = marca.url || '';
        marcaForm.orden = marca.orden;
        marcaForm.activo = marca.activo;
        marcaForm.logo = null;
    } else {
        marcaForm.reset();
        marcaForm.orden = props.marcas?.length || 0;
    }
    showMarcaModal.value = true;
};

const saveMarca = () => {
    const options = {
        onSuccess: () => { showMarcaModal.value = false; marcaForm.reset(); },
        forceFormData: true,
    };
    
    if (editingMarca.value) {
        // La ruta ya es POST, no necesita _method
        marcaForm.post(route('empresa-configuracion.landing-content.marcas.update', editingMarca.value.id), options);
    } else {
        marcaForm.post(route('empresa-configuracion.landing-content.marcas.store'), options);
    }
};

const deleteMarca = async (marca) => {
    const result = await Swal.fire({
        title: 'Eliminar marca',
        text: '¿Eliminar esta marca autorizada?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.marcas.destroy', marca.id));
    }
};

// Marcas CSAM dinámicas (ordenadas)
const marcasCSAM = Vue.computed(() => props.marcas?.filter(m => m.activo).sort((a, b) => a.orden - b.orden) || []);

// ================== PROCESOS ==================
const showProcesoModal = Vue.ref(false);
const editingProceso = Vue.ref(null);
const procesoForm = useForm({
    titulo: '',
    descripcion: '',
    tipo: 'reparacion',
    icono: 'cogs',
    orden: 0,
    activo: true,
});

const openProcesoModal = (proceso = null) => {
    editingProceso.value = proceso;
    if (proceso) {
        procesoForm.titulo = proceso.titulo;
        procesoForm.descripcion = proceso.descripcion || '';
        procesoForm.tipo = proceso.tipo || 'reparacion';
        procesoForm.icono = proceso.icono || 'cogs';
        procesoForm.orden = proceso.orden;
        procesoForm.activo = proceso.activo;
    } else {
        procesoForm.reset();
        procesoForm.orden = props.procesos?.length || 0;
    }
    showProcesoModal.value = true;
};

const saveProceso = () => {
    if (editingProceso.value) {
        procesoForm.put(route('empresa-configuracion.landing-content.procesos.update', editingProceso.value.id), {
            onSuccess: () => { showProcesoModal.value = false; procesoForm.reset(); },
        });
    } else {
        procesoForm.post(route('empresa-configuracion.landing-content.procesos.store'), {
            onSuccess: () => { showProcesoModal.value = false; procesoForm.reset(); },
        });
    }
};

const deleteProceso = async (proceso) => {
    const result = await Swal.fire({
        title: 'Eliminar paso',
        text: '¿Eliminar este paso del proceso?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.procesos.destroy', proceso.id));
    }
};

// ================== OFERTAS ==================
const showOfertaModal = Vue.ref(false);
const editingOferta = Vue.ref(null);
const ofertaForm = useForm({
    titulo: 'OFERTA ESPECIAL',
    subtitulo: '',
    descripcion: '',
    descuento_porcentaje: 20,
    precio_original: 0,
    caracteristica_1: 'Instalación profesional incluida',
    caracteristica_2: 'Garantía extendida de 3 años',
    caracteristica_3: 'Soporte técnico 24/7',
    fecha_inicio: null,
    fecha_fin: null,
    orden: 0,
    activo: true,
});

const openOfertaModal = (oferta = null) => {
    editingOferta.value = oferta;
    if (oferta) {
        ofertaForm.titulo = oferta.titulo;
        ofertaForm.subtitulo = oferta.subtitulo;
        ofertaForm.descripcion = oferta.descripcion || '';
        ofertaForm.descuento_porcentaje = oferta.descuento_porcentaje;
        ofertaForm.precio_original = oferta.precio_original;
        ofertaForm.caracteristica_1 = oferta.caracteristica_1 || '';
        ofertaForm.caracteristica_2 = oferta.caracteristica_2 || '';
        ofertaForm.caracteristica_3 = oferta.caracteristica_3 || '';
        ofertaForm.fecha_inicio = oferta.fecha_inicio?.slice(0, 16) || null;
        ofertaForm.fecha_fin = oferta.fecha_fin?.slice(0, 16) || null;
        ofertaForm.orden = oferta.orden;
        ofertaForm.activo = oferta.activo;
    } else {
        ofertaForm.reset();
        ofertaForm.titulo = 'OFERTA ESPECIAL';
        ofertaForm.descuento_porcentaje = 20;
        ofertaForm.caracteristica_1 = 'Instalación profesional incluida';
        ofertaForm.caracteristica_2 = 'Garantía extendida de 3 años';
        ofertaForm.caracteristica_3 = 'Soporte técnico 24/7';
        ofertaForm.orden = props.ofertas?.length || 0;
    }
    showOfertaModal.value = true;
};

const saveOferta = () => {
    if (editingOferta.value) {
        ofertaForm.put(route('empresa-configuracion.landing-content.ofertas.update', editingOferta.value.id), {
            onSuccess: () => { showOfertaModal.value = false; ofertaForm.reset(); },
        });
    } else {
        ofertaForm.post(route('empresa-configuracion.landing-content.ofertas.store'), {
            onSuccess: () => { showOfertaModal.value = false; ofertaForm.reset(); },
        });
    }
};

const deleteOferta = async (oferta) => {
    const result = await Swal.fire({
        title: 'Eliminar oferta',
        text: '¿Eliminar esta oferta?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (result.isConfirmed) {
        router.delete(route('empresa-configuracion.landing-content.ofertas.destroy', oferta.id));
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 0 }).format(price);
};

const normalizeIcon = (icono, fallback = 'circle-dot') => {
    if (!icono) return fallback;
    const containsEmoji = /[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test(icono);
    return containsEmoji ? fallback : icono;
};
</script>

<template>
    <Head title="Contenido de Landing Page" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-100 leading-tight">
                Contenido de Landing Page
            </h2>
        </template>

        <div class="py-8">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Tabs -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 mb-6 overflow-hidden">
                    <div class="border-b border-slate-100 dark:border-slate-700 overflow-x-auto custom-scrollbar">
                        <nav class="flex -mb-px min-w-max">
                            <button 
                                @click="activeTab = 'hero'"
                                :class="activeTab === 'hero' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Hero Principal
                            </button>
                            <button 
                                @click="activeTab = 'faqs'"
                                :class="activeTab === 'faqs' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Preguntas Frecuentes ({{ faqs?.length || 0 }})
                            </button>
                            <button 
                                @click="activeTab = 'testimonios'"
                                :class="activeTab === 'testimonios' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Testimonios ({{ testimonios?.length || 0 }})
                            </button>
                            <button 
                                @click="activeTab = 'marcas'"
                                :class="activeTab === 'marcas' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Marcas CSAM ({{ marcas?.length || 0 }})
                            </button>
                            <button 
                                @click="activeTab = 'procesos'"
                                :class="activeTab === 'procesos' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Proceso ({{ procesos?.length || 0 }})
                            </button>
                            <button 
                                @click="activeTab = 'ofertas'"
                                :class="activeTab === 'ofertas' ? 'border-brand-500 text-brand-600 dark:text-orange-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-6 py-4 text-sm font-medium border-b-2 transition-colors"
                            >
                                Ofertas ({{ ofertas?.length || 0 }})
                            </button>
                        </nav>
                    </div>

                    <!-- Content: Hero -->
                    <div v-if="activeTab === 'hero'" class="p-8 w-full">
                        <div class="mb-8">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">Personalización del Hero</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm">Cambia los textos principales que ven tus clientes al entrar a la página.</p>
                        </div>

                        <form @submit.prevent="saveHero" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Título Principal (Hero)</label>
                                    <input v-model="heroForm.hero_titulo" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Ej: Climatización Inteligente para tu Hogar" />
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 italic">* El título se divide automáticamente para resaltar la palabra central.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Palabra Resaltada (Subtítulo)</label>
                                    <input v-model="heroForm.hero_subtitulo" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Ej: Inteligente" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Texto del Badge superior</label>
                                    <input v-model="heroForm.hero_badge_texto" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Ej: Servicio Disponible hoy" />
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Descripción (Párrafo)</label>
                                    <textarea v-model="heroForm.hero_descripcion" rows="3" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Describe brevemente lo que haces..."></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Texto Botón Primario</label>
                                    <input v-model="heroForm.hero_cta_primario" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Ej: Ver Servicios" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Texto Botón Secundario</label>
                                    <input v-model="heroForm.hero_cta_secundario" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200" placeholder="Ej: Contáctanos" />
                                </div>
                            </div>

                            <div class="pt-4 flex justify-end">
                                <button type="submit" :disabled="heroForm.processing" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition shadow-xl shadow-sky-200">
                                    {{ heroForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Content: FAQs -->
                    <div v-if="activeTab === 'faqs'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <p class="text-slate-500 dark:text-slate-400">Gestiona las preguntas frecuentes de tu landing page</p>
                            <button @click="openFaqModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                                + Agregar FAQ
                            </button>
                        </div>
                        
                        <div v-if="faqs?.length" class="space-y-3">
                            <div v-for="faq in faqs" :key="faq.id" class="bg-white dark:bg-slate-800 rounded-xl p-4 flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-slate-900 dark:text-slate-100">{{ faq.pregunta }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">{{ faq.respuesta }}</p>
                                </div>
                                <div class="flex items-center gap-2 ml-4">
                                    <span :class="faq.activo ? 'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'" class="px-2 py-1 rounded-xl text-xs font-medium">
                                        {{ faq.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <button @click="openFaqModal(faq)" class="p-2 text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-blue-400"><font-awesome-icon icon="pen" /></button>
                                    <button @click="deleteFaq(faq)" class="p-2 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400"><font-awesome-icon icon="trash-can" /></button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="question-circle" /></p>
                            <p>No hay preguntas frecuentes. Se mostrarán las predeterminadas.</p>
                        </div>
                    </div>

                    <!-- Content: Testimonios -->
                    <div v-if="activeTab === 'testimonios'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <p class="text-slate-500 dark:text-slate-400">Gestiona los testimonios de clientes</p>
                            <button @click="openTestimonioModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                                + Agregar Testimonio
                            </button>
                        </div>
                        
                        <div v-if="testimonios?.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="t in testimonios" :key="t.id" class="bg-white dark:bg-slate-800 rounded-xl p-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-sky-900/20/40 flex items-center justify-center overflow-hidden">
                                        <img v-if="t.foto_url" :src="t.foto_url" class="w-full h-full object-cover" />
                                        <span v-else class="text-blue-600 dark:text-blue-400 font-bold text-sm">{{ t.iniciales }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900 dark:text-slate-100 text-sm">{{ t.nombre }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ t.cargo || t.empresa_cliente }}</p>
                                    </div>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-200 line-clamp-3 mb-3">"{{ t.comentario }}"</p>
                                    <div class="flex items-center justify-between">
                                        <div class="flex">
                                        <span v-for="i in t.calificacion" :key="i" class="text-brand-400 text-sm"><font-awesome-icon icon="star" /></span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button @click="openTestimonioModal(t)" class="p-1 text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-blue-400 text-sm"><font-awesome-icon icon="pen" /></button>
                                        <button @click="deleteTestimonio(t)" class="p-1 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 text-sm"><font-awesome-icon icon="trash-can" /></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="comments" /></p>
                            <p>No hay testimonios. Se mostrarán los predeterminados.</p>
                        </div>
                    </div>

                    <!-- Content: Logos -->
                    <div v-if="activeTab === 'logos'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <p class="text-slate-500 dark:text-slate-400">Logos de empresas que confían en ti</p>
                            <button @click="openLogoModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                                + Agregar Logo
                            </button>
                        </div>
                        
                        <div v-if="logos?.length" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            <div v-for="logo in logos" :key="logo.id" class="bg-white dark:bg-slate-800 rounded-xl p-4 text-center relative group">
                                <img v-if="logo.logo_url" :src="logo.logo_url" :alt="logo.nombre_empresa" class="h-12 mx-auto object-contain mb-2" />
                                <p class="text-xs text-slate-500 dark:text-slate-200 truncate">{{ logo.nombre_empresa }}</p>
                                <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                    <button @click="openLogoModal(logo)" class="p-1 bg-white dark:bg-slate-700 rounded-xl shadow text-xs dark:text-slate-200"><font-awesome-icon icon="pen" /></button>
                                    <button @click="deleteLogo(logo)" class="p-1 bg-white dark:bg-slate-700 rounded-xl shadow text-xs dark:text-slate-200"><font-awesome-icon icon="trash-can" /></button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="building" /></p>
                            <p>No hay logos. La sección no se mostrará en la landing.</p>
                        </div>
                    </div>

                    <!-- Content: Marcas CSAM -->
                    <div v-if="activeTab === 'marcas'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-slate-100">Centro de Servicio Autorizado (CSAM)</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestiona las marcas de las cuales eres centro oficial (Mirage, LG, etc.)</p>
                            </div>
                            <button @click="openMarcaModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                                + Agregar Marca
                            </button>
                        </div>
                        
                        <div v-if="marcas?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="marca in marcas" :key="marca.id" class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 group relative">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 bg-white dark:bg-slate-700 rounded-2xl shadow-sm flex items-center justify-center p-2">
                                        <img v-if="marca.logo_url" :src="marca.logo_url" :alt="marca.nombre" class="max-h-full max-w-full object-contain" />
                                        <span v-else class="text-2xl pt-1"><font-awesome-icon icon="shield-halved" /></span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 dark:text-slate-100">{{ marca.nombre }}</h4>
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                :class="{
                                                    'bg-blue-50 dark:bg-sky-900/20/40 text-sky-800 dark:text-sky-200 dark:text-blue-300': marca.tipo === 'master',
                                                    'bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300': marca.tipo === 'oficial',
                                                    'bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-200': marca.tipo === 'autorizada',
                                                }"
                                            >
                                                {{ marca.tipo }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 dark:text-slate-500 font-bold mt-1 uppercase tracking-wide">{{ marca.texto_auxiliar }}</p>
                                    </div>
                                </div>
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                    <button @click="openMarcaModal(marca)" class="p-2 bg-white dark:bg-slate-700 rounded-2xl shadow-xl text-sm hover:text-brand-600 dark:hover:text-blue-400"><font-awesome-icon icon="pen" /></button>
                                    <button @click="deleteMarca(marca)" class="p-2 bg-white dark:bg-slate-700 rounded-2xl shadow-xl text-sm hover:text-rose-600 dark:hover:text-rose-400"><font-awesome-icon icon="trash-can" /></button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="shield-halved" /></p>
                            <p>No hay marcas configuradas. Agrega Mirage como prioridad.</p>
                        </div>
                    </div>

                    <!-- Content: Proceso -->
                    <div v-if="activeTab === 'procesos'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-slate-100">Proceso de Servicio</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Define los pasos que sigue tu empresa para atender a un cliente.</p>
                            </div>
                            <button @click="openProcesoModal()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                                + Agregar Paso
                            </button>
                        </div>
                        
                        <div v-if="procesos?.length" class="space-y-6">
                            <div v-for="(p, index) in procesos" :key="p.id" class="flex gap-4 items-start bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-100 dark:border-slate-700 group">
                                <div class="w-10 h-10 bg-white dark:bg-slate-700 rounded-2xl shadow-sm flex items-center justify-center text-2xl flex-shrink-0">
                                    <font-awesome-icon :icon="normalizeIcon(p.icono, 'cogs')" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 px-2 py-0.5 rounded-xl uppercase">Paso {{ index + 1 }}</span>
                                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded-xl uppercase">{{ p.tipo }}</span>
                                        <h4 class="font-bold text-slate-900 dark:text-slate-100 text-lg">{{ p.titulo }}</h4>
                                    </div>
                                    <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm leading-relaxed">{{ p.descripcion }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="openProcesoModal(p)" class="p-2 bg-white dark:bg-slate-700 rounded-2xl shadow-sm text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-blue-400"><font-awesome-icon icon="pen" /></button>
                                    <button @click="deleteProceso(p)" class="p-2 bg-white dark:bg-slate-700 rounded-2xl shadow-sm text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400"><font-awesome-icon icon="trash-can" /></button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="tools" /></p>
                            <p>No hay pasos definidos. Agrega los pasos de tu servicio (Solicitud, Visita, Solución, etc.)</p>
                        </div>
                    </div>

                    <!-- Content: Ofertas -->
                    <div v-if="activeTab === 'ofertas'" class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-slate-100">Banner de Ofertas con Countdown</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Gestiona las ofertas especiales que se muestran en el banner de la landing page.</p>
                            </div>
                            <button @click="openOfertaModal()" class="px-4 py-2 bg-brand-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700">
                                + Nueva Oferta
                            </button>
                        </div>
                        
                        <div v-if="ofertas?.length" class="space-y-6">
                            <div v-for="oferta in ofertas" :key="oferta.id" class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-6 text-white relative group">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="px-3 py-1 bg-brand-500 rounded-xl text-xs font-bold">{{ oferta.titulo }}</span>
                                            <span :class="oferta.activo ? 'bg-brand-500' : 'bg-slate-400 dark:bg-slate-600'" class="px-2 py-0.5 rounded-xl text-xs">
                                                {{ oferta.activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                        <h4 class="text-xl font-bold">{{ oferta.subtitulo }}</h4>
                                        <div class="flex items-center gap-4 mt-2">
                                            <span class="text-slate-400 dark:text-slate-500 line-through">{{ formatPrice(oferta.precio_original) }}</span>
                                            <span class="text-2xl font-black text-orange-400 dark:text-orange-300">{{ formatPrice(oferta.precio_original * (1 - oferta.descuento_porcentaje / 100)) }}</span>
                                            <span class="px-2 py-1 bg-brand-500 text-white text-sm font-bold rounded-xl">-{{ oferta.descuento_porcentaje }}%</span>
                                        </div>
                                        <p v-if="oferta.fecha_fin" class="text-xs text-slate-400 dark:text-slate-500 mt-2">
                                            <font-awesome-icon icon="clock" class="mr-1" /> Termina: {{ new Date(oferta.fecha_fin).toLocaleString('es-MX') }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="openOfertaModal(oferta)" class="p-3 bg-white/10 rounded-xl hover:bg-white/20 transition"><font-awesome-icon icon="pen" /></button>
                                        <button @click="deleteOferta(oferta)" class="p-3 bg-white/10 rounded-xl hover:bg-slate-500/50 transition"><font-awesome-icon icon="trash-can" /></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <p class="text-4xl mb-2"><font-awesome-icon icon="fire" /></p>
                            <p>No hay ofertas configuradas.</p>
                            <p class="text-sm dark:text-slate-400">El banner de ofertas no se mostrará hasta que agregues una.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modales existentes... -->

        <!-- Modal FAQ -->
        <Teleport to="body">
            <div v-if="showFaqModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showFaqModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-bold mb-4">{{ editingFaq ? 'Editar' : 'Nueva' }} Pregunta Frecuente</h3>
                    <form @submit.prevent="saveFaq" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Pregunta</label>
                            <input v-model="faqForm.pregunta" type="text" class="w-full rounded-xl border-slate-300" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Respuesta</label>
                            <textarea v-model="faqForm.respuesta" rows="4" class="w-full rounded-xl border-slate-300" required></textarea>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="faqForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="faqForm.activo" type="checkbox" id="faq-activo" class="rounded-xl" />
                                <label for="faq-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showFaqModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="faqForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Testimonio -->
        <Teleport to="body">
            <div v-if="showTestimonioModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showTestimonioModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-bold mb-4">{{ editingTestimonio ? 'Editar' : 'Nuevo' }} Testimonio</h3>
                    <form @submit.prevent="saveTestimonio" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                                <input v-model="testimonioForm.nombre" type="text" class="w-full rounded-xl border-slate-300" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Cargo</label>
                                <input v-model="testimonioForm.cargo" type="text" class="w-full rounded-xl border-slate-300" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Empresa del cliente</label>
                            <input v-model="testimonioForm.empresa_cliente" type="text" class="w-full rounded-xl border-slate-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Comentario</label>
                            <textarea v-model="testimonioForm.comentario" rows="3" class="w-full rounded-xl border-slate-300" required></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Calificación</label>
                                <select v-model="testimonioForm.calificacion" class="w-full rounded-xl border-slate-300">
                                    <option :value="5">5 estrellas</option>
                                    <option :value="4">4 estrellas</option>
                                    <option :value="3">3 estrellas</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Foto (opcional)</label>
                                <input type="file" @change="testimonioForm.foto = $event.target.files[0]" accept="image/*" class="w-full text-sm" />
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="testimonioForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="testimonioForm.activo" type="checkbox" id="testimonio-activo" class="rounded-xl" />
                                <label for="testimonio-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showTestimonioModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="testimonioForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Logo -->
        <Teleport to="body">
            <div v-if="showLogoModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showLogoModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold mb-4">{{ editingLogo ? 'Editar' : 'Nuevo' }} Logo de Cliente</h3>
                    <form @submit.prevent="saveLogo" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la empresa</label>
                            <input v-model="logoForm.nombre_empresa" type="text" class="w-full rounded-xl border-slate-300" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Logo {{ editingLogo ? '(dejar vacío para mantener)' : '' }}</label>
                            <input type="file" @change="logoForm.logo = $event.target.files[0]" accept="image/*" class="w-full text-sm" :required="!editingLogo" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">URL (opcional)</label>
                            <input v-model="logoForm.url" type="url" placeholder="https://ejemplo.com" class="w-full rounded-xl border-slate-300" />
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="logoForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="logoForm.activo" type="checkbox" id="logo-activo" class="rounded-xl" />
                                <label for="logo-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showLogoModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="logoForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 disabled:opacity-50">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Marca CSAM -->
        <Teleport to="body">
            <div v-if="showMarcaModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showMarcaModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold mb-4">{{ editingMarca ? 'Editar' : 'Nueva' }} Marca CSAM</h3>
                    <form @submit.prevent="saveMarca" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                                <input v-model="marcaForm.nombre" type="text" class="w-full rounded-xl border-slate-300" required placeholder="Ej: MIRAGE" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Nivel</label>
                                <select v-model="marcaForm.tipo" class="w-full rounded-xl border-slate-300">
                                    <option value="master">Soporte Master (Top)</option>
                                    <option value="oficial">Centro Oficial</option>
                                    <option value="autorizada">Autorizado</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Texto Distintivo</label>
                            <input v-model="marcaForm.texto_auxiliar" type="text" class="w-full rounded-xl border-slate-300" placeholder="Ej: Soporte Master Oficial" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Logo {{ editingMarca ? '(dejar vacío para mantener)' : '' }}</label>
                            <input type="file" @change="marcaForm.logo = $event.target.files[0]" accept="image/*" class="w-full text-sm" :required="!editingMarca" />
                            <p class="text-[10px] text-slate-400 mt-1">Se recomienda logo en formato PNG con fondo transparente.</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="marcaForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="marcaForm.activo" type="checkbox" id="marca-activo" class="rounded-xl" />
                                <label for="marca-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showMarcaModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="marcaForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                                Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Proceso -->
        <Teleport to="body">
            <div v-if="showProcesoModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showProcesoModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                    <h3 class="text-lg font-bold mb-4">{{ editingProceso ? 'Editar' : 'Nuevo' }} Paso del Proceso</h3>
                    <form @submit.prevent="saveProceso" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Proceso</label>
                                <select v-model="procesoForm.tipo" class="w-full rounded-xl border-slate-300">
                                    <option value="reparacion">Reparación</option>
                                    <option value="instalacion">Instalación</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Icono/Emoji</label>
                                <input v-model="procesoForm.icono" type="text" class="w-full rounded-xl border-slate-300 text-center" placeholder="cogs" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Título del Paso</label>
                            <input v-model="procesoForm.titulo" type="text" class="w-full rounded-xl border-slate-300" required placeholder="Ej: Diagnóstico Preciso" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
                            <textarea v-model="procesoForm.descripcion" rows="3" class="w-full rounded-xl border-slate-300" placeholder="¿Qué sucede en este paso?"></textarea>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="procesoForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="procesoForm.activo" type="checkbox" id="proceso-activo" class="rounded-xl" />
                                <label for="proceso-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showProcesoModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="procesoForm.processing" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
                                Guardar Paso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal Oferta -->
        <Teleport to="body">
            <div v-if="showOfertaModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50" @click.self="showOfertaModal = false">
                <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-lg font-bold mb-4">{{ editingOferta ? 'Editar' : 'Nueva' }} Oferta</h3>
                    <form @submit.prevent="saveOferta" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Título del Badge</label>
                                <input v-model="ofertaForm.titulo" type="text" class="w-full rounded-xl border-slate-300" required placeholder="OFERTA ESPECIAL" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Producto/Servicio</label>
                                <input v-model="ofertaForm.subtitulo" type="text" class="w-full rounded-xl border-slate-300" required placeholder="Instalación de Minisplit Inverter" />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Precio Original ($)</label>
                                <input v-model="ofertaForm.precio_original" type="number" step="0.01" min="0" class="w-full rounded-xl border-slate-300" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Descuento (%)</label>
                                <input v-model="ofertaForm.descuento_porcentaje" type="number" min="1" max="99" class="w-full rounded-xl border-slate-300" required />
                            </div>
                        </div>
                        
                        <div v-if="ofertaForm.precio_original > 0" class="bg-gradient-to-r from-emerald-50 to-emerald-50 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/30">
                            <p class="text-sm text-slate-500">Vista previa del precio:</p>
                            <div class="flex items-center gap-4 mt-1">
                                <span class="text-slate-400 line-through">{{ formatPrice(ofertaForm.precio_original) }}</span>
                                <span class="text-2xl font-black text-emerald-600">{{ formatPrice(ofertaForm.precio_original * (1 - ofertaForm.descuento_porcentaje / 100)) }}</span>
                                <span class="px-2 py-1 bg-brand-500 text-white text-sm font-bold rounded-xl">-{{ ofertaForm.descuento_porcentaje }}%</span>
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Características incluidas (hasta 3)</label>
                            <div class="space-y-2">
                                <input v-model="ofertaForm.caracteristica_1" type="text" class="w-full rounded-xl border-slate-300" placeholder="Ej: Instalación profesional incluida" />
                                <input v-model="ofertaForm.caracteristica_2" type="text" class="w-full rounded-xl border-slate-300" placeholder="Ej: Garantía extendida de 3 años" />
                                <input v-model="ofertaForm.caracteristica_3" type="text" class="w-full rounded-xl border-slate-300" placeholder="Ej: Soporte técnico 24/7" />
                            </div>
                        </div>
                        
                        <div class="border-t pt-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Vigencia de la oferta (opcional)</label>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Fecha/Hora de inicio</label>
                                    <input v-model="ofertaForm.fecha_inicio" type="datetime-local" class="w-full rounded-xl border-slate-300" />
                                </div>
                                <div>
                                    <label class="block text-xs text-slate-500 mb-1">Fecha/Hora de fin (countdown)</label>
                                    <input v-model="ofertaForm.fecha_fin" type="datetime-local" class="w-full rounded-xl border-slate-300" />
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-2">Si no defines fecha de fin, el countdown mostrará 24 horas rotativas.</p>
                        </div>
                        
                        <div class="flex gap-4 border-t pt-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-slate-700 mb-1">Orden</label>
                                <input v-model="ofertaForm.orden" type="number" class="w-full rounded-xl border-slate-300" />
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input v-model="ofertaForm.activo" type="checkbox" id="oferta-activo" class="rounded-xl text-orange-500" />
                                <label for="oferta-activo" class="text-sm">Activo</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-2 pt-4">
                            <button type="button" @click="showOfertaModal = false" class="px-4 py-2 text-slate-500 hover:bg-slate-100 rounded-xl">Cancelar</button>
                            <button type="submit" :disabled="ofertaForm.processing" class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 disabled:opacity-50">
                                Guardar Oferta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}
</style>
