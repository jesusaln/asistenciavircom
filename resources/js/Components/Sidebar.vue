<template>
  <aside
    :class="{
      'w-64': !props.isSidebarCollapsed,
      'w-20': props.isSidebarCollapsed
    }"
    class="bg-gradient-to-b from-gray-800 to-gray-900 text-white fixed left-0 top-0 bottom-0 z-20 transition-all duration-300 ease-in-out overflow-y-auto shadow-2xl border-r border-gray-700 flex flex-col"
    role="navigation"
    aria-label="Barra lateral"
  >
    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-700 bg-gray-800/50 backdrop-blur-sm flex-shrink-0">
      <Link
        href="/panel"
        class="flex items-center group overflow-hidden"
        :class="{'justify-center w-full': props.isSidebarCollapsed}"
        :title="props.isSidebarCollapsed ? 'Ir al Panel' : null"
      >
        <img
          :src="empresaConfig?.logo_url || '/images/logo.png'"
          alt="Logo"
          class="h-10 w-auto transition-transform duration-200 group-hover:scale-105 object-contain"
          :class="{'mx-auto': props.isSidebarCollapsed}"
        />
        <span
          v-if="!props.isSidebarCollapsed"
          class="ml-3 text-xl font-semibold whitespace-nowrap overflow-hidden"
        >
        </span>
      </Link>

      <button
        v-if="!isMobile"
        @click="toggleSidebar"
        class="p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-amber-500 ml-auto"
        :title="props.isSidebarCollapsed ? 'Expandir sidebar' : 'Contraer sidebar'"
        :aria-label="props.isSidebarCollapsed ? 'Expandir sidebar' : 'Contraer sidebar'"
      >
        <FontAwesomeIcon
          :icon="props.isSidebarCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left'"
          class="text-gray-300 hover:text-white transition-colors duration-200"
        />
      </button>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 overflow-y-auto pt-4">
      <div class="px-2 pb-4">
        <!-- ========================================= -->
        <!-- 🏠 Panel Principal -->
        <!-- ========================================= -->
        <div class="mb-4">
          <NavLink href="/panel" icon="tachometer-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Panel' : null">
            Panel
          </NavLink>
        </div>

        <!-- ========================================= -->
        <!-- 🛒 CRM y Ventas -->
        <!-- ========================================= -->
        <div v-if="$can('view clientes') || $can('view citas') || $can('view cotizaciones') || $can('view pedidos') || $can('view ventas') || $can('view garantias') || $can('view crm')" class="mb-4">
          <div
            @click="toggleAccordion('ventas')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="shopping-cart" class="w-4 h-4 text-blue-400" />
              <span>CRM y Ventas</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.ventas ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.ventas }]">
            <div class="space-y-1">
              <NavLink v-if="$can('view clientes')" href="/clientes" icon="users" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Clientes' : null">
                Clientes
              </NavLink>
              <NavLink v-if="$can('view crm')" href="/crm" icon="funnel-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'CRM Prospectos' : null">
                CRM Prospectos
              </NavLink>
              <NavLink v-if="$can('view citas')" href="/citas" icon="calendar-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Citas Agendadas' : null">
                Citas Agendadas
              </NavLink>
              <NavLink href="/mi-agenda" icon="calendar-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mi Agenda' : null">
                Mi Agenda
              </NavLink>
              <NavLink v-if="$can('view citas')" href="/citas-calendario" icon="calendar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Calendario Técnicos' : null">
                Calendario Técnicos
              </NavLink>
              <NavLink v-if="$can('view cotizaciones')" href="/cotizaciones" icon="file-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cotizaciones' : null">
                Cotizaciones
              </NavLink>
              <NavLink v-if="$can('view pedidos')" href="/pedidos" icon="truck" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pedidos' : null">
                Pedidos
              </NavLink>
              <NavLink v-if="$can('view ventas')" href="/pedidos-online" icon="globe" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pedidos Web' : null">
                Pedidos Web
              </NavLink>
              <NavLink v-if="$can('view ventas')" href="/ventas" icon="dollar-sign" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ventas Realizadas' : null">
                Ventas Realizadas
              </NavLink>
              <NavLink v-if="$can('view garantias')" href="/garantias" icon="shield-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Garantías' : null">
                Garantías
              </NavLink>
            </div>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 📞 Soporte y Contratos -->
        <!-- ========================================= -->
        <div v-if="$can('view soporte') || $can('view polizas')" class="mb-4">
          <div
            @click="toggleAccordion('soporte')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="headset" class="w-4 h-4 text-orange-400" />
              <span>Soporte y Contratos</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.soporte ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.soporte }]">
            <NavLink v-if="$can('view soporte')" href="/soporte/dashboard" icon="chart-pie" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Dashboard' : null">
              Dashboard
            </NavLink>
            <NavLink v-if="$can('view soporte')" href="/soporte" icon="ticket-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Tickets' : null" :exact="true">
              Tickets
            </NavLink>
            <NavLink v-if="$can('view polizas')" href="/polizas-servicio" icon="file-signature" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pólizas de Servicio' : null">
              Pólizas de Servicio
            </NavLink>
            <NavLink v-if="$can('view polizas')" href="/planes-poliza" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Administrar Planes' : null">
              Administrar Planes
            </NavLink>
            <NavLink v-if="$can('view soporte')" href="/soporte/kb" icon="book" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Base de Conocimiento' : null">
              Base de Conocimiento
            </NavLink>
            <NavLink href="/soporte-remoto" icon="desktop" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Acceso Remoto' : null">
              Acceso Remoto
            </NavLink>
            <NavLink href="/credenciales" icon="key" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Contraseñas' : null">
              Contraseñas
            </NavLink>
            <NavLink v-if="$can('view categorias') || $can('edit soporte')" href="/soporte/categorias" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Categorías de Tickets' : null">
              Categorías de Tickets
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 📦 Compras e Inventario -->
        <!-- ========================================= -->
        <div v-if="$can('view proveedores') || $can('view ordenes_compra') || $can('view compras') || $can('view productos') || $can('view kits') || $can('view almacenes') || $can('view traspasos') || $can('view movimientos_inventario') || $can('view ajustes_inventario')" class="mb-4">
          <div
            @click="toggleAccordion('inventario')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="boxes" class="w-4 h-4 text-amber-400" />
              <span>Compras e Inventario</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.inventario ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.inventario }]">
            <!-- Compras -->
            <NavLink v-if="$can('view proveedores')" href="/proveedores" icon="truck" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Proveedores' : null">
              Proveedores
            </NavLink>
            <NavLink v-if="$can('view ordenes_compra')" href="/ordenescompra" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Órdenes de Compra' : null">
              Órdenes de Compra
            </NavLink>
            <NavLink v-if="$can('view compras')" href="/compras" icon="cart-shopping" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Compras a Proveedores' : null">
              Compras a Proveedores
            </NavLink>
            <!-- Inventario -->
            <NavLink v-if="$can('view productos')" href="/productos" icon="box" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Productos' : null">
              Productos
            </NavLink>
            <NavLink v-if="$can('view productos') && empresaConfig?.cva_active" :href="routeOr('/cva/importar')" icon="download" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Importar CVA' : null">
              Importar CVA
            </NavLink>
            <NavLink v-if="$can('view kits')" href="/kits" icon="cubes" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Kits' : null">
              Kits
            </NavLink>
            <NavLink v-if="$can('view almacenes')" href="/almacenes" icon="warehouse" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Almacenes' : null">
              Almacenes
            </NavLink>
            <NavLink v-if="$can('view traspasos')" href="/traspasos" icon="exchange-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Traspasos' : null">
              Traspasos
            </NavLink>
            <NavLink v-if="$can('view movimientos_inventario')" href="/movimientos-inventario" icon="history" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Movimientos' : null">
              Movimientos
            </NavLink>
            <NavLink v-if="$can('view ajustes_inventario')" href="/ajustes-inventario" icon="cogs" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ajustes' : null">
              Ajustes
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 💰 Finanzas -->
        <!-- ========================================= -->
        <div v-if="$can('view cuentas_bancarias') || $can('view conciliacion_bancaria') || $can('view caja_chica') || $can('view cuentas_por_pagar') || $can('view cuentas_por_cobrar') || $can('view entregas_dinero') || $can('view gastos') || $can('view traspasos_bancarios') || $can('view prestamos') || $can('view pagos')" class="mb-4">
          <div
            @click="toggleAccordion('finanzas')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="chart-line" class="w-4 h-4 text-emerald-400" />
              <span>Finanzas</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.finanzas ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.finanzas }]">
            <NavLink v-if="$can('view cuentas_bancarias')" href="/cuentas-bancarias" icon="landmark" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cuentas Bancarias' : null">
              Cuentas Bancarias
            </NavLink>
            <NavLink v-if="$can('view cuentas_por_cobrar')" href="/cuentas-por-cobrar" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cuentas por Cobrar' : null">
              Cuentas por Cobrar
            </NavLink>
            <NavLink v-if="$can('view cuentas_por_pagar')" href="/cuentas-por-pagar" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cuentas por Pagar' : null">
              Cuentas por Pagar
            </NavLink>
            <NavLink v-if="$can('view gastos')" href="/gastos" icon="receipt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gastos Operativos' : null">
              Gastos Operativos
            </NavLink>
            <NavLink v-if="$can('view traspasos_bancarios')" href="/traspasos-bancarios" icon="exchange-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Traspasos entre Cuentas' : null">
              Traspasos entre Cuentas
            </NavLink>
            <NavLink v-if="$can('view comisiones')" href="/comisiones" icon="hand-holding-usd" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Comisiones' : null">
              Comisiones
            </NavLink>
            <NavLink v-if="$can('view entregas_dinero')" href="/entregas-dinero" icon="dollar-sign" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Entregas de Dinero' : null">
              Entregas de Dinero
            </NavLink>
            <NavLink v-if="$can('view caja_chica')" href="/caja-chica" icon="wallet" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Caja Chica' : null">
              Caja Chica
            </NavLink>
            <NavLink v-if="$can('view conciliacion_bancaria')" href="/conciliacion-bancaria" icon="university" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Conciliación Bancaria' : null">
              Conciliación Bancaria
            </NavLink>
            <!-- Préstamos -->
            <NavLink v-if="$can('view prestamos')" href="/prestamos" icon="money-bill-wave" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Préstamos' : null">
              Préstamos
            </NavLink>
            <NavLink v-if="$can('view pagos')" href="/pagos" icon="credit-card" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pagos de Préstamos' : null">
              Pagos de Préstamos
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 🚗 Operaciones -->
        <!-- ========================================= -->
        <div v-if="$can('view rentas') || $can('view equipos') || $can('view vehiculos') || $can('view mantenimientos') || $can('view herramientas')" class="mb-4">
          <div
            @click="toggleAccordion('operaciones')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="tools" class="w-4 h-4 text-purple-400" />
              <span>Operaciones</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.operaciones ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.operaciones }]">
            <!-- Rentas -->
            <NavLink v-if="$can('view rentas')" href="/rentas" icon="file-contract" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Rentas PDV' : null">
              Rentas PDV
            </NavLink>
            <NavLink v-if="$can('view equipos')" href="/equipos" icon="laptop" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Equipos' : null">
              Equipos
            </NavLink>
            <!-- Vehículos y Mantenimiento -->
            <NavLink v-if="$can('view vehiculos')" href="/carros" icon="car" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Vehículos' : null">
              Vehículos
            </NavLink>
            <NavLink v-if="$can('view mantenimientos')" href="/mantenimientos" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mantenimientos' : null">
              Mantenimientos
            </NavLink>
            <!-- Herramientas -->
            <NavLink v-if="$can('view herramientas')" href="/herramientas" icon="toolbox" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Herramientas' : null">
              Herramientas
            </NavLink>
            <NavLink v-if="$can('view herramientas')" href="/herramientas/gestion" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gestión por Técnico' : null">
              Gestión por Técnico
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 👥 Recursos Humanos -->
        <!-- ========================================= -->
        <div v-if="$can('view empleados') || $can('view nominas') || $can('view vacaciones')" class="mb-4">
          <div
            @click="toggleAccordion('rrhh')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="user-tie" class="w-4 h-4 text-teal-400" />
              <span>Recursos Humanos</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.rrhh ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.rrhh }]">
            <NavLink v-if="$can('view empleados')" href="/empleados" icon="users-cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Empleados' : null">
              Empleados
            </NavLink>
            <NavLink v-if="$can('view nominas')" href="/nominas" icon="money-check-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Nóminas' : null">
              Nóminas
            </NavLink>
            <NavLink href="/mis-vacaciones" icon="umbrella-beach" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mis Vacaciones' : null">
              Mis Vacaciones
            </NavLink>
            <NavLink v-if="$can('view vacaciones')" href="/vacaciones" icon="calendar-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gestión de Vacaciones' : null">
              Gestión de Vacaciones
            </NavLink>
            <NavLink v-if="$can('create vacaciones')" href="/vacaciones/create" icon="plus-circle" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Nueva Solicitud' : null">
              Nueva Solicitud
            </NavLink>
            <NavLink v-if="$can('view vacaciones')" href="/registro-vacaciones" icon="file-signature" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Registro de Vacaciones' : null">
              Registro de Vacaciones
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- ⚙️ Configuración -->
        <!-- ========================================= -->
        <div v-if="$can('view usuarios') || $can('view roles') || $can('view bitacora') || $can('view categorias') || $can('view marcas') || $can('view servicios') || $can('manage-backups') || $can('view configuracion_empresa')" class="mb-4">
          <div
            @click="toggleAccordion('configuracion')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="cogs" class="w-4 h-4 text-gray-400" />
              <span>Configuración</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.configuracion ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.configuracion }]">
            <!-- Usuarios y Permisos -->
            <NavLink v-if="$can('view usuarios')" href="/usuarios" icon="user" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Usuarios' : null">
              Usuarios
            </NavLink>
            <NavLink v-if="$can('view roles')" href="/roles" icon="id-card" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Roles y Permisos' : null">
              Roles y Permisos
            </NavLink>
            <!-- Catálogos -->
            <NavLink v-if="$can('view categorias')" href="/categorias" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Categorías' : null">
              Categorías
            </NavLink>
            <NavLink v-if="$can('view marcas')" href="/marcas" icon="trademark" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Marcas' : null">
              Marcas
            </NavLink>
            <NavLink v-if="$can('view servicios')" href="/servicios" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Servicios' : null">
              Servicios
            </NavLink>
            <!-- Sistema -->
            <NavLink v-if="$can('view configuracion_empresa')" href="/empresa/configuracion" icon="cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Configuración de Empresa' : null">
              Configuración de Empresa
            </NavLink>
            <NavLink v-if="$can('view configuracion_empresa')" href="/empresa/landing-content" icon="palette" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Contenido de Landing' : null">
              Contenido de Landing
            </NavLink>
            <NavLink v-if="$can('view configuracion_empresa')" :href="routeOr('/admin/blog')" icon="blog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Blog' : null">
              Blog
            </NavLink>
            <NavLink v-if="$can('manage-backups')" :href="routeOr('/backup')" icon="database" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Copia de Seguridad' : null">
              Copia de Seguridad
            </NavLink>
            <NavLink v-if="$can('view bitacora')" href="/bitacora" icon="clipboard-list" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Bitácora' : null">
              Bitácora
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 📊 Reportes y CFDI -->
        <!-- ========================================= -->
        <div v-if="$can('view reportes') || $can('view finanzas') || $can('view cfdi')" class="mb-4">
          <div
            @click="toggleAccordion('reportes')"
            class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider cursor-pointer hover:text-white hover:bg-gray-700/50 rounded-md transition-colors duration-200"
          >
            <div class="flex items-center gap-2">
              <FontAwesomeIcon icon="chart-bar" class="w-4 h-4 text-indigo-400" />
              <span>Reportes y CFDI</span>
            </div>
            <FontAwesomeIcon
              icon="chevron-right"
              class="w-3 h-3 transition-transform duration-200"
              :class="accordionStates.reportes ? 'rotate-90' : ''"
            />
          </div>
          <div :class="['accordion-content', { 'accordion-open': accordionStates.reportes }]">
            <NavLink v-if="$can('view finanzas')" href="/finanzas" icon="chart-line" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Panel General' : null">
              Panel General
            </NavLink>
            <NavLink v-if="$can('view reportes')" href="/reportes" icon="chart-bar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Centro de Reportes' : null">
              Centro de Reportes
            </NavLink>
            <NavLink v-if="$can('view cfdi')" href="/cfdi" icon="file-invoice" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Documentos CFDI' : null">
              Documentos CFDI
            </NavLink>
          </div>
        </div>

        <!-- ========================================= -->
        <!-- 📁 Proyectos (Beta) -->
        <!-- ========================================= -->
        <div v-if="$can('view proyectos')" class="mb-4">
          <NavLink href="/proyectos" :icon="faFolderOpen" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Proyectos' : null">
            Proyectos (Beta)
          </NavLink>
        </div>

      </div>
    </nav>

    <!-- Usuario -->
    <div
      class="border-t border-gray-700 p-4 bg-gray-800/50 backdrop-blur-sm flex-shrink-0"
      :class="{'flex justify-center': props.isSidebarCollapsed}"
    >
      <div class="flex items-center" :class="{'w-full justify-center': props.isSidebarCollapsed, 'space-x-3': !props.isSidebarCollapsed}">
        <img
          :src="props.usuario?.profile_photo_url || 'https://ui-avatars.com/api/?name=User'"
          :alt="props.usuario?.name || 'User'"
          class="w-10 h-10 rounded-full border-2 border-gray-600 object-cover flex-shrink-0"
        />
        <div v-show="!props.isSidebarCollapsed" class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-100 truncate">
            {{ props.usuario?.name || 'Usuario' }}
          </p>
          <p class="text-xs text-gray-400 truncate">
            {{ props.usuario?.email || '' }}
          </p>
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faFolderOpen } from '@fortawesome/free-solid-svg-icons';

