<template>
    <Head title="CRM - Pipeline de Ventas" />

    <div class="min-h-screen bg-[var(--ui-surface)] p-4 md:p-6 transition-colors">
        <!-- Header Premium -->
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 via-brand-500 to-rose-500 flex items-center justify-center text-white shadow-xl shadow-brand-500/30">
                        <FontAwesomeIcon :icon="['fas', 'funnel-dollar']" class="h-7 w-7" />
                    </div>
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-300 bg-clip-text text-transparent">
                            Pipeline de Ventas
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">Gestiona prospectos y cierra más negocios</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Buscador -->
                    <div class="relative">
                        <FontAwesomeIcon :icon="['fas', 'search']" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                        <input 
                            v-model="searchTerm" 
                            type="text" 
                            placeholder="Buscar prospecto..." 
                            class="pl-10 pr-4 py-2.5 w-64 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 focus:border-transparent bg-white/80 dark:bg-slate-800 dark:text-white backdrop-blur-sm transition-colors"
                        />
                    </div>
                    
                    <!-- Filtro Vendedor (Admin) -->
                    <select v-if="isAdmin && vendedores.length" v-model="filtroVendedor" @change="filtrarPorVendedor" class="px-4 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-brand-500 bg-white/80 dark:bg-slate-800 dark:text-white backdrop-blur-sm transition-colors">
                        <option value="">Todos los vendedores</option>
                        <option v-for="v in vendedores" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>
                    
                    <!-- Botones de Acción -->
                    <button @click="abrirModalNuevo" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-500 text-white font-semibold rounded-xl hover:from-brand-600 hover:to-brand-600 transition-all shadow-xl shadow-brand-500/25 hover:shadow-brand-500/40 hover:scale-105">
                        <FontAwesomeIcon :icon="['fas', 'plus']" />
                        Nuevo Prospecto
                    </button>
                    <Link href="/crm/prospectos" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'list']" />
                        Ver Lista
                    </Link>
                    <Link href="/crm/tareas" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'tasks']" />
                        Mis Tareas
                        <span v-if="stats.con_actividad_pendiente" class="ml-1 px-2 py-0.5 bg-rose-50 dark:bg-rose-900/20/50 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-full">{{ stats.con_actividad_pendiente }}</span>
                    </Link>
                    <Link v-if="isAdmin" href="/crm/metas" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'bullseye']" />
                        Metas
                    </Link>
                    <Link v-if="isAdmin" href="/crm/campanias" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-brand-500 dark:hover:border-brand-500 transition-all">
                        <FontAwesomeIcon :icon="['fas', 'bullhorn']" />
                        Campañas
                    </Link>
                </div>
            </div>
        </div>

        <!-- Stats Cards Premium -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-blue-500/30">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Prospectos Activos</p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total_prospectos }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-emerald-500/20">
                        <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Valor Pipeline</p>
                        <p class="text-2xl font-bold text-emerald-600 dark:text-slate-400">${{ formatMonto(stats.valor_pipeline) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-brand-500/30">
                        <FontAwesomeIcon :icon="['fas', 'bell']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Seguimientos</p>
                        <p class="text-2xl font-bold text-brand-600 dark:text-amber-400">{{ stats.con_actividad_pendiente }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl transition-all">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 text-white shadow-xl shadow-purple-500/30">
                        <FontAwesomeIcon :icon="['fas', 'trophy']" class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Cerrados (Mes)</p>
                        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.cerrados_mes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mi Meta de Hoy & Leaderboard (si hay metas) -->
        <div v-if="Object.keys(miProgreso).length || (isAdmin && leaderboard.length)" class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <!-- Mi Progreso -->
            <div v-if="Object.keys(miProgreso).length" class="lg:col-span-2 bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'bullseye']" class="text-brand-500" />
                    Mi Meta de Hoy
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-for="(prog, tipo) in miProgreso" :key="tipo" 
                         :class="prog.cumplida ? 'border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/30' : 'border-brand-200 dark:border-brand-800/30 dark:border-brand-700 bg-brand-50 dark:bg-brand-900/20/50 dark:bg-brand-900/30'"
                         class="p-4 rounded-xl border flex items-center gap-4">
                        <div class="relative">
                            <!-- Circular Progress -->
                            <svg class="w-16 h-16 transform -rotate-90">
                                <circle cx="32" cy="32" r="28" stroke-width="6" fill="none" class="stroke-slate-200 dark:stroke-slate-600"></circle>
                                <circle cx="32" cy="32" r="28" stroke-width="6" fill="none"
                                        :class="prog.cumplida ? 'stroke-emerald-500' : 'stroke-brand-500'"
                                        :stroke-dasharray="`${prog.porcentaje * 1.76} 176`"
                                        stroke-linecap="round"></circle>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-sm font-bold" :class="prog.cumplida ? 'text-emerald-600 dark:text-slate-400' : 'text-brand-600 dark:text-amber-400'">
                                    {{ prog.porcentaje }}%
                                </span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ prog.tipo_label }}</p>
                            <p class="text-xl font-bold" :class="prog.cumplida ? 'text-emerald-600 dark:text-slate-400' : 'text-slate-900 dark:text-white'">
                                {{ prog.realizado }} / {{ prog.meta }}
                            </p>
                            <p v-if="prog.cumplida" class="text-xs text-emerald-600 flex items-center gap-1">
                                <FontAwesomeIcon :icon="['fas', 'check-circle']" />
                                ¡Meta cumplida!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mini Leaderboard (solo admin) -->
            <div v-if="isAdmin && leaderboard.length" class="bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm p-5 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'trophy']" class="text-brand-500" />
                    Top Vendedores Hoy
                </h3>
                <div class="space-y-2">
                    <div v-for="(item, index) in leaderboard.slice(0, 5)" :key="item.user_id"
                         class="flex items-center gap-2 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-700">
                        <span class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold"
                              :class="index === 0 ? 'bg-brand-500 text-white' : index === 1 ? 'bg-slate-400 text-white' : index === 2 ? 'bg-orange-400 text-white' : 'bg-slate-200 dark:bg-slate-600 text-slate-500 dark:text-slate-200'">
                            {{ index + 1 }}
                        </span>
                        <span class="flex-1 text-sm text-slate-700 dark:text-slate-200 truncate">{{ item.nombre }}</span>
                        <span class="text-sm font-bold" :class="item.porcentaje_cumplimiento === 100 ? 'text-emerald-600 dark:text-slate-400' : 'text-slate-500 dark:text-slate-400'">
                            {{ item.actividades }}
                        </span>
                    </div>
                </div>
                <Link href="/crm/metas" class="block text-center text-sm text-brand-600 hover:text-brand-800 dark:text-brand-200 dark:text-brand-200 mt-3">
                    Ver todo →
                </Link>
            </div>
        </div>

        <!-- Pipeline Kanban Moderno -->
        <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-gradient-to-r from-slate-50 dark:from-slate-800 to-white dark:to-slate-900">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
                        <FontAwesomeIcon :icon="['fas', 'columns']" class="text-brand-500" />
                        Pipeline de Ventas
                    </h3>
                    <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                            {{ totalProspectosFiltrados }} prospectos
                        </span>
                        <span class="font-semibold text-emerald-600 dark:text-slate-400">${{ formatMonto(stats.valor_pipeline) }}</span>
                    </div>
                </div>
                <!-- Progress Bar del Pipeline -->
                <div v-if="totalProspectosFiltrados > 0" class="flex h-2 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-700">
                    <div v-for="(etapaKey) in Object.keys(props.pipeline)" :key="etapaKey"
                         :class="getEtapaBarColor(etapaKey)"
                         :style="{ width: `${((localPipeline[etapaKey]?.length || 0) / totalProspectosFiltrados) * 100}%` }"
                         :title="`${props.pipeline[etapaKey]?.label}: ${localPipeline[etapaKey]?.length || 0}`"
                         class="transition-all duration-200">
                    </div>
                </div>
            </div>
            
            <div class="p-4 overflow-x-auto">
                <div class="flex gap-4 min-w-max">
                    <!-- Columnas del Pipeline -->
                    <div v-for="(etapaData, etapaKey) in filteredPipeline" :key="etapaKey" 
                         class="flex-shrink-0 w-80 bg-white/80 dark:bg-slate-800/80 rounded-xl border border-slate-100 dark:border-slate-700">
                        <!-- Header de Etapa -->
                        <div class="p-4 border-b border-slate-100 dark:border-slate-700">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span :class="getEtapaDotColor(etapaKey)" class="w-2 h-2 rounded-full shadow-sm"></span>
                                    <h4 class="font-semibold text-slate-800 dark:text-slate-200">{{ etapaData.label }}</h4>
                                </div>
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-700 px-2.5 py-1 rounded-2xl shadow-sm">
                                    {{ etapaData.prospectos.length }}
                                </span>
                            </div>
                            <div class="text-sm font-semibold text-emerald-600 dark:text-slate-400">${{ formatMonto(etapaData.total_valor) }}</div>
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
                                <div class="group bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm hover:shadow-xl hover:border-brand-200 dark:border-brand-800/30 dark:hover:border-brand-600 transition-all duration-200 cursor-grab active:cursor-grabbing"
                                     :class="{'ring-2 ring-rose-400 ring-opacity-50': isOverdue(prospecto), 'ring-2 ring-brand-400 ring-opacity-50': isDueToday(prospecto)}">
                                
                                    <!-- Header del Card -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <!-- Avatar Inicial -->
                                            <div :class="getAvatarColor(prospecto.id)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                                {{ getInitials(prospecto.nombre) }}
                                            </div>
                                            <div class="min-w-0">
                                                <Link :href="`/crm/prospectos/${prospecto.id}`" class="font-semibold text-slate-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 truncate block max-w-[180px]">
                                                    {{ prospecto.nombre }}
                                                </Link>
                                                <p v-if="prospecto.empresa" class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-[180px]">{{ prospecto.empresa }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span :class="getPrioridadBadge(prospecto.prioridad)" class="px-2 py-0.5 text-xs font-bold rounded-xl mb-1">
                                                {{ prospecto.prioridad?.charAt(0).toUpperCase() }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">
                                                {{ formatFechaShort(prospecto.created_at) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Valor y Vendedor -->
                                    <div class="flex items-center justify-between mb-3">
                                        <span v-if="prospecto.valor_estimado" class="text-sm font-bold text-emerald-600 dark:text-slate-400 flex items-center gap-1">
                                            <FontAwesomeIcon :icon="['fas', 'dollar-sign']" class="w-3 h-3" />
                                            {{ formatMonto(prospecto.valor_estimado) }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400 dark:text-slate-500">Sin valor</span>
                                        <span v-if="prospecto.vendedor" class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1">
                                            <FontAwesomeIcon :icon="['fas', 'user']" class="w-3 h-3" />
                                            {{ prospecto.vendedor.name?.split(' ')[0] }}
                                        </span>
                                    </div>
                                    
                                    <!-- Indicadores -->
                                    <div class="flex items-center gap-2 mb-3 text-xs flex-wrap">
                                        <span v-if="getDaysSinceContact(prospecto) > 7" class="flex items-center gap-1 px-2 py-1 rounded-xl bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400">
                                            <FontAwesomeIcon :icon="['fas', 'clock']" class="w-3 h-3" />
                                            {{ getDaysSinceContact(prospecto) }}d sin contacto
                                        </span>
                                        <span v-else-if="getDaysSinceContact(prospecto) > 3" class="flex items-center gap-1 px-2 py-1 rounded-xl bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/40 text-brand-600 dark:text-amber-400">
                                            <FontAwesomeIcon :icon="['fas', 'clock']" class="w-3 h-3" />
                                            {{ getDaysSinceContact(prospecto) }}d
                                        </span>
                                        <span v-if="prospecto.cliente_id" class="flex items-center gap-1 px-2 py-1 rounded-xl bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/40 text-blue-600 dark:text-blue-400">
                                            <FontAwesomeIcon :icon="['fas', 'user-check']" class="w-3 h-3" />
                                            Cliente
                                        </span>
                                        <span v-if="getCotizacionesCount(prospecto) > 0" class="flex items-center gap-1 px-2 py-1 rounded-xl bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400">
                                            <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="w-3 h-3" />
                                            {{ getCotizacionesCount(prospecto) }} cotiz.
                                        </span>
                                    </div>
                                    
                                    <!-- Quick Actions -->
                                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-700 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="flex items-center gap-1">
                                            <a v-if="prospecto.telefono" :href="`tel:${prospecto.telefono}`" @click.stop class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-blue-900/50 text-slate-400 hover:text-brand-600 dark:hover:text-blue-400 transition-colors" title="Llamar">
                                                <FontAwesomeIcon :icon="['fas', 'phone']" class="w-4 h-4" />
                                            </a>
                                            <Link v-if="prospecto.telefono" :href="`/marketing/whatsapp-inbox?wa=${prospecto.telefono}`" @click.stop class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-emerald-900/50 text-slate-400 hover:text-emerald-500 transition-colors" title="Abrir Chat en Inbox Interno">
                                                <FontAwesomeIcon :icon="['fab', 'whatsapp']" class="w-4 h-4" />
                                            </Link>
                                            <a v-if="prospecto.email" :href="`mailto:${prospecto.email}`" @click.stop class="p-2 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/50 text-slate-400 hover:text-brand-600 dark:hover:text-purple-400 transition-colors" title="Email">
                                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="w-4 h-4" />
                                            </a>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button @click.stop="crearCotizacion(prospecto)" class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-brand-900/50 text-slate-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors" title="Crear Cotización">
                                                <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="w-4 h-4" />
                                            </button>
                                            <button @click.stop="eliminarProspecto(prospecto)" 
                                                    class="p-2 rounded-xl hover:bg-slate-50 dark:hover:bg-rose-900/50 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors" 
                                                    title="Eliminar Prospecto">
                                                <FontAwesomeIcon :icon="['fas', 'trash']" class="w-4 h-4" />
                                            </button>
                                            <Link :href="`/crm/prospectos/${prospecto.id}`" @click.stop class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-400 hover:text-brand-600 dark:hover:text-slate-300 transition-colors" title="Ver Detalle">
                                                <FontAwesomeIcon :icon="['fas', 'eye']" class="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                        
                        <!-- Empty State -->
                        <div v-if="!localPipeline[etapaKey]?.length" class="py-12 text-center text-slate-400 dark:text-slate-500">
                            <FontAwesomeIcon :icon="['fas', 'inbox']" class="h-10 w-10 mb-3 opacity-50" />
                            <p class="text-sm font-medium">Sin prospectos</p>
                            <p class="text-xs mt-1">Arrastra aquí para mover</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas Pendientes (Compacto) -->
        <div v-if="tareasPendientes.length" class="mt-6 bg-white/70 dark:bg-slate-800/50 backdrop-blur-sm rounded-2xl border border-brand-100 dark:border-brand-900/50 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-brand-100 dark:border-brand-900/50 bg-gradient-to-r from-brand-50 dark:from-brand-900/30 to-orange-50 dark:to-orange-900/20 flex items-center justify-between">
                <h3 class="font-bold text-brand-800 dark:text-brand-200 dark:text-brand-400 flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'tasks']" />
                    Tareas para Hoy
                </h3>
                <Link href="/crm/tareas" class="text-sm text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:hover:text-brand-300 font-medium">
                    Ver todas →
                </Link>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                <div v-for="tarea in tareasPendientes.slice(0, 3)" :key="tarea.id" class="px-6 py-3 flex items-center justify-between hover:bg-slate-50/50 dark:hover:bg-brand-900/20 transition-colors">
                    <div class="flex items-center gap-2">
                        <div :class="getTareaIconBg(tarea.tipo)" class="p-2 rounded-xl">
                            <FontAwesomeIcon :icon="['fas', getTareaIcon(tarea.tipo)]" class="w-4 h-4" />
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white text-sm">{{ tarea.titulo }}</p>
                            <p v-if="tarea.prospecto" class="text-xs text-slate-500 dark:text-slate-400">{{ tarea.prospecto.nombre }}</p>
                        </div>
                    </div>
                    <button @click="completarTarea(tarea)" class="p-2 rounded-xl bg-emerald-100 dark:bg-slate-800/50 text-emerald-600 dark:text-slate-400 hover:bg-emerald-200 dark:hover:bg-emerald-900/70 transition-colors">
                        <FontAwesomeIcon :icon="['fas', 'check']" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Nuevo Prospecto -->
        <div v-if="showModalNuevo" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showModalNuevo = false">
            <div class="flex items-start justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
                
                <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-3xl w-full p-6 animate-scale-in max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-6 sticky top-0 bg-white dark:bg-slate-800 pb-4 border-b dark:border-slate-700 z-10">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center text-white">
                                <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                            </span>
                            Nuevo Prospecto
                        </h3>
                        <button @click="showModalNuevo = false" class="text-slate-400 hover:text-brand-600 dark:hover:text-slate-300 p-2 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl transition-colors">
                            <FontAwesomeIcon :icon="['fas', 'times']" class="w-4 h-4" />
                        </button>
                    </div>

                    <form @submit.prevent="crearProspecto" class="space-y-6">
                        <!-- Información General -->
                        <div class="border-b dark:border-slate-700 pb-6">
                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'user']" class="text-brand-500" />
                                Información General
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nombre / Razón Social *</label>
                                    <input v-model="form.nombre" type="text" required @blur="toUpper('nombre')" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" placeholder="Nombre del prospecto" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Email</label>
                                    <input v-model="form.email" type="email" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" placeholder="email@ejemplo.com" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Teléfono (10 dígitos)</label>
                                    <input v-model="form.telefono" type="tel" maxlength="10" @input="validateTelefono" pattern="[0-9]{10}" placeholder="6621234567" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Empresa</label>
                                    <input v-model="form.empresa" type="text" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Lista de Precios</label>
                                    <select v-model="form.price_list_id" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="pl in catalogs.priceLists" :key="pl.value" :value="pl.value">{{ pl.text }}</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-2 pt-6">
                                    <input v-model="form.requiere_factura" type="checkbox" id="requiere_factura" class="w-4 h-4 text-brand-500 border-slate-300 dark:border-slate-700 rounded-xl focus:ring-brand-500" />
                                    <label for="requiere_factura" class="text-sm font-medium text-slate-700 dark:text-slate-200">¿Requiere Factura?</label>
                                </div>
                            </div>
                        </div>

                        <!-- Datos Fiscales (Condicional) -->
                        <div v-if="form.requiere_factura" class="border-b dark:border-slate-700 pb-6 animate-fade-in">
                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'file-invoice']" class="text-brand-500" />
                                Datos Fiscales
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tipo Persona</label>
                                    <select v-model="form.tipo_persona" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors">
                                        <option value="fisica">Persona Física</option>
                                        <option value="moral">Persona Moral</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">RFC</label>
                                    <input v-model="form.rfc" type="text" @blur="toUpper('rfc')" maxlength="13" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors" placeholder="XAXX010101000" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">CP Fiscal (SAT 4.0)</label>
                                    <input v-model="form.domicilio_fiscal_cp" type="text" maxlength="5" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors" placeholder="83000" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Régimen Fiscal</label>
                                    <select v-model="form.regimen_fiscal" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 transition-colors">
                                        <option value="">Seleccionar...</option>
                                        <option v-for="r in catalogs.regimenes" :key="r.value" :value="r.value">{{ r.text }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Datos de Prospección -->
                        <div class="border-b dark:border-slate-700 pb-6">
                            <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <FontAwesomeIcon :icon="['fas', 'funnel-dollar']" class="text-brand-500" />
                                Datos de Prospección
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Origen *</label>
                                    <select v-model="form.origen" required class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                                        <option value="referido">Referido</option>
                                        <option value="llamada_entrante">Llamada Entrante</option>
                                        <option value="web">Página Web</option>
                                        <option value="redes_sociales">Redes Sociales</option>
                                        <option value="evento">Evento</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Prioridad</label>
                                    <select v-model="form.prioridad" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors">
                                        <option value="alta">🔴 Alta</option>
                                        <option value="media">🟡 Media</option>
                                        <option value="baja">🟢 Baja</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Valor Estimado</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500">$</span>
                                        <input v-model.number="form.valor_estimado" type="number" step="0.01" min="0" class="w-full pl-8 pr-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Notas</label>
                            <textarea v-model="form.notas" rows="3" class="w-full px-4 py-2.5 border border-slate-200 dark:border-slate-700 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-colors" placeholder="Notas adicionales sobre el prospecto..."></textarea>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t dark:border-slate-700">
                            <button type="button" @click="showModalNuevo = false" class="px-5 py-2.5 text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 font-medium transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="procesando" class="px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-500 text-white rounded-xl hover:from-brand-600 hover:to-brand-600 font-semibold disabled:opacity-50 transition-all flex items-center gap-2">
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
                    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
                    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full overflow-hidden animate-scale-in">
                        <!-- Header con icono de advertencia -->
                        <div class="bg-gradient-to-r from-brand-500 to-brand-500 px-6 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur flex items-center justify-center">
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
                                <div :class="getAvatarColor(retrocesoData.prospecto?.id || 0)" class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-xl">
                                    {{ getInitials(retrocesoData.prospecto?.nombre) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ retrocesoData.prospecto?.nombre }}</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ retrocesoData.prospecto?.empresa }}</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center gap-3 py-4 px-4 bg-white dark:bg-slate-700 rounded-xl mb-4">
                                <div class="text-center">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">De</span>
                                    <span class="px-3 py-1.5 bg-emerald-100 dark:bg-slate-800/50 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 rounded-xl font-medium text-sm">
                                        {{ retrocesoData.etapaOrigenLabel }}
                                    </span>
                                </div>
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="text-slate-400" />
                                <div class="text-center">
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block mb-1">A</span>
                                    <span class="px-3 py-1.5 bg-brand-50 dark:bg-brand-900/20/50 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 rounded-xl font-medium text-sm">
                                        {{ retrocesoData.etapaDestinoLabel }}
                                    </span>
                                </div>
                            </div>
                            
                            <p class="text-sm text-slate-500 dark:text-slate-400 text-center mb-6">
                                ¿Estás seguro de que quieres mover este prospecto a una etapa anterior?
                            </p>
                            
                            <div class="flex gap-3">
                                <button @click="cancelarRetroceso" class="flex-1 px-4 py-3 text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 rounded-xl hover:bg-slate-200 dark:hover:bg-slate-600 font-medium transition-all">
                                    <FontAwesomeIcon :icon="['fas', 'times']" class="mr-2" />
                                    Cancelar
                                </button>
                                <button @click="confirmarRetroceso" class="flex-1 px-4 py-3 bg-gradient-to-r from-brand-500 to-brand-500 text-white rounded-xl hover:from-brand-600 hover:to-brand-600 font-semibold transition-all shadow-xl shadow-brand-500/30">
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
import Swal from '@/Utils/Swal';

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
    prospecto: 'bg-slate-400',
    contactado: 'bg-brand-500',
    interesado: 'bg-brand-500',
    cotizado: 'bg-purple-500',
    negociacion: 'bg-orange-500'
}[etapa] || 'bg-slate-400');

