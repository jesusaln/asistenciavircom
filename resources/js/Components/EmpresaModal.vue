<template>
    <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-semibold mb-4">Detalles de la Empresa</h2>
        <p><strong>Nombre:</strong> {{ empresa.nombre_razon_social }}</p>
        <p><strong>RFC:</strong> {{ empresa.rfc }}</p>
        <p><strong>Régimen Fiscal:</strong> {{ empresa.regimen_fiscal }}</p>
        <p><strong>Uso CFDI:</strong> {{ empresa.uso_cfdi }}</p>
        <p><strong>Email:</strong> {{ empresa.email }}</p>
        <p><strong>Teléfono:</strong> {{ empresa.telefono }}</p>
        <p><strong>Dirección:</strong> {{ empresa.calle }} {{ empresa.numero_exterior }} {{ empresa.numero_interior }},
          {{ empresa.colonia }}, {{ empresa.codigo_postal }},
          {{ empresa.municipio }}, {{ empresa.estado }}, {{ empresa.pais }}</p>
        <button @click="$emit('close')" class="mt-4 bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 transition">
          Cerrar
        </button>
      </div>
    </div>
  </template>

  <script setup>
  

  const props = defineProps({
    empresa: Object,
    isOpen: Boolean
  });
  </script>

