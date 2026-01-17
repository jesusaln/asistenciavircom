<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';

const props = defineProps({
  role: Object,
  permissionGroups: Array // Array of {module: string, label: string, permissions: {action: permission}}
});

const form = useForm({
  name: props.role.name,
  permissions: props.role.permissions || []
});

const actions = ['view', 'create', 'edit', 'delete', 'export', 'stats', 'manage'];
const actionLabels = {
  view: 'Ver',
  create: 'Crear',
  edit: 'Editar',
  delete: 'Eliminar',
  export: 'Exportar',
  stats: 'Estadísticas',
  manage: 'Gestionar'
};

const toggleRow = (group) => {
  const groupPermissionNames = Object.values(group.permissions).map(p => p.name);
  const allSelected = groupPermissionNames.every(name => form.permissions.includes(name));
  
  if (allSelected) {
    // Deseleccionar todos del grupo
    form.permissions = form.permissions.filter(name => !groupPermissionNames.includes(name));
  } else {
    // Seleccionar todos los que faltan
    groupPermissionNames.forEach(name => {
      if (!form.permissions.includes(name)) form.permissions.push(name);
    });
  }
};

const toggleColumn = (action) => {
  const actionPermissionNames = props.permissionGroups
    .map(group => group.permissions[action]?.name)
    .filter(Boolean);
    
  const allSelected = actionPermissionNames.every(name => form.permissions.includes(name));
  
  if (allSelected) {
    form.permissions = form.permissions.filter(name => !actionPermissionNames.includes(name));
  } else {
    actionPermissionNames.forEach(name => {
      if (!form.permissions.includes(name)) form.permissions.push(name);
    });
  }
};

const isRowSelected = (group) => {
  const groupPermissionNames = Object.values(group.permissions).map(p => p.name);
  return groupPermissionNames.length > 0 && groupPermissionNames.every(name => form.permissions.includes(name));
};

const isColumnSelected = (action) => {
  const actionPermissionNames = props.permissionGroups
    .map(group => group.permissions[action]?.name)
    .filter(Boolean);
  return actionPermissionNames.length > 0 && actionPermissionNames.every(name => form.permissions.includes(name));
};

const submit = () => {
  form.put(route('roles.update', props.role.id));
};
</script>

<template>
  <AppLayout :title="'Editar Rol: ' + role.name">
    <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Rol: {{ role.name }}
        </h2>
    </template>

    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="mb-8 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-900">Editar Rol</h2>
            <p class="text-sm text-gray-600">Modifica el nombre del rol y gestiona los niveles de acceso de forma detallada.</p>
          </div>

          <form @submit.prevent="submit">
            <!-- Nombre del Rol -->
            <div class="mb-8 max-w-md">
              <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nombre del Rol</label>
              <input type="text" id="name" v-model="form.name" class="block w-full border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-lg shadow-sm" placeholder="Ej: Gestor de Inventario">
              <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Matriz de Permisos -->
            <div class="mb-8">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Configuración de Accesos (Matriz)</h3>
                <div class="text-xs text-gray-500 italic">
                    Haz clic en los encabezados para seleccionar/deseleccionar filas o columnas completas.
                </div>
              </div>
              
              <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-white">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider sticky left-0 bg-white z-10">
                        Módulo / Funcionalidad
                      </th>
                      <th v-for="action in actions" :key="action" scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-200 transition-colors" @click="toggleColumn(action)">
                        <div class="flex flex-col items-center gap-1">
                            <span :class="{'text-purple-600': isColumnSelected(action)}">{{ actionLabels[action] }}</span>
                            <div class="w-3 h-3 rounded-full border border-gray-300 transition-colors" :class="isColumnSelected(action) ? 'bg-purple-600 border-purple-600' : 'bg-white'"></div>
                        </div>
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="group in permissionGroups" :key="group.module" class="hover:bg-white transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-white z-10 flex items-center justify-between cursor-pointer" @click="toggleRow(group)">
                        <span>{{ group.label }}</span>
                        <div class="w-3 h-3 rounded-md border border-gray-300 transition-colors" :class="isRowSelected(group) ? 'bg-purple-600 border-purple-600' : 'bg-white'"></div>
                      </td>
                      <td v-for="action in actions" :key="action" class="px-3 py-4 whitespace-nowrap text-center">
                        <div v-if="group.permissions[action]" class="flex justify-center">
                          <input type="checkbox" 
                                 v-model="form.permissions" 
                                 :value="group.permissions[action].name"
                                 class="h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded-md transition-all cursor-pointer">
                        </div>
                        <div v-else class="text-gray-300">—</div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-200">
              <Link :href="route('roles.index')" class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-900 mr-4 transition-colors">
                Cancelar
              </Link>
              <button type="submit" 
                      class="inline-flex items-center px-6 py-3 bg-purple-600 border border-transparent rounded-xl font-bold text-sm text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-25 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5" 
                      :disabled="form.processing">
                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Actualizar Rol
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Sombras sutiles para la columna pegajosa */
th.sticky, td.sticky {
  box-shadow: 2px 0 5px -2px rgba(0,0,0,0.1);
}
</style>
