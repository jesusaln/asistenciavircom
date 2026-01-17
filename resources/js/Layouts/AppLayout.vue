<template>
    <div class="flex flex-col h-screen bg-gray-50 dark:bg-gray-950 transition-colors duration-300">
        <!-- Navigation Bar -->
        <nav class="bg-gradient-to-r from-gray-900 to-gray-800 shadow-lg border-b border-gray-700">
            <div class="px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                         <img v-if="empresaConfigShared?.logo_url" :src="empresaConfigShared.logo_url" class="h-8 w-auto object-contain" :alt="empresaConfigShared.nombre_empresa">
                         <span v-else class="text-white font-black tracking-tight text-lg">
                            <span class="text-amber-400">🔒</span> {{ empresaConfigShared?.nombre_empresa || 'CDD' }}
                         </span>
                    </div>

                    <!-- Greeting -->
                    <div v-if="usuario" class="hidden md:flex items-center">
                        <span class="text-gray-200 text-sm font-medium">
                            {{ getGreeting() }},
                            <span class="text-amber-400 font-semibold">{{ usuario.name }}</span>
                        </span>
                    </div>

                    <!-- Company Name -->
                    <div class="hidden lg:flex items-center">
                        <span class="text-gray-300 text-sm">
                            {{ empresaConfigShared?.nombre_empresa || 'CDD Sistema' }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Notifications -->
                        <NotificationBell
                            :auto-refresh="true"
                            :refresh-interval="30000"
                            @notification-clicked="handleNotificationClick"
                        />

                        <!-- Profile Dropdown -->
                        <div v-if="usuario" class="relative" ref="profileContainer">
                            <button
                                @click="toggleProfileDropdown"
                                class="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                :aria-expanded="isProfileDropdownOpen.toString()"
                                aria-controls="profile-dropdown"
                            >
                                <img
                                    :src="usuario?.profile_photo_url || 'https://ui-avatars.com/api/?name=' + (usuario?.name || 'User')"
                                    alt="Foto de perfil"
                                    class="h-8 w-8 rounded-full border-2 border-amber-500 object-cover"
                                />
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-4 w-4 text-gray-300 transition-transform duration-200"
                                    :class="{ 'rotate-180': isProfileDropdownOpen }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <Transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-150"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-if="isProfileDropdownOpen"
                                    id="profile-dropdown"
                                    role="menu"
                                    aria-orientation="vertical"
                                    aria-labelledby="profile-button"
                                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden"
                                >
                                    <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                                        <p class="text-sm font-medium text-gray-900">{{ usuario?.name || 'Usuario' }}</p>
                                        <p class="text-xs text-gray-500">{{ usuario?.email || '' }}</p>
                                    </div>

                                    <div class="py-1">
                                        <Link
                                            :href="route('perfil')"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition-colors duration-150"
                                            role="menuitem"
                                        >
                                            <svg class="mr-3 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Mi Perfil
                                        </Link>
                                        <Link
                                            :href="route('empresa-configuracion.index')"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-amber-50 hover:text-amber-700 transition-colors duration-150"
                                            role="menuitem"
                                        >
                                            <svg class="mr-3 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            Configuración
                                        </Link>
                                    </div>

                                    <div class="border-t border-gray-100">
                                        <button
                                            @click="logout"
                                            class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-150"
                                            role="menuitem"
                                        >
                                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            Cerrar Sesión
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
            <Sidebar :isSidebarCollapsed="isSidebarCollapsed" :usuario="usuario" :isMobile="isMobile" @toggleSidebar="toggleSidebar" />

            <main
                :class="[
                    {'ml-64': !isSidebarCollapsed, 'ml-20': isSidebarCollapsed},
                    'flex-1 overflow-y-auto transition-all duration-300 ease-in-out bg-gray-50 dark:bg-gray-950'
                ]"
            >
                <div class="p-6">
                    <div v-if="$slots.header" class="mb-6">
                        <slot name="header" />
                    </div>
                    <slot />
                </div>
            </main>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" aria-modal="true" role="dialog">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 flex items-center space-x-3 shadow-xl transition-colors">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-amber-500" role="status" aria-label="Cargando"></div>
                <span class="text-gray-700 dark:text-gray-200 font-medium transition-colors">Cargando...</span>
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
import { Link, usePage } from '@inertiajs/vue3';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faCalendar, faWrench, faTools, faCarAlt, faChartBar, faCartShopping,
    faCircle, faHome, faUsers, faBox, faTags, faTrademark, faTruck,
    faWarehouse, faFileAlt, faTruckLoading, faDollarSign, faUser,
    faCalculator, faHandHoldingUsd, faWallet, faShieldHalved,
    faCircleInfo, faCheckCircle, faMoneyBillWave, faCalendarAlt, faBriefcase,
} from '@fortawesome/free-solid-svg-icons';
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// --- Font Awesome Icon Configuration ---
library.add(
    faCalendar, faWrench, faTools, faCarAlt, faChartBar, faCartShopping,
    faCircle, faHome, faUsers, faBox, faTags, faTrademark, faTruck,
    faWarehouse, faFileAlt, faTruckLoading, faDollarSign, faUser,
    faCalculator, faWallet, faShieldHalved,
    faCircleInfo, faCheckCircle, faMoneyBillWave, faCalendarAlt, faBriefcase
);

// --- Reactive States ---
const { props } = usePage();
const usuario = computed(() => props.auth?.user);
const isProfileDropdownOpen = ref(false);
const isSidebarCollapsed = ref(false);
const isMobile = ref(false);
const isLoading = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');

// Configuración de empresa compartida (Inertia)
const empresaConfigShared = computed(() => props.empresa_config);

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
  document.addEventListener('click', handleClickOutside);
  checkMobile();
  window.addEventListener('resize', checkMobile);
  if (usuario.value?.is_admin) {
    cargarConfiguracionEmpresa();
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
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('resize', checkMobile);
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
