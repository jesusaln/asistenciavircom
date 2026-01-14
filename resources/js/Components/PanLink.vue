<template>
  <li>
    <Link
      :href="href"
      class="flex items-center p-4 transition-colors duration-200"
    >
      <FontAwesomeIcon v-if="icon" :icon="formattedIcon" class="mr-3" />
      <slot />
    </Link>
  </li>
</template>

<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
  href: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    default: null, // No mostrar icono por defecto
  },
});

const page = usePage();

// Determina si el enlace es activo
const isActive = computed(() => page.url.startsWith(props.href));

// Formatear el ícono para que sea compatible con FontAwesomeIcon
const formattedIcon = computed(() => {
  return ['fas', props.icon]; // Asegúrate de que el formato sea compatible con FontAwesomeIcon
});
</script>

