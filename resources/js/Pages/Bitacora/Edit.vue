<template>
  <Head title="Editar Actividad" />
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      <!-- Header mejorado -->
      <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">Editar Actividad</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">Actualiza los detalles de tu actividad</p>
              </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-3 py-2 rounded-lg">
              <div class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Creada: {{ formatDate(actividad.created_at) }}
              </div>
              <span v-if="actividad.updated_at !== actividad.created_at" class="text-gray-400 dark:text-gray-500">•</span>
              <div v-if="actividad.updated_at !== actividad.created_at" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualizada: {{ formatDate(actividad.updated_at) }}
              </div>
            </div>
          </div>
          <div class="flex gap-2">
            <Link
              :href="route('bitacora.show', actividad.id)"
              class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-600 hover:border-gray-300 transition-all duration-200 shadow-sm"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              Ver detalles
            </Link>
            <Link
              :href="route('bitacora.index')"
              class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-200 transition-all duration-200"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
              </svg>
              Volver al listado
            </Link>
          </div>
        </div>
      </div>

      <!-- Indicadores de estado mejorados -->
      <div class="space-y-4 mb-6">
        <!-- Errores -->
        <Transition name="slide-down">
          <div v-if="hasAnyError" class="rounded-xl border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20 p-4 shadow-sm">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-400 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-semibold text-red-800 dark:text-red-300">Por favor, corrige los siguientes errores:</h3>
                <div class="mt-2 text-sm text-red-700 dark:text-red-200">
                  <ul class="space-y-1">
                    <li v-for="(msg, key) in form.errors" :key="key" class="flex items-center">
                      <svg class="w-3 h-3 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 8 8">
                        <circle cx="4" cy="4" r="3"/>
                      </svg>
                      {{ msg }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </Transition>

        <!-- Cambios -->
        <Transition name="slide-down">
          <div v-if="hasChanges" class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4 shadow-sm">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </div>
                <div class="ml-3">
                  <span class="font-medium text-amber-800 dark:text-amber-300 text-sm">Hay cambios sin guardar</span>
                  <p class="text-xs text-amber-700 dark:text-amber-200 mt-0.5">{{ changedFieldsCount }} campo(s) modificado(s)</p>
                </div>
              </div>
              <button
                type="button"
                @click="showChangesModal = true"
                class="text-xs text-amber-700 dark:text-amber-300 hover:text-amber-800 dark:hover:text-amber-400 underline"
              >
                Ver cambios
              </button>
            </div>
          </div>
        </Transition>

        <!-- Progreso de completitud -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Completitud del formulario</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ Math.round(formCompleteness) }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div
              class="bg-gradient-to-r from-blue-500 to-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${formCompleteness}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Formulario principal -->
      <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="p-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Título -->
            <div class="md:col-span-2">
              <label for="titulo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Título de la actividad
                <span class="text-red-500 ml-1">*</span>
              </label>
              <div class="relative">
                <input
                  id="titulo"
                  v-model.trim="form.titulo"
                  type="text"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('titulo')"
                  placeholder="Ej. Instalación minisplit sucursal Centro"
                  maxlength="150"
                  autocomplete="off"
                  @input="validateField('titulo')"
                />
                <div v-if="form.titulo && !form.errors.titulo" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                  <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
              </div>
              <div class="flex justify-between mt-2">
                <p v-if="form.errors.titulo" class="text-sm text-red-600 dark:text-red-400 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  {{ form.errors.titulo }}
                </p>
                <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">{{ (form.titulo || '').length }} / 150</span>
              </div>
            </div>

            <!-- Empleado -->
            <div>
              <label for="user_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Empleado responsable
                <span class="text-red-500 ml-1">*</span>
              </label>
              <div class="relative">
                <select
                  id="user_id"
                  v-model="form.user_id"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('user_id')"
                  @change="validateField('user_id')"
                >
                  <option value="" disabled>Selecciona un empleado</option>
                  <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              <p v-if="form.errors.user_id" class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ form.errors.user_id }}
              </p>
            </div>

            <!-- Cliente -->
            <div>
              <label for="cliente_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cliente</label>
              <div class="relative">
                <select
                  id="cliente_id"
                  v-model="form.cliente_id"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('cliente_id')"
                >
                  <option :value="null">— Sin cliente específico —</option>
                  <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_razon_social }}</option>
                </select>
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              <p v-if="form.errors.cliente_id" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.cliente_id }}</p>
            </div>

            <!-- Asignar a (usuario asignado) -->
            <div>
              <label for="asignado_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Asignar a
                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(opcional)</span>
              </label>
              <div class="relative">
                <select
                  id="asignado_id"
                  v-model="form.asignado_id"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 appearance-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('asignado_id')"
                >
                  <option :value="null">— Sin asignar (automático) —</option>
                  <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              <p v-if="form.errors.asignado_id" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.asignado_id }}</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                La tarea aparecerá en el panel del usuario asignado.
              </p>
            </div>

            <!-- Tipo -->
            <div>
              <label for="tipo" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Tipo de actividad
                <span class="text-red-500 ml-1">*</span>
              </label>
              <div class="relative">
                <select
                  id="tipo"
                  v-model="form.tipo"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('tipo')"
                  @change="validateField('tipo')"
                >
                  <option value="" disabled>Selecciona un tipo</option>
                  <option v-for="t in tipos" :key="t" :value="t" class="capitalize">{{ formatLabel(t) }}</option>
                </select>
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              <p v-if="form.errors.tipo" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.tipo }}</p>
            </div>

            <!-- Estado -->
            <div>
              <label for="estado" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Estado actual
                <span class="text-red-500 ml-1">*</span>
              </label>
              <div class="relative">
                <select
                  id="estado"
                  v-model="form.estado"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 appearance-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('estado')"
                  @change="validateField('estado')"
                >
                  <option v-for="e in estados" :key="e" :value="e">{{ formatLabel(e) }}</option>
                </select>
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </div>
              <div class="mt-2 flex items-center justify-between">
                <div class="flex items-center">
                  <div class="w-2 h-2 rounded-full mr-2" :class="getEstadoIndicator(form.estado)"></div>
                  <span class="text-xs text-gray-600 dark:text-gray-300">{{ getEstadoDescription(form.estado) }}</span>
                </div>
                <span v-if="hasEstadoChanged" class="text-xs text-blue-600 dark:text-blue-400 font-medium bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded">
                  Antes: {{ formatLabel(actividad.estado) }}
                </span>
              </div>
              <p v-if="form.errors.estado" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.estado }}</p>
            </div>

            <!-- Fecha y hora de inicio -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label for="inicio_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                  Fecha y hora de inicio
                  <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="flex gap-1">
                  <button
                    type="button"
                    @click="setNow('inicio_at')"
                    class="text-xs px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                  >
                    Ahora
                  </button>
                </div>
              </div>
              <input
                id="inicio_at"
                v-model="form.inicio_at"
                type="datetime-local"
                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                :class="getInputClass('inicio_at')"
                @change="validateField('inicio_at')"
              />
              <p v-if="form.errors.inicio_at" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.inicio_at }}</p>
            </div>

            <!-- Fecha y hora de fin -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label for="fin_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Fecha y hora de fin</label>
                <div class="flex gap-1 flex-wrap">
                  <button type="button" @click="setNow('fin_at')" class="text-xs px-2 py-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    Ahora
                  </button>
                  <button type="button" @click="sumarMinutos(30)" class="text-xs px-2 py-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    +30m
                  </button>
                  <button type="button" @click="sumarMinutos(60)" class="text-xs px-2 py-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    +1h
                  </button>
                  <button type="button" @click="sumarMinutos(120)" class="text-xs px-2 py-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                    +2h
                  </button>
                </div>
              </div>
              <input
                id="fin_at"
                v-model="form.fin_at"
                type="datetime-local"
                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                :class="getInputClass('fin_at')"
              />
              <div class="mt-2 space-y-1">
                <p v-if="form.errors.fin_at" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.fin_at }}</p>
                <p v-if="duracionTexto" class="text-xs text-gray-600 dark:text-gray-300 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Duración: <span class="font-medium text-gray-800 dark:text-gray-100 ml-1">{{ duracionTexto }}</span>
                </p>
                <p v-if="duracionWarning" class="text-xs text-amber-600 dark:text-amber-400 flex items-center bg-amber-50 dark:bg-amber-900/20 px-2 py-1 rounded">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.339 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                  </svg>
                  {{ duracionWarning }}
                </p>
              </div>
            </div>

            <!-- Ubicación -->
            <div class="md:col-span-2">
              <label for="ubicacion" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ubicación</label>
              <div class="relative">
                <input
                  id="ubicacion"
                  v-model.trim="form.ubicacion"
                  type="text"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('ubicacion')"
                  placeholder="Ej. Sucursal Centro, Blvd. X, #123"
                  maxlength="255"
                />
                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500 absolute right-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <div class="flex justify-between mt-2">
                <p v-if="form.errors.ubicacion" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.ubicacion }}</p>
                <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">{{ (form.ubicacion || '').length }} / 255</span>
              </div>
            </div>

            <!-- Descripción -->
            <div class="md:col-span-2">
              <div class="flex items-center justify-between mb-2">
                <label for="descripcion" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Descripción detallada</label>
                <span class="text-xs text-gray-400 dark:text-gray-500">{{ (form.descripcion || '').length }} / 5000</span>
              </div>
              <div class="relative">
                <textarea
                  id="descripcion"
                  v-model.trim="form.descripcion"
                  rows="5"
                  class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 resize-none bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
                  :class="getInputClass('descripcion')"
                  placeholder="Detalle las actividades realizadas, materiales usados, pendientes, observaciones, etc."
                  maxlength="5000"
                  @input="autoResize"
                ></textarea>
              </div>
              <p v-if="form.errors.descripcion" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.descripcion }}</p>

              <!-- Plantillas de descripción -->
              <div class="mt-3">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Plantillas rápidas:</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="template in descripcionTemplates"
                    :key="template"
                    type="button"
                    @click="addTemplate(template)"
                    class="text-xs px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors border border-blue-200 dark:border-blue-700"
                  >
                    + {{ template }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer del formulario -->
        <div class="px-8 py-6 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 rounded-b-2xl">
          <div class="flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-start">
              <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span>Se registrará automáticamente el usuario que actualiza y la fecha/hora del cambio.</span>
            </div>
            <div class="flex gap-3">
              <button
                type="button"
                @click="resetForm"
                :disabled="form.processing || !hasChanges"
                class="px-6 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-white dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-sm"
              >
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Deshacer cambios
              </button>
              <button
                type="submit"
                :disabled="form.processing || !isFormValid || !hasChanges"
                class="px-6 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 font-medium shadow-lg"
              >
                <span v-if="!form.processing" class="flex items-center">
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  Actualizar actividad
                </span>
                <span v-else class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                  Guardando cambios...
                </span>
              </button>
            </div>
          </div>
        </div>
      </form>

      <!-- Modal de cambios -->
      <Transition name="modal">
        <div v-if="showChangesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50" @click="showChangesModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Cambios realizados</h3>
                <button @click="showChangesModal = false" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
            <div class="px-6 py-4 max-h-96 overflow-y-auto">
              <div class="space-y-3">
                <div v-for="(change, field) in changedFields" :key="field" class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                  <div class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">{{ getFieldLabel(field) }}</div>
                  <div class="text-xs text-amber-700 dark:text-amber-200">
                    <div class="mb-1">Antes: <span class="font-mono">{{ change.original || '(vacío)' }}</span></div>
                    <div>Ahora: <span class="font-mono">{{ change.current || '(vacío)' }}</span></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
              <button
                @click="showChangesModal = false"
                class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Información adicional mejorada -->
      <div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6 shadow-sm">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="ml-4 flex-1">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-3">Consejos para una mejor edición</h3>
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <h4 class="text-xs font-medium text-blue-800 dark:text-blue-200 mb-2">🎯 Seguimiento</h4>
                <ul class="text-xs text-blue-700 dark:text-blue-100 space-y-1">
                  <li>• Los cambios se resaltan automáticamente</li>
                  <li>• Puedes ver un resumen de modificaciones</li>
                  <li>• El progreso se muestra en tiempo real</li>
                </ul>
              </div>
              <div>
                <h4 class="text-xs font-medium text-blue-800 dark:text-blue-200 mb-2">⚡ Productividad</h4>
                <ul class="text-xs text-blue-700 dark:text-blue-100 space-y-1">
                  <li>• Usa las plantillas para descripciones</li>
                  <li>• Los botones rápidos facilitan las fechas</li>
                  <li>• Validación en tiempo real</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Atajos de teclado -->
      <div class="mt-4 text-center">
        <details class="group">
          <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
            💡 Mostrar atajos de teclado
          </summary>
          <div class="mt-3 bg-gray-900 text-gray-300 rounded-xl p-4 text-xs">
            <div class="grid md:grid-cols-3 gap-4">
              <div>
                <div class="font-medium text-white mb-2">Navegación</div>
                <div class="space-y-1">
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Tab</kbd> Siguiente campo</div>
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Shift+Tab</kbd> Campo anterior</div>
                </div>
              </div>
              <div>
                <div class="font-medium text-white mb-2">Acciones</div>
                <div class="space-y-1">
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Ctrl+S</kbd> Guardar</div>
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Ctrl+Z</kbd> Deshacer</div>
                </div>
              </div>
              <div>
                <div class="font-medium text-white mb-2">Tiempo</div>
                <div class="space-y-1">
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Ctrl+N</kbd> Fecha actual</div>
                  <div><kbd class="bg-gray-800 dark:bg-gray-700 px-1 rounded">Ctrl+H</kbd> +1 hora</div>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  actividad: Object,
  usuarios: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  tipos: { type: Array, default: () => [] },
  estados: { type: Array, default: () => ['pendiente', 'en_proceso', 'completado', 'cancelado'] },
})

