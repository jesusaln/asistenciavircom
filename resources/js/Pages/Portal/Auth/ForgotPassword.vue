<template>
  <div class="min-h-screen bg-gray-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-white">
        Recuperar Contraseña
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
        Ingresa tu email y te enviaremos un enlace para restablecerla.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white dark:bg-slate-900 py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ status }}
        </div>

        <form class="space-y-6" @submit.prevent="submit">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
              Correo Electrónico
            </label>
            <div class="mt-1">
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
            </div>
            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              Enviar Enlace
            </button>
          </div>
          
          <div class="text-center">
             <Link :href="route('portal.login')" class="font-medium text-indigo-600 hover:text-indigo-500 text-sm">
                 Volver al inicio de sesión
             </Link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';

defineProps({
    status: String
});

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('portal.password.email'));
};
</script>
