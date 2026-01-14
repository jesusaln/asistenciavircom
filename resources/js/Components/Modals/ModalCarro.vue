<template>
    <div v-if="mostrarModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <!-- Cerrar Modal -->
            <button @click="$emit('cerrar')" class="float-right text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Título -->
            <h3 class="text-xl font-semibold mb-4">Detalles del Carro</h3>

            <!-- Contenido -->
            <div v-if="carro">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Foto</label>
                    <img v-if="carro.foto" :src="carro.foto" alt="Foto del carro" class="w-full h-auto rounded">
                    <span v-else class="text-gray-500 italic">Sin foto</span>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Marca</label>
                    <p>{{ carro.marca }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Modelo</label>
                    <p>{{ carro.modelo }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Año</label>
                    <p>{{ carro.anio }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Color</label>
                    <p>{{ carro.color }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Precio</label>
                    <p>{{ carro.precio }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Número de Serie</label>
                    <p>{{ carro.numero_serie }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Combustible</label>
                    <p>{{ carro.combustible }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Kilometraje</label>
                    <p>{{ carro.kilometraje }}</p>
                </div>
                <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Placa</label>
                    <p>{{ carro.placa || 'No disponible' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    mostrarModal: Boolean,
    carro: Object,
});

defineEmits(['cerrar']);
</script>

