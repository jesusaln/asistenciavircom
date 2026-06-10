<template>
  <Head title="Crear Usuario" />
  <div class="usuarios-premium min-h-screen bg-[var(--ui-surface)] py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full mb-4">
          <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
        </div>
        <h1 class="text-3xl font-bold text-slate-900 mb-2">Crear Nuevo Usuario</h1>
        <p class="text-slate-500">Completa la información para registrar un nuevo usuario en el sistema</p>
      </div>

      <!-- Form Card -->
      <div class="bg-white/80 dark:bg-slate-950/80 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-slate-200/60 dark:border-slate-800/50 overflow-hidden">
        <!-- Progress Bar -->
        <div class="h-1 bg-slate-100">
          <div class="h-1 bg-gradient-to-r from-brand-500 to-brand-600 transition-all duration-200"
               :style="`width: ${formProgress}%`"></div>
        </div>

        <form @submit.prevent="submit" class="p-8 space-y-6">
          <!-- Personal Information Section -->
          <div class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Información Personal
              </h2>
              <p class="text-sm text-slate-500 mt-1">Datos básicos del usuario</p>
            </div>

            <!-- Nombre -->
            <div class="form-group">
              <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Nombre Completo *
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <input
                  v-model="form.name"
                  type="text"
                  id="name"
                  class="block w-full pl-10 pr-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 hover:bg-white dark:hover:bg-slate-700 dark:text-white"
                  placeholder="Ingresa el nombre completo"
                  :class="{
                    'border-rose-300 bg-rose-50 dark:bg-rose-900/20 focus:ring-brand-500': form.errors.name,
                    'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20': form.name && !form.errors.name
                  }"
                  autocomplete="name"
                />
                <div v-if="form.name && !form.errors.name" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
              <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Correo Electrónico *
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                  </svg>
                </div>
                <input
                  v-model="form.email"
                  type="email"
                  id="email"
                  class="block w-full pl-10 pr-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 hover:bg-white dark:hover:bg-slate-700 dark:text-white"
                  placeholder="correo@ejemplo.com"
                  :class="{
                    'border-rose-300 bg-rose-50 dark:bg-rose-900/20 focus:ring-brand-500': form.errors.email,
                    'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20': form.email && !form.errors.email && isValidEmail
                  }"
                  autocomplete="email"
                />
                <div v-if="form.email && !form.errors.email && isValidEmail" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <svg class="h-5 w-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
              <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Teléfono -->
            <div class="form-group">
              <label for="telefono" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                Teléfono
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                </div>
                <input
                  v-model="form.telefono"
                  type="tel"
                  id="telefono"
                  class="block w-full pl-10 pr-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 hover:bg-white dark:hover:bg-slate-700 dark:text-white"
                  placeholder="Número de teléfono"
                />
              </div>
              <InputError class="mt-2" :message="form.errors.telefono" />
            </div>
          </div>


            <!-- Configuración de Almacenes -->
            <div class="space-y-6 border-t pt-6">
              <div class="border-b border-slate-200 pb-4">
                 <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                    <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Configuración de Almacenes
                 </h2>
                 <p class="text-sm text-slate-500 mt-1">Define los almacenes predeterminados para el usuario (Opcional)</p>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div class="form-group">
                     <label for="almacen_venta_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Almacén de Venta Predeterminado</label>
                    <select id="almacen_venta_id" v-model="form.almacen_venta_id" class="block w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 text-slate-700 dark:text-white">
                       <option :value="null">Seleccione un almacén...</option>
                       <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                    </select>
                    <InputError :message="form.errors.almacen_venta_id" class="mt-2" />
                 </div>
                 
                 <div class="form-group">
                     <label for="almacen_compra_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">Almacén de Compra Predeterminado</label>
                    <select id="almacen_compra_id" v-model="form.almacen_compra_id" class="block w-full px-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 text-slate-700 dark:text-white">
                       <option :value="null">Seleccione un almacén...</option>
                       <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">{{ almacen.nombre }}</option>
                    </select>
                    <InputError :message="form.errors.almacen_compra_id" class="mt-2" />
                 </div>
              </div>
            </div>

            <!-- Asignación de Roles -->
            <div class="space-y-6 border-t pt-6">
              <div class="border-b border-slate-200 pb-4">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                  </svg>
                  Roles y Permisos
                </h2>
                <p class="text-sm text-slate-500 mt-1">Selecciona los roles que tendrá el usuario. Cada rol incluye permisos predefinidos.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="role in roles" :key="role.id" 
                     class="relative border rounded-xl p-4 hover:shadow-xl transition-all duration-200 cursor-pointer"
                     :class="form.roles.includes(role.name) ? 'border-purple-400 bg-purple-50 dark:bg-purple-900/20 ring-2 ring-purple-200' : 'border-slate-200 dark:border-slate-700'"
                     @click="toggleRole(role.name)">
                  
                  <!-- Checkbox visual -->
                  <div class="absolute top-3 right-3">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center transition-all"
                         :class="form.roles.includes(role.name) ? 'border-purple-600 bg-purple-600' : 'border-slate-300 bg-white dark:bg-slate-800'">
                      <svg v-if="form.roles.includes(role.name)" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                  </div>

                  <!-- Rol Header -->
                  <div class="mb-3">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ role.label }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-200">
                      {{ role.permissions_count }} permisos
                    </span>
                  </div>

                  <!-- Permisos Summary -->
                  <div class="space-y-1">
                    <div v-for="(count, action) in role.permissions_summary" :key="action" class="flex items-center justify-between text-xs">
                      <span class="text-slate-500 dark:text-slate-400 capitalize">{{ action }}</span>
                      <span class="font-semibold text-slate-700 dark:text-slate-200">{{ count }}</span>
                    </div>
                  </div>

                  <!-- Ejemplo de Permisos -->
                  <div v-if="role.permissions_list && role.permissions_list.length > 0" class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Incluye:</p>
                    <div class="flex flex-wrap gap-1">
                      <span v-for="perm in role.permissions_list.slice(0, 3)" :key="perm" class="inline-block px-2 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-200 rounded-xl text-[10px] font-medium">
                        {{ perm }}
                      </span>
                      <span v-if="role.permissions_count > 3" class="inline-block px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-200 rounded-xl text-[10px] font-medium">
                        +{{ role.permissions_count - 3 }} más
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              <InputError :message="form.errors.roles" class="mt-2" />
            </div>

            <!-- Atributos Especiales (Técnico) -->
            <div class="space-y-6 border-t pt-6">
              <div class="border-b border-slate-200 pb-4">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                  <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  Atributos Especiales
                </h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configura atributos adicionales para el nuevo usuario</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Técnico -->
              <div class="flex items-center space-x-3 p-4 bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer transition-all duration-200"
                   :class="form.es_tecnico ? 'ring-2 ring-blue-500 border-blue-500 bg-sky-50 dark:bg-sky-900/20/50 dark:bg-sky-900/20' : 'hover:border-brand-500 dark:hover:border-brand-500'"
                   @click="form.es_tecnico = !form.es_tecnico">
                <div class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                     :class="form.es_tecnico ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                  <span class="sr-only">Es técnico</span>
                  <span aria-hidden="true" 
                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                        :class="form.es_tecnico ? 'translate-x-5' : 'translate-x-0'"></span>
                </div>
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-900 dark:text-white">Marcar como Técnico</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">Permite asignar citas y órdenes de servicio.</span>
                </div>
              </div>

              <!-- Empleado -->
              <div class="flex items-center space-x-3 p-4 bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer transition-all duration-200"
                   :class="form.es_empleado ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/20' : 'hover:border-brand-500 dark:hover:border-brand-500'"
                   @click="form.es_empleado = !form.es_empleado">
                <div class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none"
                     :class="form.es_empleado ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-700'">
                  <span class="sr-only">Es empleado</span>
                  <span aria-hidden="true" 
                        class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                        :class="form.es_empleado ? 'translate-x-5' : 'translate-x-0'"></span>
                </div>
                <div class="flex flex-col">
                  <span class="text-sm font-bold text-slate-900 dark:text-white">Marcar como Empleado</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400">Habilita vacaciones y nómina.</span>
                </div>
              </div>
            </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <InputError :message="form.errors.es_tecnico" class="mt-2" />
              <InputError :message="form.errors.es_empleado" class="mt-2" />
            </div>
            </div>

            <!-- Acceso Multi-Empresa -->
            <div v-if="props.available_companies" class="space-y-6 border-t pt-6">
              <div class="border-b border-slate-200 pb-4">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  Acceso Multi-Empresa
                </h2>
                <p class="text-sm text-slate-500 mt-1">Selecciona las empresas a las que este usuario podrá acceder.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="empresa in props.available_companies" :key="empresa.id"
                     class="flex items-center space-x-3 p-4 bg-slate-100/50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl cursor-pointer transition-all duration-200"
                     :class="(isCreatingSuperAdmin || form.empresas_acceso.includes(empresa.id)) ? 'ring-2 ring-indigo-500 border-indigo-200 bg-indigo-50 dark:bg-indigo-900/20' : 'hover:border-indigo-500 dark:hover:border-indigo-500'"
                     @click="!isCreatingSuperAdmin && toggleEmpresa(empresa.id)">
                  <div class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full transition-colors ease-in-out duration-200 focus:outline-none"
                       :class="[
                         (isCreatingSuperAdmin || form.empresas_acceso.includes(empresa.id)) ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-700',
                         isCreatingSuperAdmin ? 'cursor-not-allowed opacity-70' : 'cursor-pointer'
                       ]">
                    <span class="sr-only">{{ empresa.nombre }}</span>
                    <span aria-hidden="true" 
                          class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                          :class="(isCreatingSuperAdmin || form.empresas_acceso.includes(empresa.id)) ? 'translate-x-5' : 'translate-x-0'"></span>
                  </div>
                  <div class="flex flex-col">
                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ empresa.nombre }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Permitir acceso a este entorno.</span>
                  </div>
                </div>
              </div>
              <InputError :message="form.errors.empresas_acceso" class="mt-2" />
            </div>

          <!-- Security Section -->
          <div class="space-y-6">
            <div class="border-b border-slate-200 pb-4">
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                <svg class="w-4 h-4 mr-2 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Seguridad
              </h2>
              <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configuración de contraseña de acceso</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Contraseña -->
              <div class="form-group">
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                  Contraseña *
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                  </div>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    class="block w-full pl-10 pr-12 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 dark:text-white"
                    placeholder="Mínimo 8 caracteres"
                    :class="{
                      'border-rose-300 bg-rose-50 dark:bg-rose-900/20 focus:ring-brand-500': form.errors.password,
                      'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20': form.password && form.password.length >= 8 && !form.errors.password
                    }"
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-600"
                  >
                    <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>
                </div>
                <div class="mt-2">
                  <div class="flex items-center space-x-2">
                    <div class="flex space-x-1">
                      <div v-for="i in 4" :key="i"
                           class="h-1 w-6 rounded-full transition-all duration-200"
                           :class="passwordStrength >= i ? 'bg-brand-500' : 'bg-slate-200'"></div>
                    </div>
                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ passwordStrengthText }}</span>
                  </div>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
              </div>

              <!-- Confirmar Contraseña -->
              <div class="form-group">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">
                  Confirmar Contraseña *
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                  </div>
                  <input
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirmation ? 'text' : 'password'"
                    id="password_confirmation"
                    class="block w-full pl-10 pr-12 py-3 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-slate-800 hover:bg-white dark:hover:bg-slate-700 dark:text-white"
                    placeholder="Repite la contraseña"
                    :class="{
                      'border-rose-300 bg-rose-50 dark:bg-rose-900/20 focus:ring-brand-500': form.errors.password_confirmation || (form.password_confirmation && form.password !== form.password_confirmation),
                      'border-emerald-300 bg-emerald-50 dark:bg-emerald-900/20': form.password_confirmation && form.password === form.password_confirmation && !form.errors.password_confirmation
                    }"
                    autocomplete="new-password"
                  />
                  <button
                    type="button"
                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-600"
                  >
                    <svg v-if="showPasswordConfirmation" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </button>
                </div>
                <div v-if="form.password_confirmation && form.password !== form.password_confirmation" class="mt-2 text-sm text-rose-600 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  Las contraseñas no coinciden
                </div>
                <div v-else-if="form.password_confirmation && form.password === form.password_confirmation" class="mt-2 text-sm text-emerald-600 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Las contraseñas coinciden
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-6 border-t border-slate-200">
            <div class="flex flex-col sm:flex-row gap-4 justify-end">
              <Link :href="route('usuarios.index')"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-slate-300 rounded-xl text-slate-700 bg-white hover:bg-white font-semibold transition-all duration-200 hover:shadow-xl">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancelar
              </Link>
              <button
                type="submit"
                class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-200 hover:shadow-xl transform hover:shadow-xl hover:shadow-xl.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:hover:shadow-none"
                :disabled="form.processing || !isFormValid"
              >
                <div v-if="form.processing" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Creando Usuario...</span>
                </div>
                <div v-else class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                  </svg>
                  <span>Crear Usuario</span>
                </div>
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
  roles: Array, // Lista de roles desde el backend
  almacenes: Array, // Lista de almacenes activos
  available_companies: Array,
});

