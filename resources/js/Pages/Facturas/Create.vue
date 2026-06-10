<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue'
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue'
import axios from 'axios'
import DialogModal from '@/Components/DialogModal.vue'
import Swal from '@/Utils/Swal'

defineOptions({ layout: AppLayout })

const { colors, cssVars } = useCompanyColors()

const props = defineProps({
  clientes: Array,
  clienteSeleccionado: Object,
  ventasPendientes: Array,
  catalogos: Object,
  ventaPreseleccionada: [String, Number],
  datosPrellenado: Object
})

const form = useForm({
  cliente_id: props.clienteSeleccionado?.id || '',
  rfc: props.clienteSeleccionado?.rfc || '',
  ventas_ids: props.ventasPendientes?.filter(v => v.selected).map(v => v.id) || [],
  regimen_fiscal: props.clienteSeleccionado?.regimen_fiscal || '',
  codigo_postal: props.clienteSeleccionado?.domicilio_fiscal_cp || props.clienteSeleccionado?.codigo_postal || '',
  uso_cfdi: props.datosPrellenado?.uso_cfdi || props.clienteSeleccionado?.uso_cfdi || 'G03',
  forma_pago: props.datosPrellenado?.forma_pago || props.clienteSeleccionado?.forma_pago_default || '99',
  metodo_pago: props.datosPrellenado?.metodo_pago || 'PPD',
  observaciones: '',
  extra_services: []
})

// Lógica Automática SAT (Bidireccional)
watch(() => form.forma_pago, (nuevaForma) => {
  if (nuevaForma === '99') {
    // Si la forma es "Por definir", el método DEBE ser PPD
    if (form.metodo_pago !== 'PPD') form.metodo_pago = 'PPD'
  } else {
    // Si la forma es real (Efectivo, Tarjeta, etc), el método suele ser PUE.
    // Si estaba en PPD, lo cambiamos a PUE porque PPD exige 99.
    if (form.metodo_pago === 'PPD') form.metodo_pago = 'PUE'
  }
})

watch(() => form.metodo_pago, (nuevoMetodo) => {
  if (nuevoMetodo === 'PPD') {
    // Si el método es PPD, la forma DEBE ser 99
    if (form.forma_pago !== '99') form.forma_pago = '99'
  } else { // PUE
    // Si el método es PUE, la forma NO puede ser 99
    if (form.forma_pago === '99') form.forma_pago = '01' // Default a Efectivo
  }
})

// Totales calculados
const totales = computed(() => {
  const selected = props.ventasPendientes.filter(v => form.ventas_ids.includes(v.id))
  const extraSubtotal = form.extra_services.reduce((acc, s) => acc + (parseFloat(s.precio) * s.cantidad), 0)
  const extraIva = extraSubtotal * 0.16 // Asumiendo IVA 16% para servicios directos
  
  return {
    subtotal: selected.reduce((acc, v) => acc + parseFloat(v.subtotal), 0) + extraSubtotal,
    iva: selected.reduce((acc, v) => acc + parseFloat(v.iva), 0) + extraIva,
    total: selected.reduce((acc, v) => acc + parseFloat(v.total), 0) + extraSubtotal + extraIva,
    count: selected.length + form.extra_services.length
  }
})

// Cambio de cliente recarga la página para traer sus ventas
const onClienteSelectedFromSearch = (cliente) => {
  if (cliente) {
    form.cliente_id = cliente.id
    router.visit(route('facturas.create'), {
      data: { cliente_id: cliente.id },
      only: ['ventasPendientes', 'clienteSeleccionado'],
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        form.ventas_ids = []
        if (props.clienteSeleccionado) {
          form.rfc = props.clienteSeleccionado.rfc || ''
          form.regimen_fiscal = props.clienteSeleccionado.regimen_fiscal || ''
          form.codigo_postal = props.clienteSeleccionado.domicilio_fiscal_cp || props.clienteSeleccionado.codigo_postal || ''
          form.uso_cfdi = props.clienteSeleccionado.uso_cfdi || 'G03'
          form.forma_pago = props.clienteSeleccionado.forma_pago_default || '99'
        }
      }
    })
  } else {
    form.cliente_id = ''
    form.rfc = ''
    form.regimen_fiscal = ''
    form.codigo_postal = ''
    form.ventas_ids = []
    router.visit(route('facturas.create'), {
      preserveState: false,
      preserveScroll: true
    })
  }
}

