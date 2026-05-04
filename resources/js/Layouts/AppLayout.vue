<template>
    <Head :title="title" />
    <div class="flex flex-col h-screen bg-[var(--ui-surface-alt)] transition-colors duration-500 overflow-hidden">
        <!-- Navigation Bar (Premium Glassmorphism) -->
        <nav v-if="!hideNavigation" class="glass-panel relative z-30 shadow-lg">
            <div class="px-6 py-4">
                <div class="flex justify-between items-center">
                    <!-- Brand / Logo -->
                    <Link :href="route('dashboard')" class="flex items-center gap-4 group">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[var(--ui-accent)] to-amber-600 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-500">
                             <img v-if="empresaConfigShared?.logo_url" :src="empresaConfigShared.logo_url" class="h-6 w-auto object-contain brightness-0 invert" :alt="empresaConfigShared.nombre_empresa">
                             <span v-else class="text-[var(--ui-accent-contrast)] text-xl font-black">V</span>
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-[0.2em] leading-tight">
                                {{ empresaConfigShared?.nombre_empresa || 'SISTEMA' }}
                            </h1>
                            <p class="text-[9px] font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-0.5">Gestión Inteligente</p>
                        </div>
                    </Link>

                    <!-- Center Area: Greeting (Refined) -->
                    <div v-if="usuario" class="hidden md:flex items-center bg-[var(--ui-surface-soft)] px-6 py-2 rounded-2xl border border-[var(--ui-border)] group hover:border-[var(--ui-accent)]/30 transition-all duration-500">
                        <span class="text-xs font-bold text-[var(--ui-text-muted)]">
                            {{ getGreeting() }}, 
                            <span class="text-[var(--ui-text)] font-black uppercase tracking-wider ml-1 group-hover:text-[var(--ui-accent)] transition-colors">{{ usuario?.name || 'Usuario' }}</span>
                        </span>
                    </div>

                    <div class="flex items-center gap-5">
                        <!-- Return to Landing Page Button -->
                        <a href="/" class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] hover:border-[var(--ui-accent)] hover:text-[var(--ui-accent)] transition-all duration-300 shadow-sm group" title="Ir a la página principal">
                            <svg class="w-4 h-4 text-[var(--ui-text-soft)] group-hover:text-[var(--ui-accent)] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="text-xs font-bold text-[var(--ui-text-muted)] group-hover:text-[var(--ui-accent)] uppercase tracking-wider transition-colors">Volver a la Web</span>
                        </a>

                        <!-- Offline/Sync Status Indicator -->
                        <div v-if="!isOnline || pendingSyncCount > 0" class="flex items-center gap-2 px-3 py-1.5 rounded-full border transition-all duration-500"
                             :class="!isOnline ? 'bg-rose-500/10 border-rose-500/20 text-rose-500' : 'bg-amber-500/10 border-amber-500/20 text-amber-500'">
                            <div class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="!isOnline ? 'bg-rose-400' : 'bg-amber-400'"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2" :class="!isOnline ? 'bg-rose-500' : 'bg-amber-500'"></span>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-widest">
                                {{ !isOnline ? 'Sin Conexión' : `Sincronizando (${pendingSyncCount})` }}
                            </span>
                        </div>

                        <!-- Notifications -->
                        <div class="relative group">
                            <NotificationBell
                                :auto-refresh="true"
                                :refresh-interval="30000"
                                @notification-clicked="handleNotificationClick"
                                class="transform group-hover:scale-110 transition-transform duration-300"
                            />
                        </div>

                        <!-- Online Users -->
                        <div v-if="onlineUsers.length > 0" class="flex items-center bg-[var(--ui-surface-soft)] px-3 py-1.5 rounded-2xl border border-[var(--ui-border)] gap-3 shadow-sm min-w-fit">
                            <div class="flex -space-x-2.5">
                                <template v-for="user in onlineUsers.slice(0, 3)" :key="user.id">
                                    <img 
                                        v-if="user.profile_photo_url || user.avatar"
                                        :src="user.profile_photo_url || user.avatar" 
                                        :title="user.name"
                                        class="w-8 h-8 rounded-full border-2 border-[var(--ui-surface)] object-cover shadow-sm transition-transform hover:scale-110 hover:z-10"
                                    >
                                    <div v-else 
                                        class="w-8 h-8 rounded-full border-2 border-[var(--ui-surface)] bg-gradient-to-br from-[var(--ui-accent)] to-amber-500 flex items-center justify-center text-[10px] font-black text-white shadow-sm"
                                        :title="user.name"
                                    >
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </template>
                            </div>
                            <div class="flex flex-col -space-y-1">
                                <span class="text-[9px] font-black text-[var(--ui-accent)] uppercase tracking-tighter">Activos</span>
                                <span class="text-[10px] font-bold text-[var(--ui-text)]">{{ onlineUsers.length }}</span>
                            </div>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button
                            @click="toggleDarkMode"
                            class="relative w-12 h-12 rounded-2xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] flex items-center justify-center hover:bg-[var(--ui-surface)] hover:border-[var(--ui-accent)]/30 transition-all duration-300 group shadow-sm active:scale-90"
                            :title="isDarkMode ? 'Modo Lumínico' : 'Modo Nocturno'"
                        >
                            <div class="relative overflow-hidden w-6 h-6 flex items-center justify-center">
                                <svg v-if="isDarkMode" class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/>
                                </svg>
                                <svg v-else class="w-6 h-6 text-[var(--ui-text-soft)] group-hover:text-[var(--ui-accent)]" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/>
                                </svg>
                            </div>
                        </button>

                        <div class="w-px h-8 bg-[var(--ui-border)] mx-1"></div>

                        <!-- Profile Dropdown -->
                        <div v-if="usuario" class="relative" ref="profileContainer">
                            <button
                                @click="toggleProfileDropdown"
                                class="flex items-center gap-3 p-1 rounded-2xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] hover:border-[var(--ui-accent)]/30 transition-all duration-300 group active:scale-95"
                            >
                                <img
                                    :src="usuario?.profile_photo_url || 'https://ui-avatars.com/api/?name=' + (usuario?.name || 'User')"
                                    alt="User"
                                    class="h-9 w-9 rounded-xl object-cover border-2 border-[var(--ui-surface)] group-hover:border-[var(--ui-accent)] transition-colors"
                                />
                                <div class="hidden sm:block text-left mr-2">
                                    <p class="text-[10px] font-black text-[var(--ui-text)] uppercase tracking-tight leading-none">{{ String(usuario?.name || '').split(' ')[0] || 'Usuario' }}</p>
                                    <p class="text-[9px] font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">Miembro</p>
                                </div>
                                <svg class="h-4 w-4 text-[var(--ui-text-soft)] transition-transform duration-300 mr-2" :class="{ 'rotate-180': isProfileDropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="transform opacity-0 scale-95 -translate-y-2"
                                enter-to-class="transform opacity-100 scale-100 translate-y-0"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="transform opacity-100 scale-100 translate-y-0"
                                leave-to-class="transform opacity-0 scale-95 -translate-y-2"
                            >
                                <div v-if="isProfileDropdownOpen" class="absolute right-0 mt-4 w-72 glass-panel rounded-[2.5rem] shadow-[0_30px_60px_-15px_rgba(0,0,0,0.3)] z-[100] overflow-hidden">
                                    <!-- User Header in Dropdown -->
                                    <div class="px-8 py-8 bg-gradient-to-br from-[var(--ui-surface-soft)] to-[var(--ui-surface)] border-b border-[var(--ui-border)]">
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-16 rounded-[1.5rem] bg-gradient-to-br from-[var(--ui-accent)] to-amber-600 p-1">
                                                <img :src="usuario?.profile_photo_url || 'https://ui-avatars.com/api/?name=' + (usuario?.name || 'User')" class="w-full h-full rounded-[1.2rem] object-cover border-2 border-white/20">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-sm font-black text-[var(--ui-text)] uppercase tracking-tight truncate">{{ usuario?.name || 'Usuario' }}</h4>
                                                <p class="text-[10px] font-bold text-[var(--ui-text-soft)] truncate mt-0.5">{{ usuario.email }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-3">
                                        <Link :href="route('perfil')" class="flex items-center gap-4 px-5 py-4 text-xs font-black uppercase tracking-widest text-[var(--ui-text-muted)] hover:bg-[var(--ui-accent)]/5 hover:text-[var(--ui-accent)] rounded-2xl transition-all duration-300 group">
                                            <div class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] flex items-center justify-center group-hover:bg-[var(--ui-accent)]/10 transition-colors">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            Mi Perfil Maestro
                                        </Link>
                                        <Link :href="route('empresa-configuracion.index')" class="flex items-center gap-4 px-5 py-4 text-xs font-black uppercase tracking-widest text-[var(--ui-text-muted)] hover:bg-amber-500/5 hover:text-amber-500 rounded-2xl transition-all duration-300 group">
                                            <div class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] flex items-center justify-center group-hover:bg-amber-500/10 transition-colors">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            Configuración
                                        </Link>
                                    </div>

                                    <div class="p-3 bg-[var(--ui-surface-alt)]">
                                        <button @click="logout" class="flex items-center gap-4 w-full px-5 py-5 text-xs font-black uppercase tracking-[0.2em] text-rose-500 hover:bg-rose-500 hover:text-white rounded-3xl transition-all duration-500 active:scale-95 shadow-lg shadow-rose-500/0 hover:shadow-rose-500/20">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Finalizar Sesión
                                        </button>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex flex-1 relative overflow-hidden">
            <Sidebar v-if="!hideNavigation" :isSidebarCollapsed="isSidebarCollapsed" :usuario="usuario" :isMobile="isMobile" @toggleSidebar="toggleSidebar" />

            <main
                :class="[
                    !hideNavigation ? {'ml-64': !isSidebarCollapsed, 'ml-20': isSidebarCollapsed} : 'ml-0',
                    'flex-1 overflow-y-auto transition-all duration-300 ease-in-out bg-[var(--ui-surface-alt)]'
                ]"
            >
                <div v-if="$slots.header" class="p-6 pb-0">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-slate-950/40 backdrop-blur-md flex items-center justify-center z-[100]" aria-modal="true" role="dialog">
            <div class="glass-panel rounded-[2.5rem] p-10 flex flex-col items-center space-y-4 shadow-2xl border border-[var(--ui-border)] transition-all animate-scale-in">
                <div class="relative w-12 h-12">
                    <div class="absolute inset-0 border-4 border-[var(--ui-accent)]/20 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-t-[var(--ui-accent)] rounded-full animate-spin"></div>
                </div>
                <span class="text-xs font-black text-[var(--ui-text)] uppercase tracking-[0.2em] animate-pulse">Cargando Sistema</span>
            </div>
        </div>

        <!-- Toast Notifications -->
        <ToastContainer ref="toastRef" />
        
        <!-- System Update Notification -->
        <NewVersionNotification />

        <!-- Global Error Modal -->
        <SystemErrorModal 
            :show="showErrorModal" 
            :error="errorMessage" 
            @close="showErrorModal = false"
        />
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import NotificationBell from '@/Components/Notifications/NotificationBell.vue';
import ToastContainer from '@/Components/Toast/ToastContainer.vue';
import NewVersionNotification from '@/Components/System/NewVersionNotification.vue';
import SystemErrorModal from '@/Components/System/SystemErrorModal.vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faCalendar, faWrench, faTools, faCarAlt, faChartBar, faCartShopping,
    faCircle, faHome, faUsers, faBox, faTags, faTrademark, faTruck,
    faWarehouse, faFileAlt, faTruckLoading, faDollarSign, faUser,
    faCalculator, faHandHoldingUsd, faWallet, faShieldHalved,
    faCircleInfo, faCheckCircle, faMoneyBillWave, faCalendarAlt, faBriefcase,
    faArrowLeft, faSyncAlt, faBolt, faCheckDouble, faCommentDots, faComments,
    faFilePdf, faIdCard, faInbox, faMicrophoneAlt, faPaperclip, faPhone,
    faRobot, faLock, faLockOpen, faPaperPlane, faSmile, faStickyNote,
    faTimes, faTrash, faUserCircle, faCheck, faClipboardCheck
} from '@fortawesome/free-solid-svg-icons';
import { ref, onMounted, onBeforeUnmount, nextTick, computed, watch } from 'vue';
import { useDarkMode } from '@/Utils/useDarkMode';
import { router } from '@inertiajs/vue3';
import { OfflineService } from '@/Services/OfflineService';
import axios from 'axios';

