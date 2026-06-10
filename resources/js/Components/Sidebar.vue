<template>
  <aside
    :class="{
      'w-[272px]': !props.isSidebarCollapsed,
      'w-[72px]': props.isSidebarCollapsed
    }"
    class="sidebar-root bg-[var(--ui-surface)] text-white fixed left-0 top-0 bottom-0 z-20 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] overflow-hidden shadow-[8px_0_32px_rgba(0,0,0,0.5)] border-r border-white/[0.04] flex flex-col"
    role="navigation"
    aria-label="Barra lateral"
  >
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-6 border-b border-white/[0.05] bg-white/[0.01] backdrop-blur-xl flex-shrink-0 relative overflow-hidden group">
      <!-- Animated Background Accents -->
      <div class="absolute -top-16 -left-16 w-32 h-32 bg-brand-500/[0.08] rounded-full blur-[60px] group-hover:bg-brand-500/[0.15] transition-all duration-700 animate-pulse"></div>
      <div class="absolute -bottom-10 -right-10 w-24 h-24 bg-blue-500/[0.05] rounded-full blur-[40px] animate-pulse" style="animation-delay: 1s"></div>

      <Link
        href="/panel"
        class="flex items-center group/logo overflow-hidden relative z-10 transition-transform duration-500 hover:scale-[1.02]"
        :class="{'justify-center w-full': props.isSidebarCollapsed}"
        :title="props.isSidebarCollapsed ? 'Ir al Panel' : null"
      >
        <div class="relative flex-shrink-0">
          <div class="absolute inset-0 bg-brand-500/20 blur-xl opacity-0 group-hover/logo:opacity-100 transition-opacity duration-500"></div>
          <img
            :src="empresaConfig?.logo_url || fallbackLogo"
            alt="Logo"
            class="h-10 w-auto transition-all duration-700 group-hover/logo:rotate-[360deg] group-hover/logo:scale-110 object-contain relative z-10"
            :class="{'mx-auto': props.isSidebarCollapsed}"
            @error="onLogoImgError"
          />
        </div>
        <div v-if="!props.isSidebarCollapsed" class="ml-4 flex flex-col">
          <span class="text-[14px] font-black uppercase tracking-[0.2em] text-white/95 leading-none">CLIMAS</span>
          <span class="text-[9px] font-bold uppercase tracking-[0.3em] text-brand-500/80 mt-1.5 flex items-center">
            <span class="w-1.5 h-1.5 bg-brand-500 rounded-full mr-1.5 animate-pulse"></span>
            SISTEMA ERP
          </span>
        </div>
      </Link>

      <button
        v-if="!isMobile"
        @click="toggleSidebar"
        class="p-2.5 rounded-xl bg-white/[0.03] hover:bg-white/[0.08] border border-white/[0.05] hover:border-white/[0.15] transition-all duration-500 focus:outline-none focus:ring-1 focus:ring-brand-500/40 ml-auto group/btn relative z-10 overflow-hidden"
        :title="props.isSidebarCollapsed ? 'Expandir' : 'Contraer'"
      >
        <div class="absolute inset-0 bg-gradient-to-tr from-brand-500/10 to-transparent opacity-0 group-hover/btn:opacity-100 transition-opacity duration-500"></div>
        <FontAwesomeIcon
          :icon="props.isSidebarCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left'"
          class="text-white/60 group-hover/btn:text-white transition-all duration-300 text-[11px] relative z-10"
          :class="{'rotate-180': props.isSidebarCollapsed}"
        />
      </button>
    </div>

    <!-- Search / Quick Nav -->
    <div v-if="!props.isSidebarCollapsed" class="px-4 py-3 border-b border-[var(--ui-border)] bg-[var(--ui-surface-soft)]/30">
      <div class="relative group/search">
        <FontAwesomeIcon :icon="faSearch" class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--ui-text-soft)] group-focus-within/search:text-brand-500 transition-colors text-xs" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Buscar en el menú…"
          title="Al escribir se muestran coincidencias y se abren todas las secciones"
          class="w-full rounded-xl border border-[var(--ui-border)] bg-[var(--ui-surface)] py-2 pl-9 pr-4 text-[11px] font-medium text-[var(--ui-text)] placeholder:text-[var(--ui-text-soft)] transition-all duration-300 focus:border-brand-500/50 focus:bg-[var(--ui-surface)] focus:outline-none focus:ring-1 focus:ring-brand-500/20"
        >
      </div>
    </div>

    <!-- Navegación -->
    <nav ref="navScrollRef" class="flex-1 overflow-y-auto sidebar-scroll">
      <div class="px-3 py-4 space-y-1">

        <!-- 🏠 Panel Principal -->
        <div class="mb-3" v-if="matchesSearch('panel principal dashboard inicio')">
          <NavLink href="/panel" icon="tachometer-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Panel' : null">
            Panel
          </NavLink>
        </div>


        <!-- ⭐ Favoritos (accesos rápidos) -->
        <div
          v-if="(canAccessWhatsAppInbox || $can('view citas') || $can('view ventas') || true) && matchesSearch('favoritos inbox whatsapp bandeja mensajes agendar cita citas ventas programar nueva pendientes to do todo tareas')"
          class="mb-4 rounded-xl border border-brand-500/15 bg-gradient-to-b from-brand-500/[0.06] to-transparent p-2 shadow-[inset_0_1px_0_rgba(251,191,36,0.08)]"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-2 px-2 pt-0.5">
            <span class="text-[8px] font-black uppercase tracking-[0.2em] text-brand-400/90">Favoritos</span>
            <span class="mt-0.5 block text-[8px] font-medium normal-case tracking-normal text-white/35">Accesos que usas cada día</span>
          </div>
          <div class="space-y-1">
            <NavLink
              v-if="canAccessWhatsAppInbox && matchesSearch('whatsapp inbox bandeja mensajes marketing')"
              href="/marketing/whatsapp-inbox"
              target="_blank"
              icon="comments"
              :collapsed="props.isSidebarCollapsed"
              :title="props.isSidebarCollapsed ? 'WhatsApp Inbox' : null"
            >
              WhatsApp Inbox
            </NavLink>


            <NavLink
              v-if="$can('view citas') && matchesSearch('citas agendadas programadas historial')"
              href="/citas"
              icon="calendar-alt"
              :collapsed="props.isSidebarCollapsed"
              :title="props.isSidebarCollapsed ? 'Citas Agendadas' : null"
            >
              Citas Agendadas
            </NavLink>
            <NavLink
              v-if="$can('view ventas') && matchesSearch('ventas realizadas facturación cobros historial')"
              href="/ventas"
              icon="dollar-sign"
              :collapsed="props.isSidebarCollapsed"
              :title="props.isSidebarCollapsed ? 'Ventas' : null"
            >
              Ventas
            </NavLink>
            <NavLink
              v-if="$can('view bitacora') || true"
              href="/mis-pendientes"
              icon="list-check"
              :collapsed="props.isSidebarCollapsed"
              :title="props.isSidebarCollapsed ? 'Tareas' : null"
            >
              Tareas
            </NavLink>
          </div>
        </div>


        <!-- —— Comercial: ventas, marketing y relación con clientes —— -->
        <div
          class="sidebar-accordion-group mb-6 transition-all duration-300"
          :class="searchQuery.trim()
            ? 'border-0 bg-transparent p-0 shadow-none'
            : 'rounded-[2rem] border border-brand-500/10 bg-gradient-to-br from-brand-500/[0.05] via-[var(--ui-surface-soft)] to-transparent p-2.5 shadow-xl shadow-brand-500/5'"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-3 px-3 pt-1">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-brand-500/80">Comercial</span>
            <span class="mt-1 block text-[8px] font-bold normal-case tracking-normal text-[var(--ui-text-soft)] opacity-60">Clientes, ventas y campañas</span>
          </div>

        <!-- 👥 CRM y clientes -->
        <SidebarSection
          v-if="($can('view clientes') || $can('view crm')) && matchesSearch('crm clientes prospectos embudo funnel oportunidades pipeline contactos cartera mirage')"
          title="CRM y clientes"
          subtitle="Cartera y prospectos"
          icon="address-book"
          iconColor="text-violet-400"
          :isOpen="accordionStates.crm"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('crm')"
        >
          <NavLink v-if="$can('view clientes')" href="/clientes" icon="users" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Clientes' : null">Clientes</NavLink>
          <NavLink v-if="$can('view crm')" href="/crm" icon="funnel-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'CRM Prospectos' : null">CRM Prospectos</NavLink>
          <NavLink v-if="$can('view crm')" href="/crm/tareas" icon="tasks" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Tareas CRM' : null">Tareas CRM</NavLink>
        </SidebarSection>

        <!-- 🛒 Ventas y facturación -->
        <SidebarSection
          v-if="($can('view citas') || $can('view cotizaciones') || $can('view pedidos') || $can('view ventas') || $can('view garantias')) && matchesSearch('ventas citas agenda cotizaciones pedidos facturación facturacion caja pos garantías garantias calendario técnicos pedidos web pedidos online realizadas')"
          title="Ventas y facturación"
          subtitle="Citas, pedidos, caja y facturas"
          icon="shopping-cart"
          iconColor="text-blue-400"
          :isOpen="accordionStates.ventas"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('ventas')"
        >
          <NavLink v-if="$can('view citas')" href="/citas" icon="calendar-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Citas Agendadas' : null">Citas Agendadas</NavLink>
          <NavLink href="/mi-agenda" icon="calendar-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mi Agenda' : null">Mi Agenda</NavLink>
          <NavLink v-if="$can('view citas')" href="/citas/calendario" icon="calendar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Calendario Técnicos' : null">Calendario Técnicos</NavLink>
          <NavLink v-if="$can('view cotizaciones')" href="/cotizaciones" icon="file-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cotizaciones' : null">Cotizaciones</NavLink>
          <NavLink v-if="$can('view pedidos')" href="/pedidos" icon="truck" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pedidos' : null">Pedidos</NavLink>
          <NavLink v-if="$can('view ventas')" href="/pedidos-online" icon="globe" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pedidos Web' : null">Pedidos Web</NavLink>
          <NavLink v-if="$can('view ventas')" href="/ventas" icon="dollar-sign" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ventas' : null">Ventas</NavLink>
          <NavLink v-if="$can('view ventas')" href="/pos" icon="cash-register" target="_blank" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Caja POS' : null">Caja POS</NavLink>
          <NavLink v-if="$can('view ventas')" href="/facturas" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Facturación' : null">Facturación</NavLink>
          <NavLink v-if="$can('view garantias')" href="/garantias" icon="shield-halved" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Garantías' : null">Garantías</NavLink>
        </SidebarSection>
        
        <!-- Mirage Integration -->
        <SidebarSection
          v-if="isAdmin || $can('view citas')"
          title="Mirage Postventa"
          subtitle="Sincronización y solicitudes"
          icon="robot"
          iconColor="text-red-500"
          :isOpen="accordionStates.mirage"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('mirage')"
        >
          <NavLink href="/mirage/solicitudes" icon="list-ul" :collapsed="props.isSidebarCollapsed">Ver Solicitudes</NavLink>
        </SidebarSection>

        <!-- 📣 Marketing y Campañas -->
        <SidebarSection
          v-if="($can('view marketing') || isAdmin) && matchesSearch('marketing digital campañas audiencias plantillas whatsapp sms masivos redes sociales social')"
          title="Marketing Digital"
          subtitle="WhatsApp Business, SMS y masivos"
          icon="bullhorn"
          iconColor="text-rose-400"
          :isOpen="accordionStates.marketing"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('marketing')"
        >
          <NavLink href="/marketing/campanias" icon="paper-plane" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Campañas' : null">Campañas</NavLink>
          <NavLink href="/marketing/audiencias" icon="users" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Audiencias' : null">Audiencias</NavLink>
          <NavLink href="/marketing/plantillas" icon="file-signature" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Plantillas' : null">Plantillas</NavLink>
          <NavLink href="/marketing/social-posts" icon="share-nodes" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Redes Sociales' : null">Redes Sociales</NavLink>
        </SidebarSection>
        </div>

        <!-- —— Operaciones: soporte, compras, stock, finanzas, campo —— -->
        <div
          class="sidebar-accordion-group mb-6 transition-all duration-300"
          :class="searchQuery.trim()
            ? 'border-0 bg-transparent p-0 shadow-none'
            : 'rounded-[2rem] border border-sky-500/10 bg-gradient-to-br from-sky-500/[0.05] via-[var(--ui-surface-soft)] to-transparent p-2.5 shadow-xl shadow-sky-500/5'"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-3 px-3 pt-1">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-sky-400/90">Operaciones</span>
            <span class="mt-1 block text-[8px] font-bold normal-case tracking-normal text-[var(--ui-text-soft)] opacity-60">Soporte, compras, inventario, tesorería y campo</span>
          </div>

        <!-- 📞 Mesa de ayuda (tickets, KB, acceso) -->
        <SidebarSection
          v-if="($can('view soporte') || $can('view polizas')) && matchesSearch('tickets soporte mesa ayuda dashboard kb conocimiento base categoría categorias remoto escritorio credencial contraseña contraseñas ticket')"
          title="Mesa de ayuda"
          subtitle="Tickets, conocimiento y acceso"
          icon="headset"
          iconColor="text-orange-400"
          :isOpen="accordionStates.tickets"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('tickets')"
        >
          <NavLink v-if="$can('view soporte')" href="/soporte/dashboard" icon="chart-pie" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Dashboard' : null">Dashboard</NavLink>
          <NavLink v-if="$can('view soporte')" href="/soporte" icon="ticket-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Tickets' : null" :exact="true">Tickets</NavLink>
          <NavLink v-if="$can('view soporte')" href="/soporte/kb" icon="book" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Base de Conocimiento' : null">Base de Conocimiento</NavLink>
          <NavLink v-if="$can('view categorias') || $can('edit soporte')" href="/soporte/categorias" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Categorías de Tickets' : null">Categorías de Tickets</NavLink>
          <NavLink href="/soporte-remoto" icon="desktop" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Acceso Remoto' : null">Acceso Remoto</NavLink>
          <NavLink href="/credenciales" icon="key" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Contraseñas' : null">Contraseñas</NavLink>
        </SidebarSection>

        <!-- 📄 Pólizas y planes de servicio -->
        <!-- 📄 Pólizas, Flota y Técnicos -->
        <SidebarSection
          v-if="matchesSearch('póliza poliza contrato servicio planes plan mantenimiento pólizas polizas técnico tecnico tecnicos flota gps')"
          title="Pólizas y Flota"
          subtitle="Contratos, técnicos en campo y GPS"
          icon="file-signature"
          iconColor="text-brand-500"
          :isOpen="accordionStates.polizas"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('polizas')"
        >
          <NavLink href="/tecnicos" icon="user-cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Técnicos y GPS' : null">Técnicos y GPS</NavLink>
          <NavLink v-if="isSuperAdmin" href="/tracking" icon="map-location-dot" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Tracker en Vivo' : null">Tracker en Vivo</NavLink>
          <NavLink href="/polizas-servicio" icon="file-signature" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pólizas de Servicio' : null">Pólizas de Servicio</NavLink>
          <NavLink href="/tecnico/mantenimientos" icon="tools" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mantenimientos de Pólizas' : null">Mantenimientos de Pólizas</NavLink>
          <NavLink href="/planes-poliza" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Administrar Planes' : null">Administrar Planes</NavLink>
        </SidebarSection>

        <!-- 🛒 Compras a proveedores -->
        <SidebarSection
          v-if="($can('view proveedores') || $can('view ordenes_compra') || $can('view compras')) && matchesSearch('compras proveedores proveedor orden órdenes ordenes oc compra factura recepción')"
          title="Compras"
          subtitle="Proveedores y órdenes de compra"
          icon="cart-shopping"
          iconColor="text-lime-400"
          :isOpen="accordionStates.compras"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('compras')"
        >
          <NavLink v-if="$can('view proveedores')" href="/proveedores" icon="truck" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Proveedores' : null">Proveedores</NavLink>
          <NavLink v-if="$can('view ordenes_compra')" href="/ordenescompra" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Órdenes de Compra' : null">Órdenes de Compra</NavLink>
          <NavLink v-if="$can('view compras')" href="/compras" icon="cart-shopping" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Compras a Proveedores' : null">Compras a Proveedores</NavLink>
        </SidebarSection>

        <!-- 📦 Inventario y almacén -->
        <SidebarSection
          v-if="($can('view productos') || $can('view kits') || $can('view almacenes') || $can('view traspasos') || $can('view movimientos_inventario') || $can('view ajustes_inventario')) && matchesSearch('inventario almacén almacen stock productos kits traspasos movimientos ajustes cva importar catálogo catalogo existencias')"
          title="Inventario y almacén"
          subtitle="Productos, ubicaciones y movimientos"
          icon="boxes"
          iconColor="text-amber-400"
          :isOpen="accordionStates.inventario"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('inventario')"
        >
          <NavLink v-if="$can('view productos')" href="/productos" icon="box" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Productos' : null">Productos</NavLink>
          <NavLink v-if="$can('view productos') && empresaConfig?.cva_active" :href="routeOr('/cva/importar')" icon="download" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Importar CVA' : null">Importar CVA</NavLink>
          <NavLink v-if="$can('view kits')" href="/kits" icon="cubes" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Kits' : null">Kits</NavLink>
          <NavLink v-if="$can('view almacenes')" href="/almacenes" icon="warehouse" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Almacenes' : null">Almacenes</NavLink>
          <NavLink v-if="$can('view traspasos')" href="/traspasos" icon="exchange-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Traspasos' : null">Traspasos</NavLink>
          <NavLink v-if="$can('view movimientos_inventario')" href="/movimientos-inventario" icon="clock-rotate-left" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Movimientos' : null">Movimientos</NavLink>
          <NavLink v-if="$can('view ajustes_inventario')" href="/ajustes-inventario" icon="cogs" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ajustes' : null">Ajustes</NavLink>
          <NavLink v-if="$can('view ajustes_inventario')" href="/inventarios-fisicos" icon="clipboard-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Inventario Físico' : null">Inventario Físico</NavLink>
          <NavLink v-if="true" href="/admin/solicitudes-material" icon="clipboard-list" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Solicitudes' : null">Solicitudes Material</NavLink>
          <NavLink :href="route('reportes-inventario.rotacion')" icon="arrows-rotate" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Rotación de Inventario' : null">Rotación de Inventario</NavLink>
        </SidebarSection>

        <!-- 🏦 Tesorería y bancos -->
        <SidebarSection
          v-if="($can('contador') || $can('view cuentas_bancarias') || $can('view conciliacion_bancaria') || $can('view caja_chica') || $can('view entregas_dinero') || $can('view traspasos_bancarios')) && matchesSearch('tesorería tesoreria banco bancaria conciliación conciliacion traspaso cuenta caja chica entrega efectivo')"
          title="Bancos"
          subtitle="Cuentas, conciliación y efectivo"
          icon="landmark"
          iconColor="text-emerald-400"
          :isOpen="accordionStates.tesoreria"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('tesoreria')"
        >
          <NavLink href="/bancos" icon="wallet" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Bancos' : null">Bancos</NavLink>
          <NavLink href="/entregas-dinero" icon="money-bill-wave" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Entregas Dinero' : null">Entregas Dinero</NavLink>
          <NavLink href="/contabilidad/saldos-xml" icon="university" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Conciliación Bancaria' : null">Conciliación Bancaria</NavLink>
        </SidebarSection>

        <!-- 🧾 Contabilidad automatizada -->
        <SidebarSection
          v-if="($can('contador') || $can('finanzas') || $can('compras') || $can('cajero') || $can('view cfdi') || isAdmin) && matchesSearch('contabilidad pólizas polizas xml catálogo catalogo sat asientos contables reportes balanza iva estado resultados cfdi documentos fiscales comprobantes')"
          title="Contabilidad"
          subtitle="Pólizas, XML, CFDI y reportes"
          icon="calculator"
          iconColor="text-brand-500"
          :isOpen="accordionStates.contabilidad"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('contabilidad')"
        >
          <NavLink v-if="$can('view cfdi')" href="/cfdi" icon="file-invoice" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Documentos CFDI' : null">Documentos CFDI</NavLink>
          <NavLink href="/contabilidad/polizas" icon="file-invoice" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pólizas y XML' : null">Pólizas y XML</NavLink>
          <NavLink href="/contabilidad/catalogo" icon="list-ul" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Catálogo de Cuentas' : null">Catálogo de Cuentas</NavLink>
          <NavLink :href="route('contabilidad.reportes.balanza')" icon="balance-scale" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Balanza de Comprobación' : null">Balanza de Comprobación</NavLink>
          <NavLink :href="route('contabilidad.reportes.estado-resultados')" icon="chart-pie" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Estado de Resultados' : null">Estado de Resultados</NavLink>
          <NavLink href="/contabilidad/reportes/iva-mensual" icon="hand-holding-usd" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Reporte de IVA' : null">Reporte de IVA</NavLink>
          <NavLink :href="route('contabilidad.reportes.balance-general')" icon="scale-balanced" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Balance General' : null">Balance General</NavLink>
          <NavLink :href="route('contabilidad.reportes.flujo-efectivo')" icon="money-bill-trend-up" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Flujo de Efectivo' : null">Flujo de Efectivo</NavLink>
        </SidebarSection>
        <!-- 💳 Cuentas por cobrar/pagar y gastos -->
        <SidebarSection
          v-if="($can('contador') || $can('view cuentas_por_pagar') || $can('view cuentas_por_cobrar') || $can('view gastos') || $can('view comisiones') || $can('view prestamos') || $can('view pagos')) && matchesSearch('cxc cxp cobrar pagar cuentas gastos comisión comisiones préstamos prestamos abonos obligaciones finanzas contabilidad')"
          title="Cuentas y gastos"
          subtitle="Por cobrar, por pagar, comisiones y préstamos"
          icon="file-invoice-dollar"
          iconColor="text-teal-400"
          :isOpen="accordionStates.cuentas"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('cuentas')"
        >
          <NavLink v-if="$can('view cuentas_por_cobrar')" href="/cuentas-por-cobrar" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cuentas por Cobrar' : null">Cuentas por Cobrar</NavLink>
          <NavLink v-if="$can('view cuentas_por_pagar')" href="/cuentas-por-pagar" icon="file-invoice-dollar" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Cuentas por Pagar' : null">Cuentas por Pagar</NavLink>
          <NavLink v-if="$can('view gastos')" href="/gastos" icon="receipt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gastos Operativos' : null">Gastos Operativos</NavLink>
          <NavLink v-if="$can('view comisiones')" href="/comisiones" icon="hand-holding-usd" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Comisiones' : null">Comisiones</NavLink>
          <NavLink v-if="$can('view prestamos')" href="/prestamos" icon="money-bill-wave" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Préstamos' : null">Préstamos</NavLink>
          <NavLink v-if="$can('view pagos')" href="/pagos" icon="credit-card" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Pagos de Préstamos' : null">Pagos de Préstamos</NavLink>
        </SidebarSection>

        <!-- 🚗 Rentas, equipos y flota -->
        <SidebarSection
          v-if="($can('view rentas') || $can('view equipos') || $can('view vehiculos')) && matchesSearch('rentas pdv punto venta plan equipo laptop vehículo vehiculo carro flota unidad móvil movil')"
          title="Rentas y flota"
          subtitle="PDV, equipos y vehículos"
          icon="car"
          iconColor="text-purple-400"
          :isOpen="accordionStates.rentas_flota"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('rentas_flota')"
        >
          <NavLink v-if="$can('view rentas')" href="/rentas" icon="file-contract" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Rentas PDV' : null">Rentas PDV</NavLink>
          <NavLink v-if="$can('view rentas')" href="/planes-renta" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Planes de Renta' : null">Planes de Renta</NavLink>
          <NavLink v-if="$can('view equipos')" href="/equipos" icon="laptop" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Equipos' : null">Equipos</NavLink>
          <NavLink v-if="$can('view vehiculos')" href="/carros" icon="car" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Vehículos' : null">Vehículos</NavLink>
        </SidebarSection>

        <!-- 🔧 Taller y herramientas -->
        <SidebarSection
          v-if="($can('view mantenimientos') || $can('view herramientas')) && matchesSearch('taller mantenimiento mantenimientos herramientas caja herramienta técnico gestión campo servicio')"
          title="Taller y herramientas"
          subtitle="Mantenimientos y control por técnico"
          icon="wrench"
          iconColor="text-fuchsia-400"
          :isOpen="accordionStates.taller"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('taller')"
        >
          <NavLink v-if="$can('view bitacora')" href="/bitacora" icon="tasks" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Bitácora de Tareas' : null">Bitácora de Tareas</NavLink>
          <NavLink v-if="$can('view mantenimientos')" href="/mantenimientos" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mantenimientos' : null">Mantenimientos</NavLink>
          <NavLink href="/taller" icon="clipboard-list" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Reparaciones' : null">Reparaciones (Taller)</NavLink>
          <NavLink v-if="$can('view herramientas')" href="/herramientas" icon="toolbox" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Herramientas' : null">Herramientas</NavLink>
          <NavLink v-if="$can('view herramientas')" href="/herramientas/historial" icon="clock-rotate-left" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Historial de Movimientos' : null">Historial de Movimientos</NavLink>
          <NavLink v-if="$can('view herramientas')" href="/herramientas/gestion" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gestión por Técnico' : null">Gestión por Técnico</NavLink>
        </SidebarSection>
        </div>

        <!-- —— Equipo: RRHH y asistencia —— -->
        <div
          class="sidebar-accordion-group mb-6 transition-all duration-300"
          :class="searchQuery.trim()
            ? 'border-0 bg-transparent p-0 shadow-none'
            : 'rounded-[2rem] border border-teal-500/10 bg-gradient-to-br from-teal-500/[0.05] via-[var(--ui-surface-soft)] to-transparent p-2.5 shadow-xl shadow-teal-500/5'"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-3 px-3 pt-1">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-teal-400/90">Equipo</span>
            <span class="mt-1 block text-[8px] font-bold normal-case tracking-normal text-[var(--ui-text-soft)] opacity-60">Asistencia, nómina y vacaciones</span>
          </div>

        <!-- ⏱ Asistencia y checador -->
        <SidebarSection
          v-if="matchesSearch('asistencia checador reloj horario entrada salida bitácora asistencia registros control tiempo')"
          title="Asistencia"
          subtitle="Checador y registros"
          icon="clock"
          iconColor="text-teal-400"
          :isOpen="accordionStates.asistencia"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('asistencia')"
        >
          <NavLink href="/asistencia" icon="clock" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Reloj Checador' : null">Reloj Checador</NavLink>
          <NavLink v-if="isAdmin || $can('view empleados')" href="/asistencia/registros" icon="clock-rotate-left" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Bitácora de Asistencia' : null">Bitácora de Asistencia</NavLink>
        </SidebarSection>

        <!-- 👥 Empleados, nómina y vacaciones -->
        <SidebarSection
          v-if="matchesSearch('empleados nómina nominas vacaciones solicitud personal rrhh talento humano registro ausencia permiso nom035 nom-035')"
          title="Empleados y vacaciones"
          subtitle="Nómina, permisos y solicitudes"
          icon="user-tie"
          iconColor="text-cyan-400"
          :isOpen="accordionStates.empleados"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('empleados')"
        >
          <NavLink v-if="$can('view empleados')" href="/empleados" icon="users-cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Empleados' : null">Empleados</NavLink>
          <NavLink v-if="$can('view nominas')" href="/nominas" icon="money-check-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Nóminas' : null">Nóminas</NavLink>
          <NavLink href="/mis-vacaciones" icon="umbrella-beach" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Mis Vacaciones' : null">Mis Vacaciones</NavLink>
          <NavLink v-if="$can('view vacaciones')" href="/vacaciones" icon="calendar-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Gestión de Vacaciones' : null">Gestión de Vacaciones</NavLink>
          <NavLink v-if="$can('create vacaciones')" href="/vacaciones/create" icon="plus-circle" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Nueva Solicitud' : null">Nueva Solicitud</NavLink>
          <NavLink v-if="$can('view vacaciones')" href="/registro-vacaciones" icon="file-signature" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Registro de Vacaciones' : null">Registro de Vacaciones</NavLink>
        </SidebarSection>
        </div>
        
        <!-- —— Cumplimiento: comisiones, legal y normas —— -->
        <div
          class="sidebar-accordion-group mb-6 transition-all duration-300"
          :class="searchQuery.trim()
            ? 'border-0 bg-transparent p-0 shadow-none'
            : 'rounded-[2rem] border border-rose-500/10 bg-gradient-to-br from-rose-500/[0.05] via-[var(--ui-surface-soft)] to-transparent p-2.5 shadow-xl shadow-rose-500/5'"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-3 px-3 pt-1">
            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-rose-400/90">Cumplimiento Legal</span>
            <span class="mt-1 block text-[8px] font-bold normal-case tracking-normal text-[var(--ui-text-soft)] opacity-60">Comisiones, normas STPS y REPSE</span>
          </div>

          <!-- 🛡️ Seguridad e Higiene (NOM-019) -->
          <SidebarSection
            v-if="matchesSearch('nom019 nom-019 seguridad higiene comisión comision recorridos actas equipo protección')"
            title="Seguridad e Higiene"
            subtitle="NOM-019: Comisiones y Actas"
            icon="shield-halved"
            iconColor="text-rose-400"
            :isOpen="accordionStates.comisiones_nom019"
            :collapsed="props.isSidebarCollapsed"
            @toggle="toggleAccordion('comisiones_nom019')"
          >
            <NavLink href="/comisiones/seguridad-higiene" icon="file-signature" :collapsed="props.isSidebarCollapsed">Actas de Comisión</NavLink>
            <NavLink href="/comisiones/recorridos" icon="clipboard-check" :collapsed="props.isSidebarCollapsed">Recorridos Mensuales</NavLink>
          </SidebarSection>

          <!-- 🧠 Riesgos Psicosociales (NOM-035) -->
          <SidebarSection
            v-if="matchesSearch('nom035 nom-035 estrés estres riesgo psicosocial política politica evaluación resultados')"
            title="Riesgos Psicosociales"
            subtitle="NOM-035: Evaluaciones y Clima"
            icon="brain"
            iconColor="text-indigo-400"
            :isOpen="accordionStates.comisiones_nom035"
            :collapsed="props.isSidebarCollapsed"
            @toggle="toggleAccordion('comisiones_nom035')"
          >
            <NavLink href="/nom035" icon="chart-bar" :collapsed="props.isSidebarCollapsed">Panel de Riesgo</NavLink>
            <NavLink href="/nom035/denuncias" icon="inbox" :collapsed="props.isSidebarCollapsed">Buzón de Denuncias</NavLink>
            <NavLink href="/nom035/actividades" icon="tasks" :collapsed="props.isSidebarCollapsed">Acciones y Control</NavLink>
            <NavLink href="/nom035/config" icon="cog" :collapsed="props.isSidebarCollapsed">Configuración Política</NavLink>
            <NavLink href="/nom035/config/matrix" icon="list-ol" :collapsed="props.isSidebarCollapsed">Matriz de Firmas</NavLink>
          </SidebarSection>

          <!-- 📄 Subcontratación (REPSE) -->
          <SidebarSection
            v-if="matchesSearch('repse subcontratación contratistas proveedores documentos vencimientos')"
            title="REPSE y Contratistas"
            subtitle="Vigilancia de proveedores"
            icon="file-contract"
            iconColor="text-emerald-400"
            :isOpen="accordionStates.comisiones_repse"
            :collapsed="props.isSidebarCollapsed"
            @toggle="toggleAccordion('comisiones_repse')"
          >
            <NavLink href="/comisiones/repse" icon="truck-loading" :collapsed="props.isSidebarCollapsed">Documentación REPSE</NavLink>
            <NavLink href="/comisiones/vencimientos" icon="calendar-xmark" :collapsed="props.isSidebarCollapsed">Vencimientos Próximos</NavLink>
          </SidebarSection>

          <!-- 💓 Clima Laboral (Pulse) -->
          <SidebarSection
            v-if="matchesSearch('pulse encuestas clima laboral satisfacción pulso felicidad equipo')"
            title="Pulso del Equipo"
            subtitle="Encuestas rápidas mensuales"
            icon="heart-pulse"
            iconColor="text-rose-500"
            :isOpen="accordionStates.comisiones_pulse"
            :collapsed="props.isSidebarCollapsed"
            @toggle="toggleAccordion('comisiones_pulse')"
          >
            <NavLink href="/comisiones/pulse" icon="poll" :collapsed="props.isSidebarCollapsed">Ver Resultados</NavLink>
            <NavLink href="/comisiones/pulse/config" icon="cog" :collapsed="props.isSidebarCollapsed">Configurar Preguntas</NavLink>
          </SidebarSection>
        </div>

        <!-- —— Administración: blog, ajustes y reportes —— -->
        <div
          class="sidebar-accordion-group mb-2 transition-all duration-300"
          :class="searchQuery.trim()
            ? 'border-0 bg-transparent p-0 shadow-none'
            : 'rounded-xl border border-[var(--ui-border)] bg-[var(--ui-surface-soft)] p-2'"
        >
          <div v-if="!props.isSidebarCollapsed && !searchQuery.trim()" class="mb-2 px-2 pt-0.5">
            <span class="text-[8px] font-black uppercase tracking-[0.2em] text-indigo-300/90">Administración</span>
            <span class="mt-0.5 block text-[8px] font-medium normal-case tracking-normal text-white/35">Blog, seguridad, catálogos e informes</span>
          </div>

        <!-- 📝 Blog -->
        <SidebarSection
          v-if="($can('view configuracion_empresa') || isAdmin) && matchesSearch('blog contenido público publico administrador landing artículos')"
          title="Blog"
          subtitle="Contenido público y administración"
          icon="blog"
          iconColor="text-sky-400"
          :isOpen="accordionStates.blog"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('blog')"
        >
          <NavLink :href="routeOr('/admin/blog')" icon="cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Administrador de Blog' : null">Administrador de Blog</NavLink>
          <NavLink href="/blog" icon="globe" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ver Blog Público' : null">Ver Blog Público</NavLink>
        </SidebarSection>

        <!-- 🔐 Seguridad y auditoría -->
        <SidebarSection
          v-if="($can('view usuarios') || $can('view roles') || $can('view bitacora') || $can('manage-backups')) && matchesSearch('usuarios roles permisos seguridad auditoría auditoria bitácora bitacora respaldo backup base datos acceso administrador')"
          title="Seguridad y auditoría"
          subtitle="Usuarios, roles y respaldos"
          icon="shield-halved"
          iconColor="text-slate-400"
          :isOpen="accordionStates.seguridad"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('seguridad')"
        >
          <NavLink v-if="$can('view usuarios')" href="/usuarios" icon="user" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Usuarios' : null">Usuarios</NavLink>
          <NavLink v-if="$can('view roles')" href="/roles" icon="id-card" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Roles y Permisos' : null">Roles y Permisos</NavLink>
          <NavLink v-if="$can('manage-backups')" :href="routeOr('/backup')" icon="database" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Copia de Seguridad' : null">Copia de Seguridad</NavLink>
          <div v-if="$can('manage-backups')" class="px-3.5 py-2 mt-1 rounded-xl bg-white/[0.03] border border-white/[0.05] flex items-center justify-between group/vps" :class="{'justify-center': props.isSidebarCollapsed}">
             <div v-if="!props.isSidebarCollapsed" class="flex flex-col min-w-0">
                <span class="text-[9px] font-black uppercase tracking-wide text-white/80">Respaldo VPS</span>
                <span class="text-[8px] text-white/40 truncate">{{ vpsStatus.last_backup }}</span>
             </div>
             <div class="flex items-center gap-1.5 flex-shrink-0">
                <div v-if="vpsStatus.status === 'success'" class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]" title="Respaldo OK"></div>
                <div v-else-if="vpsStatus.status === 'error'" class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.5)] animate-pulse" title="Respaldo Fallido"></div>
                <div v-else class="w-2 h-2 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(245,158,11,0.5)] animate-pulse" title="Pendiente"></div>
                <span v-if="!props.isSidebarCollapsed && vpsStatus.size" class="text-[8px] font-bold text-white/60">{{ vpsStatus.size }}</span>
             </div>
          </div>
          <NavLink v-if="isAdmin" href="/dispositivos" icon="mobile-alt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Dispositivos' : null">Dispositivos App</NavLink>
          <NavLink v-if="$can('view bitacora')" href="/auditoria" icon="history" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Log de Auditoría' : null">Log de Auditoría</NavLink>
        </SidebarSection>

        <!-- 📚 Catálogos y empresa -->
        <SidebarSection
          v-if="($can('view categorias') || $can('view marcas') || $can('view servicios') || $can('view configuracion_empresa') || isAdmin) && matchesSearch('categorías marcas servicios catálogo catalogo empresa landing ajustes marca producto configuración configuracion monitoreo monitor health')"
          title="Catálogos y empresa"
          subtitle="Marcas, servicios y apariencia"
          icon="cogs"
          iconColor="text-indigo-300"
          :isOpen="accordionStates.catalogos"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('catalogos')"
        >
          <NavLink v-if="$can('view categorias')" href="/categorias" icon="tags" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Categorías' : null">Categorías</NavLink>
          <NavLink v-if="$can('view marcas')" href="/marcas" icon="trademark" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Marcas' : null">Marcas</NavLink>
          <NavLink v-if="$can('view servicios')" href="/servicios" icon="wrench" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Servicios' : null">Servicios</NavLink>
          <NavLink v-if="$can('view configuracion_empresa')" href="/empresa/configuracion" icon="cog" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Configuración de Empresa' : null">Configuración de Empresa</NavLink>
          <NavLink v-if="$can('view configuracion_empresa')" href="/empresa/landing-content" icon="palette" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Contenido de Landing' : null">Contenido de Landing</NavLink>
          <NavLink v-if="isAdmin" href="/monitoreo" icon="heart-pulse" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Monitoreo' : null">Monitoreo del Sistema</NavLink>
        </SidebarSection>

        <!-- 📊 Reportes y CFDI -->
        <SidebarSection
          v-if="($can('contador') || $can('view reportes') || $can('view finanzas') || $can('view cfdi')) && matchesSearch('reportes cfdi documentos fiscales indicadores comprobantes panel general finanzas ventas periodo vendedor cita')"
          title="Reportes y CFDI"
          subtitle="Indicadores, reportes y comprobantes fiscales"
          icon="chart-bar"
          iconColor="text-indigo-400"
          :isOpen="accordionStates.reportes"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('reportes')"
        >

          
          <NavLink v-if="$can('view reportes')" href="/reportes/citas-por-tecnico" icon="user-check" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Citas por Técnico' : null">Citas por Técnico</NavLink>
          <NavLink v-if="$can('view reportes')" href="/reportes/ventas-semana" icon="receipt" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Ventas periodo' : null">Ventas (periodo)</NavLink>
          <NavLink v-if="$can('view reportes')" :href="route('reportes.ventas-utilidad')" icon="chart-line" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Reporte de ventas' : null">Reporte de ventas</NavLink>
          <NavLink v-if="$can('view reportes')" href="/reportes/productos-para-comprar" icon="shopping-basket" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Sugerencias Compra' : null">Sugerencias Compra</NavLink>

        </SidebarSection>

        <!-- 📈 Trading & Cripto (Solo Super Admin) -->
        <SidebarSection
          v-if="isAdmin"
          title="Trading Training"
          subtitle="Simulación de Velas y Binance"
          icon="chart-line"
          iconColor="text-amber-400"
          :isOpen="accordionStates.trading"
          :collapsed="props.isSidebarCollapsed"
          @toggle="toggleAccordion('trading')"
        >
          <NavLink href="/trading/simulacion" icon="chart-area" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Simulación de Velas' : null">Simulación de Velas</NavLink>
          <NavLink href="/trading/binance" icon="coins" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Binance Live' : null">Binance Live</NavLink>
        </SidebarSection>

        <!-- 📁 Proyectos (mismo bloque administración) -->
        <div v-if="$can('view proyectos') && matchesSearch('proyectos beta tareas')" class="mt-1 px-1">
          <NavLink href="/proyectos" :icon="faFolderOpen" :collapsed="props.isSidebarCollapsed" :title="props.isSidebarCollapsed ? 'Proyectos' : null">
            Proyectos (Beta)
          </NavLink>
        </div>
        </div>

      </div>
    </nav>

    <!-- Usuario Footer -->
    <div
      class="border-t border-[var(--ui-border)] p-5 bg-[var(--ui-surface)] flex-shrink-0 relative overflow-hidden group/footer"
      :class="{'flex justify-center px-2': props.isSidebarCollapsed}"
    >
      <!-- Glassmorphism overlay -->
      <div class="absolute inset-0 bg-white/[0.01] backdrop-blur-xl"></div>
      
      <div class="flex items-center relative z-10" :class="{'w-full justify-center': props.isSidebarCollapsed, 'gap-4': !props.isSidebarCollapsed}">
        <div class="relative group/avatar flex-shrink-0">
          <div class="absolute -inset-1 bg-gradient-to-tr from-brand-500 to-brand-600 rounded-xl blur opacity-20 group-hover/avatar:opacity-50 transition-all duration-500"></div>
          <img
            :src="props.usuario?.profile_photo_url || avatarFallbackUrl"
            :alt="props.usuario?.name || 'User'"
            class="w-10 h-10 rounded-xl border border-white/10 object-cover group-hover/avatar:border-brand-500/50 transition-all duration-500 relative z-10"
            @error="onAvatarImgError"
          />
          <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-emerald-500 border-[3px] border-[var(--ui-surface)] rounded-full z-20 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
        </div>
        <div v-show="!props.isSidebarCollapsed" class="flex-1 min-w-0">
          <p class="text-[12px] font-black text-white/95 truncate leading-tight tracking-wide">
            {{ props.usuario?.name || 'Usuario' }}
          </p>
          <div class="flex items-center mt-1">
            <span class="w-1.5 h-1.5 bg-brand-500 rounded-full mr-2 opacity-60"></span>
            <p class="text-[9px] font-bold text-white/60 truncate uppercase tracking-wide">
              {{ isAdmin ? 'Administrador' : 'Colaborador' }}
            </p>
          </div>
        </div>
        
        <!-- Logout Button -->
        <Link 
          v-show="!props.isSidebarCollapsed" 
          method="post" 
          as="button" 
          href="/logout" 
          class="p-2.5 rounded-xl text-white/40 hover:text-rose-400 hover:bg-black/40 hover:border-rose-500/30 border border-transparent transition-all duration-500 group/logout"
          title="Cerrar Sesión"
        >
          <FontAwesomeIcon :icon="faSignOutAlt" class="text-xs group-hover/logout:scale-125 transition-transform" />
        </Link>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, onMounted, computed, onBeforeUnmount, h, nextTick, watch } from 'vue';
