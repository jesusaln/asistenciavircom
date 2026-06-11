<script setup>
// Importar componentes necesarios
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import { computed, ref } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    confirmsTwoFactorAuthentication: Boolean, // Indica si se requiere confirmación para autenticación de dos factores
    sessions: Array, // Lista de sesiones activas del usuario
    almacenes: Array, // Lista de almacenes disponibles
    user: Object, // Información del usuario
});

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Estado para almacén de venta
const almacenes = ref(props.almacenes || []);
const userData = computed(() => props.user || user.value);
const selectedAlmacen = ref(userData.value?.almacen_venta_id || '');

// Estado para almacén de compra
const selectedAlmacenCompra = ref(userData.value?.almacen_compra_id || '');

// Estadísticas del perfil
const profileStats = computed(() => ({
  totalSessions: props.sessions?.length || 0,
  twoFactorEnabled: user.value?.two_factor_secret ? true : false,
  emailVerified: user.value?.email_verified_at ? true : false,
  lastLogin: user.value?.last_login_at || null
}));

// Configuración del header
const headerConfig = {
  module: 'profile',
  title: 'Mi Perfil',
  subtitle: `Bienvenido, ${user.value?.name || 'Usuario'}`,
  showCreateButton: false,
  showStats: true
};

// Función para actualizar almacén de venta
const updateAlmacenVenta = async () => {
    try {
        const response = await fetch('/user/update-almacen-venta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                almacen_venta_id: selectedAlmacen.value || null
            })
        });

        if (response.ok) {
            const data = await response.json();
            // Actualizar el usuario localmente
            if (userData.value) {
                userData.value.almacen_venta_id = selectedAlmacen.value;
                userData.value.almacen_venta = data.almacen_venta;
            }
            notyf.success('Almacén de venta actualizado correctamente');
        } else {
            throw new Error('Error al actualizar');
        }
    } catch (error) {
        console.error('Error:', error);
        notyf.error('Error al actualizar el almacén de venta');
        // Revertir el cambio
        selectedAlmacen.value = userData.value?.almacen_venta_id || '';
    }
};

