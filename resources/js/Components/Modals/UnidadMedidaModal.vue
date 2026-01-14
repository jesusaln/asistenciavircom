<template>
    <DialogModal :show="show" @close="closeModal">
        <template #title>
            Gestión de Unidades de Medida
        </template>

        <template #content>
            <!-- Formulario para crear/editar -->
            <div v-if="modalMode !== 'list'" class="space-y-4">
                <div>
                    <InputLabel for="unidad_nombre" value="Nombre de la Unidad *" />
                    <TextInput
                        id="unidad_nombre"
                        v-model="unidadForm.nombre"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Kilogramos, Litros, Pieza"
                        required
                    />
                    <InputError :message="unidadForm.errors.nombre" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="unidad_abreviatura" value="Abreviatura" />
                    <TextInput
                        id="unidad_abreviatura"
                        v-model="unidadForm.abreviatura"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: kg, l, pz"
                    />
                    <InputError :message="unidadForm.errors.abreviatura" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="unidad_descripcion" value="Descripción" />
                    <textarea
                        id="unidad_descripcion"
                        v-model="unidadForm.descripcion"
                        rows="3"
                        class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm"
                        placeholder="Descripción opcional de la unidad"
                    ></textarea>
                    <InputError :message="unidadForm.errors.descripcion" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="unidad_estado" value="Estado" />
                    <select
                        id="unidad_estado"
                        v-model="unidadForm.estado"
                        class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm"
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    <InputError :message="unidadForm.errors.estado" class="mt-2" />
                </div>
            </div>

            <!-- Lista de unidades -->
            <div v-else class="space-y-4">
                <!-- Buscador -->
                <div class="flex gap-2">
                    <TextInput
                        v-model="searchQuery"
                        type="text"
                        class="flex-1"
                        placeholder="Buscar unidades..."
                    />
                    <PrimaryButton @click="modalMode = 'create'">
                        + Nueva Unidad
                    </PrimaryButton>
                </div>

                <div class="flex items-start justify-between p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <div class="text-sm text-gray-700">
                        <span class="font-semibold">Predeterminada:</span>
                        {{ defaultUnidadName }}
                        <span class="text-gray-500"> - Siempre disponible y no se puede eliminar.</span>
                    </div>
                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">Valor base</span>
                </div>

                <!-- Loading state -->
                <div v-if="loading" class="flex justify-center py-4">
                    <LoadingSpinner />
                </div>

                <!-- Lista de unidades -->
                <div v-else-if="unidades.length > 0" class="max-h-96 overflow-y-auto">
                    <div
                        v-for="unidad in filteredUnidades"
                        :key="unidad.id"
                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                    >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 flex items-center gap-2">
                                {{ unidad.nombre }}
                                <span v-if="unidad.nombre === defaultUnidadName" class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                    Predeterminada
                                </span>
                                <span v-if="unidad.abreviatura" class="text-sm text-gray-500 ml-1">
                                    ({{ unidad.abreviatura }})
                                </span>
                            </div>
                            <div v-if="unidad.descripcion" class="text-sm text-gray-600 mt-1">
                                {{ unidad.descripcion }}
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                        unidad.estado === 'activo'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-red-100 text-red-800'
                                    ]"
                                >
                                    {{ unidad.estado }}
                                </span>
                                <span v-if="unidad.productos_count > 0" class="text-xs text-gray-500">
                                    Usada en {{ unidad.productos_count }} productos
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <SecondaryButton
                                size="sm"
                                @click="editUnidad(unidad)"
                            >
                                Editar
                            </SecondaryButton>
                            <DangerButton
                                v-if="unidad.puede_eliminarse && unidad.nombre !== defaultUnidadName"
                                size="sm"
                                @click="confirmDelete(unidad)"
                            >
                                Eliminar
                            </DangerButton>
                            <span
                                v-else-if="unidad.nombre === defaultUnidadName"
                                class="text-xs text-blue-600 px-2 py-1 bg-blue-50 rounded"
                                title="Unidad de medida predeterminada"
                            >
                                Predeterminada
                            </span>
                            <span
                                v-else
                                class="text-xs text-gray-400 px-2 py-1 bg-gray-100 rounded"
                                title="No se puede eliminar porque esta unidad esta en uso"
                            >
                                En uso
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="text-center py-8 text-gray-500">
                    No se encontraron unidades de medida
                </div>
            </div>
        </template>

        <template #footer>
            <!-- Footer para formularios -->
            <div v-if="modalMode !== 'list'" class="flex justify-end gap-2">
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton
                    :disabled="unidadForm.processing"
                    @click="submitUnidad"
                >
                    <span v-if="unidadForm.processing">Guardando...</span>
                    <span v-else>{{ editingUnidad ? 'Actualizar' : 'Crear' }}</span>
                </PrimaryButton>
            </div>

            <!-- Footer para lista -->
            <div v-else class="flex justify-start">
                <SecondaryButton @click="closeModal">
                    Cerrar
                </SecondaryButton>
            </div>
        </template>
    </DialogModal>

    <!-- Modal de confirmación para eliminar -->
    <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
        <template #title>
            Eliminar Unidad de Medida
        </template>

        <template #content>
            ¿Estás seguro de que deseas eliminar la unidad "{{ unidadToDelete?.nombre }}"?

            <div v-if="unidadToDelete?.productos_count > 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-red-800 text-sm">
                    <strong>Advertencia:</strong> Esta unidad está siendo utilizada por {{ unidadToDelete.productos_count }} productos.
                    No se puede eliminar hasta que se cambie la unidad en todos los productos que la utilizan.
                </p>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="showDeleteModal = false">
                Cancelar
            </SecondaryButton>
            <DangerButton
                v-if="unidadToDelete?.puede_eliminarse && unidadToDelete?.nombre !== defaultUnidadName"
                :disabled="deleting"
                @click="deleteUnidad"
            >
                <span v-if="deleting">Eliminando...</span>
                <span v-else>Eliminar</span>
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

