<script setup>
import { ref } from 'vue'
import Modal from '@/Components/Modal.vue'
import SignaturePad from '@/Components/UI/SignaturePad.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faSignature, faTimes, faCheck, faTrash } from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
  show: Boolean,
  title: { type: String, default: 'Capturar Firma' },
  subtitle: { type: String, default: 'Por favor, firme dentro del recuadro' }
})

const emit = defineEmits(['close', 'confirm'])

const firmaBase64 = ref(null)

const confirm = () => {
  if (firmaBase64.value) {
    emit('confirm', firmaBase64.value)
  }
}

const close = () => {
  emit('close')
}
</script>

<template>
  <Modal :show="show" maxWidth="md" @close="close">
    <div class="bg-[var(--ui-surface)] p-8 rounded-[2rem] border border-[var(--ui-border)] relative overflow-hidden">
      <!-- Background Glow -->
      <div class="absolute -top-24 -right-24 w-48 h-48 bg-[var(--ui-accent)]/10 blur-[100px] rounded-full pointer-events-none"></div>
      
      <div class="relative">
        <div class="flex items-center justify-between mb-8">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[var(--ui-accent)]/20 flex items-center justify-center border border-[var(--ui-accent)]/30">
              <FontAwesomeIcon :icon="faSignature" class="text-[var(--ui-accent)] text-xl" />
            </div>
            <div>
              <h3 class="text-xl font-black text-[var(--ui-text)] uppercase tracking-wider">{{ title }}</h3>
              <p class="text-xs font-bold text-[var(--ui-text-muted)] uppercase tracking-wide mt-1">{{ subtitle }}</p>
            </div>
          </div>
          <button @click="close" class="w-10 h-10 rounded-xl bg-[var(--ui-surface-soft)] flex items-center justify-center text-[var(--ui-text-muted)] hover:text-[var(--ui-text)] transition-colors">
            <FontAwesomeIcon :icon="faTimes" />
          </button>
        </div>

        <div class="bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-3xl p-4 shadow-inner mb-8">
          <SignaturePad 
            v-model="firmaBase64" 
            label="" 
            placeholder="Firme aquí (Cliente)" 
            :height="250"
          />
        </div>

        <div class="flex flex-col sm:flex-row gap-4">
          <button 
            @click="close"
            class="flex-1 px-8 py-4 bg-[var(--ui-surface-soft)] text-[var(--ui-text-muted)] font-black uppercase tracking-wide rounded-2xl border border-[var(--ui-border)] hover:bg-[var(--ui-surface)] transition-all"
          >
            Cancelar
          </button>
          <button 
            @click="confirm"
            :disabled="!firmaBase64"
            class="flex-1 px-8 py-4 bg-gradient-to-r from-[var(--ui-accent)] to-brand-600 hover:brightness-110 text-[var(--ui-accent-contrast)] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-[var(--ui-accent)]/20 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center gap-3"
          >
            <FontAwesomeIcon :icon="faCheck" />
            Confirmar Firma
          </button>
        </div>
      </div>
    </div>
  </Modal>
</template>