import axios from 'axios';
import { Link, usePage, router } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faFolderOpen, faSearch, faSignOutAlt } from '@fortawesome/free-solid-svg-icons';

// ─── Inline SidebarSection component ───
const SidebarSection = {
  name: 'SidebarSection',
  props: {
    title: String,
    subtitle: String,
    icon: String,
    iconColor: { type: String, default: 'text-slate-400' },
    isOpen: Boolean,
    collapsed: Boolean,
  },
  emits: ['toggle'],
  setup(props, { slots, emit }) {
    const getColorTheme = () => {
      const colorMap = {
        'text-brand-500': { bg: 'bg-brand-500/10', border: 'border-brand-500/20', text: 'text-brand-500', shadow: 'shadow-brand-500/10' },
        'text-violet-400': { bg: 'bg-violet-500/10', border: 'border-violet-500/20', text: 'text-violet-400', shadow: 'shadow-violet-500/10' },
        'text-blue-400': { bg: 'bg-blue-500/10', border: 'border-blue-500/20', text: 'text-blue-400', shadow: 'shadow-blue-500/10' },
        'text-red-500': { bg: 'bg-red-500/10', border: 'border-red-500/20', text: 'text-red-500', shadow: 'shadow-red-500/10' },
        'text-rose-400': { bg: 'bg-rose-500/10', border: 'border-rose-500/20', text: 'text-rose-400', shadow: 'shadow-rose-500/10' },
        'text-orange-400': { bg: 'bg-orange-500/10', border: 'border-orange-500/20', text: 'text-orange-400', shadow: 'shadow-orange-500/10' },
        'text-lime-400': { bg: 'bg-lime-500/10', border: 'border-lime-500/20', text: 'text-lime-400', shadow: 'shadow-lime-500/10' },
        'text-amber-400': { bg: 'bg-amber-500/10', border: 'border-amber-500/20', text: 'text-amber-400', shadow: 'shadow-amber-500/10' },
        'text-emerald-400': { bg: 'bg-emerald-500/10', border: 'border-emerald-500/20', text: 'text-emerald-400', shadow: 'shadow-emerald-500/10' },
        'text-teal-400': { bg: 'bg-teal-500/10', border: 'border-teal-500/20', text: 'text-teal-400', shadow: 'shadow-teal-500/10' },
        'text-indigo-400': { bg: 'bg-indigo-500/10', border: 'border-indigo-500/20', text: 'text-indigo-400', shadow: 'shadow-indigo-500/10' },
        'text-sky-400': { bg: 'bg-sky-500/10', border: 'border-sky-500/20', text: 'text-sky-400', shadow: 'shadow-sky-500/10' },
        'text-indigo-300': { bg: 'bg-indigo-500/10', border: 'border-indigo-500/20', text: 'text-indigo-300', shadow: 'shadow-indigo-500/10' },
        'text-slate-400': { bg: 'bg-white/5', border: 'border-white/10', text: 'text-white/60', shadow: 'shadow-black/20' }
      };
      return colorMap[props.iconColor] || colorMap['text-slate-400'];
    };

    return () => {
      const theme = getColorTheme();
      return h('div', { 
        class: [
          'mb-3 px-1 transition-all duration-500',
          props.isOpen ? 'sidebar-section-container' : ''
        ].join(' ')
      }, [
        h('div', {
          class: [
            'rounded-2xl transition-all duration-500 overflow-hidden',
            props.isOpen 
              ? `${theme.bg} border ${theme.border} ${theme.shadow} backdrop-blur-md` 
              : 'bg-transparent border border-transparent'
          ].join(' ')
        }, [
          // Header button
          h('div', {
            onClick: () => emit('toggle'),
            role: 'button',
            tabindex: props.collapsed ? -1 : 0,
            'aria-expanded': props.collapsed ? undefined : String(props.isOpen),
            onKeydown: (e) => {
              if (props.collapsed) return;
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                emit('toggle');
              }
            },
            class: [
              'group flex items-center justify-between px-3.5 py-3 text-[10px] font-black uppercase tracking-[0.12em] cursor-pointer transition-all duration-500 relative overflow-hidden',
              props.isOpen
                ? `text-white`
                : 'text-white/60 hover:text-white/90 hover:bg-white/[0.03]'
            ].join(' '),
          }, [
            h('div', { class: 'flex items-center gap-3.5 min-w-0 relative z-10' }, [
              h('div', { class: 'relative' }, [
                props.isOpen ? h('div', { class: `absolute -inset-2 ${theme.bg.split('/')[0]}/30 blur-lg rounded-full animate-pulse` }) : null,
                h(FontAwesomeIcon, {
                  icon: props.icon,
                  class: `w-4 h-4 ${props.isOpen ? theme.text : 'text-white/40'} flex-shrink-0 transition-all duration-500 ${props.isOpen ? 'scale-110 drop-shadow-[0_0_8px_currentColor]' : 'group-hover:scale-110'}`,
                }),
              ]),
              !props.collapsed ? h('div', { class: 'leading-tight min-w-0' }, [
                h('span', { class: 'block transition-all duration-300 ' + (props.isOpen ? 'tracking-[0.15em] text-white' : '') }, props.title),
                props.subtitle ? h('p', {
                  class: 'text-[8px] normal-case tracking-normal font-bold mt-1.5 truncate transition-all duration-500 ' +
                         (props.isOpen ? 'text-white/40' : 'text-white/20 group-hover:text-white/40'),
                }, props.subtitle) : null,
              ]) : null,
            ]),
            h('div', { class: 'relative z-10 flex items-center' }, [
              h(FontAwesomeIcon, {
                icon: 'chevron-right',
                class: `w-2 h-2 transition-all duration-500 flex-shrink-0 ${props.isOpen ? 'rotate-90 ' + theme.text : 'text-white/10 group-hover:text-white/30'}`,
              }),
            ]),
          ]),
          // Content
          h('div', {
            class: ['sidebar-accordion px-2', props.isOpen ? 'sidebar-accordion-open' : ''].join(' '),
          }, [
            h('div', { class: 'space-y-1 pb-3' }, slots.default?.()),
          ]),
        ])
      ]);
    };
  },
};

