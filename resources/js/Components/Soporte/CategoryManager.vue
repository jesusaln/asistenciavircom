<template>
  <div>
    <!-- Botón Crear -->
    <div class="mb-6 flex justify-end">
      <button @click="openModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">
        <font-awesome-icon icon="plus" class="mr-2" />
        Nueva Categoría
      </button>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">SLA</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Color/Icono</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Tickets</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Estado</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr v-for="cat in categorias" :key="cat.id">
            <td class="px-6 py-4">
              <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-gray-100">{{ cat.nombre }}</div>
              <div class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ cat.descripcion }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ cat.sla_horas }}h</td>
            <td class="px-6 py-4">
              <span :class="`badge bg-${cat.color}-100 dark:bg-${cat.color}-900/50 text-${cat.color}-800 dark:text-${cat.color}-300 px-2 py-1 rounded inline-flex items-center text-xs`">
                <font-awesome-icon :icon="cat.icono" class="mr-1" /> {{ cat.color }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">{{ cat.tickets_count || 0 }}</td>
            <td class="px-6 py-4">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                :class="cat.activo ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'">
                {{ cat.activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
              <button @click="openModal(cat)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">Editar</button>
              <button @click="deleteCategory(cat)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Eliminar</button>
            </td>
          </tr>
          <tr v-if="categorias.length === 0">
              <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm">
                  No hay categorías registradas.
              </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showModal" @close="closeModal">
      <div class="p-6 bg-white dark:bg-slate-900 dark:bg-gray-800 transition-colors">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white mb-4">{{ form.id ? 'Editar' : 'Nueva' }} Categoría</h2>
        <form @submit.prevent="submit">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
            <input v-model="form.nombre" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
            <input v-model="form.descripcion" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
          </div>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">SLA (Horas)</label>
              <input v-model="form.sla_horas" type="number" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden</label>
              <input v-model="form.orden" type="number" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Icono (FontAwesome)</label>
              <input v-model="form.icono" type="text" placeholder="ej. users" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color (Tailwind)</label>
              <select v-model="form.color" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="blue">Azul</option>
                <option value="green">Verde</option>
                <option value="red">Rojo</option>
                <option value="yellow">Amarillo</option>
                <option value="indigo">Índigo</option>
                <option value="gray">Gris</option>
                <option value="purple">Morado</option>
                <option value="pink">Rosa</option>
                <option value="orange">Naranja</option>
              </select>
            </div>
          </div>
          <div class="mb-4 flex items-center">
            <input type="checkbox" v-model="form.activo" class="mr-2 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Activo</label>
          </div>
          <div class="flex justify-end mt-6">
            <button type="button" @click="closeModal" class="mr-3 px-4 py-2 text-gray-700 hover:text-gray-900 dark:text-white dark:text-gray-400 dark:hover:text-gray-200">Cancelar</button>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700" :disabled="form.processing">Guardar</button>
          </div>
        </form>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  categorias: {
    type: Array,
    default: () => [],
  },
});

const showModal = ref(false);
const form = useForm({
  id: null,
  nombre: '',
  descripcion: '',
  sla_horas: 24,
  orden: 0,
  icono: 'tag',
  color: 'blue',
  activo: true,
});

const openModal = (cat = null) => {
  if (cat) {
    form.id = cat.id;
    form.nombre = cat.nombre;
    form.descripcion = cat.descripcion;
    form.sla_horas = cat.sla_horas;
    form.orden = cat.orden;
    form.icono = cat.icono;
    form.color = cat.color;
    form.activo = !!cat.activo;
  } else {
    form.reset();
    form.id = null;
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
};

const submit = () => {
  if (form.id) {
    form.put(route('soporte.categorias.update', form.id), {
      onSuccess: () => closeModal(),
      preserveScroll: true,
    });
  } else {
    form.post(route('soporte.categorias.store'), {
      onSuccess: () => closeModal(),
      preserveScroll: true,
    });
  }
};

const deleteCategory = (cat) => {
  if (confirm('¿Estás seguro de eliminar esta categoría?')) {
    form.delete(route('soporte.categorias.destroy', cat.id), {
        preserveScroll: true,
    });
  }
};
</script>
