<template>
  <Head title="Crear Usuario" />
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-500">
    <div class="max-w-4xl mx-auto">
      <!-- Header Area -->
      <div class="flex flex-col items-center text-center mb-12">
        <div class="w-20 h-20 rounded-[2rem] bg-[var(--color-primary)] text-white flex items-center justify-center text-3xl shadow-2xl shadow-[var(--color-primary)]/20 mb-6 transition-transform hover:rotate-6">
          <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
        </div>
        <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight mb-3">Nuevo Usuario</h1>
        <p class="text-slate-500 dark:text-slate-400 font-medium text-lg max-w-lg">Configura un nuevo perfil de acceso para el personal de la organización.</p>
      </div>

      <!-- Form Card -->
      <div class="bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden backdrop-blur-3xl transition-all duration-500">
        <div class="h-1.5 bg-slate-100 dark:bg-slate-800">
          <div class="h-full bg-gradient-to-r from-[var(--color-primary)] to-indigo-500 transition-all duration-500 shadow-[0_0_10px_var(--color-primary)]"
               :style="{ width: formProgress + '%' }"></div>
        </div>

        <form @submit.prevent="submit" class="p-10 lg:p-16 space-y-12">
          
          <!-- Personal Information Section -->
          <div class="space-y-8">
            <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6">
              <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Información de Identidad</h2>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest italic">Datos básicos obligatorios</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Nombre -->
                <div class="space-y-3">
                  <label for="name" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Nombre Completo</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none group-focus-within:text-[var(--color-primary)] transition-colors">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                    <input
                      v-model="form.name"
                      type="text"
                      id="name"
                      class="block w-full pl-14 pr-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                      placeholder="Ej: Juan Pérez"
                    />
                  </div>
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <!-- Email -->
                <div class="space-y-3">
                  <label for="email" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Correo Electrónico</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none group-focus-within:text-[var(--color-primary)] transition-colors">
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                      </svg>
                    </div>
                    <input
                      v-model="form.email"
                      type="email"
                      id="email"
                      class="block w-full pl-14 pr-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                      placeholder="juan@empresa.com"
                    />
                  </div>
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <!-- Teléfono -->
            <div class="space-y-3">
              <label for="telefono" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Contacto Telefónico</label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none group-focus-within:text-[var(--color-primary)] transition-colors">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                </div>
                <input
                  v-model="form.telefono"
                  type="tel"
                  id="telefono"
                  class="block w-full pl-14 pr-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                  placeholder="662 123 4567"
                />
              </div>
              <InputError class="mt-2" :message="form.errors.telefono" />
            </div>
          </div>

          <!-- Roles y Permisos Section -->
          <div class="space-y-8 border-t border-slate-50 dark:border-slate-800 pt-12">
            <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6">
              <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Acceso y Roles</h2>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest italic">Define privilegios del sistema</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="role in roles" :key="role.id" 
                   class="group relative bg-slate-50 dark:bg-slate-950 border-2 rounded-[2rem] p-6 lg:p-8 cursor-pointer transition-all duration-300 hover:-translate-y-2"
                   :class="form.roles.includes(role.name) ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/5' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'"
                   @click="toggleRole(role.name)">
                
                <div class="flex justify-between items-start mb-4">
                   <div class="p-3 rounded-2xl" :class="form.roles.includes(role.name) ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                     <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                     </svg>
                   </div>
                   <div v-if="form.roles.includes(role.name)" class="w-6 h-6 rounded-full bg-[var(--color-primary)] border-4 border-white dark:border-slate-900 shadow-sm animate-bounce"></div>
                </div>

                <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">{{ role.label }}</h3>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">{{ role.permissions_count }} PERMISOS ASIGNADOS</p>
                
                <div class="space-y-2 border-t border-slate-200/50 dark:border-slate-700/50 pt-4">
                    <div v-for="(count, action) in role.permissions_summary" :key="action" class="flex items-center justify-between text-[10px] font-bold">
                      <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ action }}</span>
                      <span class="text-slate-900 dark:text-white">{{ count }}</span>
                    </div>
                </div>
              </div>
            </div>
            <InputError :message="form.errors.roles" class="mt-2" />
          </div>

          <!-- Financial / Technical Section -->
          <div class="space-y-8 border-t border-slate-50 dark:border-slate-800 pt-12">
            <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6">
              <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Preferencias y Costos</h2>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest italic">Configuración logística y técnica</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
               <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Almacén Venta</label>
                  <select v-model="form.almacen_venta_id" class="block w-full px-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold appearance-none">
                     <option :value="null">SIN ALMACÉN</option>
                     <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                  </select>
               </div>
               
               <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Almacén Compra</label>
                  <select v-model="form.almacen_compra_id" class="block w-full px-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold appearance-none">
                     <option :value="null">SIN ALMACÉN</option>
                     <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                  </select>
               </div>

               <div class="space-y-3">
                  <label for="costo" class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Costo Hora Interno</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none group-focus-within:text-emerald-500 transition-colors">
                      <span class="text-lg font-black">$</span>
                    </div>
                    <input
                      v-model="form.costo_hora_interno"
                      type="number"
                      step="0.01"
                      class="block w-full pl-14 pr-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-bold"
                      placeholder="0.00"
                    />
                  </div>
               </div>
            </div>
          </div>

          <!-- Security Section -->
          <div class="space-y-8 border-t border-slate-50 dark:border-slate-800 pt-12">
            <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6">
              <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-500 flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Seguridad de Acceso</h2>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-widest italic">Protección de la cuenta</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
               <!-- Password Control -->
               <div class="space-y-4">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Contraseña de Ingreso</label>
                  <div class="relative group">
                    <div @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-6 flex items-center cursor-pointer text-slate-400 hover:text-[var(--color-primary)] transition-colors">
                      <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                      </svg>
                      <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </div>
                    <input
                      v-model="form.password"
                      :type="showPassword ? 'text' : 'password'"
                      class="block w-full pl-8 pr-16 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all font-bold tracking-widest"
                      placeholder="••••••••"
                    />
                  </div>
                  <!-- Password Strength Meter -->
                  <div class="flex gap-2 px-2 pt-1">
                    <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-all duration-500"
                      :class="passwordStrength >= i ? (passwordStrength >= 3 ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.3)]' : 'bg-amber-500') : 'bg-slate-200 dark:bg-slate-800'"></div>
                  </div>
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">{{ passwordStrengthText }}</p>
                  <InputError class="mt-2" :message="form.errors.password" />
               </div>

               <!-- Confirmation Control -->
               <div class="space-y-4">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Confirmar Contraseña</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 right-0 pr-6 flex items-center pointer-events-none">
                       <svg v-if="form.password_confirmation && form.password === form.password_confirmation" class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                       </svg>
                    </div>
                    <input
                      v-model="form.password_confirmation"
                      :type="showPassword ? 'text' : 'password'"
                      class="block w-full pl-8 pr-16 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all font-bold tracking-widest"
                      placeholder="••••••••"
                    />
                  </div>
                  <InputError class="mt-2" :message="form.errors.password_confirmation" />
               </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-12 border-t border-slate-50 dark:border-slate-800">
            <div class="flex flex-col sm:flex-row gap-6 justify-end">
              <Link :href="route('usuarios.index')"
                    class="order-2 sm:order-1 px-10 py-5 rounded-[1.5rem] bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 font-black text-xs uppercase tracking-[0.2em] hover:bg-slate-100 dark:hover:bg-slate-900 transition-all text-center">
                Cancelar Registro
              </Link>
              <button
                type="submit"
                class="order-1 sm:order-2 px-12 py-5 rounded-[1.5rem] bg-[var(--color-primary)] text-white font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-[var(--color-primary)]/20 hover:scale-[1.03] active:scale-95 transition-all text-center disabled:opacity-50 disabled:hover:scale-100 flex items-center justify-center gap-3"
                :disabled="form.processing || !isFormValid"
              >
                <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ form.processing ? 'Registrando Usuario...' : 'Crear Usuario Definitivo' }}</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
  roles: Array,
  almacenes: Array,
});

