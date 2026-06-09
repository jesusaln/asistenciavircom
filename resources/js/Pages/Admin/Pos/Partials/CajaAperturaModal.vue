<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    montoApertura: { type: Number, default: 0 },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:montoApertura', 'open']);

const onMontoInput = (event) => {
    const value = Number(event.target.value);
    emit('update:montoApertura', Number.isFinite(value) ? value : 0);
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-purple-500/30 rounded-2xl w-full max-w-sm shadow-2xl p-6">
                <h2 class="text-xl font-bold text-white mb-4">Apertura de Caja</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-slate-400 text-xs font-bold uppercase mb-2">Monto Inicial (Fondo)</label>
                        <input 
                            :value="montoApertura"
                            type="number"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white font-mono text-lg focus:border-purple-500 focus:ring-purple-500/20"
                            placeholder="0.00"
                            autofocus
                            @input="onMontoInput"
                        />
                    </div>
                    <button 
                        @click="emit('open')"
                        :disabled="loading"
                        class="w-full bg-purple-600 hover:bg-purple-500 text-white font-bold py-3 rounded-xl disabled:opacity-50 transition-all shadow-lg shadow-purple-600/20"
                    >
                        {{ loading ? 'Abriendo...' : 'Abrir Caja' }}
                    </button>
                    <Link :href="route('dashboard')" class="block text-center text-slate-500 text-xs hover:text-white mt-4">
                        Cancelar y Salir al Dashboard
                    </Link>
                </div>
            </div>
        </div>
    </Teleport>
</template>
