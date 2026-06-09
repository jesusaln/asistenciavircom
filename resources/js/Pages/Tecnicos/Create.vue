<template>
  <Head title="Crear Técnicos" />
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Header Section -->
    <div class="relative overflow-hidden bg-white/80 backdrop-blur-md border-b border-gray-200/50 shadow-lg">
      <div class="absolute inset-0 bg-gradient-to-r from-blue-600/10 to-indigo-600/10"></div>
      <div class="relative w-full px-4 py-8">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="p-3 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
            </div>
            <div>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                Crear Técnico
              </h1>
              <p class="text-gray-600 mt-1">Registra un nuevo técnico en el sistema</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <Link :href="route('tecnicos.index')" class="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 hover:scale-105">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Volver</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="w-full px-4 py-8">
      <!-- Form Container -->
      <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-200/50">
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-8 py-6 border-b border-gray-200/50">
          <h2 class="text-xl font-semibold text-gray-800 flex items-center space-x-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Información del Técnico</span>
          </h2>
        </div>

        <!-- Form Section -->
        <form @submit.prevent="submit" class="p-8">
          <!-- Global Error Alert -->
          <div v-if="hasErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.994-.833-2.764 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <span class="text-red-700 font-medium">Por favor corrige los errores para continuar</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nombre -->
            <div class="space-y-2">
              <label for="nombre" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Nombre *</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.nombre"
                  type="text"
                  id="nombre"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.nombre }"
                  @blur="convertirAMayusculas('nombre')"
                  placeholder="Ingresa el nombre"
                  required
                />
                <div v-if="form.errors.nombre" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.nombre }}</span>
                </div>
              </div>
            </div>

            <!-- Apellido -->
            <div class="space-y-2">
              <label for="apellido" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Apellido *</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.apellido"
                  type="text"
                  id="apellido"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.apellido }"
                  @blur="convertirAMayusculas('apellido')"
                  placeholder="Ingresa el apellido"
                  required
                />
                <div v-if="form.errors.apellido" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.apellido }}</span>
                </div>
              </div>
            </div>

            <!-- Email -->
            <div class="space-y-2">
              <label for="email" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                </svg>
                <span>Email *</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.email"
                  type="email"
                  id="email"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.email }"
                  placeholder="correo@ejemplo.com"
                  required
                />
                <div v-if="form.errors.email" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.email }}</span>
                </div>
              </div>
            </div>

            <!-- Teléfono -->
            <div class="space-y-2">
              <label for="telefono" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>Teléfono</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.telefono"
                  type="text"
                  id="telefono"
                  maxlength="10"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.telefono }"
                  @input="validarTelefono"
                  placeholder="1234567890"
                />
                <div class="absolute right-3 top-3 text-xs text-gray-400">
                  {{ form.telefono.length }}/10
                </div>
                <div v-if="form.errors.telefono" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.telefono }}</span>
                </div>
              </div>
            </div>

            <!-- Dirección -->
            <div class="md:col-span-2 space-y-2">
              <label for="direccion" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Dirección</span>
              </label>
              <div class="relative">
                <input
                  v-model="form.direccion"
                  type="text"
                  id="direccion"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.direccion }"
                  @blur="convertirAMayusculas('direccion')"
                  placeholder="Ingresa la dirección completa"
                />
                <div v-if="form.errors.direccion" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.direccion }}</span>
                </div>
              </div>
            </div>

            <!-- Usuario Asignado -->
            <div class="md:col-span-2 space-y-2">
              <label for="user_id" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Usuario Asignado</span>
              </label>
              <div class="relative">
                <select
                  v-model="form.user_id"
                  id="user_id"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                  :class="{ 'border-red-300 focus:ring-red-500': form.errors.user_id }"
                >
                  <option value="">Sin usuario asignado</option>
                  <option v-for="usuario in $page.props.usuarios" :key="usuario.id" :value="usuario.id">
                    {{ usuario.name }} ({{ usuario.email }})
                  </option>
                </select>
                <div v-if="form.errors.user_id" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ form.errors.user_id }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Configuración de Márgenes y Comisiones -->
          <div class="border-b border-gray-200 pb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-6 h-6 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
              <span>Márgenes y Comisiones de Venta</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Margen Venta Productos -->
              <div class="space-y-2">
                <label for="margen_venta_productos" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span>Margen Productos (%)</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.margen_venta_productos"
                    type="number"
                    id="margen_venta_productos"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                    :class="{ 'border-red-300 focus:ring-red-500': form.errors.margen_venta_productos }"
                    placeholder="0.00"
                  />
                  <div v-if="form.errors.margen_venta_productos" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ form.errors.margen_venta_productos }}</span>
                  </div>
                </div>
              </div>

              <!-- Margen Venta Servicios -->
              <div class="space-y-2">
                <label for="margen_venta_servicios" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>Margen Servicios (%)</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.margen_venta_servicios"
                    type="number"
                    id="margen_venta_servicios"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                    :class="{ 'border-red-300 focus:ring-red-500': form.errors.margen_venta_servicios }"
                    placeholder="0.00"
                  />
                  <div v-if="form.errors.margen_venta_servicios" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ form.errors.margen_venta_servicios }}</span>
                  </div>
                </div>
              </div>

              <!-- Comisión Instalación -->
              <div class="space-y-2">
                <label for="comision_instalacion" class="block text-sm font-semibold text-gray-700 flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                  <span>Comisión Instalación ($)</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.comision_instalacion"
                    type="number"
                    id="comision_instalacion"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm transition-all duration-200 hover:shadow-md"
                    :class="{ 'border-red-300 focus:ring-red-500': form.errors.comision_instalacion }"
                    placeholder="0.00"
                  />
                  <div v-if="form.errors.comision_instalacion" class="absolute -bottom-6 left-0 flex items-center space-x-1 text-red-500 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ form.errors.comision_instalacion }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end space-x-4 mt-12 pt-6 border-t border-gray-200">
            <Link :href="route('tecnicos.index')" class="flex items-center space-x-2 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 hover:scale-105">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span>Cancelar</span>
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="flex items-center space-x-2 px-8 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 hover:scale-105 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
            >
              <svg v-if="form.processing" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ form.processing ? 'Guardando...' : 'Guardar Técnico' }}</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Form Preview Card -->
      <div v-if="hasFormData" class="mt-8 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl overflow-hidden border border-gray-200/50">
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200/50">
          <h3 class="text-lg font-semibold text-gray-800 flex items-center space-x-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span>Vista Previa</span>
          </h3>
        </div>
        <div class="p-6">
          <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-lg">
              {{ getInitials() }}
            </div>
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-gray-900">{{ form.nombre }} {{ form.apellido }}</h4>
              <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                <div v-if="form.email" class="flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                  </svg>
                  <span>{{ form.email }}</span>
                </div>
                <div v-if="form.telefono" class="flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span>{{ form.telefono }}</span>
                </div>
                <div v-if="form.direccion" class="flex items-center space-x-2">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span>{{ form.direccion }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Formulario para crear un técnico
const form = useForm({
  nombre: '',
  apellido: '',
  email: '',
  telefono: '',
  direccion: '',
  user_id: '',
  margen_venta_productos: '',
  margen_venta_servicios: '',
  comision_instalacion: ''
});

// Computed properties
const hasErrors = computed(() => {
  return Object.keys(form.errors).length > 0;
});

const hasFormData = computed(() => {
  return form.nombre.trim() !== '' || form.apellido.trim() !== '';
});

// Instancia de Notyf
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    {
      type: 'success',
      background: 'linear-gradient(135deg, #10b981, #059669)',
      icon: {
        className: 'notyf__icon--success',
        tagName: 'i'
      }
    },
    {
      type: 'error',
      background: 'linear-gradient(135deg, #ef4444, #dc2626)',
      icon: {
        className: 'notyf__icon--error',
        tagName: 'i'
      }
    }
  ]
});

