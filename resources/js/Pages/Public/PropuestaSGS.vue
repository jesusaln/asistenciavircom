<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { library } from '@fortawesome/fontawesome-svg-core';
import { 
    faFileSignature, faUserShield, faChartLine, faLeaf, faToolbox,
    faCreditCard, faCalendarCheck, faShieldHalved, faBolt, faTruckFast,
    faMoneyBillWave, faLocationDot, faPhone, faEnvelope, faListCheck,
    faCircleCheck, faCircleXmark, faCheck, faTimes, faHandshake,
    faTruckMedical, faPrint, faWrench, faWind, faClipboardCheck, faTools,
    faCalendarDays, faSun, faSnowflake, faPlugCircleBolt, faPiggyBank,
    faBan, faGavel, faClock, faFileContract, faAward, faUsers, faBuilding,
    faCircleQuestion, faChevronDown
} from '@fortawesome/free-solid-svg-icons';

library.add(
    faFileSignature, faUserShield, faChartLine, faLeaf, faToolbox,
    faCreditCard, faCalendarCheck, faShieldHalved, faBolt, faTruckFast,
    faMoneyBillWave, faLocationDot, faPhone, faEnvelope, faListCheck,
    faCircleCheck, faCircleXmark, faCheck, faTimes, faHandshake,
    faTruckMedical, faPrint, faWrench, faWind, faClipboardCheck, faTools,
    faCalendarDays, faSun, faSnowflake, faPlugCircleBolt, faPiggyBank,
    faBan, faGavel, faClock, faFileContract, faAward, faUsers, faBuilding,
    faCircleQuestion, faChevronDown
);

const props = defineProps({
    empresa: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
    '--color-primary-soft': (props.empresa?.color_principal || '#FF6B35') + '15',
    '--color-primary-dark': (props.empresa?.color_principal || '#FF6B35') + 'dd',
    '--color-secondary': props.empresa?.color_secundario || '#1E40AF',
}));

