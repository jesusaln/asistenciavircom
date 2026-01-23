<template>
    <div class="bg-white dark:bg-slate-900 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800">
        <h3 v-if="title" class="text-lg font-semibold text-gray-700 mb-4">{{ title }}</h3>
        <div class="relative h-64 w-full">
            <Bar v-if="chartData.labels.length" :data="chartData" :options="chartOptions" />
            <div v-else class="flex items-center justify-center h-full text-gray-400">
                <p>No hay datos disponibles</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    title: String,
    labels: {
        type: Array,
        default: () => []
    },
    datasets: {
        type: Array,
        default: () => []
    }
});

const chartData = computed(() => ({
    labels: props.labels,
    datasets: props.datasets
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        }
    }
};
</script>
