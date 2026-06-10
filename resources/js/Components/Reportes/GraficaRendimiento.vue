<template>
    <div class="bg-[var(--ui-surface)] text-[var(--ui-text)] p-4 rounded-2xl shadow-sm border border-[var(--ui-border)]">
        <h3 v-if="title" class="text-lg font-semibold text-[var(--ui-text-muted)] mb-4">{{ title }}</h3>
        <div class="relative h-64 w-full">
            <Bar v-if="chartData.labels.length" :data="chartData" :options="chartOptions" />
            <div v-else class="flex items-center justify-center h-full text-[var(--ui-text-soft)]">
                <p>No hay datos disponibles</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent } from 'vue';

const Bar = defineAsyncComponent(() => import('@/Components/Reportes/AsyncBarChart.vue'));

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