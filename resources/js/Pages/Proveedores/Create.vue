<template>
  <Head title="Crear Proveedor" />

  <div class="min-h-screen bg-white dark:bg-slate-950 transition-colors duration-500 overflow-x-hidden relative">
    
    <!-- Ambient Background Effects -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden select-none z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute top-[20%] -right-[10%] w-[35%] h-[35%] bg-indigo-600/10 rounded-full blur-[100px] animate-pulse-slow px-2" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative z-10 w-full px-6 lg:px-12 py-10 space-y-10 animate-fade-in-up">
      
      <!-- Header -->
      <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 pb-2 border-b border-slate-200/50 dark:border-slate-800/50">
        <div class="space-y-2">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Crear Proveedor</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Directorio Maestro</span>
                <div class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="formValid ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        {{ formValid ? 'Formulario Válido' : 'Revisar Campos Obligatorios' }}
                    </span>
                </div>
            </div>
        </div>

        <Link 
          :href="route('proveedores.index')"
          class="flex items-center gap-3 px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300 active:scale-95 border border-slate-200/50 dark:border-slate-800/50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Volver al Catálogo
        </Link>
      </div>

      <!-- Main Form Container -->
      <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-10">
        
        <!-- Left Column: Form Sections -->
        <div class="lg:col-span-8 space-y-10">
          
          <!-- Seccion: Información General -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Información General</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Identificación del proveedor</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Nombre/Razón Social -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Nombre/Razón Social *</label>
                        <input
                            v-model="form.nombre_razon_social"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            :class="{ 'ring-2 ring-rose-500/20 bg-rose-500/5': form.errors.nombre_razon_social }"
                            @blur="convertirAMayusculas('nombre_razon_social')"
                            placeholder="Ej. COMERCIALIZADORA DEL NORTE SA DE CV"
                            required
                        />
                        <p v-if="form.errors.nombre_razon_social" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.nombre_razon_social }}</p>
                    </div>

                    <!-- Tipo de Persona -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Tipo de Persona *</label>
                        <select
                            v-model="form.tipo_persona"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600/20 transition-all appearance-none cursor-pointer"
                            @change="onTipoPersonaChange"
                            required
                        >
                            <option value="" disabled>Seleccionar tipo</option>
                            <option value="fisica">PERSONA FÍSICA</option>
                            <option value="moral">PERSONA MORAL</option>
                        </select>
                        <p v-if="form.errors.tipo_persona" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.tipo_persona }}</p>
                    </div>

                    <!-- RFC -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2 flex justify-between">
                            RFC *
                            <span class="opacity-50">({{ form.tipo_persona === 'fisica' ? '13' : '12' }} CARACTERES)</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.rfc"
                                type="text"
                                :maxlength="form.tipo_persona === 'fisica' ? 13 : 12"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all font-mono uppercase tracking-widest"
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': rfcValid && form.rfc }"
                                @input="onRfcInput"
                                placeholder="CNE123456789"
                                :disabled="!form.tipo_persona"
                                required
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="rfcValid && form.rfc" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                        <p v-if="form.errors.rfc" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.rfc }}</p>
                    </div>
                </div>
          </div>

          <!-- Seccion: Información Fiscal -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Información Fiscal (SAT)</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Configuración complementaria</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Régimen Fiscal *</label>
                    <select
                        v-model="form.regimen_fiscal"
                        class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600/20 transition-all appearance-none cursor-pointer"
                        required
                    >
                        <option value="" disabled>Seleccionar régimen</option>
                        <option v-for="regimen in regimenesFiscalesFiltrados" :key="regimen.codigo" :value="regimen.codigo">
                            {{ regimen.codigo }} - {{ regimen.descripcion }}
                        </option>
                    </select>
                    <p v-if="form.errors.regimen_fiscal" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.regimen_fiscal }}</p>
                </div>
          </div>

          <!-- Seccion: Información de Contacto -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Contacto Directo</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Canales de comunicación</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Correo Electrónico</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': emailValid && form.email }"
                                @input="validateEmail"
                                placeholder="proveedor@ejemplo.com"
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="emailValid && form.email" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                        <p v-if="form.errors.email" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.email }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Teléfono (10 DÍGITOS)</label>
                        <div class="relative">
                            <input
                                v-model="form.telefono"
                                type="tel"
                                maxlength="10"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all font-mono"
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': telefonoValid && form.telefono }"
                                @input="validarTelefono"
                                placeholder="6621234567"
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="telefonoValid && form.telefono" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                        <p v-if="form.errors.telefono" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.telefono }}</p>
                    </div>
                </div>
          </div>

          <!-- Seccion: Dirección -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-rose-600/10 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Ubicación</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Domicilio fiscal/operativo</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Calle -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Calle</label>
                        <input
                            v-model="form.calle"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            @blur="convertirAMayusculas('calle')"
                            placeholder="Ej. AV. REFORMA"
                        />
                    </div>

                    <!-- Código Postal -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">CP</label>
                        <input
                            v-model="form.codigo_postal"
                            type="text"
                            maxlength="5"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all font-mono"
                            @input="validarCodigoPostal"
                            placeholder="83000"
                        />
                    </div>

                    <!-- Números -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Ext</label>
                        <input
                            v-model="form.numero_exterior"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            placeholder="123"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Int</label>
                        <input
                            v-model="form.numero_interior"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            placeholder="A-1"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Colonia</label>
                        <input
                            v-model="form.colonia"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            @blur="convertirAMayusculas('colonia')"
                            placeholder="Ej. CENTRO"
                        />
                    </div>

                    <!-- Geografía (Read Only) -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Municipio</label>
                        <input
                            v-model="form.municipio"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            disabled
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Estado</label>
                        <input
                            v-model="form.estado"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            disabled
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">País</label>
                        <input
                            v-model="form.pais"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            disabled
                        />
                    </div>
                </div>
          </div>
        </div>

        <!-- Right Column: Actions & Summary -->
        <div class="lg:col-span-4 space-y-10">
            
            <!-- Summary Card -->
            <div class="sticky top-10 space-y-10">
                <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400 mb-8">Resumen de Registro</h3>
                    
                    <div class="space-y-6">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Proveedor</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white truncate uppercase">{{ form.nombre_razon_social || 'Pendiente...' }}</span>
                        </div>
                        <div v-if="form.rfc" class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">RFC Oficial</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white font-mono">{{ form.rfc }}</span>
                        </div>
                        <div class="h-px bg-slate-200/50 dark:bg-slate-800/50 w-full my-6"></div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full" :class="form.nombre_razon_social ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"></div>
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Identificación</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full" :class="rfcValid ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"></div>
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">RFC Validado</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full" :class="form.regimen_fiscal ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"></div>
                                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Régimen Fiscal</span>
                            </div>
                        </div>

                        <div class="mt-10 space-y-3">
                            <button
                                type="submit"
                                class="w-full flex items-center justify-center gap-3 px-8 py-5 bg-blue-600 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-300 active:scale-95 bg-gradient-to-r from-blue-600 to-indigo-600 disabled:opacity-50 disabled:grayscale disabled:pointer-events-none"
                                :disabled="form.processing || !formValid"
                            >
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Guardando...' : 'Crear Proveedor' }}</span>
                            </button>
                            
                            <button
                                type="button"
                                @click="previewData"
                                class="w-full px-8 py-5 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300"
                            >
                                Vista Previa
                            </button>

                            <button
                                type="button"
                                @click="resetForm"
                                class="w-full px-8 py-3 text-[9px] font-black uppercase tracking-widest text-slate-400 hover:text-rose-500 transition-colors mt-2"
                            >
                                Limpiar Formulario
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tip Card -->
                <div class="bg-blue-600/5 rounded-3xl p-8 border border-blue-500/10 active:scale-95 transition-all">
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400 block mb-1">Dato Clave</span>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 leading-relaxed uppercase tracking-tight">
                                Recuerda que los campos marcados con (*) son obligatorios para generar facturas válidas ante el SAT.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </form>
    </div>

    <!-- Modal Suite -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showPreview" class="fixed inset-0 z-[100] flex items-center justify-center p-4 lg:p-12">
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="showPreview = false"></div>
                
                <div class="relative bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 w-full max-w-2xl overflow-hidden">
                    <div class="p-10">
                        <div class="flex justify-between items-start mb-10">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400 mb-2">Resumen Operativo</h3>
                                <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter truncate max-w-md">Vista Previa</p>
                            </div>
                            <button @click="showPreview = false" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 transition-all">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">RFC</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white font-mono uppercase">{{ form.rfc || 'PENDIENTE' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Email</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ form.email || 'NO ASIGNADO' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Teléfono</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ form.telefono || 'NO ASIGNADO' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Régimen</span>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ form.regimen_fiscal || 'PENDIENTE' }}</p>
                                </div>
                            </div>

                            <div class="p-6 bg-slate-50 dark:bg-slate-950/50 rounded-3xl border border-slate-200/50 dark:border-slate-800/50">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Dato Maestro</span>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase leading-relaxed tracking-tight">
                                    {{ form.nombre_razon_social || 'SIN IDENTIFICAR' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-12 flex items-center justify-end gap-3">
                            <button @click="showPreview = false" class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-500 hover:text-slate-900 dark:hover:text-white transition-all">Cerrar</button>
                            <button @click="showPreview = false" class="px-10 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:bg-blue-700 transition-all">Confirmar Datos</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

defineOptions({ layout: AppLayout });

// Configuración de Notyf
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
});

