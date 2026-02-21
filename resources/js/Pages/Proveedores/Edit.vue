<template>
  <Head title="Editar Proveedor" />

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
            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter uppercase">Editar Proveedor</h1>
            <div class="flex items-center gap-4">
                <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400">Expediente Maestro</span>
                <div class="h-1 w-1 rounded-full bg-slate-300 dark:bg-slate-700"></div>
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="isFormValid ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                    <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
                        {{ isFormValid ? 'Campos Válidos' : 'Revisar Información' }}
                    </span>
                </div>
            </div>
        </div>

        <Link 
          :href="route('proveedores.index')"
          class="flex items-center gap-3 px-8 py-4 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300 active:scale-95 border border-slate-200/50 dark:border-slate-800/50"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Cancelar y Volver
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
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Perfil del proveedor</p>
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
                            placeholder="Nombre completo o Razón Social"
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
                            @change="handleTipoPersonaChange"
                            required
                        >
                            <option value="">Seleccionar tipo</option>
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
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': isRfcValid && form.rfc }"
                                @input="handleRfcInput"
                                placeholder="..."
                                :disabled="!form.tipo_persona"
                                required
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="isRfcValid && form.rfc" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
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
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Información Fiscal</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Cumplimiento tributario</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Régimen Fiscal *</label>
                        <select
                            v-model="form.regimen_fiscal"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600/20 transition-all appearance-none cursor-pointer"
                            required
                        >
                            <option value="">Seleccionar régimen</option>
                            <option v-for="regimen in regimenesFiscales" :key="regimen.codigo" :value="regimen.codigo">
                                {{ regimen.codigo }} - {{ regimen.descripcion }}
                            </option>
                        </select>
                        <p v-if="form.errors.regimen_fiscal" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.regimen_fiscal }}</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Uso CFDI *</label>
                        <select
                            v-model="form.uso_cfdi"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600/20 transition-all appearance-none cursor-pointer"
                            required
                        >
                            <option value="">Seleccionar uso</option>
                            <option v-for="uso in usosCFDI" :key="uso.codigo" :value="uso.codigo">
                                {{ uso.codigo }} - {{ uso.descripcion }}
                            </option>
                        </select>
                        <p v-if="form.errors.uso_cfdi" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.uso_cfdi }}</p>
                    </div>
                </div>
          </div>

          <!-- Seccion: Información de Contacto -->
          <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50 transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
                <div class="flex items-center gap-4 mb-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Información de Contacto</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Comunicación oficial</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Email -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Email *</label>
                        <div class="relative">
                            <input
                                v-model="form.email"
                                type="email"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': isValidEmail && form.email }"
                                @input="validateEmail"
                                placeholder="..."
                                required
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="isValidEmail && form.email" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </div>
                        </div>
                        <p v-if="form.errors.email" class="text-[10px] font-black text-rose-500 uppercase tracking-widest ml-4">{{ form.errors.email }}</p>
                    </div>

                    <!-- Teléfono -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Teléfono * (10 DÍGITOS)</label>
                        <div class="relative">
                            <input
                                v-model="form.telefono"
                                type="tel"
                                maxlength="10"
                                class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all font-mono"
                                :class="{ 'ring-2 ring-emerald-500/20 bg-emerald-500/5': isTelefonoValid && form.telefono }"
                                @input="handleTelefonoInput"
                                placeholder="..."
                                required
                            />
                            <div class="absolute inset-y-0 right-6 flex items-center">
                                <svg v-if="isTelefonoValid && form.telefono" class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
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
                        <h2 class="text-xs font-black uppercase tracking-[0.3em] text-slate-900 dark:text-white">Ubicación Actualizada</h2>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Documentación de domicilio</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Calle -->
                    <div class="md:col-span-2 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Calle *</label>
                        <input
                            v-model="form.calle"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all"
                            @blur="convertirAMayusculas('calle')"
                            required
                        />
                    </div>

                    <!-- Código Postal -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Código Postal *</label>
                        <input
                            v-model="form.codigo_postal"
                            type="text"
                            maxlength="5"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-black text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600/20 transition-all font-mono"
                            @input="validateCodigoPostal"
                            required
                        />
                    </div>

                    <!-- Números -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Ext *</label>
                        <input
                            v-model="form.numero_exterior"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white"
                            required
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Int</label>
                        <input
                            v-model="form.numero_interior"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white"
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Colonia *</label>
                        <input
                            v-model="form.colonia"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-100/50 dark:bg-slate-950/50 border-none rounded-2xl text-sm font-bold text-slate-900 dark:text-white"
                            @blur="convertirAMayusculas('colonia')"
                            required
                        />
                    </div>

                    <!-- Read Only Geo -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Municipio</label>
                        <input
                            v-model="form.municipio"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            readonly
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">Estado</label>
                        <input
                            v-model="form.estado"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            readonly
                        />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 ml-2">País</label>
                        <input
                            v-model="form.pais"
                            type="text"
                            class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-none rounded-2xl text-sm font-black text-slate-500 dark:text-slate-400"
                            readonly
                        />
                    </div>
                </div>
          </div>
        </div>

        <!-- Right Column: Actions -->
        <div class="lg:col-span-4 space-y-10">
            <div class="sticky top-10 space-y-10">
                
                <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] p-10 border border-slate-200/50 dark:border-slate-800/50">
                    <h3 class="text-xs font-black uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400 mb-8">Gestión de Cambios</h3>
                    
                    <div class="space-y-6">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Proveedor Actual</span>
                            <span class="text-sm font-black text-slate-900 dark:text-white truncate uppercase">{{ props.proveedor.nombre_razon_social }}</span>
                        </div>

                        <div class="h-px bg-slate-200/50 dark:bg-slate-800/50 w-full my-6"></div>

                        <div class="mt-10 space-y-3">
                            <button
                                type="submit"
                                class="w-full flex items-center justify-center gap-3 px-8 py-5 bg-blue-600 text-white rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-1 transition-all duration-300 active:scale-95 bg-gradient-to-r from-blue-600 to-indigo-600 disabled:opacity-50 disabled:grayscale disabled:pointer-events-none"
                                :disabled="form.processing || !isFormValid"
                            >
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Sincronizando...' : 'Actualizar Perfil' }}</span>
                            </button>
                            
                            <button
                                type="button"
                                @click="resetForm"
                                class="w-full px-8 py-5 bg-slate-100 dark:bg-slate-900 text-slate-600 dark:text-slate-300 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 dark:hover:bg-slate-800 transition-all duration-300"
                            >
                                Revertir Cambios
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-indigo-600/5 rounded-3xl p-8 border border-indigo-500/10 transition-all">
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                            <span class="text-[9px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 block mb-1">Auditoría</span>
                            <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 leading-relaxed uppercase tracking-tight">
                                Las actualizaciones impactarán en futuras facturas y documentos generados para este proveedor.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </form>
    </div>

    <!-- Success Toast Notification -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-10 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showSuccessToast" class="fixed bottom-10 right-10 z-[200] bg-emerald-500 text-white px-8 py-5 rounded-[1.5rem] shadow-2xl shadow-emerald-500/20 border border-emerald-400/20 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xs font-black uppercase tracking-widest">Éxito Operativos</span>
                    <span class="text-[10px] font-bold opacity-80 uppercase tracking-tight">Proveedor actualizado correctamente</span>
                </div>
            </div>
        </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
  proveedor: Object,
});

