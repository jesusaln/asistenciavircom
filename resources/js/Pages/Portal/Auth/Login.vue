<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicNavbar from '@/Components/PublicNavbar.vue';

const props = defineProps({
    empresa: Object,
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#3b82f6',
    '--color-primary-soft': (props.empresa?.color_principal || '#3b82f6') + '15',
    '--color-primary-dark': (props.empresa?.color_principal || '#3b82f6') + 'dd',
    '--color-secondary': props.empresa?.color_secundario || '#6b7280',
    '--color-terciary': props.empresa?.color_terciario || '#fbbf24',
    '--color-terciary-soft': (props.empresa?.color_terciario || '#fbbf24') + '15',
}));

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('portal.login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
    <Head title="Acceso al Portal" />

    <div class="min-h-screen bg-white dark:bg-gray-900 flex flex-col font-sans transition-colors duration-300" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="soporte" />

        <div class="flex-grow flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                
                <!-- Card Premium -->
                <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-800 p-10 md:p-12 relative overflow-hidden group transition-colors duration-300">
                    <!-- Decoración -->
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-[var(--color-primary-soft)] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute -bottom-12 -left-12 w-24 h-24 bg-[var(--color-terciary-soft)] rounded-full group-hover:scale-110 transition-transform duration-500"></div>

                    <div class="relative z-10">
                        <div class="text-center mb-10">
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] mx-auto mb-6 text-2xl">
                                🔐
                            </div>
                            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Acceso Clientes</h2>
                            <p class="text-gray-500 dark:text-gray-400 font-medium mt-2 transition-colors">Ingrese sus credenciales para gestionar su soporte.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 ml-1 transition-colors">Correo Electrónico</label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    placeholder="ejemplo@correo.com"
                                    required
                                    class="w-full px-6 py-4 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium placeholder-gray-400 dark:placeholder-gray-500"
                                >
                                <div v-if="form.errors.email" class="text-red-500 text-[10px] font-bold mt-1 ml-1 uppercase tracking-widest">{{ form.errors.email }}</div>
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between items-center ml-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 transition-colors">Contraseña</label>
                                    <Link :href="route('portal.password.request')" class="text-[10px] font-black uppercase tracking-widest text-[var(--color-primary)] hover:underline">¿Olvido su Clave?</Link>
                                </div>
                                <input 
                                    v-model="form.password" 
                                    type="password" 
                                    placeholder="••••••••"
                                    required
                                    class="w-full px-6 py-4 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium placeholder-gray-400 dark:placeholder-gray-500"
                                >
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer group/check">
                                <div class="relative flex items-center">
                                    <input v-model="form.remember" type="checkbox" class="peer hidden">
                                    <div class="w-5 h-5 border-2 border-gray-200 dark:border-gray-600 rounded-lg peer-checked:bg-[var(--color-primary)] peer-checked:border-[var(--color-primary)] transition-all"></div>
                                    <svg class="absolute w-3 h-3 text-white left-1 bottom-1 opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 group-hover/check:text-gray-600 dark:group-hover/check:text-gray-300 transition-colors">Recordarme</span>
                            </label>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50"
                            >
                                <span>Ingresar al Portal</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            </button>

                            <div class="text-center pt-4 border-t border-gray-100 dark:border-gray-800 mt-4 transition-colors">
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4 transition-colors">¿No tienes cuenta de cliente?</p>
                                <Link 
                                    :href="route('portal.register')" 
                                    class="inline-flex w-full items-center justify-center py-4 bg-[var(--color-terciary-soft)] text-[var(--color-primary)] rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-[var(--color-primary-soft)] transition-all gap-2"
                                >
                                    <span>Crear una cuenta</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer del Login -->
                <p class="text-center mt-8 text-gray-400 dark:text-gray-600 text-[10px] font-black uppercase tracking-[0.2em] transition-colors">
                    &copy; {{ new Date().getFullYear() }} {{ empresa?.nombre }} &bull; Soporte Seguro
                </p>
            </div>
        </div>
    </div>
</template>

