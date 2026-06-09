<template>
    <Head title="Editar Mantenimiento" />
    <div class="min-h-screen w-full bg-gray-50 p-4 transition-colors dark:bg-gray-900 md:p-6" :style="cssVars">
        <div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-4 border-b border-gray-100 px-6 py-5 dark:border-gray-700">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-md" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                    <FontAwesomeIcon icon="edit" class="text-xl text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Editar Mantenimiento</h1>
                    <p class="text-gray-600 dark:text-gray-400">Actualiza la información del servicio de mantenimiento para tu vehículo</p>
                </div>
            </div>

            <div class="p-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Selección de Carro -->
                <div class="rounded-xl border border-gray-200/60 bg-gray-50/50 p-4 dark:border-gray-700/60 dark:bg-slate-900/30">
                    <label class="mb-3 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        <FontAwesomeIcon icon="car" class="mr-2" />Seleccionar Vehículo
                    </label>
                    <select
                        v-model="form.carro_id"
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        required
                        @change="updateKilometraje"
                    >
                        <option value="" disabled>Selecciona un vehículo</option>
                        <option v-for="carro in carros" :key="carro.id" :value="carro.id">
                            {{ carro.marca }} {{ carro.modelo }} {{ carro.anio || '' }} - {{ formatNumber(carro.kilometraje) }} km
                        </option>
                    </select>
                    <div v-if="selectedCarro" class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900/50 dark:bg-blue-950/30">
                        <div class="flex items-center text-sm text-blue-800 dark:text-blue-200">
                            <FontAwesomeIcon icon="info-circle" class="mr-2" />
                            <span>Vehículo seleccionado: <strong>{{ selectedCarro.marca }} {{ selectedCarro.modelo }}</strong></span>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-blue-700 dark:text-blue-300">
                            <div>Kilometraje actual: <strong>{{ formatNumber(selectedCarro.kilometraje) }} km</strong></div>
                            <div v-if="selectedCarro.anio">Año: <strong>{{ selectedCarro.anio }}</strong></div>
                        </div>
                        <div v-if="selectedCarro.taller_preferido" class="mt-1 text-sm text-blue-600 dark:text-blue-400">
                            <FontAwesomeIcon icon="wrench" class="mr-1" />
                            Taller preferido: {{ selectedCarro.taller_preferido }}
                        </div>
                    </div>
                </div>

                    <!-- Detalles del Servicio -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Detalles del Servicio
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Servicio <span class="text-red-500">*</span>
                                </label>
                                <select
                                    v-model="form.tipo"
                                    @change="handleServiceChange"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm transition duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    :class="{ 'border-red-500 ring-red-200': errors.tipo }"
                                    required
                                >
                                    <option value="">Selecciona el tipo de servicio</option>
                                    <option v-for="tipo in tiposMantenimiento" :key="tipo" :value="tipo">{{ tipo }}</option>
                                </select>
                                <p v-if="errors.tipo" class="text-red-500 text-sm mt-1">{{ errors.tipo }}</p>
                            </div>

                            <!-- Campo condicional para otro servicio -->
                            <div v-if="form.tipo === 'Otro servicio'" class="transition-all duration-300">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Especifique el servicio <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.otro_servicio"
                                    type="text"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm transition duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    :class="{ 'border-red-500 ring-red-200': errors.otro_servicio }"
                                    placeholder="Describe el tipo de servicio específico"
                                    required
                                >
                                <p v-if="errors.otro_servicio" class="text-red-500 text-sm mt-1">{{ errors.otro_servicio }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fechas y Programación -->
                    <div class="bg-gradient-to-br from-purple-50 to-blue-50 p-6 rounded-xl border border-purple-200 mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            Fechas y Programación
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Fecha del Mantenimiento -->
                            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                <label class="block text-sm font-bold text-gray-800 mb-3">
                                    <FontAwesomeIcon icon="calendar-day" class="mr-2 text-purple-600" />
                                    Fecha del Mantenimiento <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.fecha"
                                    type="date"
                                    :max="todayDate"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-lg"
                                    :class="{ 'border-red-500 ring-red-200': errors.fecha }"
                                    required
                                >
                                <p v-if="errors.fecha" class="text-red-500 text-sm mt-2">{{ errors.fecha }}</p>
                                <p class="text-gray-600 text-sm mt-2 flex items-center">
                                    <FontAwesomeIcon icon="info-circle" class="mr-2 text-blue-500" />
                                    Fecha en que se realizó el mantenimiento
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Valor actual: <strong>{{ form.fecha || 'No especificado' }}</strong>
                                </p>
                            </div>

                            <!-- Próximo Mantenimiento -->
                            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                <label class="block text-sm font-bold text-gray-800 mb-3">
                                    <FontAwesomeIcon icon="calendar-plus" class="mr-2 text-blue-600" />
                                    Próximo Mantenimiento <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.proximo_mantenimiento"
                                    type="date"
                                    :min="minProximoMantenimiento"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg"
                                    :class="{ 'border-red-500 ring-red-200': errors.proximo_mantenimiento }"
                                    required
                                >
                                <p v-if="errors.proximo_mantenimiento" class="text-red-500 text-sm mt-2">{{ errors.proximo_mantenimiento }}</p>
                                <p class="text-gray-600 text-sm mt-2 flex items-center">
                                    <FontAwesomeIcon icon="info-circle" class="mr-2 text-blue-500" />
                                    Fecha estimada para el siguiente mantenimiento
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Valor actual: <strong>{{ form.proximo_mantenimiento || 'No especificado' }}</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Ayuda para calcular próximo mantenimiento -->
                        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h4 class="text-sm font-bold text-blue-800 mb-3 flex items-center">
                                <FontAwesomeIcon icon="lightbulb" class="mr-2" />
                                Recomendaciones de intervalos:
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-blue-700">
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                                    <span>Cambio de aceite: cada 3-6 meses</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                                    <span>Revisión general: cada 6-12 meses</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                                    <span>Frenos: cada 12-18 meses</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-3"></span>
                                    <span>Llantas: cada 6-12 meses</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Kilometraje -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <FontAwesomeIcon icon="tachometer-alt" class="mr-2" />Kilometraje del Servicio
                            </label>
                            <input
                                v-model="form.kilometraje_actual"
                                type="number"
                                :min="selectedCarro?.kilometraje || 0"
                                placeholder="Ingresa el kilometraje actual"
                                class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                required
                            >
                            <p class="text-sm text-gray-500 mt-2 flex items-center">
                                <FontAwesomeIcon icon="exclamation-triangle" class="text-yellow-500 mr-2" />
                                Debe ser mayor o igual al kilometraje actual del vehículo
                            </p>
                        </div>

                        <!-- Costo del Servicio -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <FontAwesomeIcon icon="dollar-sign" class="mr-2" />Costo del Servicio (Opcional)
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.costo"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    :class="{ 'border-red-500 ring-red-200': errors.costo }"
                                >
                                <button
                                    v-if="form.tipo && getCostoSugerido() > 0"
                                    type="button"
                                    @click="form.costo = getCostoSugerido()"
                                    class="px-3 py-3 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium"
                                    title="Usar costo sugerido"
                                >
                                    Sugerido
                                </button>
                            </div>
                            <p v-if="form.tipo && getCostoSugerido() > 0" class="text-xs text-gray-500 mt-1">
                                Costo sugerido para {{ form.tipo }}: ${{ formatNumber(getCostoSugerido()) }}
                            </p>
                        </div>
                    </div>

                    <!-- Taller/Lugar -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <FontAwesomeIcon icon="map-marker-alt" class="mr-2" />Taller/Lugar (Opcional)
                        </label>
                        <input
                            v-model="form.taller"
                            type="text"
                            placeholder="Nombre del taller o lugar"
                            class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                    </div>

                    <!-- Configuración de Alertas y Prioridad -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                            <FontAwesomeIcon icon="bell" class="mr-2" />
                            Configuración de Alertas y Prioridad
                        </h3>

                        <div class="grid md:grid-cols-3 gap-4">
                            <!-- Prioridad -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <FontAwesomeIcon icon="exclamation-triangle" class="mr-2" />Prioridad
                                </label>
                                <select
                                    v-model="form.prioridad"
                                    class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    required
                                >
                                    <option value="baja">Baja</option>
                                    <option value="media">Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ getDescripcionPrioridad(form.prioridad) }}
                                </p>
                            </div>

                            <!-- Días de Anticipación -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <FontAwesomeIcon icon="clock" class="mr-2" />Días de Anticipación
                                </label>
                                <input
                                    v-model="form.dias_anticipacion_alerta"
                                    type="number"
                                    min="1"
                                    max="365"
                                    :placeholder="getDiasAnticipacionSugeridos()"
                                    class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">
                                    Días antes para enviar alerta
                                </p>
                            </div>

                            <!-- Requiere Aprobación -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <FontAwesomeIcon icon="check-circle" class="mr-2" />Requiere Aprobación
                                </label>
                                <div class="flex items-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            v-model="form.requiere_aprobacion"
                                            type="checkbox"
                                            class="sr-only peer"
                                        >
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">
                                            {{ form.requiere_aprobacion ? 'Sí' : 'No' }}
                                        </span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    Si necesita aprobación especial
                                </p>
                            </div>
                        </div>

                        <!-- Observaciones de Alerta -->
                        <div class="mt-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <FontAwesomeIcon icon="sticky-note" class="mr-2" />Observaciones de Alerta (Opcional)
                            </label>
                            <textarea
                                v-model="form.observaciones_alerta"
                                rows="2"
                                placeholder="Notas adicionales para la alerta..."
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-y"
                                maxlength="500"
                            ></textarea>
                            <div class="flex justify-end text-sm text-gray-500 mt-1">
                                <span>{{ form.observaciones_alerta?.length || 0 }}/500 caracteres</span>
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Mantenimiento -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <FontAwesomeIcon icon="info-circle" class="mr-2" />Estado del Mantenimiento
                        </label>
                        <select
                            v-model="form.estado"
                            class="w-full rounded-lg border border-gray-300 bg-white p-3 transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="completado">Completado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_proceso">En Proceso</option>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <FontAwesomeIcon icon="align-left" class="mr-2" />Descripción (Opcional)
                        </label>
                        <textarea
                            v-model="form.descripcion"
                            rows="3"
                            placeholder="Descripción detallada del mantenimiento..."
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-y"
                            maxlength="1000"
                        ></textarea>
                        <div class="flex justify-end text-sm text-gray-500 mt-1">
                            <span>{{ form.descripcion?.length || 0 }}/1000 caracteres</span>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Observaciones y Notas
                        </h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notas del Mantenimiento
                            </label>
                            <textarea
                                v-model="form.notas"
                                rows="4"
                                class="w-full resize-none rounded-lg border border-gray-300 bg-white px-4 py-3 shadow-sm transition duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                :class="{ 'border-red-500 ring-red-200': errors.notas }"
                                placeholder="Describe detalles del mantenimiento realizado, piezas cambiadas, observaciones importantes, etc."
                            ></textarea>
                            <p v-if="errors.notas" class="text-red-500 text-sm mt-1">{{ errors.notas }}</p>
                            <div class="flex justify-between items-center mt-2">
                                <p class="text-gray-500 text-xs">Información adicional sobre el mantenimiento</p>
                                <span class="text-xs text-gray-400">{{ form.notas?.length || 0 }}/500 caracteres</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center justify-between border-t border-gray-200 pt-6 dark:border-gray-700">
                        <button
                            type="button"
                            @click="goBack"
                            class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 shadow-sm transition duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/50"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg border border-transparent px-8 py-3 text-sm font-medium text-white shadow-sm transition duration-200 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }"
                        >
                            <span v-if="processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Actualizando Mantenimiento...
                            </span>
                            <span v-else class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Actualizar Mantenimiento
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { notyf } from '@/Utils/notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

