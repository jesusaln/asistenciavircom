<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import WhatsAppWidget from '@/Components/WhatsAppWidget.vue';

const props = defineProps({
    cliente: Object,
    telefono: String,
    empresa: Object,
});

const page = usePage();

const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const cssVars = computed(() => ({
    '--color-primary': empresaData.value.color_principal || '#FF6B35',
    '--color-primary-soft': (empresaData.value.color_primary || '#FF6B35') + '15',
    '--color-primary-dark': (empresaData.value.color_primary || '#FF6B35') + 'dd',
    '--color-secondary': empresaData.value.color_secundario || '#D97706',
    '--color-terciary': empresaData.value.color_terciario || '#B45309',
    '--color-terciary-soft': (empresaData.value.color_terciario || '#B45309') + '15',
}));

const form = useForm({
    nombre: '',
    rfc: '',
    regimen_fiscal: '',
    uso_cfdi: 'G03',
    domicilio_fiscal_cp: '',
    email: '',
    telefono: '',
    ticket_folio: '',
    mensaje: '',
});

onMounted(() => {
    if (props.cliente) {
        form.nombre = props.cliente.razon_social || props.cliente.nombre_razon_social || '';
        form.rfc = props.cliente.rfc || '';
        form.regimen_fiscal = props.cliente.regimen_fiscal || '';
        form.uso_cfdi = props.cliente.uso_cfdi || 'G03';
        form.domicilio_fiscal_cp = props.cliente.domicilio_fiscal_cp || '';
        form.email = props.cliente.email || '';
        form.telefono = props.cliente.telefono || props.telefono || '';
    } else {
        form.telefono = props.telefono || '';
    }
});

const submit = () => {
    form.post(route('public.facturar.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.ticket_folio = '';
            form.mensaje = '';
        },
    });
};

const regimenes = [
    { clave: '601', desc: '601 - General de Ley Personas Morales' },
    { clave: '603', desc: '603 - Personas Morales con Fines no Lucrativos' },
    { clave: '605', desc: '605 - Sueldos y Salarios e Ingresos Asimilados a Salarios' },
    { clave: '606', desc: '606 - Arrendamiento' },
    { clave: '612', desc: '612 - Personas Físicas con Actividades Empresariales y Profesionales' },
    { clave: '621', desc: '621 - Incorporación Fiscal' },
    { clave: '625', desc: '625 - Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas' },
    { clave: '626', desc: '626 - Régimen Simplificado de Confianza (RESICO)' },
];

const usosCfdi = [
    { clave: 'G01', desc: 'G01 - Adquisición de mercancías' },
    { clave: 'G03', desc: 'G03 - Gastos en general' },
    { clave: 'I01', desc: 'I01 - Construcciones' },
    { clave: 'I02', desc: 'I02 - Mobiliario y equipo de oficina por inversiones' },
    { clave: 'I04', desc: 'I04 - Equipo de transporte' },
    { clave: 'I08', desc: 'I08 - Otra maquinaria y equipo' },
    { clave: 'D01', desc: 'D01 - Honorarios médicos, dentales y gastos hospitalarios' },
    { clave: 'D02', desc: 'D02 - Gastos médicos por incapacidad o discapacidad' },
    { clave: 'S01', desc: 'S01 - Sin efectos fiscales' },
];
</script>

