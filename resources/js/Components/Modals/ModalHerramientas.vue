<template>
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex justify-center items-center">
      <div class="bg-white dark:bg-slate-900 rounded-lg w-1/3">
        <div class="flex justify-between items-center p-4 border-b">
          <h2 class="text-xl font-semibold">Detalles de la Herramienta</h2>
          <button @click="closeModal" class="text-gray-600 hover:text-gray-800">
            &times;
          </button>
        </div>
        <div class="p-4">
          <p><strong>Nombre:</strong> {{ herramienta.nombre }}</p>
          <p><strong>Número de Serie:</strong> {{ herramienta.numero_serie }}</p>
          <p v-if="herramienta.foto"><strong>Foto:</strong></p>
          <img v-if="herramienta.foto" :src="herramienta.foto" alt="Foto de la herramienta" class="w-full h-auto">
          <p><strong>Técnico Asignado:</strong> {{ herramienta.tecnico ? (herramienta.tecnico.name || `${herramienta.tecnico.nombre || ''} ${herramienta.tecnico.apellido || ''}`.trim()) : 'Sin asignar' }}</p>
        </div>
      </div>
    </div>
  </template>

  <script setup>


  const props = defineProps({
    herramienta: Object,
  });

  const emit = defineEmits(['close']);

  const closeModal = () => {
    emit('close');
  };
  </script>

  <style scoped>
  /* Estilos adicionales si son necesarios */
  </style>