// Estado reactivo
const showChangesModal = ref(false)
const fieldValidationErrors = ref({})

// Plantillas para descripción
const descripcionTemplates = [
  'Materiales utilizados:',
  'Tiempo estimado:',
  'Pendientes:',
  'Observaciones:',
  'Estado del equipo:',
  'Próximos pasos:',
  'Dificultades encontradas:',
  'Recomendaciones:'
]

// Función para formatear fechas correctamente
function formatDateTime(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  const pad = (num) => num.toString().padStart(2, '0')

  const year = date.getFullYear()
  const month = pad(date.getMonth() + 1)
  const day = pad(date.getDate())
  const hours = pad(date.getHours())
  const minutes = pad(date.getMinutes())

  return `${year}-${month}-${day}T${hours}:${minutes}`
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Datos originales para comparar cambios
const originalData = {
  titulo: props.actividad.titulo,
  user_id: props.actividad.user_id,
  asignado_id: props.actividad.asignado_id,
  cliente_id: props.actividad.cliente_id,
  tipo: props.actividad.tipo,
  estado: props.actividad.estado,
  inicio_at: formatDateTime(props.actividad.inicio_at),
  fin_at: formatDateTime(props.actividad.fin_at),
  ubicacion: props.actividad.ubicacion,
  descripcion: props.actividad.descripcion,
}

const form = useForm({ ...originalData })

// Computed properties mejoradas
const hasAnyError = computed(() => Object.keys(form.errors || {}).length > 0)

const isFormValid = computed(() => {
  return form.titulo && form.user_id && form.tipo && form.estado && form.inicio_at
})

const hasChanges = computed(() => {
  return Object.keys(originalData).some(key => form[key] !== originalData[key])
})

const changedFields = computed(() => {
  const changes = {}
  Object.keys(originalData).forEach(key => {
    if (form[key] !== originalData[key]) {
      changes[key] = {
        original: originalData[key],
        current: form[key]
      }
    }
  })
  return changes
})

const changedFieldsCount = computed(() => Object.keys(changedFields.value).length)

const hasEstadoChanged = computed(() => form.estado !== originalData.estado)

const formCompleteness = computed(() => {
  const totalFields = 9 // Campos totales
  const requiredFields = ['titulo', 'user_id', 'tipo', 'estado', 'inicio_at']
  const optionalFields = ['cliente_id', 'fin_at', 'ubicacion', 'descripcion']

  let filledRequired = requiredFields.filter(field => form[field]).length
  let filledOptional = optionalFields.filter(field => form[field]).length

  // Los campos requeridos tienen más peso
  const requiredWeight = 0.7
  const optionalWeight = 0.3

  const requiredScore = (filledRequired / requiredFields.length) * requiredWeight * 100
  const optionalScore = (filledOptional / optionalFields.length) * optionalWeight * 100

  return Math.min(requiredScore + optionalScore, 100)
})

const duracionWarning = computed(() => {
  if (!form.inicio_at || !form.fin_at) return ''
  const i = new Date(form.inicio_at)
  const f = new Date(form.fin_at)
  const ms = f - i
  if (isNaN(ms)) return ''
  if (ms < 0) return 'La fecha de fin no puede ser anterior al inicio'
  const hours = ms / (1000 * 60 * 60)
  if (hours > 12) return 'Duración muy larga, verifica las fechas'
  if (hours < 0.25) return 'Duración muy corta (menos de 15 minutos)'
  return ''
})

const duracionTexto = computed(() => {
  if (!form.inicio_at || !form.fin_at) return ''
  const i = new Date(form.inicio_at)
  const f = new Date(form.fin_at)
  const ms = f - i
  if (isNaN(ms) || ms <= 0) return ''
  const m = Math.round(ms / 60000)
  const h = Math.floor(m / 60)
  const mm = m % 60
  return h ? `${h}h ${mm}m` : `${mm}m`
})

// Funciones de utilidad mejoradas
function getInputClass(field) {
  const hasError = form.errors?.[field] || fieldValidationErrors.value[field]
  const hasValue = form[field] && form[field] !== ''

  if (hasError) {
    return 'border-red-300 focus:ring-red-500 bg-red-50'
  }
  if (hasValue) {
    return 'border-green-300 focus:ring-green-500 bg-green-50'
  }
  return 'border-gray-300 hover:border-gray-400'
}

function validateField(field) {
  // Validación básica en tiempo real
  fieldValidationErrors.value = { ...fieldValidationErrors.value }
  delete fieldValidationErrors.value[field]

  if (field === 'titulo' && (!form.titulo || form.titulo.trim().length < 3)) {
    fieldValidationErrors.value[field] = 'El título debe tener al menos 3 caracteres'
  }

  if (field === 'user_id' && !form.user_id) {
    fieldValidationErrors.value[field] = 'Debe seleccionar un empleado'
  }

  if (field === 'tipo' && !form.tipo) {
    fieldValidationErrors.value[field] = 'Debe seleccionar un tipo'
  }

  if (field === 'estado' && !form.estado) {
    fieldValidationErrors.value[field] = 'Debe seleccionar un estado'
  }

  if (field === 'inicio_at' && !form.inicio_at) {
    fieldValidationErrors.value[field] = 'La fecha de inicio es obligatoria'
  }
}

function formatLabel(text) {
  return text.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, l => l.toUpperCase())
}