// --- Offline & Sync Logic ---
const isOnline = ref(navigator.onLine);
const pendingSyncCount = ref(0);
const isSyncing = ref(false);
let queueInterval = null;

const updateOnlineStatus = () => {
    isOnline.value = navigator.onLine;
    if (isOnline.value) processOfflineQueue();
};

const checkQueue = async () => {
    pendingSyncCount.value = (await OfflineService.getQueue()).length;
};

const processOfflineQueue = async () => {
    if (isSyncing.value || !isOnline.value) return;
    
    const queue = await OfflineService.getQueue();
    if (queue.length === 0) return;

    isSyncing.value = true;
    for (const item of queue) {
        try {
            await new Promise((resolve, reject) => {
                router.post(route('citas.update', item.cita_id), item.payload, {
                    forceFormData: true,
                    onSuccess: () => resolve(),
                    onError: (err) => reject(err),
                    preserveScroll: true,
                });
            });
            await OfflineService.dequeueReport(item.id);
        } catch (err) {
            console.error(`[OfflineSync] Fail #${item.cita_id}`, err);
        }
    }
    await checkQueue();
    isSyncing.value = false;
};

// --- Font Awesome Icon Configuration ---
library.add(
    faCalendar, faWrench, faTools, faCarAlt, faChartBar, faCartShopping,
    faCircle, faHome, faUsers, faBox, faTags, faTrademark, faTruck,
    faWarehouse, faFileAlt, faTruckLoading, faDollarSign, faUser,
    faCalculator, faWallet, faShieldHalved,
    faCircleInfo, faCheckCircle, faMoneyBillWave, faCalendarAlt, faBriefcase,
    faArrowLeft, faSyncAlt, faBolt, faCheckDouble, faCommentDots, faComments,
    faFilePdf, faIdCard, faInbox, faMicrophoneAlt, faPaperclip, faPhone,
    faRobot, faLock, faLockOpen, faPaperPlane, faSmile, faStickyNote,
    faTimes, faTrash, faUserCircle, faCheck, faClipboardCheck
);

