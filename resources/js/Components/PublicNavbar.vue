<script setup>
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import { useDarkMode } from '@/Utils/useDarkMode';
import { useCart } from '@/composables/useCart';

const props = defineProps({
    empresa: {
        type: Object,
        required: true
    },
    activeTab: {
        type: String,
        default: 'inicio'
    }
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || page.props.auth?.client);
const { itemCount } = useCart();

const navLinks = [
    { name: 'Inicio', route: 'landing', id: 'landing' },
    { name: 'Servicios', id: 'servicios', dropdown: true },
    { name: 'Blog', route: 'public.blog.index', id: 'blog' },
    { name: 'Productos', route: 'catalogo.index', id: 'tienda' },
];

const serviciosLinks = [
    { name: 'Cámaras y CCTV', route: 'public.servicio.show', params: { slug: 'camaras-cctv' }, id: 'cctv' },
    { name: 'Control de Accesos', route: 'public.servicio.show', params: { slug: 'control-acceso' }, id: 'acceso' },
    { name: 'Alarmas y Seguridad', route: 'public.servicio.show', params: { slug: 'alarmas-seguridad' }, id: 'alarmas' },
    { name: 'Puntos de Venta (POS)', route: 'public.servicio.show', params: { slug: 'punto-de-venta' }, id: 'pos' },
    { name: 'Redes e Infraestructura', route: 'public.servicio.show', params: { slug: 'redes-infraestructura' }, id: 'redes' },
    { name: 'Relojes Checadores', route: 'public.servicio.show', params: { slug: 'relojes-checadores' }, id: 'asistencia' },
    { name: 'Desarrollo Web', route: 'public.servicio.show', params: { slug: 'desarrollo-web' }, id: 'web' },
];

const computeLogo = computed(() => {
    return props.empresa?.logo_url || props.empresa?.logo || page.props.empresa_config?.logo_url;
});

const computeBrandName = computed(() => {
    return props.empresa?.nombre_comercial_config || 
           props.empresa?.nombre_comercial || 
           props.empresa?.nombre || 
           page.props.empresa_config?.nombre_empresa || 
           'Vircom';
});

const showAuthModal = ref(false);
const showUserMenu = ref(false);
const showServiciosMenu = ref(false);
const showMobileMenu = ref(false);
const showMobileServicios = ref(false);

const isServiciosActive = computed(() => {
    return serviciosLinks.some(link => props.activeTab === link.id);
});

// Form para cerrar sesión
const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route('portal.logout'), {
        preserveScroll: true,
    });
};

// Dark Mode Logic
const { isDarkMode, toggleDarkMode, updateThemeColors } = useDarkMode(props.empresa);

// Sincronizar colores si cambia la config desde props
watch(() => props.empresa, (newConfig) => {
    if (newConfig) updateThemeColors(newConfig);
}, { deep: true });

</script>