function getFieldLabel(field) {
  const labels = {
    titulo: 'Título',
    user_id: 'Empleado',
    cliente_id: 'Cliente',
    tipo: 'Tipo',
    estado: 'Estado',
    inicio_at: 'Fecha de inicio',
    fin_at: 'Fecha de fin',
    ubicacion: 'Ubicación',
    descripcion: 'Descripción'
  }
  return labels[field] || field
}

function getEstadoIndicator(estado) {
  const classes = {
    'pendiente': 'bg-yellow-400',
    'en_proceso': 'bg-blue-400',
    'completado': 'bg-green-400',
    'cancelado': 'bg-red-400'
  }
  return classes[estado] || 'bg-gray-400'
}

function getEstadoDescription(estado) {
  const descriptions = {
    'pendiente': 'Por iniciar',
    'en_proceso': 'En progreso',
    'completado': 'Finalizada',
    'cancelado': 'No realizada'
  }
  return descriptions[estado] || ''
}

function setNow(field) {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  const val = now.toISOString().slice(0,16)
  form[field] = val
}

function sumarMinutos(mins) {
  if (!form.inicio_at) return
  const base = new Date(form.inicio_at)
  const local = new Date(base.getTime() - base.getTimezoneOffset() * 60000)
  local.setMinutes(local.getMinutes() + mins)
  const val = new Date(local.getTime() + local.getTimezoneOffset() * 60000)
  val.setMinutes(val.getMinutes() - val.getTimezoneOffset())
  form.fin_at = val.toISOString().slice(0,16)
}