const showPassword = ref(false);

const form = useForm({
  name: '',
  email: '',
  telefono: '',
  roles: [],
  password: '',
  password_confirmation: '',
  almacen_venta_id: null,
  almacen_compra_id: null,
  costo_hora_interno: 0,
});

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  ripple: true,
  dismissible: true
});

const isValidEmail = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(form.email);
});

const passwordStrength = computed(() => {
  const password = form.password;
  if (!password) return 0;
  let strength = 0;
  if (password.length >= 8) strength++;
  if (/\d/.test(password)) strength++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
  return strength;
});

const passwordStrengthText = computed(() => {
  const s = passwordStrength.value;
  if (s <= 1) return 'MUY DÉBIL';
  if (s === 2) return 'REGULAR';
  if (s === 3) return 'FUERTE';
  return 'IMPENETRABLE';
});

const isFormValid = computed(() => {
  return form.name && form.email && isValidEmail.value && form.password.length >= 8 && form.password === form.password_confirmation;
});

const formProgress = computed(() => {
  let progress = 0;
  const totalFields = 4; // name, email, roles, password
  if (form.name) progress++;
  if (isValidEmail.value) progress++;
  if (form.roles.length > 0) progress++;
  if (form.password.length >= 8) progress++;
  return (progress / totalFields) * 100;
});

const toggleRole = (roleName) => {
  const index = form.roles.indexOf(roleName);
  if (index > -1) {
    form.roles.splice(index, 1);
  } else {
    form.roles.push(roleName);
  }
};

const submit = () => {
  form.post(route('usuarios.store'), {
    onSuccess: () => {
      notyf.success('Usuario registrado con éxito en el sistema.');
    },
    onError: (errors) => {
      notyf.error('Error en la validación de los datos. Revise los campos.');
      const firstError = Object.keys(errors)[0];
      const el = document.getElementById(firstError);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
};
</script>

<style scoped>
/* Transiciones suaves para el modo oscuro */
div, button, input, select, span {
  transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
}

select {
  background-image: url('data:image/svg+xml,%3csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"%3e%3cpath stroke="%2364748b" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4"/%3e%3c/svg%3e');
  background-position: right 1.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
}

.dark select {
  background-image: url('data:image/svg+xml,%3csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"%3e%3cpath stroke="%2394a3b8" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 8l4 4 4-4"/%3e%3c/svg%3e');
}

input:focus, select:focus {
  outline: none;
}
</style>
