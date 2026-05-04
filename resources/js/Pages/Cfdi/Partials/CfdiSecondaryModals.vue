<template>
<!-- Modal Descarga Masiva -->
    <Teleport to="body">
        <div v-if="showDescargaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeDescarga"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl p-10 animate-fadeIn">
                <button @click="closeDescarga" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>

                <h3 class="text-2xl font-black text-gray-900 mb-2">Descarga masiva SAT</h3>
                <p class="text-sm text-gray-500 mb-6">Selecciona dirección y rango de fechas. La descarga se ejecuta en segundo plano.</p>

                <div class="space-y-4">
                    <div class="flex gap-2">
                        <button @click="descargaForm.direccion = 'emitido'"
                                :class="['flex-1 py-3 rounded-xl font-bold text-sm', descargaForm.direccion === 'emitido' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600']">
                            Emitidos
                        </button>
                        <button @click="descargaForm.direccion = 'recibido'"
                                :class="['flex-1 py-3 rounded-xl font-bold text-sm', descargaForm.direccion === 'recibido' ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-600']">
                            Recibidos
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button @click="setQuickRange(1)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[11px] font-bold uppercase tracking-widest">
                            Hoy
                        </button>
                        <button @click="setQuickRange(7)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[11px] font-bold uppercase tracking-widest">
                            Semana
                        </button>
                        <button @click="setQuickRange(15)" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[11px] font-bold uppercase tracking-widest">
                            Quince dias
                        </button>
                        <button @click="setCurrentMonthRange()" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[11px] font-bold uppercase tracking-widest">
                            Mes
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Inicio</label>
                            <input type="date" v-model="descargaForm.fecha_inicio" class="w-full h-12 px-3 bg-white border-2 border-transparent rounded-xl text-sm font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                        </div>
                        <div>
                            <label class="text-[14px] font-black text-gray-400 uppercase tracking-[0.2em] pl-1">Fin</label>
                            <input type="date" v-model="descargaForm.fecha_fin" class="w-full h-12 px-3 bg-white border-2 border-transparent rounded-xl text-sm font-bold focus:bg-white focus:border-blue-100 focus:ring-0 transition-all" />
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="closeDescarga" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
                        Cancelar
                    </button>
                    <button @click="solicitarDescarga" :disabled="descargaSending"
                            class="flex-1 px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm transition-all">
                        {{ descargaSending ? 'Enviando...' : 'Iniciar descarga' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Modal de Carga de XML -->
    <Teleport to="body">
        <div v-if="showUploadModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeUpload"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-8 animate-fadeIn">
                <button @click="closeUpload" class="absolute top-6 right-6 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
                
                <h3 class="text-2xl font-black text-gray-900 mb-2">Cargar CFDI Recibido</h3>
                <p class="text-sm text-gray-500 mb-6">Sube un archivo XML de un proveedor para agregarlo a tu archivo.</p>
                
                <!-- Drop Zone -->
                <div v-if="!uploadPreview"
                     @dragover="handleDragOver"
                     @dragleave="handleDragLeave"
                     @drop="handleDrop"
                     :class="['border-2 border-dashed rounded-2xl p-10 text-center transition-all cursor-pointer', 
                              isDragging ? 'border-violet-500 bg-violet-50' : 'border-gray-200 hover:border-violet-300 hover:bg-white']"
                     @click="triggerFilePicker">
                    <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect" />
                    
                    <div v-if="isUploading" class="flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 text-violet-600 mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-500">Procesando XML...</p>
                    </div>
                    <div v-else class="flex flex-col items-center">
                        <div class="w-16 h-16 rounded-2xl bg-violet-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                        </div>
                        <p class="text-base font-bold text-gray-700">Arrastra tu archivo XML aquí</p>
                        <p class="text-xs text-gray-400 mt-1">o haz clic para seleccionar</p>
                    </div>
                </div>
                
                <!-- Preview -->
                <div v-else class="bg-white rounded-2xl p-6">
                    <div v-if="uploadPreview.is_duplicate" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                        <p class="text-sm font-bold text-amber-700">âš ï¸ Este CFDI ya existe en el sistema</p>
                        <p class="text-xs text-amber-600 mt-1">UUID: {{ uploadPreview.data?.uuid }}</p>
                    </div>

                    <!-- Alerta de RFC Nuevo -->
                    <div v-if="!uploadPreview.emisor_exists && !uploadPreview.is_duplicate" class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-blue-700">âœ¨ RFC No Registrado</p>
                            <p class="text-[10px] text-blue-600">Este proveedor no está en tu base de datos.</p>
                        </div>
                        <button v-if="uploadPreview.data?.uuid"
                                @click="createProvider(uploadPreview.data.uuid)" 
                                :disabled="isCreatingProvider[uploadPreview.data.uuid]"
                                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-blue-700 transition-all flex items-center gap-1">
                            <svg v-if="!isCreatingProvider[uploadPreview.data.uuid]" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            <svg v-else class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Crear
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="col-span-2 md:col-span-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Emisor</p>
                            <p class="font-bold text-gray-900 truncate" :title="uploadPreview.data?.emisor?.nombre">{{ uploadPreview.data?.emisor?.nombre || 'N/A' }}</p>
                            <div class="flex items-center gap-1">
                                <p class="text-xs text-gray-500">{{ uploadPreview.data?.emisor?.rfc }}</p>
                                <span v-if="uploadPreview.emisor_exists" class="text-[9px] text-emerald-500 font-bold">âœ… Registrado</span>
                            </div>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">UUID</p>
                            <p class="font-mono text-xs text-gray-600 truncate">{{ uploadPreview.data?.uuid || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha</p>
                            <p class="font-bold text-gray-900">{{ uploadPreview.data?.fecha || 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</p>
                            <p class="font-black text-lg text-violet-600 tabular-nums italic">${{ Number(uploadPreview.data?.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button @click="resetUpload" 
                                class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
                            Cancelar
                        </button>
                        <button @click="uploadXml" 
                                :disabled="isUploading || uploadPreview.is_duplicate"
                                :class="['flex-1 px-4 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2',
                                         uploadPreview.is_duplicate ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-violet-600 hover:bg-violet-700 text-white shadow-lg shadow-violet-600/20']">
                            <svg v-if="isUploading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ isUploading ? 'Guardando...' : 'Guardar en ADD' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Revisor de documentos descargados (Manual Review) -->
    <Teleport to="body">
        <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeReview"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full w-full max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
                <!-- Header -->
                <div class="p-8 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-gray-900 mb-1">Revisar Documentos Descargados</h3>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">SAT - Staging Area</p>
                    </div>
                    <button @click="closeReview" class="w-10 h-10 flex items-center justify-center bg-white text-gray-400 hover:text-gray-600 rounded-full transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto p-8 relative min-h-[300px]">
                    <div v-if="isLoadingReview" class="absolute inset-0 bg-white/80 flex flex-col items-center justify-center gap-4 z-10">
                        <svg class="animate-spin h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <p class="text-sm font-black text-gray-500 uppercase tracking-widest">Cargando documentos...</p>
                    </div>

                    <div v-else-if="documentosStaging.length === 0 && duplicadosStaging.length === 0" class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                        <div class="w-20 h-20 bg-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm border border-gray-100">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <p class="text-lg font-black text-gray-900">¡Nada más por aquí!</p>
                        <p class="text-sm text-gray-500">Todos los documentos ya han sido importados.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-if="documentosStaging.length">
                            <div class="flex items-center justify-between mb-4 px-2">
                                <p class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ documentosStaging.length }} Documentos encontrados</p>
                                <div class="flex gap-4">
                                    <button @click="seleccionarTodoStaging" class="text-[10px] font-black text-emerald-600 uppercase tracking-widest hover:underline">Seleccionar Todo</button>
                                    <button @click="deseleccionarTodoStaging" class="text-[10px] font-black text-red-500 uppercase tracking-widest hover:underline">Deseleccionar</button>
                                </div>
                            </div>

                            <div class="grid gap-3">
                                <div v-for="doc in documentosStaging" :key="doc.id" 
                                     :class="['p-4 rounded-2xl border-2 transition-all flex items-center gap-4 group', 
                                              doc.importado ? 'bg-white border-gray-200 opacity-60 grayscale-[0.5]' :
                                              selectedStagingIds.includes(doc.id) ? 'bg-emerald-50 border-emerald-500 shadow-lg shadow-emerald-500/10' : 'bg-white border-gray-100 hover:border-gray-200 hover:bg-white']">
                                
                                <!-- Checkbox o Badge Importado -->
                                <div v-if="doc.importado" class="w-6 h-6 rounded-lg bg-emerald-500 border-2 border-emerald-500 flex items-center justify-center flex-shrink-0" title="Ya importado">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <div v-else @click="toggleSeleccionStaging(doc.id)"
                                     :class="['w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all cursor-pointer flex-shrink-0', 
                                             selectedStagingIds.includes(doc.id) ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 bg-white group-hover:border-gray-400']">
                                    <svg v-if="selectedStagingIds.includes(doc.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>

                                <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4 items-center" @click="!doc.importado && toggleSeleccionStaging(doc.id)">
                                    <!-- UUID y Fecha -->
                                    <div class="flex flex-col md:col-span-3 cursor-pointer">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span :class="['px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border', getTipoBadge(doc.tipo_comprobante).color]">
                                                {{ getTipoBadge(doc.tipo_comprobante).label }}
                                            </span>
                                            <!-- Badge de Importado -->
                                            <span v-if="doc.importado" class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                ✓ Importado
                                            </span>
                                            <span class="text-[9px] font-mono text-gray-400 uppercase tracking-tighter truncate" :title="doc.uuid">{{ doc.uuid ? doc.uuid.substring(0, 8) : '--------' }}...</span>
                                        </div>
                                        <span class="text-xs font-black text-gray-900 tabular-nums italic">{{ formatDateShort(doc.fecha_emision) }}</span>
                                    </div>
                                    
                                    <!-- Emisor/Receptor -->
                                    <div class="flex flex-col md:col-span-5 cursor-pointer">
                                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.1em] mb-0.5">
                                            {{ doc.direccion === 'recibido' ? 'Emisor' : 'Receptor' }}
                                        </span>
                                        <span class="text-xs font-bold text-gray-800 truncate" :title="doc.direccion === 'recibido' ? doc.nombre_emisor : doc.nombre_receptor">
                                            {{ doc.direccion === 'recibido' ? (doc.nombre_emisor || 'Desconocido') : (doc.nombre_receptor || 'Público General') }}
                                        </span>
                                        <span class="text-[9px] font-mono text-gray-400 capitalize">
                                            {{ doc.direccion === 'recibido' ? doc.rfc_emisor : doc.rfc_receptor }}
                                        </span>
                                    </div>
                                    
                                    <!-- Total y Botones -->
                                    <div class="md:col-span-4 flex items-center justify-end gap-4">
                                        <span class="text-sm font-black text-emerald-700 tracking-tight tabular-nums italic cursor-pointer">
                                            {{ formatCurrency(doc.total) }}
                                        </span>
                                        
                                        <div class="flex items-center gap-1 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity" @click.stop>
                                            <button @click="verPdfStaging(doc)" 
                                                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                    title="Ver PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </button>
                                            <button @click="verXmlStaging(doc)" 
                                                    class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                    title="Ver XML">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        </div>

                        <div v-if="duplicadosStaging.length" class="mt-8">
                            <div class="flex items-center justify-between mb-3 px-2">
                                <p class="text-xs font-black text-amber-600 uppercase tracking-widest">
                                    {{ duplicadosStaging.length }} Duplicados (ya existentes)
                                </p>
                            </div>
                            <div class="grid gap-3">
                                <div v-for="doc in duplicadosStaging" :key="doc.uuid"
                                     class="p-4 rounded-2xl border-2 border-amber-200 bg-amber-50 flex items-center gap-4">
                                    <div class="w-6 h-6 rounded-lg border-2 border-amber-300 bg-amber-100 flex items-center justify-center text-[10px] font-black text-amber-700">
                                        D
                                    </div>

                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                        <div class="flex flex-col md:col-span-3">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span :class="['px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border', getTipoBadge(doc.tipo_comprobante).color]">
                                                    {{ getTipoBadge(doc.tipo_comprobante).label }}
                                                </span>
                                                <span class="text-[9px] font-mono text-gray-500 uppercase tracking-tighter truncate" :title="doc.uuid">{{ doc.uuid ? doc.uuid.substring(0, 8) : '--------' }}...</span>
                                            </div>
                                            <span class="text-xs font-black text-gray-900 tabular-nums italic">{{ formatDateShort(doc.fecha_emision) }}</span>
                                        </div>

                                        <div class="flex flex-col md:col-span-5">
                                            <span class="text-[10px] font-black text-gray-500 uppercase tracking-[0.1em] mb-0.5">
                                                {{ doc.direccion === 'recibido' ? 'Emisor' : 'Receptor' }}
                                            </span>
                                            <span class="text-xs font-bold text-gray-800 truncate" :title="doc.direccion === 'recibido' ? doc.nombre_emisor : doc.nombre_receptor">
                                                {{ doc.direccion === 'recibido' ? (doc.nombre_emisor || 'Desconocido') : (doc.nombre_receptor || 'Público General') }}
                                            </span>
                                            <span class="text-[9px] font-mono text-gray-500 capitalize">
                                                {{ doc.direccion === 'recibido' ? doc.rfc_emisor : doc.rfc_receptor }}
                                            </span>
                                        </div>

                                        <div class="md:col-span-4 flex items-center justify-end gap-4">
                                            <span class="text-sm font-black text-amber-700 tracking-tight tabular-nums italic">
                                                {{ formatCurrency(doc.total) }}
                                            </span>
                                            <div class="flex items-center gap-1">
                                                <button @click="verPdf(doc.uuid)" 
                                                        class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                        title="Ver PDF">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                </button>
                                                <button @click="abrirModalCrearCuenta(doc)" 
                                                        class="p-1.5 text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                        title="Crear Cuenta">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                </button>
                                                <button @click="verXml(doc.uuid)" 
                                                        class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                                        title="Ver XML">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 border-t border-gray-100 bg-white flex items-center justify-between">
                    <p class="text-xs font-black text-gray-500">
                        {{ selectedStagingIds.length }} de {{ documentosStaging.filter(d => !d.importado).length }} pendientes seleccionados
                    </p>
                    <div class="flex gap-4">
                        <button @click="closeReview" class="px-6 py-3 bg-white hover:bg-gray-100 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest border border-gray-200 transition-all">
                            Cerrar
                        </button>
                        <button @click="importarSeleccionados" :disabled="isImportingSeleccionados || selectedStagingIds.length === 0"
                                :class="[
                                    'px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all',
                                    selectedStagingIds.length === 0 
                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/20',
                                    isImportingSeleccionados ? 'opacity-50 cursor-wait' : ''
                                ]">
                            {{ isImportingSeleccionados ? 'Importando...' : 'Importar al ADD' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Modal de Confirmación de Borrado -->
    <Teleport to="body">
        <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeDelete"></div>
            <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md p-10 animate-fadeIn text-center">
                <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-white shadow-lg">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </div>
                
                <h3 class="text-2xl font-black text-gray-900 mb-2 tracking-tight italic">¿Estás seguro?</h3>
                <p class="text-gray-500 font-bold mb-8">
                    Se eliminará el registro y el <span class="text-red-500">archivo XML</span> permanentemente. Esta acción no se puede deshacer.
                </p>

                <div v-if="cfdiParaEliminar" class="bg-white rounded-2xl p-4 mb-8 text-left border border-gray-100">
                    <p class="text-[13px] font-black text-gray-400 uppercase tracking-widest mb-1">CFDI seleccionado</p>
                    <p class="text-sm font-black text-gray-800 tracking-tight italic">{{ cfdiParaEliminar.serie }}{{ cfdiParaEliminar.folio }}</p>
                    <p class="text-[10px] text-gray-400 font-mono truncate">{{ cfdiParaEliminar.uuid }}</p>
                </div>

                <div class="flex gap-4">
                    <button @click="closeDelete" :disabled="isDeletingCfdi"
                            class="flex-1 px-4 py-4 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest transition-all">
                        Cancelar
                    </button>
                    <button @click="ejecutarEliminacion" :disabled="isDeletingCfdi"
                            class="flex-1 px-4 py-4 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest transition-all shadow-lg shadow-red-500/20">
                        {{ isDeletingCfdi ? 'Eliminando...' : 'Sí, Eliminar' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    showDescargaModal: { type: Boolean, default: false },
    descargaForm: { type: Object, required: true },
    setQuickRange: { type: Function, required: true },
    setCurrentMonthRange: { type: Function, required: true },
    solicitarDescarga: { type: Function, required: true },
    descargaSending: { type: Boolean, default: false },
    onCloseDescarga: { type: Function, required: true },

    showUploadModal: { type: Boolean, default: false },
    onCloseUpload: { type: Function, required: true },
    isDragging: { type: Boolean, default: false },
    isUploading: { type: Boolean, default: false },
    uploadPreview: { type: Object, default: null },
    isCreatingProvider: { type: Object, required: true },
    createProvider: { type: Function, required: true },
    handleDragOver: { type: Function, required: true },
    handleDragLeave: { type: Function, required: true },
    handleDrop: { type: Function, required: true },
    handleFileSelect: { type: Function, required: true },
    onResetUpload: { type: Function, required: true },
    uploadXml: { type: Function, required: true },

    showReviewModal: { type: Boolean, default: false },
    onCloseReview: { type: Function, required: true },
    isLoadingReview: { type: Boolean, default: false },
    documentosStaging: { type: Array, default: () => [] },
    duplicadosStaging: { type: Array, default: () => [] },
    selectedStagingIds: { type: Array, default: () => [] },
    toggleSeleccionStaging: { type: Function, required: true },
    seleccionarTodoStaging: { type: Function, required: true },
    deseleccionarTodoStaging: { type: Function, required: true },
    formatDateShort: { type: Function, required: true },
    formatCurrency: { type: Function, required: true },
    getTipoBadge: { type: Function, required: true },
    verPdfStaging: { type: Function, required: true },
    verXmlStaging: { type: Function, required: true },
    verPdf: { type: Function, required: true },
    verXml: { type: Function, required: true },
    abrirModalCrearCuenta: { type: Function, required: true },
    isImportingSeleccionados: { type: Boolean, default: false },
    importarSeleccionados: { type: Function, required: true },

    showDeleteConfirmModal: { type: Boolean, default: false },
    onCloseDelete: { type: Function, required: true },
    cfdiParaEliminar: { type: Object, default: null },
    isDeletingCfdi: { type: Boolean, default: false },
    ejecutarEliminacion: { type: Function, required: true }
})

const fileInput = ref(null)

const triggerFilePicker = () => {
    if (fileInput.value) {
        fileInput.value.click()
    }
}

const closeDescarga = () => {
    if (props.onCloseDescarga) props.onCloseDescarga()
}

const closeUpload = () => {
    if (props.onCloseUpload) props.onCloseUpload()
}

const resetUpload = () => {
    if (props.onResetUpload) props.onResetUpload()
}

const closeReview = () => {
    if (props.onCloseReview) props.onCloseReview()
}

const closeDelete = () => {
    if (props.onCloseDelete) props.onCloseDelete()
}
</script>