const { cssVars, colors } = useCompanyColors();

// Props
const props = defineProps({
    mantenimiento: Object,
    carros: Array,
    tiposMantenimiento: { type: Array, default: () => [] },
    errors: {
        type: Object,
        default: () => ({})
    }
});

// notyf compartido

// Función para limpiar y formatear fechas
const formatDateValue = (dateValue) => {
    if (!dateValue) return null;

    // Si contiene texto basura, intentar extraer solo la parte de fecha
    if (typeof dateValue === 'string') {
        // Buscar patrones de fecha YYYY-MM-DD
        const dateMatch = dateValue.match(/(\d{4}-\d{2}-\d{2})/);
        if (dateMatch) {
            return dateMatch[1];
        }

        // Si no es una fecha válida, retornar null
        const date = new Date(dateValue);
        if (isNaN(date.getTime())) {
            return null;
        }

        return date.toISOString().split('T')[0];
    }

    return dateValue;
};

// Tipos de servicio predefinidos
const tiposServicio = [
    { value: 'Cambio de aceite', label: 'Cambio de aceite' },
    { value: 'Revisión periódica', label: 'Revisión periódica' },
    { value: 'Servicio de frenos', label: 'Servicio de frenos' },
    { value: 'Servicio de llantas', label: 'Servicio de llantas' },
    { value: 'Servicio de batería', label: 'Servicio de batería' },
    { value: 'Servicio de motor', label: 'Servicio de motor' },
    { value: 'Revisión de luces', label: 'Revisión de luces' },
    { value: 'Alineación y balanceo', label: 'Alineación y balanceo' },
    { value: 'Cambio de filtros', label: 'Cambio de filtros' },
    { value: 'Revisión de transmisión', label: 'Revisión de transmisión' },
    { value: 'Otro servicio', label: 'Otro servicio' },
];