const page = usePage();
const navScrollRef = ref(null);
const searchQuery = ref('');
const vpsStatus = ref({ last_backup: 'Cargando...', status: 'pending', size: '' });

const fetchVpsStatus = async () => {
    try {
        const response = await axios.get('/admin/backup/vps-status');
        vpsStatus.value = response.data;
    } catch (error) {
        console.error('Error fetching VPS backup status:', error);
    }
};

/** Quita tildes para comparar "cotizacion" con "cotización". */
const stripDiacritics = (s) =>
  String(s)
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

/** Filtro del buscador: la cadena de palabras clave debe contener el texto buscado. */
const matchesSearch = (keywordsText) => {
  const q = stripDiacritics(searchQuery.value.trim());
  if (!q) return true;
  return stripDiacritics(keywordsText).includes(q);
};

const fallbackLogo = '/images/logo.webp';

// ─── Scroll Persistence ───
// Save the <nav> scroll position (the element that actually scrolls)
const saveScrollPosition = () => {
  if (navScrollRef.value) {
    sessionStorage.setItem('sidebar_scroll_pos', String(navScrollRef.value.scrollTop));
  }
};

const restoreScrollPosition = () => {
  const savedPos = sessionStorage.getItem('sidebar_scroll_pos');
  if (!savedPos || !navScrollRef.value) return;
  const targetPos = parseInt(savedPos);

  // Apply immediately
  navScrollRef.value.scrollTop = targetPos;

  // Retry several times to catch post-accordion-animation layout shifts
  const attemptRestore = () => {
    if (navScrollRef.value && Math.abs(navScrollRef.value.scrollTop - targetPos) > 5) {
      navScrollRef.value.scrollTop = targetPos;
    }
  };
  [50, 100, 200, 400, 700].forEach(delay => setTimeout(attemptRestore, delay));
};

