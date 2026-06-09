<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Gráfica de Rendimiento de Técnicos -->
            <GraficaRendimiento
                title="Rendimiento de Técnicos (Tickets Completados)"
                :labels="graficaTecnicos.labels"
                :datasets="graficaTecnicos.datasets"
                class="hover:shadow-md transition-shadow duration-300"
            />

            <!-- Gráfica de Ventas por Almacén -->
            <GraficaRendimiento
                title="Ventas por Almacén (Mes Actual)"
                :labels="graficaVentas.labels"
                :datasets="graficaVentas.datasets"
                 class="hover:shadow-md transition-shadow duration-300"
            />
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <font-awesome-icon icon="info-circle" class="h-5 w-5 text-blue-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Estas gráficas se actualizan periódicamente. Para ver datos más granulares, consulte las pestañas específicas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import GraficaRendimiento from '@/Components/Reportes/GraficaRendimiento.vue';

const graficaTecnicos = ref({ labels: [], datasets: [] });
const graficaVentas = ref({ labels: [], datasets: [] });

const fetchTecnicosData = async () => {
    try {
        const response = await axios.get(route('reportes.api.rendimiento-tecnicos'));
        graficaTecnicos.value = response.data;
    } catch (error) {
        console.error('Error cargando rendimiento técnicos:', error);
    }
};

const fetchVentasData = async () => {
    try {
        const response = await axios.get(route('reportes.api.ventas-almacen'));
        graficaVentas.value = response.data;
    } catch (error) {
        console.error('Error cargando ventas por almacén:', error);
    }
};

onMounted(() => {
    fetchTecnicosData();
    fetchVentasData();
});
</script>
