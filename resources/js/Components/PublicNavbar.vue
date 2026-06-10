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
    { name: 'Productos', id: 'tienda', dropdown: true },
    { name: 'Contacto', route: 'public.contacto', id: 'contacto' },
];

const serviciosLinks = [
    { name: 'Instalación Gratis Mirage', route: 'public.instalacion-mirage', id: 'instalacion-mirage' },
    { name: 'Instalación con Costo', route: 'public.instalacion-con-costo', id: 'instalacion-con-costo' },
    { name: 'Reparación de Minisplit', route: 'public.reparacion', id: 'reparacion' },
    { name: 'Instalación de Minisplit', route: 'public.instalacion', id: 'instalacion' },
    { name: 'Mantenimiento Preventivo', route: 'public.mantenimiento', id: 'mantenimiento' },
    { name: 'Recarga de Gas', route: 'public.gas', id: 'gas' },
    { name: 'Pólizas de Soporte', route: 'catalogo.polizas', id: 'polizas' },
    { name: 'Renta de Equipos', route: 'catalogo.rentas', id: 'rentas' },
];

const productosLinks = [
    { name: 'Todos los Productos', route: 'catalogo.index', id: 'tienda' },
    { name: 'Minisplit Life 12+', route: 'public.life12plus', id: 'life12plus', highlighted: true },
    { name: 'Minisplit Magnum 22', route: 'public.magnum22', id: 'magnum22', highlighted: true },
];

