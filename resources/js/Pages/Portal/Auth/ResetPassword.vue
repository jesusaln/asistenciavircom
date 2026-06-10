<template>
  <div class="min-h-screen bg-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <h2 class="mt-6 text-center text-2xl font-black text-slate-900">
        Nueva Contraseña
      </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded sm:px-10">
        <form class="space-y-6" @submit.prevent="submit">
          
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <div class="mt-1">
              <input id="email" v-model="form.email" type="email" required class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm" />
            </div>
            <div v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <div>
             <label for="password" class="block text-sm font-medium text-slate-700">Nueva Contraseña</label>
             <div class="mt-1">
               <input id="password" v-model="form.password" type="password" required class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm" />
             </div>
             <div v-if="form.errors.password" class="text-rose-500 text-xs mt-1">{{ form.errors.password }}</div>
          </div>

          <div>
             <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirmar Contraseña</label>
             <div class="mt-1">
               <input id="password_confirmation" v-model="form.password_confirmation" type="password" required class="appearance-none block w-full px-3 py-2 border border-slate-300 rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm" />
             </div>
             <div v-if="form.errors.password_confirmation" class="text-rose-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
          </div>

          <div>
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full flex justify-center py-2 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 disabled:opacity-50"
            >
              Restablecer Contraseña
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('portal.password.store'), {
      onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>