const getEtapaBarColor = (etapa) => ({
    prospecto: 'bg-slate-300',
    contactado: 'bg-blue-400',
    interesado: 'bg-amber-400',
    cotizado: 'bg-purple-400',
    negociacion: 'bg-orange-400'
}[etapa] || 'bg-slate-300');

const getPrioridadBadge = (prioridad) => ({
    alta: 'bg-rose-100 text-rose-800 dark:text-rose-200 dark:text-rose-200',
    media: 'bg-brand-100 text-brand-800 dark:text-brand-200 dark:text-amber-200',
    baja: 'bg-emerald-100 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200'
}[prioridad] || 'bg-slate-100 text-slate-700');

const getAvatarColor = (id) => {
    const colors = [
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
        'bg-gradient-to-br from-brand-500 to-amber-600',
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

const formatFechaShort = (fecha) => {
    if (!fecha) return '';
    const d = new Date(fecha);
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit' });
};
const getTareaIconBg = (tipo) => ({ llamar: 'bg-sky-100 text-sky-700', enviar_cotizacion: 'bg-purple-100 text-purple-600', seguimiento: 'bg-brand-100 text-amber-600', visita: 'bg-emerald-100 text-emerald-600', reunion: 'bg-brand-100 text-amber-800' }[tipo] || 'bg-slate-100 text-slate-500');

// Actions
const toUpper = (field) => { if (form.value[field]) form.value[field] = form.value[field].toUpperCase(); };
const validateTelefono = () => { form.value.telefono = form.value.telefono.replace(/\D/g, '').slice(0, 10); };

const eliminarProspecto = async (prospecto) => {
    if (prospecto.cliente_id) {
        await Swal.fire({ title: 'Operación no permitida', text: 'No se puede eliminar un prospecto que ya fue convertido a cliente.', icon: 'error', confirmButtonText: 'Aceptar' });
        return;
    }

    const { isConfirmed } = await Swal.fire({ title: 'Confirmar eliminación', text: `¿Estás seguro de que deseas eliminar a "${prospecto.nombre}"? Esta acción no se puede deshacer.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'No' });
    if (!isConfirmed) return;

    router.delete(`/crm/prospectos/${prospecto.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // El pipeline se actualizará automáticamente vía Inertia
        }
    });
};

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
const crearCotizacion = async (prospecto) => {
    // Si el prospecto tiene cliente asociado, ir directo a crear cotización
    if (prospecto.cliente_id) {
        router.visit(`/cotizaciones/create?cliente_id=${prospecto.cliente_id}&prospecto_id=${prospecto.id}`);
    } else {
        // Si no tiene cliente, primero convertirlo
        const { isConfirmed } = await Swal.fire({ title: 'Convertir a cliente', text: `El prospecto "${prospecto.nombre}" no tiene cliente asociado. ¿Desea convertirlo a cliente primero?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, convertir', cancelButtonText: 'No' });
        if (!isConfirmed) return;

        router.post(`/crm/prospectos/${prospecto.id}/convertir`, {}, {
            onSuccess: () => {
                // Después de convertir, recargar y obtener el cliente_id
                router.reload();
            }
        });
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
    width: 6px;
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
