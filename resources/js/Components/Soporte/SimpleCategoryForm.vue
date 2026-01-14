<template>
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Nueva Categoría Rápida</h2>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                <input v-model="form.nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">SLA (Horas)</label>
                    <input v-model="form.sla_horas" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Color</label>
                    <select v-model="form.color" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="blue">Azul</option>
                        <option value="green">Verde</option>
                        <option value="red">Rojo</option>
                        <option value="yellow">Amarillo</option>
                        <option value="indigo">Índigo</option>
                        <option value="gray">Gris</option>
                    </select>
                </div>
            </div>

            <!-- Valores por defecto ocultos/fijos para simplificar -->
            <!-- Icono por defecto: tag -->
            
            <div class="flex justify-end mt-6">
                <button type="button" @click="$emit('close')" class="mr-3 px-4 py-2 text-gray-700 hover:text-gray-900">Cancelar</button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700" :disabled="processing">
                    {{ processing ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'created']);

const processing = ref(false);
const form = ref({
    nombre: '',
    descripcion: '',
    sla_horas: 24,
    orden: 0,
    icono: 'tag',
    color: 'blue',
    activo: true,
});

const submit = async () => {
    processing.value = true;
    try {
        const response = await axios.post(route('soporte.categorias.store'), form.value);
        if (response.data.success) {
            emit('created', response.data.categoria);
            emit('close');
        }
    } catch (error) {
        console.error('Error creando categoría:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al crear categoría',
            text: 'Verifica los datos e inténtalo de nuevo.'
        });
    } finally {
        processing.value = false;
    }
};
</script>
