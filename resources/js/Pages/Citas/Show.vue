<template>
    <Head title="Ver Cita" />
    <div class="citas-show max-w-4xl mx-auto p-6 bg-gray-50 rounded-lg shadow-md">
      <h1 class="text-2xl font-semibold mb-6 text-center">Detalles de la Cita #{{ props.cita.id }}</h1>

      <div class="space-y-6">
        <!-- Cliente -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Cliente</label>
          <p class="text-gray-700">{{ clienteNombre }}</p>
        </div>

        <!-- Tipo de Servicio -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Tipo de Servicio</label>
          <p class="text-gray-700">{{ formatearTipoServicio(cita.tipo_servicio) }}</p>
        </div>

        <!-- Fecha y Hora -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Fecha y Hora</label>
          <p class="text-gray-700">{{ formatearFechaHora(cita.fecha_hora) }}</p>
        </div>

        <!-- Tiempo de Servicio -->
        <div class="mb-4" v-if="cita.tiempo_servicio">
          <label class="block text-gray-700 text-sm font-bold mb-2">Tiempo de Servicio</label>
          <p class="text-gray-700 font-semibold text-green-600 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            {{ cita.tiempo_servicio_formateado }}
          </p>
        </div>

        <!-- Descripción -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Descripción</label>
          <p class="text-gray-700">{{ cita.descripcion || 'Sin descripción' }}</p>
        </div>

        <!-- Tipo de Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Tipo de Equipo</label>
          <p class="text-gray-700">{{ formatearTipoEquipo(cita.tipo_equipo) }}</p>
        </div>

        <!-- Marca del Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Marca del Equipo</label>
          <p class="text-gray-700">{{ cita.marca_equipo || 'Sin marca' }}</p>
        </div>

        <!-- Modelo del Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Modelo del Equipo</label>
          <p class="text-gray-700">{{ cita.modelo_equipo || 'Sin modelo' }}</p>
        </div>

        <!-- Problema Reportado -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Problema Reportado</label>
          <p class="text-gray-700">{{ cita.problema_reportado || 'Sin problema reportado' }}</p>
        </div>

        <!-- Estado -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Estado</label>
          <p class="text-gray-700">{{ formatearEstado(cita.estado) }}</p>
        </div>

        <!-- Técnico -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Técnico</label>
          <p class="text-gray-700">{{ tecnicoNombre }}</p>
        </div>

        <!-- Evidencias -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Evidencias</label>
          <p class="text-gray-700">{{ cita.evidencias || 'No hay evidencias disponibles' }}</p>
        </div>

        <!-- Foto del Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Foto del Equipo</label>
          <img v-if="cita.foto_equipo" :src="cita.foto_equipo" alt="Foto del Equipo" class="max-w-full h-auto rounded-md shadow-sm">
          <p v-else class="text-gray-500 italic">No hay foto del equipo disponible</p>
        </div>

        <!-- Foto de la Hoja de Servicio -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Foto de la Hoja de Servicio</label>
          <img v-if="cita.foto_hoja_servicio" :src="cita.foto_hoja_servicio" alt="Foto de la Hoja de Servicio" class="max-w-full h-auto rounded-md shadow-sm">
          <p v-else class="text-gray-500 italic">No hay foto de la hoja de servicio disponible</p>
        </div>

        <!-- Foto de Identificación del Cliente -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Foto de Identificación del Cliente</label>
          <img v-if="cita.foto_identificacion" :src="cita.foto_identificacion" alt="Foto de Identificación del Cliente" class="max-w-full h-auto rounded-md shadow-sm">
          <p v-else class="text-gray-500 italic">No hay foto de identificación disponible</p>
        </div>

        <!-- Firmas Digitales -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-if="cita.firma_cliente" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <label class="block text-gray-500 text-[10px] font-bold uppercase mb-3">Firma de Conformidad (Cliente)</label>
                <div class="bg-gray-50 rounded-lg p-2 border border-gray-50">
                    <img :src="cita.firma_cliente" class="h-24 object-contain mx-auto">
                </div>
                <div class="mt-3 text-center">
                    <p class="text-sm font-bold text-gray-800">{{ cita.nombre_firmante || 'Cliente' }}</p>
                    <p class="text-[10px] text-gray-500 uppercase">{{ cita.fecha_firma ? formatearFechaHora(cita.fecha_firma) : 'Fecha no registrada' }}</p>
                </div>
            </div>

            <div v-if="cita.firma_tecnico" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
                <label class="block text-gray-500 text-[10px] font-bold uppercase mb-3">Firma del Técnico</label>
                <div class="bg-gray-50 rounded-lg p-2 border border-gray-50">
                    <img :src="cita.firma_tecnico" class="h-24 object-contain mx-auto">
                </div>
                <div class="mt-3 text-center">
                    <p class="text-sm font-bold text-gray-800">{{ props.cita.tecnico?.name || 'Técnico' }}</p>
                    <p class="text-[10px] text-gray-500 uppercase">Responsable del Servicio</p>
                </div>
            </div>
        </div>

        <!-- Items de la Cita -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Productos y Servicios</label>
          <div v-if="cita.items && cita.items.length > 0" class="bg-white p-4 rounded-md shadow-sm">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Descuento (%)</th>
                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notas</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in cita.items" :key="`${item.citable_type}-${item.citable_id}`">
                    <td class="px-3 py-2 text-sm text-gray-900">{{ getItemNombre(item) }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ item.cantidad }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-right">${{ formatearPrecio(item.precio) }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ item.descuento }}%</td>
                    <td class="px-3 py-2 text-sm text-gray-900 text-right">${{ formatearPrecio(item.subtotal) }}</td>
                    <td class="px-3 py-2 text-sm text-gray-900">{{ item.notas || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <p v-else class="text-gray-500 italic">No hay productos o servicios en esta cita</p>
        </div>

        <!-- Totales -->
        <div v-if="cita.total" class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Totales</label>
          <div class="bg-white p-4 rounded-md shadow-sm">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div class="flex justify-between">
                <span>Subtotal:</span>
                <span>${{ formatearPrecio(cita.subtotal) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Descuento Items:</span>
                <span>-${{ formatearPrecio(cita.descuento_items) }}</span>
              </div>
              <div class="flex justify-between">
                <span>Descuento General:</span>
                <span>-${{ formatearPrecio(cita.descuento_general) }}</span>
              </div>
              <div class="flex justify-between">
                <span>IVA:</span>
                <span>${{ formatearPrecio(cita.iva) }}</span>
              </div>
              <div class="flex justify-between font-bold text-lg">
                <span>Total:</span>
                <span>${{ formatearPrecio(cita.total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div v-if="cita.notas" class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Notas</label>
          <p class="text-gray-700 bg-white p-4 rounded-md shadow-sm">{{ cita.notas }}</p>
        </div>

        <!-- Botones de Cambio de Estado -->
        <div class="mb-6 bg-white p-4 rounded-md shadow-sm">
          <label class="block text-gray-700 text-sm font-bold mb-3">Acciones de Estado</label>
          <div class="flex flex-wrap gap-3">
            <button
              v-if="cita.estado === 'pendiente'"
              @click="cambiarEstado('en_proceso')"
              class="px-4 py-2 bg-amber-500 text-white rounded-md hover:bg-amber-600 transition-colors flex items-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Iniciar Cita
            </button>
            
            <button
              v-if="cita.estado === 'en_proceso'"
              @click="cambiarEstado('completado')"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Completar Cita
            </button>

            <button
              v-if="['pendiente', 'en_proceso', 'programado'].includes(cita.estado)"
              @click="cambiarEstado('cancelado')"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Cancelar Cita
            </button>

            <Link
              :href="route('citas.edit', cita.id)"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Editar
            </Link>
          </div>
        </div>

        <!-- Información de Venta -->
        <div v-if="cita.venta" class="mb-4 bg-green-50 p-4 rounded-xl border border-green-200">
          <label class="block text-green-900 text-sm font-bold mb-2 flex items-center gap-2">
            <span>✅</span> Venta Asociada
          </label>
          <div class="flex justify-between items-center">
            <p class="text-green-700">Esta cita tiene una venta generada: <strong>{{ cita.venta.numero_venta }}</strong></p>
            <Link :href="route('ventas.show', cita.venta.id)" class="px-3 py-1 bg-green-600 text-white text-xs rounded-full hover:bg-green-700 font-bold">
              Ver Venta
            </Link>
          </div>
        </div>



        <!-- Botones de Acción -->
        <div class="mt-6 flex justify-end space-x-4">
          <Link
            v-if="cita.items && cita.items.length > 0 && (cita.estado === 'completado' || cita.estado === 'en_proceso')"
            :href="route('citas.convertir-a-pedido', cita.id)"
            method="post"
            as="button"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Convertir a Pedido
          </Link>
          <Link
            v-if="cita.items && cita.items.length > 0 && cita.estado === 'completado' && !cita.venta"
            :href="route('ventas.create', { cita_id: cita.id })"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center gap-2"
          >
            <span>💰</span> Generar Cobro (Venta)
          </Link>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { Head, Link } from '@inertiajs/vue3';
  import { computed, onMounted } from 'vue';
  import axios from 'axios';
  import AppLayout from '@/Layouts/AppLayout.vue';

  defineOptions({ layout: AppLayout });

  const props = defineProps({
    cita: Object,
    tecnicos: Array,
    clientes: Array,
  });

  const clienteNombre = computed(() => {
    const cliente = props.clientes.find(c => c.id === props.cita.cliente_id);
    return cliente ? cliente.nombre_razon_social : 'Desconocido';
  });

  const tecnicoNombre = computed(() => {
    const tecnico = props.tecnicos.find(t => t.id === props.cita.tecnico_id);
    return tecnico ? tecnico.nombre : 'Desconocido';
  });

  const formatearTipoServicio = (tipo) => {
    const tipos = {
      instalacion: 'Instalación',
      diagnostico: 'Diagnóstico',
      reparacion: 'Reparación',
      garantia: 'Garantía',
      otro_servicio: 'Otro Servicio',
    };
    return tipos[tipo] || 'Desconocido';
  };

  const formatearTipoEquipo = (tipo) => {
    const tipos = {
      minisplit: 'Minisplit',
      boiler: 'Boiler',
      refrigerador: 'Refrigerador',
      lavadora: 'Lavadora',
      secadora: 'Secadora',
      estufa: 'Estufa',
      campana: 'Campana',
      horno_de_microondas: 'Horno de Microondas',
      licuadora: 'Licuadora',
      otro_equipo: 'Otro Equipo',
    };
    return tipos[tipo] || 'Desconocido';
  };

  const formatearEstado = (estado) => {
    const estados = {
      pendiente: 'Pendiente',
      en_proceso: 'En Proceso',
      completado: 'Completado',
      cancelado: 'Cancelado',
    };
    return estados[estado] || 'Desconocido';
  };

  const formatearFechaHora = (fechaHora) => {
    const fecha = new Date(fechaHora);
    return fecha.toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' });
  };

  const formatearPrecio = (precio) => {
    return parseFloat(precio || 0).toFixed(2);
  };

  const formatearTipoUso = (tipoUso) => {
    const tipos = {
      'repuesto': 'Repuesto',
      'consumible': 'Consumible',
      'herramienta': 'Herramienta',
      'otro': 'Otro'
    };
    return tipos[tipoUso] || tipoUso || 'No especificado';
  };

  const getProductoNombre = (producto) => {
    return producto.nombre || `Producto #${producto.id}`;
  };

  const getItemNombre = (item) => {
    return item.citable?.nombre || `Item #${item.citable_id}`;
  };

  onMounted(() => {
    console.log('Datos de la cita:', props.cita);
    console.log('Foto del equipo:', props.cita.foto_equipo);
    console.log('Foto de la hoja de servicio:', props.cita.foto_hoja_servicio);
    console.log('Foto de identificación:', props.cita.foto_identificacion);
  });

  const cambiarEstado = async (nuevoEstado) => {
    const result = await Swal.fire({
      title: 'Confirmar cambio de estado',
      text: `¿Estás seguro de cambiar el estado a "${formatearEstado(nuevoEstado)}"?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sí, cambiar',
      cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) {
      return;
    }

    try {
      // Usamos la ruta explícita para evitar conflictos de método (405)
      const response = await axios.post(`/citas/${props.cita.id}/cambiar-estado`, {
        estado: nuevoEstado,
      });

      if (response.data.success) {
        // Recargar la página para mostrar el nuevo estado
        window.location.reload();
      }
    } catch (error) {
      console.error('Error al cambiar estado:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al cambiar el estado de la cita'
      });
    }
  };
  </script>

  <style scoped>
@import "tailwindcss" reference;
  /* Estilos personalizados */
  .citas-show {
    margin-top: 1rem;
  }

  img {
    max-width: 300px; /* Limita el ancho máximo de las imágenes */
  }
  </style>