// Save scroll BEFORE every Inertia navigation (click on any link)
let removeBeforeListener = null;
onMounted(() => {
  if ($can('manage-backups')) {
    fetchVpsStatus();
    // Refrescar cada 5 minutos
    const interval = setInterval(fetchVpsStatus, 300000);
    onBeforeUnmount(() => clearInterval(interval));
  }
  
  removeBeforeListener = router.on('before', () => {
    saveScrollPosition();
  });
});

onBeforeUnmount(() => {
  saveScrollPosition();
  if (removeBeforeListener) removeBeforeListener();
});

const empresaConfig = computed(() => page.props.empresa_config);
const auth = computed(() => page.props.auth);

const $can = (permissionOrRole) => {
  const authData = auth.value;
  if (!authData || !authData.user) return false;
  if (authData.user.is_admin) return true;
  const permissions = authData.user.permissions || [];
  const roles = authData.user.roles || [];
  const roleNames = Array.isArray(roles) ? roles.map(r => typeof r === 'string' ? r : r.name) : [];
  if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;
  return permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole);
};

const props = defineProps({
  usuario: { type: Object, required: true },
  isSidebarCollapsed: { type: Boolean, default: false },
  isMobile: { type: Boolean, default: false },
});

const avatarFallbackUrl = computed(() => {
  const name = encodeURIComponent(props.usuario?.name || 'User');
  return `https://ui-avatars.com/api/?name=${name}&background=1e293b&color=94a3b8&bold=true`;
});

