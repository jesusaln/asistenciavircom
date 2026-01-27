<template>
  <Head :title="`${cuenta.nombre} - Cuenta Bancaria`" />

  <div class="min-h-screen bg-slate-950 text-slate-200 font-outfit pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      
      <!-- Premium Header Area -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 animate-fade-in">
        <div class="flex items-center gap-5">
           <Link 
            :href="route('cuentas-bancarias.index')" 
            class="group flex items-center justify-center w-12 h-12 bg-slate-900/50 border border-slate-800 rounded-2xl hover:bg-slate-800 hover:border-slate-700 transition-all duration-300 shadow-xl"
          >
            <FontAwesomeIcon icon="arrow-left" class="text-slate-400 group-hover:text-white transition-colors" />
          </Link>
          <div>
            <h1 class="text-4xl font-extrabold text-white tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">
              {{ cuenta.nombre }}
            </h1>
            <p class="text-slate-400 mt-1 flex items-center gap-2">
              <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
              {{ cuenta.banco }} • {{ cuenta.numero_cuenta || '**** **** **** ****' }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button
            @click="showModal = true"
            class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold rounded-2xl transition-all duration-300 shadow-xl shadow-amber-900/20 active:scale-95 flex items-center gap-3"
          >
            <FontAwesomeIcon icon="plus-circle" />
            <span>Registrar Movimiento</span>
          </button>
          <Link
            :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
            class="px-5 py-3 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800 rounded-2xl transition-all duration-300 flex items-center gap-3"
          >
            <FontAwesomeIcon icon="edit" />
            <span>Editar</span>
          </Link>
          <Link
            :href="route('cuentas-bancarias.movimientos', { cuentas_bancaria: cuenta.id })"
            class="px-5 py-3 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800 rounded-2xl transition-all duration-300 flex items-center gap-3"
          >
            <FontAwesomeIcon icon="list" />
            <span>Historial</span>
          </Link>
        </div>
      </div>

      <!-- Hero Balance Card -->
      <div class="relative group mb-10 overflow-hidden rounded-[2.5rem] animate-fade-in" style="animation-delay: 100ms">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-700 to-slate-900 opacity-90 transition-all duration-500"></div>
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl group-hover:bg-white/15 transition-all"></div>
        
        <div class="relative p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-8">
          <div>
            <span class="inline-block px-4 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-widest text-blue-100 mb-4">
              Saldo Disponible Actual
            </span>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-medium text-blue-200">$</span>
              <h2 class="text-6xl font-black text-white tracking-tighter">
                {{ formatMonto(cuenta.saldo_actual) }}
              </h2>
            </div>
            <p class="text-blue-200/60 text-sm mt-4 font-medium uppercase tracking-widest">Saldo Inicial: ${{ formatMonto(cuenta.saldo_inicial) }}</p>
          </div>
          <div class="p-8 bg-black/20 backdrop-blur-3xl rounded-3xl border border-white/5 shadow-2xl">
             <div :style="{ color: cuenta.color || '#fff' }" class="filter drop-shadow-[0_0_15px_rgba(255,255,255,0.3)]">
                <FontAwesomeIcon icon="landmark" class="text-7xl" />
             </div>
          </div>
        </div>
      </div>

      <!-- Info Tabs -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
        <div class="lg:col-span-2 bg-slate-900/60 backdrop-blur-xl border border-slate-800/50 rounded-[2.5rem] overflow-hidden p-8 md:p-10 space-y-8 animate-fade-in-up" style="animation-delay: 200ms">
           <h3 class="text-xl font-bold text-white flex items-center gap-3 mb-2">
              <div class="w-1.5 h-6 bg-blue-500 rounded-full"></div>
              Detalles Administrativos
           </h3>
           <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
              <div class="flex flex-col gap-1">
                 <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Entidad Bancaria</span>
                 <span class="text-lg font-bold text-white tracking-tight">{{ cuenta.banco }}</span>
              </div>
              <div class="flex flex-col gap-1">
                 <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Número de Cuenta</span>
                 <span class="text-lg font-bold text-white font-mono select-all">{{ cuenta.numero_cuenta || '🔒 Protegido' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                 <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">CLABE Interbancaria</span>
                 <span class="text-lg font-bold text-white font-mono select-all">{{ cuenta.clabe || 'No asociada' }}</span>
              </div>
              <div class="flex flex-col gap-1">
                 <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Moneda / Divisa</span>
                 <span class="text-lg font-bold text-emerald-400 capitalize">{{ cuenta.moneda }} ({{ cuenta.tipo }})</span>
              </div>
           </div>
           
           <div class="pt-6 border-t border-slate-800 flex items-center gap-4">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Estado Sistémico:</span>
              <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800">
                 <span class="w-2 h-2 rounded-full" :class="cuenta.activa ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500'"></span>
                 <span class="text-[10px] font-black uppercase tracking-tighter" :class="cuenta.activa ? 'text-emerald-400' : 'text-rose-400'">
                   {{ cuenta.activa ? 'Activa y Operativa' : 'Suspendida / Inactiva' }}
                 </span>
              </div>
           </div>
        </div>

        <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/50 rounded-[2.5rem] p-10 flex flex-col justify-center animate-fade-in-up" style="animation-delay: 300ms">
           <div class="w-12 h-12 bg-slate-950 rounded-2xl flex items-center justify-center mb-6 border border-slate-800 shadow-xl">
              <FontAwesomeIcon icon="sticky-note" class="text-slate-500 text-lg" />
           </div>
           <h3 class="text-xl font-bold text-white mb-4">Notas Internas</h3>
           <p class="text-sm text-slate-400 italic leading-relaxed bg-slate-950/30 p-6 rounded-3xl border border-slate-800/30 shadow-inner">
             {{ cuenta.notas || 'Sin anotaciones adicionales para esta cuenta bancaria.' }}
           </p>
        </div>
      </div>

      <!-- Latest Transactions Premium Table -->
      <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/50 rounded-[2.5rem] overflow-hidden animate-fade-in-up shadow-2xl shadow-black/50" style="animation-delay: 400ms">
        <div class="px-10 py-8 border-b border-slate-800 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white flex items-center gap-3">
             <FontAwesomeIcon icon="receipt" class="text-blue-500" />
             Últimos Movimientos
          </h3>
          <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest hidden sm:block">Mostrando los registros más recientes</span>
        </div>
        
        <div v-if="movimientos.length > 0" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-950/30">
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Fecha / Periodo</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest">Concepto & Glosa</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Naturaleza</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Monto Unitario</th>
                <th class="px-10 py-5 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center">Estatus</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50">
              <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-blue-500/5 transition-colors group/row">
                <td class="px-10 py-6">
                   <div class="flex flex-col gap-0.5">
                      <span class="text-sm font-bold text-white group-hover/row:text-blue-400 transition-colors">{{ formatFecha(mov.fecha) }}</span>
                      <span class="text-[10px] font-semibold text-slate-600 uppercase tracking-tighter">Transacción #{{ mov.id }}</span>
                   </div>
                </td>
                <td class="px-10 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-slate-200">{{ mov.concepto }}</span>
                    <div v-if="mov.folio_venta" class="flex items-center gap-1.5 mt-1">
                       <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                       <span class="text-[10px] font-bold text-blue-500 tracking-wider">FOLIO: {{ mov.folio_venta }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-10 py-6 text-center">
                  <span 
                    class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                    :class="mov.tipo === 'deposito' ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'"
                  >
                    {{ mov.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                  </span>
                </td>
                <td class="px-10 py-6 text-right">
                   <span class="text-lg font-black tracking-tight" :class="mov.tipo === 'deposito' ? 'text-emerald-400' : 'text-rose-400'">
                      {{ mov.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(mov.monto) }}
                   </span>
                </td>
                <td class="px-10 py-6 text-center">
                   <span 
                    class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter border"
                    :class="getEstadoClass(mov.estado)"
                   >
                     {{ getEstadoLabel(mov.estado) }}
                   </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-else class="p-24 text-center">
          <div class="w-20 h-20 bg-slate-800 rounded-3xl flex items-center justify-center mx-auto mb-6 opacity-30">
             <FontAwesomeIcon icon="receipt" class="text-4xl text-slate-400" />
          </div>
          <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">Sin actividad financiera registrada</p>
        </div>
      </div>
    </div>

    <!-- Premium Modal for Manual Movement -->
    <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-2xl" @click="showModal = false"></div>
      <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-[3rem] shadow-2xl overflow-hidden animate-zoom-in">
        <div class="bg-gradient-to-r from-amber-600 to-amber-700 p-8 text-white relative overflow-hidden">
           <div class="absolute -right-10 -top-10 opacity-10">
              <FontAwesomeIcon icon="plus-circle" class="text-9xl" />
           </div>
           <h3 class="text-2xl font-black tracking-tight">Movimiento Manual</h3>
           <p class="text-amber-100 text-xs font-bold uppercase tracking-widest mt-1 opacity-80">Registro interno de flujo</p>
        </div>
        
        <form @submit.prevent="registrarMovimiento" class="p-10 space-y-8">
          <div class="flex gap-4">
            <label class="flex-1 cursor-pointer group">
              <input type="radio" v-model="form.tipo" value="deposito" class="sr-only peer" />
              <div class="p-4 rounded-2xl border border-slate-800 bg-slate-950/50 text-slate-500 peer-checked:bg-emerald-500/10 peer-checked:border-emerald-500 peer-checked:text-emerald-400 text-center transition-all group-hover:bg-slate-800">
                 <FontAwesomeIcon icon="arrow-up" class="block mx-auto mb-2 text-xl" />
                 <span class="text-[10px] font-black uppercase tracking-widest">Depósito</span>
              </div>
            </label>
            <label class="flex-1 cursor-pointer group">
              <input type="radio" v-model="form.tipo" value="retiro" class="sr-only peer" />
              <div class="p-4 rounded-2xl border border-slate-800 bg-slate-950/50 text-slate-500 peer-checked:bg-rose-500/10 peer-checked:border-rose-500 peer-checked:text-rose-400 text-center transition-all group-hover:bg-slate-800">
                 <FontAwesomeIcon icon="arrow-down" class="block mx-auto mb-2 text-xl" />
                 <span class="text-[10px] font-black uppercase tracking-widest">Retiro</span>
              </div>
            </label>
          </div>

          <div class="space-y-3">
             <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Monto de la Operación</label>
             <div class="relative group/input">
                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-xl font-bold text-slate-600 group-focus-within/input:text-amber-400">$</span>
                <input type="number" v-model="form.monto" step="0.01" min="0.01" required
                  class="w-full bg-slate-950 border border-slate-800 rounded-2xl pl-12 pr-6 py-5 text-2xl font-black text-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500/50 transition-all placeholder-slate-800"
                  placeholder="0.00" />
             </div>
          </div>

          <div class="space-y-3">
             <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Concepto Comercial</label>
             <input type="text" v-model="form.concepto" required placeholder="Ej: Ajuste de saldo, Retiro caja..."
               class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-6 py-5 text-white focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500/50 transition-all placeholder-slate-700" />
          </div>

          <div class="grid grid-cols-2 gap-6">
             <div class="space-y-3">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Clasificación</label>
                <select v-model="form.categoria" class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-white appearance-none focus:ring-2 focus:ring-amber-500/20 transition-all">
                  <option value="otro">Otro</option>
                  <option value="prestamo">Préstamo</option>
                  <option value="venta">Activo</option>
                  <option value="traspaso">Traspaso</option>
                  <option value="ajuste">Ajuste</option>
                </select>
             </div>
             <div class="space-y-3">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Referencia</label>
                <input type="text" v-model="form.referencia" placeholder="Opcional"
                  class="w-full bg-slate-950 border border-slate-800 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-amber-500/20 transition-all placeholder-slate-700" />
             </div>
          </div>

          <div class="flex gap-4 pt-6">
            <button type="button" @click="showModal = false" class="flex-1 py-5 bg-slate-800 text-slate-300 font-bold rounded-2xl active:scale-95 transition-all">
              Cancelar
            </button>
            <button type="submit" :disabled="form.processing"
              class="flex-1 py-5 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-amber-900/20 active:scale-95 transition-all disabled:opacity-50">
              {{ form.processing ? 'Procesando...' : 'Confirmar Registro' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuenta: { type: Object, required: true },
  movimientos: { type: Array, default: () => [] },
})

const showModal = ref(false)

const form = useForm({
  tipo: 'deposito',
  monto: '',
  concepto: '',
  categoria: 'otro',
  referencia: '',
})

const registrarMovimiento = () => {
  form.post(route('cuentas-bancarias.registrar-movimiento', { cuentas_bancaria: props.cuenta.id }), {
    onSuccess: () => {
      showModal.value = false
      form.reset()
    },
  })
}

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const getEstadoClass = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-100 text-yellow-700',
    'conciliado': 'bg-green-100 text-green-700',
    'ignorado': 'bg-gray-100 text-gray-600 dark:text-gray-300',
  }
  return clases[estado] || 'bg-gray-100 text-gray-600 dark:text-gray-300'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'conciliado': 'Conciliado',
    'ignorado': 'Ignorado',
  }
  return labels[estado] || estado
}
</script>

