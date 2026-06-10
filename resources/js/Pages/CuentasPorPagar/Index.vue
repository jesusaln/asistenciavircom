<template>
    <AppLayout title="Cuentas por Pagar">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-black text-3xl text-slate-900 dark:text-white leading-tight uppercase tracking-wider">
                        Cuentas por <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">Pagar</span>
                    </h2>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-1">Gestión de obligaciones y proveedores</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="showImportPaymentModal = true"
                        class="px-5 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-black text-xs uppercase tracking-wide rounded-2xl border border-slate-200 dark:border-slate-800 hover:border-brand-500/50 hover:text-brand-600 dark:hover:text-orange-400 transition-all duration-200 shadow-sm flex items-center gap-2 group"
                    >
                        <svg class="w-4 h-4 text-brand-500 group-hover:scale-105 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Importar XML
                    </button>
                    <Link
                        :href="route('cuentas-por-pagar.create')"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white font-black text-xs uppercase tracking-wide rounded-2xl shadow-xl shadow-blue-500/20 active:scale-95 transition-all duration-200 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva CXP
                    </Link>
                </div>
            </div>
        </template>

        <div class="p-6 space-y-6">
            <!-- Estadísticas Premium -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Vencido -->
                <div @click="filters.estado = 'vencido'; applyFilters()" class="relative group cursor-pointer">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500 to-brand-600 rounded-[2rem] blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-800/50 rounded-[2rem] p-6 shadow-xl overflow-hidden active:scale-95 transition-transform">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Total Vencido</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(stats.total_vencido) }}</h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center border border-rose-500/20">
                                <svg class="w-10 h-10 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                           <span class="px-2 py-0.5 bg-brand-500/10 text-rose-500 text-[10px] font-black rounded-xl uppercase tracking-wide">{{ stats.count_vencidas }} Cuentas</span>
                           <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wide italic">Acción requerida</span>
                        </div>
                    </div>
                </div>
                
                <!-- Deuda por Vencer (Pendiente Corriente) -->
                <div @click="filters.estado = 'pendiente'; applyFilters()" class="relative group cursor-pointer">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500 to-brand-600 rounded-[2rem] blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-800/50 rounded-[2rem] p-6 shadow-xl overflow-hidden active:scale-95 transition-transform">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[8px] font-black rounded-xl uppercase tracking-wide mb-1">Total: {{ formatCurrency(stats.total_deuda) }}</span>
                                <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Por Vencer</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(stats.total_por_vencer) }}</h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center border border-brand-500/20">
                                <svg class="w-10 h-10 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                           <span class="px-2 py-0.5 bg-brand-500/10 text-brand-500 text-[10px] font-black rounded-xl uppercase tracking-wide">{{ stats.count_por_vencer }} Pendientes</span>
                           <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wide italic">Vigente</span>
                        </div>
                    </div>
                </div>

                <!-- Total Pagado -->
                <div @click="filters.estado = 'pagado'; applyFilters()" class="relative group cursor-pointer">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500 to-brand-600 rounded-[2rem] blur opacity-20 group-hover:opacity-40 transition duration-500"></div>
                    <div class="relative bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-800/50 rounded-[2rem] p-6 shadow-xl overflow-hidden active:scale-95 transition-transform">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Total Pagado (Hist)</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(stats.total_pagado) }}</h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-brand-500/10 flex items-center justify-center border border-emerald-500/20">
                                <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                           <span class="px-2 py-0.5 bg-brand-500/10 text-emerald-500 text-[10px] font-black rounded-xl uppercase tracking-wide">{{ stats.count_pagadas }} Pagadas</span>
                           <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wide italic">Pagos liquidados</span>
                        </div>
                        <div v-if="toNumber(stats.total_saldo_favor) > 0" class="mt-2">
                            <span class="px-2 py-0.5 bg-teal-500/10 text-teal-500 text-[10px] font-black rounded-xl uppercase tracking-wide">
                                Favor: {{ formatCurrency(stats.total_saldo_favor) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Total Cancelado -->
                <div @click="filters.estado = 'cancelada'; applyFilters()" class="relative group cursor-pointer">
                    <div class="absolute -inset-0.5 bg-gradient-to-br from-slate-500 to-slate-700 rounded-[2rem] blur opacity-10 group-hover:opacity-20 transition duration-500"></div>
                    <div class="relative bg-white dark:bg-slate-800 border border-slate-200/50 dark:border-slate-800/50 rounded-[2rem] p-6 shadow-xl overflow-hidden active:scale-95 transition-transform flex flex-col justify-center">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Cuentas Canceladas</p>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(stats.total_cancelado) }}</h3>
                            </div>
                            <div class="w-10 h-10 rounded-2xl bg-slate-500/10 flex items-center justify-center border border-slate-500/20">
                                <svg class="w-10 h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                           <span class="px-2 py-0.5 bg-slate-500/10 text-slate-500 text-[10px] font-black rounded-xl uppercase tracking-wide">{{ stats.count_canceladas }} Canceladas</span>
                           <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wide italic">Anuladas</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs de Filtro Rápido -->
            <div class="flex flex-wrap items-center gap-2 pb-2">
                <button 
                    @click="filters.estado = ''; applyFilters()"
                    :class="filters.estado === '' ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700'"
                    class="px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all duration-200 border border-transparent active:scale-95"
                >
                    Todas las Cuentas
                </button>
                <button 
                    @click="filters.estado = 'vencido'; applyFilters()"
                    :class="filters.estado === 'vencido' ? 'bg-brand-500 text-white shadow-xl shadow-rose-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-rose-900/10'"
                    class="px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all duration-200 border border-transparent active:scale-95"
                >
                    Vencidas
                </button>
                <button 
                    @click="filters.estado = 'pendiente'; applyFilters()"
                    :class="filters.estado === 'pendiente' ? 'bg-brand-500 text-white shadow-xl shadow-brand-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-brand-900/10'"
                    class="px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all duration-200 border border-transparent active:scale-95"
                >
                    Pendientes
                </button>
                <button 
                    @click="filters.estado = 'pagado'; applyFilters()"
                    :class="filters.estado === 'pagado' ? 'bg-brand-500 text-white shadow-xl shadow-emerald-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-emerald-900/10'"
                    class="px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all duration-200 border border-transparent active:scale-95"
                >
                    Pagadas
                </button>
                <button 
                    @click="filters.estado = 'cancelada'; applyFilters()"
                    :class="filters.estado === 'cancelada' ? 'bg-slate-600 text-white shadow-xl shadow-slate-500/20' : 'bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                    class="px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-wide transition-all duration-200 border border-transparent active:scale-95"
                >
                    Canceladas
                </button>
            </div>

            <!-- Filtros Refinados -->
            <div class="bg-white/60 dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 rounded-[2.5rem] p-8 shadow-2xl">
                <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2 pl-1">Buscar por Folio/Proveedor</label>
                        <div class="relative">
                            <input 
                                v-model="filters.buscar" 
                                type="text" 
                                placeholder="Escribe para buscar..."
                                class="w-full pl-10 pr-10 bg-[var(--ui-surface)] dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-white focus:ring-[var(--color-primary)]/20 focus:border-[var(--color-primary)] transition-all"
                            >
                            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <button 
                                v-if="filters.buscar" 
                                @click="filters.buscar = ''; applyFilters()"
                                type="button"
                                class="absolute right-3 top-2.5 text-slate-400 hover:text-rose-500 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2 pl-1">Filtrar por Estado</label>
                        <select v-model="filters.estado" class="w-full bg-[var(--ui-surface)] dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-white focus:ring-[var(--color-primary)]/20 focus:border-[var(--color-primary)] transition-all">
                            <option value="">Cualquier estado</option>
                            <option value="pendiente">Pendiente</option>
                            <option value="parcial">Parcial</option>
                            <option value="pagado">Pagado</option>
                            <option value="vencido">Vencido</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-2 pl-1">Proveedor</label>
                        <select v-model="filters.proveedor_id" class="w-full bg-[var(--ui-surface)] dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold text-slate-700 dark:text-white focus:ring-[var(--color-primary)]/20 focus:border-[var(--color-primary)] transition-all">
                            <option value="">Todos los proveedores</option>
                            <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                {{ proveedor.nombre_razon_social }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="w-full py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black text-[10px] uppercase tracking-wide rounded-2xl hover:bg-slate-800 dark:hover:bg-slate-200 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-xl">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            Buscar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de Cuentas Premium -->
            <div class="bg-white/60 dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-950/20 border-b border-slate-100 dark:border-slate-800">
                                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] w-48">Ref. Compra</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Proveedor</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Montos</th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Vencimiento</th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Estado</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                            <tr v-for="cuenta in cuentas.data" :key="cuenta.id" class="group hover:bg-white dark:hover:bg-slate-950/40 transition-all duration-200">
                                <td class="px-8 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider group-hover:text-blue-500 transition-colors">
                                            {{ cuenta.compra ? cuenta.compra.numero_compra : 'SIN REF' }}
                                        </span>
                                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-0.5">ID #{{ cuenta.id }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700 group-hover:border-brand-500/30 transition-all">
                                            {{ (cuenta.compra?.proveedor?.nombre_razon_social || cuenta.proveedor?.nombre_razon_social || 'S').charAt(0) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-black text-slate-700 dark:text-slate-200 truncate uppercase tracking-wider">
                                                {{ cuenta.compra?.proveedor?.nombre_razon_social || cuenta.proveedor?.nombre_razon_social || 'SIN PROVEEDOR' }}
                                            </p>
                                            <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 tracking-wide mt-0.5">
                                                {{ cuenta.compra?.proveedor?.rfc || cuenta.proveedor?.rfc || 'RFC NO REGISTRADO' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-xs font-black text-slate-900 dark:text-white tracking-tight">{{ formatCurrency(cuenta.monto_total) }}</span>
                                        <span :class="toNumber(cuenta.monto_pendiente) > 0 ? 'text-rose-500' : 'text-emerald-500'" class="text-[9px] font-black uppercase tracking-wide mt-0.5">
                                            Pend: {{ formatCurrency(cuenta.monto_pendiente) }}
                                        </span>
                                        <span v-if="toNumber(cuenta.saldo_favor_generado) > 0" class="text-[9px] font-black uppercase tracking-wide mt-0.5 text-teal-500">
                                            Favor: {{ formatCurrency(cuenta.saldo_favor_generado) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-xs font-bold text-slate-500 dark:text-slate-200 tracking-tight">
                                            {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString('es-MX', {day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A' }}
                                        </span>
                                        <div v-if="esVencido(cuenta)" class="mt-0.5 flex items-center gap-1">
                                            <span class="w-1 h-1 rounded-full bg-brand-500 animate-pulse"></span>
                                            <span class="text-[8px] font-black text-rose-500 uppercase tracking-wide">Vencido</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <span :class="{
                                        'bg-brand-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20': estadoPara(cuenta) === 'vencido',
                                        'bg-brand-500/10 text-brand-600 dark:text-brand-400 border-brand-500/20': estadoPara(cuenta) === 'parcial',
                                        'bg-brand-500/10 text-emerald-600 dark:text-slate-400 border-emerald-500/20': estadoPara(cuenta) === 'pagado',
                                        'bg-slate-500/10 text-slate-500 dark:text-slate-200 border-slate-500/20': estadoPara(cuenta) === 'cancelada',
                                        'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700': estadoPara(cuenta) === 'pendiente'
                                    }" class="inline-flex items-center px-3 py-1 rounded-xl text-[9px] font-black uppercase tracking-wide border">
                                        {{ estadoPara(cuenta) }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link 
                                            :href="route('cuentas-por-pagar.show', cuenta.id)" 
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400 hover:border-brand-500/50 hover:shadow-xl transition-all active:scale-90"
                                            title="Ver detalles"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        
                                        <Link 
                                            v-if="!['pagado', 'cancelada'].includes(estadoPara(cuenta))"
                                            :href="route('cuentas-por-pagar.edit', cuenta.id)" 
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-brand-500 dark:hover:text-brand-400 hover:border-brand-500/50 hover:shadow-xl transition-all active:scale-90"
                                            title="Editar"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>

                                        <button
                                            v-if="estadoPara(cuenta) !== 'cancelada'"
                                            type="button"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-brand-500 dark:hover:text-orange-400 hover:border-brand-500/50 hover:shadow-xl transition-all active:scale-90"
                                            title="Cancelar cuenta"
                                            @click="cancelCuenta(cuenta)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                        
                                        <button
                                            v-if="!['pagado', 'cancelada'].includes(estadoPara(cuenta)) && toNumber(cuenta.monto_pagado) === 0"
                                            type="button"
                                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 hover:text-rose-500 dark:hover:text-rose-400 hover:border-brand-500/50 hover:shadow-xl transition-all active:scale-90"
                                            title="Eliminar"
                                            @click="removeCuenta(cuenta)"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="cuentas.data.length === 0">
                                <td colspan="6" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-6">
                                        <div class="w-16 h-16 bg-[var(--ui-surface)] rounded-full flex items-center justify-center border border-slate-100 dark:border-slate-800 shadow-inner">
                                            <svg class="w-10 h-10 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">No se encontraron cuentas</p>
                                            <p class="text-xs text-slate-400 dark:text-slate-700 font-bold mt-1">Intenta ajustando los filtros de búsqueda</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Premium -->
                <div class="px-8 py-8 bg-slate-50/50 dark:bg-slate-950/20 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-6">
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">
                        Mostrando {{ cuentas.from || 0 }} - {{ cuentas.to || 0 }} <span class="text-slate-300 dark:text-slate-700 mx-2">/</span> Total de {{ cuentas.total }} registros
                    </p>
                    <div class="inline-flex items-center p-1.5 bg-white/50 dark:bg-black/50 backdrop-blur-md rounded-2xl border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
                        <template v-for="link in cuentas.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url.replace(/^http(s)?:\/\/[^\/]+/, '')"
                                v-html="link.label"
                                :class="[
                                    'min-w-[2.5rem] h-10 px-3 flex items-center justify-center text-[10px] font-black uppercase tracking-wide transition-all duration-200 rounded-xl',
                                    link.active 
                                        ? 'bg-gradient-to-br from-blue-600 to-indigo-700 text-white shadow-xl shadow-blue-500/30' 
                                        : 'text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700'
                                ]"
                                :preserve-scroll="true"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="min-w-[2.5rem] h-10 px-3 flex items-center justify-center text-[10px] font-black uppercase tracking-wide text-slate-300 dark:text-slate-700 opacity-50"
                            ></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Portals y Modales -->
        <ImportPaymentXmlModal
            :show="showImportPaymentModal"
            :cuentas-bancarias="cuentasBancarias"
            @close="showImportPaymentModal = false"
            @imported="handlePaymentImported"
        />
    </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed } from 'vue';
import { Link, router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ImportPaymentXmlModal from '@/Components/CuentasPorPagar/ImportPaymentXmlModal.vue';
import Swal from '@/Utils/Swal';

const props = defineProps({
    cuentas: Object,
    stats: Object,
    filters: Object,
    cuentasBancarias: Array,
    proveedores: Array,
});

const proveedores = computed(() => props.proveedores || []);
const cuentasBancarias = computed(() => props.cuentasBancarias || []);

const filters = ref({
    estado: props.filters.estado || '',
    proveedor_id: props.filters.proveedor_id || '',
    buscar: props.filters.buscar || '',
});

const showImportPaymentModal = ref(false);

const handlePaymentImported = () => {
    // console.log('Pagos importados exitosamente');
};

const currencyFormatter = new Intl.NumberFormat('es-MX', { 
    style: 'currency', 
    currency: 'MXN',
    minimumFractionDigits: 2
});

const toNumber = (value) => {
    if (value === null || value === undefined) return 0;
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const estadoPara = (cuenta) => {
    if (cuenta?.estado === 'cancelada') return 'cancelada';
    if (cuenta?.pagado || toNumber(cuenta?.monto_pendiente) <= 0) return 'pagado';
    
    // Si tiene saldo insuficiente y pasó la fecha de vencimiento es "vencido"
    if (esVencido(cuenta)) return 'vencido';

    return cuenta?.estado || 'pendiente';
};

const esVencido = (cuenta) => {
    if (cuenta?.pagado || toNumber(cuenta?.monto_pendiente) <= 0) return false;
    if (!cuenta.fecha_vencimiento) return false;
    
    const hoy = new Date();
    hoy.setHours(0, 0, 0, 0);
    const vencimiento = new Date(cuenta.fecha_vencimiento);
    vencimiento.setHours(0,0,0,0);
    
    return hoy > vencimiento;
};

const removeCuenta = async (cuenta) => {
    const { isConfirmed } = await Swal.fire({
        title: 'Eliminar cuenta',
        text: `¿Estás seguro de eliminar la cuenta por pagar #${cuenta.id}? Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No',
    });
    if (!isConfirmed) return;

    router.delete(route('cuentas-por-pagar.destroy', cuenta.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Notificar éxito si es necesario
        }
    });
};

const cancelCuenta = async (cuenta) => {
    const motivo = prompt(`Motivo de cancelación para la cuenta #${cuenta.id}:`);

    if (motivo === null) return;

    const motivoLimpio = motivo.trim();
    if (motivoLimpio.length < 5) {
        Swal.fire({ title: 'Validación', text: 'El motivo debe tener al menos 5 caracteres.', icon: 'warning', confirmButtonText: 'Aceptar' });
        return;
    }

    const { isConfirmed } = await Swal.fire({
        title: 'Cancelar cuenta',
        text: `¿Confirmas cancelar la cuenta #${cuenta.id}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No',
    });
    if (!isConfirmed) return;

    router.post(route('cuentas-por-pagar.cancelar', cuenta.id), {
        motivo_cancelacion: motivoLimpio,
    }, {
        preserveScroll: true,
    });
};

const applyFilters = () => {
    router.visit(route('cuentas-por-pagar.index', filters.value), {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};
</script>

<style scoped>
/* Transiciones suaves para hover en filas */
.group:hover {
    transform: translateY(-1px);
}

/* Efecto de scrollbar premium */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.2);
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.4);
}
</style>


