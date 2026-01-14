<template>
    <AppLayout title="Proyectos">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                    <font-awesome-icon icon="folder-open" class="mr-3 text-indigo-500" />
                    Mis Proyectos
                </h2>
                <button 
                    @click="openModal()"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm"
                >
                    <font-awesome-icon icon="plus" class="mr-2" />
                    Nuevo Proyecto
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Sección: Mis Proyectos -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-l-4 border-indigo-500 pl-3">
                        Proyectos Propios
                    </h3>
                    
                    <div v-if="misProyectos.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                         <font-awesome-icon icon="clipboard-list" class="text-4xl text-gray-400 mb-3" />
                         <p class="text-gray-500">No has creado ningún proyecto aún.</p>
                         <button @click="openModal()" class="mt-4 text-indigo-600 hover:text-indigo-800 font-medium">Crear mi primer proyecto</button>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="proyecto in misProyectos" :key="proyecto.id" 
                            class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-300 border-t-4 cursor-pointer group relative"
                            :style="{ borderColor: proyecto.color }"
                            @click="irAProyecto(proyecto.id)"
                        >
                            <div class="p-6">
                                <div class="flex justify-between items-start">
                                    <h4 class="text-lg font-bold text-gray-800 mb-2 truncate pr-2 group-hover:text-indigo-600 transition-colors w-3/4">
                                        {{ proyecto.nombre }}
                                    </h4>
                                    <!-- Action Buttons -->
                                    <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity absolute top-4 right-4 bg-white p-1 rounded-md shadow-sm">
                                        <button @click.stop="openModal(proyecto)" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-full hover:bg-gray-100 transition-colors" title="Editar">
                                            <font-awesome-icon icon="pen" class="w-3 h-3" />
                                        </button>
                                        <button @click.stop="confirmDelete(proyecto)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-full hover:bg-gray-100 transition-colors" title="Eliminar">
                                            <font-awesome-icon icon="trash-can" class="w-3 h-3" />
                                        </button>
                                    </div>
                                </div>
                                <p class="text-gray-500 text-sm mb-4 line-clamp-2 min-h-[40px]">
                                    {{ proyecto.descripcion || 'Sin descripción' }}
                                </p>
                                <div v-if="proyecto.cliente" class="flex items-center text-xs text-gray-600 mb-2">
                                    <font-awesome-icon icon="user" class="mr-1 text-gray-400" />
                                    <span class="font-medium">{{ proyecto.cliente.nombre_razon_social }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs text-gray-400 mt-4 border-t pt-4">
                                     <span>Creado el {{ formatDate(proyecto.created_at) }}</span>
                                     <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full font-semibold">Dueño</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Proyectos Compartidos -->
                <div v-if="proyectosCompartidos.length > 0">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 border-l-4 border-emerald-500 pl-3">
                        Compartidos conmigo
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                         <div v-for="proyecto in proyectosCompartidos" :key="proyecto.id" 
                            class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-300 border-t-4 border-emerald-400 cursor-pointer group"
                            @click="irAProyecto(proyecto.id)"
                        >
                            <div class="p-6 relative">
                                <div class="absolute top-0 right-0 bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-1 rounded-bl-lg">
                                    {{ proyecto.pivot?.role === 'viewer' ? 'LECTOR' : 'EDITOR' }}
                                </div>
                                
                                <h4 class="text-lg font-bold text-gray-800 mb-2 truncate pr-2 group-hover:text-emerald-600 transition-colors mt-2">
                                    {{ proyecto.nombre }}
                                </h4>
                                <p class="text-gray-500 text-sm mb-4 line-clamp-2 min-h-[40px]">
                                    {{ proyecto.descripcion || 'Sin descripción' }}
                                </p>
                                <div class="flex justify-between items-center text-xs text-gray-400 mt-4 border-t pt-4">
                                     <span>Compartido por <strong>{{ getOwnerName(proyecto) }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal Crear/Editar Proyecto -->
        <DialogModal :show="showingInfoModal" @close="closeModal">
            <template #title>{{ form.id ? 'Editar Proyecto' : 'Nuevo Proyecto' }}</template>
            <template #content>
                 <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre del Proyecto" />
                        <TextInput v-model="form.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Rediseño Web Corporativa" autofocus />
                        <InputError :message="form.errors.nombre" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Descripción" />
                        <textarea v-model="form.descripcion" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3"></textarea>
                    </div>
                    <div>
                        <InputLabel value="Color Identificador" />
                        <div class="flex space-x-2 mt-2">
                            <button v-for="color in colors" :key="color" 
                                @click="form.color = color"
                                class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 focus:outline-none"
                                :class="[form.color === color ? 'border-gray-600 ring-2 ring-indigo-200' : 'border-transparent']"
                                :style="{ backgroundColor: color }"
                            ></button>
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Cliente (opcional)" />
                        <select v-model="form.cliente_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Sin cliente asignado</option>
                            <option v-for="cliente in clientes" :key="cliente.id" :value="cliente.id">
                                {{ cliente.nombre_razon_social }} {{ cliente.rfc ? `(${cliente.rfc})` : '' }}
                            </option>
                        </select>
                        <InputError :message="form.errors.cliente_id" class="mt-2" />
                    </div>
                 </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal" class="mr-2">Cancelar</SecondaryButton>
                <button @click="saveProyecto" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 min-w-[100px] justify-center text-center" :disabled="form.processing">
                    {{ form.processing ? 'Guardando...' : (form.id ? 'Actualizar' : 'Crear') }}
                </button>
            </template>
        </DialogModal>

        <!-- Confirmation Modal -->
        <ConfirmationModal :show="confirmingDeletion" @close="closeDeleteModal">
            <template #title>Eliminar Proyecto</template>
            <template #content>
                ¿Estás seguro de que deseas eliminar este proyecto? Se eliminarán también todas sus tareas asociadas. Esta acción es irreversible.
            </template>
            <template #footer>
                <SecondaryButton @click="closeDeleteModal">Cancelar</SecondaryButton>
                <DangerButton class="ml-2" @click="deleteProyecto" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Eliminar Proyecto
                </DangerButton>
            </template>
        </ConfirmationModal>

    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    misProyectos: Array,
    proyectosCompartidos: Array,
    clientes: Array,
});

