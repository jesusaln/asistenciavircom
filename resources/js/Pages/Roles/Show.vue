<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
  role: Object,
  allUsers: Array,
});

const notyf = new Notyf({
  duration: 3000,
  position: { x: 'right', y: 'top' },
});

const page = usePage();
onMounted(() => {
  const flash = page.props.flash;
  if (flash?.success) notyf.success(flash.success);
  if (flash?.error) notyf.error(flash.error);
});

// Modal for adding users
const showAddUserModal = ref(false);
const selectedUserId = ref(null);
const isLoading = ref(false);

// Users already in role (for exclusion from add list)
const assignedUserIds = computed(() => props.role.users.map(u => u.id));

// Available users (not yet assigned to this role)
const availableUsers = computed(() => {
  return props.allUsers.filter(u => !assignedUserIds.value.includes(u.id));
});

// Add user to role
const addUser = () => {
  if (!selectedUserId.value) return;
  
  isLoading.value = true;
  router.post(route('roles.add-user', props.role.id), {
    user_id: selectedUserId.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showAddUserModal.value = false;
      selectedUserId.value = null;
      isLoading.value = false;
    },
    onError: () => {
      isLoading.value = false;
      notyf.error('Error al agregar usuario');
    }
  });
};

// Remove user from role
const removeUser = (userId) => {
  if (!confirm('¿Estás seguro de que deseas remover este usuario del rol?')) return;
  
  router.post(route('roles.remove-user', props.role.id), {
    user_id: userId
  }, {
    preserveScroll: true,
    onError: () => {
      notyf.error('Error al remover usuario');
    }
  });
};

// Get role color class
const getRoleColorClass = (roleName) => {
  const colors = {
    'admin': 'from-purple-600 to-purple-700',
    'super-admin': 'from-red-600 to-red-700',
    'vendedor': 'from-blue-600 to-blue-700',
    'cobranza': 'from-green-600 to-green-700',
    'tecnico': 'from-amber-600 to-amber-700',
    'almacenista': 'from-orange-600 to-orange-700',
    'compras': 'from-teal-600 to-teal-700',
  };
  return colors[roleName] || 'from-gray-600 to-gray-700';
};
</script>

<template>
  <AppLayout>
    <Head :title="`Rol: ${role.name}`" />

    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <Link :href="route('roles.index')" 
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-white transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
              </Link>
              <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestión del Rol</h1>
                <p class="text-sm text-gray-600">Administra los usuarios asignados a este rol</p>
              </div>
            </div>
            <Link :href="route('roles.edit', role.id)" 
                  class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Editar Permisos
            </Link>
          </div>
        </div>

        <!-- Role Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
          <div :class="['px-8 py-6 bg-gradient-to-r text-white', getRoleColorClass(role.name)]">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-white/20 rounded-xl">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-bold capitalize">{{ role.name }}</h2>
                <p class="text-white/80">{{ role.permissions_count }} permisos asignados</p>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-8 bg-white">
            <div class="bg-white rounded-xl p-4 border border-gray-100">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13-1.268a2 2 0 100-4 2 2 0 000 4z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900">{{ role.users_count }}</p>
                  <p class="text-sm text-gray-500">Usuarios asignados</p>
                </div>
              </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-100">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 rounded-lg">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-2xl font-bold text-gray-900">{{ role.permissions_count }}</p>
                  <p class="text-sm text-gray-500">Permisos</p>
                </div>
              </div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-gray-100">
              <div class="flex items-center space-x-3">
                <div class="p-2 bg-purple-100 rounded-lg">
                  <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ new Date(role.created_at).toLocaleDateString('es-MX') }}</p>
                  <p class="text-sm text-gray-500">Fecha de creación</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Users Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Usuarios con este rol</h3>
              <p class="text-sm text-gray-500">Gestiona qué usuarios tienen asignado este rol</p>
            </div>
            <button 
              @click="showAddUserModal = true"
              :disabled="availableUsers.length === 0"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Agregar Usuario
            </button>
          </div>

          <!-- Users List -->
          <div v-if="role.users.length > 0" class="divide-y divide-gray-100">
            <div v-for="user in role.users" :key="user.id" 
                 class="px-6 py-4 flex items-center justify-between hover:bg-white transition-colors">
              <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ user.name }}</p>
                  <p class="text-sm text-gray-500">{{ user.email }}</p>
                </div>
              </div>
              <button 
                @click="removeUser(user.id)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                title="Remover del rol">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="px-6 py-16 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13-1.268a2 2 0 100-4 2 2 0 000 4z"/>
              </svg>
            </div>
            <h4 class="text-lg font-medium text-gray-900 mb-1">Sin usuarios asignados</h4>
            <p class="text-gray-500 mb-4">No hay usuarios con este rol actualmente</p>
            <button 
              @click="showAddUserModal = true"
              :disabled="availableUsers.length === 0"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Agregar primer usuario
            </button>
          </div>
        </div>

        <!-- Permissions Preview -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Permisos del rol</h3>
            <p class="text-sm text-gray-500">Los usuarios con este rol tienen los siguientes permisos</p>
          </div>
          <div class="p-6">
            <div v-if="role.permissions.length > 0" class="flex flex-wrap gap-2">
              <span v-for="permission in role.permissions" :key="permission" 
                    class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full">
                {{ permission }}
              </span>
            </div>
            <p v-else class="text-gray-500 text-center py-4">No hay permisos asignados a este rol</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Add User Modal -->
    <div v-if="showAddUserModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showAddUserModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Agregar usuario al rol</h3>
          <button @click="showAddUserModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="p-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Selecciona un usuario</label>
          <select v-model="selectedUserId" 
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option :value="null">-- Seleccionar usuario --</option>
            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
          <p v-if="availableUsers.length === 0" class="mt-2 text-sm text-amber-600">
            Todos los usuarios ya tienen este rol asignado.
          </p>
        </div>
        <div class="px-6 py-4 bg-white flex justify-end space-x-3">
          <button @click="showAddUserModal = false" 
                  class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-white transition-colors">
            Cancelar
          </button>
          <button @click="addUser" 
                  :disabled="!selectedUserId || isLoading"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
            <span v-if="isLoading">Agregando...</span>
            <span v-else>Agregar</span>
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