// Variables reactivas
const form = reactive({
    carro_id: props.mantenimiento.carro_id || '',
    tipo: props.mantenimiento.tipo || '',
    otro_servicio: props.mantenimiento.otro_servicio || '',
    fecha: formatDateValue(props.mantenimiento.fecha) || '',
    proximo_mantenimiento: formatDateValue(props.mantenimiento.proximo_mantenimiento) || '',
    notas: props.mantenimiento.notas || '',
    kilometraje_actual: props.mantenimiento.kilometraje_actual || '',
    costo: props.mantenimiento.costo || '',
    taller: props.mantenimiento.taller || '',
    descripcion: props.mantenimiento.descripcion || '',
    estado: props.mantenimiento.estado || 'completado',
    proximo_kilometraje: props.mantenimiento.proximo_kilometraje || '',
    prioridad: props.mantenimiento.prioridad || 'media',
    dias_anticipacion_alerta: props.mantenimiento.dias_anticipacion_alerta || 30,
    requiere_aprobacion: props.mantenimiento.requiere_aprobacion || false,
    observaciones_alerta: props.mantenimiento.observaciones_alerta || '',
});

const processing = ref(false);
const errors = computed(() => props.errors || {});

// Fecha actual para validaciones
const todayDate = computed(() => {
    return new Date().toISOString().split('T')[0];
});

