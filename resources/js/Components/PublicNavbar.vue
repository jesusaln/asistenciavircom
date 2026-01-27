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
    { name: 'Contacto', route: 'public.contacto', id: 'contacto' },
    { name: 'Soporte', route: 'portal.dashboard', id: 'soporte' },
];

const serviciosLinks = [
    { name: 'Pólizas de Soporte', route: 'catalogo.polizas', id: 'polizas' },
    { name: 'Renta de Equipos', route: 'catalogo.rentas', id: 'rentas' },
    { name: 'Cámaras y CCTV', route: 'public.servicio.show', params: { slug: 'camaras-cctv' }, id: 'cctv' },
    { name: 'Control de Acceso', route: 'public.servicio.show', params: { slug: 'control-acceso' }, id: 'acceso' },
    { name: 'Alarmas y Seguridad', route: 'public.servicio.show', params: { slug: 'alarmas-seguridad' }, id: 'alarmas' },
    { name: 'Puntos de Venta (POS)', route: 'public.servicio.show', params: { slug: 'punto-de-venta' }, id: 'pos' },
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
            <div class="hidden md:flex items-center gap-8">
                <template v-for="link in navLinks" :key="link.id">
                    <!-- Link Especial para Soporte -->
                    <Link 
                        v-if="link.id === 'soporte'"
                        :href="route(link.route)"
                        class="px-5 py-2.5 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-xl font-black text-xs uppercase tracking-widest hover:bg-[var(--color-primary)] hover:text-white transition-all"
                    >
                        {{ link.name }}
                    </Link>

                    <!-- Dropdown de Servicios -->
                    <div v-else-if="link.dropdown" class="relative" v-click-outside="() => showServiciosMenu = false">
                        <button 
                            @mousedown="showServiciosMenu = !showServiciosMenu"
                            :class="[
                                'flex items-center gap-1 text-sm font-bold transition-all uppercase tracking-widest pb-1',
                                (isServiciosActive || showServiciosMenu)
                                    ? 'text-gray-900 dark:text-white border-b-2 border-[var(--color-primary)]' 
                                    : 'text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)]'
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
                            <div v-if="showServiciosMenu" class="absolute left-0 mt-2 w-48 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-gray-100 dark:border-slate-800 py-2 z-50">
                                <Link 
                                    v-for="sLink in serviciosLinks" 
                                    :key="sLink.id"
                                    :href="route(sLink.route, sLink.params || {})" 
                                    class="block px-4 py-3 text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-800 hover:text-[var(--color-primary)] transition-colors"
                                    @click="showServiciosMenu = false"
                                >
                                    {{ sLink.name }}
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <!-- Links Normales -->
                    <Link 
                        v-else-if="link.route"
                        :href="route(link.route)" 
                        :class="[
                            'text-sm font-bold transition-all uppercase tracking-widest pb-1',
                            activeTab === link.id 
                                ? 'text-gray-900 dark:text-white border-b-2 border-[var(--color-primary)]' 
                                : 'text-gray-500 dark:text-gray-400 hover:text-[var(--color-primary)]'
                        ]"
                    >
                        {{ link.name }}
                    </Link>
                </template>

                <div class="h-6 w-px bg-gray-200 ml-2"></div>

                <!-- User Actions -->
                <div class="flex items-center gap-4">
                     <!-- Dark Mode Toggle -->
                    <!-- Dark Mode Toggle Button Desktop -->
                    <button 
                        @click="toggleDarkMode" 
                        class="relative z-20 p-2.5 mr-2 rounded-xl text-gray-400 hover:text-[var(--color-primary)] hover:bg-gray-50 dark:hover:bg-gray-800 transition-all focus:outline-none active:scale-90 cursor-pointer"
                        :title="isDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
                        type="button"
                    >
                        <Transition name="rotate-icon" mode="out-in">
                            <!-- Icono Sol (para cuando está oscuro -> ir a claro) -->
                            <svg v-if="isDarkMode" class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Icono Luna (para cuando está claro -> ir a oscuro) -->
                            <svg v-else class="w-6 h-6 text-gray-400 hover:text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </Transition>
                    </button>



                    <!-- Cart Icon -->
                    <Link :href="route('tienda.carrito')" class="relative p-2.5 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:text-[var(--color-primary)] hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700 hover:shadow-sm transition-all group/cart">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span v-if="itemCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-[var(--color-primary)] rounded-full text-[10px] font-black text-white flex items-center justify-center border-2 border-white">
                            {{ itemCount > 9 ? '9+' : itemCount }}
                        </span>
                    </Link>

                    <div class="h-6 w-px bg-gray-200"></div>

                    <div v-if="currentUser" class="flex items-center gap-4">
                        <!-- Dropdown Menu para Usuario -->
                        <div class="relative" v-click-outside="() => showUserMenu = false">
                            <button 
                                @click="showUserMenu = !showUserMenu"
                                class="flex items-center gap-2 px-4 py-2.5 bg-[var(--color-primary)] text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:shadow-lg hover:shadow-[var(--color-primary)]/20 transition-all"
                            >
                                <span class="hidden sm:inline">{{ currentUser.nombre_razon_social?.split(' ')[0] || currentUser.name?.split(' ')[0] || 'Hola' }}</span>
                                <span class="sm:hidden">👤</span>
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showUserMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <!-- Dropdown -->
                            <Transition
                                enter-active-class="transition-all duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition-all duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2"
                            >
                                <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-slate-800 dark:border-gray-700 py-2 z-50">
                                    <Link 
                                        :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" 
                                        class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                                        @click="showUserMenu = false"
                                    >
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Mi Panel
                                    </Link>
                                    <div class="border-t border-gray-100 dark:border-slate-800 dark:border-gray-700 my-1"></div>
                                    <button 
                                        @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors"
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
                        <Link :href="route('portal.login')" class="text-sm font-bold text-gray-500 dark:text-gray-400 dark:text-gray-300 hover:text-[var(--color-primary)] transition-all uppercase tracking-widest">
                            Ingresar
                        </Link>
                        <Link :href="route('portal.register')" class="px-5 py-2.5 bg-[var(--color-primary-soft)] text-[var(--color-primary)] rounded-xl font-black text-xs uppercase tracking-widest hover:bg-[var(--color-primary)] hover:text-white transition-all">
                            Registro
                        </Link>
                        <!-- Staff Shortcut -->
                        <a href="/login" class="ml-2 text-[10px] font-black uppercase tracking-widest text-gray-300 hover:text-gray-400" title="Acceso Administrativo">
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
                <button @click="showMobileMenu = !showMobileMenu" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white transition-colors relative z-50">
                    <svg v-if="!showMobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-full"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-full"
        >
            <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-white dark:bg-slate-950 md:hidden">
                <div class="pt-24 pb-8 px-6 space-y-4 h-full overflow-y-auto">
                    <div v-for="link in navLinks" :key="link.id">
                        <template v-if="link.dropdown">
                            <button 
                                @click="showServiciosMenu = !showServiciosMenu"
                                class="w-full flex justify-between items-center py-4 text-xl font-black uppercase tracking-tighter text-gray-900 dark:text-white border-b border-gray-100 dark:border-slate-800"
                            >
                                {{ link.name }}
                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showServiciosMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div v-if="showServiciosMenu" class="bg-gray-50 dark:bg-slate-900/50 rounded-2xl mt-2 overflow-hidden">
                                <Link 
                                    v-for="sLink in serviciosLinks" 
                                    :key="sLink.id"
                                    :href="route(sLink.route, sLink.params || {})"
                                    class="block px-6 py-4 text-lg font-bold text-gray-600 dark:text-gray-400 border-b border-white dark:border-slate-800 last:border-0"
                                    @click="showMobileMenu = false"
                                >
                                    {{ sLink.name }}
                                </Link>
                            </div>
                        </template>
                        <Link 
                            v-else
                            :href="route(link.route)"
                            class="block py-4 text-xl font-black uppercase tracking-tighter text-gray-900 dark:text-white border-b border-gray-100 dark:border-slate-800"
                            @click="showMobileMenu = false"
                        >
                            {{ link.name }}
                        </Link>
                    </div>
                    
                    <!-- Auth Mobile -->
                    <div class="pt-8 space-y-4">
                        <Link v-if="!currentUser" :href="route('portal.login')" class="block w-full py-4 text-center bg-gray-100 dark:bg-slate-800 rounded-2xl font-black uppercase" @click="showMobileMenu = false">Ingresar</Link>
                        <Link v-if="!currentUser" :href="route('portal.register')" class="block w-full py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase" @click="showMobileMenu = false">Registro</Link>
                        <div v-else class="space-y-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Hola, {{ currentUser.nombre_razon_social?.split(' ')[0] || currentUser.name?.split(' ')[0] }}</p>
                            <Link :href="route(currentUser.tipo === 'cliente' ? 'portal.dashboard' : 'dashboard')" class="block w-full py-4 text-center bg-[var(--color-primary)] text-white rounded-2xl font-black uppercase" @click="showMobileMenu = false">Mi Panel</Link>
                            <button @click="logout(); showMobileMenu = false" class="block w-full py-4 text-center bg-red-50 text-red-600 rounded-2xl font-black uppercase">Cerrar Sesión</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </nav>

    <!-- Modal de Autorización Pendiente -->
    <div v-if="showAuthModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showAuthModal = false"></div>
        
        <!-- Modal Content -->
        <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-md w-full p-8 text-center overflow-hidden transform transition-all scale-100 animate-fade-in-up">
            <div class="w-16 h-16 bg-blue-100 rounded-2xl flex items-center justify-center mx-auto mb-6 text-blue-600 text-3xl shadow-lg shadow-blue-100">
                🛍️
            </div>
            <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-3 tracking-tight">¡Bienvenido!</h3>
            <div class="text-gray-500 dark:text-gray-400 font-medium mb-8 leading-relaxed text-sm space-y-4">
                <p>
                    Tu cuenta ha sido creada y <strong class="text-green-600">puedes realizar compras en la tienda ahora mismo sin ningún problema.</strong>
                </p>
                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100 text-yellow-800 text-xs">
                    <strong class="block mb-1 text-yellow-900">Nota sobre el Panel:</strong>
                    Tu acceso al área de Soporte y Facturación (Mi Panel) está en revisión. Te avisaremos cuando sea aprobado.
                </div>
            </div>
            <div class="grid grid-cols-1 gap-3">
                 <button 
                    @click="showAuthModal = false"
                    class="w-full py-4 bg-[var(--color-primary)] text-white rounded-xl font-black text-xs uppercase tracking-widest hover:shadow-lg hover:shadow-[var(--color-primary)]/20 transition-all"
                >
                    Continuar Comprando
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rotate-icon-enter-active,
.rotate-icon-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.rotate-icon-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.5);
}

.rotate-icon-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.5);
}
</style>
