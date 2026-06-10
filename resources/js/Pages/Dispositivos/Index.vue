<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { computed } from 'vue';
const { formatDateTime } = useFormatters();

const props = defineProps({
  sessions: Array
});

const page = usePage();
const auth = computed(() => page.props.auth);

const formatFechaHoraLocal = (dateString) => {
  if (!dateString) return 'Nunca';
  const date = new Date(dateString);
  return date.toLocaleString('es-MX', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>

<template>
  <AppLayout>
    <Head title="Dispositivos Conectados" />

    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        
        <!-- Encabezado -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold text-slate-900">Dispositivos Conectados</h2>
            <p class="text-sm text-slate-500">Monitoreo de sesiones activas, versiones de la app y hardware en uso.</p>
          </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Usuario
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Dispositivo
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  SO / Versión
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Versión App
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                  Último Acceso
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="session in sessions" :key="session.id" class="hover:bg-slate-50">
                
                <!-- Usuario -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-brand-100 text-brand-800 font-bold">
                      {{ session.user?.name ? session.user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-slate-900">
                        {{ session.user?.name || 'Usuario Desconocido' }}
                      </div>
                      <div class="text-sm text-slate-500">
                        {{ session.user?.email || 'Sin correo' }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Dispositivo (Modelo / Marca) -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                  <div class="font-medium">{{ session.model || 'Desconocido' }}</div>
                  <div class="text-xs text-slate-500 capitalize">{{ session.manufacturer || 'Sin marca' }}</div>
                </td>

                <!-- Plataforma y Versión de SO -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  <span :class="{
                    'px-2.5 py-0.5 text-xs font-medium rounded-full': true,
                    'bg-emerald-100 text-emerald-800 dark:text-emerald-200': session.platform === 'android',
                    'bg-sky-100 text-sky-800 dark:text-sky-200': session.platform === 'ios',
                    'bg-slate-100 text-slate-800': session.platform !== 'android' && session.platform !== 'ios'
                  }">
                    {{ session.platform?.toUpperCase() || 'WEB' }}
                  </span>
                  <div class="text-xs text-slate-500 mt-1">OS: {{ session.os_version || 'N/A' }}</div>
                </td>

                <!-- Versión de la App -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-bold">
                  v{{ session.version || '0.0.1' }}
                </td>

                <!-- Último Acceso -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                  {{ formatFechaHoraLocal(session.last_seen_at) }}
                </td>

              </tr>
              <tr v-if="sessions.length === 0">
                <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                  No se han registrado sesiones de dispositivos todavía.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AppLayout>
</template>