function addTemplate(template) {
  const currentDesc = form.descripcion || ''
  const separator = currentDesc && !currentDesc.endsWith('\n') ? '\n' : ''
  form.descripcion = currentDesc + separator + template + ' \n'

  // Enfocar el textarea después de agregar la plantilla
  nextTick(() => {
    const textarea = document.getElementById('descripcion')
    if (textarea) {
      textarea.focus()
      textarea.setSelectionRange(textarea.value.length, textarea.value.length)
    }
  })
}

function autoResize(event) {
  const textarea = event.target
  textarea.style.height = 'auto'
  textarea.style.height = textarea.scrollHeight + 'px'
}

function resetForm() {
  if (confirm('¿Estás seguro de que quieres deshacer todos los cambios?')) {
    Object.keys(originalData).forEach(key => {
      form[key] = originalData[key]
    })
    fieldValidationErrors.value = {}
  }
}

function normalizePayload() {
  return { ...form.data() }
}

function submit() {
  const payload = normalizePayload()
  form.put(route('bitacora.update', props.actividad.id), {
    preserveScroll: true,
    data: payload,
    onSuccess: () => {
      router.visit(route('bitacora.index'))
    },
  })
}

// Atajos de teclado
function handleKeyboard(event) {
  // Ctrl+S para guardar
  if (event.ctrlKey && event.key === 's') {
    event.preventDefault()
    if (isFormValid.value && hasChanges.value && !form.processing) {
      submit()
    }
  }

  // Ctrl+Z para deshacer
  if (event.ctrlKey && event.key === 'z' && !event.shiftKey) {
    event.preventDefault()
    if (hasChanges.value) {
      resetForm()
    }
  }

  // Ctrl+N para fecha actual en campos de fecha
  if (event.ctrlKey && event.key === 'n') {
    const activeElement = document.activeElement
    if (activeElement && activeElement.type === 'datetime-local') {
      event.preventDefault()
      const fieldName = activeElement.id
      setNow(fieldName)
    }
  }

  // Ctrl+H para sumar 1 hora
  if (event.ctrlKey && event.key === 'h') {
    const activeElement = document.activeElement
    if (activeElement && activeElement.id === 'fin_at') {
      event.preventDefault()
      sumarMinutos(60)
    }
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyboard)

  // Auto-resize todos los textareas al montar
  const textareas = document.querySelectorAll('textarea')
  textareas.forEach(textarea => {
    textarea.style.height = 'auto'
    textarea.style.height = textarea.scrollHeight + 'px'
  })
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyboard)
})

// Watchers para validación en tiempo real
watch(() => form.titulo, () => validateField('titulo'), { immediate: true })
watch(() => form.user_id, () => validateField('user_id'), { immediate: true })
watch(() => form.tipo, () => validateField('tipo'), { immediate: true })
watch(() => form.estado, () => validateField('estado'), { immediate: true })
watch(() => form.inicio_at, () => validateField('inicio_at'), { immediate: true })
</script>

<style scoped>
/* Transiciones mejoradas */
.slide-down-enter-active, .slide-down-leave-active {
  transition: all 0.3s ease;
}
.slide-down-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.9);
}

/* Estilos para campos enfocados */
input:focus, select:focus, textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Animación de carga */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Estilos para kbd */
kbd {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Monaco, Consolas, "Liberation Mono", "Menlo", monospace;
  font-size: 0.75rem;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  border: 1px solid #374151;
}

/* Mejoras de accesibilidad */
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Responsive improvements */
@media (max-width: 640px) {
  .grid.md\\:grid-cols-2 {
    grid-template-columns: 1fr;
  }

  .flex.gap-1.flex-wrap button {
    font-size: 0.625rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>