<template>
    <Head :title="`Solicitar Factura - ${empresaData?.nombre || 'Climas del Desierto'}`">
        <meta name="description" content="Solicita tu factura electrónica CFDI 4.0 de forma rápida y sencilla llenando tus datos fiscales." />
    </Head>

    <div class="min-h-screen bg-[var(--ui-surface)] flex flex-col font-sans transition-colors duration-200" :style="cssVars">
        <WhatsAppWidget :whatsapp="empresaData?.whatsapp" :empresaNombre="empresaData?.nombre" />
        <PublicNavbar :empresa="empresaData" />

        <main class="flex-grow">
            <!-- Hero Section -->
            <section class="relative py-16 bg-slate-900 text-white overflow-hidden">
                <div class="absolute inset-0">
                    <img
                        src="/storage/servicios/contacto-hero.webp"
                        alt="Facturación"
                        class="w-full h-full object-cover opacity-10"
                    >
                    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/90 via-slate-900/80 to-slate-900"></div>
                </div>

                <div class="absolute -top-24 -right-24 w-64 h-64 bg-[var(--color-primary)] rounded-full blur-[120px] opacity-15"></div>

                <div class="w-full px-4 relative z-10 text-center">
                    <h1 class="text-3xl md:text-5xl font-black mb-3 tracking-tight">
                        Solicitar <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Factura Electrónica</span>
                    </h1>
                    <p class="text-base text-slate-300 max-w-2xl mx-auto font-medium">
                        Genera tu comprobante CFDI 4.0. Confirma tus datos fiscales y nosotros nos encargamos del resto.
                    </p>
                </div>
            </section>

            <!-- Form Section -->
            <section class="py-12 -mt-6 relative z-20">
                <div class="w-full px-4 max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-slate-800 p-8 md:p-12 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800">
                        
                        <!-- Notificación de Éxito / Error general -->
                        <div v-if="page.props.flash?.success" class="mb-6 p-5 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/30 rounded-2xl flex items-start gap-4">
                            <span class="text-emerald-500 text-xl">✅</span>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">¡Solicitud Procesada!</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ page.props.flash.success }}</p>
                            </div>
                        </div>

                        <!-- Banner Prefilado si el cliente existe -->
                        <div v-if="cliente" class="mb-8 p-5 bg-blue-50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800/30 rounded-2xl flex items-center gap-4">
                            <span class="text-blue-500 text-2xl">✨</span>
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">¡Número Reconocido!</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Hemos rellenado automáticamente tus datos fiscales registrados.</p>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Datos del Ticket -->
                            <div class="bg-slate-50 dark:bg-slate-800/40 p-6 rounded-2xl border border-slate-200/60 dark:border-slate-700/50 space-y-4">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-2">Información del Ticket o Servicio</h3>
                                
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Folio del Ticket / Venta *</label>
                                        <input v-model="form.ticket_folio" type="text" required placeholder="Ej. T0123" class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        <div v-if="form.errors.ticket_folio" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.ticket_folio }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Teléfono de Contacto (10 dígitos) *</label>
                                        <input v-model="form.telefono" type="tel" required placeholder="6621234567" maxlength="10" class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        <div v-if="form.errors.telefono" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.telefono }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Fiscales -->
                            <div class="space-y-6">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white border-b border-slate-200 dark:border-slate-700 pb-2">Datos de Facturación (CFDI 4.0)</h3>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Razón Social o Nombre Completo *</label>
                                        <input v-model="form.nombre" @input="form.nombre = form.nombre.toUpperCase()" type="text" required placeholder="Ej. JUAN PEREZ o CLIMAS S.A. DE C.V." class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm uppercase" />
                                        <div v-if="form.errors.nombre" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.nombre }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">RFC *</label>
                                        <input v-model="form.rfc" @input="form.rfc = form.rfc.toUpperCase()" type="text" required placeholder="Ej. XAXX010101000" maxlength="13" class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm uppercase" />
                                        <div v-if="form.errors.rfc" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.rfc }}</div>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Régimen Fiscal (SAT) *</label>
                                        <select v-model="form.regimen_fiscal" required class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm text-slate-700 dark:text-slate-300">
                                            <option value="" disabled>Selecciona tu régimen...</option>
                                            <option v-for="r in regimenes" :key="r.clave" :value="r.clave">{{ r.desc }}</option>
                                        </select>
                                        <div v-if="form.errors.regimen_fiscal" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.regimen_fiscal }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Uso de CFDI *</label>
                                        <select v-model="form.uso_cfdi" required class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm text-slate-700 dark:text-slate-300">
                                            <option v-for="u in usosCfdi" :key="u.clave" :value="u.clave">{{ u.desc }}</option>
                                        </select>
                                        <div v-if="form.errors.uso_cfdi" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.uso_cfdi }}</div>
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Código Postal (Domicilio Fiscal) *</label>
                                        <input v-model="form.domicilio_fiscal_cp" type="text" required placeholder="Ej. 83000" maxlength="5" class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        <div v-if="form.errors.domicilio_fiscal_cp" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.domicilio_fiscal_cp }}</div>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Correo Electrónico (donde enviaremos el PDF/XML) *</label>
                                        <input v-model="form.email" type="email" required placeholder="correo@ejemplo.com" class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm" />
                                        <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.email }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensaje adicional -->
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 dark:text-slate-400 ml-1">Notas o Comentarios Adicionales (Opcional)</label>
                                <textarea v-model="form.mensaje" rows="3" placeholder="Ej. Favor de facturar con fecha del mes en curso..." class="w-full px-5 py-3 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-[var(--color-primary)] focus:border-transparent transition-all text-sm resize-none"></textarea>
                                <div v-if="form.errors.mensaje" class="text-rose-500 text-xs mt-1 ml-1">{{ form.errors.mensaje }}</div>
                            </div>

                            <!-- Botón Enviar -->
                            <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-bold text-sm uppercase tracking-wider shadow-lg hover:shadow-xl hover:bg-[var(--color-primary-dark)] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                                <span v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                {{ form.processing ? 'Enviando Solicitud...' : 'Solicitar Factura' }}
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <PublicFooter :empresa="empresaData" />
    </div>
</template>