const onLogoImgError = (e) => {
  const el = e?.target;
  if (!el || !el.src) return;
  if (!el.src.includes('logo.webp')) el.src = fallbackLogo;
};

const onAvatarImgError = (e) => {
  const el = e?.target;
  if (!el || !el.src) return;
  const fb = avatarFallbackUrl.value;
  if (el.src !== fb) el.src = fb;
};

const isVentasRole = computed(() => {
  if (!props.usuario || !props.usuario.roles) return false;
  const hasAdmin = props.usuario.roles.some(role => ['admin', 'super-admin'].includes(role.name)) || props.usuario.is_admin;
  const hasVentas = props.usuario.roles.some(role => role.name === 'ventas');
  return hasVentas && !hasAdmin;
});

const isAdmin = computed(() => {
  if (!props.usuario) return false;
  if (props.usuario.is_admin) return true;
  if (props.usuario.roles) {
    return props.usuario.roles.some(role => ['admin', 'super-admin'].includes(role.name));
  }
  return false;
});

const isSuperAdmin = computed(() => {
  if (!props.usuario) return false;
  if (props.usuario.roles) {
    return props.usuario.roles.some(role => role.name === 'super-admin');
  }
  return false;
});

/** Coincide con rutas: role admin|super-admin|ventas en marketing/whatsapp-inbox */
const canAccessWhatsAppInbox = computed(() => {
  if (!props.usuario) return false;
  if (isAdmin.value) return true;
  if ($can('view marketing')) return true;
  return props.usuario.roles?.some((r) => r.name === 'ventas') ?? false;
});