/** Próximo mantenimiento debe ser estrictamente posterior a la fecha del servicio (regla backend: after:fecha). */
const minProximoMantenimiento = computed(() => {
    if (!form.fecha) {
        return todayDate.value;
    }
    const d = new Date(form.fecha + 'T12:00:00');
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
});

// Carro seleccionado
const selectedCarro = computed(() => {
    return props.carros.find(carro => Number(carro.id) === Number(form.carro_id));
});

// Función para actualizar el kilometraje cuando se selecciona un carro
const updateKilometraje = () => {
    if (selectedCarro.value) {
        form.kilometraje_actual = selectedCarro.value.kilometraje;

        // Auto-llenar taller si el vehículo tiene uno preferido
        if (selectedCarro.value.taller_preferido && !form.taller) {
            form.taller = selectedCarro.value.taller_preferido;
        }
    } else {
        form.kilometraje_actual = '';
        form.taller = '';
    }
};

// Manejar cambio en el tipo de servicio
const handleServiceChange = () => {
    if (form.tipo !== 'Otro servicio') {
        form.otro_servicio = '';
    }
};

// Función para regresar
const goBack = () => {
    router.visit(route('mantenimientos.index'));
};

// Validar formulario antes del envío
const validateForm = () => {
    const validationErrors = [];

    if (!form.carro_id) {
        validationErrors.push('Debe seleccionar un vehículo');
    }

    if (!form.tipo) {
        validationErrors.push('Debe seleccionar un tipo de servicio');
    }

    if (form.tipo === 'Otro servicio' && !form.otro_servicio.trim()) {
        validationErrors.push('Debe especificar el tipo de servicio personalizado');
    }

    if (!form.fecha) {
        validationErrors.push('Debe seleccionar la fecha del mantenimiento');
    }

    if (!form.proximo_mantenimiento) {
        validationErrors.push('Debe seleccionar la fecha del próximo mantenimiento');
    }

    // Validar que la fecha del próximo mantenimiento sea posterior a la fecha actual
    if (form.fecha && form.proximo_mantenimiento) {
        if (new Date(form.proximo_mantenimiento) <= new Date(form.fecha)) {
            validationErrors.push('La fecha del próximo mantenimiento debe ser posterior a la fecha del mantenimiento actual');
        }
    }

    // Validar que la fecha del mantenimiento no sea futura
    if (form.fecha && new Date(form.fecha) > new Date()) {
        validationErrors.push('La fecha del mantenimiento no puede ser futura');
    }

    // Validar longitud de notas
    if (form.notas && form.notas.length > 500) {
        validationErrors.push('Las notas no pueden exceder 500 caracteres');
    }

    return validationErrors;
};