const computeLogo = computed(() => {
    // Usar primero URLs resueltas por backend (ya validadas)
    const logoSource = props.empresa?.logo_url ||
                     page.props.empresa_config?.logo_url ||
                     props.empresa?.logo;

    if (!logoSource) return '/images/logo.webp'; // Fallback a imagen estática

    // Si ya es una URL completa o un path absoluto, retornarlo tal cual
    if (logoSource.startsWith('http') || logoSource.startsWith('/')) {
        return logoSource;
    }

    // Si es un path relativo, fallback a storage público
    return `/storage/${logoSource}`;
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

const isProductosActive = computed(() => {
    return props.activeTab === 'tienda' || props.activeTab === 'life12plus' || props.activeTab === 'magnum22';
});

const showProductosMenu = ref(false);
const showMobileProductos = ref(false);

// Form para cerrar sesión
const logoutForm = useForm({});

const logout = () => {
    // Si hay un cliente logueado, usamos la ruta del portal.
    // De lo contrario usamos la ruta de staff (Fortify).
    const isClient = !!page.props.auth?.client;
    const logoutRoute = isClient ? 'portal.logout' : 'logout';
    
    logoutForm.post(route(logoutRoute), {
        preserveScroll: true,
        onSuccess: () => {
            // Opcional: Si era staff y estaba en una página pública, 
            // podemos forzar recarga o dejar que Fortify/Laravel redirija.
        }
    });
};

// Dark Mode Logic
const { isDarkMode, toggleDarkMode, updateThemeColors } = useDarkMode(props.empresa);

// Sincronizar colores si cambia la config desde props
watch(() => props.empresa, (newConfig) => {
    if (newConfig) updateThemeColors(newConfig);
}, { deep: true });
// Scroll Logic
const isScrolled = ref(false);
const isVisible = ref(true);

const handleScroll = () => {
    const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
    
    // Si estamos al inicio (menos de 50px de scroll)
    isScrolled.value = currentScrollPosition > 50;
    
    // Siempre visible pero con efectos de scroll
    isVisible.value = true;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

</script>

<template>
    <nav 
        class="bg-[var(--ui-surface)]/80 backdrop-blur-md border-b border-[var(--ui-border)] sticky top-0 z-50 transition-all duration-500 ease-in-out h-auto overflow-visible"
        :class="[
            isScrolled ? 'shadow-xl shadow-black/5 dark:shadow-white/5' : '',
            isVisible ? 'translate-y-0' : '-translate-y-full'
        ]"
    >
        <div 
            class="max-w-7xl mx-auto px-4 flex justify-between items-center transition-all duration-500 ease-in-out"
            :class="isScrolled ? 'h-14 sm:h-16' : 'h-20 sm:h-24'"
        >
            <!-- Logo / Brand -->
            <div class="flex items-center gap-2 sm:gap-4">
                <Link :href="route('landing')" class="flex items-center gap-4 group">
                    <img 
                        v-if="computeLogo" 
                        :src="computeLogo" 
                        class="w-auto object-contain transition-all duration-500 ease-in-out group-hover:scale-105" 
                        :class="isScrolled ? 'h-8 sm:h-10' : 'h-12 sm:h-14'"
                        :alt="computeBrandName"
                    >
                    <span v-else class="text-2xl font-black text-[var(--ui-text)] transition-colors">
                        {{ computeBrandName }}
                    </span>
                </Link>
                
                <!-- CAS Badge -->
                <div class="hidden sm:flex flex-col items-start leading-none border-l border-[var(--ui-border)] pl-4">
                    <span class="text-[var(--color-primary)] font-black text-[10px] uppercase tracking-wide">Centro Autorizado</span>
                    <span class="text-[var(--ui-text)] font-black text-[12px] uppercase tracking-wide flex items-center gap-1">
                        Mirage
                        <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                    </span>
                </div>
            </div>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-8">
                <template v-for="link in navLinks" :key="link.id">
                    <!-- Dropdown de Servicios -->
                    <div v-if="link.id === 'servicios'" class="relative" v-click-outside="() => showServiciosMenu = false">
                        <button 
                            @mousedown="showServiciosMenu = !showServiciosMenu"
                            :class="[
                                'flex items-center gap-1 text-sm font-bold transition-all uppercase tracking-wide pb-1',
                                (isServiciosActive || showServiciosMenu)
                                    ? 'text-[var(--ui-text)] border-b-2 border-[var(--color-primary)]' 
                                    : 'text-[var(--ui-text-soft)] hover:text-[var(--color-primary)]'
                            ]"
                        >
                            {{ link.name }}
                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showServiciosMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                            <div v-if="showServiciosMenu" class="absolute left-0 mt-2 w-48 bg-[var(--ui-surface)] rounded-xl shadow-xl border border-[var(--ui-border)] py-2 z-50">
                                <Link 
                                    v-for="sLink in serviciosLinks" 
                                    :key="sLink.id"
                                    :href="route(sLink.route, sLink.params || {})" 
                                    class="block px-4 py-3 text-sm font-bold uppercase tracking-wider text-[var(--ui-text-muted)] text-[var(--ui-text-muted)] hover:bg-[var(--ui-surface-soft)] hover:text-[var(--color-primary)] transition-colors"
                                    @click="showServiciosMenu = false"
                                >
                                    {{ sLink.name }}
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <!-- Dropdown de Productos -->
                    <div v-else-if="link.id === 'tienda'" class="relative" v-click-outside="() => showProductosMenu = false">
                        <button 
                            @mousedown="showProductosMenu = !showProductosMenu"
                            :class="[
                                'flex items-center gap-1 text-sm font-bold transition-all uppercase tracking-wide pb-1',
                                (isProductosActive || showProductosMenu)
                                    ? 'text-[var(--ui-text)] border-b-2 border-[var(--color-primary)]' 
                                    : 'text-[var(--ui-text-soft)] hover:text-[var(--color-primary)]'
                            ]"
                        >
                            {{ link.name }}
                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showProductosMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                            <div v-if="showProductosMenu" class="absolute left-0 mt-2 w-56 bg-[var(--ui-surface)] rounded-xl shadow-xl border border-[var(--ui-border)] py-2 z-50">
                                <Link 
                                    v-for="pLink in productosLinks" 
                                    :key="pLink.id"
                                    :href="route(pLink.route, pLink.params || {})" 
                                    class="block px-4 py-3 text-sm font-bold uppercase tracking-wider transition-colors"
                                    :class="pLink.highlighted ? 'text-[var(--color-primary)] bg-[var(--color-primary)]/5 hover:bg-[var(--color-primary)]/10' : 'text-[var(--ui-text-muted)] text-[var(--ui-text-muted)] hover:bg-[var(--ui-surface-soft)] hover:text-[var(--color-primary)]'"
                                    @click="showProductosMenu = false"
                                >
                                    {{ pLink.name }}
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <!-- Links Normales -->
                    <Link 
                        v-else-if="link.route"
                        :href="route(link.route)" 
                        :class="[
                            'text-sm font-bold transition-all uppercase tracking-wide pb-1',
                            activeTab === link.id 
                                ? 'text-[var(--ui-text)] border-b-2 border-[var(--color-primary)]' 
                                : 'text-[var(--ui-text-soft)] hover:text-[var(--color-primary)]'
                        ]"
                    >
                        {{ link.name }}
                    </Link>
                </template>

                <div class="h-6 w-px bg-[var(--ui-border)] ml-2"></div>

                <!-- User Actions -->
                <div class="flex items-center gap-4">
                     <!-- Dark Mode Toggle -->
                    <button 
                        @click="toggleDarkMode" 
                        class="relative z-20 p-2.5 mr-2 rounded-xl text-[var(--ui-text-soft)] hover:text-[var(--color-primary)] hover:bg-[var(--ui-surface-soft)] transition-all focus:outline-none active:scale-90 cursor-pointer"
                        :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                        type="button"
                    >
                        <Transition name="rotate-icon" mode="out-in">
                            <svg v-if="isDarkMode" class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-[var(--ui-text-soft)] hover:text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </Transition>
                    </button>

                    <!-- Cart Icon -->
                    <Link :href="route('tienda.carrito')" class="relative p-2.5 bg-[var(--ui-surface-soft)] rounded-xl text-[var(--ui-text-soft)] dark:text-[var(--ui-text-muted)] hover:text-[var(--color-primary)] hover:bg-white dark:hover:bg-white/10 hover:shadow-sm transition-all group/cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-if="itemCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-[var(--color-primary)] rounded-full text-[10px] font-black text-white flex items-center justify-center border-2 border-white">
                            {{ itemCount > 9 ? '9+' : itemCount }}
                        </span>
                    </Link>

                    <div class="h-6 w-px bg-[var(--ui-border)]"></div>

                    <div v-if="currentUser" class="flex items-center gap-4">
                        <div class="relative" v-click-outside="() => showUserMenu = false">
                            <button 
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2 px-4 py-2.5 bg-[var(--color-primary)] text-white rounded-xl font-bold text-xs uppercase tracking-wide hover:shadow-lg hover:shadow-[var(--color-primary)]/20 transition-all"
                            >
                                <span class="hidden sm:inline">{{ currentUser.nombre_razon_social?.split(' ')[0] || currentUser.name?.split(' ')[0] || 'Hola' }}</span>
                                <font-awesome-icon icon="user-circle" class="sm:hidden" />
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showUserMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-[var(--ui-surface)] rounded-xl shadow-xl border border-[var(--ui-border)] py-2 z-50">
                                    <Link 
                                        :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" 
                                        class="flex items-center gap-3 px-4 py-3 text-sm text-[var(--ui-text-muted)] text-[var(--ui-text-muted)] hover:bg-[var(--ui-surface-soft)] transition-colors"
                                        @click="showUserMenu = false"
                                    >
                                        <svg class="w-4 h-4 text-[var(--ui-text-soft)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Mi Panel
                                    </Link>
                                    <div class="border-t border-[var(--ui-border)] my-1"></div>
                                    <button 
                                        @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-sm text-rose-600 hover:bg-rose-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Cerrar Sesión
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-4">
                        <Link :href="route('portal.login')" class="px-5 py-2.5 bg-[var(--color-primary)] text-white rounded-xl font-black text-xs uppercase tracking-wide hover:shadow-lg hover:shadow-[var(--color-primary)]/30 transition-all">
                            Ingresar
                        </Link>
                        <a href="/login" class="ml-2 text-[10px] font-black uppercase tracking-wide text-[var(--ui-text-muted)] hover:text-[var(--ui-text-soft)]" title="Acceso Administrativo">
                            Staff
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile Actions -->
            <div class="md:hidden flex items-center gap-3">
                 <button 
                    @click="toggleDarkMode" 
                    class="p-2 text-[var(--ui-text-soft)] dark:text-[var(--ui-text-muted)] focus:outline-none active:scale-90 transition-transform relative z-20"
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

                 <Link :href="route('tienda.carrito')" class="relative p-2 text-[var(--ui-text-soft)] dark:text-[var(--ui-text-muted)]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span v-if="itemCount > 0" class="absolute top-0 right-0 w-4 h-4 bg-[var(--color-primary)] rounded-full text-[8px] font-black text-white flex items-center justify-center border border-white">
                        {{ itemCount }}
                    </span>
                </Link>

                <button 
                    type="button"
                    @click="showMobileMenu = true" 
                    class="w-12 h-12 flex items-center justify-center rounded-2xl bg-[var(--ui-surface-soft)] text-[var(--ui-text)] transition-all active:scale-90 relative z-[40] border border-[var(--ui-border)] shadow-sm"
                    aria-label="Open Menu"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 8h16M4 16h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (Teleported) -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showMobileMenu" class="fixed inset-0 z-[9999] md:hidden overflow-hidden">
                    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xl" @click="showMobileMenu = false"></div>
                    
                    <Transition name="drawer-slide" appear>
                        <div v-if="showMobileMenu" class="absolute right-0 top-0 bottom-0 w-[85%] max-w-[360px] bg-[var(--ui-surface)] shadow-[0_0_80px_rgba(0,0,0,0.5)] flex flex-col border-l border-[var(--ui-border)]">
                            <div class="p-6 flex justify-between items-center border-b border-[var(--ui-border)]">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[var(--color-primary)] flex items-center justify-center text-white font-black text-sm">C</div>
                                    <span class="text-xs font-black uppercase tracking-[0.2em] text-[var(--ui-text)]">Menú</span>
                                </div>
                                <button @click="showMobileMenu = false" class="w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--ui-surface-soft)] text-[var(--ui-text)] transition-all active:scale-90">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-grow overflow-y-auto px-8 py-8 space-y-2 custom-scrollbar">
                                <template v-for="link in navLinks" :key="link.id">
                                    <div v-if="link.dropdown" class="mb-2">
                                        <button 
                                            @click="link.id === 'servicios' ? showMobileServicios = !showMobileServicios : showMobileProductos = !showMobileProductos"
                                            class="w-full flex justify-between items-center py-5 text-2xl font-black uppercase tracking-wide text-[var(--ui-text)] border-b border-[var(--ui-border)]"
                                        >
                                            {{ link.name }}
                                            <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180 text-[var(--color-primary)]': (link.id === 'servicios' ? showMobileServicios : showMobileProductos) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Mobile Servicios -->
                                        <Transition
                                            v-if="link.id === 'servicios'"
                                            enter-active-class="transition-all duration-300 ease-out"
                                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                            enter-to-class="max-h-[500px] opacity-100"
                                            leave-active-class="transition-all duration-200 ease-in"
                                            leave-from-class="max-h-[500px] opacity-100"
                                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                        >
                                            <div v-if="showMobileServicios" class="mt-4 bg-[var(--ui-surface-soft)] rounded-3xl overflow-hidden border border-[var(--ui-border)]">
                                                <Link 
                                                    v-for="sLink in serviciosLinks" 
                                                    :key="sLink.id"
                                                    :href="route(sLink.route, sLink.params || {})"
                                                    class="block px-6 py-4 text-base font-bold text-slate-600text-[var(--ui-text-soft)] border-b border-[var(--ui-border)] last:border-0 active:bg-[var(--color-primary)] active:text-white"
                                                    @click="showMobileMenu = false"
                                                >
                                                    {{ sLink.name }}
                                                </Link>
                                            </div>
                                        </Transition>

                                        <!-- Mobile Productos -->
                                        <Transition
                                            v-if="link.id === 'tienda'"
                                            enter-active-class="transition-all duration-300 ease-out"
                                            enter-from-class="max-h-0 opacity-0 overflow-hidden"
                                            enter-to-class="max-h-[500px] opacity-100"
                                            leave-active-class="transition-all duration-200 ease-in"
                                            leave-from-class="max-h-[500px] opacity-100"
                                            leave-to-class="max-h-0 opacity-0 overflow-hidden"
                                        >
                                            <div v-if="showMobileProductos" class="mt-4 bg-[var(--ui-surface-soft)] rounded-3xl overflow-hidden border border-[var(--ui-border)]">
                                                <Link 
                                                    v-for="pLink in productosLinks" 
                                                    :key="pLink.id"
                                                    :href="route(pLink.route, pLink.params || {})"
                                                    class="block px-6 py-4 text-base font-bold border-b border-[var(--ui-border)] last:border-0 active:bg-[var(--color-primary)] active:text-white"
                                                    :class="pLink.highlighted ? 'text-[var(--color-primary)] bg-[var(--color-primary)]/5' : 'text-slate-600text-[var(--ui-text-soft)]'"
                                                    @click="showMobileMenu = false"
                                                >
                                                    {{ pLink.name }}
                                                </Link>
                                            </div>
                                        </Transition>
                                    </div>
                                    <Link 
                                        v-else
                                        :href="route(link.route)"
                                        class="block py-5 text-2xl font-black uppercase tracking-wide text-[var(--ui-text)] border-b border-[var(--ui-border)]"
                                        :class="{ 'text-[var(--color-primary)]': activeTab === link.id }"
                                        @click="showMobileMenu = false"
                                    >
                                        {{ link.name }}
                                    </Link>
                                </template>
                            </div>
                            
                            <!-- Auth Mobile -->
                            <div class="p-8 bg-[var(--ui-surface-alt)] rounded-t-[3rem] border-t border-[var(--ui-border)]">
                                <div v-if="!currentUser" class="grid grid-cols-1 gap-4">
                                    <Link :href="route('portal.login')" class="py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase text-[10px] tracking-wide shadow-lg shadow-[var(--color-primary)]/30" @click="showMobileMenu = false">Ingresar</Link>
                                </div>
                                <div v-else class="space-y-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[var(--color-primary)] to-brand-500 flex items-center justify-center text-white font-black shadow-lg">
                                            {{ (currentUser.nombre_razon_social || currentUser.name || 'C').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[10px] font-black text-[var(--ui-text-soft)] uppercase tracking-wide leading-none mb-1">Usuario</p>
                                            <p class="font-black text-[var(--ui-text)] truncate">{{ currentUser.nombre_razon_social || currentUser.name }}</p>
                                        </div>
                                    </div>
                                    <Link :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" class="block w-full py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase text-[10px] tracking-wide" @click="showMobileMenu = false">Mi Portal</Link>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </nav>

    <!-- Modal de Autorización Pendiente -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showAuthModal" class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showAuthModal = false"></div>
                <div class="relative bg-[var(--ui-surface)] rounded-[2.5rem] shadow-2xl max-w-md w-full p-10 text-center border border-white/10 overflow-hidden animate-scale-in">
                    <div class="w-16 h-16 bg-sky-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-blue-600 text-3xl shadow-lg shadow-blue-100">
                        <font-awesome-icon icon="shopping-bag" />
                    </div>
                    <h3 class="text-3xl font-black text-[var(--ui-text)] mb-4 tracking-tight">¡Bienvenido!</h3>
                    <div class="text-[var(--ui-text-soft)] font-medium mb-8 leading-relaxed text-sm space-y-4">
                        <p>Tu cuenta ha sido creada y <strong class="text-emerald-600">puedes realizar compras ahora mismo.</strong></p>
                        <div class="bg-yellow-50 dark:bg-white/5 p-4 rounded-xl border border-yellow-100 dark:border-white/5 text-yellow-800 dark:text-yellow-200 text-xs">
                            <strong class="block mb-1 text-yellow-900 dark:text-yellow-100">Nota sobre el Panel:</strong>
                            Tu acceso al área de Soporte está en revisión.
                        </div>
                    </div>
                    <button @click="showAuthModal = false" class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase tracking-wide text-xs">Continuar Comprando</button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.rotate-icon-enter-active, .rotate-icon-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.rotate-icon-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.5); }
.rotate-icon-leave-to { opacity: 0; transform: rotate(90deg) scale(0.5); }

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
