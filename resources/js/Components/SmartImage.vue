<template>
  <div
    ref="containerRef"
    class="smart-image"
    :class="[
      `smart-image--${shape}`,
      { 'smart-image--loaded': isLoaded }
    ]"
    :style="containerStyle"
  >
    <!-- Skeleton loader mientras carga -->
    <div
      v-if="!isLoaded && showSkeleton"
      class="smart-image__skeleton"
    >
      <div class="smart-image__skeleton-inner" />
    </div>

    <!-- Placeholder inicial (blur muy alto) -->
    <img
      v-if="showPlaceholder && !isLoaded"
      :src="placeholderSrc"
      :alt="alt"
      class="smart-image__placeholder"
      :class="{ 'smart-image__placeholder--hidden': isLoaded }"
    />

    <!-- Imagen principal -->
    <img
      ref="imageRef"
      :src="optimizedSrc"
      :srcset="srcset"
      :sizes="sizes"
      :alt="alt"
      :width="width"
      :height="height"
      :loading="lazy ? 'lazy' : 'eager'"
      :decoding="decoding"
      class="smart-image__img"
      :class="[
        { 'smart-image__img--hidden': !isLoaded },
        imgClass
      ]"
      @load="onLoad"
      @error="onError"
    />

    <!-- Error overlay -->
    <div
      v-if="error && showError"
      class="smart-image__error"
    >
      <slot name="error">
        <div class="smart-image__error-content">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="smart-image__error-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <span>{{ errorText }}</span>
        </div>
      </slot>
    </div>

    <!-- Badge de formato (WebP, etc.) -->
    <span
      v-if="formatBadge && webpSupported && !error"
      class="smart-image__badge"
    >
      WebP
    </span>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onUnmounted,
  watch,
} from 'vue';

const props = defineProps({
  // Fuente principal
  src: {
    type: String,
    default: '',
  },

  // Fuentes para responsive (srcset)
  sources: {
    type: Array,
    default: () => [],
    /*
      [
        { src: 'img-sm.webp', width: 400 },
        { src: 'img-md.webp', width: 800 },
        { src: 'img-lg.webp', width: 1200 },
      ]
    */
  },

  // Texto alternativo
  alt: {
    type: String,
    default: 'Imagen',
  },

  // Dimensiones
  width: {
    type: [Number, String],
    default: 400,
  },
  height: {
    type: [Number, String],
    default: 400,
  },

  // Clases adicionales para la imagen
  imgClass: {
    type: [String, Array, Object],
    default: '',
  },

  // Placeholder
  placeholder: {
    type: String,
    default: '',
  },

  // Fallback para errores
  fallback: {
    type: String,
    default: '',
  },

  // Mostrar skeleton loader
  showSkeleton: {
    type: Boolean,
    default: true,
  },

  // Mostrar placeholder
  showPlaceholder: {
    type: Boolean,
    default: true,
  },

  // Mostrar overlay de error
  showError: {
    type: Boolean,
    default: true,
  },

  // Texto de error personalizado
  errorText: {
    type: String,
    default: 'Imagen no disponible',
  },

  // Lazy loading con IntersectionObserver
  lazy: {
    type: Boolean,
    default: true,
  },

  // Prioridad de carga
  priority: {
    type: Boolean,
    default: false,
  },

  // Modo de decodificación
  decoding: {
    type: String,
    default: 'async',
    validator: (v) => ['auto', 'async', 'sync'].includes(v),
  },

  // Forma del contenedor
  shape: {
    type: String,
    default: 'square',
    validator: (v) => ['square', 'rounded-xl', 'circle', 'rect'].includes(v),
  },

  // Color de fondo del skeleton
  skeletonColor: {
    type: String,
    default: '#e5e7eb',
  },

  // Badge de formato
  formatBadge: {
    type: Boolean,
    default: false,
  },

  // Cache busting query param
  cacheBust: {
    type: Boolean,
    default: true,
  },
});

// Refs
const containerRef = ref(null);
const imageRef = ref(null);
const isLoaded = ref(false);
const error = ref(false);
const observer = ref(null);

// Estado del navegador
const webpSupported = ref(false);

// Computed: Verificar soporte WebP
webpSupported.value = (() => {
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  const dataUri = canvas.toDataURL('image/webp');
  return dataUri.indexOf('data:image/webp') === 0;
})();

// Computed: Optimizar src con cache busting
const optimizedSrc = computed(() => {
  if (!props.src) return '';

  let url = props.src;

  // Si es URL externa y queremos cache busting
  if (props.cacheBust && (url.startsWith('http://') || url.startsWith('https://'))) {
    const separator = url.includes('?') ? '&' : '?';
    url = `${url}${separator}_=${Date.now()}`;
  }

  return url;
});

