<template>
  <Head title="Editar Usuario" />
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-500">
    <div class="max-w-6xl mx-auto">
      
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 mb-12">
        <div class="flex items-center gap-6">
          <div class="w-20 h-20 rounded-[2rem] bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center text-3xl shadow-2xl shadow-blue-500/20 transition-transform hover:rotate-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </div>
          <div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight">
              {{ isAdmin ? `Gestionar Usuario` : `Perfil de Usuario` }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 font-medium text-lg mt-1 group">
              Identificador: <span class="bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-lg text-slate-900 dark:text-white font-bold text-sm">#{{ props.usuario.id }}</span>
            </p>
          </div>
        </div>
        
        <div v-if="isAdmin" class="flex gap-4">
           <Link :href="route('usuarios.index')" class="px-8 py-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-black text-xs uppercase tracking-widest hover:bg-slate-50 transition-all active:scale-95 shadow-sm">
             Volver al Listado
           </Link>
        </div>
      </div>

      <!-- Main Form Container -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Vital Info & Security -->
        <div class="lg:col-span-2 space-y-8">
          <div class="bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-2xl overflow-hidden backdrop-blur-3xl transition-all duration-500">
             <div class="h-1.5 bg-slate-100 dark:bg-slate-800 relative">
               <div class="h-full bg-[var(--color-primary)] shadow-[0_0_15px_var(--color-primary)] transition-all duration-700" :style="{ width: formProgress + '%' }"></div>
             </div>

             <form @submit.prevent="submit" class="p-10 lg:p-14 space-y-12">
               <!-- Basic Section -->
               <div class="space-y-8">
                 <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6">
                    <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center shadow-sm">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                    </div>
                    <div>
                      <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Información General</h2>
                      <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-widest italic">Detalles de identidad y contacto</p>
                    </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Name -->
                    <div class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Nombre Completo</label>
                      <input
                        v-model="form.name"
                        type="text"
                        :readonly="!isAdmin"
                        class="block w-full px-6 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                        :class="{'opacity-60 cursor-not-allowed': !isAdmin}"
                      />
                      <InputError :message="form.errors.name" />
                    </div>

                    <!-- Email -->
                    <div class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Correo Electrónico</label>
                      <input
                        v-model="form.email"
                        type="email"
                        :readonly="!isAdmin"
                        class="block w-full px-6 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                        :class="{'opacity-60 cursor-not-allowed': !isAdmin}"
                      />
                      <InputError :message="form.errors.email" />
                    </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                   <!-- Phone -->
                   <div class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Teléfono</label>
                      <input
                        v-model="form.telefono"
                        type="tel"
                        :readonly="!isAdmin"
                        class="block w-full px-6 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-[var(--color-primary)]/10 focus:border-[var(--color-primary)] transition-all font-bold"
                        :class="{'opacity-60 cursor-not-allowed': !isAdmin}"
                      />
                      <InputError :message="form.errors.telefono" />
                    </div>

                    <!-- Internal Cost -->
                    <div v-if="isAdmin" class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Costo Hora Interno</label>
                      <div class="relative group">
                         <span class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 font-black">$</span>
                         <input
                           v-model="form.costo_hora_interno"
                           type="number"
                           step="0.01"
                           class="block w-full pl-10 pr-6 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all font-bold"
                         />
                      </div>
                      <InputError :message="form.errors.costo_hora_interno" />
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
                      <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Cambio de Credenciales</h2>
                      <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-widest italic">Solo si desea actualizar la contraseña</p>
                    </div>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Nueva Contraseña</label>
                      <div class="relative">
                         <input
                           v-model="form.password"
                           :type="showPassword ? 'text' : 'password'"
                           class="block w-full px-8 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all font-bold tracking-widest"
                           placeholder="••••••••"
                         />
                         <button type="button" @click="showPassword = !showPassword" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 transition-colors">
                            <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                         </button>
                      </div>
                      <InputError :message="form.errors.password" />
                    </div>

                    <div class="space-y-3">
                      <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Confirmar Nueva Contraseña</label>
                      <input
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full px-8 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-red-500/10 focus:border-red-500 transition-all font-bold tracking-widest"
                        placeholder="••••••••"
                      />
                      <InputError :message="form.errors.password_confirmation" />
                    </div>
                 </div>
               </div>

               <!-- Bottom Actions -->
               <div class="pt-12 border-t border-slate-50 dark:border-slate-800 flex justify-end">
                  <button
                    v-if="isAdmin"
                    type="submit"
                    :disabled="form.processing || !isFormValid"
                    class="px-12 py-5 rounded-[1.5rem] bg-[var(--color-primary)] text-white font-black text-xs uppercase tracking-[0.2em] shadow-2xl shadow-[var(--color-primary)]/20 hover:scale-[1.03] active:scale-95 transition-all text-center flex items-center gap-3 disabled:opacity-50"
                  >
                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ form.processing ? 'Guardando Cambios...' : 'Actualizar Información' }}</span>
                  </button>
               </div>
             </form>
          </div>

          <!-- Roles Selection Grid (Below main form for better space) -->
          <div v-if="isAdmin" class="bg-white dark:bg-slate-900 rounded-[3rem] p-10 lg:p-14 border border-slate-100 dark:border-slate-800 shadow-2xl transition-all duration-500">
             <div class="flex items-center gap-4 border-b border-slate-50 dark:border-slate-800 pb-6 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center shadow-sm">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Niveles de Acceso</h2>
                  <p class="text-[10px] font-black text-slate-400 mt-1 uppercase tracking-widest italic">Selección de roles corporativos</p>
                </div>
             </div>

             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="role in props.roles" :key="role.id" 
                     @click="toggleRole(role.name)"
                     class="group relative bg-slate-50 dark:bg-slate-950 border-2 rounded-[2.5rem] p-8 cursor-pointer transition-all duration-300 hover:-translate-y-2"
                     :class="form.roles.includes(role.name) ? 'border-[var(--color-primary)] bg-[var(--color-primary)]/5' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700'">
                  
                  <div class="flex justify-between items-start mb-6">
                     <div class="p-3 rounded-2xl" :class="form.roles.includes(role.name) ? 'bg-[var(--color-primary)] text-white shadow-lg' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                     </div>
                     <div v-if="form.roles.includes(role.name)" class="w-6 h-6 rounded-full bg-[var(--color-primary)] border-4 border-white dark:border-slate-900 shadow-sm animate-bounce"></div>
                  </div>

                  <h3 class="text-xl font-black text-slate-900 dark:text-white mb-1 leading-tight">{{ role.label }}</h3>
                  <p class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">{{ role.permissions_count }} PERMISOS RELEVANTE(S)</p>
                  
                  <div class="space-y-2 pt-4 border-t border-slate-200/50 dark:border-slate-800/50">
                     <div v-for="(count, action) in role.permissions_summary" :key="action" class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                        <span class="text-slate-400 italic">{{ action }}</span>
                        <span class="text-slate-900 dark:text-white">{{ count }}</span>
                     </div>
                  </div>
                </div>
             </div>
          </div>
        </div>

        <!-- Right Column: Settings, Matrix and Stats -->
        <div class="space-y-8">
           <!-- Preferences Card -->
           <div v-if="isAdmin" class="bg-white dark:bg-slate-900 rounded-[3rem] p-10 border border-slate-100 dark:border-slate-800 shadow-2xl transition-all duration-500">
              <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-8">Almacenes</h3>
              
              <div class="space-y-8">
                 <div class="space-y-3">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Para Ventas</label>
                    <select v-model="selectedAlmacenVenta" @change="updateAlmacenVenta" class="block w-full px-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold appearance-none">
                       <option value="">SIN PREDETERMINADO</option>
                       <option v-for="almacen in props.almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                    </select>
                 </div>

                 <div class="space-y-3">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 ml-1">Para Compras</label>
                    <select v-model="selectedAlmacenCompra" @change="updateAlmacenCompra" class="block w-full px-5 py-5 border-slate-100 dark:border-slate-800 rounded-3xl bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold appearance-none">
                       <option value="">SIN PREDETERMINADO</option>
                       <option v-for="almacen in props.almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                    </select>
                 </div>
              </div>
           </div>

           <!-- Permissions Matrix Card -->
           <div v-if="isAdmin && props.permissionGroups" class="bg-white dark:bg-slate-900 rounded-[3rem] p-10 border border-slate-100 dark:border-slate-800 shadow-2xl transition-all duration-500">
              <div class="flex items-center justify-between mb-8">
                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Micro-Permisos</h3>
                <button @click="savePermissions" :disabled="savingPermissions || !permissionsChanged" class="p-4 rounded-2xl bg-indigo-500/10 text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all disabled:opacity-30">
                   <svg v-if="savingPermissions" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                   <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                </button>
              </div>

              <div class="space-y-6 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                 <div v-for="group in props.permissionGroups" :key="group.module" class="space-y-3 p-4 rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 shadow-sm">
                    <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">{{ group.label }}</h4>
                    <div class="grid grid-cols-4 gap-2">
                       <div v-for="action in actions" :key="action" class="flex flex-col items-center">
                          <template v-if="group.permissions[action]">
                             <button 
                                type="button" 
                                @click="togglePermission(group.permissions[action].name)"
                                :disabled="isRolePermission(group.permissions[action].name)"
                                :class="[
                                   'w-10 h-10 rounded-xl flex items-center justify-center transition-all border-2 text-[10px] font-black uppercase',
                                   isRolePermission(group.permissions[action].name) ? 'bg-purple-500/20 border-purple-500/40 text-purple-600' :
                                   isDirectPermission(group.permissions[action].name) ? 'bg-emerald-500 text-white border-emerald-500 shadow-lg' :
                                   'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 text-slate-300'
                                ]"
                                :title="actionLabels[action] + ': ' + (isRolePermission(group.permissions[action].name) ? 'Heredado' : 'Manual')"
                             >
                               {{ action.charAt(0).toUpperCase() }}
                             </button>
                          </template>
                       </div>
                    </div>
                 </div>
              </div>
              <p class="text-[9px] font-bold text-slate-400 mt-6 text-center italic">C = CREAR | V = VER | E = EDITAR | D = BORRAR</p>
           </div>
        </div>
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
  usuario: Object,
  roles: Array,
  almacenes: Array,
  auth: Object,
  permissionGroups: Array,
  userDirectPermissions: Array,
  rolePermissions: Array,
});

