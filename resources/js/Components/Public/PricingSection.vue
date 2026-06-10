<script setup>
defineProps({
    whatsappLink: String
});

const packages = [
    { cap: '1 Ton', price: '$1,650', linkSuffix: ' para equipo de 1 Ton' },
    { cap: '1.5 Ton', price: '$2,150', linkSuffix: ' para equipo de 1.5 Ton' },
    { cap: '2 Ton', price: '$2,650', linkSuffix: ' para equipo de 2 Ton', popular: true },
    { cap: '3 Ton', price: '$3,850', linkSuffix: ' para equipo de 3 Ton' }
];

const features = ['Nitrógeno para pruebas', 'Vacío técnico bomba', 'Carga con báscula', 'Sellado Certificado', 'Puesta en marcha'];
</script>

<template>
    <section class="py-24 bg-slate-900/20 transition-colors">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16">
                <span class="text-[var(--color-primary)] text-xs font-black uppercase tracking-[0.3em] mb-3 block">Transparencia Total</span>
                <h2 class="text-3xl md:text-5xl font-black text-slate-100 tracking-tight transition-colors">
                    Paquetes de <span class="text-[var(--color-primary)]">Recarga Completa</span>
                </h2>
                <p class="mt-4 text-slate-400 max-w-2xl mx-auto">
                    Precios cerrados para recarga total. <strong class="text-slate-100">Incluyen Gas Refrigerante R410A/R32 y Nitrógeno</strong> para pruebas de hermeticidad.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div 
                    v-for="pkg in packages" 
                    :key="pkg.cap"
                    class="relative group p-8 bg-slate-900/50 backdrop-blur-sm rounded-[2rem] border transition-all duration-500 shadow-xl"
                    :class="[
                        pkg.popular 
                            ? 'border-2 border-[var(--color-primary)] shadow-[var(--color-primary)]/10 scale-105 z-10' 
                            : 'border-slate-800 hover:border-[var(--color-primary)]/50'
                    ]"
                >
                    <div v-if="pkg.popular" class="absolute -top-4 left-1/2 -translate-x-1/2 bg-[var(--color-primary)] text-white px-4 py-1 rounded-full text-[8px] font-black uppercase tracking-[0.2em]">Más Solicitado</div>
                    
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-blue-500 text-[10px] font-black uppercase tracking-wide block">Capacidad {{ pkg.cap }}</span>
                        <div class="flex flex-col items-end leading-none">
                            <span class="text-[var(--color-primary)] font-black text-[7px] uppercase">CAS</span>
                            <span class="text-slate-100 font-black text-[9px] uppercase tracking-wide">Mirage</span>
                        </div>
                    </div>
                    <h3 class="text-lg font-black text-slate-100 mb-2 leading-tight">Recarga R410a/R32</h3>
                    
                    <div class="flex items-baseline gap-1 mt-4 mb-6">
                        <span class="text-3xl font-black text-slate-100">{{ pkg.price }}</span>
                        <span class="text-slate-400 text-xs font-bold">MXN</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        <li v-for="item in features" :key="item" class="flex items-center gap-2 text-[11px] text-slate-400">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            {{ item }}
                        </li>
                    </ul>

                    <a :href="whatsappLink + pkg.linkSuffix" target="_blank" class="w-full py-3 bg-[var(--color-primary)] text-white rounded-xl font-black text-[10px] uppercase tracking-wide text-center block hover:scale-[1.02] transition-transform">Solicitar</a>
                </div>
            </div>
        </div>
    </section>
</template>