// Computed: Generar srcset
const srcset = computed(() => {
  if (!props.sources.length) return '';

  return props.sources
    .map((s) => `${s.src} ${s.width}w`)
    .join(', ');
});

// Computed: Sizes attribute
const sizes = computed(() => {
  if (!props.sources.length) return '';

  // Generar sizes estándar
  return '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw';
});

// Computed: Placeholder src
const placeholderSrc = computed(() => {
  if (props.placeholder) return props.placeholder;

  // Generar placeholder local
  const bg = 'e5e7eb';
  const fg = '6b7280';
  const text = encodeURIComponent('Cargando...');

  return `https://placehold.co/${props.width}x${props.height}/${bg}/${fg}?text=${text}`;
});

// Computed: Fallback src
const fallbackSrc = computed(() => {
  if (props.fallback) return props.fallback;

  // Placeholder de error
  const bg = 'fee2e2';
  const fg = 'dc2626';
  const text = encodeURIComponent('Error');

  return `https://placehold.co/${props.width}x${props.height}/${bg}/${fg}?text=${text}`;
});

// Computed: Estilos del contenedor
const containerStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
}));

// Métodos
function onLoad() {
  isLoaded.value = true;
  error.value = false;

  // Desconectar observer si estaba activo
  if (observer.value) {
    observer.value.disconnect();
    observer.value = null;
  }
}

function onError(e) {
  error.value = true;
  isLoaded.value = false;

  // Intentar con fallback
  if (e.target.src !== fallbackSrc.value) {
    e.target.src = fallbackSrc.value;
    return;
  }

  // Si el fallback también falla, mostrar error
  console.warn('[SmartImage] Failed to load image:', props.src);
}

function setupObserver() {
  if (!props.lazy || props.priority) return;

  const options = {
    root: null,
    rootMargin: '50px',
    threshold: 0.01,
  };

  observer.value = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting && imageRef.value) {
        // Forzar carga de la imagen
        const src = imageRef.value.dataset.src || imageRef.value.src;
        if (src !== imageRef.value.src) {
          imageRef.value.src = src;
        }
        observer.value?.disconnect();
      }
    });
  }, options);

  if (containerRef.value) {
    observer.value.observe(containerRef.value);
  }
}

function cleanupObserver() {
  if (observer.value) {
    observer.value.disconnect();
    observer.value = null;
  }
}

// Lifecycle
onMounted(() => {
  setupObserver();
});

onUnmounted(() => {
  cleanupObserver();
});

// Watch para إعادة تحميل cuando cambia src
watch(() => props.src, () => {
  isLoaded.value = false;
  error.value = false;
});

// Expose para control externo
defineExpose({
  isLoaded,
  error,
  reload: () => {
    isLoaded.value = false;
    error.value = false;
    if (imageRef.value) {
      imageRef.value.src = optimizedSrc.value;
    }
  },
});
</script>

<style scoped>
.smart-image {
  position: relative;
  overflow: hidden;
  background-color: #f3f4f6;
}

.smart-image--square {
  border-radius: 0;
}

.smart-image--rounded-xl {
  border-radius: 8px;
}

.smart-image--circle {
  border-radius: 50%;
}

.smart-image--rect {
  border-radius: 4px;
}

/* Imagen */
.smart-image__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity 0.3s ease;
}

.smart-image__img--hidden {
  opacity: 0;
}

/* Placeholder */
.smart-image__placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: blur(20px);
  transform: scale(1.1);
  transition: opacity 0.3s ease;
}

.smart-image__placeholder--hidden {
  opacity: 0;
}

/* Skeleton */
.smart-image__skeleton {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(
    90deg,
    #f3f4f6 25%,
    #e5e7eb 50%,
    #f3f4f6 75%
  );
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.smart-image__skeleton-inner {
  width: 60%;
  height: 60%;
  max-width: 80px;
  max-height: 80px;
  border-radius: 50%;
  background-color: #d1d5db;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Error */
.smart-image__error {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #fef2f2;
}

.smart-image__error-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #dc2626;
  text-align: center;
  padding: 16px;
}

.smart-image__error-icon {
  width: 48px;
  height: 48px;
  opacity: 0.5;
}

/* Badge */
.smart-image__badge {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 4px 8px;
  background-color: rgba(0, 0, 0, 0.6);
  color: white;
  font-size: 10px;
  font-weight: 600;
  border-radius: 4px;
  text-transform: uppercase;
}

/* Loaded state */
.smart-image--loaded .smart-image__skeleton {
  display: none;
}
</style>
