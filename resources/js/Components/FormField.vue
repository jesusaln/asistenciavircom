<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>
        
        <textarea
            v-if="type === 'textarea'"
            v-bind="$attrs"
            :id="id"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full px-4 py-2.5 border rounded-xl text-sm transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 dark:focus:border-amber-400"
            :class="[
                error ? 'border-rose-300 dark:border-rose-600' : 'border-slate-300 dark:border-slate-600'
            ]"
        ></textarea>

        <select
            v-else-if="type === 'select'"
            v-bind="$attrs"
            :id="id"
            :value="modelValue"
            @change="$emit('update:modelValue', $event.target.value)"
            class="w-full px-4 py-2.5 border rounded-xl text-sm transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 dark:focus:border-amber-400"
            :class="[
                error ? 'border-rose-300 dark:border-rose-600' : 'border-slate-300 dark:border-slate-600'
            ]"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.text || option.label }}
            </option>
        </select>

        <input
            v-else
            v-bind="$attrs"
            :id="id"
            :type="type"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            class="w-full px-4 py-2.5 border rounded-xl text-sm transition-all duration-200 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 dark:focus:border-amber-400"
            :class="[
                error ? 'border-rose-300 dark:border-rose-600' : 'border-slate-300 dark:border-slate-600'
            ]"
        />

        <p v-if="error" class="mt-1 text-xs text-rose-500 font-bold px-1">{{ error }}</p>
        <p v-else-if="help" class="mt-1 text-xs text-slate-400 font-bold px-1">{{ help }}</p>
    </div>
</template>

<script setup>
defineProps({
    id: String,
    label: String,
    modelValue: [String, Number],
    type: {
        type: String,
        default: 'text'
    },
    options: {
        type: Array,
        default: () => []
    },
    error: String,
    help: String,
    required: Boolean,
})
defineEmits(['update:modelValue'])
</script>
