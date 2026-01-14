<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ClientLayout from './Layout/ClientLayout.vue';

const props = defineProps({
  categorias: Array,
});

const page = usePage();

// Colores corporativos desde la configuración de empresa
const empresaConfig = computed(() => page.props.empresa_config || {});

const cssVars = computed(() => ({
  '--color-primary': empresaConfig.value.color_principal || '#F59E0B',
  '--color-primary-dark': (empresaConfig.value.color_principal || '#F59E0B') + 'dd',
  '--color-primary-soft': (empresaConfig.value.color_principal || '#F59E0B') + '20',
}));

const form = useForm({
  titulo: '',
  descripcion: '',
  categoria_id: '',
  prioridad: 'media',
});

const submit = () => {
  form.post(route('portal.tickets.store'));
};
</script>

<template>
  <ClientLayout>
    <div class="max-w-3xl mx-auto" :style="cssVars">
      <div class="bg-white shadow-lg sm:rounded-2xl border border-gray-100">
        <div class="px-6 py-8 sm:p-10">
          <!-- Header con icono -->
          <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-xl bg-[var(--color-primary-soft)] flex items-center justify-center">
              <svg class="w-6 h-6 text-[var(--color-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Ticket</h3>
              <p class="text-sm text-gray-500">Describe tu problema y nuestro equipo te atenderá lo antes posible.</p>
            </div>
          </div>
          
          <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Título -->
            <div>
              <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Asunto</label>
              <input
                type="text"
                name="titulo"
                id="titulo"
                v-model="form.titulo"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                placeholder="Ej: No puedo acceder al sistema"
              />
              <div v-if="form.errors.titulo" class="text-red-500 text-xs mt-1">{{ form.errors.titulo }}</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Categoría -->
              <div>
                <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoría</label>
                <select
                  id="categoria"
                  name="categoria"
                  v-model="form.categoria_id"
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="" disabled>Selecciona una categoría</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
                <div v-if="form.errors.categoria_id" class="text-red-500 text-xs mt-1">{{ form.errors.categoria_id }}</div>
              </div>

              <!-- Prioridad -->
              <div>
                <label for="prioridad" class="block text-sm font-semibold text-gray-700 mb-2">Prioridad</label>
                <select
                  id="prioridad"
                  name="prioridad"
                  v-model="form.prioridad"
                  class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all"
                >
                  <option value="baja">🟢 Baja (Consultas generales)</option>
                  <option value="media">🟡 Media (Problemas funcionales)</option>
                  <option value="alta">🟠 Alta (Bloqueo de trabajo)</option>
                  <option value="urgente">🔴 Urgente (Sistema caído)</option>
                </select>
                <div v-if="form.errors.prioridad" class="text-red-500 text-xs mt-1">{{ form.errors.prioridad }}</div>
              </div>
            </div>
              
            <!-- Descripción -->
            <div>
              <label for="descripcion" class="block text-sm font-semibold text-gray-700 mb-2">Descripción detallada</label>
              <textarea
                id="descripcion"
                name="descripcion"
                rows="5"
                v-model="form.descripcion"
                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-[var(--color-primary-soft)] focus:border-[var(--color-primary)] transition-all resize-none"
                placeholder="Describe tu problema con el mayor detalle posible..."
              ></textarea>
              <div v-if="form.errors.descripcion" class="text-red-500 text-xs mt-1">{{ form.errors.descripcion }}</div>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 pt-4">
              <Link 
                :href="route('portal.dashboard')" 
                class="px-6 py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm hover:bg-gray-200 transition-all"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-3 bg-[var(--color-primary)] text-white rounded-xl font-semibold text-sm hover:opacity-90 transition-all disabled:opacity-50 flex items-center gap-2"
              >
                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Crear Ticket
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ClientLayout>
</template>
