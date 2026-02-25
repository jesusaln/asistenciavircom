<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleado: { type: Object, required: true },
  nominasRecientes: { type: Array, default: () => [] },
  resumenAnual: { type: Object, default: () => ({}) },
  vacacionesResumen: { type: Object, default: () => ({}) },
  prestamosEmpleado: { type: Object, default: () => ({}) },
  asistenciaResumen: { type: Object, default: () => ({ semana: [], horario: {} }) },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const page = usePage()
onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2, style: 'currency', currency: 'MXN' })}`
}

const formatearFecha = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

const formatWorkedTime = (mins) => {
  const h = Math.floor(mins / 60)
  const m = mins % 60
  return `${h}h ${m.toString().padStart(2, '0')}m`
}

const maxDayMinutes = computed(() => {
  const max = Math.max(...(props.asistenciaResumen?.semana || []).map(d => d.workedMinutes || 0), 1)
  return Math.max(max, (props.asistenciaResumen?.horario?.horas_jornada || 8) * 60)
})

const editarEmpleado = () => router.visit(`/empleados/${props.empleado.id}/edit`)
const generarNomina = () => router.visit(`/nominas/create?empleado_id=${props.empleado.id}`)
const solicitarVacaciones = () => router.visit(`/vacaciones/create?empleado_id=${props.empleado.id}`)
const crearPrestamoEmpleado = () => router.visit(`/prestamos/create?empleado_id=${props.empleado.id}`)
const volver = () => router.visit('/empleados')

const imprimirContrato = () => {
  window.open(`/empleados/${props.empleado.id}/imprimir-contrato`, '_blank')
}

const descargarContrato = () => {
  window.open(`/empleados/${props.empleado.id}/descargar-contrato`, '_blank')
}
</script>

<template>
  <Head :title="`Expediente - ${empleado.name || 'Sin nombre'}`" />

  <div class="min-h-screen bg-neutral-950 text-white font-sans selection:bg-blue-500/30 selection:text-blue-200 pb-20">
    <!-- Fondo con gradientes premium -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto py-12 px-6">
      
      <!-- Back & Actions Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-8">
        <div class="flex items-center gap-6">
          <button @click="volver" class="group p-4 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition-all">
            <svg class="w-5 h-5 text-neutral-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-emerald-400">Expediente Maestro</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-black tracking-tighter text-white">{{ empleado.name || 'Colaborador' }}</h1>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button v-if="empleado.puede_imprimir_contrato" @click="imprimirContrato" class="px-5 py-3.5 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-all flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Imprimir Contrato
          </button>
          <button @click="crearPrestamoEmpleado" class="px-5 py-3.5 bg-blue-600/20 border border-blue-500/30 text-blue-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600/30 transition-all">Préstamo</button>
          <button @click="solicitarVacaciones" class="px-5 py-3.5 bg-cyan-600/20 border border-cyan-500/30 text-cyan-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-cyan-600/30 transition-all">Vacaciones</button>
          <button @click="generarNomina" class="px-5 py-3.5 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-900/20 hover:scale-105 active:scale-95 transition-all">Generar Nómina</button>
          <button @click="editarEmpleado" class="p-3.5 bg-white text-black rounded-2xl hover:bg-neutral-200 transition-all"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg></button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Contenido Central -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- Perfil Destacado -->
          <div class="bg-gradient-to-br from-blue-600/10 to-indigo-900/20 border border-white/10 rounded-[3rem] p-10 backdrop-blur-xl relative overflow-hidden group">
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-blue-500/10 blur-[100px] rounded-full"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-10">
                <div class="relative">
                    <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-[2.5rem] flex items-center justify-center text-4xl font-black text-white shadow-2xl">
                        {{ empleado.name?.charAt(0) || '?' }}
                    </div>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <div class="text-[10px] font-black text-blue-400 uppercase tracking-[0.2em] mb-2">Puesto Actual</div>
                    <div class="text-3xl font-black mb-3">{{ empleado.puesto || 'Puesto no asignado' }}</div>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4">
                        <span class="px-4 py-1.5 bg-black/40 border border-white/10 rounded-full text-[10px] font-black uppercase tracking-widest text-neutral-400">ID: {{ empleado.numero_empleado }}</span>
                        <span class="px-4 py-1.5 bg-purple-500/10 border border-purple-500/20 rounded-full text-[10px] font-black uppercase tracking-widest text-purple-400">{{ empleado.departamento || 'Sin Depto.' }}</span>
                        <span class="px-4 py-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-full text-[10px] font-black uppercase tracking-widest text-emerald-400">Alta: {{ formatearFecha(empleado.fecha_contratacion) }}</span>
                    </div>
                </div>
            </div>
          </div>

          <!-- Información Desglosada -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Personales -->
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md hover:bg-white/[0.07] transition-all">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-neutral-500 mb-8 flex items-center gap-3">
                    <span class="w-1.5 h-4 bg-blue-500 rounded-full"></span>
                    Datos de Identidad
                </h3>
                <div class="space-y-6">
                    <div v-for="(val, label) in { 
                        'Correo Electrónico': empleado.email,
                        'Teléfono de Contacto': empleado.telefono,
                        'CURP': empleado.curp,
                        'RFC': empleado.rfc,
                        'Número de Seguridad Social': empleado.nss,
                        'Identificación Oficial': empleado.ine 
                    }" :key="label" class="space-y-1">
                        <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">{{ label }}</div>
                        <div class="text-sm font-bold text-neutral-300">{{ val || '—' }}</div>
                    </div>
                </div>
            </div>

            <!-- Laborales -->
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md hover:bg-white/[0.07] transition-all">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-neutral-500 mb-8 flex items-center gap-3">
                    <span class="w-1.5 h-4 bg-indigo-500 rounded-full"></span>
                    Relación Laboral
                </h3>
                <div class="space-y-6">
                    <div class="space-y-1">
                        <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">Esquema de Salario</div>
                        <div class="text-xl font-black text-emerald-400">{{ formatearMoneda(empleado.salario_base) }}</div>
                        <div class="text-[9px] font-black text-neutral-700 uppercase tracking-widest">{{ empleado.frecuencia_pago_formateada || empleado.frecuencia_pago }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">Tipo de Régimen</div>
                        <div class="text-sm font-bold text-neutral-300">{{ empleado.tipo_contrato_formateado || empleado.tipo_contrato }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">Días de Jornada</div>
                        <div class="text-xs font-medium text-neutral-400 flex flex-wrap gap-1 mt-2">
                            <span v-for="d in (empleado.dias_trabajo || [])" :key="d" class="px-2 py-0.5 bg-white/5 rounded-md border border-white/5">{{ d }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════ HORARIO Y ASISTENCIA ═══════ -->
            <div class="bg-gradient-to-br from-cyan-500/[0.06] to-blue-500/[0.04] border border-cyan-500/15 rounded-[2.5rem] p-10 backdrop-blur-md">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-cyan-400 mb-8 flex items-center gap-3">
                    <span class="w-1.5 h-4 bg-cyan-500 rounded-full"></span>
                    Horario y Asistencia
                </h3>

                <!-- Schedule Info -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="space-y-1">
                        <div class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Entrada</div>
                        <div class="text-xl font-black text-white tabular-nums">{{ asistenciaResumen.horario?.hora_entrada || '—' }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Salida</div>
                        <div class="text-xl font-black text-white tabular-nums">{{ asistenciaResumen.horario?.hora_salida || '—' }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Jornada</div>
                        <div class="text-sm font-bold text-neutral-300">{{ asistenciaResumen.horario?.tipo_jornada || '—' }}</div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-[8px] font-extrabold text-neutral-600 uppercase tracking-[0.15em]">Hrs/Día</div>
                        <div class="text-xl font-black text-cyan-400 tabular-nums">{{ asistenciaResumen.horario?.horas_jornada || '—' }}</div>
                    </div>
                </div>

                <!-- Weekly Attendance Grid -->
                <div v-if="asistenciaResumen.semana?.length" class="space-y-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-[9px] font-extrabold text-neutral-500 uppercase tracking-widest">Esta Semana</div>
                        <div class="flex items-center gap-4 text-[9px] font-bold">
                            <span class="text-cyan-400 tabular-nums">{{ asistenciaResumen.totalWeekHours }}h total</span>
                            <span v-if="asistenciaResumen.avgArrival" class="text-neutral-500">⌀ llegada {{ asistenciaResumen.avgArrival }}</span>
                        </div>
                    </div>

                    <div v-for="day in asistenciaResumen.semana" :key="day.date" class="flex items-center gap-3 group">
                        <div class="w-8 text-[10px] font-extrabold text-neutral-500 uppercase">{{ day.dayName }}</div>
                        <div class="flex-1 h-7 rounded-lg bg-white/[0.03] border border-white/[0.04] overflow-hidden relative">
                            <div
                                class="h-full rounded-lg transition-all duration-500"
                                :class="day.hasIncidence ? 'bg-gradient-to-r from-amber-500/40 to-amber-600/20' : 'bg-gradient-to-r from-cyan-500/40 to-blue-500/20'"
                                :style="{ width: `${Math.min(100, (day.workedMinutes / maxDayMinutes) * 100)}%` }"
                            ></div>
                            <div class="absolute inset-0 flex items-center px-3 justify-between">
                                <div class="flex items-center gap-2 text-[9px] font-bold">
                                    <span v-if="day.entry" class="text-emerald-400">{{ day.entry }}</span>
                                    <span v-if="day.entry && day.exit" class="text-neutral-600">→</span>
                                    <span v-if="day.exit" class="text-rose-400">{{ day.exit }}</span>
                                    <span v-if="day.entry && !day.exit" class="text-amber-400 text-[8px] animate-pulse">activo</span>
                                </div>
                                <div class="text-[9px] font-bold text-neutral-400 tabular-nums">
                                    {{ formatWorkedTime(day.workedMinutes) }}
                                    <span v-if="day.hasIncidence" class="ml-1">⚠️</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!asistenciaResumen.semana.length" class="text-center py-6 text-neutral-700 text-[10px] font-bold uppercase tracking-widest">Sin registros esta semana</div>
                </div>

                <!-- Week Summary Cards -->
                <div class="grid grid-cols-3 gap-3 mt-6">
                    <div class="rounded-xl p-3 bg-white/[0.03] border border-white/[0.04] text-center">
                        <div class="text-lg font-black text-white tabular-nums">{{ asistenciaResumen.daysWorked || 0 }}</div>
                        <div class="text-[7px] font-extrabold text-neutral-600 uppercase tracking-widest">Días</div>
                    </div>
                    <div class="rounded-xl p-3 bg-white/[0.03] border border-white/[0.04] text-center">
                        <div class="text-lg font-black text-cyan-400 tabular-nums">{{ asistenciaResumen.totalWeekHours || 0 }}h</div>
                        <div class="text-[7px] font-extrabold text-neutral-600 uppercase tracking-widest">Horas</div>
                    </div>
                    <div class="rounded-xl p-3 bg-white/[0.03] border border-white/[0.04] text-center">
                        <div class="text-lg font-black text-white tabular-nums">{{ asistenciaResumen.avgArrival || '—' }}</div>
                        <div class="text-[7px] font-extrabold text-neutral-600 uppercase tracking-widest">Promedio</div>
                    </div>
                </div>
            </div>
          </div>

          <!-- Finanzas y Banca -->
          <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md">
            <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-neutral-500 mb-8">Información de Tesorería</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="space-y-1">
                    <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">Institución Bancaria</div>
                    <div class="text-sm font-black text-white px-4 py-2 bg-black/30 rounded-xl border border-white/5 block w-full">{{ empleado.banco || '—' }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">Número de Cuenta</div>
                    <div class="text-sm font-black text-white px-4 py-2 bg-black/30 rounded-xl border border-white/5">{{ empleado.numero_cuenta || '—' }}</div>
                </div>
                <div class="space-y-1">
                    <div class="text-[9px] font-black text-neutral-600 uppercase tracking-widest">CLABE Interbancaria</div>
                    <div class="text-sm font-black text-white px-4 py-2 bg-black/30 rounded-xl border border-white/5">{{ empleado.clabe_interbancaria || '—' }}</div>
                </div>
            </div>
          </div>

          <!-- Vacaciones Status -->
          <div class="bg-gradient-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/20 rounded-[2.5rem] p-10 backdrop-blur-md">
            <div class="flex items-center justify-between mb-8">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-400">Balance de Vacaciones</h3>
                <div class="text-[10px] font-black text-neutral-500 uppercase">Gestión de Ausencias</div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div v-for="(v, l) in {
                    'Antigüedad': empleado.antiguedad_formateada || '—',
                    'Asignados': vacacionesResumen.dias_correspondientes ?? 0,
                    'Gozados': vacacionesResumen.dias_utilizados ?? 0,
                    'Disponibles': vacacionesResumen.dias_disponibles ?? 0
                }" :key="l" class="space-y-2">
                    <div class="text-[8px] font-black text-neutral-600 uppercase tracking-widest">{{ l }}</div>
                    <div :class="['text-2xl font-black', l === 'Disponibles' ? 'text-emerald-400' : 'text-white']">{{ v }}</div>
                </div>
            </div>
          </div>
        </div>

        <!-- Sidebar Directivo -->
        <div class="space-y-8">
            
            <!-- Resumen Financiero Anual -->
            <div class="bg-gradient-to-br from-indigo-700 to-blue-900 rounded-[3rem] p-10 shadow-2xl relative overflow-hidden group">
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-white/10 blur-3xl rounded-full transition-transform group-hover:scale-150"></div>
                <div class="relative z-10 flex flex-col gap-10">
                    <div class="flex justify-between items-start">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-white/60">Anualidad {{ new Date().getFullYear() }}</div>
                        <div class="p-2 bg-white/10 rounded-xl"><svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-end border-b border-white/10 pb-4">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white/40">Neto Percibido</span>
                            <span class="text-3xl font-black">{{ formatearMoneda(resumenAnual.total_neto) }}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-[10px] font-black uppercase tracking-widest">
                            <div class="text-white/40">Deducciones: <span class="text-white block mt-1">{{ formatearMoneda(resumenAnual.total_deducciones) }}</span></div>
                            <div class="text-white/40">Pagos: <span class="text-white block mt-1">{{ resumenAnual.nominas_pagadas || 0 }} Emisiones</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Préstamos Quick Card -->
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-neutral-500 mb-8 flex items-center justify-between">
                    Pasivos Internos
                    <span v-if="prestamosEmpleado.activos > 0" class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                </h3>
                <div class="space-y-6">
                    <div class="flex justify-between items-end">
                        <span class="text-[10px] font-black text-neutral-600 uppercase tracking-widest">Saldo Pendiente</span>
                        <span class="text-2xl font-black text-red-500">{{ formatearMoneda(prestamosEmpleado.monto_pendiente ?? 0) }}</span>
                    </div>
                    <div class="text-[10px] font-bold text-neutral-500 uppercase tracking-widest flex justify-between">
                        <span>Cant. Créditos</span>
                        <span>{{ prestamosEmpleado.total ?? 0 }}</span>
                    </div>
                    <button @click="crearPrestamoEmpleado" class="w-full py-4 bg-white/5 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-all mt-4">Solicitar Nuevo</button>
                </div>
            </div>

            <!-- Nóminas Recientes -->
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md overflow-hidden relative">
                <div class="flex items-center justify-between mb-10">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-neutral-500">Últimos Recibos</h3>
                    <Link :href="`/nominas?empleado_id=${empleado.id}`" class="text-[9px] font-black uppercase text-blue-400 hover:text-blue-300">Detallado</Link>
                </div>
                <div v-if="nominasRecientes.length" class="space-y-6">
                    <div v-for="nomina in nominasRecientes.slice(0, 4)" :key="nomina.id" class="group flex items-center justify-between py-4 border-b border-white/5 last:border-0 hover:translate-x-2 transition-transform cursor-pointer">
                        <div class="space-y-1">
                            <div class="text-xs font-black text-white group-hover:text-blue-400 transition-colors">{{ nomina.periodo_formateado }}</div>
                            <div class="text-[8px] font-black text-neutral-700 uppercase tracking-widest">{{ nomina.tipo_periodo_formateado }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-black text-white">{{ formatearMoneda(nomina.total_neto) }}</div>
                            <div :class="['text-[8px] font-black uppercase tracking-widest mt-1', nomina.estado === 'pagado' ? 'text-emerald-500' : 'text-amber-500']">{{ nomina.estado }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 opacity-30 italic text-xs">Sin registros recientes</div>
            </div>

            <!-- Emergencia y Notas -->
            <div class="bg-red-500/5 border border-red-500/10 rounded-[2.5rem] p-10">
                <h3 class="text-[11px] font-black uppercase tracking-[0.2em] text-red-400/60 mb-8">Contacto S.O.S</h3>
                <div class="space-y-4">
                    <div class="text-sm font-black text-white">{{ empleado.contacto_emergencia_nombre || 'No definido' }}</div>
                    <div class="text-xl font-black text-red-500 tracking-tighter">{{ empleado.contacto_emergencia_telefono || '—' }}</div>
                    <div class="text-[10px] font-black text-neutral-600 uppercase tracking-widest">{{ empleado.contacto_emergencia_parentesco || '—' }}</div>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>
