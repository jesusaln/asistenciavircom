<template>
    <Head title="Editar Mantenimiento" />
    <div class="w-full">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center mb-6">
                <div class="bg-blue-500 p-3 rounded-lg mr-4">
                    <i class="fas fa-edit text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Editar Mantenimiento</h1>
                    <p class="text-gray-600">Actualiza la información del servicio de mantenimiento para tu vehículo</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Selección de Carro -->
                <div class="bg-white p-4 rounded-lg">
                    <label class="block text-gray-700 text-sm font-semibold mb-3">
                        <i class="fas fa-car mr-2"></i>Seleccionar Vehículo
                    </label>
                    <select
                        v-model="form.carro_id"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        required
                        @change="updateKilometraje"
                    >
                        <option value="" disabled>Selecciona un vehículo</option>
                        <option v-for="carro in carros" :key="carro.id" :value="carro.id">
                            {{ carro.marca }} {{ carro.modelo }} {{ carro.año || '' }} - {{ formatNumber(carro.kilometraje) }} km
                        </option>
                    </select>
                    <div v-if="selectedCarro" class="mt-3 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span>Vehículo seleccionado: <strong>{{ selectedCarro.marca }} {{ selectedCarro.modelo }}</strong></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm text-blue-700 mt-2">
                            <div>Kilometraje actual: <strong>{{ formatNumber(selectedCarro.kilometraje) }} km</strong></div>
                            <div v-if="selectedCarro.año">Año: <strong>{{ selectedCarro.año }}</strong></div>
                        </div>
                        <div v-if="selectedCarro.taller_preferido" class="text-sm text-blue-600 mt-1">
                            <i class="fas fa-wrench mr-1"></i>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                    :class="{ 'border-red-500 ring-red-200': errors.tipo }"
                                    required
                                >
                                    <option value="">Selecciona el tipo de servicio</option>
                                    <optgroup label="Servicios Generales">
                                        <option value="Revisión periódica">🔍 Revisión periódica</option>
                                        <option value="Cambio de aceite">🛢️ Cambio de aceite</option>
                                        <option value="Revisión de luces">💡 Revisión de luces</option>
                                    </optgroup>
                                    <optgroup label="Servicios Específicos">
                                        <option value="Servicio de frenos">🛑 Servicio de frenos</option>
                                        <option value="Servicio de llantas">🛞 Servicio de llantas</option>
                                        <option value="Servicio de batería">🔋 Servicio de batería</option>
                                        <option value="Servicio de motor">🔧 Servicio de motor</option>
                                        <option value="Servicio de transmisión">⚙️ Servicio de transmisión</option>
                                        <option value="Servicio de aire acondicionado">❄️ Aire acondicionado</option>
                                        <option value="Alineación y balanceo">📐 Alineación y balanceo</option>
                                    </optgroup>
                                    <optgroup label="Otros">
                                        <option value="Otro servicio">✨ Otro servicio</option>
                                    </optgroup>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
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
                            📅 Fechas y Programación
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <!-- Fecha del Mantenimiento -->
                            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                <label class="block text-sm font-bold text-gray-800 mb-3">
                                    <i class="fas fa-calendar-day mr-2 text-purple-600"></i>
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
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                    Fecha en que se realizó el mantenimiento
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Valor actual: <strong>{{ form.fecha || 'No especificado' }}</strong>
                                </p>
                            </div>

                            <!-- Próximo Mantenimiento -->
                            <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                                <label class="block text-sm font-bold text-gray-800 mb-3">
                                    <i class="fas fa-calendar-plus mr-2 text-blue-600"></i>
                                    Próximo Mantenimiento <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.proximo_mantenimiento"
                                    type="date"
                                    :min="todayDate"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-lg"
                                    :class="{ 'border-red-500 ring-red-200': errors.proximo_mantenimiento }"
                                    required
                                >
                                <p v-if="errors.proximo_mantenimiento" class="text-red-500 text-sm mt-2">{{ errors.proximo_mantenimiento }}</p>
                                <p class="text-gray-600 text-sm mt-2 flex items-center">
                                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>
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
                                <i class="fas fa-lightbulb mr-2"></i>
                                💡 Recomendaciones de intervalos:
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

                    <!-- Debug Panel (Temporal) -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-bold text-yellow-800 mb-2">🔧 Debug - Información de Fechas:</h4>
                        <div class="text-sm text-yellow-700 space-y-1">
                            <div><strong>Fecha del mantenimiento:</strong> {{ formatDateValue(form.fecha) || 'No definida' }}</div>
                            <div><strong>Próximo mantenimiento:</strong> {{ formatDateValue(form.proximo_mantenimiento) || 'No definido' }}</div>
                            <div class="text-xs text-gray-600 mt-2">
                                <em>Si ves texto extraño, es porque hay datos corruptos en la base de datos</em>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Kilometraje -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <i class="fas fa-tachometer-alt mr-2"></i>Kilometraje del Servicio
                            </label>
                            <input
                                v-model="form.kilometraje_actual"
                                type="number"
                                :min="selectedCarro?.kilometraje || 0"
                                placeholder="Ingresa el kilometraje actual"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                required
                            >
                            <p class="text-sm text-gray-500 mt-2 flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                Debe ser mayor o igual al kilometraje actual del vehículo
                            </p>
                        </div>

                        <!-- Costo del Servicio -->
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-3">
                                <i class="fas fa-dollar-sign mr-2"></i>Costo del Servicio (Opcional)
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
                                    💰 Sugerido
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
                            <i class="fas fa-map-marker-alt mr-2"></i>Taller/Lugar (Opcional)
                        </label>
                        <input
                            v-model="form.taller"
                            type="text"
                            placeholder="Nombre del taller o lugar"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                    </div>

                    <!-- Configuración de Alertas y Prioridad -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <h3 class="text-lg font-semibold text-blue-800 mb-4 flex items-center">
                            <i class="fas fa-bell mr-2"></i>
                            Configuración de Alertas y Prioridad
                        </h3>

                        <div class="grid md:grid-cols-3 gap-4">
                            <!-- Prioridad -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>Prioridad
                                </label>
                                <select
                                    v-model="form.prioridad"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    required
                                >
                                    <option value="baja">🟢 Baja</option>
                                    <option value="media">🔵 Media</option>
                                    <option value="alta">🟠 Alta</option>
                                    <option value="critica">🔴 Crítica</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ getDescripcionPrioridad(form.prioridad) }}
                                </p>
                            </div>

                            <!-- Días de Anticipación -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <i class="fas fa-clock mr-2"></i>Días de Anticipación
                                </label>
                                <input
                                    v-model="form.dias_anticipacion_alerta"
                                    type="number"
                                    min="1"
                                    max="365"
                                    :placeholder="getDiasAnticipacionSugeridos()"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">
                                    Días antes para enviar alerta
                                </p>
                            </div>

                            <!-- Requiere Aprobación -->
                            <div>
                                <label class="block text-gray-700 text-sm font-semibold mb-3">
                                    <i class="fas fa-check-circle mr-2"></i>Requiere Aprobación
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
                                <i class="fas fa-sticky-note mr-2"></i>Observaciones de Alerta (Opcional)
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
                            <i class="fas fa-info-circle mr-2"></i>Estado del Mantenimiento
                        </label>
                        <select
                            v-model="form.estado"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        >
                            <option value="completado">✅ Completado</option>
                            <option value="pendiente">⏳ Pendiente</option>
                            <option value="en_proceso">🔄 En Proceso</option>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-3">
                            <i class="fas fa-align-left mr-2"></i>Descripción (Opcional)
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
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 resize-none"
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
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <button
                            type="button"
                            @click="goBack"
                            class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-8 py-3 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 border border-transparent rounded-lg shadow-sm hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition duration-200"
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

