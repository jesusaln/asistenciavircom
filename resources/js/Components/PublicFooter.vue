<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import GoogleMapEmbed from '@/Components/GoogleMapEmbed.vue';

import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
});

const page = usePage();

// Usar una combinación de datos globales y props para asegurar que no falte info (como dirección)
const empresaData = computed(() => {
    const globalConfig = page.props.empresa_config || {};
    const localProp = props.empresa || {};
    return { ...globalConfig, ...localProp };
});

const computeLogo = computed(() => {
    const logoSource = empresaData.value?.logo_url ||
                     page.props.empresa_config?.logo_url ||
                     empresaData.value?.logo;

    if (!logoSource) return '/images/logo.webp';
    
    if (logoSource.startsWith('http') || logoSource.startsWith('/')) {
        return logoSource;
    }
    
    return `/storage/${logoSource}`;
});

// WhatsApp link
const whatsappLink = computed(() => {
    if (!empresaData.value?.whatsapp) return null;
    const phone = empresaData.value.whatsapp.replace(/\D/g, '');
    return `https://wa.me/${phone}?text=Hola, me gustaría obtener más información.`;
});
</script>

<template>
    <footer class="bg-gray-900 dark:bg-gray-950 text-white pt-20 pb-8 mt-auto transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Grid Principal: 4 columnas en desktop -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12 mb-16">
                
                <!-- Columna 1: Logo + Descripción + Redes -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <img :src="computeLogo" class="h-10 w-auto object-contain" :alt="empresaData?.nombre_empresa || empresaData?.nombre || 'Logo'">
                        <span class="text-xl font-bold text-white">{{ empresaData?.nombre_empresa || empresaData?.nombre || 'Empresa' }}</span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        {{ empresaData?.hero_descripcion || empresaData?.descripcion_empresa || 'Soluciones integrales para tu negocio' }}
                    </p>
                    <!-- Redes Sociales Dinámicas -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <!-- Facebook -->
                        <a v-if="empresaData?.facebook_url" :href="empresaData?.facebook_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition-all" title="Facebook">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                        </a>
                        <!-- Instagram -->
                        <a v-if="empresaData?.instagram_url" :href="empresaData?.instagram_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-gradient-to-tr hover:from-yellow-400 hover:via-pink-500 hover:to-purple-600 hover:text-white transition-all" title="Instagram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"/></svg>
                        </a>
                        <!-- Twitter/X -->
                        <a v-if="empresaData?.twitter_url" :href="empresaData?.twitter_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-black hover:text-white transition-all" title="X (Twitter)">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <!-- TikTok -->
                        <a v-if="empresaData?.tiktok_url" :href="empresaData?.tiktok_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-black hover:text-white transition-all" title="TikTok">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                        <!-- YouTube -->
                        <a v-if="empresaData?.youtube_url" :href="empresaData?.youtube_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-600 hover:text-white transition-all" title="YouTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        </a>
                        <!-- LinkedIn -->
                        <a v-if="empresaData?.linkedin_url" :href="empresaData?.linkedin_url" target="_blank" rel="noopener noreferrer" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center text-gray-400 hover:bg-blue-700 hover:text-white transition-all" title="LinkedIn">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    </div>
                </div>
                
                <!-- Columna 2: Servicios -->
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-6">Servicios</h4>
                    <ul class="space-y-4">
                        <li><Link :href="route('public.instalacion-con-costo')" class="text-gray-400 hover:text-white transition-colors text-sm">Instalación con Costo</Link></li>
                        <li><Link :href="route('catalogo.polizas')" class="text-gray-400 hover:text-white transition-colors text-sm">Pólizas Premium</Link></li>
                        <li><Link :href="route('catalogo.index')" class="text-gray-400 hover:text-white transition-colors text-sm">Productos</Link></li>
                        <li><Link :href="route('public.soporte')" class="text-gray-400 hover:text-white transition-colors text-sm">Soporte Técnico</Link></li>
                    </ul>
                </div>
                
                <!-- Columna 3: Contacto -->
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-6">Contacto</h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-3">
                            <font-awesome-icon icon="phone" class="text-[var(--color-primary)] mt-0.5" />
                            <span class="text-gray-400 text-sm">{{ empresaData?.telefono || 'N/A' }}</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <font-awesome-icon icon="envelope" class="text-[var(--color-primary)] mt-0.5" />
                            <span class="text-gray-400 text-sm break-all">{{ empresaData?.email || 'N/A' }}</span>
                        </li>
                    </ul>
                    <div v-if="empresaData?.whatsapp" class="mt-6">
                        <a :href="whatsappLink" target="_blank" class="flex items-center gap-3 px-4 py-3 bg-green-600 hover:bg-green-500 text-white rounded-xl transition-all group shadow-lg shadow-green-600/20">
                            <font-awesome-icon :icon="['fab', 'whatsapp']" class="text-2xl group-hover:scale-110 transition-transform" />
                            <div>
                                <span class="text-xs font-bold block">WhatsApp Directo</span>
                                <span class="text-[10px] text-green-100 block">Respuesta inmediata</span>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Columna 4: Legal -->
                <div>
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-6">Legal</h4>
                    <ul class="space-y-4">
                        <li><Link :href="route('public.privacidad')" class="text-gray-400 hover:text-white transition-colors text-sm">Aviso de Privacidad</Link></li>
                        <li><Link :href="route('public.terminos')" class="text-gray-400 hover:text-white transition-colors text-sm">Términos de Servicio</Link></li>
                        <li><Link :href="route('public.contacto')" class="text-[var(--color-primary)] font-semibold hover:underline text-sm">Hablemos Hoy →</Link></li>
                    </ul>
                </div>
            </div>
            
            <!-- Mapa de Ubicación - Ancho Completo (Solo en Contacto) -->
            <div v-if="route().current('public.contacto')" class="mb-12">
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">
                    <font-awesome-icon icon="map-marker-alt" class="mr-2" />Encuéntranos
                </h4>
                <GoogleMapEmbed 
                    :empresa="empresaData"
                    :direccion="empresaData?.direccion_completa || empresaData?.direccion"
                    height="250px"
                />
                <p class="mt-3 text-gray-400 text-sm">{{ empresaData?.direccion_completa || empresaData?.direccion || 'Dirección no disponible' }}</p>
            </div>

            <!-- Secciones de SEO Local -->
            <div class="mb-12 pt-12 border-t border-gray-800/50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h5 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-4">Especialistas en Sonora</h5>
                        <p class="text-[10px] text-gray-500 leading-relaxed uppercase">
                            Expertos en <strong class="text-gray-400">Venta de Aire Acondicionado en Hermosillo</strong> y todo Sonora. Ofrecemos soluciones de climatización de alta eficiencia adaptadas al clima del desierto.
                        </p>
                    </div>
                    <div>
                        <h5 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-4">Marcas Líderes</h5>
                        <p class="text-[10px] text-gray-500 leading-relaxed uppercase">
                            Distribuidor autorizado de <strong class="text-gray-400">Minisplits Mirage en Hermosillo</strong>. Contamos con tecnología Inverter para ahorro de energía en hogares y empresas.
                        </p>
                    </div>
                    <div>
                        <h5 class="text-xs font-black text-gray-500 uppercase tracking-widest mb-4">Servicio Integral</h5>
                        <p class="text-[10px] text-gray-500 leading-relaxed uppercase">
                            Desde la <strong class="text-gray-400">Instalación de Minisplits</strong> hasta el mantenimiento preventivo y correctivo. Tu confort es nuestra prioridad en cada proyecto.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Copyright Bar -->
            <div class="pt-8 border-t border-gray-800 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-xs text-center sm:text-left">
                    © {{ new Date().getFullYear() }} {{ empresaData?.nombre_empresa || empresaData?.nombre || 'Empresa' }} · Todos los derechos reservados
                </p>
                <div class="flex items-center gap-6 text-xs">
                    <Link :href="route('public.privacidad')" class="text-gray-500 hover:text-gray-300 transition-colors">Privacidad</Link>
                    <Link :href="route('public.terminos')" class="text-gray-500 hover:text-gray-300 transition-colors">Términos</Link>
                </div>
            </div>
        </div>
    </footer>
</template>