const equipos = [
    { area: 'Lab. Preparación', equipo: 'Aerocooler Maxxicooler M-180', cap: '-', precio: 5940 },
    { area: 'Site', equipo: 'Minisplit Mirage', cap: '1.5 Ton', precio: 1500 },
    { area: 'Comedor', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Recepción', equipo: 'Minisplit Midea', cap: '2 Ton', precio: 1500 },
    { area: 'Sala de Juntas', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Supervisión', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Jefatura', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'H&S', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Lockers', equipo: 'Minisplit Raheem', cap: '2 Ton', precio: 1500 },
    { area: 'Vía Húmeda', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Área de Balanzas', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Balanzas', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Gravimetría', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Almacén EF', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Absorción Atómica', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
    { area: 'Oficinas EF', equipo: 'Minisplit Mirage', cap: '2 Ton', precio: 1500 },
];

const subtotal = 28440.00;
const iva = 4550.40;
const isr = 355.50;
const totalNeto = 32634.90;

const formatter = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
});

const incluyeMantenimiento = [
    'Lavado a presión de evaporador con hidrolavadora profunda.',
    'Lavado a fondo de unidad condensadora exterior.',
    'Limpieza de charola y manguera de desagüe (antiobstrucción).',
    'Desinfección de serpentín con químicos biodegradables.',
    'Limpieza de turbina, filtros y cubiertas plásticas.',
    'Revisión de presiones de gas refrigerante.',
    'Medición de consumo de amperaje y limpieza de terminales de voltaje.',
    'Reporte técnico detallado post-servicio.'
];

const noIncluyeMantenimiento = [
    'Recarga de gas refrigerante (en caso de detectar baja presión).',
    'Reparaciones mecánicas o eléctricas por fallas existentes.',
    'Cambio de piezas dañadas (capacitores, sensores, motores).',
    'Corrección de fugas de gas en tuberías o componentes del equipo.',
    'Servicios en alturas superiores a un primer piso sin acceso seguro.'
];

const valorAgregado = [
    { icono: 'file-signature', titulo: 'Documentación Corporativa', desc: 'Reporte técnico detallado con hojas de vida por equipo y evidencia fotográfica del Antes y Después.' },
    { icono: 'user-shield', titulo: 'Atención Prioritaria VIP', desc: 'Asignación prioritaria en temporada alta (verano), saltando los tiempos normales de espera.' },
    { icono: 'chart-line', titulo: 'Optimización Energética', desc: 'Monitoreo de voltajes y amperajes para prevenir fallas mayores y reducir el gasto eléctrico.' },
    { icono: 'leaf', titulo: 'Sostenibilidad', desc: 'Uso exclusivo de serpentines y químicos biodegradables certificados, cuidando la salud en sus laboratorios.' },
    { icono: 'user-shield', titulo: 'Portal de Cliente', desc: 'Se le proporciona un usuario y contraseña para acceder a la plataforma web donde podrá consultar la bitácora de vida de cada equipo: historial de servicios, reportes fotográficos y estado en tiempo real.' },
    { icono: 'award', titulo: 'Centro de Servicio Mirage Autorizado', desc: 'Somos centro de servicio autorizado por Mirage, lo que garantiza que nuestros técnicos están certificados de fábrica y utilizamos refacciones originales con respaldo de garantía del fabricante.' },
];

const terminos = [
    { icono: 'toolbox', titulo: 'Refacciones', desc: 'Las refacciones y materiales adicionales no están incluidos.' },
    { icono: 'credit-card', titulo: 'Forma de Pago', desc: '100% del total para iniciar con la póliza. El pago completo es requerido antes del primer servicio programado.' },
    { icono: 'calendar-check', titulo: 'Vigencia', desc: 'Póliza vigente durante 12 meses a partir de la firma.' },
    { icono: 'shield-halved', titulo: 'Garantía', desc: 'Garantía de 3 meses sobre mano de obra en servicios correctivos realizados por CDD.' },
];

const emergencias = [
    { icono: 'bolt', titulo: 'Diagnóstico Incluido', desc: 'Su póliza incluye 1 diagnóstico de emergencia en sitio sin costo adicional, con respuesta en máximo 72 hrs hábiles. Las reparaciones derivadas se cotizan por separado.' },
    { icono: 'truck-fast', titulo: 'Visitas Adicionales', desc: 'Las 2 rondas preventivas son las únicas visitas incluidas. Cualquier visita adicional de revisión tiene un costo de diagnóstico de $650.00 MXN neto por equipo revisado.' },
    { icono: 'money-bill-wave', titulo: 'Viáticos Foráneos', desc: 'Viáticos: $5,000 MXN neto por evento. Solo aplica para servicios fuera de Hermosillo (incluye traslado, hospedaje y logística técnica).' }
];

const metodologia = [
    { paso: '01', icono: 'wrench', titulo: 'Desarmado', desc: 'Retiramos cubiertas, filtros y componentes para acceder a las áreas críticas del equipo.' },
    { paso: '02', icono: 'wind', titulo: 'Lavado Profundo', desc: 'Hidrolavadora + químicos biodegradables en serpentín, turbina y condensadora.' },
    { paso: '03', icono: 'shield-halved', titulo: 'Desinfección', desc: 'Bactericida y fungicida profesional libre de patógenos para entornos de laboratorio.' },
    { paso: '04', icono: 'clipboard-check', titulo: 'Reporte Técnico', desc: 'Verificación de presiones, amperaje, voltaje y documentación fotográfica Antes/Después.' },
];

const calendario = [
    { mes: 'Abril – Mayo', icono: 'sun', desc: 'Ronda 1: Mantenimiento preventivo antes de temporada de calor.', tag: 'Urgente' },
    { mes: 'Octubre – Noviembre', icono: 'snowflake', desc: 'Ronda 2: Mantenimiento preventivo antes de temporada de frío.', tag: 'Programado' },
];

const ahorros = [
    { concepto: 'Ahorro en recibo de luz (equipos limpios)', valor: '~30% menos consumo', icono: 'plug-circle-bolt' },
    { concepto: 'Prevención de reparaciones mayores', valor: 'Hasta $15,000/año', icono: 'piggy-bank' },
    { concepto: 'Extensión de vida útil del equipo', valor: '+3 a 5 años', icono: 'calendar-days' },
];

const terminosExtendidos = [
    { icono: 'ban', titulo: 'Cancelación', desc: 'La póliza puede cancelarse con 30 días naturales de anticipación por escrito. No se realizan reembolsos proporcionales de servicios ya ejecutados.' },
    { icono: 'gavel', titulo: 'Responsabilidad', desc: 'CDD no se hace responsable por daños preexistentes, fallas por mala instalación de terceros, o daños por variaciones eléctricas (picos de voltaje, apagones). Se recomienda uso de regulador de voltaje.' },
    { icono: 'clock', titulo: 'Programación', desc: 'Las visitas se agendan con mínimo 5 días hábiles de anticipación. El cliente debe asegurar acceso libre a todos los equipos el día del servicio.' },
    { icono: 'file-contract', titulo: 'Aceptación', desc: 'La firma de esta propuesta o el pago del anticipo constituyen la aceptación total de los términos y condiciones aquí descritos.' },
    { icono: 'bolt', titulo: 'Fuerza Mayor', desc: 'CDD queda exento de responsabilidad ante desastres naturales, contingencias sanitarias, pandemias o cualquier evento fuera de su control que impida la ejecución del servicio.' },
    { icono: 'chart-line', titulo: 'Ajuste de Precios', desc: 'Los precios de refacciones y gas refrigerante están sujetos a variaciones del mercado. Cualquier material adicional se cotizará por separado al precio vigente del momento.' },
    { icono: 'list-check', titulo: 'Alcance del Servicio', desc: 'Esta póliza cubre exclusivamente los 16 equipos listados en esta propuesta. Equipos adicionales requerirán una cotización complementaria.' },
    { icono: 'shield-halved', titulo: 'Equipos Obsoletos', desc: 'Si durante la inspección se detecta un equipo con vida útil agotada o daño irreparable, CDD emitirá un dictamen técnico sin responsabilidad sobre su funcionamiento posterior.' },
    { icono: 'handshake', titulo: 'Confidencialidad', desc: 'Ambas partes se comprometen a mantener la confidencialidad de los términos comerciales, precios y cualquier información técnica compartida durante la vigencia de la póliza.' },
    { icono: 'location-dot', titulo: 'Jurisdicción', desc: 'Para cualquier controversia derivada de esta propuesta, ambas partes se someten a los tribunales competentes de la ciudad de Hermosillo, Sonora.' },
];

const stats = [
    { valor: '+14', label: 'Años de Experiencia', icono: 'award' },
    { valor: '+1,500', label: 'Equipos Atendidos', icono: 'snowflake' },
    { valor: '98%', label: 'Clientes Satisfechos', icono: 'users' },
    { valor: '+50', label: 'Pólizas Corporativas', icono: 'building' },
];

const faqs = [
    { pregunta: '¿Cuántos servicios incluye la póliza?', respuesta: 'La póliza incluye 2 visitas preventivas al año para cada uno de los 16 equipos listados. Esto equivale a 32 servicios de mantenimiento preventivo en total (16 equipos × 2 rondas). La primera ronda se programa en Abril-Mayo (antes de temporada de calor) y la segunda en Octubre-Noviembre (antes de temporada de frío).' },
    { pregunta: '¿Las visitas adicionales tienen costo?', respuesta: 'Sí. Cualquier llamada de servicio fuera de los 32 previstos se factura como visita de diagnóstico con un costo preferencial de $650.00 MXN neto por equipo.' },
    { pregunta: '¿Los diagnósticos de emergencia son gratis?', respuesta: 'Sí, durante la vigencia de la póliza se incluye 1 diagnóstico de emergencia en sitio sin costo, con un tiempo de respuesta máximo de 72 horas hábiles. Las reparaciones derivadas del diagnóstico (refacciones, mano de obra correctiva, recarga de gas) se cotizan y facturan por separado.' },
    { pregunta: '¿Qué pasa si mi equipo necesita gas durante el mantenimiento?', respuesta: 'Un minisplit es un sistema sellado. Si necesita gas es porque tiene una fuga. Le informamos y cotizamos la recarga y/o reparación por separado. No está incluido en la póliza porque la fuga es una falla preexistente del equipo, no resultado del mantenimiento.' },
    { pregunta: '¿Qué sucede si un equipo falla fuera del horario de atención?', respuesta: 'Puede reportar la falla por WhatsApp o correo electrónico en cualquier momento. La visita de diagnóstico se agenda dentro del siguiente día hábil según disponibilidad, con un plazo máximo de 72 horas hábiles.' },
    { pregunta: '¿Pueden facturar a nombre de SGS México directamente?', respuesta: 'Sí. Emitimos CFDI 4.0 con los datos fiscales que nos proporcionen. Como RESICO aplicamos la retención de ISR correspondiente (1.25%) que se descuenta directamente en la factura.' },
    { pregunta: '¿Qué pasa si agregamos más equipos durante la vigencia?', respuesta: 'Se realiza una cotización complementaria proporcional al tiempo restante de la póliza. Los equipos adicionales se anexan al contrato existente sin afectar los términos de los 16 equipos originales.' },
    { pregunta: '¿Qué pasa si un equipo ya no tiene reparación?', respuesta: 'Si durante el mantenimiento preventivo detectamos que un equipo tiene vida útil agotada o daño irreparable, emitimos un dictamen técnico por escrito. CDD no se hace responsable del funcionamiento posterior de equipos dictaminados como obsoletos, pero le asesoramos gratuitamente en la selección del equipo sustituto.' },
    { pregunta: '¿Se pueden agendar mantenimientos en horario nocturno o fines de semana?', respuesta: 'Nuestro horario operativo incluido en la póliza es de Lunes a Viernes, y Sábados de 9:00 AM a 5:00 PM. Los trabajos que por logística del laboratorio requieran realizarse en horario nocturno o en Domingo generarán un cargo extra por concepto de tarifa de horario extraordinario.' },
    { pregunta: '¿Su personal cumple con normativas de seguridad y registro REPSE?', respuesta: 'Absolutamente. Climas del Desierto cuenta con registro REPSE frente a la STPS. Todos nuestros técnicos ingresan con IMSS (SUA) vigente, constancias de habilidades DC-3 (para trabajos en alturas) y portan permanentemente su Equipo de Protección Personal (EPP) completo.' },
];

const activeFaq = ref(null);
const toggleFaq = (id) => { activeFaq.value = activeFaq.value === id ? null : id; };

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head title="Póliza Integral de Mantenimiento Preventivo HVAC – SGS México" />

    <div :style="cssVars" class="min-h-screen bg-[var(--ui-surface)] flex flex-col font-sans text-slate-800 dark:text-slate-200 printable-wrapper transition-colors">
        <!-- NO SE IMPRIME -->
        <div class="no-print">
            <PublicNavbar :empresa="empresa" activeTab="none" />
        </div>

        <main class="flex-grow">
            <!-- HEADER VIP -->
            <section class="relative bg-slate-900 py-16 md:py-24 overflow-hidden print-header">
                <!-- Background decorations -->
                <div class="absolute inset-0 z-0 no-print">
                    <img src="/storage/servicios/mantenimiento-preventivo-hero.webp" alt="Protección Integral" class="w-full h-full object-cover blur-sm opacity-20 scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-900/50"></div>
                </div>

                <div class="relative z-10 max-w-6xl mx-auto px-4 flex flex-col items-start justify-center text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-md rounded-full border border-white/20 mb-6 no-print shadow-xl">
                        <span class="w-2 h-2 rounded-full bg-brand-400 animate-pulse shadow-[0_0_10px_rgba(251,191,36,0.8)]"></span>
                        <span class="text-brand-400 text-xs font-bold uppercase tracking-wide">Póliza Integral HVAC – Nivel Laboratorio</span>
                    </div>
                    <h1 class="text-4xl md:text-6xl font-black text-white mb-6 tracking-tight leading-tight">
                        Mantenimiento <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Preventivo VIP</span>
                    </h1>
                    <p class="text-slate-300 text-lg md:text-2xl font-medium max-w-2xl leading-relaxed">
                        A la medida de <span class="text-white font-black">SGS México</span>. Respaldamos su operación garantizando el óptimo rendimiento de sus equipos.
                    </p>
                </div>
            </section>

            <div class="max-w-6xl mx-auto px-4 py-12">
                <!-- A LA ATENCIÓN DE -->
                <div class="flex flex-col md:flex-row gap-8 mb-12 print-page-break">
                    <div class="flex-1 bg-white dark:bg-slate-800 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none bg-gradient-to-br from-white to-slate-50/50 dark:from-slate-800 dark:to-slate-900/50">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.2em] mb-4 block print-text-dark">A quien corresponda:</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2 print-text-black">Diego Mena</h2>
                        <p class="text-slate-500 dark:text-slate-400 font-bold mb-4 print-text-gray">Departamento de Compras • SGS México</p>
                        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mt-4 print-text-gray">
                            <font-awesome-icon icon="location-dot" class="text-emerald-500" />
                            <span>Volcán 150, Piso 5, Col. Lomas de Chapultepec, CDMX.</span>
                        </div>
                    </div>

                    <div class="flex-1 bg-white dark:bg-slate-800 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.2em] mb-4 block print-text-dark">Proveedor Asignado:</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2 print-text-black">Jesús Alberto López</h2>
                        <p class="text-slate-500 dark:text-slate-400 font-bold mb-4 print-text-gray">Climas del Desierto - Hermosillo</p>
                        
                        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 print-text-gray">
                            <font-awesome-icon icon="phone" class="text-[var(--color-primary)]" />
                            <span>662 460 6840</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mt-2 print-text-gray">
                            <font-awesome-icon icon="envelope" class="text-[var(--color-primary)]" />
                            <span>contacto@climasdeldesierto.com</span>
                        </div>
                    </div>
                </div>

                <!-- VALOR AGREGADO (PREMIUM) -->
                <div class="mb-12 print-page-break">
                    <div class="text-center mb-8">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-2 block">Ventaja Competitiva</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white print-text-black">Por qué elegir a CDD</h2>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6">
                        <div v-for="(valor, i) in valorAgregado" :key="i" class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-xl shadow-slate-200/50 dark:shadow-none hover:shadow-xl hover:shadow-xl transition-transform duration-200 print-box text-center">
                            <div class="w-14 h-14 mx-auto bg-[var(--ui-surface)] rounded-full flex items-center justify-center text-2xl text-[var(--color-primary)] mb-4 border border-slate-100 dark:border-slate-800">
                                <font-awesome-icon :icon="valor.icono" />
                            </div>
                            <h3 class="font-black text-slate-900 dark:text-white mb-2 text-[15px] print-text-black">{{ valor.titulo }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed print-text-gray">{{ valor.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- DESGLOSE DE PRECIOS -->
                <div class="mb-12 print-page-break">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-10 h-10 bg-[var(--color-primary-soft)] rounded-xl flex items-center justify-center text-[var(--color-primary)]">
                            <font-awesome-icon icon="list-check" />
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white print-text-black">Lista de Equipos y Cobertura</h3>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-black/50 border-b border-slate-100 dark:border-slate-700">
                                        <th class="py-4 px-6 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide print-text-dark">Área</th>
                                        <th class="py-4 px-6 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide print-text-dark">Equipo</th>
                                        <th class="py-4 px-6 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-center print-text-dark">Capacidad</th>
                                        <th class="py-4 px-6 text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide text-right print-text-dark">Anualidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(eq, i) in equipos" :key="i" class="border-b border-slate-50 dark:border-slate-700/50 hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                        <td class="py-4 px-6 font-bold text-slate-800 dark:text-slate-200 text-sm print-text-black">{{ eq.area }}</td>
                                        <td class="py-4 px-6 text-slate-500 dark:text-slate-400 text-sm print-text-gray">{{ eq.equipo }}</td>
                                        <td class="py-4 px-6 text-slate-500 dark:text-slate-500 text-sm text-center font-medium print-text-gray">{{ eq.cap }}</td>
                                        <td class="py-4 px-6 font-mono font-bold text-slate-900 dark:text-white text-right print-text-black">{{ formatter.format(eq.precio) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- TOTALES (RESICO) -->
                        <div class="bg-slate-900 text-white p-6 md:p-8 flex flex-col md:flex-row justify-end items-end gap-6 md:gap-16 print-invoice-footer">
                            <div class="flex flex-col gap-2 min-w-[250px]">
                                <div class="flex justify-between items-center text-slate-400 text-sm font-bold">
                                    <span>Subtotal</span>
                                    <span class="font-mono text-white">{{ formatter.format(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-slate-400 text-sm font-bold border-b border-slate-700 pb-2">
                                    <span>IVA (16%)</span>
                                    <span class="font-mono text-white">{{ formatter.format(iva) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-rose-400 text-sm font-bold border-b border-slate-700 pb-2">
                                    <span>Retención ISR (1.25%)</span>
                                    <span class="font-mono text-rose-400">-{{ formatter.format(isr) }}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-white font-black uppercase tracking-wide text-sm">Total Neto</span>
                                    <span class="font-mono text-2xl font-black text-amber-400">{{ formatter.format(totalNeto) }}</span>
                                </div>
                                <p class="text-xs text-slate-500 text-right mt-1 w-full uppercase tracking-wider font-bold">* Cotización bajo Régimen RESICO</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DETALLES DEL SERVICIO: SÍ O NO -->
                <div class="grid lg:grid-cols-2 gap-8 mb-12 print-page-break">
                    <!-- SÍ INCLUYE -->
                    <div class="bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/10 rounded-[2rem] p-8 border border-emerald-100 dark:border-emerald-900/30 shadow-sm print-box">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-10 h-10 bg-brand-500 rounded-xl flex items-center justify-center text-2xl shadow-xl shadow-emerald-500/20 text-white">
                                <font-awesome-icon icon="circle-check" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight print-text-black">Sí Incluye</h2>
                                <p class="text-emerald-600 dark:text-slate-400 font-bold uppercase tracking-wide text-[10px] print-text-dark">Rutina Preventiva</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li v-for="(item, i) in incluyeMantenimiento" :key="i" class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-200 print-text-gray">
                                <font-awesome-icon icon="check" class="text-emerald-500 mt-1" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- NO INCLUYE -->
                    <div class="bg-rose-50 dark:bg-rose-900/20/50 dark:bg-rose-900/10 rounded-[2rem] p-8 border border-rose-100 dark:border-rose-900/30 shadow-sm print-box">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-10 h-10 bg-brand-500 rounded-xl flex items-center justify-center text-2xl shadow-xl shadow-rose-500/20 text-white">
                                <font-awesome-icon icon="circle-xmark" />
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight print-text-black">No Incluye</h2>
                                <p class="text-rose-600 dark:text-rose-400 font-bold uppercase tracking-wide text-[10px] print-text-dark">Cargos Adicionales / Exclusiones</p>
                            </div>
                        </div>
                        <ul class="space-y-3">
                            <li v-for="(item, i) in noIncluyeMantenimiento" :key="i" class="flex items-start gap-3 text-sm text-slate-700 dark:text-slate-200 print-text-gray">
                                <font-awesome-icon icon="times" class="text-rose-500 mt-1 px-0.5" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- METODOLOGÍA DE SERVICIO -->
                <div class="mb-12 print-page-break">
                    <div class="text-center mb-8">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-2 block">Proceso Certificado</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Metodología de Servicio</h2>
                    </div>
                    <div class="grid md:grid-cols-4 gap-6">
                        <div v-for="(step, i) in metodologia" :key="i" class="relative bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm text-center hover:shadow-xl hover:shadow-xl transition-transform">
                            <span class="absolute top-4 right-5 text-2xl font-black text-slate-100 dark:text-slate-700 select-none">{{ step.paso }}</span>
                            <div class="w-10 h-10 mx-auto bg-[var(--color-primary-soft)] rounded-full flex items-center justify-center text-xl text-[var(--color-primary)] mb-4">
                                <font-awesome-icon :icon="step.icono" />
                            </div>
                            <h3 class="font-black text-slate-900 dark:text-white mb-2 text-sm">{{ step.titulo }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ step.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- CALENDARIO ANUAL -->
                <div class="mb-12 print-section-calendario">
                    <div class="text-center mb-8">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-2 block">Planificación</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Calendario de Visitas</h2>
                        <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm">2 visitas preventivas al año, estratégicamente programadas antes de cada temporada crítica.</p>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div v-for="(cal, i) in calendario" :key="i" class="bg-white dark:bg-slate-800 p-6 rounded-[2rem] border border-slate-100 dark:border-slate-700 shadow-sm flex items-start gap-5">
                            <div class="w-14 h-14 shrink-0 bg-brand-500/15 rounded-xl flex items-center justify-center text-2xl text-brand-500">
                                <font-awesome-icon :icon="cal.icono" />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="font-black text-slate-900 dark:text-white text-lg">{{ cal.mes }}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide" :class="cal.tag === 'Urgente' ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-emerald-100 text-emerald-600 dark:bg-slate-800/30 dark:text-slate-400'">{{ cal.tag }}</span>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ cal.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ANÁLISIS DE AHORRO / ROI -->
                <div class="mb-12 print-section-roi">
                    <div class="text-center mb-8">
                        <span class="text-emerald-500 text-xs font-black uppercase tracking-[0.3em] mb-2 block">Retorno de Inversión</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Beneficio Económico Estimado</h2>
                    </div>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div v-for="(ahorro, i) in ahorros" :key="i" class="bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/10 p-6 rounded-[2rem] border border-emerald-100 dark:border-emerald-900/30 text-center">
                            <div class="w-10 h-10 mx-auto bg-brand-500/15 rounded-full flex items-center justify-center text-xl text-emerald-500 mb-4">
                                <font-awesome-icon :icon="ahorro.icono" />
                            </div>
                            <h3 class="font-black text-2xl text-emerald-600 dark:text-slate-400 mb-2">{{ ahorro.valor }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">{{ ahorro.concepto }}</p>
                        </div>
                    </div>
                </div>

                <!-- TÉRMINOS Y EMERGENCIAS -->
                <div class="grid md:grid-cols-2 gap-8 mb-12 print-page-break">
                    <!-- TERMINOS -->
                    <div>
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-10 h-10 bg-brand-500/20 rounded-xl flex items-center justify-center text-brand-500">
                                <font-awesome-icon icon="handshake" />
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white print-text-black">Términos Generales</h3>
                        </div>
                        <div class="space-y-6">
                            <div v-for="(term, i) in terminos" :key="i" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm print-box">
                                <div class="flex items-start gap-4">
                                    <div class="text-[var(--color-primary)] mt-1"><font-awesome-icon :icon="term.icono" /></div>
                                    <div>
                                        <h4 class="font-black text-sm uppercase tracking-wider text-slate-900 dark:text-white mb-1 print-text-black">{{ term.titulo }}</h4>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 print-text-gray">{{ term.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EMERGENCIAS -->
                    <div>
                        <div class="flex items-center gap-2 mb-6">
                            <div class="w-10 h-10 bg-brand-500/20 rounded-xl flex items-center justify-center text-rose-500">
                                <font-awesome-icon icon="truck-medical" />
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white print-text-black">Atención de Emergencias</h3>
                        </div>
                        <div class="space-y-6">
                            <div v-for="(em, i) in emergencias" :key="i" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-rose-100 dark:border-rose-900/30 shadow-sm border-l-4 border-l-rose-500 print-box">
                                <div class="flex items-start gap-4">
                                    <div class="text-rose-500 mt-1"><font-awesome-icon :icon="em.icono" /></div>
                                    <div>
                                        <h4 class="font-black text-sm uppercase tracking-wider text-slate-900 dark:text-white mb-1 print-text-black">{{ em.titulo }}</h4>
                                        <p class="text-sm text-slate-500 dark:text-slate-400 print-text-gray">{{ em.desc }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TÉRMINOS EXTENDIDOS / CLÁUSULAS -->
                <div class="mb-12 print-page-break">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="w-10 h-10 bg-slate-200 dark:bg-slate-700 rounded-xl flex items-center justify-center text-slate-500 dark:text-slate-400">
                            <font-awesome-icon icon="gavel" />
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white">Cláusulas Adicionales</h3>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div v-for="(clausula, i) in terminosExtendidos" :key="i" class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="text-slate-400 dark:text-slate-500 mt-1"><font-awesome-icon :icon="clausula.icono" /></div>
                                <div>
                                    <h4 class="font-black text-sm uppercase tracking-wider text-slate-900 dark:text-white mb-1">{{ clausula.titulo }}</h4>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ clausula.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AUTORIZACIÓN (ESPACIOS FIRMAS) -->
                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-300 dark:border-slate-600 p-8 rounded-[2rem] mt-12 print-page-break print-box">
                    <h3 class="text-center font-black text-slate-500 uppercase tracking-[0.3em] mb-12 text-sm print-text-dark">Firmas de Conformidad</h3>
                    <div class="grid grid-cols-2 gap-12 w-full max-w-2xl mx-auto opacity-70">
                        <div class="flex flex-col items-center">
                            <div class="w-full h-px bg-slate-400 dark:bg-slate-600 mb-4"></div>
                            <span class="font-black text-sm text-slate-800 dark:text-white mb-1 print-text-black">Jesús A. López N.</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide print-text-gray">Proveedor - CDD</span>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-full h-px bg-slate-400 dark:bg-slate-600 mb-4"></div>
                            <span class="font-black text-sm text-slate-800 dark:text-white mb-1 print-text-black">Diego Mena</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wide print-text-gray">Compras - SGS</span>
                        </div>
                    </div>
                </div>

                <!-- ESTADÍSTICAS / SOCIAL PROOF -->
                <div class="mb-12 print-page-break">
                    <div class="bg-slate-900 dark:bg-slate-800 rounded-[2rem] p-8 md:p-12">
                        <div class="text-center mb-8">
                            <span class="text-brand-400 text-xs font-black uppercase tracking-[0.3em] mb-2 block">Nuestra Trayectoria</span>
                            <h2 class="text-2xl md:text-2xl font-black text-white">Respaldados por Resultados</h2>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div v-for="(stat, i) in stats" :key="i" class="text-center">
                                <div class="w-10 h-10 mx-auto bg-white/10 rounded-full flex items-center justify-center text-xl text-brand-400 mb-3">
                                    <font-awesome-icon :icon="stat.icono" />
                                </div>
                                <h3 class="text-3xl md:text-4xl font-black text-white mb-1">{{ stat.valor }}</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-wide">{{ stat.label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PREGUNTAS FRECUENTES -->
                <div class="mb-12 print-section-faq">
                    <div class="text-center mb-8">
                        <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-2 block">Dudas Comunes</span>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white">Preguntas Frecuentes</h2>
                    </div>
                    <div class="space-y-3 max-w-4xl mx-auto">
                        <div v-for="(faq, i) in faqs" :key="i" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm">
                            <button @click="toggleFaq(i)" class="w-full px-6 py-5 flex items-center justify-between text-left">
                                <span class="font-black text-slate-900 dark:text-white text-sm pr-4">{{ faq.pregunta }}</span>
                                <span class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 transition-transform duration-200 shrink-0" :class="{'rotate-180 !bg-[var(--color-primary)] !text-white': activeFaq === i}">
                                    <font-awesome-icon icon="chevron-down" class="text-xs" />
                                </span>
                            </button>
                            <div v-show="activeFaq === i" class="px-6 pb-5 print-faq-answer">
                                <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed border-t border-slate-50 dark:border-slate-700 pt-4">{{ faq.respuesta }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA / PRÓXIMOS PASOS -->
                <div class="mb-12 no-print">
                    <div class="bg-gradient-to-br from-[var(--color-primary)] to-brand-500 rounded-[2rem] p-8 md:p-12 text-center text-white shadow-2xl">
                        <h2 class="text-3xl md:text-4xl font-black mb-4">¿Listo para proteger su operación?</h2>
                        <p class="text-white/80 text-lg mb-8 max-w-xl mx-auto">Esta propuesta tiene vigencia de 15 días naturales. Asegure su lugar antes de la temporada de calor.</p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <a href="https://wa.me/526624606840?text=Hola%2C%20me%20interesa%20la%20propuesta%20de%20p%C3%B3liza%20anual%20para%20SGS" target="_blank" class="px-8 py-4 bg-white text-slate-900 rounded-full font-black text-sm uppercase tracking-wide hover:scale-105 transition-transform shadow-xl flex items-center justify-center gap-3">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Contactar por WhatsApp
                            </a>
                            <a href="mailto:contacto@climasdeldesierto.com?subject=Aceptación Propuesta Póliza SGS" class="px-8 py-4 border-2 border-white/30 text-white rounded-full font-black text-sm uppercase tracking-wide hover:bg-white/10 transition-all flex items-center justify-center gap-3">
                                <font-awesome-icon icon="envelope" />
                                Enviar Correo
                            </a>
                        </div>
                        <p class="text-white/50 text-xs mt-6 font-bold uppercase tracking-wide">Propuesta válida hasta el 10 de Abril de 2026</p>
                    </div>
                </div>

            </div>

            <!-- PIE DE PÁGINA — SOLO SE VE EN PDF -->
            <div class="print-only-footer hidden">
                <div class="print-footer-line"></div>
                <div class="print-footer-content">
                    <div>
                        <span class="print-footer-brand">Climas del Desierto</span>
                        <span class="print-footer-tagline">Centro de Servicio Mirage Autorizado</span>
                    </div>
                    <div class="print-footer-info">
                        <span><font-awesome-icon icon="phone" /> 662 460 6840</span>
                        <span><font-awesome-icon icon="envelope" /> contacto@climasdeldesierto.com</span>
                        <span><font-awesome-icon icon="location-dot" /> Hermosillo, Sonora, México</span>
                    </div>
                    <div class="print-footer-legal">
                        climasdeldesierto.com • RFC: LONJ9XXXXX • Documento confidencial – Prohibida su reproducción parcial o total sin autorización.
                    </div>
                </div>
            </div>
        </main>

        <!-- NO SE IMPRIME -->
        <div class="no-print">
            <PublicFooter :empresa="empresa" />
        </div>

        <!-- BOTÓN DE IMPRESIÓN (FLOTANTE) -->
        <button @click="printPage" class="no-print fixed bottom-6 right-6 z-50 bg-[var(--color-primary)] text-white px-6 py-3 rounded-full shadow-2xl flex items-center justify-center gap-3 hover:scale-105 hover:bg-[var(--color-secondary)] transition-all font-bold uppercase tracking-wide text-xs">
            <font-awesome-icon icon="print" class="text-base" />
            Imprimir Propuesta
        </button>
    </div>
</template>

<style scoped>
/* ================================================= */
/*  PDF CORPORATIVO DARK — PREMIUM PRINT STYLES      */
/* ================================================= */

@media print {
    .no-print { display: none !important; }

    /* ═══════════════════════════════════════════════ */
    /*  PAGE SETUP: Full-bleed dark corporate         */
    /* ═══════════════════════════════════════════════ */
    @page {
        margin: 0mm !important;
        size: letter;
    }

    html, body {
        height: 100% !important;
        min-height: 100% !important;
        font-size: 10pt !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background-color: #111827 !important;
        color: #e5e7eb !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-shadow: none !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  LAYOUT WRAPPER                                */
    /* ═══════════════════════════════════════════════ */
    .printable-wrapper {
        background-color: #111827 !important;
        color: #e5e7eb !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        min-height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }

    main {
        padding: 0 !important;
        background-color: #111827 !important;
        flex: 1 1 auto !important;
    }

    .max-w-6xl {
        max-width: 100% !important;
        width: 100% !important;
        padding-left: 1.8cm !important;
        padding-right: 1.8cm !important;
    }

    .py-12 {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  PORTADA (PAGE 1)                              */
    /* ═══════════════════════════════════════════════ */
    .print-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        padding: 3cm 1.8cm 2cm 1.8cm !important;
        margin-bottom: 0 !important;
        border-bottom: 3px solid #FF6B35 !important;
        min-height: 100vh !important;
        display: flex !important;
        align-items: center !important;
    }

    .print-header .relative.z-10 {
        width: 100% !important;
    }

    .print-header h1 {
        color: white !important;
        font-size: 32pt !important;
        line-height: 1.1 !important;
        margin-bottom: 0.8cm !important;
        letter-spacing: -0.02em !important;
    }
    .print-header h1 span {
        background: none !important;
        -webkit-text-fill-color: #FF6B35 !important;
        color: #FF6B35 !important;
    }
    .print-header p {
        color: #94a3b8 !important;
        font-size: 13pt !important;
        line-height: 1.5 !important;
    }
    .print-header p span {
        color: white !important;
    }
    .print-header::after {
        content: 'CLIMAS DEL DESIERTO  •  climasdeldesierto.com  •  662 460 6840';
        display: block;
        position: absolute;
        bottom: 1.5cm;
        right: 1.8cm;
        font-size: 6.5pt;
        color: #475569;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-weight: 700;
    }

    /* ═══════════════════════════════════════════════ */
    /*  PAGE BREAKS                                   */
    /* ═══════════════════════════════════════════════ */
    .print-page-break {
        page-break-before: always !important;
        break-before: page !important;
        padding-top: 1.5cm !important;
    }

    /* Secciones agrupadas (sin page-break propio) */
    .print-section-calendario {
        padding-top: 0.8cm !important;
        break-inside: avoid !important;
    }
    .print-section-roi {
        padding-top: 0.6cm !important;
        break-inside: avoid !important;
    }
    .print-section-faq {
        padding-top: 0.6cm !important;
        break-inside: avoid !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  COLORES DE FONDO Y BORDES                     */
    /* ═══════════════════════════════════════════════ */
    .bg-white, [class*="bg-white"], .bg-slate-50 {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }
    .dark\:bg-slate-800 { background-color: #1e293b !important; }
    .dark\:bg-slate-900, .dark\:bg-slate-950 { background-color: #111827 !important; }
    .border-slate-100, .border-slate-200 { border-color: #334155 !important; }
    .dark\:border-slate-700, .dark\:border-slate-800 { border-color: #334155 !important; }

    .rounded-xl-\[2rem\] { border-radius: 10px !important; }
    .rounded-2xl { border-radius: 8px !important; }

    .p-8 { padding: 0.6cm !important; }
    .p-6 { padding: 0.45cm !important; }
    .p-5 { padding: 0.4cm !important; }

    /* ═══════════════════════════════════════════════ */
    /*  TIPOGRAFÍA                                    */
    /* ═══════════════════════════════════════════════ */
    .text-slate-900, .text-slate-800, .dark\:text-white, .dark\:text-slate-200, .font-black {
        color: #f1f5f9 !important;
    }
    .text-slate-700, .text-slate-500, .text-slate-500, .dark\:text-slate-300, .dark\:text-slate-400 {
        color: #94a3b8 !important;
    }
    .text-slate-400 { color: #64748b !important; }

    h2 {
        font-size: 15pt !important;
        margin-bottom: 0.3cm !important;
        line-height: 1.2 !important;
    }
    h3 {
        font-size: 10pt !important;
        line-height: 1.3 !important;
    }
    h4 {
        font-size: 8.5pt !important;
    }

    .tracking-\[0\.3em\], .tracking-\[0\.2em\] {
        font-size: 6pt !important;
        letter-spacing: 0.2em !important;
        margin-bottom: 0.15cm !important;
    }

    p, li, span {
        font-size: 8pt !important;
        line-height: 1.4 !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  COLORES DE MARCA                              */
    /* ═══════════════════════════════════════════════ */
    .text-\[var\(--color-primary\)\] { color: #FF6B35 !important; }
    .text-brand-400 { color: #fbbf24 !important; }
    .text-brand-500 { color: #f59e0b !important; }
    .text-rose-500 { color: #f43f5e !important; }
    .text-rose-400 { color: #fb7185 !important; }
    .text-emerald-500 { color: #34d399 !important; }
    .text-emerald-600, .dark\:text-emerald-400 { color: #34d399 !important; }

    /* ═══════════════════════════════════════════════ */
    /*  TABLA DE EQUIPOS                              */
    /* ═══════════════════════════════════════════════ */
    table {
        font-size: 7.5pt !important;
        width: 100% !important;
        border-collapse: collapse !important;
    }
    table th {
        background-color: #0f172a !important;
        color: #64748b !important;
        padding: 5px 8px !important;
        font-size: 6pt !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        border-bottom: 2px solid #334155 !important;
    }
    table td {
        padding: 4px 8px !important;
        border-bottom: 1px solid #1e293b !important;
        color: #cbd5e1 !important;
        font-size: 7.5pt !important;
    }
    table tr:nth-child(even) td {
        background-color: rgba(15, 23, 42, 0.5) !important;
    }
    table td:first-child {
        color: #f1f5f9 !important;
        font-weight: 700 !important;
    }
    table td:last-child {
        color: #f1f5f9 !important;
        font-weight: 700 !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  TOTALES RESICO                                */
    /* ═══════════════════════════════════════════════ */
    .print-invoice-footer, .bg-slate-900.text-white {
        background-color: #0f172a !important;
        color: white !important;
        padding: 0.5cm 0.6cm !important;
        border-top: 2px solid #FF6B35 !important;
    }
    .print-invoice-footer span { color: white !important; }
    .print-invoice-footer .text-brand-400 { color: #fbbf24 !important; font-size: 18pt !important; }
    .print-invoice-footer .text-rose-400 { color: #fb7185 !important; }
    .print-invoice-footer .text-slate-400 { color: #64748b !important; }
    .print-invoice-footer .text-slate-500 { color: #475569 !important; }

    /* ═══════════════════════════════════════════════ */
    /*  SÍ / NO INCLUYE                               */
    /* ═══════════════════════════════════════════════ */
    .bg-emerald-50 {
        background-color: rgba(6, 78, 59, 0.2) !important;
        border: 1px solid rgba(52, 211, 153, 0.15) !important;
    }
    .bg-rose-50 {
        background-color: rgba(76, 5, 25, 0.2) !important;
        border: 1px solid rgba(244, 63, 94, 0.15) !important;
    }
    .bg-brand-500 { background-color: #059669 !important; }
    .bg-rose-500 { background-color: #e11d48 !important; }

    .space-y-3 > * + * { margin-top: 0.2cm !important; }
    .space-y-6 > * + * { margin-top: 0.2cm !important; }

    /* ═══════════════════════════════════════════════ */
    /*  GRIDS: ajustados para hoja carta              */
    /* ═══════════════════════════════════════════════ */

    /* Valor Agregado (6 items): 3→2 cols */
    .grid.md\:grid-cols-3 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.35cm !important;
    }

    /* Metodología (4 items): 4→2 cols */
    .grid.md\:grid-cols-4 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.35cm !important;
    }

    /* Términos/Cláusulas: 2→1 col (para que no se corte nada) */
    .grid.md\:grid-cols-2 {
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 0.25cm !important;
    }

    /* Excepción: Calendario (2 items) sí en 2 cols */
    .print-section-calendario .grid.md\:grid-cols-2 {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.35cm !important;
    }

    /* Excepción: Sí/No incluye en 2 cols */
    .grid.lg\:grid-cols-2 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 0.4cm !important;
    }

    /* Firmas: siempre 2 cols */
    .grid.grid-cols-2 {
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
    }

    /* Stats: 4 cols */
    .grid-cols-2.md\:grid-cols-4 {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 0.4cm !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  STATS BAR                                     */
    /* ═══════════════════════════════════════════════ */
    .bg-slate-900.dark\:bg-slate-800 {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
        color: white !important;
        border: 1px solid #334155 !important;
    }
    .bg-slate-900.dark\:bg-slate-800 h2,
    .bg-slate-900.dark\:bg-slate-800 h3 { color: white !important; }
    .bg-slate-900.dark\:bg-slate-800 .text-brand-400 { color: #fbbf24 !important; }

    .grid-cols-2.md\:grid-cols-4 h3 {
        font-size: 20pt !important;
        color: white !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  FIRMAS                                        */
    /* ═══════════════════════════════════════════════ */
    .bg-slate-50.dark\:bg-slate-800\/50 {
        background-color: #1e293b !important;
        border: 1px solid #334155 !important;
        padding: 1.2cm !important;
    }
    .bg-slate-50.dark\:bg-slate-800\/50 .opacity-70 {
        opacity: 1 !important;
    }
    .bg-slate-50.dark\:bg-slate-800\/50 .h-px {
        height: 2px !important;
        background-color: #475569 !important;
    }
    .bg-slate-50.dark\:bg-slate-800\/50 h3 {
        font-size: 8pt !important;
        margin-bottom: 1.2cm !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  FAQ: expandir todo para PDF                   */
    /* ═══════════════════════════════════════════════ */
    /* Ocultar solo el icono chevron, no el texto */
    .print-section-faq button .w-7 { display: none !important; }
    .print-faq-answer {
        display: block !important;
    }
    .border-slate-50 { border-color: #334155 !important; }
    .max-w-4xl { max-width: 100% !important; }

    /* ═══════════════════════════════════════════════ */
    /*  ICON CIRCLES (más compactos)                  */
    /* ═══════════════════════════════════════════════ */
    .w-14.h-14, .w-12.h-12 {
        width: 32px !important;
        height: 32px !important;
        border: 1px solid #334155 !important;
    }
    .w-14.h-14 svg, .w-12.h-12 svg {
        font-size: 13px !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  SPACING GLOBAL                                */
    /* ═══════════════════════════════════════════════ */
    .gap-8 { gap: 0.4cm !important; }
    .gap-6 { gap: 0.35cm !important; }
    .gap-5 { gap: 0.3cm !important; }
    .gap-4 { gap: 0.25cm !important; }
    .gap-3 { gap: 0.15cm !important; }
    .gap-12 { gap: 1.5cm !important; }

    .mb-12 { margin-bottom: 0 !important; }
    .mb-8 { margin-bottom: 0.3cm !important; }
    .mb-6 { margin-bottom: 0.25cm !important; }
    .mb-4 { margin-bottom: 0.15cm !important; }
    .mt-12 { margin-top: 0 !important; }

    /* ═══════════════════════════════════════════════ */
    /*  ANTI-CORTE                                    */
    /* ═══════════════════════════════════════════════ */
    .grid > div {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    .print-box {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    /* ═══════════════════════════════════════════════ */
    /*  LÍNEA DECORATIVA                              */
    /* ═══════════════════════════════════════════════ */
    .print-page-break::after {
        content: '';
        display: block;
        width: 50px;
        height: 2px;
        background: linear-gradient(90deg, #FF6B35, #fbbf24);
        margin: 0.6cm auto 0 auto;
        border-radius: 2px;
    }

    /* ═══════════════════════════════════════════════ */
    /*  PIE DE PÁGINA CORPORATIVO (SOLO PDF)          */
    /* ═══════════════════════════════════════════════ */
    .print-only-footer {
        display: block !important;
        margin-top: auto !important;
        padding: 0 1.8cm 1.2cm 1.8cm !important;
    }
    .print-footer-line {
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, #FF6B35, #fbbf24, transparent);
        margin-bottom: 0.6cm;
    }
    .print-footer-content {
        display: flex;
        flex-direction: column;
        gap: 0.3cm;
    }
    .print-footer-brand {
        font-size: 10pt !important;
        font-weight: 900;
        color: #f1f5f9 !important;
        letter-spacing: 0.05em;
    }
    .print-footer-tagline {
        font-size: 7pt !important;
        color: #FF6B35 !important;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.15em;
        margin-left: 0.4cm;
    }
    .print-footer-info {
        display: flex;
        gap: 1cm;
        font-size: 7pt !important;
        color: #94a3b8 !important;
    }
    .print-footer-info span {
        font-size: 7pt !important;
    }
    .print-footer-info svg {
        color: #FF6B35 !important;
        margin-right: 0.1cm;
        font-size: 7pt !important;
    }
    .print-footer-legal {
        font-size: 5.5pt !important;
        color: #475569 !important;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        font-weight: 600;
        margin-top: 0.2cm;
    }

    @page :first { margin-top: 0; }
}
</style>


