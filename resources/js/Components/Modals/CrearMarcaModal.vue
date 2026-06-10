<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTag, faTimes, faSave } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'marca-creada'])

const form = useForm({
  nombre: '',
  descripcion: '',
  estado: 'activo'
})

const submit = () => {
  form.post(route('marcas.store'), {
    preserveScroll: true,
    onSuccess: (page) => {
      // Intentar obtener la marca recién creada de la respuesta o recargar
      // Si el controlador devuelve la marca, la emitimos. 
      // Pero como marcas.store suele ser un resource controller que redirecciona,
      // a veces es mejor usar axios para esto si queremos respuesta inmediata.
      // Vamos a intentar con axios para que sea una experiencia fluida.
    },
  })
}

const submitWithAxios = async () => {
  try {
    const response = await axios.post('/marcas', form.data())
    emit('marca-creada', response.data.marca || response.data)
    form.reset()
    emit('close')
  } catch (error) {
    if (error.response?.data?.errors) {
      form.setError(error.response.data.errors)
    }
  }
}

const close = () => {
  form.reset()
  form.clearErrors()
  emit('close')
}
</script>

<template>
  <Teleport to="#app">
    <Transition
      enter-active-class="transition-opacity duration-300"
      leave-active-class="transition-opacity duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>

        <!-- Modal Content -->
        <div class="relative bg-[var(--ui-surface)] border border-[var(--ui-border)] w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden animate-fade-in">
          <div class="p-8">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[var(--ui-accent)]/20 flex items-center justify-center border border-[var(--ui-accent)]/30">
                  <FontAwesomeIcon :icon="faTag" class="text-[var(--ui-accent)]" />
                </div>
                <h3 class="text-xl font-black text-[var(--ui-text)] uppercase tracking-wider">Nueva Marca</h3>
              </div>
              <button @click="close" class="text-[var(--ui-text-muted)] hover:text-[var(--ui-text)] transition-colors">
                <FontAwesomeIcon :icon="faTimes" />
              </button>
            </div>

            <form @submit.prevent="submitWithAxios" class="space-y-6">
              <div class="space-y-2">
                <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Nombre de la Marca</label>
                <input 
                  v-model="form.nombre"
                  type="text" 
                  placeholder="Ej. Samsung, Daikin, LG..."
                  class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] placeholder:text-[var(--ui-text-muted)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
                  required
                >
                <p v-if="form.errors.nombre" class="text-rose-500 text-xs font-bold mt-1">{{ form.errors.nombre }}</p>
              </div>

              <div class="space-y-2">
                <label class="text-xs font-black text-[var(--ui-text-muted)] uppercase tracking-wide ml-1">Descripción (Opcional)</label>
                <textarea 
                  v-model="form.descripcion"
                  rows="2" 
                  placeholder="Notas adicionales sobre la marca..."
                  class="w-full bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-2xl py-3.5 px-4 text-[var(--ui-text)] placeholder:text-[var(--ui-text-muted)] focus:ring-2 focus:ring-[var(--ui-accent)]/50 transition-all"
                ></textarea>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <button 
                  type="button" 
                  @click="close"
                  class="px-6 py-3 rounded-xl text-[var(--ui-text-muted)] font-bold hover:text-[var(--ui-text)] transition-all"
                >
                  Cancelar
                </button>
                <button 
                  type="submit" 
                  :disabled="form.processing"
                  class="px-8 py-3 bg-[var(--ui-accent)] hover:brightness-110 text-[var(--ui-accent-contrast)] font-black uppercase tracking-wide rounded-xl shadow-lg shadow-[var(--ui-accent)]/20 transition-all active:scale-95 disabled:opacity-50"
                >
                  <FontAwesomeIcon v-if="!form.processing" :icon="faSave" class="mr-2" />
                  <span v-else class="mr-2 animate-spin border-2 border-white/30 border-t-white rounded-full w-4 h-4 inline-block"></span>
                  Guardar Marca
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}
</style>
