<template>
  <AppLayout title="Calculadora y Cotizador de Pólizas TI — Asistencia Vircom">
    <template #header>
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">
              <i class="fas fa-shield-alt"></i> Asistencia Vircom
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
              <i class="fas fa-chart-line"></i> Precios de Mercado 2026
            </span>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
              <i class="fas fa-map-marker-alt"></i> Hermosillo, Sonora
            </span>
          </div>
          <h2 class="font-black text-2xl text-slate-800 dark:text-white leading-tight flex items-center gap-3">
            <span class="p-2.5 rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/30">
              <i class="fas fa-calculator"></i>
            </span>
            Calculadora y Cotizador de Pólizas TI
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Cotizador comercial para el equipo de ventas: calcula con tarifas reales de mercado por equipo, servidores, redes, alarmas y CONTPAQi.
          </p>
        </div>

        <!-- Botones de Navegación Rápida -->
        <div class="flex items-center gap-2">
          <Link
            href="/polizas-servicio"
            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-2 shadow-xs"
          >
            <i class="fas fa-file-signature text-blue-500"></i>
            <span>Ver Pólizas Activas</span>
          </Link>
          <button
            type="button"
            @click="imprimirPropuesta"
            class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all flex items-center gap-2 shadow-xs cursor-pointer"
            title="Imprimir o guardar PDF"
          >
            <i class="fas fa-print text-indigo-500"></i>
            <span>Imprimir</span>
          </button>
        </div>
      </div>
    </template>

    <div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Banner de Paquete Integral si aplica -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
        <div v-if="esPaqueteIntegral" class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-sky-600 text-white shadow-lg shadow-emerald-500/20 border border-emerald-400/30 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl font-bold">
              ⭐
            </span>
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-amber-300">¡Paquete Solución Integral 360° Activado!</p>
              <p class="text-xs text-white/90">Este cliente cuenta con cobertura total: Cómputo + Servidor + Red + CONTPAQi + Seguridad. Se aplica una bonificación del <strong>10% adicional</strong>.</p>
            </div>
          </div>
          <span class="hidden sm:inline-block px-3 py-1 rounded-full bg-white/20 text-xs font-black uppercase tracking-wider border border-white/30">
            -10% Bundle Bonificado
          </span>
        </div>
      </transition>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- COLUMNA IZQUIERDA: CONFIGURADOR DE EQUIPOS (7 COLS) -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- 1. Datos del Cliente / Prospecto (Idéntico a Módulo de Ventas) -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-user-tie"></i> 1. Datos del Cliente / Prospecto
              </h3>
              <button
                type="button"
                @click="modoProspectoLibre = !modoProspectoLibre"
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold transition-all border cursor-pointer"
                :class="modoProspectoLibre 
                  ? 'bg-blue-600 text-white border-blue-600 shadow-xs' 
                  : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-600 hover:bg-slate-200'"
              >
                <i :class="modoProspectoLibre ? 'fas fa-search' : 'fas fa-user-plus'"></i>
                <span>{{ modoProspectoLibre ? '← Volver a Buscar Cliente' : '+ Capturar Prospecto Nuevo' }}</span>
              </button>
            </div>

            <!-- Buscador de Clientes IDÉNTICO al módulo de Ventas -->
            <div v-if="!modoProspectoLibre">
              <BuscarCliente
                :clientes="props.clientes"
                :cliente-seleccionado="clienteSeleccionado"
                @cliente-seleccionado="onClienteSeleccionado"
                :mostrar-opcion-nuevo-cliente="false"
                label-busqueda="Buscar Cliente en el Sistema"
                placeholder-busqueda="Escribe nombre, razón social, RFC, teléfono o correo..."
                mensaje-vacio="No hay cliente seleccionado"
                submensaje-vacio="Busca y selecciona un cliente registrado para autocompletar la propuesta"
              />
            </div>

            <!-- Captura de Prospecto Nuevo Manual -->
            <div v-else class="space-y-4">
              <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/30 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2">
                <i class="fas fa-info-circle text-amber-500"></i>
                <span>Modo Prospecto Nuevo: Ingresa los datos manualmente para generar y enviar la propuesta sin dar de alta al cliente en el catálogo aún.</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial / Razón Social *</label>
                  <input 
                    type="text" 
                    v-model="form.cliente_nombre" 
                    placeholder="Ej. Comercializadora del Noroeste SA de CV" 
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500" 
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">RFC (Opcional)</label>
                  <input 
                    type="text" 
                    v-model="form.cliente_rfc" 
                    placeholder="XAXX010101000" 
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 uppercase" 
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico *</label>
                  <input 
                    type="email" 
                    v-model="form.cliente_email" 
                    placeholder="contacto@empresa.com" 
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500" 
                  />
                </div>

                <div>
                  <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono / WhatsApp</label>
                  <input 
                    type="tel" 
                    v-model="form.cliente_telefono" 
                    placeholder="6621234567" 
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500" 
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Infraestructura de Cómputo (Con Descuento por Volumen) -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-sm font-black text-sky-600 dark:text-sky-400 uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-desktop"></i> 2. Computadoras y Laptops (Endpoints)
              </h3>
              <!-- Badge de Descuento por Volumen -->
              <span 
                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border transition-all"
                :class="form.computadoras >= 20 
                  ? 'bg-purple-500/10 text-purple-600 border-purple-500/30' 
                  : (form.computadoras >= 10 
                    ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30' 
                    : (form.computadoras >= 5 
                      ? 'bg-blue-500/10 text-blue-600 border-blue-500/30' 
                      : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border-transparent'))"
              >
                {{ ladderInfo.label }} (${{ ladderInfo.precioUnitario }} c/u)
              </span>
            </div>

            <div class="space-y-4">
              <!-- Stepper Computadoras -->
              <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                  <div>
                    <span class="font-bold text-slate-800 dark:text-white text-sm">Cantidad de Equipos de Usuario (PC / Mac / Laptops)</span>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                      Soporte remoto ilimitado L-S, antivirus gestionado, optimización y mantenimiento preventivo semestral con pasta térmica.
                    </p>
                  </div>
                  <div class="flex items-center gap-2 self-end sm:self-center">
                    <button type="button" @click="changeQty('computadoras', -1)" class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-white transition-all cursor-pointer">-</button>
                    <input 
                      type="number" 
                      min="0" 
                      v-model.number="form.computadoras" 
                      class="w-16 text-center font-black text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl py-1.5 text-base"
                    />
                    <button type="button" @click="changeQty('computadoras', 1)" class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-white transition-all cursor-pointer">+</button>
                  </div>
                </div>

                <!-- Botones Rápidos de Selección -->
                <div class="flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-slate-200/60 dark:border-white/5">
                  <span class="text-[11px] font-bold text-slate-400 uppercase">Accesos rápidos:</span>
                  <button 
                    v-for="qty in [3, 5, 10, 15, 20, 30]" 
                    :key="qty"
                    type="button"
                    @click="form.computadoras = qty"
                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all cursor-pointer"
                    :class="form.computadoras === qty 
                      ? 'bg-blue-600 text-white shadow-xs' 
                      : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/10 hover:bg-slate-100'"
                  >
                    {{ qty }} PCs
                  </button>
                </div>

                <!-- Desglose de Escala de Precios de Mercado -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 pt-3 border-t border-slate-200/60 dark:border-white/5 text-[11px]">
                  <div class="p-2 rounded-xl text-center" :class="form.computadoras >= 1 && form.computadoras <= 4 ? 'bg-blue-500/10 border border-blue-500/30 font-bold text-blue-600' : 'text-slate-400'">
                    <div>1 a 4 equipos</div>
                    <div class="font-black">$320 c/u</div>
                  </div>
                  <div class="p-2 rounded-xl text-center" :class="form.computadoras >= 5 && form.computadoras <= 9 ? 'bg-blue-500/10 border border-blue-500/30 font-bold text-blue-600' : 'text-slate-400'">
                    <div>5 a 9 equipos</div>
                    <div class="font-black">$270 c/u (-15%)</div>
                  </div>
                  <div class="p-2 rounded-xl text-center" :class="form.computadoras >= 10 && form.computadoras <= 19 ? 'bg-emerald-500/10 border border-emerald-500/30 font-bold text-emerald-600' : 'text-slate-400'">
                    <div>10 a 19 equipos</div>
                    <div class="font-black">$210 c/u (-35%)</div>
                  </div>
                  <div class="p-2 rounded-xl text-center" :class="form.computadoras >= 20 ? 'bg-purple-500/10 border border-purple-500/30 font-bold text-purple-600' : 'text-slate-400'">
                    <div>20+ equipos</div>
                    <div class="font-black">$180 c/u (-44%)</div>
                  </div>
                </div>

                <div v-if="form.computadoras > 0" class="mt-2 text-right text-xs font-bold text-slate-600 dark:text-slate-300">
                  Subtotal Cómputo: <span class="text-blue-600 dark:text-blue-400 font-black">${{ formatNumber(costoComputadoras) }} MXN/mes</span>
                  <span v-if="ahorroVolumenPcs > 0" class="text-emerald-500 ml-2 font-medium">
                    (Ahorro por volumen: -${{ formatNumber(ahorroVolumenPcs) }})
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Servidores Dedicados y Bases de Datos -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-server"></i> 3. Servidores Dedicados y Bases de Datos SQL
            </h3>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                  <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                    <i class="fas fa-database text-indigo-500"></i>
                    Servidores Físicos / Virtuales / SQL Server / Active Directory
                  </span>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Monitoreo 24/7 de CPU/RAM/Discos, parches de seguridad de SO (Windows Server/Linux), verificación de RAID y respaldo diario automático a la nube ($1,250 MXN c/u).
                  </p>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-center">
                  <button type="button" @click="changeQty('servidores_cantidad', -1)" class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-white transition-all cursor-pointer">-</button>
                  <input 
                    type="number" 
                    min="0" 
                    v-model.number="form.servidores_cantidad" 
                    class="w-16 text-center font-black text-indigo-600 dark:text-indigo-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl py-1.5 text-base"
                  />
                  <button type="button" @click="changeQty('servidores_cantidad', 1)" class="w-9 h-9 rounded-xl bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-white transition-all cursor-pointer">+</button>
                </div>
              </div>

              <div v-if="form.servidores_cantidad > 0" class="mt-2 text-right text-xs font-bold text-slate-600 dark:text-slate-300">
                Subtotal Servidores: <span class="text-indigo-600 dark:text-indigo-400 font-black">${{ formatNumber(costoServidores) }} MXN/mes</span>
              </div>
            </div>
          </div>

          <!-- 4. Redes y Conectividad -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-cyan-600 dark:text-cyan-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-network-wired"></i> 4. Infraestructura de Redes y Conectividad
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <!-- Opción 1: Sin Red -->
              <div 
                @click="form.tipo_red = 'ninguna'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer text-center flex flex-col justify-between"
                :class="form.tipo_red === 'ninguna' 
                  ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 shadow-sm' 
                  : 'border-slate-200 dark:border-white/5 hover:border-slate-300'"
              >
                <div>
                  <span class="text-xs font-black uppercase tracking-wider block text-slate-700 dark:text-slate-300">Sin Gestión de Red</span>
                  <p class="text-[11px] text-slate-400 mt-1">El cliente administra su propio router de proveedor de internet.</p>
                </div>
                <div class="mt-3 font-black text-slate-700 dark:text-slate-300 text-sm">$0 MXN</div>
              </div>

              <!-- Opción 2: Red Básica PyME -->
              <div 
                @click="form.tipo_red = 'basica'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer text-center flex flex-col justify-between"
                :class="form.tipo_red === 'basica' 
                  ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20 shadow-sm' 
                  : 'border-slate-200 dark:border-white/5 hover:border-slate-300'"
              >
                <div>
                  <span class="text-xs font-black uppercase tracking-wider block text-cyan-600 dark:text-cyan-400">Red Básica PyME</span>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Monitoreo de módem de proveedor ISP, router principal, switches no administrables y Wi-Fi estándar.</p>
                </div>
                <div class="mt-3 font-black text-cyan-600 dark:text-cyan-400 text-sm">+$450 MXN/mes</div>
              </div>

              <!-- Opción 3: Red Empresarial Wi-Fi Corporativo -->
              <div 
                @click="form.tipo_red = 'empresarial'"
                class="p-4 rounded-2xl border-2 transition-all cursor-pointer text-center flex flex-col justify-between relative overflow-hidden"
                :class="form.tipo_red === 'empresarial' 
                  ? 'border-cyan-500 bg-cyan-50/50 dark:bg-cyan-950/20 shadow-sm' 
                  : 'border-slate-200 dark:border-white/5 hover:border-slate-300'"
              >
                <div class="absolute top-0 right-0 bg-cyan-500 text-white text-[9px] font-black px-2 py-0.5 rounded-bl-lg uppercase">
                  Recomendado
                </div>
                <div>
                  <span class="text-xs font-black uppercase tracking-wider block text-cyan-600 dark:text-cyan-400">Red Empresarial / Wi-Fi</span>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Switches PoE administrables, VLANs, Access Points UniFi/Omada, Firewall/Balanceador de carga y failover WAN.</p>
                </div>
                <div class="mt-3 font-black text-cyan-600 dark:text-cyan-400 text-sm">+$850 MXN/mes</div>
              </div>
            </div>
          </div>

          <!-- 5. Sistemas CONTPAQi / ERP / Facturación -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-file-invoice-dollar"></i> 5. Sistemas CONTPAQi / Facturación / ERP
            </h3>

            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
              <div>
                <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                  <i class="fas fa-chart-pie text-emerald-500"></i>
                  Soporte Especializado a Sistemas CONTPAQi
                </span>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Mantenimiento a bases de datos SQL Server de CONTPAQi (Contabilidad, Bancos, Nóminas, Comercial Premium, Factura Electrónica), soporte a timbrado, reindexación y resolución de incidencias fiscales (+$650 MXN/mes).
                </p>
              </div>
              <input 
                type="checkbox" 
                v-model="form.tiene_contpaqi" 
                class="w-6 h-6 rounded-lg text-emerald-600 focus:ring-emerald-500 cursor-pointer" 
              />
            </div>
          </div>

          <!-- 6. Seguridad Física: Cámaras CCTV, Alarmas y Accesos -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-shield-alt"></i> 6. Seguridad Física, CCTV y Accesos
            </h3>

            <div class="space-y-4">
              <!-- Stepper Cámaras -->
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5 gap-2">
                <div>
                  <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                    <i class="fas fa-video text-rose-500"></i>
                    Cámaras de Seguridad CCTV (DVR / NVR)
                  </span>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Monitoreo de disco duro y grabación, limpieza y reorientación de lentes, app móvil ($300 base + $35/cámara).
                  </p>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-center">
                  <button type="button" @click="changeQty('camaras', -2)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">-</button>
                  <input 
                    type="number" 
                    min="0" 
                    v-model.number="form.camaras" 
                    class="w-16 text-center font-black text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-xl py-1 text-sm"
                  />
                  <button type="button" @click="changeQty('camaras', 2)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">+</button>
                </div>
              </div>

              <!-- Toggle Alarma -->
              <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5">
                <div>
                  <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                    <i class="fas fa-bell text-amber-500"></i>
                    Sistema de Alarma contra Robo
                  </span>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Pruebas de sirena, sensores PIR/magnéticos, revisión de batería de respaldo 12V y vinculación app (+$380/mes).
                  </p>
                </div>
                <input type="checkbox" v-model="form.tiene_alarma" class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500 cursor-pointer" />
              </div>

              <!-- Stepper Reloj Checador Biométrico -->
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5 gap-2">
                <div>
                  <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                    <i class="fas fa-fingerprint text-purple-500"></i>
                    Relojes Checadores Biométricos (Huella / Facial)
                  </span>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Descarga de checadas, respaldo de usuarios y mantenimiento ($280 c/u).</p>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-center">
                  <button type="button" @click="changeQty('relojes_checadores', -1)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">-</button>
                  <span class="w-8 text-center font-black text-blue-600 dark:text-blue-400">{{ form.relojes_checadores }}</span>
                  <button type="button" @click="changeQty('relojes_checadores', 1)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">+</button>
                </div>
              </div>

              <!-- Stepper Controles de Acceso -->
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-white/5 gap-2">
                <div>
                  <span class="font-bold text-slate-800 dark:text-white text-sm flex items-center gap-2">
                    <i class="fas fa-door-closed text-amber-500"></i>
                    Controles de Acceso (Chapas Magnéticas / Torniquetes)
                  </span>
                  <p class="text-xs text-slate-500 dark:text-slate-400">Chapa magnética, botón de salida, lector RFID y enrolamiento ($320 c/u).</p>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-center">
                  <button type="button" @click="changeQty('controles_acceso', -1)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">-</button>
                  <span class="w-8 text-center font-black text-blue-600 dark:text-blue-400">{{ form.controles_acceso }}</span>
                  <button type="button" @click="changeQty('controles_acceso', 1)" class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 font-bold hover:bg-slate-300">+</button>
                </div>
              </div>
            </div>
          </div>

          <!-- 7. Visitas Presenciales en Sitio y SLA -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-purple-600 dark:text-purple-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-car-side"></i> 7. Visitas Presenciales en Sitio y SLA
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Visitas Presenciales al Mes (Hermosillo)</label>
                <select v-model="form.visitas_mensuales" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 cursor-pointer">
                  <option :value="1">1 Visita al mes (Incluida en tarifa base)</option>
                  <option :value="2">2 Visitas al mes (+$350 MXN) (Recomendada)</option>
                  <option :value="4">4 Visitas al mes (1 por semana) (+$1,000 MXN)</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tiempo de Respuesta Garantizado (SLA)</label>
                <select v-model="form.sla_tiempo" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500 cursor-pointer">
                  <option value="Menos de 2 horas">Menos de 2 horas (Estándar)</option>
                  <option value="Menos de 45 minutos">Menos de 45 minutos (Prioritario)</option>
                  <option value="Menos de 15 minutos">Menos de 15 minutos (Crítico 24/7)</option>
                </select>
              </div>
            </div>

            <div class="mt-4">
              <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notas u Observaciones Especiales (Aparecen en la propuesta)</label>
              <textarea v-model="form.notas" rows="2" placeholder="Ej. El cliente cuenta con sucursal adicional en Parque Industrial..." class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
          </div>

          <!-- 8. Descuento Comercial Negociable (Ventas) -->
          <div class="bg-white dark:bg-slate-800/80 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-3xl p-6 shadow-sm">
            <h3 class="text-sm font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-4 flex items-center gap-2">
              <i class="fas fa-tags"></i> 8. Descuento Comercial Negociable (Cierre de Ventas)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
              <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tipo de Descuento</label>
                <select v-model="form.descuento_comercial_tipo" class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-amber-500 cursor-pointer">
                  <option value="ninguno">Sin descuento adicional</option>
                  <option value="porcentaje">Porcentaje (%)</option>
                  <option value="fijo">Monto Fijo ($ MXN)</option>
                </select>
              </div>

              <div v-if="form.descuento_comercial_tipo !== 'ninguno'">
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                  Valor del Descuento {{ form.descuento_comercial_tipo === 'porcentaje' ? '(%)' : '($ MXN)' }}
                </label>
                <div class="flex items-center gap-2">
                  <input 
                    type="number" 
                    min="0" 
                    v-model.number="form.descuento_comercial_valor" 
                    class="w-full bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm text-slate-800 dark:text-white focus:ring-2 focus:ring-amber-500" 
                  />
                  <span class="text-xs font-bold text-slate-500">{{ form.descuento_comercial_tipo === 'porcentaje' ? '%' : 'MXN' }}</span>
                </div>
              </div>

              <div v-if="form.descuento_comercial_tipo !== 'ninguno'" class="flex items-center gap-1.5 self-end pb-1">
                <button 
                  v-if="form.descuento_comercial_tipo === 'porcentaje'" 
                  v-for="p in [5, 10, 15]" 
                  :key="p"
                  type="button" 
                  @click="form.descuento_comercial_valor = p"
                  class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20 hover:bg-amber-500/20 cursor-pointer"
                >
                  -{{ p }}%
                </button>
              </div>
            </div>

            <div v-if="descuentoComercialMonto > 0" class="mt-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-bold text-amber-700 dark:text-amber-300 flex items-center justify-between">
              <span>Descuento Comercial Aplicado:</span>
              <span>-${{ formatNumber(descuentoComercialMonto) }} MXN/mes</span>
            </div>
          </div>

        </div>

        <!-- COLUMNA DERECHA: TARJETA DE RESUMEN Y ACCIONES DE VENTA (5 COLS) -->
        <div class="lg:col-span-5">
          <div class="sticky top-6 space-y-6">
            
            <!-- TARJETA PRINCIPAL DE INVERSIÓN -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-900 to-blue-950 border-2 border-emerald-500/40 rounded-3xl p-6 text-white shadow-2xl relative overflow-hidden">
              <div class="absolute -right-8 -top-8 w-36 h-36 bg-blue-500/20 rounded-full blur-3xl"></div>
              <div class="absolute -left-8 -bottom-8 w-36 h-36 bg-emerald-500/20 rounded-full blur-3xl"></div>

              <div class="flex items-center justify-between mb-4">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-black uppercase tracking-wider border border-emerald-500/30">
                  <i class="fas fa-chart-pie"></i> Propuesta Económica
                </div>
                <span class="text-[11px] font-bold text-slate-400">Vircom Service Level 2026</span>
              </div>

              <!-- Precio Mensual -->
              <div class="mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Inversión Mensual Neta</span>
                <div class="flex items-baseline gap-1 mt-1">
                  <span class="text-2xl text-emerald-400 font-bold">$</span>
                  <span class="text-5xl font-black tracking-tight text-white">{{ formatNumber(totalMensual) }}</span>
                  <span class="text-xs font-bold text-slate-400">MXN + IVA</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                  <span>Con IVA (16%):</span>
                  <strong class="text-emerald-300 font-bold">${{ formatNumber(totalMensual * 1.16) }} MXN</strong>
                </div>
              </div>

              <!-- Desglose de Precios Lista vs Descuentos -->
              <div class="p-4 rounded-2xl bg-white/5 border border-white/10 space-y-2 text-xs mb-5">
                <div class="flex justify-between text-slate-300">
                  <span>Subtotal Precio de Lista:</span>
                  <strong class="text-white">${{ formatNumber(subtotalLista) }} MXN</strong>
                </div>

                <div v-if="ahorroVolumenPcs > 0" class="flex justify-between text-emerald-400 font-bold">
                  <span>Ahorro por Volumen (PCs):</span>
                  <span>-${{ formatNumber(ahorroVolumenPcs) }} MXN</span>
                </div>

                <div v-if="ahorroPaqueteIntegral > 0" class="flex justify-between text-teal-400 font-bold">
                  <span>Bonificación Paquete Integral 360° (10%):</span>
                  <span>-${{ formatNumber(ahorroPaqueteIntegral) }} MXN</span>
                </div>

                <div v-if="descuentoComercialMonto > 0" class="flex justify-between text-amber-400 font-bold">
                  <span>Descuento Comercial Negociado:</span>
                  <span>-${{ formatNumber(descuentoComercialMonto) }} MXN</span>
                </div>

                <div v-if="totalAhorrosMensuales > 0" class="pt-2 border-t border-white/10 flex justify-between text-emerald-300 font-black">
                  <span>Ahorro Total para el Cliente:</span>
                  <span>-${{ formatNumber(totalAhorrosMensuales) }} MXN/mes</span>
                </div>
              </div>

              <!-- Comparativa de Ahorro Nómina -->
              <div class="p-3.5 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 mb-5">
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs mb-1">
                  <i class="fas fa-piggy-bank"></i> Ahorro del {{ ahorroPorcentaje }}% vs Nómina de Sistemas Interna
                </div>
                <p class="text-[11px] text-slate-300 leading-relaxed">
                  Un ingeniero de sistemas interno cuesta ~$18,500/mes más IMSS, Infonavit y pasivo laboral. Con Asistencia Vircom obtienes equipo multidisciplinario, continuidad 365 días y factura 100% deducible.
                </p>
              </div>

              <!-- Pago Anual con 10% adicional de Pronto Pago -->
              <div class="space-y-2 border-t border-white/10 pt-4 text-xs">
                <div class="flex justify-between text-slate-300">
                  <span class="flex items-center gap-1.5">
                    <i class="fas fa-tag text-emerald-400"></i>
                    Opción Anual Anticipada (10% Dto):
                  </span>
                  <strong class="text-emerald-400 font-black text-sm">${{ formatNumber(totalAnual) }} MXN</strong>
                </div>
                <div class="flex justify-between text-slate-300">
                  <span class="flex items-center gap-1.5">
                    <i class="fas fa-clock text-sky-400"></i>
                    Horas estimadas de atención mensual:
                  </span>
                  <strong class="text-white font-bold">~{{ horasEstimadas }} hrs/mes</strong>
                </div>
                <div class="flex justify-between text-slate-300">
                  <span class="flex items-center gap-1.5">
                    <i class="fas fa-car-side text-indigo-400"></i>
                    Visitas en sitio garantizadas:
                  </span>
                  <strong class="text-white font-bold">{{ form.visitas_mensuales }} visita(s)/mes</strong>
                </div>
              </div>

              <!-- BOTONES DE ACCIÓN PARA EL VENDEDOR -->
              <div class="mt-6 space-y-3">
                <!-- 1. Enviar por Correo -->
                <button 
                  type="button"
                  :disabled="isSending || !form.cliente_email || !form.cliente_nombre"
                  @click="sendEmailProposal"
                  class="w-full py-3.5 px-5 rounded-2xl font-black text-xs uppercase tracking-wider text-white bg-gradient-to-r from-blue-600 to-sky-600 hover:from-blue-500 hover:to-sky-500 shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                >
                  <i v-if="isSending" class="fas fa-circle-notch fa-spin"></i>
                  <template v-else>
                    <i class="fas fa-paper-plane"></i>
                    <span>Enviar Propuesta por Correo</span>
                  </template>
                </button>

                <!-- 2. Enviar por WhatsApp Directo -->
                <a 
                  :href="whatsappUrl" 
                  target="_blank"
                  class="w-full py-3 px-5 rounded-2xl font-black text-xs uppercase tracking-wider text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 cursor-pointer no-underline"
                >
                  <i class="fab fa-whatsapp text-sm"></i>
                  <span>Enviar Resumen por WhatsApp</span>
                </a>

                <!-- 3. Guardar como Cotización en el ERP -->
                <button 
                  type="button"
                  :disabled="isSavingCotizacion || !form.cliente_nombre"
                  @click="guardarEnErp"
                  class="w-full py-2.5 px-5 rounded-xl font-bold text-xs uppercase tracking-wider text-slate-200 bg-white/10 hover:bg-white/15 border border-white/10 transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                >
                  <i v-if="isSavingCotizacion" class="fas fa-circle-notch fa-spin"></i>
                  <template v-else>
                    <i class="fas fa-save text-blue-400"></i>
                    <span>Guardar como Cotización en ERP</span>
                  </template>
                </button>

                <p v-if="!form.cliente_email" class="text-center text-[11px] text-amber-300 font-medium">
                  <i class="fas fa-info-circle mr-1"></i> Ingresa el correo para habilitar el envío oficial.
                </p>
              </div>
            </div>

            <!-- Resumen de Beneficios Incluidos Vircom -->
            <div class="bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-white/5 rounded-3xl p-5 shadow-sm text-xs space-y-2.5">
              <h4 class="font-black text-slate-800 dark:text-white uppercase tracking-wider mb-2 flex items-center gap-2">
                <i class="fas fa-check-double text-blue-500"></i>
                La Póliza Vircom Incluye:
              </h4>
              <div class="flex items-start gap-2 text-slate-600 dark:text-slate-300">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 shrink-0"></i>
                <span><strong>Soporte Remoto Ilimitado</strong> de Lunes a Sábado vía AnyDesk / RustDesk institucional.</span>
              </div>
              <div class="flex items-start gap-2 text-slate-600 dark:text-slate-300">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 shrink-0"></i>
                <span><strong>Mantenimiento Preventivo Periódico</strong> (limpieza de polvo interno, pasta térmica y optimización).</span>
              </div>
              <div class="flex items-start gap-2 text-slate-600 dark:text-slate-300">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 shrink-0"></i>
                <span><strong>Backup Diario en la Nube</strong> de archivos contables y bases de datos SQL.</span>
              </div>
              <div class="flex items-start gap-2 text-slate-600 dark:text-slate-300">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 shrink-0"></i>
                <span><strong>Antivirus y Protección Endpoint</strong> administrada centralmente.</span>
              </div>
              <div class="flex items-start gap-2 text-slate-600 dark:text-slate-300">
                <i class="fas fa-check-circle text-emerald-500 mt-0.5 shrink-0"></i>
                <span><strong>Préstamo de equipo de respaldo temporal</strong> sin costo ante fallas críticas de hardware.</span>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import axios from 'axios';

const props = defineProps({
  clientes: {
    type: Array,
    default: () => [],
  },
});

const isSending = ref(false);
const isSavingCotizacion = ref(false);
const clienteSeleccionado = ref(null);
const modoProspectoLibre = ref(false);

const form = reactive({
  cliente_id: null,
  cliente_nombre: '',
  cliente_rfc: '',
  cliente_email: '',
  cliente_telefono: '',
  computadoras: 5,
  servidores_cantidad: 0,
  tiene_servidor: false,
  tipo_red: 'ninguna', // 'ninguna', 'basica', 'empresarial'
  tiene_contpaqi: false,
  camaras: 0,
  relojes_checadores: 0,
  controles_acceso: 0,
  tiene_alarma: false,
  visitas_mensuales: 1,
  sla_tiempo: 'Menos de 45 minutos',
  descuento_comercial_tipo: 'ninguno', // 'ninguno', 'porcentaje', 'fijo'
  descuento_comercial_valor: 0,
  notas: '',
});

const onClienteSeleccionado = (cliente) => {
  clienteSeleccionado.value = cliente;
  if (cliente) {
    form.cliente_id = cliente.id;
    form.cliente_nombre = cliente.nombre_razon_social || cliente.nombre || '';
    form.cliente_rfc = cliente.rfc || '';
    form.cliente_email = cliente.email || '';
    form.cliente_telefono = cliente.telefono || '';
  } else {
    form.cliente_id = null;
    form.cliente_nombre = '';
    form.cliente_rfc = '';
    form.cliente_email = '';
    form.cliente_telefono = '';
  }
};

const changeQty = (field, delta) => {
  const next = (form[field] || 0) + delta;
  if (next >= 0) {
    form[field] = next;
  }
};

// ==========================================
// ESCALA DE PRECIOS REALES DE MERCADO MÉXICO
// ==========================================
const ladderInfo = computed(() => {
  const qty = form.computadoras || 0;
  if (qty >= 20) {
    return { precioUnitario: 180, label: 'Tarifa Corporativa', descuentoPct: 44 };
  } else if (qty >= 10) {
    return { precioUnitario: 210, label: 'Descuento por Volumen (10+)', descuentoPct: 35 };
  } else if (qty >= 5) {
    return { precioUnitario: 270, label: 'Tarifa PyME', descuentoPct: 15 };
  } else {
    return { precioUnitario: 320, label: 'Tarifa Base', descuentoPct: 0 };
  }
});

const costoComputadoras = computed(() => {
  return (form.computadoras || 0) * ladderInfo.value.precioUnitario;
});

const ahorroVolumenPcs = computed(() => {
  const precioBase = 320;
  const precioActual = ladderInfo.value.precioUnitario;
  const ahorroUnitario = Math.max(0, precioBase - precioActual);
  return (form.computadoras || 0) * ahorroUnitario;
});

const costoServidores = computed(() => {
  return (form.servidores_cantidad || 0) * 1250;
});

const costoRedes = computed(() => {
  if (form.tipo_red === 'empresarial') return 850;
  if (form.tipo_red === 'basica') return 450;
  return 0;
});

const costoContpaqi = computed(() => {
  return form.tiene_contpaqi ? 650 : 0;
});

const costoCctv = computed(() => {
  return (form.camaras || 0) > 0 ? (300 + (form.camaras * 35)) : 0;
});

const costoAlarma = computed(() => {
  return form.tiene_alarma ? 380 : 0;
});

const costoRelojes = computed(() => {
  return (form.relojes_checadores || 0) * 280;
});

const costoAccesos = computed(() => {
  return (form.controles_acceso || 0) * 320;
});

const costoVisitas = computed(() => {
  const v = form.visitas_mensuales || 1;
  if (v === 2) return 350;
  if (v === 4) return 1000;
  return 0;
});

// Base mínima operativa de mesa de ayuda Vircom
const baseMinimaOperativa = computed(() => {
  return 600;
});

// Subtotal Lista sin descuentos
const subtotalLista = computed(() => {
  const computadorasLista = (form.computadoras || 0) * 320;
  return Math.round(
    baseMinimaOperativa.value +
    computadorasLista +
    costoServidores.value +
    costoRedes.value +
    costoContpaqi.value +
    costoCctv.value +
    costoAlarma.value +
    costoRelojes.value +
    costoAccesos.value +
    costoVisitas.value
  );
});

// Subtotal intermedio considerando escala de volumen en PCs
const subtotalConVolumen = computed(() => {
  return Math.round(
    baseMinimaOperativa.value +
    costoComputadoras.value +
    costoServidores.value +
    costoRedes.value +
    costoContpaqi.value +
    costoCctv.value +
    costoAlarma.value +
    costoRelojes.value +
    costoAccesos.value +
    costoVisitas.value
  );
});

// Detección de Paquete Solución Integral 360°
const esPaqueteIntegral = computed(() => {
  const tienePcs = (form.computadoras || 0) >= 3;
  const tieneServidor = (form.servidores_cantidad || 0) >= 1;
  const tieneRed = form.tipo_red === 'basica' || form.tipo_red === 'empresarial';
  const tieneContpaqi = form.tiene_contpaqi;
  const tieneSeguridad = form.tiene_alarma || (form.camaras || 0) > 0;
  return tienePcs && tieneServidor && tieneRed && tieneContpaqi && tieneSeguridad;
});

const ahorroPaqueteIntegral = computed(() => {
  if (!esPaqueteIntegral.value) return 0;
  return Math.round(subtotalConVolumen.value * 0.10);
});

const subtotalPostPaquete = computed(() => {
  return Math.max(0, subtotalConVolumen.value - ahorroPaqueteIntegral.value);
});

// Descuento Comercial Negociable
const descuentoComercialMonto = computed(() => {
  if (form.descuento_comercial_tipo === 'porcentaje') {
    const pct = Math.min(50, Math.max(0, Number(form.descuento_comercial_valor) || 0));
    return Math.round(subtotalPostPaquete.value * (pct / 100));
  } else if (form.descuento_comercial_tipo === 'fijo') {
    return Math.min(subtotalPostPaquete.value, Math.max(0, Number(form.descuento_comercial_valor) || 0));
  }
  return 0;
});

// Total Mensual Final Neto
const totalMensual = computed(() => {
  const final = subtotalPostPaquete.value - descuentoComercialMonto.value;
  return Math.max(850, Math.round(final));
});

// Ahorros acumulados
const totalAhorrosMensuales = computed(() => {
  return Math.max(0, subtotalLista.value - totalMensual.value);
});

// Pago Anual (10% descuento por pronto pago anual)
const totalAnual = computed(() => {
  return Math.round(totalMensual.value * 12 * 0.90);
});

// Horas de soporte estimadas
const horasEstimadas = computed(() => {
  let horas = 4;
  horas += (form.computadoras || 0) * 0.8;
  horas += (form.servidores_cantidad || 0) * 4;
  if (form.tipo_red === 'empresarial') horas += 3;
  if (form.tiene_contpaqi) horas += 2.5;
  horas += (form.camaras || 0) * 0.15;
  horas += (form.controles_acceso || 0) * 0.3;
  return Math.round(horas);
});

// Porcentaje de ahorro vs nómina interna ($18,500/mes)
const ahorroPorcentaje = computed(() => {
  const costoInterno = 18500;
  const diff = costoInterno - totalMensual.value;
  return Math.max(10, Math.round((diff / costoInterno) * 100));
});

const formatNumber = (num) => {
  return (num || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// ==========================================
// HERRAMIENTAS DE DESPACHO Y CIERRE DE VENTA
// ==========================================

// Enlace de WhatsApp
const whatsappUrl = computed(() => {
  const tel = (form.cliente_telefono || '').replace(/\D/g, '');
  const destinatario = tel.length === 10 ? `52${tel}` : (tel.length === 12 ? tel : '');
  
  const mensaje = [
    `*Propuesta de Póliza TI — Asistencia Vircom* 🛡️`,
    `Hola ${form.cliente_nombre || 'Estimado Cliente'}, te comparto la cotización de póliza de soporte técnico y mantenimiento para tu empresa:`,
    ``,
    `📋 *Infraestructura y Cobertura:*`,
    form.computadoras > 0 ? `• ${form.computadoras} Computadoras/Laptops ($${ladderInfo.value.precioUnitario} MXN c/u)` : null,
    form.servidores_cantidad > 0 ? `• ${form.servidores_cantidad} Servidor(es) Dedicado(s) / SQL Server` : null,
    form.tipo_red !== 'ninguna' ? `• Red: ${form.tipo_red === 'empresarial' ? 'Empresarial / Wi-Fi Corporativo' : 'Red Básica PyME'}` : null,
    form.tiene_contpaqi ? `• Sistemas CONTPAQi / ERP (Mantenimiento SQL)` : null,
    form.camaras > 0 ? `• ${form.camaras} Cámaras de Seguridad CCTV + DVR/NVR` : null,
    form.tiene_alarma ? `• Sistema de Alarma y Sensores` : null,
    form.relojes_checadores > 0 ? `• ${form.relojes_checadores} Relojes Checadores` : null,
    form.controles_acceso > 0 ? `• ${form.controles_acceso} Controles de Acceso` : null,
    `• ${form.visitas_mensuales} Visita(s) presencial(es) en sitio al mes`,
    `• SLA de respuesta: ${form.sla_tiempo}`,
    ``,
    esPaqueteIntegral.value ? `⭐ *Paquete Solución Integral 360° Aplicado (-10%)*` : null,
    descuentoComercialMonto.value > 0 ? `🏷️ *Descuento Especial Negociado:* -$${formatNumber(descuentoComercialMonto.value)} MXN` : null,
    ``,
    `💰 *Inversión Mensual:* $${formatNumber(totalMensual.value)} MXN + IVA`,
    `⭐ *Pago Anual Anticipado (10% Dto):* $${formatNumber(totalAnual.value)} MXN + IVA`,
    ``,
    `Factura 100% deducible de impuestos. ¿Deseas que agendemos la visita inicial de diagnóstico?`,
  ].filter(line => line !== null).join('\n');

  const base = destinatario ? `https://wa.me/${destinatario}` : `https://wa.me/`;
  return `${base}?text=${encodeURIComponent(mensaje)}`;
});

// Enviar Propuesta por Correo
const sendEmailProposal = async () => {
  if (!form.cliente_email || !form.cliente_nombre) {
    alert('Por favor ingresa el nombre y correo del cliente.');
    return;
  }

  isSending.value = true;
  try {
    const payload = {
      cliente_id: form.cliente_id,
      cliente_nombre: form.cliente_nombre,
      cliente_rfc: form.cliente_rfc,
      cliente_email: form.cliente_email,
      cliente_telefono: form.cliente_telefono,
      computadoras: form.computadoras,
      precio_unitario_pc: ladderInfo.value.precioUnitario,
      servidores_cantidad: form.servidores_cantidad,
      tiene_servidor: form.servidores_cantidad > 0,
      tipo_red: form.tipo_red,
      tiene_contpaqi: form.tiene_contpaqi,
      camaras: form.camaras,
      relojes_checadores: form.relojes_checadores,
      controles_acceso: form.controles_acceso,
      tiene_alarma: form.tiene_alarma,
      visitas_mensuales: form.visitas_mensuales,
      sla_tiempo: form.sla_tiempo,
      subtotal_lista: subtotalLista.value,
      descuento_comercial_tipo: form.descuento_comercial_tipo,
      descuento_comercial_valor: form.descuento_comercial_valor,
      descuento_monto: totalAhorrosMensuales.value,
      es_paquete_integral: esPaqueteIntegral.value,
      total_mensual: totalMensual.value,
      total_anual: totalAnual.value,
      notas: form.notas,
    };

    const res = await axios.post('/api/polizas/calculator/send-email', payload);
    alert(res.data?.message || 'Propuesta enviada exitosamente por correo.');
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al enviar el correo.';
    alert(msg);
  } finally {
    isSending.value = false;
  }
};

// Guardar como Cotización en ERP
const guardarEnErp = async () => {
  if (!form.cliente_nombre) {
    alert('Por favor ingresa al menos el nombre o razón social del cliente.');
    return;
  }

  isSavingCotizacion.value = true;
  try {
    const payload = {
      cliente_id: form.cliente_id,
      cliente_nombre: form.cliente_nombre,
      cliente_rfc: form.cliente_rfc,
      cliente_email: form.cliente_email,
      cliente_telefono: form.cliente_telefono,
      computadoras: form.computadoras,
      precio_unitario_pc: ladderInfo.value.precioUnitario,
      servidores_cantidad: form.servidores_cantidad,
      tipo_red: form.tipo_red,
      tiene_contpaqi: form.tiene_contpaqi,
      camaras: form.camaras,
      relojes_checadores: form.relojes_checadores,
      controles_acceso: form.controles_acceso,
      tiene_alarma: form.tiene_alarma,
      visitas_mensuales: form.visitas_mensuales,
      sla_tiempo: form.sla_tiempo,
      subtotal_lista: subtotalLista.value,
      descuento_monto: totalAhorrosMensuales.value,
      descuento_comercial_tipo: form.descuento_comercial_tipo,
      descuento_comercial_valor: form.descuento_comercial_valor,
      es_paquete_integral: esPaqueteIntegral.value,
      total_mensual: totalMensual.value,
      total_anual: totalAnual.value,
      notas: form.notas,
    };

    const res = await axios.post('/api/polizas/calculator/guardar-cotizacion', payload);
    alert(res.data?.message || 'Cotización registrada con éxito en el ERP.');
  } catch (err) {
    const msg = err.response?.data?.message || 'Error al registrar la cotización en el ERP.';
    alert(msg);
  } finally {
    isSavingCotizacion.value = false;
  }
};

// Imprimir Propuesta
const imprimirPropuesta = () => {
  window.print();
};
</script>

<style scoped>
@media print {
  nav, header, .sticky button, a[href*="wa.me"] {
    display: none !important;
  }
  .lg\:col-span-7, .lg\:col-span-5 {
    width: 100% !important;
  }
}
</style>
