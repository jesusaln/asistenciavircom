<template>
    <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm rounded-xl border border-gray-100 dark:border-slate-800 hover:shadow-md transition-shadow duration-300">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div :class="`rounded-md p-3 ${colorClass}`">
                        <i :class="`${icon} text-white text-lg`"></i>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{{ title }}</dt>
                        <dd>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ value }}</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-5 py-3 border-t border-gray-100 dark:border-slate-800">
            <div class="text-sm">
                <span class="font-medium text-blue-600 hover:text-blue-500 transition-colors cursor-pointer" @click="$emit('click')">
                    Ver detalles <span aria-hidden="true">&rarr;</span>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: {
        type: String,
        default: 'blue'
    }
});

defineEmits(['click']);

const colorClass = computed(() => {
    const colors = {
        blue: 'bg-blue-500',
        green: 'bg-green-500',
        red: 'bg-red-500',
        yellow: 'bg-yellow-500',
        purple: 'bg-purple-500',
        indigo: 'bg-amber-500',
        teal: 'bg-teal-500',
        gray: 'bg-gray-500',
        orange: 'bg-orange-500'
    };
    return colors[props.color] || colors.blue;
});
</script>

