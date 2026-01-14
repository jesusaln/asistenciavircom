<template>
    <Head title="Mostrar productos" />
    <div>
        <h1 class="text-2xl font-semibold mb-4">Detalles del Producto</h1>

        <!-- Aquí ya no mostramos los detalles directamente -->
        <ProductModal
            :producto="producto"
            :isOpen="isModalOpen"
            @close="closeModal"
        />

        <!-- Botón para abrir el modal -->
        <button @click="openModal" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
            Ver Detalles
        </button>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import ProductModal from '@/Components/ProductoModal.vue'; // Importar el componente del modal

// Recibe el producto como prop
const props = defineProps({
    producto: Object, // Recibe el producto
});

// Estado para controlar la visibilidad del modal
const isModalOpen = ref(false);

// Funciones para abrir y cerrar el modal
const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};
</script>

<style scoped>
@import "tailwindcss" reference;
/* Estilos adicionales si son necesarios */
</style>