// Estados reactivos
const showPreview = ref(false);
const rfcValid = ref(false);
const emailValid = ref(false);
const telefonoValid = ref(false);

const page = usePage();
const flash = computed(() => page.props.flash || {});

watch(flash, (newFlash) => {
  if (newFlash.success) notyf.success(newFlash.success);
  if (newFlash.error) notyf.error(newFlash.error);
}, { deep: true });

// Listas predefinidas
const regimenesFiscales = {
  fisica: [
    { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
    { codigo: '614', descripcion: 'Personas Físicas con Actividades Empresariales' },
    { codigo: '616', descripcion: 'Personas Físicas con Actividades Profesionales' },
    { codigo: '621', descripcion: 'Incorporación Fiscal' },
    { codigo: '626', descripcion: 'Régimen Simplificado de Confianza' },
    { codigo: '629', descripcion: 'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales' },
    { codigo: '630', descripcion: 'Enajenación de acciones en bolsa de valores' }
  ],
  moral: [
    { codigo: '601', descripcion: 'General de Ley Personas Morales' },
    { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
    { codigo: '609', descripcion: 'Consolidación' },
    { codigo: '620', descripcion: 'Sociedades Cooperativas de Producción' },
    { codigo: '622', descripcion: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras' },
    { codigo: '623', descripcion: 'Opcional para Grupos de Sociedades' },
    { codigo: '624', descripcion: 'Coordinados' }
  ]
};

const regimenesFiscalesFiltrados = computed(() => {
  if (!form.tipo_persona) return [];
  return regimenesFiscales[form.tipo_persona] || [];
});

const form = useForm({
  nombre_razon_social: '',
  tipo_persona: '',
  rfc: '',
  regimen_fiscal: '',
  uso_cfdi: '',
  email: '',
  telefono: '',
  calle: '',
  numero_exterior: '',
  numero_interior: '',
  colonia: '',
  codigo_postal: '83000',
  municipio: 'HERMOSILLO',
  estado: 'SONORA',
  pais: 'MEXICO'
});

const formValid = computed(() => {
  return form.nombre_razon_social &&
         form.tipo_persona &&
         rfcValid.value &&
         form.regimen_fiscal;
});

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.has('nombre_razon_social')) form.nombre_razon_social = params.get('nombre_razon_social');
  if (params.has('rfc')) {
    form.rfc = params.get('rfc');
    if (params.has('tipo_persona')) {
      form.tipo_persona = params.get('tipo_persona');
      setTimeout(() => validarRFC(), 100);
    }
  }
  if (params.has('regimen_fiscal')) form.regimen_fiscal = params.get('regimen_fiscal');
  if (params.has('rfc')) notyf.success('Datos precargados. Complete la información.');
});

