<template>
  <Head title="Copias de Seguridad" />
  <div class="database-backup-index min-h-screen bg-gray-50">
    <div class="max-w-8xl mx-auto px-6 py-8">
      <!-- Header -->
      <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8 mb-6">
        <div class="flex flex-col lg:flex-row gap-8 items-start lg:items-center justify-between">
          <!-- Izquierda -->
          <div class="flex flex-col gap-6 w-full lg:w-auto">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-slate-900">Copias de Seguridad</h1>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
              <button
                @click="createBackup"
                :disabled="creating"
                class="inline-flex items-center gap-2.5 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg v-if="!creating" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <svg v-else class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>{{ creating ? 'Creando...' : 'Nueva Copia' }}</span>
              </button>

              <button
                @click="showCleanDialog = true"
                class="inline-flex items-center gap-2 px-4 py-3 bg-red-50 text-red-700 rounded-xl hover:bg-red-100 transition-all duration-200 border border-red-200"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span class="text-sm font-medium">Limpiar Antiguos</span>
              </button>

              <button
                @click="toggleAdvancedMetrics"
                :class="showAdvancedMetrics ? 'bg-blue-100 text-blue-800' : 'bg-blue-50 text-blue-700'"
                class="inline-flex items-center gap-2 px-4 py-3 rounded-xl hover:bg-blue-100 transition-all duration-200 border border-blue-200"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span class="text-sm font-medium">{{ showAdvancedMetrics ? 'Ocultar Métricas' : 'Ver Métricas' }}</span>
              </button>
            </div>

            <!-- Estadísticas Avanzadas -->
            <div class="flex flex-wrap items-center gap-4 text-sm">
              <div class="flex items-center gap-2 px-4 py-3 bg-slate-50 rounded-xl border border-slate-200">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                </svg>
                <span class="font-medium text-slate-700">Total:</span>
                <span class="font-bold text-slate-900 text-lg">{{ formatNumber(stats.total) }}</span>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-green-50 rounded-xl border border-green-200">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium text-slate-700">Comprimidos:</span>
                <span class="font-bold text-green-700 text-lg">{{ formatNumber(stats.compressed) }}</span>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-blue-50 rounded-xl border border-blue-200">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="font-medium text-slate-700">Sin Comprimir:</span>
                <span class="font-bold text-blue-700 text-lg">{{ formatNumber(stats.uncompressed) }}</span>
              </div>

              <div class="flex items-center gap-2 px-4 py-3 bg-yellow-50 rounded-xl border border-yellow-200">
                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-medium text-slate-700">Tamaño Total:</span>
                <span class="font-bold text-yellow-700 text-lg">{{ formatFileSize(stats.total_size) }}</span>
              </div>

              <!-- Métricas de Salud del Sistema -->
              <div class="flex items-center gap-2 px-4 py-3 bg-green-50 rounded-xl border border-green-200">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-slate-700">Salud Sistema:</span>
                <span :class="getHealthColor(systemHealth)" class="font-bold text-lg">{{ systemHealth }}%</span>
              </div>

              <!-- Métricas de Rendimiento -->
              <div class="flex items-center gap-2 px-4 py-3 bg-purple-50 rounded-xl border border-purple-200">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span class="font-medium text-slate-700">Tasa Éxito:</span>
                <span class="font-bold text-purple-700 text-lg">{{ successRate }}%</span>
              </div>
            </div>
          </div>

          <!-- Derecha: Filtros -->
          <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto lg:flex-shrink-0">
            <!-- Búsqueda -->
            <div class="relative">
              <input
                v-model="searchTerm"
                @input="handleSearchChange($event.target.value)"
                type="text"
                :placeholder="'Buscar por nombre...'"
                class="w-full sm:w-64 lg:w-80 pl-4 pr-10 py-3 border border-slate-300 rounded-xl bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
              />
              <svg class="absolute right-3 top-3.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>

            <!-- Orden -->
            <select
              v-model="sortBy"
              @change="handleSortChange($event.target.value)"
              class="px-4 py-3 border border-slate-300 rounded-xl bg-white text-slate-900 focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200"
            >
              <option value="created_at-desc">Más Recientes</option>
              <option value="created_at-asc">Más Antiguos</option>
              <option value="name-asc">Nombre A-Z</option>
              <option value="name-desc">Nombre Z-A</option>
              <option value="size-desc">Tamaño Mayor</option>
              <option value="size-asc">Tamaño Menor</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Panel de Métricas Avanzadas -->
      <div v-if="showAdvancedMetrics" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Métricas Avanzadas del Sistema</h3>
          <p class="text-sm text-gray-600 mt-1">Información detallada sobre el rendimiento y salud del sistema de respaldos</p>
        </div>

        <div class="p-6">
          <div v-if="loadingMetrics" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-600">Cargando métricas avanzadas...</span>
          </div>

          <div v-else-if="monitoringData.monitoring" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Salud General -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-green-900">Salud General</h4>
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <div class="text-2xl font-bold text-green-700 mb-1">{{ monitoringData.monitoring.overview.health_score }}%</div>
              <div class="text-sm text-green-600">{{ monitoringData.monitoring.overview.system_health }}</div>
            </div>

            <!-- Rendimiento -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-blue-900">Rendimiento</h4>
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
              </div>
              <div class="text-2xl font-bold text-blue-700 mb-1">{{ monitoringData.monitoring.performance.avg_execution_time }}s</div>
              <div class="text-sm text-blue-600">Tiempo promedio</div>
            </div>

            <!-- Capacidad -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-purple-900">Capacidad</h4>
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
              </div>
              <div class="text-2xl font-bold text-purple-700 mb-1">{{ monitoringData.monitoring.capacity.usage_percentage }}%</div>
              <div class="text-sm text-purple-600">Espacio utilizado</div>
            </div>

            <!-- Predicciones -->
            <div v-if="monitoringData.monitoring.predictions.storage_growth" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-orange-900">Crecimiento</h4>
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
              </div>
              <div class="text-lg font-bold text-orange-700 mb-1">{{ monitoringData.monitoring.predictions.storage_growth.weekly_mb }} MB/semana</div>
              <div class="text-sm text-orange-600">Crecimiento estimado</div>
            </div>

            <!-- Confiabilidad -->
            <div class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-lg p-4 border border-teal-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-teal-900">Confiabilidad</h4>
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="text-2xl font-bold text-teal-700 mb-1">{{ monitoringData.monitoring.reliability.reliability_score }}%</div>
              <div class="text-sm text-teal-600">Puntuación de confiabilidad</div>
            </div>

            <!-- Tiempo hasta lleno -->
            <div v-if="monitoringData.monitoring.predictions.storage_growth?.time_to_full" class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 border border-red-200">
              <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-red-900">Capacidad</h4>
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="text-lg font-bold text-red-700 mb-1">{{ monitoringData.monitoring.predictions.storage_growth.time_to_full }}</div>
              <div class="text-sm text-red-600">Tiempo hasta lleno</div>
            </div>
          </div>

          <!-- Alertas -->
          <div v-if="monitoringData.monitoring?.alerts?.length > 0" class="mt-6">
            <h4 class="font-medium text-gray-900 mb-3">Alertas del Sistema</h4>
            <div class="space-y-3">
              <div v-for="alert in monitoringData.monitoring.alerts" :key="alert.timestamp"
                   :class="alert.level === 'critical' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200'"
                   class="p-4 rounded-lg border">
                <div class="flex items-start">
                  <div class="flex-shrink-0">
                    <svg v-if="alert.level === 'critical'" class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <svg v-else class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                  </div>
                  <div class="ml-3 flex-1">
                    <h5 class="text-sm font-medium" :class="alert.level === 'critical' ? 'text-red-900' : 'text-yellow-900'">
                      {{ alert.message }}
                    </h5>
                    <p class="text-sm mt-1" :class="alert.level === 'critical' ? 'text-red-700' : 'text-yellow-700'">
                      {{ alert.description }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Recomendaciones -->
          <div v-if="monitoringData.monitoring?.recommendations?.length > 0" class="mt-6">
            <h4 class="font-medium text-gray-900 mb-3">Recomendaciones</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="rec in monitoringData.monitoring.recommendations" :key="rec.title"
                   class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start justify-between mb-2">
                  <h5 class="text-sm font-medium text-gray-900">{{ rec.title }}</h5>
                  <span :class="rec.priority === 'high' ? 'bg-red-100 text-red-800' : rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'"
                        class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ rec.priority }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 mb-2">{{ rec.description }}</p>
                <div class="text-xs text-gray-500">
                  <span class="font-medium">Esfuerzo estimado:</span> {{ rec.estimated_effort }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha de Creación</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tamaño</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="backup in backups" :key="backup.path" class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ backup.name }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ formatearFecha(backup.created_at) }}</div>
                  <div class="text-xs text-gray-500">{{ formatearHora(backup.created_at) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-700">{{ formatFileSize(backup.size) }}</div>
                </td>
                <td class="px-6 py-4">
                  <span :class="backup.compressed ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ backup.compressed ? 'Comprimido' : 'Sin Comprimir' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-1">
                    <button @click="downloadBackup(backup)" class="w-8 h-8 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors duration-150" title="Descargar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                    </button>
                    <button @click="confirmRestore(backup)" class="w-8 h-8 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 transition-colors duration-150" title="Restaurar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                    </button>
                    <button @click="confirmDelete(backup)" class="w-8 h-8 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors duration-150" title="Eliminar">
                      <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="backups.length === 0">
                <td colspan="5" class="px-6 py-16 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                      <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 font-medium">No hay copias de seguridad</p>
                      <p class="text-sm text-gray-500">Las copias de seguridad aparecerán aquí cuando se creen</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="pagination.lastPage > 1" class="bg-white border-t border-gray-200 px-4 py-3 sm:px-6">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-700">
                Mostrando {{ pagination.from }} - {{ pagination.to }} de {{ pagination.total }} resultados
              </p>
              <select
                :value="pagination.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-300 rounded-md text-sm py-1 px-2 bg-white"
              >
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
              </select>
            </div>

            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
              <button
                v-if="pagination.prevPageUrl"
                @click="handlePageChange(pagination.currentPage - 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </span>

              <button
                v-for="page in [pagination.currentPage - 1, pagination.currentPage, pagination.currentPage + 1].filter(p => p > 0 && p <= pagination.lastPage)"
                :key="page"
                @click="handlePageChange(page)"
                :class="page === pagination.currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'"
                class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
              >
                {{ page }}
              </button>

              <button
                v-if="pagination.nextPageUrl"
                @click="handlePageChange(pagination.currentPage + 1)"
                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
              >
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </button>

              <span v-else class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
              </span>
            </nav>
          </div>
        </div>
      </div>

      <!-- Modal de Confirmación de Restauración -->
      <div v-if="restoreDialog.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="restoreDialog.show = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              Confirmar Restauración
            </h3>
            <button @click="restoreDialog.show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div class="text-center">
              <div class="w-12 h-12 mx-auto bg-yellow-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">¿Restaurar Base de Datos?</h3>
              <p class="text-sm text-gray-500 mb-4">
                ¿Estás seguro de que deseas restaurar la base de datos desde el respaldo <strong>{{ restoreDialog.backup?.name }}</strong>?
                Esta acción reemplazará todos los datos actuales.
              </p>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="restoreDialog.show = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cancelar
            </button>
            <button @click="executeRestore" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
              Restaurar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de Confirmación de Eliminación -->
      <div v-if="deleteDialog.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="deleteDialog.show = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              Confirmar Eliminación
            </h3>
            <button @click="deleteDialog.show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div class="text-center">
              <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">¿Eliminar Copia de Seguridad?</h3>
              <p class="text-sm text-gray-500 mb-4">
                ¿Estás seguro de que deseas eliminar la copia de seguridad <strong>{{ deleteDialog.backup?.name }}</strong>?
                Esta acción no se puede deshacer.
              </p>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="deleteDialog.show = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cancelar
            </button>
            <button @click="executeDelete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
              Eliminar
            </button>
          </div>
        </div>
      </div>

      <!-- Modal de Limpieza -->
      <div v-if="showCleanDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCleanDialog = false">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
          <!-- Header del modal -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">
              Limpiar Copias Antiguas
            </h3>
            <button @click="showCleanDialog = false" class="text-gray-400 hover:text-gray-600 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="p-6">
            <div class="text-center">
              <div class="w-12 h-12 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </div>
              <h3 class="text-lg font-medium text-gray-900 mb-2">¿Limpiar Copias Antiguas?</h3>
              <p class="text-sm text-gray-500 mb-4">
                Se eliminarán las copias de seguridad más antiguas que:
              </p>
              <select
                v-model="cleanForm.daysOld"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm"
              >
                <option value="7">7 días</option>
                <option value="15">15 días</option>
                <option value="30">30 días</option>
                <option value="60">60 días</option>
                <option value="90">90 días</option>
              </select>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
            <button @click="showCleanDialog = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cancelar
            </button>
            <button @click="executeClean" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
              Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)
})