// --- Props ---
const props_received = defineProps({
    title: {
        type: String,
        default: ''
    },
    hideNavigation: {
        type: Boolean,
        default: false
    }
});

// --- Reactive States ---
const { props } = usePage();
const usuario = computed(() => props.auth?.user);
const onlineUsers = ref([]);
const isProfileDropdownOpen = ref(false);
const isSidebarCollapsed = ref(false);
const isMobile = ref(false);
const isLoading = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');
// --- Dark Mode Logic ---
const { isDarkMode, toggleDarkMode, updateThemeColors } = useDarkMode(props.empresa_config);

// Configuración de empresa compartida (Inertia)
const empresaConfigShared = computed(() => props.empresa_config);

// Sincronizar colores si cambia la config
watch(() => props.empresa_config, (newConfig) => {
    if (newConfig) updateThemeColors(newConfig);
}, { deep: true });

// Cargar configuración de empresa (API para datos extendidos si es admin)
const empresaConfigExtended = ref({
  nombre_empresa: 'CDD Sistema',
  color_principal: '#F59E0B',
  color_secundario: '#D97706',
  logo_url: null,
});

const cargarConfiguracionEmpresa = async () => {
  try {
    const response = await axios.get('/empresa/configuracion/api');
    empresaConfigExtended.value = response.data.configuracion;
  } catch (error) {
    console.error('Error al cargar configuración de empresa:', error);
  }
};

