<template>
    <svg :width="width" :height="height" viewBox="0 0 100 30" preserveAspectRatio="none">
        <polyline
            v-if="points.length"
            :points="points"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="text-blue-600"
        />
        <line x1="0" :y1="baseline" x2="100" :y2="baseline" stroke="#e5e7eb" stroke-width="1" />
    </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    width: {
        type: Number,
        default: 100,
    },
    height: {
        type: Number,
        default: 30,
    },
});

const normalized = computed(() => {
    if (!props.data || !props.data.length) return [];
    const vals = props.data.map((d) => Number(d.neto || 0));
    const min = Math.min(...vals, 0);
    const max = Math.max(...vals, 0);
    const range = max - min || 1;
    return { vals, min, max, range, norm: vals.map((v) => (v - min) / range) };
});

const points = computed(() => {
    if (!normalized.value.norm || !normalized.value.norm.length) return '';
    const step = 100 / (normalized.value.norm.length - 1 || 1);
    return normalized.value.norm
        .map((v, idx) => `${(idx * step).toFixed(2)},${(30 - v * 30).toFixed(2)}`)
        .join(' ');
});

const baseline = computed(() => {
    if (!normalized.value.norm) return 30;
    const min = normalized.value.min;
    const range = normalized.value.range || 1;
    // posición de y para valor 0
    const ratio = (0 - min) / range;
    return 30 - ratio * 30;
});
</script>