const submit = () => {
  if (!formValid.value) return;
  form.post(route('proveedores.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      resetValidationStates();
      notyf.success('Proveedor creado exitosamente');
    },
    onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
            Object.values(errors).forEach(error => notyf.error(error));
        }
    }
  });
};

const resetForm = () => {
  form.reset();
  resetValidationStates();
};

const resetValidationStates = () => {
  rfcValid.value = false;
  emailValid.value = false;
  telefonoValid.value = false;
};

const previewData = () => {
  showPreview.value = true;
};

const convertirAMayusculas = (campo) => {
  if (form[campo]) form[campo] = form[campo].toUpperCase().trim();
};

const onTipoPersonaChange = () => {
  form.rfc = '';
  rfcValid.value = false;
  form.clearErrors('rfc');
  form.regimen_fiscal = '';
  form.clearErrors('regimen_fiscal');
};

const onRfcInput = (event) => {
  form.rfc = event.target.value.toUpperCase();
  validarRFC();
};

const validarRFC = () => {
  if (!form.rfc || !form.tipo_persona) {
    rfcValid.value = false;
    return;
  }
  const rfcRegexFisica = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/;
  const rfcRegexMoral = /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;

  if (form.tipo_persona === 'fisica') {
    if (form.rfc.length !== 13 || !rfcRegexFisica.test(form.rfc)) {
      form.setError('rfc', 'Formato de RFC persona física inválido');
      rfcValid.value = false;
      return;
    }
  } else if (form.tipo_persona === 'moral') {
    if (form.rfc.length !== 12 || !rfcRegexMoral.test(form.rfc)) {
      form.setError('rfc', 'Formato de RFC persona moral inválido');
      rfcValid.value = false;
      return;
    }
  }
  form.clearErrors('rfc');
  rfcValid.value = true;
};

