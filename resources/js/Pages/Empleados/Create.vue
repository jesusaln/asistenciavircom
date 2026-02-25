<script setup>
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  usuariosDisponibles: { type: Array, default: () => [] },
  departamentos: { type: Array, default: () => [] },
  puestos: { type: Array, default: () => [] },
  tiposContrato: { type: Array, default: () => [] },
  tiposJornada: { type: Array, default: () => [] },
  frecuenciasPago: { type: Array, default: () => [] },
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const form = useForm({
  user_id: '',
  numero_empleado: '',
  fecha_nacimiento: '',
  curp: '',
  rfc: '',
  nss: '',
  ine: '',
  imss: '',
  direccion: '',
  puesto: '',
  departamento: '',
  fecha_contratacion: '',
  salario_base: '',
  tipo_contrato: 'tiempo_completo',
  tipo_jornada: 'diurna',
  horas_jornada: 8,
  hora_entrada: '08:00',
  hora_salida: '17:00',
  trabaja_sabado: false,
  hora_entrada_sabado: '08:00',
  hora_salida_sabado: '14:00',
  dias_trabajo: ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'],
  dias_descanso: ['sabado', 'domingo'],
  frecuencia_pago: 'quincenal',
  banco: '',
  numero_cuenta: '',
  clabe_interbancaria: '',
  contacto_emergencia_nombre: '',
  contacto_emergencia_telefono: '',
  contacto_emergencia_parentesco: '',
  observaciones: '',
  contrato_adjunto: null,
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
  if (form.frecuencia_pago === 'semanal') {
    return base / 4
  } else if (form.frecuencia_pago === 'quincenal') {
    return base / 2
  }
  return base
})

// Formato de moneda seguro
const formatCurrency = (value) => {
  try {
    const num = cleanNumber(value)
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(num)
  } catch (e) {
    return '$0.00'
  }
}

const submit = () => {
  form.post('/empleados', {
    onSuccess: () => notyf.success('Empleado creado exitosamente'),
    onError: () => notyf.error('Error al crear el empleado'),
  })
}

const cancelar = () => router.visit('/empleados')

const usuarioSeleccionado = computed(() => {
  return props.usuariosDisponibles.find(u => u.id == form.user_id)
})
</script>