const page = usePage();
const empresaConfig = computed(() => page.props.empresa_config);

// Función local $can que usa usePage() reactivo (NO el $can global estático)
const auth = computed(() => page.props.auth);

const $can = (permissionOrRole) => {
  const authData = auth.value;
  if (!authData || !authData.user) return false;

  // Check if user is admin (from is_admin flag)
  if (authData.user.is_admin) return true;

  const permissions = authData.user.permissions || [];
  const roles = authData.user.roles || [];

  // Also check if user has admin or super-admin in roles array (handles both string and object formats)
  const roleNames = Array.isArray(roles) ? roles.map(r => typeof r === 'string' ? r : r.name) : [];
  if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;

  return permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole);
};

// Props
const props = defineProps({
  usuario: {
    type: Object,
    required: true
  },
  isSidebarCollapsed: {
    type: Boolean,
    default: false
  },
  isMobile: {
    type: Boolean,
    default: false
  }
});

// Computed para determinar si el usuario tiene rol de ventas (y no es admin)
const isVentasRole = computed(() => {
  if (!props.usuario || !props.usuario.roles) return false;
  const hasAdmin = props.usuario.roles.some(role => ['admin', 'super-admin'].includes(role.name)) || props.usuario.is_admin;
  const hasVentas = props.usuario.roles.some(role => role.name === 'ventas');
  return hasVentas && !hasAdmin; // Solo ventas si tiene ventas pero no admin
});