<template>
    <nav class="bg-white dark:bg-slate-900/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-100 dark:border-slate-800 dark:border-gray-800 sticky top-0 z-50 transition-all">
        <div class="max-w-7xl mx-auto px-4 h-20 flex justify-between items-center">
            <!-- Logo / Brand -->
            <Link :href="route('landing')" class="flex items-center gap-4 group">
                <img v-if="computeLogo" :src="computeLogo" class="h-12 w-auto object-contain transition-transform group-hover:scale-105" :alt="computeBrandName">
                <span v-else class="text-2xl font-black text-gray-900 dark:text-white dark:text-white transition-colors">
                    {{ computeBrandName }}
                </span>
            </Link>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-10">
                <!-- Links Group -->
                <div class="flex items-center gap-8">
                    <template v-for="link in navLinks" :key="link.id">
                        <!-- Dropdown de Servicios -->
                        <div v-if="link.dropdown" class="relative" v-click-outside="() => showServiciosMenu = false">
                            <button 
                                @mousedown="showServiciosMenu = !showServiciosMenu"
                                :class="[
                                    'flex items-center gap-1 text-xs font-black transition-all uppercase tracking-[0.15em] pb-1',
                                    (isServiciosActive || showServiciosMenu)
                                        ? 'text-gray-900 dark:text-white border-b-2 border-[var(--color-primary)]' 
                                        : 'text-gray-400 dark:text-gray-500 hover:text-[var(--color-primary)]'
                                ]"
                            >
                                {{ link.name }}
                                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': showServiciosMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Dropdown Content -->
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="showServiciosMenu" class="absolute left-0 mt-4 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 py-3 z-50 overflow-hidden ring-1 ring-black/5">
                                    <Link 
                                        v-for="sLink in serviciosLinks" 
                                        :key="sLink.id"
                                        :href="route(sLink.route, sLink.params || {}) + (sLink.hash || '')" 
                                        class="block px-5 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-[var(--color-primary)] transition-all"
                                        @click="showServiciosMenu = false"
                                    >
                                        {{ sLink.name }}
                                    </Link>
                                </div>
                            </Transition>
                        </div>

                        <!-- Links Normales -->
                        <Link 
                            v-else
                            :href="route(link.route)" 
                            :class="[
                                'text-xs font-black transition-all uppercase tracking-[0.15em] pb-1',
                                activeTab === link.id 
                                    ? 'text-gray-900 dark:text-white border-b-2 border-[var(--color-primary)]' 
                                    : 'text-gray-400 dark:text-gray-500 hover:text-[var(--color-primary)]'
                            ]"
                        >
                            {{ link.name }}
                        </Link>
                    </template>
                </div>

                <!-- Action Button Group -->
                <div class="flex items-center gap-5 pl-8 border-l border-gray-100 dark:border-slate-800">
                    <!-- Link Soporte -->
                    <Link 
                        :href="route('public.contacto')"
                        class="px-6 py-3.5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-105 hover:shadow-xl hover:shadow-[var(--color-primary)]/40 transition-all border border-transparent"
                    >
                        Cotizar Ahora
                    </Link>

                    <!-- User Actions Tools -->
                    <div class="flex items-center gap-1.5">
                        <!-- Dark Mode Toggle -->
                        <button 
                            @click="toggleDarkMode" 
                            class="p-2.5 rounded-xl text-gray-400 hover:text-[var(--color-primary)] hover:bg-gray-50 dark:hover:bg-gray-800 transition-all focus:outline-none"
                            :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                        >
                            <svg v-if="isDarkMode" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- Cart Icon -->
                        <Link :href="route('tienda.carrito')" class="relative p-2.5 text-gray-400 hover:text-[var(--color-primary)] hover:bg-gray-50 dark:hover:bg-gray-800 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-if="itemCount > 0" class="absolute top-1 right-1 w-4.5 h-4.5 bg-[var(--color-primary)] rounded-full text-[8px] font-black text-white flex items-center justify-center ring-2 ring-white dark:ring-slate-900">
                                {{ itemCount > 9 ? '9+' : itemCount }}
                            </span>
                        </Link>
                    </div>

                    <!-- Divider -->
                    <div class="h-6 w-px bg-gray-100 dark:bg-slate-800"></div>

                    <!-- Auth Block -->
                    <div v-if="currentUser">
                        <div class="relative" v-click-outside="() => showUserMenu = false">
                            <button 
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-800 rounded-xl transition-all group border border-transparent hover:border-gray-100 dark:hover:border-slate-700 shadow-sm hover:shadow-md"
                            >
                                <div class="w-6 h-6 rounded-lg bg-[var(--color-primary)] flex items-center justify-center text-[10px] font-black text-white">
                                    {{ (currentUser.nombre_razon_social || currentUser.name || 'H').charAt(0).toUpperCase() }}
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-700 dark:text-gray-200">Panel</span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="{ 'rotate-180': showUserMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- User Dropdown Details ... -->
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="showUserMenu" class="absolute right-0 mt-3 w-52 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-800 py-2 z-50 ring-1 ring-black/5">
                                    <Link 
                                        :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" 
                                        class="flex items-center gap-3 px-4 py-3.5 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-50 dark:hover:bg-slate-800 dark:text-gray-400 hover:text-[var(--color-primary)] transition-all"
                                        @click="showUserMenu = false"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Mi Panel
                                    </Link>
                                    <div class="mx-4 border-t border-gray-50 dark:border-white/5 my-1"></div>
                                    <button 
                                        @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-3.5 text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Cerrar Sesión
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-5">
                        <Link :href="route('portal.login')" class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-[var(--color-primary)]/30 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-[var(--color-primary)]/40 transition-all">
                            Ingresar
                        </Link>
                        <!-- Staff Shortcut -->
                        <a href="/login" class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-300 hover:text-[var(--color-primary)] transition-colors" title="Acceso Administrativo">
                            Staff
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="md:hidden flex items-center gap-3">
                 <!-- Dark Mode Toggle Mobile -->
                 <button 
                    @click="toggleDarkMode" 
                    class="p-2 text-gray-500 dark:text-gray-400 dark:text-gray-300 focus:outline-none active:scale-90 transition-transform relative z-20"
                    type="button"
                 >
                    <Transition name="rotate-icon" mode="out-in">
                        <svg v-if="isDarkMode" class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </Transition>
                 </button>



                 <Link :href="route('tienda.carrito')" class="relative p-2 text-gray-500 dark:text-gray-400 dark:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span v-if="itemCount > 0" class="absolute top-0 right-0 w-4 h-4 bg-[var(--color-primary)] rounded-full text-[8px] font-black text-white flex items-center justify-center border border-white">
                        {{ itemCount }}
                    </span>
                </Link>
                <!-- Mobile Toggle -->
                <button 
                    type="button"
                    @click="showMobileMenu = true" 
                    class="w-12 h-12 flex items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white transition-all active:scale-90 relative z-[40] border border-gray-200 dark:border-gray-700 shadow-sm"
                    aria-label="Open Menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 8h16M4 16h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (Teleported to Body for absolute reliability) -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showMobileMenu" class="fixed inset-0 z-[9999] md:hidden overflow-hidden">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xl" @click="showMobileMenu = false"></div>
                    
                    <!-- Drawer Content -->
                    <Transition name="drawer-slide" appear>
                        <div v-if="showMobileMenu" class="absolute right-0 top-0 bottom-0 w-[85%] max-w-[360px] bg-white dark:bg-slate-950 shadow-[0_0_80px_rgba(0,0,0,0.5)] flex flex-col border-l border-gray-100 dark:border-slate-800">
                            <!-- Header / Close -->
                            <div class="p-6 flex justify-between items-center border-b border-gray-50 dark:border-white/5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-[var(--color-primary)] flex items-center justify-center text-white font-black text-sm">V</div>
                                    <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-900 dark:text-white">Menú</span>
                                </div>
                                <button @click="showMobileMenu = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white transition-all active:scale-90">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-grow overflow-y-auto px-8 py-8 space-y-2 custom-scrollbar">
                                <template v-for="link in navLinks" :key="link.id">
                                    <div v-if="link.dropdown" class="mb-2">
                                        <button 
                                            @click="showMobileServicios = !showMobileServicios"
                                            class="w-full flex justify-between items-center py-5 text-2xl font-black uppercase tracking-tighter text-gray-900 dark:text-white border-b border-gray-50 dark:border-white/5"
                                        >
                                            {{ link.name }}
                                            <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180 text-[var(--color-primary)]': showMobileServicios }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        
                                        <Transition
                                            enter-active-class="transition-all duration-300 ease-out"
                                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                            enter-to-class="max-h-[500px] opacity-100"
                                            leave-active-class="transition-all duration-200 ease-in"
                                            leave-from-class="max-h-[500px] opacity-100"
                                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                        >
                                            <div v-if="showMobileServicios" class="mt-4 bg-gray-50 dark:bg-white/5 rounded-3xl overflow-hidden border border-gray-100 dark:border-white/5">
                                                <Link 
                                                    v-for="sLink in serviciosLinks" 
                                                    :key="sLink.id"
                                                    :href="route(sLink.route, sLink.params || {}) + (sLink.hash || '')"
                                                    class="block px-6 py-4 text-base font-bold text-gray-600 dark:text-gray-400 border-b border-white dark:border-white/5 last:border-0 active:bg-[var(--color-primary)] active:text-white"
                                                    @click="showMobileMenu = false"
                                                >
                                                    {{ sLink.name }}
                                                </Link>
                                            </div>
                                        </Transition>
                                    </div>
                                    <Link 
                                        v-else
                                        :href="route(link.route)"
                                        class="block py-5 text-2xl font-black uppercase tracking-tighter text-gray-900 dark:text-white border-b border-gray-50 dark:border-white/5"
                                        :class="{ 'text-[var(--color-primary)]': activeTab === link.id }"
                                        @click="showMobileMenu = false"
                                    >
                                        {{ link.name }}
                                    </Link>
                                </template>
                            </div>
                            
                            <!-- Auth Mobile -->
                            <div class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-t-[3rem] border-t border-gray-100 dark:border-white/5">
                                <div v-if="!currentUser" class="grid grid-cols-1 gap-4">
                                    <Link :href="route('portal.login')" class="py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg shadow-[var(--color-primary)]/30" @click="showMobileMenu = false">Ingresar / Registro</Link>
                                </div>
                                <div v-else class="space-y-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[var(--color-primary)] to-amber-500 flex items-center justify-center text-white font-black shadow-lg">
                                            {{ (currentUser.nombre_razon_social || currentUser.name || 'H').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Usuario</p>
                                            <p class="font-black text-gray-900 dark:text-white truncate">{{ currentUser.nombre_razon_social || currentUser.name }}</p>
                                        </div>
                                    </div>
                                    <Link :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" class="block w-full py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase text-[10px] tracking-widest" @click="showMobileMenu = false">Mi Portal</Link>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </nav>

    <!-- Modal Autorización (Teleported to avoid 500 issues) -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showAuthModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showAuthModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl max-w-md w-full p-10 text-center border border-white/10 overflow-hidden animate-scale-in">
                    <div class="text-6xl mb-6">🛍️</div>
                    <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-4 tracking-tight">¡Bienvenido!</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 text-sm">Tu cuenta está lista para realizar compras.</p>
                    <button @click="showAuthModal = false" class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase tracking-widest text-xs">Continuar</button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Transiciones Refinadas */
.fade-enter-active, .fade-leave-active { transition: opacity 0.4s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.drawer-slide-enter-active { transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-slide-leave-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-slide-enter-from, .drawer-slide-leave-to { transform: translateX(100%); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(var(--color-primary-rgb), 0.1); border-radius: 10px; }

@keyframes scale-in {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-scale-in { animation: scale-in 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
</style>
