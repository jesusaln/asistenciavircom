<template>
  <img
    :src="safeSrc"
    :alt="alt"
    :width="w"
    :height="h"
    @error="onErr"
    loading="lazy"
    class="transition-opacity duration-200"
    :class="{ 'opacity-50': isLoadingFallback }"
  />
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  src: String,
  alt: { type: String, default: 'Imagen' },
  w: { type: Number, default: 400 },
  h: { type: Number, default: 400 },
  bg: { type: String, default: 'e5e7eb' }, // sin '#'
  fg: { type: String, default: '6b7280' },
  text: { type: String, default: 'Sin imagen' },
  localFallback: { type: String, default: '/images/placeholder-400x400.svg' }
});

const isLoadingFallback = ref(false);

// Placeholder local usando la ruta de Laravel (primera opción)
const localPlaceholder = computed(() =>
  `/placeholder/${props.w}x${props.h}/${props.bg}/${props.fg}?text=${encodeURIComponent(props.text)}`
);

// CDN placeholder como respaldo
const cdnPlaceholder = computed(() =>
  `https://placehold.co/${props.w}x${props.h}/${props.bg}/${props.fg}?text=${encodeURIComponent(props.text)}`
);

// Fuente segura: usa imagen original si existe, sino placeholder local
const safeSrc = computed(() => {
  const trimmedSrc = props.src?.trim();
  return trimmedSrc ? trimmedSrc : localPlaceholder.value;
});

function onErr(e) {
  isLoadingFallback.value = true;

  // 1er fallo: si no es la imagen original, intenta con placeholder local
  if (e.target.dataset.fallback !== 'local' && e.target.src !== localPlaceholder.value) {
    e.target.dataset.fallback = 'local';
    e.target.src = localPlaceholder.value;
    return;
  }

  // 2do fallo: intenta con CDN placeholder
  if (e.target.dataset.fallback !== 'cdn') {
    e.target.dataset.fallback = 'cdn';
    e.target.src = cdnPlaceholder.value;
    return;
  }

  // 3er fallo: usa imagen local por defecto
  e.target.src = props.localFallback;
}
</script>

