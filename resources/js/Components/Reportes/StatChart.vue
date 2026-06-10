<template>
    <div class="relative w-full h-64">
        <canvas ref="chartCanvas"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    type: { type: String, default: 'line' },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const chartCanvas = ref(null);
let chartInstance = null;

const createChart = async () => {
    if (chartInstance) {
        chartInstance.destroy();
    }

    if (!chartCanvas.value) return;

    const { default: Chart } = await import('chart.js/auto');
    const ctx = chartCanvas.value.getContext('2d');
    chartInstance = new Chart(ctx, {
        type: props.type,
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

onBeforeUnmount(() => {
    if (chartInstance) {
        chartInstance.destroy();
    }
});
</script>