</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref, computed } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import AppLayout from '@/Layouts/AppLayout.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
    mantenimiento: Object,
    carros: Array,
    errors: {
        type: Object,
        default: () => ({})
    }
});

// Configuración de Notyf
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        {
            type: 'success',
            background: '#10b981',
            icon: { className: 'fas fa-check-circle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'error',
            background: '#ef4444',
            icon: { className: 'fas fa-times-circle', tagName: 'i', color: '#fff' }
        },
        {
            type: 'warning',
            background: '#f59e0b',
            icon: { className: 'fas fa-exclamation-triangle', tagName: 'i', color: '#fff' }
        }
    ],
});

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
    { value: 'Cambio de aceite', label: '🛢️ Cambio de aceite' },
    { value: 'Revisión periódica', label: '🔍 Revisión periódica' },
    { value: 'Servicio de frenos', label: '🛑 Servicio de frenos' },
    { value: 'Servicio de llantas', label: '🛞 Servicio de llantas' },
    { value: 'Servicio de batería', label: '🔋 Servicio de batería' },
    { value: 'Servicio de motor', label: '⚙️ Servicio de motor' },
    { value: 'Revisión de luces', label: '💡 Revisión de luces' },
    { value: 'Alineación y balanceo', label: '⚖️ Alineación y balanceo' },
    { value: 'Cambio de filtros', label: '🔧 Cambio de filtros' },
    { value: 'Revisión de transmisión', label: '🔄 Revisión de transmisión' },
    { value: 'Otro servicio', label: '📝 Otro servicio' },
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

// Carro seleccionado
const selectedCarro = computed(() => {
    return props.carros.find(carro => carro.id === form.carro_id);
});

// Debug helper para mostrar información de los datos
const debugInfo = computed(() => {
    return {
        mantenimiento: props.mantenimiento,
        form: form,
        fecha: form.fecha,
        proximo_mantenimiento: form.proximo_mantenimiento,
        todayDate: todayDate.value
    };
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