// Props
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    unidades: {
        type: Array,
        default: () => [],
    },
    defaultName: {
        type: String,
        default: 'Pieza',
    },
});

// Emits
const emit = defineEmits(['close', 'unidad-created', 'unidad-updated', 'unidad-deleted']);

// Estado del modal
const modalMode = ref('list'); // 'list', 'create', 'edit'
const editingUnidad = ref(null);
const loading = ref(false);
const searchQuery = ref('');
const showDeleteModal = ref(false);
const unidadToDelete = ref(null);
const deleting = ref(false);

// Formulario para crear/editar unidades
const unidadForm = useForm({
    nombre: '',
    abreviatura: '',
    descripcion: '',
    estado: 'activo',
});

// Computed
const defaultUnidadName = computed(() => (props.defaultName || 'Pieza').trim());

const ordenarUnidades = (lista = []) => {
    return [...lista].sort((a, b) => {
        if (a.nombre === defaultUnidadName.value) return -1;
        if (b.nombre === defaultUnidadName.value) return 1;
        return (a.nombre || '').localeCompare(b.nombre || '');
    });
};

const filteredUnidades = computed(() => {
    const base = props.unidades || [];

    const coincidencias = !searchQuery.value
        ? base
        : base.filter(unidad =>
            unidad.nombre.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (unidad.abreviatura && unidad.abreviatura.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
            (unidad.descripcion && unidad.descripcion.toLowerCase().includes(searchQuery.value.toLowerCase()))
        );

    return ordenarUnidades(coincidencias);
});

// Métodos
const closeModal = () => {
    modalMode.value = 'list';
    editingUnidad.value = null;
    unidadForm.reset();
    unidadForm.clearErrors();
    emit('close');
};

const createUnidad = () => {
    modalMode.value = 'create';
    editingUnidad.value = null;
    unidadForm.reset();
    unidadForm.clearErrors();
};

const editUnidad = (unidad) => {
    modalMode.value = 'edit';
    editingUnidad.value = unidad;
    unidadForm.nombre = unidad.nombre;
    unidadForm.abreviatura = unidad.abreviatura || '';
    unidadForm.descripcion = unidad.descripcion || '';
    unidadForm.estado = unidad.estado;
    unidadForm.clearErrors();
};

const submitUnidad = () => {
    if (editingUnidad.value) {
        updateUnidad();
    } else {
        createNewUnidad();
    }
};

const createNewUnidad = () => {
    unidadForm.post('/api/unidades-medida', {
        onSuccess: (response) => {
            emit('unidad-created', response.props?.unidad || unidadForm);
            closeModal();
        },
        onError: (errors) => {
            console.error('Errores al crear unidad:', errors);
        }
    });
};

const updateUnidad = () => {
    unidadForm.put(`/api/unidades-medida/${editingUnidad.value.id}`, {
        onSuccess: (response) => {
            emit('unidad-updated', response.props?.unidad || unidadForm);
            closeModal();
        },
        onError: (errors) => {
            console.error('Errores al actualizar unidad:', errors);
        }
    });
};

const confirmDelete = (unidad) => {
    if (unidad?.nombre === defaultUnidadName.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Unidad predeterminada',
            text: 'La unidad predeterminada no se puede eliminar.'
        });
        return;
    }
    unidadToDelete.value = unidad;
    showDeleteModal.value = true;
};

const deleteUnidad = () => {
    if (!unidadToDelete.value) return;

    deleting.value = true;

    // Usar axios o fetch para eliminar
    fetch(`/api/unidades-medida/${unidadToDelete.value.id}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            emit('unidad-deleted', unidadToDelete.value);
            showDeleteModal.value = false;
            unidadToDelete.value = null;
        } else {
            throw new Error('Error al eliminar la unidad');
        }
    })
    .catch(error => {
        console.error('Error eliminando unidad:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al eliminar unidad',
            text: 'No se pudo eliminar la unidad de medida'
        });
    })
    .finally(() => {
        deleting.value = false;
    });
};

// Watch para limpiar formulario cuando cambie el modo
watch(modalMode, (newMode) => {
    if (newMode !== 'list') {
        unidadForm.clearErrors();
    }
});
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>