const emit = defineEmits(['toggleSidebar']);

const SIDEBAR_ACCORDION_STORAGE_KEY = 'sidebar_accordion_states';

/** Ruta Inertia → clave de acordeón (una sección a la vez cuando coincide). */
function detectSidebarSectionFromPath(path) {
  const p = path || '';

  // ─── Specific compliance and sub-routes (high priority to prevent generic overlaps) ───
  if (p.startsWith('/comisiones/seguridad-higiene') || p.startsWith('/comisiones/recorridos')) {
    return 'comisiones_nom019';
  }
  if (p.startsWith('/comisiones/repse') || p.startsWith('/comisiones/vencimientos')) {
    return 'comisiones_repse';
  }
  if (p.startsWith('/comisiones/pulse')) {
    return 'comisiones_pulse';
  }
  if (p.startsWith('/nom035') || (p.startsWith('/empleados/cumplimiento') && !p.startsWith('/empleados/cumplimiento/'))) {
    return 'comisiones_nom035';
  }

  // ─── Standard modules ───
  if (p.startsWith('/clientes') || p.startsWith('/crm')) {
    return 'crm';
  }
  if (p.startsWith('/citas') || p.startsWith('/citas-calendario') ||
      p.startsWith('/cotizaciones') || p.startsWith('/pedidos') || p.startsWith('/ventas') ||
      p.startsWith('/facturas') || p.startsWith('/garantias') ||
      p.startsWith('/mi-agenda') || p.startsWith('/pedidos-online') || p.startsWith('/pos')) {
    return 'ventas';
  }
  if (p.startsWith('/marketing/')) {
    return 'marketing';
  }
  if (p.startsWith('/polizas-servicio') || p.startsWith('/planes-poliza') || p.startsWith('/tecnico/mantenimientos') || p.startsWith('/tecnicos')) {
    return 'polizas';
  }
  if (p.startsWith('/soporte') || p.startsWith('/soporte-remoto') || p.startsWith('/credenciales')) {
    return 'tickets';
  }
  if (p.startsWith('/proveedores') || p.startsWith('/ordenescompra') || p.startsWith('/compras')) {
    return 'compras';
  }
  if (p.startsWith('/productos') || p.startsWith('/kits') || p.startsWith('/cva') ||
      (p.startsWith('/traspasos') && !p.startsWith('/traspasos-bancarios')) ||
      p.startsWith('/movimientos-inventario') || p.startsWith('/ajustes-inventario') ||
      p.startsWith('/inventarios-fisicos') ||
      p.startsWith('/admin/solicitudes-material') ||
      p.startsWith('/reportes-inventario') ||
      p.startsWith('/almacenes')) {
    return 'inventario';
  }
  if (p.startsWith('/cuentas-bancarias') || p.startsWith('/conciliacion-bancaria') ||
      p.startsWith('/caja-chica') || p.startsWith('/entregas-dinero') ||
      p.startsWith('/traspasos-bancarios')) {
    return 'tesoreria';
  }
  if (p.startsWith('/cuentas-por-pagar') || p.startsWith('/cuentas-por-cobrar') ||
      p.startsWith('/gastos') || p.startsWith('/comisiones') || p.startsWith('/prestamos') || p.startsWith('/pagos')) {
    return 'cuentas';
  }
  if (p.startsWith('/mantenimientos') || p.startsWith('/herramientas') || p.startsWith('/taller')) {
    return 'taller';
  }
  if (p.startsWith('/rentas') || p.startsWith('/planes-renta') || p.startsWith('/equipos') || p.startsWith('/carros')) {
    return 'rentas_flota';
  }
  if (p.startsWith('/asistencia')) {
    return 'asistencia';
  }
  if (p.startsWith('/mis-pendientes')) {
    return 'todos';
  }
  if (p.startsWith('/empleados') || p.startsWith('/nominas') || p.startsWith('/vacaciones') ||
      p.startsWith('/mis-vacaciones') || p.startsWith('/registro-vacaciones')) {
    return 'empleados';
  }
  if (p.startsWith('/usuarios') || p.startsWith('/roles') || p.startsWith('/bitacora') ||
      p.startsWith('/dispositivos') || p.startsWith('/backup')) {
    return 'seguridad';
  }
  if (p.startsWith('/categorias') || p.startsWith('/marcas') || p.startsWith('/servicios') ||
      p.startsWith('/empresa/configuracion') || p.startsWith('/empresa/landing-content') || p === '/monitoreo') {
    return 'catalogos';
  }
  if (p.startsWith('/admin/blog') || p.startsWith('/gestion-blog') || p.startsWith('/blog')) {
    return 'blog';
  }
  if (p.startsWith('/reportes') || p === '/finanzas') {
    return 'reportes';
  }
  if (p.startsWith('/trading')) {
    return 'trading';
  }
  if (p.startsWith('/contabilidad') || p.startsWith('/cfdi')) {
    return 'contabilidad';
  }
  if (p.startsWith('/mirage')) {
    return 'mirage';
  }
  return null;
}

