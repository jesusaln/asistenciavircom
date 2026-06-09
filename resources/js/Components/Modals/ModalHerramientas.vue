<template>
    <div class="fixed inset-0 bg-black/60 dark:bg-black/70 flex justify-center items-center p-4">
      <div class="bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 rounded-2xl w-full max-w-xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b border-slate-200 dark:border-slate-800">
          <h2 class="text-xl font-semibold">Detalles de la Herramienta</h2>
          <button @click="closeModal" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
            &times;
          </button>
        </div>
        <div class="p-4 overflow-y-auto max-h-[calc(90vh-72px)]">
          <p><strong>Nombre:</strong> {{ herramienta.nombre }}</p>
          <p><strong>Número de Serie:</strong> {{ herramienta.numero_serie }}</p>
          <p v-if="herramienta.foto"><strong>Foto:</strong></p>
          <img v-if="herramienta.foto" :src="herramienta.foto" alt="Foto de la herramienta" class="w-full h-auto rounded-lg border border-slate-200 dark:border-slate-800">
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
