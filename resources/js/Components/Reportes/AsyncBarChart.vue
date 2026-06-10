<template>
    <div class="relative w-full h-full">
        <canvas ref="chartCanvas"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const chartCanvas = ref(null);
let chartInstance = null;

const createChart = () => {
    if (chartInstance) {
        chartInstance.destroy();
    }

    if (!chartCanvas.value) return;

    const ctx = chartCanvas.value.getContext('2d');
    chartInstance = new ChartJS(ctx, {
        type: 'bar',
        data: props.data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...props.options,
        },
    });
};

onMounted(() => {
    createChart();
});

watch(() => props.data, () => {
    createChart();
}, { deep: true });
</script>