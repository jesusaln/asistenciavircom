<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    compact: {
        type: Boolean,
        default: false
    }
});
const trustFeatures = [
    { icon: 'shield-halved', title: 'Garantía Intacta', desc: 'Mantenga la validez de su garantía de fábrica al usar un centro autorizado.' },
    { icon: 'tools', title: 'Refacciones Originales', desc: 'Solo utilizamos componentes 100% legítimos de la marca Mirage.' },
    { icon: 'user-check', title: 'Técnicos Certificados', desc: 'Personal capacitado directamente por Mirage en nuevas tecnologías.' },
    { icon: 'map-marker-alt', title: 'Verificación Oficial', desc: 'Encuéntrenos en el directorio oficial mirage.mx como Climas del Desierto.' }
];
</script>

<template>
    <section :class="[compact ? 'py-12' : 'py-24', 'bg-slate-950 transition-colors']">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-gradient-to-br from-slate-900 to-[#020617] rounded-[3rem] p-8 md:p-16 relative overflow-hidden shadow-2xl border border-white/5">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 w-96 h-96 bg-[var(--color-primary)] rounded-full blur-[150px] opacity-10 -translate-y-1/2 translate-x-1/2"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500 rounded-full blur-[120px] opacity-10 translate-y-1/2 -translate-x-1/2"></div>

                <div class="relative z-10 flex flex-col lg:flex-row items-center gap-12">
                    <!-- Badge/Logo Side -->
                    <div class="w-full lg:w-1/3 flex flex-col items-center text-center">
                        <div class="w-32 h-32 md:w-48 md:h-48 bg-slate-800 rounded-3xl p-4 shadow-xl mb-8 flex items-center justify-center group hover:scale-105 transition-transform duration-500">
                            <img src="/images/logo.webp" alt="Mirage CAS" class="w-full h-auto object-contain">
                        </div>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-brand-500/10 rounded-full border border-brand-500/20 mb-4">
                            <span class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
                            </span>
                            <span class="text-brand-500 text-[10px] font-black uppercase tracking-wide">Centro Autorizado Verificado</span>
                        </div>
                        <h3 class="text-white text-2xl font-black tracking-tight mb-2">CAS Mirage</h3>
                        <p class="text-slate-400 text-sm font-bold uppercase tracking-wide">Climas del Desierto - CDD</p>
                    </div>

                    <!-- Content Side -->
                    <div class="w-full lg:w-2/3">
                        <h2 class="text-3xl md:text-5xl font-black text-white mb-6 tracking-tight leading-tight">
                            Su inversión está protegida por un <span class="text-transparent bg-clip-text bg-gradient-to-r from-[var(--color-primary)] to-amber-400">Centro de Servicio Autorizado</span>
                        </h2>
                        
                        <p class="text-slate-300 text-lg mb-10 leading-relaxed">
                            Somos parte de la red oficial de centros de servicio **Mirage (CAS)** en Hermosillo. Esto garantiza que su equipo sea intervenido bajo los protocolos más estrictos de fábrica, preservando su garantía original y utilizando únicamente refacciones legítimas.
                        </p>

                        <div class="grid sm:grid-cols-2 gap-6 mb-10">
                            <div v-for="(v, i) in trustFeatures" :key="i" class="flex gap-4">
                                <div class="w-12 h-12 rounded-xl bg-white/5 flex items-center justify-center text-[var(--color-primary)] text-xl shrink-0">
                                    <font-awesome-icon :icon="v.icon" />
                                </div>
                                <div>
                                    <h4 class="text-white font-black text-sm mb-1 uppercase tracking-wider">{{ v.title }}</h4>
                                    <p class="text-slate-400 text-xs leading-relaxed">{{ v.desc }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <a 
                                href="https://mirage.mx/ubicaciones/centros-de-servicio/" 
                                target="_blank"
                                class="px-6 py-3 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-black uppercase tracking-wide border border-white/10 transition-all flex items-center gap-2"
                            >
                                <font-awesome-icon icon="external-link-alt" />
                                Verificar en Directorio Oficial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