// Props
const props = defineProps({
  backups: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
  mysqldump_available: { type: Boolean, default: false },
  total_backups: { type: Number, default: 0 },
  total_size: { type: Number, default: 0 }
})

// Estado reactivo para métricas avanzadas
const systemHealth = ref(100)
const successRate = ref(100)
const monitoringData = ref({})
const showAdvancedMetrics = ref(false)

// Estado UI
const showCleanDialog = ref(false)
const creating = ref(false)
const restoring = ref(false)
const deleting = ref(false)
const loadingMetrics = ref(false)

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')

// Formularios
const backupForm = ref({
  name: '',
  compress: true,
  include_structure_only: false
})

const cleanForm = ref({
  daysOld: 30
})

// Diálogos
const restoreDialog = ref({
  show: false,
  backup: null
})

const deleteDialog = ref({
  show: false,
  backup: null
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('backup.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    per_page: props.pagination?.per_page || 10,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('backup.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    per_page: props.pagination?.per_page || 10,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePerPageChange = (newPerPage) => {
  router.get(route('backup.index'), {
    ...props.filters,
    ...props.sorting,
    per_page: newPerPage,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('backup.index'), {
    ...props.filters,
    ...props.sorting,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}

// Métodos principales
const createBackup = async () => {
  if (creating.value) return

  creating.value = true

  try {
    const formData = new FormData()
    formData.append('name', backupForm.value.name || '')
    formData.append('compress', backupForm.value.compress ? '1' : '0')
    formData.append('include_structure_only', backupForm.value.include_structure_only ? '1' : '0')

    await router.post(route('backup.create'), formData, {
      onSuccess: () => {
        backupForm.value = {
          name: '',
          compress: true,
          include_structure_only: false
        }
        notyf.success('Copia de seguridad creada exitosamente')
      },
      onError: (errors) => {
        console.error('Error creating backup:', errors)
        notyf.error('Error al crear la copia de seguridad')
      },
      onFinish: () => {
        creating.value = false
      }
    })
  } catch (error) {
    console.error('Unexpected error:', error)
    creating.value = false
  }
}

const confirmRestore = (backup) => {
  restoreDialog.value.backup = backup
  restoreDialog.value.show = true
}

const executeRestore = async () => {
  if (restoring.value || !restoreDialog.value.backup) return

  restoring.value = true

  try {
    await router.post(route('backup.restore', { filename: restoreDialog.value.backup.name }), {}, {
      onSuccess: () => {
        restoreDialog.value.show = false
        restoreDialog.value.backup = null
        notyf.success('Base de datos restaurada exitosamente')
      },
      onError: (errors) => {
        console.error('Error restoring backup:', errors)
        notyf.error('Error al restaurar la base de datos')
      },
      onFinish: () => {
        restoring.value = false
      }
    })
  } catch (error) {
    console.error('Unexpected error:', error)
    restoring.value = false
  }
}

const confirmDelete = (backup) => {
  deleteDialog.value.backup = backup
  deleteDialog.value.show = true
}

const executeDelete = async () => {
  if (deleting.value || !deleteDialog.value.backup) return

  deleting.value = true

  try {
    await router.delete(route('backup.delete', { filename: deleteDialog.value.backup.name }), {
      onSuccess: () => {
        deleteDialog.value.show = false
        deleteDialog.value.backup = null
        notyf.success('Copia de seguridad eliminada exitosamente')
      },
      onError: (errors) => {
        console.error('Error deleting backup:', errors)
        notyf.error('Error al eliminar la copia de seguridad')
      },
      onFinish: () => {
        deleting.value = false
      }
    })
  } catch (error) {
    console.error('Unexpected error:', error)
    deleting.value = false
  }
}

const executeClean = async () => {
  if (!showCleanDialog.value) return

  try {
    const formData = new FormData()
    formData.append('days_old', cleanForm.value.daysOld.toString())

    await router.post(route('backup.clean'), formData, {
      onSuccess: () => {
        showCleanDialog.value = false
        notyf.success('Copias antiguas eliminadas exitosamente')
      },
      onError: (errors) => {
        console.error('Error cleaning backups:', errors)
        notyf.error('Error al limpiar las copias antiguas')
      }
    })
  } catch (error) {
    console.error('Unexpected error:', error)
  }
}

const downloadBackup = (backup) => {
  const link = document.createElement('a')
  link.href = route('backup.download', { filename: backup.name })
  link.download = backup.name
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  notyf.success('Descarga iniciada')
}

// Métodos para métricas avanzadas
const loadAdvancedMetrics = async () => {
  loadingMetrics.value = true
  try {
    // Cargar datos de monitoreo avanzado
    const monitoringResponse = await fetch(route('backup.monitoring'))
    if (monitoringResponse.ok) {
      monitoringData.value = await monitoringResponse.json()
      if (monitoringData.value.success) {
        systemHealth.value = monitoringData.value.monitoring.overview.health_score || 100
        successRate.value = monitoringData.value.monitoring.overview.success_rate_30d || 100
      }
    }

    // Cargar estadísticas de seguridad
    const securityResponse = await fetch(route('backup.security.stats'))
    if (securityResponse.ok) {
      const securityData = await securityResponse.json()
      if (securityData.success) {
        // Actualizar métricas de seguridad si es necesario
      }
    }

  } catch (error) {
    console.error('Error loading advanced metrics:', error)
  } finally {
    loadingMetrics.value = false
  }
}

const toggleAdvancedMetrics = () => {
  showAdvancedMetrics.value = !showAdvancedMetrics.value
  if (showAdvancedMetrics.value) {
    loadAdvancedMetrics()
  }
}

const getHealthColor = (health) => {
  if (health >= 90) return 'text-green-700'
  if (health >= 75) return 'text-yellow-700'
  if (health >= 60) return 'text-orange-700'
  return 'text-red-700'
}

// Helpers
const formatNumber = (num) => new Intl.NumberFormat('es-ES').format(num)
const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Fecha inválida'
  }
}

const formatearHora = (date) => {
  if (!date) return 'Hora no disponible'
  try {
    const d = new Date(date)
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return 'Hora inválida'
  }
}

const formatFileSize = (bytes) => {
  if (!bytes || bytes === 0) return '0 B'

  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>

<style scoped>
/* Animaciones */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.list-enter-active, .list-leave-active {
    transition: all 0.3s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.list-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.list-move {
    transition: transform 0.3s ease;
}

/* Animación de spin personalizada */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* Hover effects */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Focus styles mejorados */
.focus\:ring-2:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

/* Custom scrollbar para modal */
.modal-content::-webkit-scrollbar {
    width: 4px;
}

.modal-content::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.modal-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.modal-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Estados de loading */
.loading-overlay {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(2px);
}

/* Mejoras responsive */
@media (max-width: 640px) {
    .grid-cols-1 {
        gap: 1rem;
    }

    .space-x-2 > * + * {
        margin-left: 0.25rem;
    }
}
</style>