const showingInfoModal = ref(false);
const confirmingDeletion = ref(false);
const projectToDelete = ref(null);

const form = useForm({
    id: null,
    nombre: '',
    descripcion: '',
    color: '#fbbf24',
    cliente_id: '',
});

const colors = ['#f87171', '#fbbf24', '#34d399', '#60a5fa', '#a78bfa', '#f472b6', '#9ca3af'];

const openModal = (proyecto = null) => {
    form.reset();
    form.clearErrors();
    if (proyecto) {
        form.id = proyecto.id;
        form.nombre = proyecto.nombre;
        form.descripcion = proyecto.descripcion;
        form.color = proyecto.color;
        form.cliente_id = proyecto.cliente_id || '';
    } else {
        form.id = null;
    }
    showingInfoModal.value = true;
};

const closeModal = () => {
    showingInfoModal.value = false;
    form.reset();
};

const saveProyecto = () => {
    if (form.id) {
        form.put(route('proyectos.update', form.id), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('proyectos.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const confirmDelete = (proyecto) => {
    projectToDelete.value = proyecto;
    confirmingDeletion.value = true;
};

const closeDeleteModal = () => {
    confirmingDeletion.value = false;
    projectToDelete.value = null;
};

const deleteProyecto = () => {
    if (!projectToDelete.value) return;
    
    router.delete(route('proyectos.destroy', projectToDelete.value.id), {
        onSuccess: () => closeDeleteModal()
    });
};

const irAProyecto = (id) => {
    router.visit(route('proyectos.show', id));
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const getOwnerName = (proyecto) => {
     return proyecto.owner ? proyecto.owner.name : 'Desconocido';
};
</script>
