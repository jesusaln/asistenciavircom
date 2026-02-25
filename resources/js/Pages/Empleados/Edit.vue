<script setup>
import { ref, computed, onErrorCaptured } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleado: { type: Object, required: true },
  departamentos: { type: Array, default: () => [] },
  puestos: { type: Array, default: () => [] },
  tiposContrato: { type: Array, default: () => [] },
  tiposJornada: { type: Array, default: () => [] },
  frecuenciasPago: { type: Array, default: () => [] },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const form = useForm({
  numero_empleado: props.empleado.numero_empleado || '',
  fecha_nacimiento: props.empleado.fecha_nacimiento || '',
  curp: props.empleado.curp || '',
  rfc: props.empleado.rfc || '',
  nss: props.empleado.nss || '',
  ine: props.empleado.ine || '',
  imss: props.empleado.imss || '',
  direccion: props.empleado.direccion || '',
  puesto: props.empleado.puesto || '',
  departamento: props.empleado.departamento || '',
  fecha_contratacion: props.empleado.fecha_contratacion || '',
  salario_base: props.empleado.salario_base || '',
  tipo_contrato: props.empleado.tipo_contrato || 'tiempo_completo',
  tipo_jornada: props.empleado.tipo_jornada || 'diurna',
  horas_jornada: props.empleado.horas_jornada || 8,
  hora_entrada: props.empleado.hora_entrada?.substring(0, 5) || '08:00',
  hora_salida: props.empleado.hora_salida?.substring(0, 5) || '17:00',
  trabaja_sabado: props.empleado.trabaja_sabado ?? false,
  hora_entrada_sabado: props.empleado.hora_entrada_sabado?.substring(0, 5) || '08:00',
  hora_salida_sabado: props.empleado.hora_salida_sabado?.substring(0, 5) || '14:00',
  dias_trabajo: props.empleado.dias_trabajo || ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
  dias_descanso: props.empleado.dias_descanso || ['sabado', 'domingo'],
  frecuencia_pago: props.empleado.frecuencia_pago || 'quincenal',
  banco: props.empleado.banco || '',
  numero_cuenta: props.empleado.numero_cuenta || '',
  clabe_interbancaria: props.empleado.clabe_interbancaria || '',
  contacto_emergencia_nombre: props.empleado.contacto_emergencia_nombre || '',
  contacto_emergencia_telefono: props.empleado.contacto_emergencia_telefono || '',
  contacto_emergencia_parentesco: props.empleado.contacto_emergencia_parentesco || '',
  observaciones: props.empleado.observaciones || '',
  activo: props.empleado.activo ?? true,
  contrato_adjunto: null,
  _method: 'PUT',
})

const diasSemana = [
  { value: 'lunes', label: 'Lunes' },
  { value: 'martes', label: 'Martes' },
  { value: 'miercoles', label: 'Miércoles' },
  { value: 'jueves', label: 'Jueves' },
  { value: 'viernes', label: 'Viernes' },
  { value: 'sabado', label: 'Sábado' },
  { value: 'domingo', label: 'Domingo' },
]

// Computed: Salario diario (salario base / 30 días)
// Helper to cleaner parsing
const cleanNumber = (val) => {
  if (!val) return 0
  if (typeof val === 'number') return val
  // Remove commas if present and parse
  const clean = val.toString().replace(/,/g, '')
  return parseFloat(clean) || 0
}

// Computed: Salario diario (salario base / 30 días)
const salarioDiario = computed(() => {
  const base = cleanNumber(form.salario_base)
  return base / 30
})

// Computed: Salario por periodo según frecuencia
const salarioPorPeriodo = computed(() => {
  const base = cleanNumber(form.salario_base)
  console.log('Calculating period salary. Base:', base, 'Freq:', form.frecuencia_pago)
  
  if (form.frecuencia_pago === 'semanal') {
    return base / 4 // 4 semanas al mes
  } else if (form.frecuencia_pago === 'quincenal') {
    return base / 2 // 2 quincenas al mes
  }
  return base // mensual
})

