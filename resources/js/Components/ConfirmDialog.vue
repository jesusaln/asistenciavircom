<template>
    <div v-if="visible" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-lg shadow-md max-w-sm w-full">
        <h3 class="text-lg font-semibold mb-4">{{ title }}</h3>
        <p class="mb-4 text-gray-700">{{ message }}</p>
        <div class="flex justify-end space-x-4">
          <!-- Botón de Cancelar -->
          <button @click="cancel" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition duration-300">
            Cancelar
          </button>
          <!-- Botón de Confirmar -->
          <button @click="confirm" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition duration-300">
            Confirmar
          </button>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref } from 'vue';

  // Estados reactivos para controlar la visibilidad del modal y el contenido
  const visible = ref(false);
  const title = ref(''); // Título del cuadro de confirmación
  const message = ref(''); // Mensaje del cuadro de confirmación
  const resolvePromise = ref(null); // Para almacenar la promesa que se resuelve con la respuesta

  // Método para mostrar el cuadro de confirmación
  const show = (newTitle, newMessage) => {
    title.value = newTitle; // Establece el título
    message.value = newMessage; // Establece el mensaje
    visible.value = true; // Muestra el cuadro de confirmación
    return new Promise((resolve) => {
      resolvePromise.value = resolve; // Almacena la función resolver de la promesa
    });
  };

  // Método para confirmar la acción
  const confirm = () => {
    visible.value = false; // Oculta el cuadro de confirmación
    if (resolvePromise.value) {
      resolvePromise.value(true); // Resuelve la promesa con `true`
      resolvePromise.value = null; // Reinicia la función resolver para evitar problemas futuros
    }
  };

  // Método para cancelar la acción
  const cancel = () => {
    visible.value = false; // Oculta el cuadro de confirmación
    if (resolvePromise.value) {
      resolvePromise.value(false); // Resuelve la promesa con `false`
      resolvePromise.value = null; // Reinicia la función resolver
    }
  };

  // Expón el método `show` para que otros componentes puedan usarlo
  defineExpose({ show });
  </script>

  <style scoped>
  /* Estilos adicionales si son necesarios */
  </style>

