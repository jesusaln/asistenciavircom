<template>
    <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Detalles del Técnico</h2>

        <!-- Detalles del técnico -->
        <div class="space-y-2">
          <p><strong>Nombre:</strong> {{ tecnico.nombre }}</p>
          <p><strong>Apellido:</strong> {{ tecnico.apellido }}</p>
          <p><strong>Email:</strong> {{ tecnico.email }}</p>
          <p><strong>Teléfono:</strong> {{ tecnico.telefono }}</p>
          <p><strong>Dirección:</strong> {{ tecnico.direccion }}</p>
        </div>

        <!-- Botón para cerrar el modal -->
        <button @click="closeModal" class="mt-4 bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
          Cerrar
        </button>
      </div>
    </div>
  </template>

  <script setup>
  // Props para recibir el técnico y el estado del modal
  const props = defineProps({
    tecnico: Object, // Recibe el técnico
    isOpen: Boolean  // Estado para controlar la visibilidad del modal
  });

  // Emite un evento para cerrar el modal
  const emit = defineEmits(['close']);

  const closeModal = () => {
    emit('close');
  };
  </script>

  <style scoped>
  /* Estilos adicionales si son necesarios */
  </style>