<template>
  <Head title="Alta de Colaborador - Black Premium" />

  <div class="min-h-screen bg-neutral-950 text-white font-sans selection:bg-blue-500/30 selection:text-blue-200 pb-32">
    <!-- Fondo con gradientes dinámicos -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[10%] w-[50%] h-[50%] bg-blue-600/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -left-[10%] w-[50%] h-[50%] bg-emerald-600/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-8">
        <div class="space-y-4">
          <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-[0.2em] animate-fade-in">
            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
            NUEVO REGISTRO CORPORATIVO
          </div>
          <h1 class="text-5xl md:text-6xl font-black tracking-tighter text-white leading-none">Alta de Staff</h1>
          <p class="text-neutral-400 text-sm font-medium">Inicie la integración de un nuevo colaborador al ecosistema de la organización.</p>
        </div>
        <button @click="cancelar" type="button" class="group px-6 py-4 rounded-2xl text-[11px] font-black text-neutral-500 uppercase tracking-widest hover:text-white hover:bg-white/5 transition-all duration-300">
          Cancelar Alta
        </button>
      </div>

      <form @submit.prevent="submit" class="space-y-8">
        
        <!-- Selección de Perfil de Usuario -->
        <div class="bg-gradient-to-r from-blue-600/10 to-indigo-600/5 border border-blue-500/20 rounded-[2.5rem] p-10 backdrop-blur-md relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                <svg class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <div class="relative z-10 space-y-8">
                <div>
                   <h3 class="text-sm font-black uppercase tracking-widest text-blue-400 mb-2">Punto de Partida</h3>
                   <p class="text-xs text-neutral-500 font-medium">Vincule un perfil de acceso existente al nuevo registro laboral</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-end">
                    <div class="space-y-3">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest ml-1">Seleccionar Usuario del Sistema</label>
                        <select v-model="form.user_id" class="w-full bg-black/40 border border-white/10 rounded-2xl px-6 py-5 text-white font-bold focus:ring-2 focus:ring-blue-500/50 [color-scheme:dark] transition-all" required>
                            <option value="">Desplegar lista de usuarios...</option>
                            <option v-for="user in usuariosDisponibles" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                        </select>
                        <InputError :message="form.errors.user_id" />
                    </div>
                    <div v-if="usuarioSeleccionado" class="p-6 bg-blue-500/5 border border-blue-500/10 rounded-2xl animate-fade-in">
                        <div class="text-[9px] font-black text-blue-500/60 uppercase tracking-widest mb-1">Perfil Confirmado</div>
                        <div class="text-sm font-black text-blue-200">{{ usuarioSeleccionado.name }}</div>
                        <div class="text-[10px] text-neutral-500 font-medium">{{ usuarioSeleccionado.email }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-xl relative overflow-hidden group">
            <div class="relative z-10 space-y-10">
              <h2 class="text-2xl font-black tracking-tight text-white mb-1">Identidad Gubernamental</h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-3">
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-400">ID de Empleado</label>
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

          <div class="bg-gradient-to-br from-blue-700 to-indigo-900 rounded-[2.5rem] p-10 shadow-2xl flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white/10 blur-[100px] rounded-full"></div>
            <div class="relative z-10">
                <div class="text-white/60 text-[11px] font-black uppercase tracking-[0.2em] mb-10">Remuneración Proyectada</div>
                <div class="space-y-1">
                    <div class="text-[10px] font-black text-white/40 uppercase tracking-widest">Monto por Frecuencia</div>
                    <div class="text-5xl font-black tracking-tighter text-white">{{ formatCurrency(salarioPorPeriodo) }}</div>
                </div>
            </div>
            <div class="relative z-10 pt-8 border-t border-white/10 mt-8 flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                <span class="text-white/40">Frecuencia seleccionada</span>
                <span class="text-blue-200">{{ form.frecuencia_pago }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8">
            <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white/60 mb-8 border-b border-white/5 pb-4">Documentación Legal</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div v-for="f in [
                  {m: 'curp', l: 'CURP', mx: 18, u: true},
                  {m: 'rfc', l: 'RFC', mx: 13, u: true},
                  {m: 'nss', l: 'NSS (Seguro Social)', mx: 11, u: false},
                  {m: 'imss', l: 'Registro IMSS', mx: 50, u: false},
                  {m: 'ine', l: 'INE / Cédula', mx: 30, u: true}
              ]" :key="f.m" class="space-y-2">
                  <label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">{{ f.l }}</label>
                  <input v-model="form[f.m]" :maxlength="f.mx" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" :class="f.u ? 'uppercase' : ''" />
                  <InputError :message="form.errors[f.m]" />
              </div>
              <div class="sm:col-span-2 space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Domicilio Completo</label><input v-model="form.direccion" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /><InputError :message="form.errors.direccion" /></div>
            </div>
          </div>

          <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-md space-y-8">
            <h3 class="text-sm font-black uppercase tracking-[0.2em] text-white/60 mb-8 border-b border-white/5 pb-4">Configuración Laboral</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Rol / Puesto</label><input v-model="form.puesto" type="text" list="puestos-list" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /><datalist id="puestos-list"><option v-for="p in puestos" :key="p" :value="p" /></datalist></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Departamento</label><input v-model="form.departamento" type="text" list="departamentos-list" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /><datalist id="departamentos-list"><option v-for="d in departamentos" :key="d" :value="d" /></datalist></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Fecha de Contratación</label><input v-model="form.fecha_contratacion" type="date" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]" /></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Régimen Contractual</label><select v-model="form.tipo_contrato" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]"><option v-for="tipo in tiposContrato" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option></select></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Jornada</label><select v-model="form.tipo_jornada" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 [color-scheme:dark]"><option v-for="tj in tiposJornada" :key="tj.value" :value="tj.value">{{ tj.label }}</option></select></div>
              <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Horas Diarias</label><input v-model="form.horas_jornada" type="number" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
            </div>
          </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-md flex flex-col md:flex-row gap-12">
            <div class="flex-1">
                <h3 class="text-sm font-black uppercase tracking-widest text-blue-400 mb-6">Calendario de Operación</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <label v-for="dia in diasSemana" :key="`trabajo-${dia.value}`" class="flex items-center gap-3 p-4 rounded-2xl border border-white/5 bg-black/20 cursor-pointer transition-all" :class="form.dias_trabajo.includes(dia.value) ? 'border-blue-500/50 bg-blue-500/10' : ''">
                        <input v-model="form.dias_trabajo" :value="dia.value" type="checkbox" class="w-5 h-5 rounded border-white/10 bg-transparent text-blue-600 focus:ring-0" />
                        <span class="text-xs font-bold">{{ dia.label }}</span>
                    </label>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-black uppercase tracking-widest text-indigo-400 mb-6">Días de Descanso</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <label v-for="dia in diasSemana" :key="`descanso-${dia.value}`" class="flex items-center gap-3 p-4 rounded-2xl border border-white/5 bg-black/20 cursor-pointer transition-all" :class="form.dias_descanso.includes(dia.value) ? 'border-indigo-500/50 bg-indigo-500/10' : ''">
                        <input v-model="form.dias_descanso" :value="dia.value" type="checkbox" class="w-5 h-5 rounded border-white/10 bg-transparent text-indigo-600 focus:ring-0" />
                        <span class="text-xs font-bold">{{ dia.label }}</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 backdrop-blur-xl relative overflow-hidden">
          <div class="grid grid-cols-1 xl:grid-cols-2 gap-16">
            <div class="space-y-8">
              <h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-4 text-blue-400"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Horario Ordinario (L-V)</h3>
              <div class="grid grid-cols-2 gap-8 p-8 bg-black/40 rounded-[2rem] border border-white/5">
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Entrada</label><input v-model="form.hora_entrada" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-blue-400 focus:ring-0 [color-scheme:dark]" /></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Salida</label><input v-model="form.hora_salida" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-blue-400 focus:ring-0 [color-scheme:dark]" /></div>
              </div>
            </div>
            <div class="space-y-8">
              <div class="flex items-center justify-between"><h3 class="text-xl font-black uppercase tracking-tight flex items-center gap-4 text-amber-500"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>Jornada Sabatina</h3><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" v-model="form.trabaja_sabado" class="sr-only peer"><div class="w-12 h-6 bg-white/10 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600 border border-white/5"></div></label></div>
              <div v-if="form.trabaja_sabado" class="grid grid-cols-2 gap-8 p-8 bg-black/40 rounded-[2rem] border border-amber-500/20 animate-fade-in">
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Entrada</label><input v-model="form.hora_entrada_sabado" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-amber-400 focus:ring-0 [color-scheme:dark]" /></div>
                <div class="space-y-2"><label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Salida</label><input v-model="form.hora_salida_sabado" type="time" class="w-full bg-transparent border-none p-0 text-3xl font-black text-amber-400 focus:ring-0 [color-scheme:dark]" /></div>
              </div>
              <div v-else class="h-[108px] flex items-center justify-center border-2 border-dashed border-white/5 rounded-[2rem] text-[10px] font-black text-neutral-700 uppercase tracking-[0.2em]">Exento de Jornada Sabatina</div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 space-y-10">
                <h3 class="text-xl font-black uppercase tracking-tight text-emerald-400 flex items-center gap-4"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>Esquema Salarial</h3>
                <div class="space-y-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-neutral-500 uppercase tracking-widest">Salario Base Mensual</label>
                        <div class="relative group"><span class="absolute left-6 top-1/2 -translate-y-1/2 text-2xl font-black text-neutral-700 group-focus-within:text-emerald-500 transition-colors">$</span><input v-model="form.salario_base" type="number" step="0.01" class="w-full bg-black/40 border border-white/10 rounded-3xl pl-12 pr-6 py-6 text-4xl font-black text-white focus:ring-2 focus:ring-emerald-500/50 transition-all" /></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4"><button v-for="freq in ['semanal', 'quincenal']" :key="freq" type="button" @click="form.frecuencia_pago = freq" :class="['py-6 rounded-2xl text-[10px] font-black uppercase tracking-widest border-2 transition-all', form.frecuencia_pago === freq ? 'bg-emerald-600 border-emerald-400 text-white shadow-lg' : 'bg-black/20 border-white/5 text-neutral-500']">{{ freq }}</button></div>
                </div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 space-y-8">
                <h3 class="text-xl font-black uppercase tracking-tight text-blue-400 flex items-center gap-4"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>Información Bancaria</h3>
                <div class="space-y-6">
                    <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">Institución Bancaria</label><input v-model="form.banco" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">No. Cuenta</label><input v-model="form.numero_cuenta" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                        <div class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">CLABE</label><input v-model="form.clabe_interbancaria" type="text" maxlength="18" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-blue-500 transition-all" /></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 bg-white/5 border border-white/10 rounded-[2.5rem] p-10 space-y-8">
                <h3 class="text-[11px] font-black uppercase tracking-widest text-red-400/60 pb-4 border-b border-white/5">Protocolo de Emergencia</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                    <div v-for="e in [{m:'contacto_emergencia_nombre', l:'Nombre'}, {m:'contacto_emergencia_telefono', l:'Teléfono'}, {m:'contacto_emergencia_parentesco', l:'Parentesco'}]" :key="e.m" class="space-y-2"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest">{{ e.l }}</label><input v-model="form[e.m]" type="text" class="w-full bg-black/30 border border-white/5 rounded-2xl px-5 py-4 text-sm focus:border-red-500 transition-all" /></div>
                </div>
                <div class="pt-4 border-t border-white/5"><label class="text-[9px] font-black text-neutral-500 uppercase tracking-widest mb-3 block">Observaciones del Reclutamiento</label><textarea v-model="form.observaciones" rows="3" class="w-full bg-black/30 border border-white/5 rounded-[1.5rem] px-6 py-4 text-sm focus:border-blue-500 transition-all resize-none"></textarea></div>
            </div>
            <div class="bg-white/5 border border-white/10 rounded-[2.5rem] p-10 flex flex-col justify-between">
                <div><h3 class="text-[11px] font-black uppercase tracking-widest text-blue-400 pb-4 border-b border-white/5">Expediente Maestro</h3><p class="text-[9px] text-neutral-500 font-bold uppercase py-6 leading-relaxed">Adjunte el contrato digitalizado o imagen de alta del colaborador.</p></div>
                <div class="relative"><input type="file" id="contrato_adjunto" @input="form.contrato_adjunto = $event.target.files[0]" class="hidden" accept=".pdf,image/*" /><label for="contrato_adjunto" class="flex flex-col items-center justify-center gap-4 p-8 border-2 border-dashed border-white/10 rounded-3xl cursor-pointer hover:border-blue-500/50 hover:bg-blue-500/5 group"><svg class="w-8 h-8 text-blue-500 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg><span class="text-[9px] font-black text-white uppercase tracking-widest text-center">{{ form.contrato_adjunto ? form.contrato_adjunto.name : 'Vincular Archivo Digital' }}</span></label></div>
            </div>
        </div>

        <div class="fixed bottom-10 left-0 right-0 z-50 px-4">
            <div class="max-w-3xl mx-auto bg-neutral-900/90 backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-4 flex items-center justify-between shadow-2xl">
                <button type="submit" :disabled="form.processing" class="w-full py-5 bg-gradient-to-r from-emerald-600 to-blue-700 rounded-[1.5rem] text-[11px] font-black text-white uppercase tracking-[0.2em] shadow-lg active:scale-95 transition-all">
                    {{ form.processing ? 'Sincronizando...' : 'Finalizar Alta de Colaborador' }}
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
.animate-fade-in { animation: fadeIn 0.8s forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