// --- DOM References ---
const profileContainer = ref(null);

/**
 * Returns a greeting based on the current hour.
 */
const getGreeting = () => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Buenos días';
  if (hour < 18) return 'Buenas tardes';
  return 'Buenas noches';
};

/**
 * Checks if the device is mobile and adjusts sidebar state accordingly.
 */
const checkMobile = () => {
  isMobile.value = window.innerWidth <= 768;
  if (isMobile.value) {
    isSidebarCollapsed.value = true;
  } else {
    const savedState = localStorage.getItem('sidebarCollapsed');
    isSidebarCollapsed.value = savedState !== null ? JSON.parse(savedState) : false;
  }
};

// --- Methods ---
const toggleProfileDropdown = () => {
    isProfileDropdownOpen.value = !isProfileDropdownOpen.value;
};

const toggleSidebar = () => {
  isSidebarCollapsed.value = !isSidebarCollapsed.value;
  if (!isMobile.value) {
    localStorage.setItem('sidebarCollapsed', JSON.stringify(isSidebarCollapsed.value));
  }
};

const logout = async () => {
    isLoading.value = true;
    try {
        await router.post(route('logout'));
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    } finally {
        isLoading.value = false;
    }
};

const handleNotificationClick = (notification) => {
    if (notification.action_url) {
        router.visit(notification.action_url);
    }
};

