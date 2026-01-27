<template>
<!-- Modal Descarga Masiva -->
    <Teleport to="body">
        <div v-if="showDescargaModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-[#020617]/80 backdrop-blur-md" @click="closeDescarga"></div>
            
            <div class="relative bg-[#0F172A] border border-slate-700/50 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
                <!-- Decorative Gradients -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 blur-[80px] rounded-full pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/10 blur-[80px] rounded-full pointer-events-none"></div>

                <!-- Header -->
                <div class="px-10 pt-10 pb-6 relative z-10">
                    <button @click="closeDescarga" class="absolute top-8 right-8 text-slate-500 hover:text-white transition-colors bg-slate-800/50 hover:bg-slate-700 p-2 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>

                    <h3 class="text-3xl font-black text-white tracking-tight mb-2 italic">Descarga Masiva SAT</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest leading-relaxed">Configura los parámetros para la recuperación de comprobantes desde el servidor del SAT.</p>
                </div>

                <div class="px-10 pb-10 space-y-8 relative z-10">
                    <!-- Tipo de Comprobante -->
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Dirección de los CFDI</label>
                        <div class="flex p-1.5 bg-slate-900/80 rounded-2xl border border-slate-800">
                            <button @click="descargaForm.direccion = 'emitido'"
                                    :class="['flex-1 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-300', 
                                             descargaForm.direccion === 'emitido' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'text-slate-500 hover:text-slate-300']">
                                Emitidos (Ingresos)
                            </button>
                            <button @click="descargaForm.direccion = 'recibido'"
                                    :class="['flex-1 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest transition-all duration-300', 
                                             descargaForm.direccion === 'recibido' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/20' : 'text-slate-500 hover:text-slate-300']">
                                Recibidos (Gastos)
                            </button>
                        </div>
                    </div>

                    <!-- Filtros Rápidos -->
                    <div class="space-y-3">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Ajuste de Rango Rápido</label>
                        <div class="flex flex-wrap gap-2">
                            <button @click="setQuickRange(1)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-700/50 transition-all">Hoy</button>
                            <button @click="setQuickRange(7)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-700/50 transition-all">Semana</button>
                            <button @click="setQuickRange(15)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-700/50 transition-all">Qna</button>
                            <button @click="setCurrentMonthRange()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-700/50 transition-all">Mes</button>
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Fecha Inicio</label>
                            <div class="relative group">
                                <input type="date" v-model="descargaForm.fecha_inicio" 
                                       class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Fecha Fin</label>
                            <div class="relative group">
                                <input type="date" v-model="descargaForm.fecha_fin" 
                                       class="w-full bg-slate-900/50 border border-slate-700 rounded-2xl px-4 py-3 text-white font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all appearance-none" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-10 py-8 bg-slate-900/50 backdrop-blur-xl border-t border-slate-800 flex gap-4 relative z-10">
                    <button @click="closeDescarga" class="flex-1 px-4 py-4 bg-slate-800 hover:bg-slate-700 text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl transition-all">
                        Cancelar
                    </button>
                    <button @click="solicitarDescarga" :disabled="descargaSending"
                            class="flex-[2] px-4 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-lg shadow-emerald-900/30 transition-all disabled:opacity-50 disabled:cursor-wait flex items-center justify-center gap-3 active:scale-95">
                        <svg v-if="descargaSending" class="animate-spin h-3 w-3 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ descargaSending ? 'Iniciando Servicio...' : 'Iniciar Descarga SAT' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Modal de Carga de XML -->
    <Teleport to="body">
        <div v-if="showUploadModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-[#020617]/80 backdrop-blur-md" @click="closeUpload"></div>
            
            <div class="relative bg-[#0F172A] border border-slate-700/50 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden animate-fadeIn">
                <!-- Decorative Gradients -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-violet-600/10 blur-[80px] rounded-full pointer-events-none"></div>

                <div class="px-10 pt-10 pb-6 relative z-10">
                    <button @click="closeUpload" class="absolute top-8 right-8 text-slate-500 hover:text-white transition-colors bg-slate-800/50 hover:bg-slate-700 p-2 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    
                    <h3 class="text-3xl font-black text-white tracking-tight mb-2 italic">Cargar CFDI</h3>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest leading-relaxed">Arrastra el archivo XML recibido para integrarlo a tu repositorio digital.</p>
                </div>
                
                <div class="px-10 pb-10 relative z-10">
                    <!-- Drop Zone -->
                    <div v-if="!uploadPreview"
                         @dragover="handleDragOver"
                         @dragleave="handleDragLeave"
                         @drop="handleDrop"
                         :class="['border-2 border-dashed rounded-[2rem] p-12 text-center transition-all cursor-pointer group relative overflow-hidden', 
                                  isDragging ? 'border-violet-500 bg-violet-500/10' : 'border-slate-700 hover:border-violet-400 hover:bg-slate-800/50']"
                         @click="triggerFilePicker">
                        <input type="file" ref="fileInput" class="hidden" accept=".xml" @change="handleFileSelect" />
                        
                        <div v-if="isUploading" class="flex flex-col items-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-4 border-violet-500 border-t-transparent mb-6 shadow-lg shadow-violet-500/20"></div>
                            <p class="text-[10px] font-black text-violet-400 uppercase tracking-widest animate-pulse">Analizando XML...</p>
                        </div>
                        <div v-else class="flex flex-col items-center">
                            <div class="w-20 h-20 bg-slate-800 rounded-3xl flex items-center justify-center mb-6 border border-slate-700 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-10 h-10 text-slate-400 group-hover:text-violet-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            </div>
                            <p class="text-lg font-black text-white">Selta tu archivo aquí</p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-2">Formatos aceptados: .XML</p>
                        </div>
                    </div>
                    
                    <!-- Preview -->
                    <div v-else class="space-y-6">
                        <div v-if="uploadPreview.is_duplicate" class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-start gap-4">
                            <div class="p-2 bg-amber-500/20 text-amber-500 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-amber-400 tracking-tight">UUID Duplicado</p>
                                <p class="text-[10px] text-amber-600 font-bold uppercase tracking-widest mt-0.5">Este comprobante ya existe en el ADD</p>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="bg-slate-900/50 rounded-3xl border border-slate-800 p-6 space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Emisor</p>
                                    <p class="text-sm font-black text-white truncate">{{ uploadPreview.data?.emisor?.nombre || 'N/A' }}</p>
                                    <p class="text-[10px] font-mono font-bold text-slate-500 mt-0.5">{{ uploadPreview.data?.emisor?.rfc }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Fecha</p>
                                    <p class="text-xs font-bold text-slate-300">{{ uploadPreview.data?.fecha || 'N/A' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total</p>
                                    <p class="text-xl font-black text-emerald-400 tabular-nums italic">${{ Number(uploadPreview.data?.total || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-4">
                            <button @click="resetUpload" 
                                    class="flex-1 px-4 py-4 bg-slate-800 hover:bg-slate-700 text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl transition-all">
                                Cancelar
                            </button>
                            <button @click="uploadXml" 
                                    :disabled="isUploading || uploadPreview.is_duplicate"
                                    :class="['flex-[2] px-4 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3',
                                             uploadPreview.is_duplicate ? 'bg-slate-800 text-slate-600 cursor-not-allowed' : 'bg-gradient-to-r from-violet-600 to-violet-500 hover:from-violet-500 hover:to-violet-400 text-white shadow-lg shadow-violet-900/40']">
                                <svg v-if="isUploading" class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                {{ isUploading ? 'Procesando...' : 'Guardar en ADD' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Revisor de documentos descargados (Manual Review) -->
    <Teleport to="body">
        <div v-if="showReviewModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-[#020617]/80 backdrop-blur-md" @click="closeReview"></div>
            
            <div class="relative bg-[#0F172A] border border-slate-700/50 rounded-[2.5rem] shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden animate-fadeIn">
                <!-- Header -->
                <div class="px-10 py-8 border-b border-slate-800 bg-[#0F172A]/50 backdrop-blur-xl flex items-center justify-between z-10">
                    <div>
                        <h3 class="text-3xl font-black text-white tracking-tight italic mb-1">Staging Area SAT</h3>
                        <p class="text-[10px] text-slate-500 font-black uppercase tracking-[0.2em]">Revisión de documentos recuperados vía descarga masiva</p>
                    </div>
                    <button @click="closeReview" class="w-12 h-12 flex items-center justify-center bg-slate-800/50 hover:bg-slate-700 text-slate-500 hover:text-white rounded-2xl transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="flex-1 overflow-y-auto px-10 py-8 relative scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                    <div v-if="isLoadingReview" class="absolute inset-0 bg-[#0F172A]/80 backdrop-blur-sm flex flex-col items-center justify-center gap-6 z-20">
                        <div class="animate-spin rounded-full h-16 w-16 border-4 border-emerald-500 border-t-transparent shadow-lg shadow-emerald-500/20"></div>
                        <p class="text-xs font-black text-emerald-400 uppercase tracking-widest animate-pulse">Sincronizando con el servidor...</p>
                    </div>

                    <div v-else-if="documentosStaging.length === 0 && duplicadosStaging.length === 0" class="flex flex-col items-center justify-center py-24 bg-slate-900/30 rounded-[2.5rem] border-2 border-dashed border-slate-800">
                        <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center mb-6 border border-slate-700 opacity-50">
                            <svg class="w-12 h-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <p class="text-xl font-black text-white mb-2">ADD Actualizado</p>
                        <p class="text-xs text-slate-500 font-bold uppercase tracking-widest">No hay documentos pendientes de revisión.</p>
                    </div>

                    <div v-else class="space-y-8">
                        <div v-if="documentosStaging.length">
                            <div class="flex items-center justify-between mb-6 px-2">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">{{ documentosStaging.length }} Documentos Válidos</p>
                                <div class="flex gap-4">
                                    <button @click="seleccionarTodoStaging" class="text-[10px] font-black text-emerald-400 uppercase tracking-widest hover:text-emerald-300 transition-colors">Selleccionar Todo</button>
                                    <button @click="deseleccionarTodoStaging" class="text-[10px] font-black text-red-400 uppercase tracking-widest hover:text-red-300 transition-colors">Deseleccionar</button>
                                </div>
                            </div>

                            <div class="grid gap-4">
                                <div v-for="doc in documentosStaging" :key="doc.id" 
                                     @click="!doc.importado && toggleSeleccionStaging(doc.id)"
                                     :class="['p-5 rounded-3xl border transition-all cursor-pointer group relative overflow-hidden', 
                                              doc.importado ? 'bg-slate-900/50 border-slate-800 opacity-60' :
                                              selectedStagingIds.includes(doc.id) ? 'bg-emerald-500/10 border-emerald-500 shadow-lg shadow-emerald-500/10' : 'bg-slate-900 border-slate-800 hover:border-slate-600']">
                                
                                    <div class="flex items-center gap-6 relative z-10">
                                        <!-- Selection Indicator -->
                                        <div v-if="doc.importado" class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 shadow-lg shadow-emerald-900/40">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        <div v-else
                                             :class="['w-7 h-7 rounded-xl border-2 flex items-center justify-center transition-all flex-shrink-0', 
                                                     selectedStagingIds.includes(doc.id) ? 'bg-emerald-500 border-emerald-500 shadow-lg shadow-emerald-900/40' : 'border-slate-700 bg-slate-800 group-hover:border-slate-500']">
                                            <svg v-if="selectedStagingIds.includes(doc.id)" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>

                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                                            <!-- UUID & Date -->
                                            <div class="md:col-span-3">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span :class="['px-2 py-0.5 rounded-lg text-[8px] font-black uppercase tracking-widest border', getTipoBadge(doc.tipo_comprobante).color]">
                                                        {{ getTipoBadge(doc.tipo_comprobante).label }}
                                                    </span>
                                                    <span class="text-[9px] font-mono font-bold text-slate-500 uppercase tracking-tighter truncate" :title="doc.uuid">{{ doc.uuid ? doc.uuid.substring(0, 13) : '--------' }}...</span>
                                                </div>
                                                <p class="text-sm font-black text-white tabular-nums italic">{{ formatDateShort(doc.fecha_emision) }}</p>
                                            </div>
                                            
                                            <!-- Client/Entity -->
                                            <div class="md:col-span-6">
                                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">
                                                    {{ doc.direccion === 'recibido' ? 'Emisor (Proveedor)' : 'Receptor (Cliente)' }}
                                                </p>
                                                <p class="text-sm font-black text-slate-200 truncate group-hover:text-white transition-colors">
                                                    {{ doc.direccion === 'recibido' ? (doc.nombre_emisor || 'Desconocido') : (doc.nombre_receptor || 'Público General') }}
                                                </p>
                                                <p class="text-[10px] font-mono font-bold text-slate-500 lowercase mt-0.5">
                                                    {{ doc.direccion === 'recibido' ? doc.rfc_emisor : doc.rfc_receptor }}
                                                </p>
                                            </div>
                                            
                                            <!-- Amount & Tools -->
                                            <div class="md:col-span-3 flex items-center justify-end gap-6 text-right">
                                                <p class="text-base font-black text-emerald-400 tracking-tight tabular-nums italic">
                                                    {{ formatCurrency(doc.total) }}
                                                </p>
                                                
                                                <div class="flex items-center gap-1 bg-slate-800/50 p-1 rounded-xl border border-slate-700 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0" @click.stop>
                                                    <button @click="verPdfStaging(doc)" class="p-2 text-slate-400 hover:text-blue-400 hover:bg-blue-500/10 rounded-lg transition-colors" title="PDF">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                    </button>
                                                    <button @click="verXmlStaging(doc)" class="p-2 text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded-lg transition-colors" title="XML">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Duplicate Section -->
                        <div v-if="duplicadosStaging.length">
                            <h4 class="text-[10px] font-black text-amber-500/80 uppercase tracking-[0.2em] mb-4 border-b border-amber-500/10 pb-2">Omitidos por duplicidad ({{ duplicadosStaging.length }})</h4>
                            <div class="grid gap-3 opacity-60">
                                <div v-for="doc in duplicadosStaging" :key="doc.uuid"
                                     class="p-4 rounded-2xl border border-slate-800 bg-slate-900 flex items-center gap-4">
                                    <div class="w-6 h-6 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">
                                        !
                                    </div>
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4 items-center text-xs">
                                        <div class="md:col-span-3 text-slate-400 font-mono text-[9px]">{{ doc.uuid ? doc.uuid.substring(0, 18) : '---' }}...</div>
                                        <div class="md:col-span-6 text-slate-300 font-black italic">{{ doc.nombre_emisor || doc.nombre_receptor }}</div>
                                        <div class="md:col-span-3 text-right text-slate-400 tabular-nums">{{ formatCurrency(doc.total) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-10 py-8 border-t border-slate-800 bg-[#0F172A]/80 backdrop-blur-xl flex items-center justify-between z-10">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest bg-slate-900 px-4 py-2 rounded-full border border-slate-800">
                        <span class="text-emerald-400">{{ selectedStagingIds.length }}</span> / {{ documentosStaging.filter(d => !d.importado).length }} Seleccionados
                    </p>
                    <div class="flex gap-4">
                        <button @click="closeReview" class="px-8 py-4 bg-slate-800 hover:bg-slate-700 text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl transition-all">
                            Cerrar
                        </button>
                        <button @click="importarSeleccionados" :disabled="isImportingSeleccionados || selectedStagingIds.length === 0"
                                :class="['px-10 py-4 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all flex items-center gap-3',
                                         selectedStagingIds.length === 0 
                                            ? 'bg-slate-800 text-slate-600 cursor-not-allowed' 
                                            : 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white shadow-lg shadow-emerald-900/40']">
                            <svg v-if="isImportingSeleccionados" class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ isImportingSeleccionados ? 'Importando...' : 'Confirmar Importación' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Modal de Confirmación de Borrado -->
    <Teleport to="body">
        <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-[#020617]/90 backdrop-blur-md" @click="closeDelete"></div>
            <div class="relative bg-[#0F172A] border border-red-500/20 rounded-[3rem] shadow-2xl w-full max-w-md p-12 overflow-hidden animate-fadeIn text-center">
                <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-red-500/50 to-transparent"></div>
                
                <div class="w-24 h-24 bg-red-500/10 text-red-500 rounded-full flex items-center justify-center mx-auto mb-8 border border-red-500/20 shadow-lg shadow-red-900/20">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </div>
                
                <h3 class="text-3xl font-black text-white tracking-tight mb-4 italic">¿Eliminar CFDI?</h3>
                <p class="text-sm text-slate-400 font-bold mb-10 leading-relaxed uppercase tracking-widest text-[10px]">
                    Esta operación purgará el <span class="text-red-400">archivo XML</span> y su registro del ADD de forma irreversible.
                </p>

                <div v-if="cfdiParaEliminar" class="bg-slate-900/50 rounded-2xl p-5 mb-10 text-left border border-slate-800">
                    <p class="text-[9px] font-black text-slate-600 uppercase tracking-widest mb-1.5">Identificador Fiscal</p>
                    <p class="text-sm font-black text-red-400 tracking-tight italic">{{ cfdiParaEliminar.serie || '' }}{{ cfdiParaEliminar.folio || '' }}</p>
                    <p class="text-[10px] text-slate-500 font-mono mt-0.5 truncate">{{ cfdiParaEliminar.uuid }}</p>
                </div>

                <div class="flex gap-4">
                    <button @click="closeDelete" :disabled="isDeletingCfdi"
                            class="flex-1 px-4 py-4 bg-slate-800 hover:bg-slate-700 text-slate-400 font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl transition-all">
                        Cancelar
                    </button>
                    <button @click="ejecutarEliminacion" :disabled="isDeletingCfdi"
                            class="flex-[1.5] px-4 py-4 bg-red-600 hover:bg-red-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] transition-all shadow-lg shadow-red-900/40 active:scale-95">
                        {{ isDeletingCfdi ? 'Purgando Datos...' : 'Sí, Eliminar' }}
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
