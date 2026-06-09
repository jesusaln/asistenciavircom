<template>
    <div class="relative w-full">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        
        <div class="relative">
            <input
                type="text"
                v-model="search"
                @input="handleInput"
                @focus="showResults = true"
                @keydown.down.prevent="navigateResults(1)"
                @keydown.up.prevent="navigateResults(-1)"
                @keydown.enter.prevent="selectCurrent"
                :placeholder="placeholder"
                class="input-field pr-10"
                autocomplete="off"
            />
            
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg v-if="loading" class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div v-if="showResults && (results.length > 0 || search.length >= 3)" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-md shadow-lg max-h-60 overflow-y-auto">
            <ul v-if="results.length > 0" class="py-1">
                <li
                    v-for="(result, index) in results"
                    :key="result.clave"
                    @click="selectResult(result)"
                    class="px-4 py-2 hover:bg-blue-50 cursor-pointer text-sm"
                    :class="{ 'bg-blue-100': index === activeIndex }"
                >
                    <div class="font-bold text-blue-700">{{ result.clave }}</div>
                    <div class="text-gray-600 truncate">{{ result.descripcion }}</div>
                </li>
            </ul>
            <div v-else-if="search.length >= 3 && !loading" class="px-4 py-3 text-sm text-gray-500 italic">
                No se encontraron resultados para "{{ search }}"
            </div>
            <div v-else-if="search.length < 3" class="px-4 py-3 text-sm text-gray-400 italic">
                Escriba al menos 3 caracteres para buscar...
            </div>
        </div>

        <div v-if="selectedDescription" class="mt-2 p-2 bg-blue-50 border border-blue-100 rounded text-xs text-blue-800 flex items-start gap-2">
            <svg class="h-4 w-4 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span><strong>Seleccionado:</strong> {{ selectedDescription }}</span>
        </div>
        
        <p v-if="error" class="error-message mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: String,
    label: { type: String, default: 'Clave SAT (Producto/Servicio)' },
    placeholder: { type: String, default: 'Buscar por clave o descripción...' },
    required: Boolean,
    error: String,
    initialDescription: String
});

const emit = defineEmits(['update:modelValue', 'select']);

const normalizeClave = (value) => {
    if (value === null || value === undefined) return '';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    if (typeof value === 'object') {
        if (value.clave) return String(value.clave);
        if (value.value) return String(value.value);
    }
    return '';
};

const normalizeDescripcion = (value) => {
    if (value === null || value === undefined) return '';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    if (typeof value === 'object') {
        if (value.descripcion) return String(value.descripcion);
        if (value.nombre) return String(value.nombre);
    }
    return '';
};

const search = ref(normalizeClave(props.modelValue));
const results = ref([]);
const loading = ref(false);
const showResults = ref(false);
const activeIndex = ref(-1);
const selectedDescription = ref(normalizeDescripcion(props.initialDescription));

let debounceTimer = null;

const handleInput = () => {
    showResults.value = true;
    activeIndex.value = -1;
    
    if (search.value.length < 3) {
        results.value = [];
        return;
    }

    // Si es un código de 8 dígitos, intentar buscar coincidencia exacta inmediatamente
    if (search.value.length === 8 && /^\d+$/.test(search.value)) {
        clearTimeout(debounceTimer);
        performSearch();
        return;
    }

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(performSearch, 400);
};

const performSearch = async () => {
    loading.value = true;
    try {
        const response = await fetch(`${window.location.origin}/sat/buscar-clave-prod-serv?search=${encodeURIComponent(search.value)}`);
        if (!response.ok) throw new Error('Error al buscar');
        results.value = await response.json();
        
        // Si hay un resultado exacto y el search tiene 8 dígitos, actualizar descripción automáticamente
        if (search.value.length === 8 && results.value.length > 0) {
            const exactMatch = results.value.find(r => r.clave === search.value);
            if (exactMatch) {
                selectedDescription.value = exactMatch.descripcion;
                emit('update:modelValue', exactMatch.clave);
            }
        }
    } catch (err) {
        console.error('Error buscando clave SAT:', err);
    } finally {
        loading.value = false;
    }
};

const selectResult = (result) => {
    search.value = result.clave;
    selectedDescription.value = result.descripcion;
    showResults.value = false;
    emit('update:modelValue', result.clave);
    emit('select', result);
};

const navigateResults = (direction) => {
    if (!showResults.value || results.value.length === 0) return;
    
    activeIndex.value = (activeIndex.value + direction + results.value.length) % results.value.length;
};

const selectCurrent = () => {
    if (activeIndex.value >= 0 && results.value[activeIndex.value]) {
        selectResult(results.value[activeIndex.value]);
    }
};

// Cerrar resultados al hacer clic fuera
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative.w-full')) {
        showResults.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

// Watch para cambios externos en modelValue
watch(() => props.modelValue, (newVal) => {
    const normalized = normalizeClave(newVal);
    if (normalized !== search.value) {
        search.value = normalized;
    }
});

watch(() => props.initialDescription, (newVal) => {
    selectedDescription.value = normalizeDescripcion(newVal);
});
</script>

<style scoped>
.input-field {
    width: 100%;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-width: 1px;
    border-style: solid;
    border-color: #D1D5DB;
    border-radius: 0.375rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.error-message {
    font-size: 0.875rem;
    color: #DC2626;
}
</style>
