<script setup>
import Modal from './Modal.vue';

const props = defineProps({
  tecnico: Object,
  isOpen: Boolean
});

const emit = defineEmits(['close']);

const closeModal = () => {
  emit('close');
};
</script>

<template>
  <Modal :show="isOpen" max-width="md" @close="closeModal">
    <div class="px-6 pt-6 pb-4 bg-[var(--ui-surface)] text-[var(--ui-text)]">
      <h2 class="text-xl font-semibold mb-4">Detalles del Técnico</h2>

      <div class="space-y-2 text-sm">
        <p><strong>Nombre:</strong> {{ tecnico.nombre }}</p>
        <p><strong>Apellido:</strong> {{ tecnico.apellido }}</p>
        <p><strong>Email:</strong> {{ tecnico.email }}</p>
        <p><strong>Teléfono:</strong> {{ tecnico.telefono }}</p>
        <p><strong>Dirección:</strong> {{ tecnico.direccion }}</p>
      </div>
    </div>

    <div class="flex justify-end px-6 py-4 bg-[var(--ui-surface-alt)] border-t border-[var(--ui-border)]">
      <button @click="closeModal" class="px-4 py-2 rounded-xl bg-rose-500 text-white hover:bg-rose-600">
        Cerrar
      </button>
    </div>
  </Modal>
</template>
