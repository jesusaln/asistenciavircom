<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
  status: Number,
})

const title = computed(() => {
  return {
    503: 'Servicio No Disponible',
    500: 'Error del Servidor',
    404: 'Página No Encontrada',
    403: 'Acceso Denegado',
    401: 'No Autenticado',
    419: 'Sesión Expirada',
    429: 'Demasiadas Peticiones',
  }[props.status] || 'Error Desconocido'
})

const description = computed(() => {
  return {
    503: 'El sistema se encuentra en mantenimiento en este momento. Por favor, intente de nuevo en unos minutos.',
    500: 'Oops, algo salió mal en nuestros servidores. Hemos notificado a soporte técnico para resolverlo lo antes posible.',
    404: 'Lo sentimos, pero la página que está buscando no existe o ha sido movida.',
    403: 'No tiene los permisos necesarios para acceder a esta sección. Contacte a su administrador.',
    401: 'Por favor, inicie sesión para acceder a esta área.',
    419: 'Por su seguridad, su sesión ha expirado tras un tiempo de inactividad. Por favor, recargue la página o inicie sesión nuevamente.',
    429: 'Ha realizado demasiadas peticiones en poco tiempo. Por favor, espere un momento antes de continuar.',
  }[props.status] || 'Ha ocurrido un error inesperado. Si el problema persiste, contacte a soporte.'
})

const illustration = computed(() => {
  if (props.status === 404) return '🔍'
  if (props.status === 403 || props.status === 401) return '🛡️'
  if (props.status === 500 || props.status === 503) return '🔧'
  if (props.status === 419) return '⏱️'
  return '⚠️'
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-5 w-full bg-gray-50 dark:bg-slate-950">
    <Head :title="title" />
    <div class="text-center w-full max-w-lg">
      <div class="text-8xl mb-6 select-none animate-bounce" style="animation-duration: 3s;">
        {{ illustration }}
      </div>
      <h1 class="text-6xl font-black text-gray-900 dark:text-white tracking-tighter mb-2">
        {{ status }}
      </h1>
      <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">
        {{ title }}
      </h2>
      <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
        {{ description }}
      </p>
      
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <!-- Back Button -->
        <button 
          @click="() => window.history.back()" 
          class="px-6 py-3 rounded-xl bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 font-semibold shadow-sm border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 hover:text-gray-900 dark:hover:text-white transition-all focus:ring-4 focus:ring-gray-100 dark:focus:ring-slate-800"
        >
          &larr; Volver Atrás
        </button>
        
        <!-- Home Button -->
        <Link 
          href="/" 
          class="px-6 py-3 rounded-xl bg-blue-600 text-white font-semibold shadow-md shadow-blue-500/20 hover:bg-blue-700 transition-all focus:ring-4 focus:ring-blue-500/30"
        >
          Ir al Inicio
        </Link>
      </div>
    </div>
  </div>
</template>