// Funciones auxiliares
const formatNumber = (number) => {
    return new Intl.NumberFormat('es-ES').format(number);
};

const getCostoSugerido = () => {
    const costos = {
        'Cambio de aceite': 800,
        'Revisión periódica': 1200,
        'Servicio de frenos': 2500,
        'Servicio de llantas': 600,
        'Servicio de batería': 1800,
        'Servicio de motor': 3500,
        'Revisión de luces': 300,
        'Alineación y balanceo': 800,
        'Cambio de filtros': 400,
        'Revisión de transmisión': 2000,
        'Otro servicio': 0
    };

    return costos[form.tipo] || 0;
};

const getDescripcionPrioridad = (prioridad) => {
    const descripciones = {
        'baja': 'Mantenimiento rutinario, no urgente',
        'media': 'Mantenimiento importante, programar pronto',
        'alta': 'Mantenimiento crítico, requiere atención prioritaria',
        'critica': 'Mantenimiento urgente, requiere atención inmediata'
    };
    return descripciones[prioridad] || 'Selecciona una prioridad';
};

const getDiasAnticipacionSugeridos = () => {
    const sugerencias = {
        'Cambio de aceite': 30,
        'Revisión periódica': 60,
        'Servicio de frenos': 90,
        'Servicio de llantas': 180,
        'Servicio de batería': 180,
        'Servicio de motor': 120,
        'Revisión de luces': 30,
        'Alineación y balanceo': 180,
        'Cambio de filtros': 60,
        'Revisión de transmisión': 120,
        'Otro servicio': 30
    };

    return sugerencias[form.tipo] || 30;
};

// Función para enviar el formulario
const submit = async () => {
    if (processing.value) return;

    // Validar formulario
    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
        validationErrors.forEach(error => {
            notyf.error(error);
        });
        return;
    }

    processing.value = true;

    try {
        await router.put(route('mantenimientos.update', props.mantenimiento.id), form, {
            onSuccess: (page) => {
                notyf.success('¡El mantenimiento ha sido actualizado exitosamente!');
            },
            onError: (errors) => {
                console.error('Errores de validación:', errors);

                // Mostrar errores específicos
                const errorMessages = Object.values(errors).flat();
                if (errorMessages.length > 0) {
                    errorMessages.forEach(message => {
                        notyf.error(message);
                    });
                } else {
                    notyf.error('Hubo errores en el formulario. Por favor revisa los campos.');
                }
            },
            onFinish: () => {
                processing.value = false;
            }
        });
    } catch (error) {
        console.error('Error inesperado:', error);
        notyf.error('Ocurrió un error inesperado. Por favor intenta de nuevo.');
        processing.value = false;
    }
};
</script>