// Reactive variables para mostrar/ocultar contraseñas
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Form usando Inertia
const form = useForm({
  name: '',
  email: '',
  telefono: '',
  roles: [],
  password: '',
  password_confirmation: '',
  es_tecnico: false,
  es_empleado: false,
  almacen_venta_id: null,
  almacen_compra_id: null,
  empresas_acceso: [],
});

// Instancia de Notyf para notificaciones
const notyf = new Notyf({
  duration: 3000,
  position: { x: 'right', y: 'top' },
  ripple: true,
  dismissible: true
});

// Computed para validar email
const isCreatingSuperAdmin = computed(() => {
  return form.roles.includes('super-admin');
});

const isValidEmail = computed(() => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(form.email);
});

// Computed para calcular la fortaleza de la contraseña
const passwordStrength = computed(() => {
  const password = form.password;
  if (!password) return 0;

  let strength = 0;

  // Longitud mínima
  if (password.length >= 8) strength++;

  // Contiene números
  if (/\d/.test(password)) strength++;

  // Contiene letras minúsculas y mayúsculas
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;

  // Contiene caracteres especiales
  if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;

  return strength;
});

// Computed para el texto de fortaleza de contraseña
const passwordStrengthText = computed(() => {
  switch (passwordStrength.value) {
    case 0:
    case 1:
      return 'Débil';
    case 2:
      return 'Regular';
    case 3:
      return 'Buena';
    case 4:
      return 'Excelente';
    default:
      return '';
  }
});

