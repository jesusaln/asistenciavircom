<template>
  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Soporte: Gestión de Categorías
      </h2>
    </template>

    <div class="py-12">
      <div class="w-full sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Botón Crear -->
            <div class="mb-6 flex justify-end">
              <button @click="openModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                <font-awesome-icon icon="plus" class="mr-2" />
                Nueva Categoría
              </button>
            </div>

            <!-- Tabla -->
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                   <th class="px-6 py-3 bg-white text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                   <th class="px-6 py-3 bg-white text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SLA (Horas)</th>
                   <th class="px-6 py-3 bg-white text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color/Icono</th>
                   <th class="px-6 py-3 bg-white text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tickets</th>
                   <th class="px-6 py-3 bg-white text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                   <th class="px-6 py-3 bg-white text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="cat in categorias" :key="cat.id">
                   <td class="px-6 py-4">
                      <div class="text-sm font-medium text-gray-900">{{ cat.nombre }}</div>
                      <div class="text-sm text-gray-500">{{ cat.descripcion }}</div>
                   </td>
                   <td class="px-6 py-4 text-sm text-gray-500">{{ cat.sla_horas }}h</td>
                   <td class="px-6 py-4">
                      <span :class="`badge bg-${cat.color}-100 text-${cat.color}-800 px-2 py-1 rounded inline-flex items-center`">
                         <font-awesome-icon :icon="cat.icono" class="mr-1" /> {{ cat.color }}
                      </span>
                   </td>
                   <td class="px-6 py-4 text-sm text-gray-500">{{ cat.tickets_count }}</td>
                   <td class="px-6 py-4">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                          :class="cat.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                          {{ cat.activo ? 'Activo' : 'Inactivo' }}
                      </span>
                   </td>
                   <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                      <button @click="openModal(cat)" class="text-indigo-600 hover:text-indigo-900">Editar</button>
                      <button @click="deleteCategory(cat)" class="text-red-600 hover:text-red-900">Eliminar</button>
                   </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="showModal" @close="closeModal">
       <div class="p-6">
           <h2 class="text-lg font-medium text-gray-900 mb-4">{{ form.id ? 'Editar' : 'Nueva' }} Categoría</h2>
           <form @submit.prevent="submit">
               <div class="mb-4">
                   <label class="block text-sm font-medium text-gray-700">Nombre</label>
                   <input v-model="form.nombre" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
               </div>
               <div class="mb-4">
                   <label class="block text-sm font-medium text-gray-700">Descripción</label>
                   <input v-model="form.descripcion" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
               </div>
               <div class="grid grid-cols-2 gap-4 mb-4">
                   <div>
                       <label class="block text-sm font-medium text-gray-700">SLA (Horas)</label>
                       <input v-model="form.sla_horas" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
                   </div>
                   <div>
                       <label class="block text-sm font-medium text-gray-700">Orden</label>
                       <input v-model="form.orden" type="number" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                   </div>
               </div>
               <div class="grid grid-cols-2 gap-4 mb-4">
                   <div>
                       <label class="block text-sm font-medium text-gray-700">Icono (FontAwesome)</label>
                       <input v-model="form.icono" type="text" placeholder="ej. users" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required />
                   </div>
                   <div>
                       <label class="block text-sm font-medium text-gray-700">Color (Tailwind)</label>
                       <select v-model="form.color" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                           <option value="blue">Azul</option>
                           <option value="green">Verde</option>
                           <option value="red">Rojo</option>
                           <option value="yellow">Amarillo</option>
                           <option value="indigo">Índigo</option>
                           <option value="gray">Gris</option>
                       </select>
                   </div>
               </div>
               <div class="mb-4 flex items-center">
                   <input type="checkbox" v-model="form.activo" class="mr-2" />
                   <label class="text-sm font-medium text-gray-700">Activo</label>
               </div>
               <div class="flex justify-end mt-6">
                   <button type="button" @click="closeModal" class="mr-3 px-4 py-2 text-gray-700 hover:text-gray-900">Cancelar</button>
                   <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700" :disabled="form.processing">Guardar</button>
               </div>
           </form>
       </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  categorias: Array,
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
        });
    } else {
        form.post(route('soporte.categorias.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCategory = (cat) => {
    if (confirm('¿Estás seguro de eliminar esta categoría?')) {
        form.delete(route('soporte.categorias.destroy', cat.id));
    }
};
</script>