// Computed para saber si es admin
const isAdmin = computed(() => {
  if (!props.usuario) return false;
  if (props.usuario.is_admin) return true;
  if (props.usuario.roles) {
    return props.usuario.roles.some(role => ['admin', 'super-admin'].includes(role.name));
  }
  return false;
});

// Emits
const emit = defineEmits(['toggleSidebar']);

// Estado del acordeón (reorganizado)
const accordionStates = ref({
  ventas: false,
  soporte: false,
  inventario: false,
  finanzas: false,
  operaciones: false,
  rrhh: false,
  configuracion: false,
  reportes: false,
});

// Función para alternar acordeón
const toggleAccordion = (section) => {
  // Estado deseado: si está abierto, cerrar; si está cerrado, abrir
  const willBeOpen = !accordionStates.value[section];
  
  // Si el sidebar está colapsado o queremos abrir una sección
  if (props.isSidebarCollapsed || willBeOpen) {
    // Cerrar todas las demás y dejar solo esta abierta (si corresponde)
    Object.keys(accordionStates.value).forEach(key => {
      accordionStates.value[key] = key === section;
    });
  } else {
    // Si queremos cerrar una sección abierta (y no está colapsado)
    accordionStates.value[section] = false;
  }
};

// Función para determinar la sección actual basada en la URL
const getCurrentSection = () => {
  const path = window.location.pathname;

  // CRM y Ventas
  if (path.includes('/clientes') || path.includes('/citas') || path.includes('/cotizaciones') || path.includes('/pedidos') || path.includes('/ventas') || path.includes('/garantias') || path.includes('/crm') || path.includes('/mi-agenda') || path.includes('/pedidos-online')) {
    return 'ventas';
  }
  
  // Soporte y Contratos
  if (path.includes('/soporte') || path.includes('/polizas-servicio') || path.includes('/planes-poliza') || path.includes('/credenciales')) {
    return 'soporte';
  }

  // Finanzas (incluye préstamos)
  if (path.includes('/cuentas-bancarias') || path.includes('/conciliacion-bancaria') || path.includes('/caja-chica') || path.includes('/cuentas-por-pagar') || path.includes('/cuentas-por-cobrar') || path.includes('/entregas-dinero') || path.includes('/gastos') || path.includes('/traspasos-bancarios') || path.includes('/comisiones') || path.includes('/prestamos') || path.includes('/pagos')) {
    return 'finanzas';
  }

  // Compras e Inventario
  if (path.includes('/productos') || path.includes('/kits') || (path.includes('/traspasos') && !path.includes('/traspasos-bancarios')) || path.includes('/movimientos-inventario') || path.includes('/ajustes-inventario') || path.includes('/almacenes') || path.includes('/compras') || path.includes('/ordenescompra') || path.includes('/proveedores')) {
    return 'inventario';
  }
  
  // Operaciones
  if (path.includes('/rentas') || path.includes('/equipos') || path.includes('/carros') || path.includes('/mantenimientos') || path.includes('/herramientas')) {
    return 'operaciones';
  }

  // RRHH
  if (path.includes('/empleados') || path.includes('/nominas') || path.includes('/vacaciones') || path.includes('/mis-vacaciones')) {
    return 'rrhh';
  }

  // Configuración
  if (path.includes('/usuarios') || path.includes('/roles') || path.includes('/bitacora') || path.includes('/categorias') || path.includes('/marcas') || path.includes('/servicios') || path.includes('/backup') || path.includes('/empresa/configuracion') || path.includes('/empresa/landing-content') || path.includes('/admin/blog')) {
    return 'configuracion';
  }

  // Reportes
  if (path.includes('/reportes') || path.includes('/cfdi') || path === '/finanzas') {
    return 'reportes';
  }

  return null;
};

// Auto-expandir la sección actual cuando se carga la página
const autoExpandCurrentSection = () => {
  const currentSection = getCurrentSection();
  if (currentSection) {
    accordionStates.value[currentSection] = true;
  }
};

// Helper para tolerar ausencia de Ziggy route()
const routeOr = (fallback) => {
  if (typeof route === 'function') {
    try {
      if (fallback === '/cva/importar') return route('cva.import');
      if (fallback === '/admin/blog') return route('admin.blog.index');
      return route('backup.index');
    } catch (e) {
      return fallback;
    }
  }
  return fallback;
};

// Toggle sidebar
const toggleSidebar = () => {
  emit('toggleSidebar');
};

// On mount
onMounted(() => {
  autoExpandCurrentSection();
});
</script>

<style scoped>
/* Accordion animation */
.accordion-content {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease-out, opacity 0.2s ease-out;
  opacity: 0;
}

.accordion-open {
  max-height: 1000px;
  opacity: 1;
  transition: max-height 0.5s ease-in, opacity 0.3s ease-in;
}

/* Scrollbar styling */
aside::-webkit-scrollbar {
  width: 6px;
}

aside::-webkit-scrollbar-track {
  background: transparent;
}

aside::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

aside::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}
</style>
