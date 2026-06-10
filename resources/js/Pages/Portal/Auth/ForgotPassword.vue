<template>
  <div class="min-h-screen bg-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-2xl font-black text-slate-900">
        Recuperar Contraseña
      </h2>
      <p class="mt-2 text-center text-sm text-slate-500">
        Ingresa tu email y te enviaremos un enlace para restablecerla.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded sm:px-10">
        <div v-if="status" class="mb-4 font-medium text-sm text-emerald-600 text-center">
            {{ status }}
        </div>

        <form class="space-y-6" @submit.prevent="submit">
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700">
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
                class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"
              />
            </div>
            <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
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