// Función para actualizar almacén de compra
const updateAlmacenCompra = async () => {
    try {
        const response = await fetch('/user/update-almacen-compra', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                almacen_compra_id: selectedAlmacenCompra.value || null
            })
        });

        if (response.ok) {
            const data = await response.json();
            // Actualizar el usuario localmente
            if (userData.value) {
                userData.value.almacen_compra_id = selectedAlmacenCompra.value;
                userData.value.almacen_compra = data.almacen_compra;
            }
            notyf.success('Almacén de compra actualizado correctamente');
        } else {
            throw new Error('Error al actualizar');
        }
    } catch (error) {
        console.error('Error:', error);
        notyf.error('Error al actualizar el almacén de compra');
        // Revertir el cambio
        selectedAlmacenCompra.value = userData.value?.almacen_compra_id || '';
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Mi Perfil" />

        <div class="profile-page min-h-screen bg-[var(--ui-surface)] text-slate-200">
            <!-- Header Premium con Degradado -->
            <div class="relative overflow-hidden mb-10">
                <!-- Capa de fondo decorativa -->
                <div class="absolute inset-0 bg-gradient-to-r from-[#ff6600]/10 to-transparent pointer-events-none"></div>
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-[#ff6600]/5 rounded-full blur-3xl"></div>
                
                <div class="relative bg-slate-900/50 backdrop-blur-md border-b border-white/5 p-8 lg:p-12">
                    <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between">
                        <!-- Información del usuario -->
                        <div class="flex flex-col gap-6 w-full lg:w-auto">
                            <div class="flex items-center gap-6">
                                <div class="relative group">
                                    <div class="absolute -inset-1 bg-gradient-to-r from-[#ff6600] to-[#ff9900] rounded-full blur opacity-25 group-hover:opacity-50 transition duration-700 group-hover:duration-200"></div>
                                    <img
                                        :src="user.profile_photo_url"
                                        :alt="user.name"
                                        class="relative w-16 h-16 rounded-full border-2 border-[#ff6600]/30 object-cover shadow-2xl"
                                    />
                                    <div class="absolute bottom-1 right-1 w-7 h-7 bg-[#ff6600] rounded-full border-4 border-[#0b0f19] flex items-center justify-center shadow-xl">
                                        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <div>
                                    <h1 class="text-4xl font-black text-white tracking-tight">{{ user.name }}</h1>
                                    <p class="text-xl text-slate-400 font-medium">{{ user.email }}</p>
                                    <div class="flex flex-wrap items-center gap-3 mt-4">
                                        <span v-if="profileStats.emailVerified" class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-500/10 text-emerald-400 ring-1 ring-emerald-400/30 text-xs font-bold rounded-full uppercase tracking-wider backdrop-blur-sm">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            Verificado
                                        </span>
                                        <span v-if="profileStats.twoFactorEnabled" class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#ff6600]/10 text-[#ff6600] ring-1 ring-[#ff6600]/30 text-xs font-bold rounded-full uppercase tracking-wider backdrop-blur-sm">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                            </svg>
                                            Seguridad 2FA
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats cards rápidas -->
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="px-6 py-4 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl flex flex-col items-center justify-center min-w-[140px] hover:border-[#ff6600]/50 transition-colors group cursor-default shadow-xl">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1 group-hover:text-[#ff6600]/70 transition-colors">Sesiones</span>
                                <span class="text-2xl font-black text-white">{{ profileStats.totalSessions }}</span>
                            </div>
                            <div class="px-6 py-4 bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl flex flex-col items-center justify-center min-w-[140px] hover:border-[#ff6600]/50 transition-colors group cursor-default shadow-xl">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1 group-hover:text-[#ff6600]/70 transition-colors">Seguridad</span>
                                <span class="text-lg font-black" :class="profileStats.twoFactorEnabled ? 'text-emerald-400' : 'text-amber-400'">
                                    {{ profileStats.twoFactorEnabled ? 'ÓPTIMA' : 'BÁSICA' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 pb-20">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    
                    <!-- Información Personal (Card Estilo Premium) -->
                    <div v-if="$page.props.jetstream.canUpdateProfileInformation" class="premium-card">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-[#ff6600] shadow-inner">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white tracking-tight">Información Personal</h3>
                                <p class="text-sm text-slate-500">Actualiza tus datos básicos y contacto.</p>
                            </div>
                        </div>
                        <UpdateProfileInformationForm :user="user" />
                    </div>

                    <!-- Configuración de Almacenes -->
                    <div class="grid grid-cols-1 gap-8">
                        <!-- Almacén Venta -->
                        <div class="premium-card relative overflow-hidden group">
                           <div class="absolute top-0 right-0 p-8 text-[#ff6600]/5 group-hover:text-[#ff6600]/10 transition-colors pointer-events-none">
                               <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                           </div>
                           <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-brand-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h3 class="text-xl font-bold text-white tracking-tight">Venta Predeterminada</h3>
                            </div>
                            <div class="space-y-6">
                                <select
                                    v-model="selectedAlmacen"
                                    @change="updateAlmacenVenta"
                                    class="premium-select"
                                >
                                    <option value="" class="bg-[#0b0f19]">Sin almacén predeterminado</option>
                                    <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id" class="bg-[#0b0f19]">
                                        {{ almacen.nombre }}
                                    </option>
                                </select>
                                <div v-if="userData.almacen_venta" class="p-4 bg-brand-500/5 border border-brand-500/20 rounded-2xl flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-brand-500/20 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-brand-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <span class="text-sm font-medium text-brand-200 uppercase tracking-wide">Actual: {{ userData.almacen_venta.nombre }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Almacén Compra -->
                        <div class="premium-card relative overflow-hidden group">
                           <div class="absolute top-0 right-0 p-8 text-emerald-500/5 group-hover:text-emerald-500/10 transition-colors pointer-events-none">
                               <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                           </div>
                           <div class="flex items-center gap-4 mb-6">
                                <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-emerald-500">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <h3 class="text-xl font-bold text-white tracking-tight">Compra Predeterminada</h3>
                            </div>
                            <div class="space-y-6">
                                <select
                                    v-model="selectedAlmacenCompra"
                                    @change="updateAlmacenCompra"
                                    class="premium-select"
                                >
                                    <option value="" class="bg-[#0b0f19]">Sin almacén predeterminado</option>
                                    <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id" class="bg-[#0b0f19]">
                                        {{ almacen.nombre }}
                                    </option>
                                </select>
                                <div v-if="userData.almacen_compra" class="p-4 bg-brand-500/5 border border-emerald-500/20 rounded-2xl flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-full bg-brand-500/20 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                    </div>
                                    <span class="text-sm font-medium text-emerald-200 uppercase tracking-wide">Actual: {{ userData.almacen_compra.nombre }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seguridad (Full Width en grid) -->
                    <div v-if="$page.props.jetstream.canUpdatePassword" class="premium-card">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-[#ff6600]">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Seguridad de la Cuenta</h3>
                        </div>
                        <UpdatePasswordForm />
                    </div>

                    <!-- Autenticación 2FA -->
                    <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication" class="premium-card">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-emerald-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Autenticación de 2 Factores</h3>
                        </div>
                        <TwoFactorAuthenticationForm :requires-confirmation="props.confirmsTwoFactorAuthentication" />
                    </div>



                    <!-- Sesiones Activas -->
                    <div class="premium-card">
                        <div class="flex items-center gap-4 mb-8">
                             <div class="w-10 h-10 bg-white/5 rounded-2xl flex items-center justify-center border border-white/10 text-[#ff6600]">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Sesiones del Navegador</h3>
                        </div>
                        <LogoutOtherBrowserSessionsForm :sessions="props.sessions" />
                    </div>
                </div>

                <!-- Eliminar Cuenta (Danger Zone) -->
                <div v-if="$page.props.jetstream.hasAccountDeletionFeatures" class="mt-12 p-8 bg-brand-500/5 rounded-[2rem] border-2 border-dashed border-rose-500/20 group hover:border-brand-500/40 transition-all duration-500">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="w-10 h-10 bg-brand-500/10 rounded-2xl flex items-center justify-center text-rose-500 group-hover:scale-105 transition-transform">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-rose-300">Zona de Peligro</h3>
                            <p class="text-sm text-rose-500/70 font-medium">Acciones irreversibles sobre tu cuenta.</p>
                        </div>
                    </div>
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

.profile-page {
    animation: pageReveal 1s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes pageReveal {
    from { opacity: 0; transform: translateY(20px); filter: blur(10px); }
    to { opacity: 1; transform: translateY(0); filter: blur(0); }
}

/* Custom scrollbar para la página en dark mode */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #0b0f19; }
::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #ff6600; }
</style>