function pathFromInertiaUrl(url) {
  if (!url) return '';
  if (url.startsWith('/')) return url.split('?')[0];
  try {
    return new URL(url).pathname;
  } catch {
    return '';
  }
}

const getInitialAccordionState = () => {
  const defaultState = {
    crm: false, ventas: false, marketing: false,
    tickets: false, polizas: false, compras: false, inventario: false,
    tesoreria: false, cuentas: false, rentas_flota: false, taller: false,
    asistencia: false, empleados: false, todos: false,
    blog: false, seguridad: false, catalogos: false, reportes: false,
    trading: false, contabilidad: false, mirage: false,
    comisiones_nom019: false, comisiones_nom035: false, comisiones_repse: false, comisiones_pulse: false,
  };
  try {
    const sess = sessionStorage.getItem(SIDEBAR_ACCORDION_STORAGE_KEY);
    const local = localStorage.getItem(SIDEBAR_ACCORDION_STORAGE_KEY);
    const raw = sess ?? local;
    if (raw) {
      const parsed = JSON.parse(raw);
      Object.keys(parsed).forEach((key) => {
        if (key in defaultState) defaultState[key] = !!parsed[key];
      });
    }
  } catch (e) {}

  const path = typeof window !== 'undefined' ? window.location.pathname : '';
  const fromPath = detectSidebarSectionFromPath(path);
  if (fromPath) {
    Object.keys(defaultState).forEach((k) => { defaultState[k] = false; });
    defaultState[fromPath] = true;
  }
  return defaultState;
};

