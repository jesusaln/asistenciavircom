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
    <Head title="Términos y Condiciones" />
    
    <div class="min-h-screen bg-white" :style="cssVars">
        <!-- Navbar -->
        <PublicNavbar :empresa="empresa" activeTab="" />

        <!-- Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 py-12">
            <!-- Header de la página -->
            <div class="mb-12 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-full text-sm text-gray-600 mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Documento Legal
                </div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Términos y Condiciones
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
                        Aceptación de los Términos
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        Al acceder y utilizar los servicios de <strong>{{ empresa?.nombre }}</strong> (en adelante "la Empresa"), usted acepta cumplir con estos Términos y Condiciones. Si no está de acuerdo con alguno de estos términos, le solicitamos que no utilice nuestros servicios. El uso continuado de nuestros servicios, sitio web o plataformas constituye su aceptación de estos términos y cualquier modificación futura.
                    </p>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">2</span>
                        Descripción de los Servicios
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        {{ empresa?.nombre_comercial }} ofrece los siguientes servicios:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                                    <svg class="w-4 h-4" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900">Equipos de Climatización</h3>
                            </div>
                            <p class="text-sm text-gray-600">Venta e instalación de aires acondicionados, minisplits, sistemas de refrigeración y calefacción.</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                                    <svg class="w-4 h-4" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-gray-900">Servicio Técnico</h3>
                            </div>
                            <p class="text-sm text-gray-600">Mantenimiento preventivo y correctivo, reparaciones, pólizas de servicio y atención de emergencias.</p>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">3</span>
                        Proceso de Compra y Contratación
                    </h2>
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold" style="background-color: var(--color-primary);">1</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Cotización</h4>
                                <p class="text-gray-600 text-sm">Solicitamos la información necesaria para elaborar una cotización personalizada. Las cotizaciones tienen una vigencia de 15 días naturales.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold" style="background-color: var(--color-primary);">2</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Confirmación del Pedido</h4>
                                <p class="text-gray-600 text-sm">Una vez aceptada la cotización, se genera un pedido que debe ser confirmado con un anticipo del 50% del total.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold" style="background-color: var(--color-primary);">3</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Entrega e Instalación</h4>
                                <p class="text-gray-600 text-sm">Programamos la entrega e instalación en la fecha acordada. El saldo restante debe liquidarse al momento de la instalación.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">4</span>
                        Precios y Formas de Pago
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Todos los precios están expresados en Pesos Mexicanos (MXN) e incluyen el Impuesto al Valor Agregado (IVA) del 16%, salvo que se indique lo contrario. Aceptamos las siguientes formas de pago:
                    </p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-100">
                            <span class="text-2xl mb-1 block">💵</span>
                            <span class="text-sm font-medium text-gray-700">Efectivo</span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-100">
                            <span class="text-2xl mb-1 block">💳</span>
                            <span class="text-sm font-medium text-gray-700">Tarjetas</span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-100">
                            <span class="text-2xl mb-1 block">🏦</span>
                            <span class="text-sm font-medium text-gray-700">Transferencia</span>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-100">
                            <span class="text-2xl mb-1 block">📱</span>
                            <span class="text-sm font-medium text-gray-700">SPEI/CoDi</span>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">5</span>
                        Garantías
                    </h2>
                    <div class="bg-green-50 rounded-xl p-6 border border-green-100 mb-4">
                        <h3 class="font-semibold text-green-900 mb-2">Garantía de Productos</h3>
                        <p class="text-green-800 text-sm">
                            Todos nuestros equipos cuentan con la garantía del fabricante. La garantía cubre defectos de fabricación y no aplica en casos de mal uso, instalación incorrecta, falta de mantenimiento o daños por agentes externos.
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
                        <h3 class="font-semibold text-blue-900 mb-2">Garantía de Instalación</h3>
                        <p class="text-blue-800 text-sm">
                            Ofrecemos 6 meses de garantía en mano de obra para instalaciones realizadas por nuestro personal técnico certificado. Esta garantía cubre defectos de instalación, pero no incluye consumibles ni refacciones.
                        </p>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">6</span>
                        Pólizas de Servicio
                    </h2>
                    <p class="text-gray-700 leading-relaxed mb-4">
                        Las pólizas de servicio son contratos anuales que incluyen mantenimientos preventivos programados. Los términos específicos de cada póliza se detallan en el contrato correspondiente. Condiciones generales:
                    </p>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span>Los mantenimientos se programan en horario de lunes a viernes de 9:00 a 18:00 hrs.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span>Las pólizas no cubren refacciones ni consumibles, estos se cotizan por separado.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span>Los servicios de emergencia fuera de horario tienen un costo adicional.</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="w-2 h-2 rounded-full mt-2 flex-shrink-0" style="background-color: var(--color-primary);"></span>
                            <span>La póliza se renueva automáticamente salvo que se notifique por escrito 30 días antes del vencimiento.</span>
                        </li>
                    </ul>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">7</span>
                        Cancelaciones y Devoluciones
                    </h2>
                    <div class="bg-amber-50 rounded-xl p-6 border border-amber-100">
                        <ul class="text-amber-900 text-sm space-y-2">
                            <li>• Las cancelaciones antes de la entrega tienen derecho a devolución del 80% del anticipo.</li>
                            <li>• Una vez instalado el equipo, no aplican devoluciones.</li>
                            <li>• Los servicios de mantenimiento ya realizados no son reembolsables.</li>
                            <li>• Las cancelaciones deben notificarse por escrito al correo {{ empresa?.email }}.</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">8</span>
                        Limitación de Responsabilidad
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        {{ empresa?.nombre }} no será responsable por daños indirectos, incidentales, especiales o consecuentes que resulten del uso o la imposibilidad de uso de nuestros productos o servicios. Nuestra responsabilidad máxima estará limitada al monto pagado por el cliente por el producto o servicio en cuestión.
                    </p>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">9</span>
                        Contacto
                    </h2>
                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                        <p class="text-gray-700 mb-4">
                            Para cualquier duda, aclaración o reclamación relacionada con estos Términos y Condiciones, puede contactarnos a través de:
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div v-if="empresa?.email" class="flex items-center gap-3 text-gray-600">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                                    <svg class="w-5 h-5" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Correo electrónico</p>
                                    <p class="font-medium text-gray-900">{{ empresa.email }}</p>
                                </div>
                            </div>
                            <div v-if="empresa?.telefono" class="flex items-center gap-3 text-gray-600">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                                    <svg class="w-5 h-5" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Teléfono</p>
                                    <p class="font-medium text-gray-900">{{ empresa.telefono }}</p>
                                </div>
                            </div>
                            <div v-if="empresa?.whatsapp" class="flex items-center gap-3 text-gray-600">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">WhatsApp</p>
                                    <p class="font-medium text-gray-900">{{ empresa.whatsapp }}</p>
                                </div>
                            </div>
                            <div v-if="empresa?.direccion" class="flex items-center gap-3 text-gray-600">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: var(--color-primary); opacity: 0.1;">
                                    <svg class="w-5 h-5" style="color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Dirección</p>
                                    <p class="font-medium text-gray-900">{{ empresa.direccion }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-10">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-sm font-bold" style="color: var(--color-primary);">10</span>
                        Legislación Aplicable
                    </h2>
                    <p class="text-gray-700 leading-relaxed">
                        Los presentes Términos y Condiciones se rigen por las leyes de los Estados Unidos Mexicanos. Para cualquier controversia que surja en relación con estos términos, las partes acuerdan someterse a la jurisdicción de los tribunales competentes de la ciudad de Hermosillo, Sonora, renunciando a cualquier otro fuero que pudiera corresponderles por razón de su domicilio presente o futuro.
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
                        <Link :href="route('public.privacidad')" class="text-sm font-medium hover:underline" style="color: var(--color-primary);">
                            Política de Privacidad
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
