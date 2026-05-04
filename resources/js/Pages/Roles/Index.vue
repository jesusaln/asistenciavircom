<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

// Función local $can reactiva
const page = usePage()
const auth = computed(() => page.props.auth)
const $can = (permissionOrRole) => {
  const authData = auth.value;
  if (!authData || !authData.user) return false;
  if (authData.user.is_admin) return true;
  const permissions = authData.user.permissions || [];
  const roles = authData.user.roles || [];
  const roleNames = Array.isArray(roles) ? roles.map(r => typeof r === 'string' ? r : r.name) : [];
  if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;
  return permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole);
};

const props = defineProps({
  roles: Array
});

const confirmarEliminacion = (id, name) => {
  if (confirm(`¿Estás seguro de que deseas eliminar el rol "${name}"?`)) {
    router.delete(route('roles.destroy', id));
  }
};
</script>

<template>
  <AppLayout>
    <Head title="Gestión de Roles" />

    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Gestión de Roles y Permisos</h2>
            <p class="text-sm text-gray-600">Administra los roles de usuario y sus niveles de acceso</p>
          </div>
          <Link v-if="$can('create roles')" :href="route('roles.create')" 
                class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring focus:ring-purple-300 disabled:opacity-25 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Crear Nuevo Rol
          </Link>
        </div>

        <!-- Tabla -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rol
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Permisos
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Creado
                </th>
                <th scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Acciones</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="role in roles" :key="role.id" class="hover:bg-white">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-full bg-purple-100 text-purple-600">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                      </svg>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 capitalize">{{ role.name }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    {{ role.permissions_count }} permisos asignados
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(role.created_at).toLocaleDateString() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <Link v-if="$can('edit roles')" :href="route('roles.show', role.id)" class="text-blue-600 hover:text-blue-900">Usuarios</Link>
                  <Link v-if="$can('edit roles')" :href="route('roles.edit', role.id)" class="text-indigo-600 hover:text-indigo-900">Editar</Link>
                  <button v-if="role.name !== 'admin' && $can('delete roles')" @click="confirmarEliminacion(role.id, role.name)" class="text-red-600 hover:text-red-900">
                    Eliminar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
