<script setup>
import Modal from './Modal.vue';

defineProps({
    isOpen: Boolean,
    title: String,
    message: String,
    itemName: String,
});

defineEmits(['cancel', 'confirm']);
</script>

<template>
    <Modal :show="isOpen" max-width="sm" @close="$emit('cancel')">
        <div class="px-6 pt-6 pb-4 bg-[var(--ui-surface)] text-[var(--ui-text)]">
            <h2 class="text-lg font-semibold">{{ title }}</h2>
            <p class="mt-3 text-sm text-[var(--ui-text-muted)]">
                {{ message }} <strong class="text-[var(--ui-text)]">{{ itemName }}</strong>
            </p>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-[var(--ui-surface-alt)] border-t border-[var(--ui-border)]">
            <button @click="$emit('cancel')" class="px-4 py-2 rounded-xl bg-slate-400/90 text-white hover:bg-slate-500 transition">
                Cancelar
            </button>
            <button @click="$emit('confirm')" class="px-4 py-2 rounded-xl bg-rose-500 text-white hover:bg-rose-600 transition">
                Eliminar
            </button>
        </div>
    </Modal>
</template>
