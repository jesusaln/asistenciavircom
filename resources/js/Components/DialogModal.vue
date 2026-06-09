<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-6 py-4 bg-[var(--ui-surface)] text-[var(--ui-text)]">
            <div class="text-lg font-medium font-ui">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm text-[var(--ui-text-muted)] font-ui">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end px-6 py-4 text-end bg-[var(--ui-surface-alt)] border-t border-[var(--ui-border)]">
            <slot name="footer" />
        </div>
    </Modal>
</template>