const showSuccessToast = ref(false);
const isValidEmail = ref(true);

const regimenesFiscales = [
  { codigo: '601', descripcion: 'General de Ley Personas Morales' },
  { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos' },
  { codigo: '605', descripcion: 'Sueldos y Salarios e Ingresos Asimilados a Salarios' },
  { codigo: '606', descripcion: 'Arrendamiento' },
  { codigo: '607', descripcion: 'Régimen de Enajenación o Adquisición de Bienes' },
  { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales' },
  { codigo: '626', descripcion: 'Régimen Simplificado de Confianza' }
];

const usosCFDI = [
  { codigo: 'G01', descripcion: 'Adquisición de mercancías' },
  { codigo: 'G03', descripcion: 'Gastos en general' },
  { codigo: 'I01', descripcion: 'Construcciones' },
  { codigo: 'S01', descripcion: 'Sin efectos fiscales' }
];

const form = useForm({
  nombre_razon_social: props.proveedor.nombre_razon_social || '',
  tipo_persona: props.proveedor.tipo_persona || '',
  rfc: props.proveedor.rfc || '',
  regimen_fiscal: props.proveedor.regimen_fiscal || '',
  uso_cfdi: props.proveedor.uso_cfdi || '',
  email: props.proveedor.email || '',
  telefono: props.proveedor.telefono || '',
  calle: props.proveedor.calle || '',
  numero_exterior: props.proveedor.numero_exterior || '',
  numero_interior: props.proveedor.numero_interior || '',
  colonia: props.proveedor.colonia || '',
  codigo_postal: props.proveedor.codigo_postal || '',
  municipio: props.proveedor.municipio || '',
  estado: props.proveedor.estado || '',
  pais: props.proveedor.pais || 'México'
});

const originalFormData = { ...form.data() };

const isRfcValid = computed(() => {
  if (!form.rfc || !form.tipo_persona) return false;
  const regex = form.tipo_persona === 'fisica' ? /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/ : /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;
  return regex.test(form.rfc);
});

const isTelefonoValid = computed(() => form.telefono && /^\d{10}$/.test(form.telefono));

const isFormValid = computed(() => {
  return form.nombre_razon_social && form.tipo_persona && isRfcValid.value && form.email && isTelefonoValid.value;
});

const convertirAMayusculas = (campo) => {
  if (form[campo]) form[campo] = form[campo].toString().toUpperCase().trim();
};

const handleTipoPersonaChange = () => {
  form.rfc = '';
  form.clearErrors('rfc');
};

const handleRfcInput = (event) => {
    form.rfc = event.target.value.toUpperCase();
    validateRFC();
};

const handleTelefonoInput = (event) => {
    form.telefono = event.target.value.replace(/\D/g, '');
    validateTelefono();
};

const validateEmail = () => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    isValidEmail.value = emailRegex.test(form.email);
    if (form.email && !isValidEmail.value) form.setError('email', 'Email inválido');
    else form.clearErrors('email');
};

const validateRFC = () => {
    if (!form.tipo_persona) { form.setError('rfc', 'Seleccione tipo'); return; }
    const regex = form.tipo_persona === 'fisica' ? /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/ : /^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/;
    if (!regex.test(form.rfc)) form.setError('rfc', 'RFC Inválido');
    else form.clearErrors('rfc');
};

const validateTelefono = () => {
    if (form.telefono && !/^\d{10}$/.test(form.telefono)) form.setError('telefono', 'Debe ser de 10 dígitos');
    else form.clearErrors('telefono');
};

const validateCodigoPostal = async (event) => {
    const value = event.target.value.replace(/\D/g, '').slice(0, 5);
    form.codigo_postal = value;
    if (value.length === 5) {
        try {
            const response = await fetch(`/api/cp/${value}`);
            if (response.ok) {
                const data = await response.json();
                form.estado = data.estado;
                form.municipio = data.municipio;
                form.pais = data.pais;
                if (data.colonias && data.colonias.length === 1) form.colonia = data.colonias[0];
            }
        } catch (e) { console.warn(e); }
    }
};

const resetForm = () => {
    Object.keys(originalFormData).forEach(key => { form[key] = originalFormData[key]; });
    form.clearErrors();
};

const submit = () => {
    if (!isFormValid.value) return;
    form.put(route('proveedores.update', props.proveedor.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccessToast.value = true;
            setTimeout(() => { showSuccessToast.value = false; }, 3000);
        }
    });
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