// Computed para verificar si el formulario es válido
const isFormValid = computed(() => {
  return form.name &&
         form.email &&
         isValidEmail.value &&
         form.password &&
         form.password.length >= 8 &&
         form.password_confirmation &&
         form.password === form.password_confirmation;
});

// Computed para el progreso del formulario
const formProgress = computed(() => {
  let progress = 0;
  const totalFields = 3; // nombre, email, contraseña

  if (form.name) progress++;
  if (form.email && isValidEmail.value) progress++;
  if (form.password && form.password.length >= 8) progress++;

  return (progress / totalFields) * 100;
});

// Toggle role selection
const toggleRole = (roleName) => {
  const index = form.roles.indexOf(roleName);
  if (index > -1) {
    form.roles.splice(index, 1);
  } else {
    form.roles.push(roleName);
  }
};

// Toggle empresa selection
const toggleEmpresa = (empresaId) => {
  const index = form.empresas_acceso.indexOf(empresaId);
  if (index > -1) {
    form.empresas_acceso.splice(index, 1);
  } else {
    form.empresas_acceso.push(empresaId);
  }
};

// Función para enviar el formulario
const submit = () => {
  form.post(route('usuarios.store'), {
    onSuccess: () => {
      notyf.success('Usuario creado exitosamente.');
    },
    onError: (errors) => {
      notyf.error('Error al crear el usuario.');
      // Scroll al primer error
      const firstError = Object.keys(errors)[0];
      const el = document.getElementById(firstError);
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },
    onFinish: () => {
      // Se ejecuta siempre al terminar la petición
      console.log('Petición terminada');
    }
  });
};
</script>

<style scoped>
.form-group {
  margin-bottom: 1.5rem;
}

/* Estilos para el input focus */
input:focus-visible, select:focus-visible {
  outline: 2px solid #6366f1;
  outline-offset: 2px;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

/* Animación para los botones */
button:not(:disabled):hover {
  transform: translateY(-1px);
}

button:disabled {
  background-color: #d1d5db;
  cursor: not-allowed;
  transform: none;
}

/* Estilos para el select personalizado */
select {
  background-image: none;
}

/* Animaciones suaves */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Efecto hover para los inputs */
input:hover:not(:focus), select:hover:not(:focus) {
  border-color: #9ca3af;
}

/* Estilo para campos válidos */
.border-emerald-300 {
  border-color: #86efac;
}

.bg-emerald-50 {
  background-color: #f0fdf4;
}

/* Estilo para campos con error */
.border-rose-300 {
  border-color: #fca5a5;
}

.bg-rose-50 {
  background-color: #fef2f2;
}
</style>
