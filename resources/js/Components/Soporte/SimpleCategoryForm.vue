<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['close', 'created']);

const processing = ref(false);
const form = ref({
    nombre: '',
    descripcion: '',
    sla_horas: 24,
    orden: 0,
    icono: 'tag',
    color: 'blue',
    activo: true,
});

const submit = async () => {
    processing.value = true;
    try {
        const response = await axios.post(route('soporte.categorias.store'), form.value);
        if (response.data.success) {
            emit('created', response.data.categoria);
            emit('close');
        }
    } catch (error) {
        console.error('Error creando categoría:', error);
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="p-8 space-y-8 uppercase">
        <div class="flex items-center gap-5 border-b border-white/5 pb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/20 flex items-center justify-center text-brand-500 text-xl">
                ⚡
            </div>
            <div>
                <h2 class="text-xl font-black text-white tracking-tighter">Categoría Flash</h2>
                <p class="text-[9px] font-bold text-slate-500 tracking-wide italic tracking-[0.2em] mt-1">Sintonización rápida de taxonomía</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-8">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-500 tracking-wide px-1 italic">Nombre de la Categoría</label>
                <input v-model="form.nombre" type="text" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-sm font-black text-white placeholder-slate-800 transition-all focus:border-brand-500/50 shadow-inner" required />
            </div>
            
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 tracking-wide px-1 italic">SLA (Hrs)</label>
                    <input v-model="form.sla_horas" type="number" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black text-white focus:border-brand-500/50 shadow-inner" required />
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-500 tracking-wide px-1 italic">Gama</label>
                    <select v-model="form.color" class="w-full bg-slate-950/60 border border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black text-white appearance-none cursor-pointer focus:border-brand-500/50">
                        <option v-for="c in ['blue','green','red','yellow','indigo','gray']" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-4 pt-4">
                <button type="button" @click="$emit('close')" class="px-6 py-3 text-[10px] font-black text-slate-500 hover:text-white transition-colors tracking-wide">Abortar</button>
                <button 
                    type="submit" 
                    class="px-10 py-4 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black tracking-wide rounded-2xl shadow-xl transition-all disabled:opacity-30 active:scale-95 flex items-center gap-3" 
                    :disabled="processing"
                >
                    <svg v-if="processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Sincronizar
                </button>
            </div>
        </form>
    </div>
</template>