const accordionStates = ref(getInitialAccordionState());

/** Al buscar, abrir todas las secciones; al limpiar, restaurar el estado previo. */
const accordionSnapshotBeforeSearch = ref(null);

const saveAccordionState = () => {
  const payload = JSON.stringify(accordionStates.value);
  try {
    sessionStorage.setItem(SIDEBAR_ACCORDION_STORAGE_KEY, payload);
  } catch (e) {}
  try {
    localStorage.setItem(SIDEBAR_ACCORDION_STORAGE_KEY, payload);
  } catch (e) {}
};

const toggleAccordion = (section) => {
  const willBeOpen = !accordionStates.value[section];
  if (props.isSidebarCollapsed || willBeOpen) {
    Object.keys(accordionStates.value).forEach(key => {
      accordionStates.value[key] = key === section;
    });
  } else {
    accordionStates.value[section] = false;
  }
  saveAccordionState();
};

watch(searchQuery, (q) => {
  const trimmed = String(q || '').trim();
  if (trimmed) {
    if (!accordionSnapshotBeforeSearch.value) {
      accordionSnapshotBeforeSearch.value = { ...accordionStates.value };
    }
    Object.keys(accordionStates.value).forEach((k) => {
      accordionStates.value[k] = true;
    });
  } else if (accordionSnapshotBeforeSearch.value) {
    const snap = accordionSnapshotBeforeSearch.value;
    accordionSnapshotBeforeSearch.value = null;
    Object.keys(accordionStates.value).forEach((k) => {
      if (Object.prototype.hasOwnProperty.call(snap, k)) {
        accordionStates.value[k] = snap[k];
      }
    });
    saveAccordionState();
  }
});

/** Al navegar (Inertia), dejar solo abierto el acordeón que corresponde a la ruta. */
watch(
  () => page.url,
  (url) => {
    if (searchQuery.value.trim()) return;
    const path = pathFromInertiaUrl(url);
    const section = detectSidebarSectionFromPath(path);
    if (section) {
      Object.keys(accordionStates.value).forEach((k) => {
        accordionStates.value[k] = k === section;
      });
      saveAccordionState();
      nextTick(() => restoreScrollPosition());
    }
  },
);

const routeOr = (fallback) => {
  if (typeof route === 'function') {
    try {
      if (fallback === '/cva/importar') return route('cva.import');
      if (fallback === '/admin/blog') return route('admin.blog.index');

      return route('backup.index');
    } catch (e) { return fallback; }
  }
  return fallback;
};

const toggleSidebar = () => emit('toggleSidebar');

onMounted(() => {
  saveAccordionState();
  // Wait for the DOM and accordion animations to settle before restoring scroll
  nextTick(() => {
    restoreScrollPosition();
  });
});
</script>

<style>
/* ─── Theme-aware overrides ─── */
.sidebar-root {
  background: linear-gradient(180deg, var(--ui-surface) 0%, var(--ui-bg) 100%);
  color: var(--ui-text);
}

/* Map hardcoded white text to theme tokens */
.sidebar-root .text-white,
.sidebar-root .text-white\/95 { color: var(--ui-text) !important; }
.sidebar-root .text-white\/80,
.sidebar-root .text-white\/60 { color: var(--ui-text-muted) !important; }
.sidebar-root .text-white\/40,
.sidebar-root .text-white\/35,
.sidebar-root .text-white\/30,
.sidebar-root .text-white\/10 { color: var(--ui-text-soft) !important; }

/* Map hardcoded white borders to theme border */
.sidebar-root .border-white\/\[0\.04\],
.sidebar-root .border-white\/\[0\.05\],
.sidebar-root .border-white\/\[0\.06\],
.sidebar-root .border-white\/\[0\.08\],
.sidebar-root .border-white\/\[0\.10\],
.sidebar-root .border-white\/\[0\.15\] { border-color: var(--ui-border) !important; }

/* Map subtle glass bg elements to theme-aware surface */
.sidebar-root .bg-white\/\[0\.01\],
.sidebar-root .bg-white\/\[0\.03\] { background-color: var(--ui-surface-soft) !important; transition: background-color 0.3s; }

/* Sidebar section open state bg */
.sidebar-root .bg-white\/\[0\.06\] { background-color: var(--ui-surface-soft) !important; }

/* Accordion animation */
.sidebar-root .sidebar-accordion {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease;
  opacity: 0;
}
.sidebar-root .sidebar-accordion-open {
  max-height: 1200px;
  opacity: 1;
  transition: max-height 0.55s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease 0.05s;
}

/* Custom scrollbar */
.sidebar-root .sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-root .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-root .sidebar-scroll::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.3);
  border-radius: 4px;
}
.sidebar-root .sidebar-scroll::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.5);
}
</style>