// Función para obtener iniciales
const getInitials = () => {
  const nombre = form.nombre.trim();
  const apellido = form.apellido.trim();
  if (!nombre && !apellido) return '';
  return `${nombre.charAt(0)}${apellido.charAt(0)}`.toUpperCase();
};

// Función para enviar el formulario
const submit = () => {
  form.post(route('tecnicos.store'), {
    onSuccess: () => {
      notyf.success('Técnico creado exitosamente');
      form.reset();
    },
    onError: (errors) => {
      notyf.error('Por favor corrige los errores en el formulario');
      console.error('Errores:', errors);
    }
  });
};

// Método para convertir a mayúsculas
const convertirAMayusculas = (campo) => {
  if (form[campo]) {
    form[campo] = form[campo].toUpperCase();
  }
};

// Validación del teléfono
const validarTelefono = (event) => {
  // Solo permitir números
  const value = event.target.value.replace(/\D/g, '');
  form.telefono = value;

  // Validar longitud
  if (value.length > 0 && value.length !== 10) {
    form.setError('telefono', 'El teléfono debe tener exactamente 10 dígitos.');
  } else {
    form.clearErrors('telefono');
  }
};
</script>

<style scoped>
/* Animaciones personalizadas */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-slide-in-up {
  animation: slideInUp 0.5s ease-out;
}

/* Efectos de focus mejorados */
input:focus {
  transform: translateY(-1px);
  box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.25);
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(135deg, #2563eb, #4f46e5);
}

/* Transiciones suaves para los estados de error */
.border-red-300 {
  transition: all 0.2s ease;
}

/* Efectos de hover en los labels */
label:hover {
  transform: translateX(2px);
  transition: transform 0.2s ease;
}
</style>

