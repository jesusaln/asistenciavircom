<template>
    <div>
        <Head title="Editar Cita" />
        <div class="w-full">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h1 class="text-2xl font-semibold mb-6 text-gray-800">Editar Cita #{{ cita.id }}</h1>

            <!-- Alertas globales -->
            <div v-if="hasGlobalErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Error en el formulario</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li v-for="error in Object.values(form.errors)" :key="error">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notificación de éxito -->
            <div v-if="showSuccessMessage" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">¡Cita actualizada exitosamente!</h3>
                        <p class="text-sm text-green-700 mt-1">Los cambios han sido guardados correctamente.</p>
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Sección: Información del Cliente y Técnico -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Asignación</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Buscador de Cliente Mejorado -->
                        <div class="md:col-span-2">
                            <BuscarCliente
                                ref="buscarClienteRef"
                                :clientes="clientes"
                                :cliente-seleccionado="selectedCliente"
                                @cliente-seleccionado="onClienteSeleccionado"
                                @crear-nuevo-cliente="onCrearNuevoCliente"
                                label-busqueda="Cliente"
                                placeholder-busqueda="Buscar cliente por nombre, email, teléfono o RFC..."
                                :requerido="true"
                                titulo-cliente-seleccionado="Cliente Seleccionado"
                                mensaje-vacio="No hay cliente seleccionado"
                                submensaje-vacio="Busca y selecciona un cliente para continuar"
                                :mostrar-opcion-nuevo-cliente="true"
                                :mostrar-estado-cliente="true"
                                :mostrar-info-comercial="true"
                            />
                            <p v-if="form.errors.cliente_id" class="mt-1 text-sm text-red-600">{{ form.errors.cliente_id }}</p>
                        </div>

                        <FormField
                            v-model="form.tecnico_id"
                            label="Técnico"
                            type="select"
                            id="tecnico_id"
                            :options="tecnicosOptions"
                            :error="form.errors.tecnico_id"
                            required
                        />
                    </div>
                </div>

                <!-- Sección: Detalles del Servicio -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Detalles del Servicios</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField
                            v-model="form.fecha_hora"
                            label="Fecha y Hora"
                            type="datetime-local"
                            id="fecha_hora"
                            :error="form.errors.fecha_hora"
                            :min="minDateTime"
                            required
                        />

                        <FormField
                            v-model="form.estado"
                            label="Estado"
                            type="select"
                            id="estado"
                            :options="estadoOptions"
                            :error="form.errors.estado"
                            required
                        />

                        <!-- OPCIÓN PARA CERRAR TICKET ASOCIADO -->
                        <div v-if="cita.ticket_id && form.estado === 'completado'" class="md:col-span-2 mt-[-1rem] transition-all animate-fade-in">
                            <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl flex items-start gap-3 shadow-sm">
                                <div class="p-2 bg-indigo-100 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <label class="flex-1 cursor-pointer">
                                    <div class="flex items-center space-x-3">
                                        <input 
                                            type="checkbox" 
                                            v-model="form.cerrar_ticket"
                                            class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                        >
                                        <span class="text-sm font-bold text-indigo-900">
                                            Resolver ticket #{{ cita.ticket_id }} automáticamente
                                        </span>
                                    </div>
                                    <p class="text-xs text-indigo-600 mt-2 ml-8">
                                        Al activar esta opción, el ticket se marcará como <strong>resuelto</strong> y se guardará el reporte técnico en su historial.
                                    </p>
                                </label>
                            </div>
                        </div>

                        <FormField
                            v-model="form.prioridad"
                            label="Prioridad"
                            type="select"
                            id="prioridad"
                            :options="prioridadOptions"
                            :error="form.errors.prioridad"
                        />

                        <FormField
                            v-model="form.tipo_servicio"
                            label="Tipo de Servicio"
                            type="select"
                            id="tipo_servicio"
                            :options="tipoServicioOptions"
                            :error="form.errors.tipo_servicio"
                            required
                        />

                        <div class="md:col-span-2">
                            <FormField
                                v-model="form.descripcion"
                                label="Descripción del Servicio"
                                type="textarea"
                                id="descripcion"
                                :error="form.errors.descripcion"
                                placeholder="Descripción detallada del servicio a realizar..."
                                :rows="3"
                            />
                        </div>
                    </div>
                </div>

                <!-- Sección: Información del Equipo -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Información del Equipo</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <FormField
                            v-model="form.tipo_equipo"
                            label="Tipo de Equipo"
                            type="select"
                            id="tipo_equipo"
                            :options="tipoEquipoOptions"
                            :error="form.errors.tipo_equipo"
                        />

                        <div class="space-y-1">
                            <label for="marca_equipo" class="block text-sm font-medium text-gray-700">Marca</label>
                            <input 
                                v-model="form.marca_equipo" 
                                list="marcas-list" 
                                id="marca_equipo"
                                type="text"
                                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                placeholder="Ej. Samsung"
                                @input="convertirAMayusculas('marca_equipo')"
                            >
                            <datalist id="marcas-list">
                                <option v-for="marca in marcasComunes" :key="marca" :value="marca"></option>
                            </datalist>
                             <p v-if="form.errors.marca_equipo" class="mt-1 text-sm text-red-600">{{ form.errors.marca_equipo }}</p>
                        </div>

                        <FormField
                            v-model="form.modelo_equipo"
                            label="Modelo"
                            type="text"
                            id="modelo_equipo"
                            :error="form.errors.modelo_equipo"
                            placeholder="Ej. AR12MV"
                            @input="convertirAMayusculas('modelo_equipo')"
                        />
                    </div>
                </div>

                <!-- Sección: Información Adicional -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Información Adicional</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField
                            v-model="form.direccion_servicio"
                            label="Dirección del Servicio"
                            type="textarea"
                            id="direccion_servicio"
                            :error="form.errors.direccion_servicio"
                            placeholder="Dirección completa donde se realizará el servicio..."
                            :rows="2"
                        />

                        <FormField
                            v-model="form.observaciones"
                            label="Observaciones"
                            type="textarea"
                            id="observaciones"
                            :error="form.errors.observaciones"
                            placeholder="Observaciones adicionales, instrucciones especiales..."
                            :rows="2"
                        />

                    </div>
                </div>

                <!-- Sección: Productos y Servicios Utilizados -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Productos y Servicios (Cargos Extra)</h2>
                    
                    <BuscarProducto
                        :productos="productos"
                        :servicios="servicios"
                        @agregar-producto="onAgregarProducto"
                        :validar-stock="true"
                        almacen-id="1" 
                    />

                    <ProductosSeleccionados
                        :selected-products="selectedProducts"
                        :productos="productos"
                        :quantities="quantities"
                        :prices="prices"
                        :discounts="discounts"
                        @eliminar-producto="onEliminarProducto"
                        @update-quantity="onUpdateQuantity"
                        @update-price="onUpdatePrice"
                        @update-discount="onUpdateDiscount"
                    />
                    
                    <!-- Resumen de Totales Estimados -->
                     <div v-if="selectedProducts.length > 0" class="mt-4 flex justify-end">
                        <div class="w-64 space-y-2 bg-white p-4 rounded-lg">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ totalCalculado.subtotal.toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">IVA (16%):</span>
                                <span class="font-medium">${{ totalCalculado.iva.toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2 border-gray-200">
                                <span>Total:</span>
                                <span class="text-blue-600">${{ totalCalculado.total.toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Notas -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Notas Adicionales</h2>
                    <div>
                        <textarea
                            v-model="form.notas"
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
                            rows="4"
                            placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la cita..."
                        ></textarea>
                    </div>
                </div>

                <!-- Sección: Evidencias y Reporte (Editable) -->
                <div class="border-b border-gray-200 pb-6 bg-white/50 p-6 rounded-xl border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span>📸</span> Evidencias y Reporte de Servicio
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reporte del Trabajo Realizado</label>
                            <textarea
                                v-model="form.trabajo_realizado"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-vertical bg-white"
                                rows="3"
                                placeholder="Describe el trabajo realizado, hallazgos importantes, etc..."
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tiempo del Servicio (Minutos)</label>
                            <div class="relative">
                                <input
                                    type="number"
                                    v-model="form.tiempo_servicio"
                                    class="w-full border-gray-300 rounded-lg py-3 pl-4 pr-12 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    placeholder="Ej. 60"
                                    min="0"
                                >
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">min</span>
                                </div>
                            </div>
                            <p class="mt-2 text-[10px] text-gray-500 italic">
                                * Se convertirá a horas para el ticket (60 min = 1 hr)
                            </p>
                        </div>
                    </div>

                    <!-- Galería de Fotos Existentes -->
                    <div v-if="cita.fotos_finales?.length > 0" class="mb-6">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Evidencias Guardadas</label>
                        <div 
                            @click="openGallery(cita.fotos_finales, `Evidencias - Cita #${cita.id}`)"
                            class="grid grid-cols-4 sm:grid-cols-6 gap-3 cursor-pointer group"
                        >
                            <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm transition-transform hover:scale-105 relative group-hover:ring-2 ring-indigo-300">
                                <img :src="'/storage/' + foto" class="w-full h-full object-cover">
                                <span class="absolute bottom-1 right-1 bg-black/50 text-white text-[10px] px-1 rounded backdrop-blur-sm">#{{ idx + 1 }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-indigo-500 mt-2 text-center animate-pulse cursor-pointer" @click="openGallery(cita.fotos_finales, `Evidencias - Cita #${cita.id}`)">👆 Ver galería completa</p>
                    </div>

                    <!-- Subida de Nuevas Fotos -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Agregar Nuevas Evidencias</label>
                        <div class="flex items-center justify-center w-full">
                            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-white hover:bg-gray-100 transition-colors">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                    </svg>
                                    <p class="text-sm text-gray-500"><span class="font-semibold">Clic para subir</span> o arrastra imágenes</p>
                                    <p class="text-xs text-gray-500">SVG, PNG, JPG or WEBP (MAX. 5MB)</p>
                                </div>
                                <input id="dropzone-file" type="file" multiple class="hidden" accept="image/*" @change="handleNewPhotos" />
                            </label>
                        </div>
                        
                        <!-- Previsualización de Nuevas Fotos -->
                        <div v-if="previewNewPhotos.length > 0" class="mt-4 grid grid-cols-4 sm:grid-cols-6 gap-3">
                            <div v-for="(preview, idx) in previewNewPhotos" :key="idx" class="relative group aspect-square rounded-lg overflow-hidden border border-indigo-200 shadow-sm">
                                <img :src="preview" class="w-full h-full object-cover">
                                <button type="button" @click="removeNewPhoto(idx)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                            </div>
                        </div>
                    </div>

                    <!-- Sección de Firmas -->
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h3 class="text-md font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <span>✍️</span> Conformidad y Cierre
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Firma del Cliente -->
                            <div class="space-y-4">
                                <div v-if="cita.firma_cliente" class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Firma del Cliente Registrada</p>
                                    <img :src="cita.firma_cliente" class="h-32 object-contain mx-auto">
                                    <div class="mt-3 pt-3 border-t border-gray-100 text-center">
                                        <p class="text-sm font-bold text-gray-800">{{ cita.nombre_firmante || 'Cliente' }}</p>
                                        <p class="text-[10px] text-gray-500 uppercase">{{ cita.fecha_firma ? new Date(cita.fecha_firma).toLocaleString() : 'Fecha no registrada' }}</p>
                                    </div>
                                </div>
                                
                                <div v-else class="bg-white p-2 rounded-xl border border-gray-100 shadow-sm">
                                    <SignaturePad 
                                        v-model="form.firma_cliente"
                                        label="Firma de Conformidad (Cliente)"
                                        placeholder="El cliente debe firmar aquí"
                                        :error="form.errors.firma_cliente"
                                    />
                                    <div class="mt-4 px-2">
                                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nombre de quien recibe</label>
                                        <input 
                                            v-model="form.nombre_firmante"
                                            type="text"
                                            class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500 transition-all"
                                            placeholder="Ej. Juan Pérez"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Firma del Técnico -->
                            <div class="space-y-4">
                                <div v-if="cita.firma_tecnico" class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Firma del Técnico</p>
                                    <img :src="cita.firma_tecnico" class="h-32 object-contain mx-auto">
                                    <div class="mt-3 pt-3 border-t border-gray-100 text-center">
                                        <p class="text-sm font-bold text-gray-800">{{ cita.tecnico?.name || 'Técnico' }}</p>
                                        <p class="text-[10px] text-gray-500 uppercase">Responable del Servicio</p>
                                    </div>
                                </div>
                                
                                <div v-else class="bg-white p-2 rounded-xl border border-gray-100 shadow-sm">
                                    <SignaturePad 
                                        v-model="form.firma_tecnico"
                                        label="Firma del Técnico"
                                        placeholder="Firme aquí para validar el reporte"
                                        :error="form.errors.firma_tecnico"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="flex space-x-4">
                        <button
                            type="button"
                            @click="resetForm"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Limpiar Formulario
                        </button>

                        <button
                            type="button"
                            @click="saveDraft"
                            :disabled="form.processing"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            Guardar Borrador
                        </button>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || !selectedCliente"
                        class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing" class="flex items-center">
                            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Guardando...
                        </span>
                        <span v-else class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ form.estado === 'completado' ? 'Finalizar y Guardar Servicio' : (isEdit ? 'Actualizar Cita' : 'Crear Cita') }}
                        </span>
                    </button>
                </div>
            </form>
            <!-- Modal de Galería de Fotos -->
        <div v-if="showGalleryModal" class="fixed inset-0 bg-black/90 z-[60] flex flex-col" @click.self="closeGallery">
            <!-- Toolbar -->
            <div class="flex justify-between items-center p-4 text-white bg-black/50 backdrop-blur-sm">
            <div class="text-sm font-medium">
                {{ imageTitle }}
                <span class="ml-2 text-white/50 text-xs">({{ currentImageIndex + 1 }} / {{ galleryImages.length }})</span>
            </div>
            <button @click="closeGallery" class="p-2 hover:bg-white/20 rounded-full transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            </div>

            <!-- Main Image Area -->
            <div class="flex-1 flex items-center justify-center relative p-4 overflow-hidden">
            <button v-if="galleryImages.length > 1" @click.stop="prevImage" class="absolute left-4 p-3 bg-black/50 hover:bg-black/70 text-white rounded-full backdrop-blur-sm transition-all hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            
            <img :src="galleryImages[currentImageIndex]" class="max-h-full max-w-full object-contain rounded-lg shadow-2xl transition-all duration-300" :key="currentImageIndex">

            <button v-if="galleryImages.length > 1" @click.stop="nextImage" class="absolute right-4 p-3 bg-black/50 hover:bg-black/70 text-white rounded-full backdrop-blur-sm transition-all hover:scale-110">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            </div>

            <!-- Thumbnails Strip -->
            <div v-if="galleryImages.length > 1" class="p-4 bg-black/50 backdrop-blur-sm overflow-x-auto flex justify-center gap-2">
            <button 
                v-for="(img, idx) in galleryImages" 
                :key="idx" 
                @click.stop="currentImageIndex = idx"
                :class="['w-16 h-16 rounded-lg overflow-hidden border-2 transition-all', currentImageIndex === idx ? 'border-indigo-500 scale-110' : 'border-transparent opacity-50 hover:opacity-100']"
            >
                <img :src="img" class="w-full h-full object-cover">
            </button>
            </div>
        </div>
    </div>
    </div>
    </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import SignaturePad from '@/Components/UI/SignaturePad.vue';
import ProductosSeleccionados from '@/Components/CreateComponents/ProductosSeleccionados.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';


defineOptions({ layout: AppLayout });

const isEdit = true;

const props = defineProps({
    cita: {
        type: Object,
        required: true
    },
    tecnicos: Array,
    clientes: Array,
    productos: Array,
    servicios: Array,
    errors: {
        type: Object,
        default: () => ({})
    },
});

// Referencias reactivas para el buscador de clientes
const selectedCliente = ref(null);
const showSuccessMessage = ref(false);

// Referencias a los componentes
const buscarClienteRef = ref(null);

// Variables para productos y servicios
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serials = ref({});

// Cargar items existentes
onMounted(() => {
    if (props.cita.items && props.cita.items.length > 0) {
        props.cita.items.forEach(item => {
            const citableType = item.citable_type.includes('Producto') ? 'producto' : 'servicio';
            const key = `${citableType}-${item.citable_id}`;
            const productoOriginal = citableType === 'producto' 
                ? props.productos.find(p => p.id === item.citable_id) 
                : props.servicios.find(s => s.id === item.citable_id);
                
            if (productoOriginal) {
                const itemData = {
                    ...productoOriginal,
                    id: item.citable_id,
                    tipo: citableType,
                    precio: item.precio // Usar precio guardado
                };
                
                selectedProducts.value.push(itemData);
                quantities.value[key] = item.cantidad;
                prices.value[key] = item.precio;
                discounts.value[key] = item.descuento;
            }
        });
    }
});

const onAgregarProducto = (item) => {
    const key = `${item.tipo}-${item.id}`;
    
    // Verificar si ya existe
    if (selectedProducts.value.some(p => `${p.tipo}-${p.id}` === key)) {
        // Incrementar cantidad
        const currentQty = quantities.value[key] || 0;
        quantities.value[key] = Number(currentQty) + 1;
        showTemporaryMessage('Cantidad actualizada', 'info');
        return;
    }
    
    selectedProducts.value.push(item);
    quantities.value[key] = 1;
    prices.value[key] = item.precio_venta || item.precio || 0;
    discounts.value[key] = 0;
    showTemporaryMessage('Producto agregado', 'success');
};

const onEliminarProducto = (item) => {
    selectedProducts.value = selectedProducts.value.filter(p => `${p.tipo}-${p.id}` !== `${item.tipo}-${item.id}`);
    const key = `${item.tipo}-${item.id}`;
    delete quantities.value[key];
    delete prices.value[key];
    delete discounts.value[key];
};

const onUpdateQuantity = (key, qty) => {
    quantities.value[key] = qty;
};

const onUpdatePrice = (key, price) => {
    prices.value[key] = price;
};

const onUpdateDiscount = (key, discount) => {
    discounts.value[key] = discount;
};

const totalCalculado = computed(() => {
    let subtotal = 0;
    selectedProducts.value.forEach(p => {
        const key = `${p.tipo}-${p.id}`;
        const qty = quantities.value[key] || 1;
        const price = prices.value[key] || 0;
        const discount = discounts.value[key] || 0;
        
        const itemSubtotal = qty * price;
        const itemDiscount = itemSubtotal * (discount / 100);
        subtotal += (itemSubtotal - itemDiscount);
    });
    
    const iva = subtotal * 0.16;
    return {
        subtotal,
        iva,
        total: subtotal + iva
    };
});
// Opciones de selección mejoradas
const tecnicosOptions = computed(() => [
    { value: '', text: 'Selecciona un técnico', disabled: true },
    ...props.tecnicos.map(tecnico => ({
        value: tecnico.id,
        text: tecnico.name || `${tecnico.nombre || ''} ${tecnico.apellido || ''}`.trim(),
        disabled: false
    }))
]);


const estadoOptions = [
    { value: '', text: 'Selecciona el estado', disabled: true },
    { value: 'pendiente', text: 'Pendiente' },
    { value: 'programado', text: 'Programado' },
    { value: 'en_proceso', text: 'En Proceso' },
    { value: 'completado', text: 'Completado' },
    { value: 'cancelado', text: 'Cancelado' },
    { value: 'reprogramado', text: 'Reprogramado' }
];

const prioridadOptions = [
    { value: '', text: 'Selecciona la prioridad', disabled: true },
    { value: 'baja', text: 'Baja' },
    { value: 'media', text: 'Media' },
    { value: 'alta', text: 'Alta' },
    { value: 'urgente', text: 'Urgente' }
];

const tipoServicioOptions = [
    { value: '', text: 'Selecciona el tipo de servicio', disabled: true },
    { value: 'garantia', text: 'Garantía' },
    { value: 'instalacion', text: 'Instalación' },
    { value: 'reparacion', text: 'Reparación' },
    { value: 'mantenimiento', text: 'Mantenimiento' },
    { value: 'diagnostico', text: 'Diagnóstico' },
    { value: 'otro', text: 'Otro' }
];

const tipoEquipoOptions = [
    { value: '', text: 'Selecciona el tipo de equipo', disabled: true },
    { value: 'minisplit', text: 'Minisplit / Aire Acondicionado' },
    { value: 'boiler', text: 'Boiler / Calentador' },
    { value: 'refrigerador', text: 'Refrigerador' },
    { value: 'lavadora', text: 'Lavadora' },
    { value: 'secadora', text: 'Secadora' },
    { value: 'estufa', text: 'Estufa' },
    { value: 'horno', text: 'Horno' },
    { value: 'campana', text: 'Campana Extractora' },
    { value: 'horno_de_microondas', text: 'Horno de Microondas' },
    { value: 'lavavajillas', text: 'Lavavajillas' },
    { value: 'licuadora', text: 'Licuadora' },
    { value: 'ventilador', text: 'Ventilador' },
    { value: 'otro_equipo', text: 'Otro Equipo' }
];

const garantiaOptions = [
    { value: '', text: 'Selecciona opción', disabled: true },
    { value: 'si', text: 'Sí, tiene garantía' },
    { value: 'no', text: 'No tiene garantía' },
    { value: 'no_seguro', text: 'No está seguro' }
];

const marcasComunes = [
    'SAMSUNG', 'LG', 'WHIRLPOOL', 'MABE', 'FRIGIDAIRE', 'GE', 'BOSCH',
    'ELECTROLUX', 'CARRIER', 'YORK', 'TRANE', 'RHEEM', 'CALOREX'
];

// Fechas para validación
const todayDate = computed(() => {
    return new Date().toISOString().split('T')[0];
});

const minDateTime = computed(() => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
});

// Funciones para manejo del nuevo componente BuscarCliente
const onClienteSeleccionado = (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente ? cliente.id : '';

    // Auto-llenar dirección si existe
    if (cliente && cliente.direccion) {
        form.direccion_servicio = cliente.direccion;
    }
};

const applyPrefillFromProps = () => {
    const prefill = props.prefill || {};
    if (!prefill || Object.keys(prefill).length === 0) return;

    if (prefill.cliente_id) {
        const id = Number(prefill.cliente_id);
        const cliente = props.clientes?.find(c => Number(c.id) === id);
        if (cliente) {
            onClienteSeleccionado(cliente);
        } else {
            form.cliente_id = id;
        }
    }

    if (prefill.numero_serie) form.numero_serie = prefill.numero_serie;
    if (prefill.descripcion) form.descripcion = prefill.descripcion;
    if (prefill.direccion_servicio) form.direccion_servicio = prefill.direccion_servicio;
    if (prefill.tipo_servicio) form.tipo_servicio = prefill.tipo_servicio;
    if (prefill.garantia) form.garantia = prefill.garantia;

    // Si es una cita de garantía, establecer prioridad media y estado programado
    if (prefill.tipo_servicio === 'garantia') {
        form.prioridad = 'media';
        form.estado = 'programado';
    }
};

const onCrearNuevoCliente = (nombreBuscado) => {
    // Abrir ventana para crear nuevo cliente
    window.open(route('clientes.create'), '_blank');
};




// Inicializar formulario con datos de la cita existente
const initFormData = () => {
    // Formatear fecha_hora para input datetime-local
    let fechaHoraFormatted = '';
    if (props.cita.fecha_hora) {
        const fecha = new Date(props.cita.fecha_hora);
        // Convertir a formato YYYY-MM-DDTHH:mm para datetime-local
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        const hours = String(fecha.getHours()).padStart(2, '0');
        const minutes = String(fecha.getMinutes()).padStart(2, '0');
        fechaHoraFormatted = `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    return {
        cliente_id: props.cita.cliente_id || '',
        tecnico_id: props.cita.tecnico_id || '',
        fecha_hora: fechaHoraFormatted,
        estado: props.cita.estado || 'pendiente',
        prioridad: props.cita.prioridad || 'media',
        tipo_servicio: props.cita.tipo_servicio || '',
        descripcion: props.cita.descripcion || '',
        direccion_servicio: props.cita.direccion_servicio || '',
        observaciones: props.cita.observaciones || '',
        notas: props.cita.notas || '',
        // Funcionalidad extra
        cerrar_ticket: false,
        // Reporte
        trabajo_realizado: props.cita.trabajo_realizado || '',
        nuevas_fotos: [],
        
        tipo_equipo: props.cita.tipo_equipo || '',
        marca_equipo: props.cita.marca_equipo || '',
        modelo_equipo: props.cita.modelo_equipo || '',
        tiempo_servicio: props.cita.tiempo_servicio || '',
        // Firmas
        firma_cliente: null,
        nombre_firmante: props.cita.nombre_firmante || '',
        firma_tecnico: null,
    };
};

const form = useForm(initFormData());

// Computed para errores globales
const hasGlobalErrors = computed(() => {
    return Object.keys(form.errors).length > 0;
});

// Función para limpiar cliente seleccionado
const clearClienteSelection = () => {
    selectedCliente.value = null;
    form.cliente_id = '';
};

// Funciones de utilidad
const convertirAMayusculas = (campo) => {
    if (form[campo]) {
        form[campo] = form[campo].toString().toUpperCase().trim();
    }
};

const saveDraft = () => {
    const draftData = {
        ...form.data(),
        selectedCliente: selectedCliente.value,
        timestamp: new Date().toISOString()
    };

    try {
        sessionStorage.setItem('citaDraft', JSON.stringify(draftData));
        showTemporaryMessage('Borrador guardado correctamente', 'success');
    } catch (error) {
        console.error('Error al guardar borrador:', error);
        showTemporaryMessage('Error al guardar borrador', 'error');
    }
};

const loadDraft = () => {
    try {
        const draftData = sessionStorage.getItem('citaDraft');
        if (draftData) {
            const parsed = JSON.parse(draftData);

            // Verificar que el borrador no sea muy antiguo (más de 24 horas)
            const draftDate = new Date(parsed.timestamp);
            const now = new Date();
            const hoursDiff = (now - draftDate) / (1000 * 60 * 60);

            if (hoursDiff < 24) {
                // Cargar datos del formulario
                Object.keys(form.data()).forEach(key => {
                    if (parsed[key] !== undefined && key !== 'tipo_equipo' && key !== 'marca_equipo' && key !== 'modelo_equipo' && key !== 'foto_equipo' && key !== 'foto_hoja_servicio' && key !== 'foto_identificacion') {
                        form[key] = parsed[key];
                    }
                });

                // Cargar cliente seleccionado usando el nuevo componente
                if (parsed.selectedCliente) {
                    selectedCliente.value = parsed.selectedCliente;
                    // El componente BuscarCliente se actualizará automáticamente con el cliente seleccionado
                }

                showTemporaryMessage('Borrador cargado correctamente', 'info');
            } else {
                // Limpiar borrador antiguo
                sessionStorage.removeItem('citaDraft');
            }
        }
    } catch (error) {
        console.error('Error al cargar borrador:', error);
        sessionStorage.removeItem('citaDraft');
    }
};

const showTemporaryMessage = (message, type) => {
    // Crear elemento de notificación temporal
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Remover después de 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
};

const resetForm = () => {
    form.reset();
    form.estado = 'pendiente';
    form.prioridad = '';

    // Limpiar selección de cliente
    clearClienteSelection();
    
    // Validar fecha no sea en el pasado (restablecer a ahora si es necesario)
    const now = new Date();
    const offset = now.getTimezoneOffset();
    now.setMinutes(now.getMinutes() - offset);
    form.fecha_hora = now.toISOString().slice(0, 16);

    // Limpiar borrador
    sessionStorage.removeItem('citaDraft');

    showTemporaryMessage('Formulario limpiado', 'info');
};

const validateForm = () => {
    const errors = [];

    if (!selectedCliente.value || !form.cliente_id) {
        errors.push('Debe seleccionar un cliente');
    }

    if (!form.tecnico_id) {
        errors.push('Debe seleccionar un técnico');
    }

    if (!form.tipo_servicio) {
        errors.push('Debe seleccionar el tipo de servicio');
    }

    if (!form.fecha_hora) {
        errors.push('Debe especificar la fecha y hora');
    }

    // Removido: validación de problema_reportado ya no es requerido

    // Validar fecha no sea en el pasado (excepto hoy)
    const selectedDate = new Date(form.fecha_hora);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        errors.push('La fecha de la cita no puede ser anterior a hoy');
    }

    // Validar firmas si el estado es COMPLETADO
    if (form.estado === 'completado') {
        if (!form.firma_cliente && !props.cita.firma_cliente) {
            errors.push('La firma del cliente es obligatoria para finalizar el servicio');
        }
        if (!form.firma_tecnico && !props.cita.firma_tecnico) {
            errors.push('La firma del técnico es obligatoria para finalizar el servicio');
        }
        if (!form.trabajo_realizado && !props.cita.trabajo_realizado) {
            errors.push('Debe describir el trabajo realizado para finalizar el servicio');
        }
    }

    return errors;
};

const submit = () => {
    // Validar formulario antes de enviar
    const validationErrors = validateForm();

    if (validationErrors.length > 0) {
        showTemporaryMessage(`Errores de validación: ${validationErrors.join(', ')}`, 'error');
        return;
    }

    // Validar y preparar items
    const itemsData = selectedProducts.value.map(p => {
        const key = `${p.tipo}-${p.id}`;
        return {
            id: p.id,
            tipo: p.tipo,
            cantidad: quantities.value[key] || 1,
            precio: prices.value[key] || 0,
            descuento: discounts.value[key] || 0,
            notas: '' // Opcional
        };
    });

    // Usar la capacidad de Inertia para simular PUT con archivos
    form.transform((data) => ({
        ...data,
        items: itemsData,
        _method: 'PUT',
    })).post(route('citas.update', props.cita.id), {
        forceFormData: true,
        preserveScroll: true,
        onStart: () => {
            showSuccessMessage.value = false;
        },
        onSuccess: () => {
            showSuccessMessage.value = true;
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 3000);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onError: (errors) => {
            console.error('Error al actualizar la cita:', errors);
            setTimeout(() => {
                const firstErrorElement = document.querySelector('.text-red-500, .border-red-300');
                if (firstErrorElement) {
                    firstErrorElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 100);
        }
    });
};

// Observar cambios en el estado para resetear el flag de cerrar ticket
import { watch } from 'vue';
watch(() => form.estado, (newVal) => {
    if (newVal !== 'completado') {
        form.cerrar_ticket = false;
    }
});

// Función para comprimir imágenes
const compressImage = async (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Mantener aspect ratio, max 1920x1920
                let width = img.width;
                let height = img.height;
                const maxSize = 1920;
                
                if (width > height && width > maxSize) {
                    height = (height * maxSize) / width;
                    width = maxSize;
                } else if (height > maxSize) {
                    width = (width * maxSize) / height;
                    height = maxSize;
                }
                
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);
                
                canvas.toBlob((blob) => {
                    resolve(new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', 0.8);
            };
            img.onerror = reject;
        };
        reader.onerror = reject;
    });
};

const previewNewPhotos = ref([]);

const handleNewPhotos = (event) => {
    const files = Array.from(event.target.files);
    files.forEach(file => {
        if (!file.type.match('image.*')) return;
        
        form.nuevas_fotos.push(file);
        
        const reader = new FileReader();
        reader.onload = (e) => previewNewPhotos.value.push(e.target.result);
        reader.readAsDataURL(file);
    });
    // Limpiar input para permitir seleccionar las mismas fotos de nuevo si se borraron
    event.target.value = '';
};

const removeNewPhoto = (index) => {
    form.nuevas_fotos.splice(index, 1);
    previewNewPhotos.value.splice(index, 1);
};


// Auto-guardar borrador cada 30 segundos
let autoSaveInterval;

// Galería de imágenes (Reporte de Cierre)
const showGalleryModal = ref(false)
const galleryImages = ref([])
const currentImageIndex = ref(0)
const imageTitle = ref('')

const openGallery = (images, title = 'Galería') => {
  if (!images || images.length === 0) return
  galleryImages.value = images.map(img => img.startsWith('/') ? img : '/storage/' + img)
  currentImageIndex.value = 0
  imageTitle.value = title
  showGalleryModal.value = true
}

const closeGallery = () => {
  showGalleryModal.value = false
  galleryImages.value = []
}

const nextImage = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % galleryImages.value.length
}

const prevImage = () => {
  currentImageIndex.value = (currentImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length
}

const handleKeydown = (e) => {
  if (!showGalleryModal.value) return
  if (e.key === 'Escape') closeGallery()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

onMounted(() => {
    // Cargar cliente seleccionado si existe
    if (props.cita.cliente_id) {
        const cliente = props.clientes.find(c => c.id === props.cita.cliente_id);
        if (cliente) {
            selectedCliente.value = cliente;
        }
    }
    window.addEventListener('keydown', handleKeydown)
});

// Limpiar interval al desmontar componente
onUnmounted(() => {
    if (autoSaveInterval) {
        clearInterval(autoSaveInterval);
    }
    window.removeEventListener('keydown', handleKeydown)
});

// Detectar cuando el usuario intenta salir de la página sin guardar
window.addEventListener('beforeunload', (e) => {
    if (form.isDirty && !form.processing) {
        e.preventDefault();
        e.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
        return e.returnValue;
    }
});
</script>

