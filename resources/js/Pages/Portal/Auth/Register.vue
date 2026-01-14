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
  nombre: '',
  email: '',
  telefono: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('portal.register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};

const hasMinLength = computed(() => form.password.length >= 8);
const hasNumber = computed(() => /\d/.test(form.password));
const hasSymbol = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.password));
const hasMixedCase = computed(() => /[a-z]/.test(form.password) && /[A-Z]/.test(form.password));

const strength = computed(() => {
    let s = 0;
    if (hasMinLength.value) s++;
    if (hasNumber.value) s++;
    if (hasSymbol.value) s++;
    if (hasMixedCase.value) s++;
    return s;
});

const strengthLabel = computed(() => {
    if (strength.value <= 2) return 'Débil';
    if (strength.value === 3) return 'Buena';
    return 'Excelente';
});
</script>

<template>
    <Head title="Registro de Clientes" />

    <div class="min-h-screen bg-gray-50 flex flex-col font-sans" :style="cssVars">
        <PublicNavbar :empresa="empresa" activeTab="soporte" />

        <div class="flex-grow flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                
                <!-- Card Premium -->
                <div class="bg-white rounded-[3rem] shadow-2xl shadow-gray-200/50 border border-gray-100 p-10 md:p-12 relative overflow-hidden group">
                    <!-- Decoración -->
                    <div class="absolute -top-12 -right-12 w-32 h-32 bg-[var(--color-primary-soft)] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="absolute -bottom-12 -left-12 w-24 h-24 bg-[var(--color-terciary-soft)] rounded-full group-hover:scale-110 transition-transform duration-500"></div>

                    <div class="relative z-10">
                        <div class="text-center mb-10">
                            <div class="w-16 h-16 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] mx-auto mb-6 text-2xl">
                                ✨
                            </div>
                            <h2 class="text-3xl font-black text-gray-900 tracking-tight">Registro Rápido</h2>
                            <p class="text-gray-500 font-medium mt-2">Crea tu cuenta para comprar al instante.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Nombre Completo</label>
                                <input 
                                    v-model="form.nombre" 
                                    type="text" 
                                    placeholder="Juan Pérez"
                                    required
                                    class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium"
                                >
                                <div v-if="form.errors.nombre" class="text-red-500 text-[10px] font-bold mt-1 ml-1 uppercase tracking-widest">{{ form.errors.nombre }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Correo Electrónico</label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    placeholder="ejemplo@correo.com"
                                    required
                                    class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium"
                                >
                                <div v-if="form.errors.email" class="text-red-500 text-[10px] font-bold mt-1 ml-1 uppercase tracking-widest">{{ form.errors.email }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Teléfono / WhatsApp</label>
                                <input 
                                    v-model="form.telefono" 
                                    type="tel" 
                                    placeholder="(55) 1234 5678"
                                    required
                                    class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium"
                                >
                                <div v-if="form.errors.telefono" class="text-red-500 text-[10px] font-bold mt-1 ml-1 uppercase tracking-widest">{{ form.errors.telefono }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Contraseña</label>
                                <input 
                                    v-model="form.password" 
                                    type="password" 
                                    placeholder="••••••••"
                                    required
                                    class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium"
                                >
                                <div v-if="form.errors.password" class="text-red-500 text-[10px] font-bold mt-1 ml-1 uppercase tracking-widest">{{ form.errors.password }}</div>
                            </div>

                            <div class="space-y-1">
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">Confirmar Contraseña</label>
                                <input 
                                    v-model="form.password_confirmation" 
                                    type="password" 
                                    placeholder="••••••••"
                                    required
                                    class="w-full px-6 py-4 bg-gray-50 border-gray-100 rounded-2xl focus:ring-4 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all font-medium"
                                >
                            </div>

                            <!-- Password Strength Indicator -->
                            <div v-if="form.password" class="p-4 bg-gray-50 rounded-2xl border border-gray-100 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Fortaleza</span>
                                    <span :class="{'text-red-500': strength < 3, 'text-yellow-500': strength === 3, 'text-green-500': strength >= 4}" class="text-[10px] font-black uppercase tracking-widest">{{ strengthLabel }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                     <div :class="{'bg-red-500': strength < 3, 'bg-yellow-500': strength === 3, 'bg-green-500': strength >= 4}" class="h-full transition-all duration-300" :style="{ width: (strength * 25) + '%' }"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                     <div :class="{'text-green-600': hasMinLength, 'text-gray-400': !hasMinLength}" class="flex items-center gap-2 text-[10px] font-bold transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="hasMinLength ? 'bg-green-500' : 'bg-gray-300'"></span> 8+ Caracteres
                                     </div>
                                     <div :class="{'text-green-600': hasNumber, 'text-gray-400': !hasNumber}" class="flex items-center gap-2 text-[10px] font-bold transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="hasNumber ? 'bg-green-500' : 'bg-gray-300'"></span> Un Número
                                     </div>
                                     <div :class="{'text-green-600': hasSymbol, 'text-gray-400': !hasSymbol}" class="flex items-center gap-2 text-[10px] font-bold transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="hasSymbol ? 'bg-green-500' : 'bg-gray-300'"></span> Un Símbolo
                                     </div>
                                     <div :class="{'text-green-600': hasMixedCase, 'text-gray-400': !hasMixedCase}" class="flex items-center gap-2 text-[10px] font-bold transition-colors">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="hasMixedCase ? 'bg-green-500' : 'bg-gray-300'"></span> Mayús/Minús
                                     </div>
                                </div>
                            </div>

                            <div class="pt-4">
                                <button 
                                    type="submit" 
                                    :disabled="form.processing"
                                    class="w-full py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-[var(--color-primary)]/20 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[var(--color-primary)]/30 transition-all flex items-center justify-center gap-3 disabled:opacity-50"
                                >
                                    <span>Crear Cuenta Ahora</span>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <p class="text-center mt-8 text-gray-400 text-[10px] font-black uppercase tracking-[0.2em]">
                    &copy; {{ new Date().getFullYear() }} {{ empresa?.nombre }} &bull; Tienda Oficial
                </p>
            </div>
        </div>
    </div>
</template>
