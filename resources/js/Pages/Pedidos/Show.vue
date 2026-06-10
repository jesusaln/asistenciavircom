<template>
    <Head title="Mostrar Pedido" />
    <div class="pedidos-show min-h-screen bg-[var(--ui-surface)] p-6">
      <h1 class="text-2xl font-bold mb-6" :style="{ color: colors.principal }">Detalles del Pedido</h1>

      <div v-if="pedido" class="bg-white rounded-2xl shadow-xl p-6">
        <div class="mb-4">
          <h2 class="text-lg font-medium text-slate-700">Cliente</h2>
          <p>{{ pedido.cliente.nombre_razon_social }}</p>
        </div>
        <div class="mb-4">
          <h2 class="text-lg font-medium text-slate-700">Productos</h2>
          <ul>
            <li v-for="producto in pedido.productos" :key="producto.id" class="mb-2">
              <strong>{{ producto.nombre }}</strong> - ${{ producto.pivot.precio }} (Cantidad: {{ producto.pivot.cantidad }})
            </li>
          </ul>
        </div>
          <div class="space-y-2 border-t pt-4 mt-4">
            <div class="flex justify-between">
              <span class="text-slate-500">Subtotal:</span>
              <span class="font-medium">${{ Number(pedido.subtotal).toFixed(2) }}</span>
            </div>
            <div v-if="parseFloat(pedido.descuento_general) > 0" class="flex justify-between text-emerald-600">
              <span>Descuento:</span>
              <span>-${{ Number(pedido.descuento_general).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-slate-500">
              <span>IVA:</span>
              <span>${{ Number(pedido.iva).toFixed(2) }}</span>
            </div>
            <div v-if="parseFloat(pedido.retencion_iva) > 0" class="flex justify-between text-orange-600">
              <span>Retención IVA:</span>
              <span>-${{ Number(pedido.retencion_iva).toFixed(2) }}</span>
            </div>
            <div v-if="parseFloat(pedido.retencion_isr) > 0" class="flex justify-between text-orange-600">
              <span>Retención ISR:</span>
              <span>-${{ Number(pedido.retencion_isr).toFixed(2) }}</span>
            </div>
             <div v-if="parseFloat(pedido.isr) > 0" class="flex justify-between text-slate-500">
              <span>ISR:</span>
              <span>-${{ Number(pedido.isr).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between text-xl font-bold border-t pt-2 mt-2">
              <span>Total:</span>
              <span>${{ Number(pedido.total).toFixed(2) }}</span>
            </div>
          </div>
        <div class="mb-4">
          <h2 class="text-lg font-medium text-slate-700">Estado</h2>
          <p>{{ pedido.estado }}</p>
        </div>
      </div>
      <div v-else>
        <p>Cargando detalles del pedido...</p>
      </div>

      <div v-if="pedido" class="mt-6 flex space-x-4">
        <Link :href="route('pedidos.pdf', pedido.id)" target="_blank" class="bg-purple-500 text-white px-4 py-2 rounded-xl hover:bg-purple-600">
          📄 PDF
        </Link>
        <Link :href="route('pedidos.ticket', pedido.id)" target="_blank" class="bg-brand-500 text-white px-4 py-2 rounded-xl hover:bg-orange-600">
          🖨️ Ticket
        </Link>
        <Link :href="route('pedidos.edit', pedido.id)" class="text-white px-4 py-2 rounded-xl hover:brightness-110" :style="{ backgroundColor: colors.principal }">
          Editar
        </Link>
        <button @click="eliminarPedido(pedido.id)" class="bg-brand-500 text-white px-4 py-2 rounded-xl hover:bg-rose-600">
          Eliminar
        </button>
        <button @click="$emit('convertir-a-venta', pedido)" class="bg-brand-500 text-white px-4 py-2 rounded-xl hover:bg-emerald-600">
          Enviar a Ventas
        </button>
      </div>

      <div v-if="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2" :style="{ borderColor: colors.principal }"></div>
      </div>
    </div>
  </template>

  <script setup>
  import { Head, Link, router } from '@inertiajs/vue3';
  import Swal from '@/Utils/Swal';
  import { ref } from 'vue';
  import { Notyf } from 'notyf';
  import 'notyf/notyf.min.css';
  import { useCompanyColors } from '@/Composables/useCompanyColors';

  // Colores de empresa
  const { colors } = useCompanyColors();

  defineProps({ pedido: Object });
  defineEmits(['convertir-a-venta']); // Declarar el evento

  const loading = ref(false);
  const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'top' } });

  const eliminarPedido = async (id) => {
    const result = await Swal.fire({
      title: 'Eliminar pedido',
      text: '¿Estás seguro de que deseas eliminar este pedido?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#ef4444',
    });

    if (!result.isConfirmed) return;

    loading.value = true;
    try {
      await router.delete(`/pedidos/${id}`, {
        onSuccess: () => {
          notyf.success('Pedido eliminado exitosamente.');
          router.visit(route('pedidos.index'));
        },
        onError: () => notyf.error('Error al eliminar el pedido.')
      });
    } catch (error) {
      notyf.error('Ocurrió un error inesperado.');
      console.error('Error al eliminar el pedido:', error);
    } finally {
      loading.value = false;
    }
  };
  </script>