const showPassword = ref(false);
const selectedAlmacenVenta = ref(props.usuario.almacen_venta_id || '');
const selectedAlmacenCompra = ref(props.usuario.almacen_compra_id || '');

const actions = ['view', 'create', 'edit', 'delete', 'export', 'stats', 'manage'];
const actionLabels = {
  view: 'Ver', create: 'Crear', edit: 'Editar', delete: 'Borrar',
  export: 'Export', stats: 'Stats', manage: 'Admin'
};

const directPermissions = ref([...(props.userDirectPermissions || [])]);
const savingPermissions = ref(false);

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  ripple: true,
  dismissible: true
});

const permissionsChanged = computed(() => {
  const original = [...(props.userDirectPermissions || [])].sort();
  const current = [...directPermissions.value].sort();
  return JSON.stringify(original) !== JSON.stringify(current);
});

const isRolePermission = (permName) => (props.rolePermissions || []).includes(permName);
const isDirectPermission = (permName) => directPermissions.value.includes(permName);
const togglePermission = (permName) => {
  if (isRolePermission(permName)) return;
  const index = directPermissions.value.indexOf(permName);
  if (index === -1) directPermissions.value.push(permName);
  else directPermissions.value.splice(index, 1);
};

const savePermissions = async () => {
  savingPermissions.value = true;
  try {
    const response = await fetch(`/usuarios/${props.usuario.id}/sync-permissions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ permissions: directPermissions.value })
    });
    const data = await response.json();
    if (data.success) {
      notyf.success('Políticas de seguridad sincronizadas correctamente');
      directPermissions.value = data.userDirectPermissions || [];
    } else throw new Error(data.message);
  } catch (error) {
    notyf.error('Fallo en sincronización: ' + error.message);
  } finally {
    savingPermissions.value = false;
  }
};

const updateAlmacenVenta = async () => {
  try {
    await fetch(`/usuarios/${props.usuario.id}/update-almacen-venta`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ almacen_venta_id: selectedAlmacenVenta.value || null })
    });
    notyf.success('Logística de venta actualizada');
  } catch (e) { notyf.error('Error en logística'); }
};

const updateAlmacenCompra = async () => {
  try {
    await fetch(`/usuarios/${props.usuario.id}/update-almacen-compra`, {
      method: 'POST', headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify({ almacen_compra_id: selectedAlmacenCompra.value || null })
    });
    notyf.success('Logística de compra actualizada');
  } catch (e) { notyf.error('Error en logística'); }
};

const form = useForm({
  name: props.usuario.name,
  email: props.usuario.email,
  telefono: props.usuario.telefono || '',
  roles: props.usuario.roles.map(r => r.name),
  password: '',
  password_confirmation: '',
  costo_hora_interno: props.usuario.costo_hora_interno || 0,
});

const isAdmin = computed(() => props.auth.user?.roles.some(role => ['admin', 'super-admin'].includes(role.name)));

const isFormValid = computed(() => {
  const hasChanges = form.name !== props.usuario.name || form.email !== props.usuario.email || form.telefono !== (props.usuario.telefono || '') || form.costo_hora_interno !== (props.usuario.costo_hora_interno || 0) || form.password || JSON.stringify([...form.roles].sort()) !== JSON.stringify(props.usuario.roles.map(r => r.name).sort());
  const validPassword = !form.password || (form.password.length >= 8 && form.password === form.password_confirmation);
  return hasChanges && validPassword;
});

const formProgress = computed(() => {
  let p = 0; if (form.name) p++; if (form.email) p++; if (form.roles.length > 0) p++; return (p / 3) * 100;
});

const toggleRole = (roleName) => {
  const index = form.roles.indexOf(roleName);
  if (index > -1) form.roles.splice(index, 1);
  else form.roles.push(roleName);
};

const submit = () => {
  form.put(route('usuarios.update', props.usuario.id), {
    onSuccess: () => {
      notyf.success('Registro de usuario actualizado íntegramente.');
      form.reset('password', 'password_confirmation');
    },
    onError: (errors) => {
      notyf.error('Error al procesar la actualización.');
      const firstError = Object.keys(errors)[0];
      const el = document.getElementById(firstError);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
};
</script>

<style scoped>
div, button, input, select, span {
  transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease, color 0.3s ease;
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

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155;
}
</style>
