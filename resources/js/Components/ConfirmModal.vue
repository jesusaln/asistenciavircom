<template>
    <div v-if="isOpen" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h2 class="text-xl font-semibold mb-4">{{ title }}</h2>
            <p class="text-gray-700 mb-4">{{ message }} <strong>{{ itemName }}</strong></p>
            <div class="flex justify-end space-x-4">
                <button @click="$emit('cancel')" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 transition">
                    Cancelar
                </button>
                <button @click="$emit('confirm')" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                    Eliminar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    isOpen: Boolean,
    title: String,
    message: String,
    itemName: String,
});

defineEmits(['cancel', 'confirm']);
</script>