const validateEmail = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!form.email) {
    emailValid.value = true;
    form.clearErrors('email');
    return;
  }
  if (!emailRegex.test(form.email)) {
    form.setError('email', 'Email inválido');
    emailValid.value = false;
    return;
  }
  form.clearErrors('email');
  emailValid.value = true;
};

const validarTelefono = () => {
  form.telefono = form.telefono.replace(/\D/g, '');
  if (!form.telefono) {
    telefonoValid.value = true;
    form.clearErrors('telefono');
    return;
  }
  if (form.telefono.length !== 10) {
    form.setError('telefono', 'Teléfono debe tener 10 dígitos');
    telefonoValid.value = false;
    return;
  }
  form.clearErrors('telefono');
  telefonoValid.value = true;
};

const validarCodigoPostal = async () => {
  form.codigo_postal = form.codigo_postal.replace(/\D/g, '');
  if (form.codigo_postal.length === 5) {
    form.clearErrors('codigo_postal');
    try {
      const response = await fetch(`/api/cp/${form.codigo_postal}`);
      if (response.ok) {
        const data = await response.json();
        form.estado = data.estado;
        form.municipio = data.municipio;
        form.pais = data.pais;
        if (data.colonias && data.colonias.length === 1) form.colonia = data.colonias[0];
      }
    } catch (error) { console.warn('CP Error:', error); }
  } else if (form.codigo_postal.length > 0) {
    form.setError('codigo_postal', 'CP debe tener 5 dígitos');
  }
};
</script>

<style>
.animate-fade-in-up {
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-pulse-slow {
    animation: pulse-slow 8s ease-in-out infinite;
}

@keyframes pulse-slow {
    0%, 100% { opacity: 0.1; transform: scale(1); }
    50% { opacity: 0.15; transform: scale(1.1); }
}

input:focus, select:focus {
    outline: none;
}
</style>
