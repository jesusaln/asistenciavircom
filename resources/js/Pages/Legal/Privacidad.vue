<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import PublicNavbar from '@/Components/PublicNavbar.vue';
import PublicFooter from '@/Components/PublicFooter.vue';

const props = defineProps({
    empresa: Object
})

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3B82F6',
    '--color-secondary': props.empresa?.color_secundario || '#64748B',
}))

const currentYear = new Date().getFullYear()
const lastUpdated = new Date().toLocaleDateString('es-MX', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
})
</script>

<template>
    <Head title="Política de Privacidad" />
    
    <div class="min-h-screen bg-white" :style="cssVars">
        <!-- Navbar -->
        <PublicNavbar :empresa="empresa" activeTab="" />

        <!-- Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
            <!-- Header de la página -->
            <div class="mb-12 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-600 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Documento Legal
                </div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Política de Privacidad
                </h1>
                <p class="text-gray-500">
                    Última actualización: {{ lastUpdated }}
                </p>
            </div>

            <!-- Contenido del documento -->
            <div class="prose prose-gray max-w-none">
                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">1</span>
                        Identidad del Responsable
                    </h2>
                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                        <p class="text-gray-700 leading-relaxed">
                            <strong>{{ empresa?.nombre }}</strong> (en adelante "la Empresa"), con domicilio en 
                            <span v-if="empresa?.direccion">{{ empresa.direccion }}, </span>
                            <span v-if="empresa?.ciudad">{{ empresa.ciudad }}, </span>
                            <span v-if="empresa?.estado">{{ empresa.estado }}, </span>
                            <span v-if="empresa?.codigo_postal">C.P. {{ empresa.codigo_postal }}, </span>
                            México, es el responsable del tratamiento de los datos personales que nos proporcione.
                        </p>
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div v-if="empresa?.email" class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ empresa.email }}
                            </div>
                            <div v-if="empresa?.telefono" class="flex items-center gap-2 text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ empresa.telefono }}
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">2</span>
                        Datos Personales que Recopilamos
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Para cumplir con las finalidades descritas en el presente Aviso de Privacidad, podemos recabar los siguientes datos personales:
                    </p>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span><strong>Datos de identificación:</strong> Nombre completo, RFC, CURP, fecha de nacimiento.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span><strong>Datos de contacto:</strong> Domicilio, correo electrónico, teléfono fijo y/o celular.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span><strong>Datos fiscales:</strong> RFC, régimen fiscal, uso de CFDI.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span><strong>Datos financieros:</strong> Información bancaria para pagos y facturación.</span>
                        </li>
                    </ul>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">3</span>
                        Finalidades del Tratamiento
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Los datos personales que recabamos serán utilizados para las siguientes finalidades primarias:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <h3 class="font-semibold text-gray-900 mb-2">Finalidades Primarias</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Proveer los productos y servicios solicitados</li>
                                <li>• Generar facturas y comprobantes fiscales</li>
                                <li>• Dar seguimiento a cotizaciones y pedidos</li>
                                <li>• Brindar soporte técnico y atención al cliente</li>
                                <li>• Cumplir con obligaciones contractuales</li>
                            </ul>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <h3 class="font-semibold text-gray-900 mb-2">Finalidades Secundarias</h3>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Enviar promociones y ofertas especiales</li>
                                <li>• Realizar encuestas de satisfacción</li>
                                <li>• Informar sobre nuevos productos o servicios</li>
                                <li>• Estadísticas y análisis internos</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">4</span>
                        Derechos ARCO
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los utilizamos y las condiciones del uso que les damos (Acceso). Asimismo, es su derecho solicitar la corrección de su información personal en caso de que esté desactualizada, sea inexacta o incompleta (Rectificación); que la eliminemos de nuestros registros o bases de datos cuando considere que la misma no está siendo utilizada adecuadamente (Cancelación); así como oponerse al uso de sus datos personales para fines específicos (Oposición).
                    </p>
                    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                        <p class="text-blue-800 text-sm">
                            <strong>Para ejercer sus derechos ARCO,</strong> puede enviar su solicitud al correo electrónico 
                            <a :href="'mailto:' + empresa?.email" class="underline font-semibold">{{ empresa?.email }}</a> 
                            o comunicarse al teléfono <strong>{{ empresa?.telefono }}</strong>.
                        </p>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">5</span>
                        Uso de Cookies y Tecnologías de Rastreo
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        Nuestro sitio web utiliza cookies y otras tecnologías de rastreo para mejorar su experiencia de navegación, analizar el tráfico del sitio y personalizar el contenido. Usted puede configurar su navegador para rechazar todas las cookies o para que le avise cuando se envíe una cookie. Sin embargo, si desactiva las cookies, es posible que algunas partes de nuestro sitio no funcionen correctamente.
                    </p>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">6</span>
                        Transferencia de Datos
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        Sus datos personales podrán ser transferidos y tratados dentro y fuera del país, por personas distintas a esta empresa. En ese sentido, su información podrá ser compartida con: proveedores de servicios de pago, empresas de paquetería y mensajería, autoridades fiscales y gubernamentales cuando sea requerido por ley. Nos comprometemos a no transferir su información personal a terceros sin su consentimiento, salvo las excepciones previstas en la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.
                    </p>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">7</span>
                        Cambios al Aviso de Privacidad
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        El presente aviso de privacidad puede sufrir modificaciones, cambios o actualizaciones derivadas de nuevos requerimientos legales, de nuestras propias necesidades por los productos o servicios que ofrecemos, de nuestras prácticas de privacidad, de cambios en nuestro modelo de negocio, o por otras causas. Nos comprometemos a mantenerlo informado sobre los cambios que pueda sufrir el presente aviso de privacidad a través de nuestro sitio web.
                    </p>
                </section>
            </div>

            <!-- Footer del documento -->
            <div class="mt-12 pt-8 border-t border-gray-100">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <p class="text-sm text-gray-500">
                        © {{ currentYear }} {{ empresa?.nombre_comercial }}. Todos los derechos reservados.
                    </p>
                    <div class="flex items-center gap-4">
                        <Link :href="route('public.terminos')" class="text-sm font-medium hover:underline" style="color: var(--color-primary);">
                            Términos y Condiciones
                        </Link>
                        <Link :href="route('public.contacto')" class="text-sm font-medium hover:underline" style="color: var(--color-primary);">
                            Contacto
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <PublicFooter :empresa="empresa" />
    </div>
</template>
