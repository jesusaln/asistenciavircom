<template>
  <Head title="Cuentas Bancarias" />

  <div class="min-h-screen bg-slate-950 text-slate-200 font-outfit pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      
      <!-- Header Area -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 animate-fade-in">
        <div>
          <h1 class="text-4xl font-extrabold text-white tracking-tight flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-600/20 rounded-2xl flex items-center justify-center border border-blue-500/30">
              <FontAwesomeIcon icon="landmark" class="text-blue-500" />
            </div>
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-white to-slate-400">
              Cuentas Bancarias
            </span>
          </h1>
          <p class="text-slate-400 mt-2 flex items-center gap-2">
            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
            Gestiona tus cuentas bancarias y flujo de efectivo
          </p>
        </div>
        
        <div class="flex items-center gap-4">
          <Link
            :href="route('traspasos-bancarios.index')"
            class="group flex items-center gap-3 px-6 py-3 bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800 rounded-2xl transition-all duration-300"
          >
            <FontAwesomeIcon icon="exchange-alt" class="group-hover:rotate-180 transition-transform duration-500" />
            <span class="font-bold">Ver Traspasos</span>
          </Link>
          <Link
            :href="route('cuentas-bancarias.create')"
            class="flex items-center gap-3 px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl transition-all duration-300 shadow-xl shadow-blue-900/20 active:scale-95"
          >
            <FontAwesomeIcon icon="plus" />
            <span>Nueva Cuenta</span>
          </Link>
        </div>
      </div>

      <!-- Total Balance Visualization -->
      <div class="relative group mb-10 overflow-hidden rounded-[2.5rem] animate-fade-in" style="animation-delay: 100ms">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-indigo-700 to-slate-900 opacity-90 transition-all duration-500 group-hover:scale-105"></div>
        <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/10 rounded-full blur-3xl group-hover:bg-white/15 transition-all"></div>
        
        <div class="relative p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-8">
          <div>
            <span class="inline-block px-4 py-1.5 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-widest text-blue-100 mb-4">
              Saldo Consolidado
            </span>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-medium text-blue-200">$</span>
              <h2 class="text-6xl font-black text-white tracking-tighter">
                {{ formatMonto(totales.saldo_total) }}
              </h2>
            </div>
            <div class="flex items-center gap-6 mt-6">
              <div class="flex flex-col">
                <span class="text-xs font-bold text-blue-200/60 uppercase">Cuentas Activas</span>
                <span class="text-xl font-bold text-white">{{ totales.cuentas_activas }}</span>
              </div>
              <div class="w-px h-10 bg-white/10"></div>
              <div class="flex flex-col">
                <span class="text-xs font-bold text-blue-200/60 uppercase">Estado</span>
                <span class="text-xl font-bold text-emerald-400">Saludable</span>
              </div>
            </div>
          </div>
          <div class="p-8 bg-black/20 backdrop-blur-3xl rounded-3xl border border-white/5 shadow-2xl">
            <FontAwesomeIcon icon="wallet" class="text-7xl text-white/80" />
          </div>
        </div>
      </div>

      <!-- Accounts Grid -->
      <div v-if="cuentas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div
          v-for="(cuenta, index) in cuentas"
          :key="cuenta.id"
          class="group relative animate-fade-in-up"
          :style="{ animationDelay: `${200 + index * 50}ms` }"
        >
          <!-- Active Glow Effect -->
          <div v-if="cuenta.activa" class="absolute -inset-0.5 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-[2rem] blur opacity-0 group-hover:opacity-100 transition duration-500"></div>
          
          <div 
            class="relative bg-slate-900/60 backdrop-blur-xl border border-slate-800/50 rounded-[2rem] overflow-hidden transition-all duration-300 group-hover:-translate-y-2 group-hover:border-slate-700/50 group-hover:shadow-2xl shadow-blue-900/10"
            :class="{ 'opacity-50 grayscale select-none': !cuenta.activa }"
          >
            <!-- Bank Color Stripe -->
            <div class="h-1.5 w-full flex">
              <div :style="{ backgroundColor: cuenta.color }" class="flex-1 opacity-80 group-hover:opacity-100 transition-opacity"></div>
              <div :style="{ backgroundColor: cuenta.color }" class="flex-none w-1/3 blur-sm opacity-50"></div>
            </div>
            
            <div class="p-8">
              <!-- Bank Header -->
              <div class="flex items-start justify-between mb-8">
                <div class="space-y-1">
                  <h3 class="text-lg font-bold text-white group-hover:text-blue-400 transition-colors">{{ cuenta.nombre }}</h3>
                  <div class="flex flex-col gap-0.5">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ cuenta.banco }}</span>
                    <span class="text-[10px] font-mono text-slate-600">{{ cuenta.numero_cuenta_mascarado }}</span>
                  </div>
                </div>
                <div 
                  v-if="!cuenta.activa"
                  class="px-2.5 py-1 bg-slate-800 text-slate-400 text-[10px] font-bold rounded-full border border-slate-700 uppercase"
                >
                  Inactiva
                </div>
              </div>

              <!-- Balance Display -->
              <div class="bg-black/30 border border-slate-800/50 rounded-2xl p-5 mb-8 relative group/balance overflow-hidden transition-all hover:bg-black/40">
                <div class="absolute top-0 right-0 p-2 opacity-5">
                   <FontAwesomeIcon icon="piggy-bank" class="text-4xl text-white" />
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Saldo Disponible</p>
                <div class="flex items-baseline gap-1">
                  <span class="text-sm font-bold text-slate-400">$</span>
                  <p class="text-3xl font-black transition-all group-hover/balance:scale-105 origin-left" :class="cuenta.saldo_actual >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ formatMonto(cuenta.saldo_actual) }}
                  </p>
                </div>
                <div class="flex items-center justify-between mt-4">
                   <div class="h-1 bg-slate-800 flex-1 rounded-full mr-4">
                      <div class="h-full bg-blue-500 rounded-full w-2/3 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
                   </div>
                   <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ cuenta.tipo }}</span>
                </div>
              </div>

              <!-- Stats & Actions -->
              <div class="flex items-center justify-between mb-6">
                 <div class="flex items-center gap-2 text-slate-400">
                    <FontAwesomeIcon icon="history" class="text-[10px]" />
                    <span class="text-xs font-bold">{{ cuenta.movimientos_count }} ops</span>
                 </div>
                 <div class="flex gap-2">
                    <button 
                      @click="verDetalle(cuenta)"
                      class="w-10 h-10 bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl flex items-center justify-center transition-all"
                    >
                      <FontAwesomeIcon icon="eye" />
                    </button>
                    <Link
                      :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuenta.id })"
                      class="w-10 h-10 bg-slate-900 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl flex items-center justify-center transition-all"
                    >
                      <FontAwesomeIcon icon="edit" />
                    </Link>
                 </div>
              </div>

              <div class="flex gap-3">
                 <Link
                    :href="route('cuentas-bancarias.show', { cuentas_bancaria: cuenta.id })"
                    class="flex-1 py-4 bg-slate-950 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-blue-900/10 to-transparent text-center text-xs font-black uppercase tracking-widest rounded-2xl transition-all"
                  >
                    Ver Libro Mayor
                  </Link>
                  <button
                    v-if="cuenta.movimientos_count === 0"
                    @click="eliminar(cuenta)"
                    class="w-14 h-14 bg-rose-950/20 border border-rose-900/30 text-rose-500 hover:bg-rose-500 hover:text-white rounded-2xl flex items-center justify-center transition-all group/trash"
                  >
                    <FontAwesomeIcon icon="trash" class="group-hover/trash:scale-125 transition-transform" />
                  </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-slate-900/50 backdrop-blur-xl border border-slate-800 rounded-[3rem] p-20 text-center animate-pulse-slow">
        <div class="w-24 h-24 bg-slate-800 rounded-3xl flex items-center justify-center mx-auto mb-8">
           <FontAwesomeIcon icon="piggy-bank" class="text-5xl text-slate-600" />
        </div>
        <h3 class="text-2xl font-bold text-white mb-2">Sin cuentas configuradas</h3>
        <p class="text-slate-400 mb-10 max-w-md mx-auto italic">Comienza agregando tu primera cuenta bancaria para llevar el control absoluto de tu flujo financiero.</p>
        <Link
          :href="route('cuentas-bancarias.create')"
          class="inline-flex items-center gap-4 px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black uppercase tracking-widest rounded-3xl transition-all shadow-2xl shadow-blue-500/20"
        >
          <FontAwesomeIcon icon="plus" />
          <span>Configurar Mi Primera Cuenta</span>
        </Link>
      </div>
    </div>

    <!-- Premium Detail Modal -->
    <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 lg:p-0">
      <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-xl" @click="showModal = false"></div>
      
      <div class="relative w-full max-w-2xl bg-slate-900/90 border border-slate-800 rounded-[3rem] shadow-2xl overflow-hidden animate-zoom-in">
        <!-- Progress Color Loader -->
        <div class="h-1.5 w-full bg-slate-800">
           <div :style="{ width: '100%', backgroundColor: cuentaSeleccionada?.color }" class="h-full"></div>
        </div>
        
        <div class="p-8 md:p-12">
          <div class="flex items-center justify-between mb-10">
            <div class="flex items-center gap-5">
              <div class="w-16 h-16 bg-slate-950 border border-slate-800 rounded-2xl flex items-center justify-center text-3xl shadow-xl">
                 <FontAwesomeIcon icon="university" class="text-slate-500" />
              </div>
              <div>
                <h3 class="text-3xl font-black text-white leading-tight">{{ cuentaSeleccionada?.nombre }}</h3>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ cuentaSeleccionada?.banco }}</span>
              </div>
            </div>
            <button @click="showModal = false" class="w-12 h-12 bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white rounded-2xl transition-all">
              <FontAwesomeIcon icon="times" class="text-lg" />
            </button>
          </div>

          <!-- Balance Feature -->
          <div class="bg-gradient-to-br from-blue-600 to-indigo-800 rounded-[2.5rem] p-10 text-white shadow-2xl mb-10 relative overflow-hidden group/m">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
            <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-2 opacity-80">Saldo Consolidado en Cuenta</p>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-medium text-blue-200 opacity-60">$</span>
              <p class="text-6xl font-black tracking-tighter group-hover/m:scale-105 transition-transform origin-left">
                {{ formatMonto(cuentaSeleccionada?.saldo_actual) }}
              </p>
            </div>
          </div>

          <!-- Extended Grid -->
          <div class="grid grid-cols-2 gap-6 mb-10">
            <div class="bg-slate-950/50 border border-slate-800 rounded-3xl p-6">
               <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Número de Cuenta</p>
               <p class="text-lg font-bold text-white selection:bg-blue-500/30">{{ cuentaSeleccionada?.numero_cuenta || '🔒 Enmascarado' }}</p>
            </div>
            <div class="bg-slate-950/50 border border-slate-800 rounded-3xl p-6">
               <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Tipo / Línea</p>
               <p class="text-lg font-bold text-white capitalize flex items-center gap-2">
                  <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                  {{ cuentaSeleccionada?.tipo }}
               </p>
            </div>
          </div>

          <div v-if="cuentaSeleccionada?.clabe" class="bg-slate-950/50 border border-slate-800 rounded-[2rem] p-8 mb-10">
             <div class="flex items-center justify-between">
                <div>
                   <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">CLABE Interbancaria (18 dígitos)</p>
                   <p class="text-2xl font-mono text-white tracking-widest select-all">{{ cuentaSeleccionada?.clabe }}</p>
                </div>
                <FontAwesomeIcon icon="circle-check" class="text-3xl text-emerald-500 opacity-20" />
             </div>
          </div>

          <!-- Notes -->
          <div v-if="cuentaSeleccionada?.notas" class="mb-12">
             <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Notas Internas</h4>
             <div class="bg-slate-950/30 border-l-4 border-blue-500/50 p-6 rounded-r-3xl italic text-slate-300 leading-relaxed shadow-inner">
                {{ cuentaSeleccionada?.notas }}
             </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-4">
            <button @click="showModal = false" class="flex-1 py-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-2xl transition-all active:scale-95">
              Cerrar Vista
            </button>
            <Link
              :href="route('cuentas-bancarias.edit', { cuentas_bancaria: cuentaSeleccionada?.id })"
              class="flex-1 py-5 bg-white text-slate-950 font-black text-center rounded-2xl transition-all hover:bg-slate-200 active:scale-95 shadow-xl shadow-white/5"
            >
              Gestionar Cuenta
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

defineOptions({ layout: AppLayout })

const props = defineProps({
  cuentas: { type: Array, default: () => [] },
  totales: { type: Object, default: () => ({ saldo_total: 0, cuentas_activas: 0 }) },
})

const showModal = ref(false)
const cuentaSeleccionada = ref(null)

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const verDetalle = (cuenta) => {
  cuentaSeleccionada.value = cuenta
  showModal.value = true
}

const eliminar = (cuenta) => {
  if (cuenta.movimientos_count > 0) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'No se puede eliminar una cuenta con movimientos. Desactívela en su lugar.'
    })
    return
  }
  Swal.fire({
    title: '¿Eliminar cuenta?',
    text: `¿Eliminar la cuenta "${cuenta.nombre}"?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('cuentas-bancarias.destroy', { cuentas_bancaria: cuenta.id }))
    }
  })
}
</script>




