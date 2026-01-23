<template>
    <Head title="CRM - Pipeline de Ventas" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-amber-50/30 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 p-4 md:p-6 transition-colors">
        <!-- Header Premium -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-red-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/30">
                        <FontAwesomeIcon :icon="['fas', 'funnel-dollar']" class="h-7 w-7" />
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            Pipeline de Ventas
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm">Gestiona prospectos y cierra más negocios</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Buscador -->
                    <div class="relative">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input 
                            v-model="searchTerm" 
                            type="text" 
                            placeholder="Buscar prospecto..." 
                            class="pl-10 pr-4 py-2.5 w-64 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-transparent bg-white dark:bg-slate-900/80 dark:bg-gray-800 dark:text-white backdrop-blur-sm transition-colors"
                        />
                    </div>
                    
                    <!-- Filtro Vendedor (Admin) -->
                    <select v-if="isAdmin && vendedores.length" v-model="filtroVendedor" @change="filtrarPorVendedor" class="px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 bg-white dark:bg-slate-900/80 dark:bg-gray-800 dark:text-white backdrop-blur-sm transition-colors">
                        <option value="">Todos los vendedores</option>
                        <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>
                    
                    <!-- Botones de Acción -->
                    <button @click="abrirModalNuevo" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:scale-105">
                        <FontAwesomeIcon :icon="['fas', 'plus']" />
                        Nuevo Prospecto
                    </button>
                    <Link href="/crm/tareas" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'tasks']" />
                        Mis Tareas
                        <span v-if="stats.con_actividad_pendiente" class="ml-1 px-2 py-0.5 bg-red-100 dark:bg-red-900/50 text-red-600 dark:text-red-400 text-xs font-bold rounded-full">{{ stats.con_actividad_pendiente }}</span>
                    </Link>
                    <Link v-if="isAdmin" href="/crm/metas" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'bullseye']" />
                        Metas
                    </Link>
                    <Link v-if="isAdmin" href="/crm/campanias" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-medium rounded-xl border border-gray-200 dark:border-slate-800 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'bullhorn']" />
                        Campañas
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards Premium -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Prospectos Activos</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-white">{{ stats.total_prospectos }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 text-white shadow-lg shadow-emerald-500/30">
                        <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Valor Pipeline</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ formatMonto(stats.valor_pipeline) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30">
                        <FontAwesomeIcon :icon="['fas', 'bell']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Seguimientos</p>
                        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.con_actividad_pendiente }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 text-white shadow-lg shadow-purple-500/30">
                        <FontAwesomeIcon :icon="['fas', 'trophy']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wide">Cerrados (Mes)</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.cerrados_mes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mi Meta de Hoy & Leaderboard (si hay metas) -->
        <div v-if="Object.keys(miProgreso).length || (isAdmin && leaderboard.length)" class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Mi Progreso -->
            <div v-if="Object.keys(miProgreso).length" class="lg:col-span-2 bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 dark:text-gray-200 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'bullseye']" class="text-amber-500" />
                    Mi Meta de Hoy
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="(prog, tipo) in miProgreso" :key="tipo" 
                         :class="prog.cumplida ? 'border-green-200 dark:border-green-700 bg-green-50/50 dark:bg-green-900/30' : 'border-amber-200 dark:border-amber-700 bg-amber-50/50 dark:bg-amber-900/30'"
                         class="p-4 rounded-xl border flex items-center gap-4">
                        <div class="relative">
                            <!-- Circular Progress -->
                            <svg class="w-16 h-16 transform -rotate-90">
                                <circle cx="32" cy="32" r="28" stroke-width="6" fill="none" class="stroke-gray-200 dark:stroke-gray-600"></circle>
                                <circle cx="32" cy="32" r="28" stroke-width="6" fill="none"
                                        :class="prog.cumplida ? 'stroke-green-500' : 'stroke-amber-500'"
                                        :stroke-dasharray="`${prog.porcentaje * 1.76} 176`"
                                        stroke-linecap="round"></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-sm font-bold" :class="prog.cumplida ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                                    {{ prog.porcentaje }}%
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ prog.tipo_label }}</p>
                            <p class="text-xl font-bold" :class="prog.cumplida ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white dark:text-white'">
                                {{ prog.realizado }} / {{ prog.meta }}
                            </p>
                            <p v-if="prog.cumplida" class="text-xs text-green-600 flex items-center gap-1">
                                <FontAwesomeIcon :icon="['fas', 'check-circle']" />
                                ¡Meta cumplida!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mini Leaderboard (solo admin) -->
            <div v-if="isAdmin && leaderboard.length" class="bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 dark:text-gray-200 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'trophy']" class="text-amber-500" />
                    Top Vendedores Hoy
                </h3>
                <div class="space-y-2">
                    <div v-for="(item, index) in leaderboard.slice(0, 5)" :key="item.user_id"
                         class="flex items-center gap-2 p-2 rounded-lg hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                              :class="index === 0 ? 'bg-amber-500 text-white' : index === 1 ? 'bg-gray-400 text-white' : index === 2 ? 'bg-orange-400 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 dark:text-gray-300'">
                            {{ index + 1 }}
                        </span>
                        <span class="flex-1 text-sm text-gray-700 dark:text-gray-300 truncate">{{ item.nombre }}</span>
                        <span class="text-sm font-bold" :class="item.porcentaje_cumplimiento === 100 ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-300 dark:text-gray-400'">
                            {{ item.actividades }}
                        </span>
                    </div>
                </div>
                <Link href="/crm/metas" class="block text-center text-sm text-amber-600 hover:text-amber-700 mt-3">
                    Ver todo →
                </Link>
            </div>
        </div>

        <!-- Pipeline Kanban Moderno -->
        <div class="bg-white dark:bg-slate-900/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 dark:from-gray-800 to-white dark:to-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100 dark:text-gray-200 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'columns']" class="text-amber-500" />
                        Pipeline de Ventas
                    </h3>
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            {{ totalProspectosFiltrados }} prospectos
                        </span>
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">${{ formatMonto(stats.valor_pipeline) }}</span>
                    </div>
                </div>
                <!-- Progress Bar del Pipeline -->
                <div v-if="totalProspectosFiltrados > 0" class="flex h-2 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                    <div v-for="(etapaKey) in Object.keys(props.pipeline)" :key="etapaKey"
                         :class="getEtapaBarColor(etapaKey)"
                         :style="{ width: `${((localPipeline[etapaKey]?.length || 0) / totalProspectosFiltrados) * 100}%` }"
                         :title="`${props.pipeline[etapaKey]?.label}: ${localPipeline[etapaKey]?.length || 0}`"
                         class="transition-all duration-300">
                    </div>
                </div>
            </div>
            
            <div class="p-4 overflow-x-auto">
                <div class="flex gap-4 min-w-max">
                    <!-- Columnas del Pipeline -->
                    <div v-for="(etapaData, etapaKey) in filteredPipeline" :key="etapaKey" 
                         class="flex-shrink-0 w-80 bg-white dark:bg-slate-900/80 dark:bg-gray-800/80 rounded-xl border border-gray-100 dark:border-gray-700">
                        <!-- Header de Etapa -->
                        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span :class="getEtapaDotColor(etapaKey)" class="w-3 h-3 rounded-full shadow-sm"></span>
                                    <h4 class="font-semibold text-gray-800 dark:text-gray-100 dark:text-gray-200">{{ etapaData.label }}</h4>
                                </div>
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400 bg-white dark:bg-slate-900 dark:bg-gray-700 px-2.5 py-1 rounded-lg shadow-sm">
                                    {{ etapaData.prospectos.length }}
                                </span>
                            </div>
                            <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">${{ formatMonto(etapaData.total_valor) }}</div>
                        </div>
                        
                        <!-- Cards de Prospectos (Draggable) -->
                        <draggable 
                            v-model="localPipeline[etapaKey]"
                            :group="{ name: 'prospectos', pull: true, put: true }"
                            item-key="id"
                            :animation="250"
                            ghost-class="kanban-ghost"
                            drag-class="kanban-drag"
                            chosen-class="kanban-chosen"
                            class="p-3 space-y-3 min-h-[300px] max-h-[60vh] overflow-y-auto"
                            @change="onDragChange($event, etapaKey)"
                        >
                            <template #item="{ element: prospecto }">
                                <div class="group bg-white dark:bg-slate-900 dark:bg-gray-800 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg hover:border-amber-200 dark:hover:border-amber-600 transition-all duration-200 cursor-grab active:cursor-grabbing"
                                     :class="{'ring-2 ring-red-400 ring-opacity-50': isOverdue(prospecto), 'ring-2 ring-amber-400 ring-opacity-50': isDueToday(prospecto)}">
                                
                                    <!-- Header del Card -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <!-- Avatar Inicial -->
                                            <div :class="getAvatarColor(prospecto.id)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                                {{ getInitials(prospecto.nombre) }}
                                            </div>
                                            <div class="min-w-0">
                                                <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-semibold text-gray-900 dark:text-white dark:text-white hover:text-amber-600 dark:hover:text-amber-400 truncate block max-w-[180px]">
                                                    {{ prospecto.nombre }}
                                                </Link>
                                                <p v-if="prospecto.empresa" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 truncate max-w-[180px]">{{ prospecto.empresa }}</p>
                                            </div>
                                        </div>
                                        <span :class="getPrioridadBadge(prospecto.prioridad)" class="px-2 py-0.5 text-xs font-bold rounded-lg">
                                            {{ prospecto.prioridad?.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    
                                    <!-- Valor y Vendedor -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span v-if="prospecto.valor_estimado" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                            <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="w-3 h-3" />
                                            {{ formatMonto(prospecto.valor_estimado) }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400">Sin valor</span>
                                        <span v-if="prospecto.vendedor" class="text-xs text-gray-400 dark:text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <FontAwesomeIcon :icon="['fas', 'user']" class="w-3 h-3" />
                                            {{ prospecto.vendedor.name?.split(' ')[0] }}
                                        </span>
                                    </div>
                                    
                                    <!-- Indicadores -->
                                    <div class="flex items-center gap-2 mb-3 text-xs flex-wrap">
                                        <span v-if="getDaysSinceContact(prospecto) > 7" class="flex items-center gap-1 px-2 py-1 rounded-lg bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400">
                                            <FontAwesomeIcon :icon="['fas', 'clock']" class="w-3 h-3" />
                                            {{ getDaysSinceContact(prospecto) }}d sin contacto
                                        </span>
                                        <span v-else-if="getDaysSinceContact(prospecto) > 3" class="flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400">
                                            <FontAwesomeIcon :icon="['fas', 'clock']" class="w-3 h-3" />
                                            {{ getDaysSinceContact(prospecto) }}d
                                        </span>
                                        <span v-if="prospecto.cliente_id" class="flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400">
                                            <FontAwesomeIcon :icon="['fas', 'user-check']" class="w-3 h-3" />
                                            Cliente
                                        </span>
                                        <span v-if="getCotizacionesCount(prospecto) > 0" class="flex items-center gap-1 px-2 py-1 rounded-lg bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
                                            <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="w-3 h-3" />
                                            {{ getCotizacionesCount(prospecto) }} cotiz.
                                        </span>
                                    </div>
                                    
                                    <!-- Quick Actions -->
                                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="flex items-center gap-1">
                                            <a v-if="prospecto.telefono" :href="`tel:${prospecto.telefono}`" @click.stop class="p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/50 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors" title="Llamar">
                                                <FontAwesomeIcon :icon="['fas', 'phone']" class="w-4 h-4" />
                                            </a>
                                            <a v-if="prospecto.telefono" :href="`https://wa.me/52${prospecto.telefono}`" target="_blank" @click.stop class="p-2 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/50 text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition-colors" title="WhatsApp">
                                                <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="w-4 h-4" />
                                            </a>
                                            <a v-if="prospecto.email" :href="`mailto:${prospecto.email}`" @click.stop class="p-2 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/50 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors" title="Email">
                                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-4 h-4" />
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button @click.stop="crearCotizacion(prospecto)" class="p-2 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/50 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors" title="Crear Cotización">
                                                <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="w-4 h-4" />
                                            </button>
                                            <Link :href="`/crm/prospectos/${prospecto.id}`" @click.stop class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 transition-colors" title="Ver Detalle">
                                                <FontAwesomeIcon :icon="['fas', 'eye']" class="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        
                        <!-- Empty State -->
                        <div v-if="!localPipeline[etapaKey]?.length" class="text-center py-12 text-gray-400 dark:text-gray-500 dark:text-gray-400">
                            <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-10 w-10 mb-3 opacity-50" />
                            <p class="text-sm font-medium">Sin prospectos</p>
                            <p class="text-xs mt-1">Arrastra aquí para mover</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas Pendientes (Compacto) -->
        <div v-if="tareasPendientes.length" class="mt-6 bg-white dark:bg-slate-900/70 dark:bg-gray-800/70 backdrop-blur-sm rounded-2xl border border-amber-100 dark:border-amber-900/50 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-amber-100 dark:border-amber-900/50 bg-gradient-to-r from-amber-50 dark:from-amber-900/30 to-orange-50 dark:to-orange-900/20 flex items-center justify-between">
                <h3 class="font-bold text-amber-800 dark:text-amber-400 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'tasks']" />
                    Tareas para Hoy
                </h3>
                <Link href="/crm/tareas" class="text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium">
                    Ver todas →
                </Link>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="tarea in tareasPendientes.slice(0, 3)" :key="tarea.id" class="px-6 py-3 flex items-center justify-between hover:bg-amber-50/50 dark:hover:bg-amber-900/20 transition-colors">
                    <div class="flex items-center gap-3">
                        <div :class="getTareaIconBg(tarea.tipo)" class="p-2 rounded-lg">
                            <FontAwesomeIcon :icon="['fas', getTareaIcon(tarea.tipo)]" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white dark:text-white text-sm">{{ tarea.titulo }}</p>
                            <p v-if="tarea.prospecto" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ tarea.prospecto.nombre }}</p>
                        </div>
                    </div>
                    <button @click="completarTarea(tarea)" class="p-2 rounded-lg bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/70 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'check']" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Prospecto -->
        <div v-if="showModalNuevo" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalNuevo = false">
            <div class="flex items-start justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-2xl max-w-3xl w-full p-6 animate-scale-in max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6 sticky top-0 bg-white dark:bg-slate-900 dark:bg-gray-800 pb-4 border-b dark:border-gray-700 z-10">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white flex items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white">
                                <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                            </span>
                            Nuevo Prospecto
                        </h3>
                        <button @click="showModalNuevo = false" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-300 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <FontAwesomeIcon :icon="['fas', 'times']" class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="crearProspecto" class="space-y-6">
                        <!-- Información General -->
                        <div class="border-b dark:border-gray-700 pb-6">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'user']" class="text-amber-500" />
                                Información General
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre / Razón Social *</label>
                                    <input v-model="form.nombre" type="text" required @blur="toUpper('nombre')" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" placeholder="Nombre del prospecto" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                    <input v-model="form.email" type="email" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" placeholder="email@ejemplo.com" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono (10 dígitos)</label>
                                    <input v-model="form.telefono" type="tel" maxlength="10" @input="validateTelefono" pattern="[0-9]{10}" placeholder="6621234567" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empresa</label>
                                    <input v-model="form.empresa" type="text" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lista de Precios</label>
                                    <select v-model="form.price_list_id" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="pl in catalogs.priceLists" :key="pl.value" :value="pl.value">{{ pl.text }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2 pt-6">
                                    <input v-model="form.requiere_factura" type="checkbox" id="requiere_factura" class="w-5 h-5 text-amber-500 border-gray-300 dark:border-gray-600 rounded focus:ring-amber-500" />
                                    <label for="requiere_factura" class="text-sm font-medium text-gray-700 dark:text-gray-300">¿Requiere Factura?</label>
                                </div>
                            </div>
                        </div>

                        <!-- Datos Fiscales (Condicional) -->
                        <div v-if="form.requiere_factura" class="border-b dark:border-gray-700 pb-6 animate-fade-in">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'file-invoice']" class="text-amber-500" />
                                Datos Fiscales
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo Persona</label>
                                    <select v-model="form.tipo_persona" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors">
                                        <option value="fisica">Persona Física</option>
                                        <option value="moral">Persona Moral</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">RFC</label>
                                    <input v-model="form.rfc" type="text" @blur="toUpper('rfc')" maxlength="13" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors" placeholder="XAXX010101000" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CP Fiscal (SAT 4.0)</label>
                                    <input v-model="form.domicilio_fiscal_cp" type="text" maxlength="5" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors" placeholder="83000" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Régimen Fiscal</label>
                                    <select v-model="form.regimen_fiscal" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="r in catalogs.regimenes" :key="r.value" :value="r.value">{{ r.text }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de Prospección -->
                        <div class="border-b dark:border-gray-700 pb-6">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'funnel-dollar']" class="text-amber-500" />
                                Datos de Prospección
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Origen *</label>
                                    <select v-model="form.origen" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors">
                                        <option value="referido">Referido</option>
                                        <option value="llamada_entrante">Llamada Entrante</option>
                                        <option value="web">Página Web</option>
                                        <option value="redes_sociales">Redes Sociales</option>
                                        <option value="evento">Evento</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Prioridad</label>
                                    <select v-model="form.prioridad" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors">
                                        <option value="alta">🔴 Alta</option>
                                        <option value="media">🟡 Media</option>
                                        <option value="baja">🟢 Baja</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Valor Estimado</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 dark:text-gray-400">$</span>
                                        <input v-model.number="form.valor_estimado" type="number" step="0.01" min="0" class="w-full pl-8 pr-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
                            <textarea v-model="form.notas" rows="3" class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-colors" placeholder="Notas adicionales sobre el prospecto..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t dark:border-gray-700">
                            <button type="button" @click="showModalNuevo = false" class="px-5 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl hover:from-amber-600 hover:to-orange-600 font-semibold disabled:opacity-50 transition-all flex items-center gap-2">
                                <FontAwesomeIcon v-if="procesando" :icon="['fas', 'spinner']" class="animate-spin" />
                                <FontAwesomeIcon v-else :icon="['fas', 'check']" />
                                Crear Prospecto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Confirmación de Retroceso (Premium Design) -->
        <Transition name="modal">
            <div v-if="showModalRetroceso" class="fixed inset-0 z-50 overflow-y-auto" @click.self="cancelarRetroceso">
                <div class="flex items-center justify-center min-h-screen px-4 py-8">
                    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-scale-in">
                        <!-- Header con icono de advertencia -->
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-white dark:bg-slate-900/20 backdrop-blur flex items-center justify-center">
                                    <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="h-6 w-6 text-white" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-white">¿Retroceder etapa?</h3>
                                    <p class="text-white/80 text-sm">Esta acción moverá el prospecto hacia atrás</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contenido -->
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div :class="getAvatarColor(retrocesoData.prospecto?.id || 0)" class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg">
                                    {{ getInitials(retrocesoData.prospecto?.nombre) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white dark:text-white">{{ retrocesoData.prospecto?.nombre }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ retrocesoData.prospecto?.empresa }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center gap-3 py-4 px-4 bg-white dark:bg-slate-900 dark:bg-gray-700 rounded-xl mb-4">
                                <div class="text-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 block mb-1">De</span>
                                    <span class="px-3 py-1.5 bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-400 rounded-lg font-medium text-sm">
                                        {{ retrocesoData.etapaOrigenLabel }}
                                    </span>
                                </div>
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="text-gray-400" />
                                <div class="text-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 block mb-1">A</span>
                                    <span class="px-3 py-1.5 bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 rounded-lg font-medium text-sm">
                                        {{ retrocesoData.etapaDestinoLabel }}
                                    </span>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400 text-center mb-6">
                                ¿Estás seguro de que quieres mover este prospecto a una etapa anterior?
                            </p>
                            
                            <div class="flex gap-3">
                                <button @click="cancelarRetroceso" class="flex-1 px-4 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all">
                                    <FontAwesomeIcon :icon="['fas', 'times']" class="mr-2" />
                                    Cancelar
                                </button>
                                <button @click="confirmarRetroceso" class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl hover:from-amber-600 hover:to-orange-600 font-semibold transition-all shadow-lg shadow-amber-500/30">
                                    <FontAwesomeIcon :icon="['fas', 'check']" class="mr-2" />
                                    Sí, retroceder
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import draggable from 'vuedraggable';

defineOptions({ layout: AppLayout });

const props = defineProps({
    pipeline: Object,
    stats: Object,
    tareasPendientes: Array,
    vendedores: Array,
    filtros: Object,
    etapas: Object,
    isAdmin: Boolean,
    catalogs: Object,
    miProgreso: Object,
    leaderboard: Array,
});

const showModalNuevo = ref(false);
const procesando = ref(false);
const filtroVendedor = ref(props.filtros?.vendedor_id || '');
const searchTerm = ref('');

// Estado para modal de retroceso
const showModalRetroceso = ref(false);
const retrocesoData = ref({
    prospecto: null,
    etapaOrigen: '',
    etapaDestino: '',
    etapaOrigenLabel: '',
    etapaDestinoLabel: '',
});

const initForm = () => ({
    nombre: '',
    telefono: '',
    email: '',
    empresa: '',
    origen: 'llamada_entrante',
    prioridad: 'media',
    valor_estimado: null,
    notas: '',
    price_list_id: '',
    requiere_factura: false,
    tipo_persona: 'fisica',
    rfc: '',
    domicilio_fiscal_cp: '',
    regimen_fiscal: '',
});

const form = ref(initForm());

// Reactive local pipeline para drag-and-drop
const localPipeline = ref({});

// Inicializar localPipeline desde props.pipeline
const initLocalPipeline = () => {
    const result = {};
    for (const [etapa, data] of Object.entries(props.pipeline || {})) {
        result[etapa] = [...(data.prospectos || [])];
    }
    localPipeline.value = result;
};

// Inicializar al montar
initLocalPipeline();

// Re-sincronizar cuando el pipeline cambie desde el servidor
watch(() => props.pipeline, initLocalPipeline, { deep: true });

// Orden de etapas para detectar retrocesos
const etapasOrden = ['prospecto', 'contactado', 'interesado', 'cotizado', 'negociacion', 'cerrado_ganado', 'cerrado_perdido'];

// Manejar cambios de drag-and-drop
const onDragChange = (event, etapaDestino) => {
    // Solo procesar cuando se agrega un elemento (significa que se movió a esta columna)
    if (event.added) {
        const prospecto = event.added.element;
        const etapaOrigen = prospecto.etapa;
        
        // Detectar si es un retroceso
        const indexOrigen = etapasOrden.indexOf(etapaOrigen);
        const indexDestino = etapasOrden.indexOf(etapaDestino);
        const esRetroceso = indexDestino < indexOrigen && indexDestino >= 0 && indexOrigen >= 0;
        
        // Si es retroceso, mostrar modal de confirmación
        if (esRetroceso) {
            retrocesoData.value = {
                prospecto: prospecto,
                etapaOrigen: etapaOrigen,
                etapaDestino: etapaDestino,
                etapaOrigenLabel: props.pipeline[etapaOrigen]?.label || etapaOrigen,
                etapaDestinoLabel: props.pipeline[etapaDestino]?.label || etapaDestino,
            };
            showModalRetroceso.value = true;
            return; // No hacer nada hasta que confirme
        }
        
        // Si no es retroceso, mover directamente
        moverProspecto(prospecto.id, etapaDestino);
    }
};

// Confirmar retroceso desde el modal
const confirmarRetroceso = () => {
    const { prospecto, etapaDestino } = retrocesoData.value;
    showModalRetroceso.value = false;
    moverProspecto(prospecto.id, etapaDestino);
};

// Cancelar retroceso
const cancelarRetroceso = () => {
    showModalRetroceso.value = false;
    initLocalPipeline(); // Revertir visualmente
};

// Función común para mover prospecto
const moverProspecto = (prospectoId, etapaDestino) => {
    router.patch(`/crm/prospectos/${prospectoId}/etapa`, {
        etapa: etapaDestino
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            console.error('Error al mover prospecto:', errors);
            initLocalPipeline();
        }
    });
};

// Computed: Pipeline filtrado por búsqueda
const filteredPipeline = computed(() => {
    if (!searchTerm.value) return props.pipeline;
    
    const search = searchTerm.value.toLowerCase();
    const filtered = {};
    
    for (const [etapa, data] of Object.entries(props.pipeline)) {
        filtered[etapa] = {
            ...data,
            prospectos: data.prospectos.filter(p => 
                p.nombre?.toLowerCase().includes(search) ||
                p.empresa?.toLowerCase().includes(search) ||
                p.telefono?.includes(search)
            )
        };
    }
    return filtered;
});

const totalProspectosFiltrados = computed(() => {
    return Object.values(localPipeline.value).reduce((sum, arr) => sum + (arr?.length || 0), 0);
});

// Formatters
const formatMonto = (valor) => Number(valor || 0).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

// Helpers visuales
const getEtapaDotColor = (etapa) => ({
    prospecto: 'bg-gray-400',
    contactado: 'bg-blue-500',
    interesado: 'bg-yellow-500',
    cotizado: 'bg-purple-500',
    negociacion: 'bg-orange-500'
}[etapa] || 'bg-gray-400');

const getEtapaBarColor = (etapa) => ({
    prospecto: 'bg-gray-300',
    contactado: 'bg-blue-400',
    interesado: 'bg-yellow-400',
    cotizado: 'bg-purple-400',
    negociacion: 'bg-orange-400'
}[etapa] || 'bg-gray-300');

const getPrioridadBadge = (prioridad) => ({
    alta: 'bg-red-100 text-red-700',
    media: 'bg-amber-100 text-amber-700',
    baja: 'bg-green-100 text-green-700'
}[prioridad] || 'bg-gray-100 text-gray-700');

const getAvatarColor = (id) => {
    const colors = [
        'bg-gradient-to-br from-blue-500 to-blue-600',
        'bg-gradient-to-br from-purple-500 to-purple-600',
        'bg-gradient-to-br from-emerald-500 to-emerald-600',
        'bg-gradient-to-br from-amber-500 to-amber-600',
        'bg-gradient-to-br from-rose-500 to-rose-600',
        'bg-gradient-to-br from-cyan-500 to-cyan-600',
    ];
    return colors[id % colors.length];
};

const getInitials = (nombre) => {
    if (!nombre) return '?';
    const parts = nombre.split(' ');
    return parts.length > 1 ? (parts[0][0] + parts[1][0]).toUpperCase() : nombre.substring(0, 2).toUpperCase();
};

const getDaysSinceContact = (prospecto) => {
    // Usar ultima_actividad_at o created_at como fallback
    const fechaReferencia = prospecto.ultima_actividad_at || prospecto.created_at;
    if (!fechaReferencia) return 0;
    const lastContact = new Date(fechaReferencia);
    const now = new Date();
    const days = Math.floor((now - lastContact) / (1000 * 60 * 60 * 24));
    return Math.max(0, days); // Nunca valores negativos
};

const isOverdue = (prospecto) => {
    if (!prospecto.proxima_actividad_at) return false;
    return new Date(prospecto.proxima_actividad_at) < new Date();
};

const isDueToday = (prospecto) => {
    if (!prospecto.proxima_actividad_at) return false;
    const dueDate = new Date(prospecto.proxima_actividad_at).toDateString();
    return dueDate === new Date().toDateString();
};

const getCotizacionesCount = (prospecto) => {
    return prospecto.cliente?.cotizaciones?.length || 0;
};

const getTareaIcon = (tipo) => ({ llamar: 'phone', enviar_cotizacion: 'file-invoice-dollar', seguimiento: 'redo', visita: 'building', reunion: 'users' }[tipo] || 'tasks');
const getTareaIconBg = (tipo) => ({ llamar: 'bg-blue-100 text-blue-600', enviar_cotizacion: 'bg-purple-100 text-purple-600', seguimiento: 'bg-amber-100 text-amber-600', visita: 'bg-green-100 text-green-600', reunion: 'bg-orange-100 text-orange-600' }[tipo] || 'bg-gray-100 text-gray-600 dark:text-gray-300');

// Actions
const toUpper = (field) => { if (form.value[field]) form.value[field] = form.value[field].toUpperCase(); };
const validateTelefono = () => { form.value.telefono = form.value.telefono.replace(/\D/g, '').slice(0, 10); };

const abrirModalNuevo = () => {
    form.value = initForm();
    showModalNuevo.value = true;
};

const filtrarPorVendedor = () => {
    router.get('/crm', { vendedor_id: filtroVendedor.value || undefined }, { preserveState: true });
};

const crearProspecto = () => {
    procesando.value = true;
    router.post('/crm/prospectos', form.value, {
        onSuccess: () => {
            showModalNuevo.value = false;
            form.value = initForm();
            procesando.value = false;
        },
        onError: () => { procesando.value = false; },
    });
};

const completarTarea = (tarea) => {
    router.patch(`/crm/tareas/${tarea.id}/completar`, {}, { preserveState: true });
};

// Integración con Cotizaciones
const crearCotizacion = (prospecto) => {
    // Si el prospecto tiene cliente asociado, ir directo a crear cotización
    if (prospecto.cliente_id) {
        router.visit(`/cotizaciones/create?cliente_id=${prospecto.cliente_id}&prospecto_id=${prospecto.id}`);
    } else {
        // Si no tiene cliente, primero convertirlo
        if (confirm(`El prospecto "${prospecto.nombre}" no tiene cliente asociado. ¿Desea convertirlo a cliente primero?`)) {
            router.post(`/crm/prospectos/${prospecto.id}/convertir`, {}, {
                onSuccess: () => {
                    // Después de convertir, recargar y obtener el cliente_id
                    router.reload();
                }
            });
        }
    }
};
</script>

<style scoped>
.animate-scale-in {
    animation: scale-in 0.2s ease-out;
}

@keyframes scale-in {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Custom scrollbar for pipeline columns */
.max-h-\[60vh\]::-webkit-scrollbar {
    width: 4px;
}
.max-h-\[60vh\]::-webkit-scrollbar-track {
    background: transparent;
}
.max-h-\[60vh\]::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 2px;
}
.max-h-\[60vh\]::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Kanban Drag-and-Drop Styles (Notion-like) */
.kanban-ghost {
    opacity: 0.4;
    background: linear-gradient(135deg, #fef3c7, #fed7aa) !important;
    border: 2px dashed #f59e0b !important;
    border-radius: 0.75rem;
    box-shadow: none !important;
}

.kanban-drag {
    transform: rotate(3deg) scale(1.02);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2), 
                0 0 0 3px rgba(245, 158, 11, 0.3) !important;
    z-index: 9999 !important;
    cursor: grabbing !important;
}

.kanban-chosen {
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.5) !important;
    background: #fffbeb !important;
}

/* Smooth transitions for all cards */
.sortable-chosen {
    transition: transform 0.15s ease, box-shadow 0.15s ease !important;
}

/* Drop placeholder animation */
.sortable-ghost {
    transition: all 0.2s ease !important;
}
</style>