// Computed: Total mensual (para verificación)
const totalMensual = computed(() => {
  if (form.frecuencia_pago === 'semanal') {
    return salarioPorPeriodo.value * 4
  } else if (form.frecuencia_pago === 'quincenal') {
    return salarioPorPeriodo.value * 2
  }
  return cleanNumber(form.salario_base)
})

// Computed: Número de pagos al mes
const pagosPorMes = computed(() => {
  if (form.frecuencia_pago === 'semanal') return 4
  if (form.frecuencia_pago === 'quincenal') return 2
  return 1
})

// Formato de moneda seguro
const formatCurrency = (value) => {
  try {
    const num = cleanNumber(value)
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num)
  } catch (e) {
    console.error('Error formatting currency:', e)
    return '$0.00'
  }
}

const submit = () => {
  form.post(`/empleados/${props.empleado.id}`, {
    onSuccess: () => notyf.success('Empleado actualizado exitosamente'),
    onError: () => notyf.error('Error al actualizar el empleado'),
  })
}

const imprimirContrato = () => {
  window.open(`/empleados/${props.empleado.id}/imprimir-contrato`, '_blank')
}

const cancelar = () => router.visit(`/empleados/${props.empleado.id}`)
</script>

<template>
  <Head :title="`Editar - ${empleado.name || 'Sin nombre'}`" />

  <div class="min-h-screen bg-neutral-950 text-white font-sans selection:bg-blue-500/30 selection:text-blue-200 pb-32">
    <!-- Fondo con gradientes dinámicos -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -left-[10%] w-[50%] h-[50%] bg-indigo-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <!-- Header Modernizado -->
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-8">
        <div class="space-y-4">
          <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-[0.2em] animate-fade-in">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            SISTEMA DE GESTIÓN DE CAPITAL HUMANO
          </div>
          <h1 class="text-5xl md:text-6xl font-black tracking-tighter text-white leading-none">
            {{ empleado.name || 'Nuevo Registro' }}
          </h1>
          <div class="flex items-center gap-4 text-neutral-400 font-medium">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                {{ empleado.puesto || 'Puesto Sin Definir' }}
            </span>
            <span class="opacity-30">|</span>
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                {{ empleado.departamento || 'No Asignado' }}
            </span>
          </div>
        </div>
        
        <div class="flex items-center gap-4">
          <button 
            v-if="empleado.puede_imprimir_contrato" 
            @click="imprimirContrato"
            type="button"
            class="group flex items-center px-6 py-4 bg-white/5 hover:bg-blue-600/20 border border-white/10 hover:border-blue-500/30 rounded-2xl text-[11px] font-black text-white uppercase tracking-widest transition-all duration-500 backdrop-blur-md"
          >
            <svg class="w-4 h-4 mr-3 text-blue-400 group-hover:scale-125 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Imprimir Contrato
          </button>
          
          <button @click="cancelar" type="button" class="group px-6 py-4 rounded-2xl text-[11px] font-black text-neutral-500 uppercase tracking-widest hover:text-white hover:bg-white/5 transition-all duration-300">
            Cerrar
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        
        <!-- Header Identificativo Automático (No Editable) -->
        <div class="bg-gradient-to-r from-amber-500/10 to-orange-500/5 border border-amber-500/20 rounded-[2.5rem] p-8 backdrop-blur-md mb-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="p-3 bg-amber-500/20 rounded-2xl text-amber-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-sm font-black uppercase tracking-widest text-amber-500">Credenciales Vinculadas (Lectura)</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-amber-500/60 uppercase tracking-widest">Nombre Completo del Usuario</label>
                    <div class="text-xl font-black text-amber-200">{{ empleado.name || 'Sin nombre' }}</div>
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-amber-500/60 uppercase tracking-widest">Correo Electrónico de Plataforma</label>
                    <div class="text-xl font-black text-amber-200">{{ empleado.email || 'Sin correo' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-xl relative overflow-hidden group">
            <div class="relative z-10 space-y-10">
              <div class="flex items-center justify-between">
                <div>
                  <h2 class="text-2xl font-black tracking-tight text-white mb-1">Estatus Laboral</h2>
                  <p class="text-xs text-neutral-500 font-medium">Defina la vigencia del colaborador en la organización</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="form.activo" class="sr-only peer">
                  <div class="w-16 h-8 bg-white/10 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600 border border-white/5"></div>
                  <span class="ml-4 text-[11px] font-black uppercase tracking-widest" :class="form.activo ? 'text-emerald-400' : 'text-neutral-600'">
                    {{ form.activo ? 'ACTIVO' : 'BAJA DEFINITIVA' }}
                  </span>
                </label>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">ID Único de Empleado</label>
                  <input v-model="form.numero_empleado" type="text" placeholder="EMP-000" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-5 text-white font-bold focus:ring-2 focus:ring-blue-500/50 transition-all" />
                  <InputError :message="form.errors.numero_empleado" />
                </div>
                <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">Fecha de Nacimiento</label>
                  <input v-model="form.fecha_nacimiento" type="date" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-5 text-white focus:ring-2 focus:ring-blue-500/50 transition-all [color-scheme:dark]" />
                </div>
              </div>
            </div>
          </div>

          <!-- Card Salarial Destacado -->
          <div class="bg-gradient-to-br from-blue-700 to-indigo-900 rounded-[2.5rem] p-10 shadow-2xl flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white/10 blur-[100px] rounded-full"></div>
            <div class="relative z-10">
                <div class="text-white/60 text-[11px] font-black uppercase tracking-[0.2em] mb-10">Remuneración Proyectada</div>
                <div class="space-y-1">
                    <div class="text-[10px] font-black text-white/40 uppercase tracking-widest">Monto por Frecuencia</div>
                    <div class="text-5xl font-black tracking-tighter text-white">{{ formatCurrency(salarioPorPeriodo) }}</div>
                </div>
            </div>
            <div class="relative z-10 pt-8 border-t border-white/10 mt-8">
                <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                    <span class="text-white/40">Frecuencia actual</span>
                    <span class="text-blue-200">{{ form.frecuencia_pago }}</span>
                </div>
            </div>
          </div>
        </div>

        <!-- Secciones de Información Técnica -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Documentación Legal -->
          <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 bg-blue-500 rounded-full"></div>
                <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white/60">Identidad Gubernamental</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">CURP</label><input v-model="form.curp" type="text" maxlength="18" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 uppercase transition-all" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">RFC</label><input v-model="form.rfc" type="text" maxlength="13" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 uppercase transition-all" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">NSS (Seguro Social)</label><input v-model="form.nss" type="text" maxlength="11" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Registro IMSS</label><input v-model="form.imss" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">INE / Cédula</label><input v-model="form.ine" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 uppercase transition-all" /></div>
              <div class="space-y-2 sm:col-span-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Domicilio Fiscal/Particular</label><input v-model="form.direccion" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
            </div>
          </div>

          <!-- Estructura y Contratación -->
          <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8">
            <div class="flex items-center gap-4">
                <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white/60">Configuración Organizacional</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Rol / Puesto</label><input v-model="form.puesto" type="text" list="puestos-list" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /><datalist id="puestos-list"><option v-for="p in puestos" :key="p" :value="p" /></datalist></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Departamento</label><input v-model="form.departamento" type="text" list="departamentos-list" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /><datalist id="departamentos-list"><option v-for="d in departamentos" :key="d" :value="d" /></datalist></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Alta de Contrato</label><input v-model="form.fecha_contratacion" type="date" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Tipo de Régimen</label><select v-model="form.tipo_contrato" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]"><option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option></select></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Jornada Laboral</label><select v-model="form.tipo_jornada" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]"><option v-for="tj in tiposJornada" :key="tj.value" :value="tj.value">{{ tj.label }}</option></select></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Horas Diarias</label><input v-model="form.horas_jornada" type="number" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
            </div>
          </div>
        </div>

        <!-- Días de Trabajo (Checkboxes) -->
        <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div>
                   <h3 class="text-sm font-black uppercase tracking-widest text-blue-400 mb-6">Días de Operación</h3>
                   <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                       <label v-for="dia in diasSemana" :key="`trabajo-${dia.value}`" class="flex items-center gap-3 p-4 rounded-2xl border border-white/5 bg-black/20 cursor-pointer transition-all hover:bg-white/5" :class="form.dias_trabajo.includes(dia.value) ? 'border-blue-500/50 bg-blue-500/10' : ''">
                           <input v-model="form.dias_trabajo" :value="dia.value" type="checkbox" class="w-5 h-5 rounded border-white/10 bg-transparent text-blue-600 focus:ring-0" />
                           <span class="text-xs font-bold">{{ dia.label }}</span>
                       </label>
                   </div>
                </div>
                <div>
                   <h3 class="text-sm font-black uppercase tracking-widest text-indigo-400 mb-6">Días de Descanso</h3>
                   <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                       <label v-for="dia in diasSemana" :key="`descanso-${dia.value}`" class="flex items-center gap-3 p-4 rounded-2xl border border-white/5 bg-black/20 cursor-pointer transition-all hover:bg-white/5" :class="form.dias_descanso.includes(dia.value) ? 'border-indigo-500/50 bg-indigo-500/10' : ''">
                           <input v-model="form.dias_descanso" :value="dia.value" type="checkbox" class="w-5 h-5 rounded border-white/10 bg-transparent text-indigo-600 focus:ring-0" />
                           <span class="text-xs font-bold">{{ dia.label }}</span>
                       </label>
                   </div>
                </div>
            </div>
        </div>

        <!-- Horarios y Esquema Sabatino -->
        <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-xl relative overflow-hidden">
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-16">
            <div class="space-y-8">
              <div class="flex items-center gap-4">
                <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></div>
                <h3 class="text-xl font-black uppercase tracking-tight">Horario Lunes a Viernes</h3>
              </div>
              <div class="grid grid-cols-2 gap-8 p-8 bg-black/40 rounded-[2rem] border border-white/5">
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Hora Entrada</label><input v-model="form.hora_entrada" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-blue-400 focus:ring-0 [color-scheme:dark]" /></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Hora Salida</label><input v-model="form.hora_salida" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-blue-400 focus:ring-0 [color-scheme:dark]" /></div>
              </div>
            </div>
            
            <div class="space-y-8">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-amber-500/10 text-amber-500"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Esquema Sabatino</h3>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="form.trabaja_sabado" class="sr-only peer">
                  <div class="w-12 h-6 bg-white/10 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600 border border-white/5"></div>
                </label>
              </div>

              <div v-if="form.trabaja_sabado" class="grid grid-cols-2 gap-8 p-8 bg-black/40 rounded-[2rem] border border-amber-500/20 animate-fade-in">
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Entrada Sábado</label><input v-model="form.hora_entrada_sabado" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-amber-400 focus:ring-0 [color-scheme:dark]" /></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Salida Sábado</label><input v-model="form.hora_salida_sabado" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-amber-400 focus:ring-0 [color-scheme:dark]" /></div>
              </div>
              <div v-else class="h-[108px] flex items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] text-sm font-black text-neutral-700 uppercase tracking-widest">
                No Labora Sábados
              </div>
            </div>
          </div>
        </div>

        <!-- Finanzas y Banca -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-emerald-500/10 text-emerald-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Finanzas</h3>
                </div>
                <div class="space-y-8">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest block ml-1">Salario Mensual Bruto</label>
                        <div class="relative group">
                            <span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-neutral-700 group-focus-within:text-emerald-500 transition-colors">$</span>
                            <input v-model="form.salario_base" type="number" step="0.01" class="w-full bg-black/40 border border-white/10 rounded-3xl pl-12 pr-8 py-6 text-4xl font-black text-white focus:ring-2 focus:ring-emerald-500/50 transition-all" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <button v-for="freq in ['semanal', 'quincenal']" :key="freq" type="button" @click="form.frecuencia_pago = freq" :class="['py-6 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-500 border-2', form.frecuencia_pago === freq ? 'bg-emerald-600 border-emerald-400 text-white shadow-[0_0_30px_rgba(16,185,129,0.3)]' : 'bg-black/20 border-white/5 text-neutral-500 hover:border-white/10']">
                            {{ freq }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-10">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-2xl bg-blue-500/10 text-blue-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Datos Bancarios</h3>
                </div>
                <div class="space-y-6">
                    <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Institución Bancaria</label><input v-model="form.banco" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Número de Cuenta</label><input v-model="form.numero_cuenta" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                        <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">CLABE Interbancaria</label><input v-model="form.clabe_interbancaria" type="text" maxlength="18" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contacto Emergencia & Expediente Digital -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8">
                <h3 class="text-sm font-black uppercase tracking-widest text-red-400/60 border-b border-white/5 pb-4">Protocolo de Emergencia</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Nombre del Contacto</label><input v-model="form.contacto_emergencia_nombre" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-red-500 transition-all" /></div>
                    <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Teléfono Directo</label><input v-model="form.contacto_emergencia_telefono" type="tel" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-red-500 transition-all" /></div>
                    <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Parentesco / Relación</label><input v-model="form.contacto_emergencia_parentesco" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-red-500 transition-all" /></div>
                </div>
                <div class="pt-4 border-t border-white/5">
                    <label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest mb-3 block">Observaciones y Notas Internas</label>
                    <textarea v-model="form.observaciones" rows="3" placeholder="Notas sobre el desempeño, salud o historial..." class="w-full bg-black/30 border border-white/5 rounded-[1.5rem] px-6 py-4 text-sm focus:border-blue-500 transition-all resize-none"></textarea>
                </div>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8 flex flex-col justify-between">
                <div class="space-y-6">
                    <h3 class="text-sm font-black uppercase tracking-widest text-blue-400 border-b border-white/5 pb-4">Expediente Digital</h3>
                    <div v-if="empleado.contrato_adjunto" class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl flex items-center justify-between">
                        <span class="text-[10px] font-black text-blue-200 uppercase tracking-widest">Contrato Firmado</span>
                        <a :href="`/empleados/${empleado.id}/descargar-contrato`" target="_blank" class="text-[10px] font-black text-blue-400 hover:text-blue-300 transition-colors underline">VER PDF</a>
                    </div>
                </div>
                
                <div class="relative">
                    <input type="file" id="contrato_adjunto" @input="form.contrato_adjunto = $event.target.files[0]" class="hidden" accept=".pdf,image/*" />
                    <label for="contrato_adjunto" class="flex flex-col items-center justify-center gap-4 p-8 border-2 border-dashed border-white/10 rounded-3xl cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 transition-all group">
                        <div class="p-4 bg-white/5 rounded-2xl group-hover:scale-110 transition-transform"><svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg></div>
                        <div class="text-center">
                            <div class="text-[10px] font-black text-white uppercase tracking-widest">{{ form.contrato_adjunto ? 'Archivo Seleccionado' : 'Subir Nuevo Contrato' }}</div>
                            <div class="text-[9px] text-neutral-500 mt-1 uppercase">{{ form.contrato_adjunto ? form.contrato_adjunto.name : 'PDF, JPG o PNG Máx 5MB' }}</div>
                        </div>
                    </label>
                    <button v-if="form.contrato_adjunto" type="button" @click="form.contrato_adjunto = null" class="absolute -top-2 -right-2 p-1.5 bg-red-600 rounded-full text-white hover:bg-red-700 transition-colors shadow-lg"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
            </div>
        </div>

        <!-- Barra de Acción Persistente -->
        <div class="fixed bottom-10 left-0 right-0 z-50 px-4">
            <div class="max-w-3xl mx-auto bg-neutral-900/90 backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-4 flex items-center justify-between shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                <div class="hidden md:flex flex-col ml-6">
                    <span class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Estado del Formulario</span>
                    <span class="text-[10px] font-bold" :class="form.isDirty ? 'text-amber-500' : 'text-emerald-500'">{{ form.isDirty ? 'CAMBIOS SIN GUARDAR' : 'DATOS SINCRONIZADOS' }}</span>
                </div>
                <button type="submit" :disabled="form.processing" class="flex-1 md:flex-initial px-16 py-5 bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-500 hover:to-indigo-600 rounded-[1.5rem] text-[11px] font-black text-white uppercase tracking-[0.2em] shadow-lg active:scale-95 transition-all duration-300 disabled:opacity-50">
                    {{ form.processing ? 'Sincronizando...' : 'Actualizar Expediente Maestro' }}
                </button>
            </div>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
input[type="time"]::-webkit-calendar-picker-indicator { filter: invert(1); }
input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.5; }

/* Animaciones */
.animate-fade-in { animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

/* Scrollbar sutil */
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
</style>