const handleClickOutside = (event) => {
    if (isProfileDropdownOpen.value && profileContainer.value && !profileContainer.value.contains(event.target)) {
        isProfileDropdownOpen.value = false;
    }
};

// --- Lifecycle Hooks ---
const toastRef = ref(null)

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus);
  window.addEventListener('offline', updateOnlineStatus);
  
  // Verificación periódica de la cola
  queueInterval = setInterval(checkQueue, 5000);
  
  checkQueue();
  if (isOnline.value) processOfflineQueue();

  document.addEventListener('click', handleClickOutside);
  checkMobile();
  window.addEventListener('resize', checkMobile);
  if (usuario.value?.is_admin) {
    cargarConfiguracionEmpresa();
  }

  // Presencia en tiempo real (solo si Reverb/Echo está configurado en el build)
  if (window.Echo && usuario.value) {
    window.Echo.join('admin-presence')
      .here((users) => {
        onlineUsers.value = [...users]; // Forzar copia para reactividad
      })
      .joining((user) => {
        if (!onlineUsers.value.find(u => u.id === user.id)) {
          onlineUsers.value = [...onlineUsers.value, user];
        }
      })
      .leaving((user) => {
        onlineUsers.value = onlineUsers.value.filter(u => u.id !== user.id);
      })
      .error((error) => {
        if (import.meta.env.DEV) {
          console.debug('[Echo] Canal de presencia:', error);
        }
      });
  }

  // --- Listeners de Errores Globales (Inertia) ---
  const unregisterInvalid = router.on('invalid', (event) => {
    // Solo mostrar el modal si no estamos en local/desarrollo o si queremos forzarlo
    // Si la respuesta es un 500 o error de servidor HTML
    if (event.detail.response.status >= 500) {
      event.preventDefault()
      errorMessage.value = `Error ${event.detail.response.status}: El servidor no pudo procesar la solicitud.`
      showErrorModal.value = true
    }
  })

  const unregisterException = router.on('exception', (event) => {
    // Captura excepciones de JS o errores directos de Inertia
    console.error('Inertia Exception:', event.detail.exception)
    // No prevenimos el comportamiento por defecto en desarrollo para poder depurar
    // Pero podemos mostrar un aviso.
  })

  const unregisterError = router.on('error', (errors) => {
    // Esto es para errores de validación (422), usualmente los componentes los manejan
    // Pero si hay un error 'general' o inesperado, podemos mostrar el modal
    if (errors.error) {
      errorMessage.value = errors.error
      showErrorModal.value = true
    }
  })
  
  // Guardar unregister functions para limpiar en onBeforeUnmount
  onBeforeUnmount(() => {
    unregisterInvalid()
    unregisterException()
    unregisterError()
  })
  
  // Mostrar mensajes flash como toast automáticamente (después de que ToastContainer se monte)
  nextTick(() => {
    const flash = props.flash
    if (flash?.success && window.$toast) {
      window.$toast.success(flash.success)
    }
    if (flash?.error && window.$toast) {
      window.$toast.error(flash.error)
    }
    if (flash?.warning && window.$toast) {
      window.$toast.warning(flash.warning)
    }
    if (flash?.info && window.$toast) {
      window.$toast.info(flash.info)
    }
  })
});

onBeforeUnmount(() => {
  if (queueInterval) clearInterval(queueInterval);
  window.removeEventListener('online', updateOnlineStatus);
  window.removeEventListener('offline', updateOnlineStatus);
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('resize', checkMobile);

  // Abandonar el canal de presencia
  if (window.Echo) {
    window.Echo.leave('admin-presence');
  }
});
</script>

<style scoped>
/* Accessibility: Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

/* Responsive adjustments for dropdown width */
@media (max-width: 768px) {
    .w-80 {
        width: calc(100vw - 2rem);
        max-width: 320px;
    }
}
</style>