const toggleVenta = async (id) => {
  const index = form.ventas_ids.indexOf(id)
  if (index === -1) {
    // Validar mezcla de estados PUE/PPD
    if (form.ventas_ids.length > 0) {
       const ventaNueva = props.ventasPendientes.find(v => v.id === id)
       const ventaExistente = props.ventasPendientes.find(v => v.id === form.ventas_ids[0])
       
       if (ventaNueva && ventaExistente && ventaNueva.pagado !== ventaExistente.pagado) {
          const { isConfirmed } = await Swal.fire({ title: 'Mezcla de métodos de pago', text: 'Estás mezclando ventas Pagadas (PUE) con ventas Pendientes (PPD). Esto puede causar conflictos fiscales. ¿Deseas continuar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'No' })
          if (!isConfirmed) return
       }
    }
    form.ventas_ids.push(id)
  } else {
    form.ventas_ids.splice(index, 1)
  }

  // Auto-ajustar método y forma según la última venta seleccionada (o la única)
  if (form.ventas_ids.length > 0) {
      // Tomamos la última seleccionada como referencia principal
      const referenciaId = form.ventas_ids[form.ventas_ids.length - 1]
      const venta = props.ventasPendientes.find(v => v.id === referenciaId)
      
      if (venta) {
          form.metodo_pago = venta.metodo_pago_sugerido
          form.forma_pago = venta.forma_pago_sugerida
      }
  }
}

const showModalSat = ref(false)
const itemsParaCompletarSat = ref([])
const guardandoSat = ref(false)

const itemsFaltantesSat = computed(() => {
  const faltantes = []
  
  // 1. Verificar ventas seleccionadas
  const ventasSeleccionadas = props.ventasPendientes.filter(v => form.ventas_ids.includes(v.id))
  ventasSeleccionadas.forEach(v => {
    if (v.items) {
      v.items.forEach(item => {
        const satClave = item.sat_clave_prod_serv
        const satUnidad = item.sat_clave_unidad
        
        if (!satClave || !satUnidad) {
          const yaExiste = faltantes.some(f => f.id === item.ventable_id && f.tipo === (item.ventable_type === 'App\\Models\\Producto' ? 'producto' : 'servicio'))
          if (!yaExiste) {
            faltantes.push({
              id: item.ventable_id,
              tipo: item.ventable_type === 'App\\Models\\Producto' ? 'producto' : 'servicio',
              nombre: item.nombre,
              sat_clave_prod_serv: satClave || '',
              sat_clave_unidad: satUnidad || ''
            })
          }
        }
      })
    }
  })
  
  // 2. Verificar servicios adicionales
  form.extra_services.forEach(item => {
    const satClave = item.sat_clave_prod_serv
    const satUnidad = item.sat_clave_unidad
    
    if (!satClave || !satUnidad) {
      const yaExiste = faltantes.some(f => f.id === item.id && f.tipo === 'servicio')
      if (!yaExiste) {
        faltantes.push({
          id: item.id,
          tipo: 'servicio',
          nombre: item.nombre,
          sat_clave_prod_serv: satClave || '',
          sat_clave_unidad: satUnidad || ''
        })
      }
    }
  })
  
  return faltantes
})

const submit = () => {
  if (form.ventas_ids.length === 0 && form.extra_services.length === 0) {
    Swal.fire({ title: 'Campos requeridos', text: 'Selecciona al menos una venta o servicio para facturar', icon: 'warning', confirmButtonText: 'Aceptar' })
    return
  }
  
  // Si hay items con claves SAT faltantes, abrir el modal de captura
  const faltantes = itemsFaltantesSat.value
  if (faltantes.length > 0) {
    itemsParaCompletarSat.value = JSON.parse(JSON.stringify(faltantes))
    showModalSat.value = true
    return
  }
  
  form.post(route('facturas.store'))
}

const guardarClavesSat = async () => {
  const incompletos = itemsParaCompletarSat.value.some(item => !item.sat_clave_prod_serv || !item.sat_clave_unidad)
  if (incompletos) {
    Swal.fire({
      title: 'Campos incompletos',
      text: 'Por favor ingrese la clave de producto y clave de unidad para todos los elementos.',
      icon: 'warning',
      confirmButtonText: 'Aceptar'
    })
    return
  }
  
  guardandoSat.value = true
  try {
    const promesas = itemsParaCompletarSat.value.map(item => {
      const url = item.tipo === 'producto' 
        ? route('productos.sat.update', item.id) 
        : route('servicios.sat.update', item.id)
        
      return axios.put(url, {
        sat_clave_prod_serv: item.sat_clave_prod_serv,
        sat_clave_unidad: item.sat_clave_unidad
      })
    })
    
    await Promise.all(promesas)
    
    // Actualizar localmente en el listado para evitar volver a pedir al enviar
    itemsParaCompletarSat.value.forEach(completado => {
      props.ventasPendientes.forEach(v => {
        if (v.items) {
          v.items.forEach(item => {
            const itemTipo = item.ventable_type === 'App\\Models\\Producto' ? 'producto' : 'servicio'
            if (item.ventable_id === completado.id && itemTipo === completado.tipo) {
              item.sat_clave_prod_serv = completado.sat_clave_prod_serv
              item.sat_clave_unidad = completado.sat_clave_unidad
            }
          })
        }
      })
      
      form.extra_services.forEach(item => {
        if (item.id === completado.id && item.tipo === completado.tipo) {
          item.sat_clave_prod_serv = completado.sat_clave_prod_serv
          item.sat_clave_unidad = completado.sat_clave_unidad
        }
      })
    })
    
    showModalSat.value = false
    Swal.fire({
      title: 'Claves guardadas',
      text: 'Las claves SAT se han actualizado correctamente en el catálogo. Se procederá a timbrar.',
      icon: 'success',
      confirmButtonText: 'Continuar'
    }).then(() => {
      form.post(route('facturas.store'))
    })
    
  } catch (error) {
    console.error('Error al guardar claves SAT:', error)
    Swal.fire({
      title: 'Error',
      text: 'Ocurrió un error al intentar actualizar las claves SAT.',
      icon: 'error',
      confirmButtonText: 'Aceptar'
    })
  } finally {
    guardandoSat.value = false
  }
}

const agregarServicio = (item) => {
   const existe = form.extra_services.find(s => s.id === item.id)
   if (existe) {
     existe.cantidad++
   } else {
     form.extra_services.push({
       id: item.id,
       nombre: item.nombre,
       precio: item.precio,
       cantidad: 1,
       tipo: 'servicio',
       sat_clave_prod_serv: item.sat_clave_prod_serv || item.clave_sat || '',
       sat_clave_unidad: item.sat_clave_unidad || item.unidad_sat || '',
     })
   }
}

const eliminarServicio = (index) => {
   form.extra_services.splice(index, 1)
}

const formatearMoneda = (monto) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(monto)
}
</script>

<template>
  <div :style="cssVars" class="min-h-screen bg-[var(--ui-surface)] font-sans text-slate-300">
    <div class="py-12">
      <div class="w-full mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">
        
        <div class="mb-8">
            <Link :href="route('facturas.index')" class="text-indigo-400 hover:text-indigo-300 text-sm font-medium flex items-center mb-2">
                &larr; Volver a Facturas
            </Link>
            <h2 class="text-3xl font-bold text-white">Nueva Factura</h2>
            <p class="text-slate-400 mt-1">Selecciona un cliente y las ventas que deseas facturar.</p>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-8">
          
          <!-- Columna Izquierda: Selección -->
          <div class="md:col-span-2 space-y-6">
            
            <!-- Step 1: Cliente -->
            <div class="bg-slate-800 shadow-xl border border-slate-700/50 sm:rounded-2xl p-6 relative overflow-hidden">
               <div class="absolute top-0 right-0 p-4 opacity-10">
                   <svg class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                   </svg>
               </div> 
              <h3 class="text-lg font-medium leading-6 text-white mb-4 flex items-center">
                  <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-400 text-sm font-bold mr-3 border border-indigo-500/30">1</span>
                  Selección de Cliente
              </h3>
              <div class="grid grid-cols-1 gap-6 relative z-10">
                <div>
                   <BuscarCliente
                      :clientes="clientes"
                      :cliente-seleccionado="clienteSeleccionado"
                      @cliente-seleccionado="onClienteSelectedFromSearch"
                      label-busqueda="Cliente a Facturar"
                      placeholder-busqueda="Buscar por nombre, RFC..."
                      :mostrar-opcion-nuevo-cliente="false"
                   />
                  <p class="mt-3 text-sm text-slate-500" v-if="!form.cliente_id">
                    Seleccione un cliente para ver sus ventas pendientes.
                  </p>
                </div>
              </div>
            </div>

            <!-- Step 2: Ventas -->
            <div class="bg-slate-800 shadow-xl border border-slate-700/50 sm:rounded-2xl p-6 relative overflow-hidden" v-if="form.cliente_id">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium leading-6 text-white flex items-center">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-400 text-sm font-bold mr-3 border border-indigo-500/30">2</span>
                    Ventas Pendientes
                </h3>
                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-medium bg-indigo-900/50 text-indigo-300 border border-indigo-700/50">
                    {{ ventasPendientes.length }} ventas disponibles
                </span>
              </div>

              <div class="overflow-hidden rounded-xl border border-slate-700/50" v-if="ventasPendientes.length > 0">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                  <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left">
                        <input type="checkbox" class="rounded-xl border-slate-600 bg-slate-800 text-indigo-600 focus:ring-brand-500 focus:ring-offset-slate-900" 
                          :checked="form.ventas_ids.length === ventasPendientes.length && ventasPendientes.length > 0"
                          @change="form.ventas_ids = $event.target.checked ? ventasPendientes.map(v => v.id) : []"
                        >
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Folio</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Estado Pago</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Concepto</th>
                      <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="venta in ventasPendientes" :key="venta.id" 
                        class="cursor-pointer hover:bg-slate-700/30 transition-colors"
                        @click="toggleVenta(venta.id)"
                        :class="{'bg-indigo-900/10': form.ventas_ids.includes(venta.id)}"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" 
                          :checked="form.ventas_ids.includes(venta.id)"
                          @change="toggleVenta(venta.id)"
                          @click.stop
                          class="rounded-xl border-slate-600 bg-slate-800 text-indigo-600 focus:ring-brand-500 focus:ring-offset-slate-900">
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-white">#{{ venta.folio }}</div>
                        <div class="text-xs text-slate-500">{{ venta.fecha }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-xl text-xs font-medium" :class="venta.pagado ? 'bg-emerald-400/10 text-emerald-400' : 'bg-brand-400/10 text-amber-400'">
                            {{ venta.etiqueta_pago }}
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <div class="text-sm text-slate-300">{{ venta.descripcion }}</div>
                        <div class="text-xs text-indigo-400 mt-1" v-if="venta.items_count > 1">+ {{ venta.items_count - 1 }} artículos más</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-emerald-400">
                        {{ formatearMoneda(venta.total) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="py-12 text-center px-4 rounded-xl border-2 border-dashed border-slate-700">
                <p class="text-slate-500">Este cliente no tiene ventas pendientes de facturar.</p>
              </div>
            </div>

            <!-- Step 2.5: Servicios Adicionales (Taller/Otros) -->
             <div class="bg-slate-800 shadow-xl border border-slate-700/50 sm:rounded-2xl p-6 relative overflow-hidden" v-if="form.cliente_id">
               <h3 class="text-lg font-medium leading-6 text-white mb-6 flex items-center">
                   <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-500/20 text-brand-400 text-sm font-bold mr-3 border border-brand-500/30">+</span>
                   Servicios Adicionales
               </h3>
               
               <div class="mb-6">
                 <BuscarProducto 
                   :solo-productos="false"
                   label="Buscar Servicio (Taller/General)"
                   placeholder="Escribe el nombre del servicio..."
                   @agregar-producto="agregarServicio"
                 />
               </div>

               <div v-if="form.extra_services.length > 0" class="overflow-hidden rounded-xl border border-slate-700/50">
                 <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                   <thead class="bg-slate-50 dark:bg-slate-800/50">
                     <tr>
                       <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Servicio</th>
                       <th class="px-6 py-3 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">Cantidad</th>
                       <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Precio</th>
                       <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Subtotal</th>
                       <th class="px-6 py-3 text-right text-xs font-medium text-slate-400 uppercase tracking-wider">Acción</th>
                     </tr>
                   </thead>
                   <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                     <tr v-for="(s, index) in form.extra_services" :key="index">
                       <td class="px-6 py-4">
                         <div class="text-sm font-medium text-white">{{ s.nombre }}</div>
                       </td>
                       <td class="px-6 py-4 text-center">
                         <div class="flex items-center justify-center gap-2">
                            <button @click="s.cantidad > 1 ? s.cantidad-- : null" class="w-6 h-6 rounded-xl bg-slate-700 text-white">-</button>
                            <span class="text-sm text-white w-8 text-center">{{ s.cantidad }}</span>
                            <button @click="s.cantidad++" class="w-6 h-6 rounded-xl bg-slate-700 text-white">+</button>
                         </div>
                       </td>
                       <td class="px-6 py-4 text-right text-sm text-slate-300">
                         {{ formatearMoneda(s.precio) }}
                       </td>
                       <td class="px-6 py-4 text-right text-sm font-bold text-emerald-400">
                         {{ formatearMoneda(s.precio * s.cantidad) }}
                       </td>
                       <td class="px-6 py-4 text-right">
                         <button @click="eliminarServicio(index)" class="text-rose-400 hover:text-rose-300">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                         </button>
                       </td>
                     </tr>
                   </tbody>
                 </table>
               </div>
               <div v-else class="py-6 text-center px-4 rounded-xl border border-slate-700/30">
                 <p class="text-slate-500 text-sm italic">No se han agregado servicios adicionales manuales.</p>
               </div>
             </div>
          </div>

          <!-- Columna Derecha: Configuración y Resumen -->
          <div class="md:col-span-1">
            <div class="bg-slate-800 shadow-xl border border-slate-700/50 sm:rounded-2xl p-6 sticky top-6">
              <h3 class="text-lg font-medium leading-6 text-white mb-6 flex items-center">
                  <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-400 text-sm font-bold mr-3 border border-indigo-500/30">3</span>
                  Datos Fiscales
              </h3>
              
              <div class="space-y-5">
                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">RFC del Cliente</label>
                  <input type="text" v-model="form.rfc" maxlength="13" class="block w-full py-2.5 px-3 text-base bg-slate-900 border border-slate-700 text-white uppercase focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl" placeholder="Ej. XAXX010101000">
                  <p v-if="!form.rfc" class="mt-1 text-xs text-rose-400">El RFC es obligatorio para facturar.</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Régimen Fiscal</label>
                  <select v-model="form.regimen_fiscal" class="block w-full pl-3 pr-10 py-2.5 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                    <option value="" disabled>Seleccione un régimen...</option>
                    <option v-for="regimen in catalogos.regimenes" :key="regimen.clave" :value="regimen.clave">
                      {{ regimen.clave }} - {{ regimen.descripcion }}
                    </option>
                  </select>
                  <p v-if="!form.regimen_fiscal" class="mt-1 text-xs text-rose-400">El régimen fiscal es obligatorio.</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">C.P. Domicilio Fiscal</label>
                  <input type="text" v-model="form.codigo_postal" maxlength="5" class="block w-full py-2.5 px-3 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl" placeholder="Ej. 85000">
                  <p v-if="!form.codigo_postal" class="mt-1 text-xs text-rose-400">El C.P. es obligatorio para CFDI 4.0.</p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Uso de CFDI</label>
                  <select v-model="form.uso_cfdi" class="block w-full pl-3 pr-10 py-2.5 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                    <option v-for="uso in catalogos.usosCfdi" :key="uso.clave" :value="uso.clave">
                      {{ uso.clave }} - {{ uso.descripcion }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Forma de Pago</label>
                  <select v-model="form.forma_pago" class="block w-full pl-3 pr-10 py-2.5 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                    <option v-for="fp in catalogos.formasPago" :key="fp.clave" :value="fp.clave">
                      {{ fp.clave }} - {{ fp.descripcion }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Método de Pago</label>
                  <select v-model="form.metodo_pago" class="block w-full pl-3 pr-10 py-2.5 text-base bg-slate-900 border border-slate-700 text-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                    <option v-for="mp in catalogos.metodosPago" :key="mp.clave" :value="mp.clave">
                      {{ mp.clave }} - {{ mp.descripcion }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-400 mb-1">Observaciones</label>
                  <textarea v-model="form.observaciones" rows="3" class="block w-full shadow-sm sm:text-sm bg-slate-900 border border-slate-700 text-white rounded-xl focus:ring-brand-500 focus:border-brand-500" placeholder="Opcional..."></textarea>
                </div>

                <div class="border-t border-slate-700 pt-6 mt-6 bg-slate-900/50 -mx-6 px-6 pb-2">
                  <div class="flex justify-between text-sm text-slate-400 mb-2">
                    <span>Ventas seleccionadas:</span>
                    <span class="font-bold text-white">{{ totales.count }}</span>
                  </div>
                  <div class="flex justify-between text-sm text-slate-400 mb-2">
                    <span>Subtotal:</span>
                    <span>{{ formatearMoneda(totales.subtotal) }}</span>
                  </div>
                  <div class="flex justify-between text-sm text-slate-400 mb-2">
                    <span>IVA:</span>
                    <span>{{ formatearMoneda(totales.iva) }}</span>
                  </div>
                  <div class="flex justify-between text-xl font-bold text-white mt-4 border-t border-slate-700 pt-4">
                    <span>Total:</span>
                    <span class="text-emerald-400">{{ formatearMoneda(totales.total) }}</span>
                  </div>
                </div>

                <div class="pt-4">
                  <button 
                    @click="submit" 
                    :disabled="form.processing || form.ventas_ids.length === 0"
                    class="w-full flex items-center justify-center py-3 px-4 border border-transparent rounded-2xl shadow-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-[1.02]"
                  >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Procesando...' : 'Crear y Emitir Factura' }}
                  </button>
                  <Link :href="route('facturas.index')" class="mt-4 w-full block text-center text-sm text-slate-500 hover:text-white transition-colors">
                    Cancelar
                  </Link>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal para Capturar Claves SAT Faltantes -->
    <DialogModal :show="showModalSat" @close="showModalSat = false" maxWidth="lg">
      <template #content>
        <div class="bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl w-full overflow-hidden">
          <div class="px-6 py-4 bg-slate-900 border-b border-slate-700 flex justify-between items-center">
            <h3 class="font-bold text-lg text-white">Claves SAT Faltantes</h3>
            <button @click="showModalSat = false" class="text-slate-400 hover:text-white transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <div class="p-6 space-y-6 bg-slate-900/40 max-h-[60vh] overflow-y-auto">
            <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl text-amber-200 text-xs">
              ⚠️ Algunos de los productos o servicios seleccionados no tienen configuradas las claves SAT necesarias para la emisión de la factura. Ingrese los datos a continuación para guardarlos en el catálogo y continuar.
            </div>
            
            <div class="space-y-4">
              <div v-for="(item, idx) in itemsParaCompletarSat" :key="idx" 
                   class="p-4 bg-slate-800 rounded-xl border border-slate-700/80 space-y-3">
                <div class="flex justify-between items-start">
                  <h4 class="font-bold text-sm text-white">{{ item.nombre }}</h4>
                  <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider bg-slate-700 text-slate-300">
                    {{ item.tipo }}
                  </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Clave Producto (8 dígitos)</label>
                    <input type="text" v-model="item.sat_clave_prod_serv" maxlength="8" 
                           class="w-full py-2 px-3 bg-slate-950 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:border-brand-500" 
                           placeholder="Ej. 84111506">
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Clave Unidad (3 caracteres)</label>
                    <input type="text" v-model="item.sat_clave_unidad" maxlength="3" 
                           class="w-full py-2 px-3 bg-slate-950 border border-slate-700 rounded-lg text-xs text-white focus:outline-none focus:border-brand-500" 
                           placeholder="Ej. H87">
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="px-6 py-4 bg-slate-900 border-t border-slate-700 flex justify-end gap-3">
            <button @click="showModalSat = false" class="px-4 py-2 font-black text-slate-400 hover:text-white uppercase text-[10px] tracking-wider transition-colors">
              Cancelar
            </button>
            <button @click="guardarClavesSat" :disabled="guardandoSat" 
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold uppercase text-[10px] tracking-wider disabled:opacity-50 flex items-center gap-2 shadow-lg transition-all">
              <span v-if="guardandoSat" class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"></span>
              {{ guardandoSat ? 'Guardando...' : 'Guardar y Continuar' }}
            </button>
          </div>
        </div>
      </template>
    </DialogModal>
  </div>
</template>
