<template>
    <AppLayout :title="`Planeación: ${proyecto.nombre}`">
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <Link :href="route('proyectos.index')" class="text-indigo-500 hover:text-indigo-700 mr-4 text-sm font-bold">
                        <font-awesome-icon icon="arrow-left" class="mr-1" /> Volver
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight flex items-center border-l-2 pl-4 border-gray-300">
                        <span class="w-4 h-4 rounded-full mr-3 block" :style="{ backgroundColor: proyecto.color }"></span>
                        {{ proyecto.nombre }}
                    </h2>
                </div>
                <div class="flex space-x-2">
                    <button 
                         v-if="isOwner"
                         @click="showingShareModal = true"
                         class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-white dark:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <font-awesome-icon icon="users" class="mr-2 text-indigo-500" />
                        Compartir
                    </button>

                    <button 
                         v-if="isOwner"
                         @click="confirmDeleteProject"
                         class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 rounded-md font-semibold text-xs text-red-600 uppercase tracking-widest shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                         title="Eliminar Proyecto"
                    >
                        <font-awesome-icon icon="trash-can" />
                    </button>

                    <button 
                        @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm"
                    >
                        <font-awesome-icon icon="plus" class="mr-2" />
                        Nueva Tarea
                    </button>
                    <button 
                        @click="generatePDF"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm"
                        title="Generar PDF"
                    >
                        <font-awesome-icon icon="file-pdf" class="mr-2" />
                        PDF
                    </button>
                </div>
            </div>
            <!-- Lista de Miembros -->
            <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400 ml-20">
                <span class="mr-2 font-bold">Miembros:</span>
                <div class="flex -space-x-2 overflow-hidden">
                    <div class="relative inline-block h-6 w-6 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] font-bold text-indigo-800" title="Dueño">
                       OP
                    </div>
                     <div v-for="member in members" :key="member.id" class="relative inline-block h-6 w-6 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px]" :title="member.name">
                        {{ member.name.charAt(0) }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-4">
            <!-- Kanban Board -->
            <div class="flex space-x-4 overflow-x-auto pb-4 custom-scrollbar px-4">
                <!-- Columns -->
                <div v-for="(tasks, status) in localColumnas" :key="status" class="flex-shrink-0 w-80">
                    <div class="bg-gray-100 rounded-lg p-3 shadow-inner h-full min-h-[500px]">
                        <div class="flex items-center justify-between mb-4 px-2">
                            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center">
                                <span :class="getStatusColor(status)" class="w-2 h-2 rounded-full mr-2"></span>
                                {{ getStatusLabel(status) }}
                                <span class="ml-2 bg-gray-200 text-gray-600 dark:text-gray-300 text-xs px-2 py-0.5 rounded-full">{{ tasks ? tasks.length : 0 }}</span>
                            </h3>
                        </div>


                        <!-- Cards -->
                        <draggable 
                            v-model="localColumnas[status]"
                            :group="{ name: 'tareas', pull: true, put: true }"
                            item-key="id"
                            :animation="300"
                            ghost-class="kanban-ghost"
                            drag-class="kanban-drag"
                            chosen-class="kanban-chosen"
                            class="space-y-3 min-h-[500px] transition-all duration-300"
                            @change="onDragChange($event, status)"
                        >
                            <template #item="{ element: tarea }">
                                <div 
                                    class="bg-white dark:bg-slate-900 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 hover:shadow-md transition-all duration-200 cursor-grab active:cursor-grabbing group relative"
                                    @click="editTarea(tarea)"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <span :class="getPriorityBadge(tarea.prioridad)" class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-tighter">
                                            {{ tarea.prioridad }}
                                        </span>
                                        <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click.stop="confirmDelete(tarea)" class="text-gray-400 hover:text-red-500 p-1">
                                                <font-awesome-icon icon="trash-can" />
                                            </button>
                                        </div>
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-amber-600 transition-colors">
                                        {{ tarea.titulo }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed">
                                        {{ tarea.descripcion || 'Sin descripción' }}
                                    </p>
                                    
                                    <div class="mt-3 flex items-center justify-between text-[10px] text-gray-400">
                                        <div class="flex items-center">
                                            <font-awesome-icon icon="clock" class="mr-1" />
                                            {{ formatDate(tarea.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </draggable>
                    </div>
                </div>
            </div>

            <!-- Cost Summary Panel -->
            <div class="mt-6 px-4">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow border border-gray-200 dark:border-slate-800">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <font-awesome-icon icon="receipt" class="text-amber-500 mr-2" />
                            Resumen de Costos
                        </h3>
                        <div class="flex items-center space-x-4">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalGastos) }}</span>
                            <button @click="showingGastoModal = true" class="bg-amber-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-amber-700">
                                <font-awesome-icon icon="plus" class="mr-1" /> Agregar
                            </button>
                        </div>
                    </div>
                    <div v-if="gastos && gastos.length > 0" class="max-h-64 overflow-y-auto overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                            <thead>
                                <tr class="bg-white dark:bg-slate-900">
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Fecha</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-32">Categoría</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Descripción</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Proveedor</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-28">Monto</th>
                                    <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                <tr v-for="gasto in gastos" :key="gasto.id" class="hover:bg-white dark:bg-slate-900">
                                    <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400">{{ formatDateShort(gasto.fecha_compra) }}</td>
                                    <td class="px-2 py-2 text-xs text-gray-900 dark:text-white">{{ gasto.categoria_gasto?.nombre || '-' }}</td>
                                    <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate" :title="gasto.notas">{{ gasto.notas || '-' }}</td>
                                    <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400">{{ gasto.proveedor?.nombre_razon_social || 'Sin proveedor' }}</td>
                                    <td class="px-2 py-2 text-xs text-gray-900 dark:text-white text-right font-medium">{{ formatCurrency(gasto.total) }}</td>
                                    <td class="px-2 py-2 text-center">
                                        <div class="flex justify-center space-x-1">
                                            <button @click="removeGasto(gasto.id)" class="text-red-500 hover:text-red-700" title="Eliminar">
                                                <font-awesome-icon icon="trash-can" class="w-3 h-3" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <font-awesome-icon icon="folder-open" class="text-gray-300 text-3xl mb-2" />
                        <p class="text-sm">No hay gastos asociados a este proyecto</p>
                        <button @click="showingGastoModal = true" class="mt-2 text-amber-600 hover:text-amber-800 font-medium text-sm">
                            Agregar gasto
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Panel -->
            <div class="mt-6 px-4">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow border border-gray-200 dark:border-slate-800">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <font-awesome-icon icon="boxes-stacked" class="text-indigo-500 mr-2" />
                            Productos del Proyecto
                        </h3>
                        <div class="flex items-center space-x-4">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totalProductos) }}</span>
                            <button @click="showingProductModal = true" class="bg-indigo-600 text-white px-3 py-1.5 rounded-md text-sm hover:bg-indigo-700">
                                <font-awesome-icon icon="plus" class="mr-1" /> Agregar
                            </button>
                        </div>
                    </div>
                    <div v-if="productosProyecto && productosProyecto.length > 0" class="max-h-64 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                            <thead class="bg-white dark:bg-slate-900 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Producto</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cantidad</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Precio Unit.</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Subtotal</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-800">
                                <tr v-for="prod in productosProyecto" :key="prod.id" class="hover:bg-white dark:bg-slate-900">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        <div class="font-medium">{{ prod.nombre }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ prod.codigo }}</div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-center">{{ prod.pivot.cantidad }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right">{{ formatCurrency(prod.pivot.precio_unitario) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right font-medium">{{ formatCurrency(prod.pivot.cantidad * prod.pivot.precio_unitario) }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <button @click="removeProducto(prod.id)" class="text-red-500 hover:text-red-700" title="Eliminar">
                                            <font-awesome-icon icon="trash-can" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <font-awesome-icon icon="boxes-stacked" class="text-gray-300 text-3xl mb-2" />
                        <p class="text-sm">No hay productos asignados a este proyecto</p>
                        <button @click="showingProductModal = true" class="mt-2 text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                            Agregar productos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Modal Compartir Proyecto -->
        <DialogModal :show="showingShareModal" @close="showingShareModal = false">
             <template #title>Compartir Proyecto</template>
             <template #content>
                 <div class="space-y-6">
                     <div>
                         <h4 class="text-sm font-medium text-gray-900 dark:text-white">Agregar Colaborador</h4>
                         <div class="flex mt-2 space-x-2">
                             <select v-model="shareForm.user_id" class="flex-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                 <option value="">Seleccionar usuario...</option>
                                 <option v-for="usuario in availableUsers" :key="usuario.id" :value="usuario.id">
                                     {{ usuario.name }} ({{ usuario.email }})
                                 </option>
                             </select>
                             <select v-model="shareForm.role" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:text-sm">
                                 <option value="editor">Editor</option>
                                 <option value="viewer">Lector</option>
                             </select>
                             <button @click="shareProject" class="bg-indigo-600 text-white px-3 py-2 rounded-md hover:bg-indigo-700" :disabled="shareForm.processing || !shareForm.user_id">
                                 <font-awesome-icon icon="plus" />
                             </button>
                         </div>
                         <InputError :message="shareForm.errors.user_id" class="mt-1" />
                     </div>

                     <div v-if="members.length > 0">
                         <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Miembros Actuales</h4>
                         <ul class="divide-y divide-gray-200 dark:divide-slate-800 border rounded-md">
                             <li v-for="member in members" :key="member.id" class="px-4 py-3 flex justify-between items-center text-sm">
                                 <div class="flex items-center">
                                      <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold mr-3">
                                          {{ member.name.charAt(0) }}
                                      </div>
                                      <div>
                                          <div class="font-medium text-gray-900 dark:text-white">{{ member.name }}</div>
                                          <div class="text-gray-500 dark:text-gray-400 text-xs">{{ member.email }}</div>
                                      </div>
                                 </div>
                                 <div class="flex items-center space-x-3">
                                     <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                         {{ member.pivot.role === 'editor' ? 'Editor' : 'Lector' }}
                                     </span>
                                     <button @click="removeMember(member)" class="text-red-500 hover:text-red-700">
                                         <font-awesome-icon icon="trash-can" />
                                     </button>
                                 </div>
                             </li>
                         </ul>
                     </div>
                 </div>
             </template>
             <template #footer>
                 <SecondaryButton @click="showingShareModal = false">Cerrar</SecondaryButton>
             </template>
        </DialogModal>
        
        <!-- ... Modales Tarea Existentes ... -->
        <!-- Estilos para Kanban Drag & Drop -->
        <component is="style">
        .kanban-ghost {
            opacity: 0.5;
            background: #fef3c7; /* amber-100 */
            border: 2px dashed #d97706; /* amber-600 */
        }
        .kanban-drag {
            opacity: 1;
            transform: scale(1.02) rotate(2deg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .kanban-chosen {
            background: #fffbeb; /* amber-50 */
        }
        /* Animación suave para movimientos de lista */
        .list-move,
        .list-enter-active,
        .list-leave-active {
            transition: all 0.5s ease;
        }
        .list-enter-from,
        .list-leave-to {
            opacity: 0;
            transform: translateX(30px);
        }
        .list-leave-active {
            position: absolute;
        }
        </component>

        <!-- Modal Tarea -->
        <DialogModal :show="showingModal" @close="closeModal">
            <template #title>
                {{ form.id ? 'Editar Tarea' : 'Nueva Tarea' }}
            </template>

            <template #content>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <InputLabel for="titulo" value="Título" />
                        <TextInput id="titulo" v-model="form.titulo" type="text" class="mt-1 block w-full" placeholder="Ej: Implementar modo oscuro" />
                        <InputError :message="form.errors.titulo" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="descripcion" value="Descripción" />
                        <textarea 
                            id="descripcion" 
                            v-model="form.descripcion" 
                            class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm" 
                            rows="3"
                            placeholder="Detalles sobre lo que se necesita hacer..."
                        ></textarea>
                        <InputError :message="form.errors.descripcion" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="estado" value="Estado" />
                            <select id="estado" v-model="form.estado" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm">
                                <option value="sugerencias">Sugerencias</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="en_progreso">En Progreso</option>
                                <option value="completado">Completado</option>
                            </select>
                            <InputError :message="form.errors.estado" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="prioridad" value="Prioridad" />
                            <select id="prioridad" v-model="form.prioridad" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm text-sm">
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                            <InputError :message="form.errors.prioridad" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal" class="mr-2">Cancelar</SecondaryButton>
                <button 
                    @click="saveTarea" 
                    :disabled="form.processing"
                    class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 active:bg-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm disabled:opacity-50"
                >
                    {{ form.id ? 'Actualizar' : 'Guardar' }}
                </button>
            </template>
        </DialogModal>

        <!-- Modal Confirmación Eliminación Proyecto -->
        <ConfirmationModal :show="confirmingProjectDeletion" @close="confirmingProjectDeletion = false">
            <template #title>Eliminar Proyecto</template>
            <template #content>
                ¿Estás seguro de que deseas eliminar este proyecto "{{ proyecto.nombre }}"? <br>
                Se eliminarán permanentemente todas las tareas y datos asociados. Esta acción no se puede deshacer.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingProjectDeletion = false">Cancelar</SecondaryButton>
                <DangerButton @click="deleteProyecto" class="ml-2">Eliminar Proyecto</DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Modal Confirmación Eliminación Tarea -->
        <ConfirmationModal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <template #title>Eliminar Tarea</template>
            <template #content>¿Estás seguro de que deseas eliminar esta tarea? Esta acción no se puede deshacer.</template>
            <template #footer>
                <SecondaryButton @click="confirmingDeletion = false">Cancelar</SecondaryButton>
                <DangerButton @click="deleteTarea" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="ml-2">Eliminar</DangerButton>
            </template>
        </ConfirmationModal>

        <!-- Modal Agregar Producto -->
        <DialogModal :show="showingProductModal" @close="showingProductModal = false">
            <template #title>Agregar Producto al Proyecto</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Buscar Producto" />
                        <TextInput 
                            v-model="productoBusqueda" 
                            type="text" 
                            placeholder="Escribe el nombre o código del producto..." 
                            class="mt-1 block w-full" 
                            @input="onProductoSearch"
                        />
                        <!-- Selected product display -->
                        <div v-if="productoSeleccionado" class="mt-2 p-3 bg-indigo-50 border border-indigo-200 rounded-md flex justify-between items-center">
                            <div>
                                <div class="font-medium text-indigo-900">{{ productoSeleccionado.nombre }}</div>
                                <div class="text-sm text-indigo-600">{{ productoSeleccionado.codigo }} - {{ formatCurrency(productoSeleccionado.precio_venta) }}</div>
                            </div>
                            <button @click="clearProductoSeleccionado" class="text-indigo-500 hover:text-indigo-700">
                                <font-awesome-icon icon="times" />
                            </button>
                        </div>
                        <!-- Search results -->
                        <div v-else-if="productoBusqueda.length >= 2 && productosFiltrados.length > 0" class="mt-1 border border-gray-300 rounded-md max-h-48 overflow-y-auto bg-white dark:bg-slate-900 shadow-lg">
                            <button 
                                v-for="prod in productosFiltrados" 
                                :key="prod.id" 
                                @click="selectProducto(prod)"
                                type="button"
                                class="w-full px-4 py-2 text-left hover:bg-indigo-50 border-b border-gray-100 last:border-b-0"
                            >
                                <div class="font-medium text-gray-900 dark:text-white">{{ prod.nombre }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ prod.codigo }} - {{ formatCurrency(prod.precio_venta) }}</div>
                            </button>
                        </div>
                        <div v-else-if="productoBusqueda.length >= 2 && productosFiltrados.length === 0" class="mt-1 p-3 text-center text-gray-500 dark:text-gray-400 text-sm">
                            No se encontraron productos
                        </div>
                        <InputError :message="productoForm.errors.producto_id" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Cantidad" />
                            <TextInput v-model="productoForm.cantidad" type="number" step="0.01" min="0.01" class="mt-1 block w-full" />
                            <InputError :message="productoForm.errors.cantidad" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Precio Unitario (opcional)" />
                            <TextInput v-model="productoForm.precio_unitario" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="Usa precio del producto" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Notas (opcional)" />
                        <textarea v-model="productoForm.notas" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2"></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showingProductModal = false" class="mr-2">Cancelar</SecondaryButton>
                <button @click="addProducto" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50" :disabled="productoForm.processing || !productoForm.producto_id">
                    Agregar
                </button>
            </template>
        </DialogModal>

        <!-- Modal Agregar Gasto -->
        <DialogModal :show="showingGastoModal" @close="showingGastoModal = false">
            <template #title>Agregar Gasto al Proyecto</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="Categoría" />
                            <button @click="openCategoriaModal" class="text-xs text-amber-600 hover:text-amber-800 font-semibold flex items-center">
                                <font-awesome-icon icon="plus" class="mr-1" />
                                Nueva
                            </button>
                        </div>
                        <select v-model="gastoForm.categoria_gasto_id" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm">
                            <option value="">Seleccionar categoría...</option>
                            <option v-for="cat in categoriasGasto" :key="cat.id" :value="cat.id">
                                {{ cat.nombre }}
                            </option>
                        </select>
                        <InputError :message="gastoForm.errors.categoria_gasto_id" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Total *" />
                            <TextInput v-model="gastoForm.total" type="number" step="0.01" min="0.01" class="mt-1 block w-full" placeholder="0.00" />
                            <InputError :message="gastoForm.errors.total" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Fecha" />
                            <TextInput v-model="gastoForm.fecha_compra" type="date" class="mt-1 block w-full" />
                            <InputError :message="gastoForm.errors.fecha_compra" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Descripción (opcional)" />
                        <textarea v-model="gastoForm.descripcion" class="mt-1 block w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm" rows="2" placeholder="Descripción del gasto..."></textarea>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showingGastoModal = false" class="mr-2">Cancelar</SecondaryButton>
                <button @click="addGasto" class="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 disabled:opacity-50" :disabled="gastoForm.processing || !gastoForm.total">
                    Agregar
                </button>
            </template>
        </DialogModal>

        <!-- Modal Nueva Categoría -->
        <DialogModal :show="showingCategoriaModal" @close="showingCategoriaModal = false">
            <template #title>Nueva Categoría de Gasto</template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Nombre *" />
                        <TextInput v-model="categoriaForm.nombre" type="text" class="mt-1 block w-full" placeholder="Ej: Viáticos, Materiales..." />
                        <InputError :message="categoriaForm.errors.nombre" class="mt-1" />
                    </div>
                    <div>
                        <InputLabel value="Descripción (opcional)" />
                        <textarea v-model="categoriaForm.descripcion" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2" placeholder="Descripción de la categoría..."></textarea>
                        <InputError :message="categoriaForm.errors.descripcion" class="mt-1" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showingCategoriaModal = false" class="mr-2">Cancelar</SecondaryButton>
                <button @click="addCategoria" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50" :disabled="categoriaForm.processing || !categoriaForm.nombre">
                    Guardar
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faPlus, faTasks, faClock, faChevronLeft, faChevronRight, faTrashCan, faFolderOpen, faArrowLeft, faUsers, faReceipt, faBoxesStacked, faTimes, faFilePdf } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import draggable from 'vuedraggable';
import { jsPDF } from 'jspdf';

library.add(faPlus, faTasks, faClock, faChevronLeft, faChevronRight, faTrashCan, faFolderOpen, faArrowLeft, faUsers, faReceipt, faBoxesStacked, faTimes, faFilePdf);

const props = defineProps({
    columnas: Object,
    proyecto: Object,
    members: Array,
    isOwner: Boolean,
    gastos: Array,
    totalGastos: Number,
    usuarios: Array,
    productosProyecto: Array,
    productosDisponibles: Array,
    totalProductos: Number,
    categoriasGasto: Array,
});

// Kanban Logic
const localColumnas = ref({});
const initLocalColumnas = () => {
    localColumnas.value = {
        'sugerencias': [...(props.columnas['sugerencias'] || [])],
        'pendiente': [...(props.columnas['pendiente'] || [])],
        'en_progreso': [...(props.columnas['en_progreso'] || [])],
        'completado': [...(props.columnas['completado'] || [])]
    };
};
watch(() => props.columnas, () => { initLocalColumnas(); }, { deep: true });
onMounted(() => { initLocalColumnas(); });

// Project Logic
const confirmingProjectDeletion = ref(false);
const confirmDeleteProject = () => confirmingProjectDeletion.value = true;
const deleteProyecto = () => {
    router.delete(route('proyectos.destroy', props.proyecto.id), {
        onSuccess: () => confirmingProjectDeletion.value = false
    });
};

// Tareas Actions
const showingModal = ref(false);
const confirmingDeletion = ref(false);
const tareaToDelete = ref(null);
const form = useForm({
    id: null,
    titulo: '',
    descripcion: '',
    estado: 'pendiente',
    prioridad: 'media',
    proyecto_id: null // Added for project binding
});

const openCreateModal = () => {
    form.reset();form.clearErrors();form.id = null;form.estado = 'pendiente';form.prioridad = 'media';
    form.proyecto_id = props.proyecto.id; // Bind to current project
    showingModal.value = true;
};
const editTarea = (tarea) => {
    form.reset(); form.clearErrors();
    form.id = tarea.id; form.titulo = tarea.titulo; form.descripcion = tarea.descripcion;
    form.estado = tarea.estado; form.prioridad = tarea.prioridad;
    form.proyecto_id = props.proyecto.id;
    showingModal.value = true;
};
const closeModal = () => showingModal.value = false;

const saveTarea = () => {
    if (form.id) {
        form.put(route('proyecto.tareas.update', form.id), { onSuccess: () => closeModal() });
    } else {
        form.post(route('proyecto.tareas.store'), { onSuccess: () => closeModal() });
    }
};

const confirmDelete = (tarea) => { tareaToDelete.value = tarea; confirmingDeletion.value = true; };
const deleteTarea = () => {
    form.delete(route('proyecto.tareas.destroy', tareaToDelete.value.id), {
        onSuccess: () => { confirmingDeletion.value = false; tareaToDelete.value = null; }
    });
};

// Drag and Drop
const onDragChange = (event, status) => {
    if (event.added) {
        updateTareaStatus(event.added.element, status, event.added.newIndex);
    } else if (event.moved) {
        reorderColumn(status);
    }
};
const updateTareaStatus = (tarea, newStatus, newIndex) => {
    tarea.estado = newStatus;
    router.put(route('proyecto.tareas.update', tarea.id), {
        estado: newStatus, orden: newIndex
    }, { preserveScroll: true });
};
const reorderColumn = (status) => {
    const tareasEnColumna = localColumnas.value[status].map((t, index) => ({
        id: t.id, orden: index, estado: status
    }));
    router.post(route('proyecto.tareas.reorder'), { tareas: tareasEnColumna }, { preserveScroll: true });
};

// --- Sharing Logic ---
const showingShareModal = ref(false);
const shareForm = useForm({
    user_id: '',
    role: 'editor'
});

// Usuarios disponibles (excluyendo los que ya son miembros)
const availableUsers = computed(() => {
    if (!props.usuarios) return [];
    const memberIds = props.members?.map(m => m.id) || [];
    return props.usuarios.filter(u => !memberIds.includes(u.id));
});

const shareProject = () => {
    shareForm.post(route('proyectos.share', props.proyecto.id), {
        preserveScroll: true,
        onSuccess: () => shareForm.reset('user_id')
    });
};

const removeMember = (member) => {
    if(confirm(`¿Quitar acceso a ${member.name}?`)) {
        router.delete(route('proyectos.members.remove', [props.proyecto.id, member.id]), {
            preserveScroll: true
        });
    }
};

// --- Product Management ---
const showingProductModal = ref(false);
const productoBusqueda = ref('');
const productoSeleccionado = ref(null);

const productoForm = useForm({
    producto_id: '',
    cantidad: 1,
    precio_unitario: '',
    notas: ''
});

// Filtered products based on search
const productosFiltrados = computed(() => {
    if (!productoBusqueda.value || productoBusqueda.value.length < 2) return [];
    const search = productoBusqueda.value.toLowerCase();
    return (props.productosDisponibles || [])
        .filter(p => 
            p.nombre.toLowerCase().includes(search) || 
            (p.codigo && p.codigo.toLowerCase().includes(search))
        )
        .slice(0, 15); // Limit to 15 results
});

const selectProducto = (producto) => {
    productoSeleccionado.value = producto;
    productoForm.producto_id = producto.id;
    productoForm.precio_unitario = producto.precio_venta || '';
    productoBusqueda.value = '';
};

const clearProductoSeleccionado = () => {
    productoSeleccionado.value = null;
    productoForm.producto_id = '';
    productoBusqueda.value = '';
};

const onProductoSearch = () => {
    // Clear selection if user starts typing again
    if (productoSeleccionado.value) {
        clearProductoSeleccionado();
    }
};

const addProducto = () => {
    productoForm.post(route('proyectos.productos.add', props.proyecto.id), {
        preserveScroll: true,
        onSuccess: () => {
            productoForm.reset();
            productoSeleccionado.value = null;
            productoBusqueda.value = '';
            showingProductModal.value = false;
        }
    });
};

const removeProducto = (productoId) => {
    if(confirm('¿Eliminar este producto del proyecto?')) {
        router.delete(route('proyectos.productos.remove', [props.proyecto.id, productoId]), {
            preserveScroll: true
        });
    }
};

// --- Expense Management ---
const showingGastoModal = ref(false);
const showingCategoriaModal = ref(false);

const gastoForm = useForm({
    categoria_gasto_id: '',
    total: '',
    fecha_compra: new Date().toISOString().split('T')[0],
    descripcion: ''
});

const categoriaForm = useForm({
    nombre: '',
    descripcion: '',
});

const openGastoModal = () => {
    gastoForm.reset();
    gastoForm.clearErrors();
    gastoForm.fecha_compra = new Date().toISOString().split('T')[0];
    showingGastoModal.value = true;
};

const addGasto = () => {
    gastoForm.post(route('proyectos.gastos.add', props.proyecto.id), {
        preserveScroll: true,
        onSuccess: () => {
            gastoForm.reset();
            gastoForm.fecha_compra = new Date().toISOString().split('T')[0];
            showingGastoModal.value = false;
        }
    });
};

const openCategoriaModal = () => {
    categoriaForm.reset();
    categoriaForm.clearErrors();
    showingCategoriaModal.value = true;
};

const addCategoria = () => {
    categoriaForm.post(route('proyectos.categorias.add'), {
        preserveScroll: true,
        onSuccess: () => {
            categoriaForm.reset();
            showingCategoriaModal.value = false;
        }
    });
};

// Utility helpers
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short' }).format(date);
};
const getPriorityBadge = (prioridad) => {
    const classes = { 'urgente': 'bg-red-100 text-red-700', 'alta': 'bg-orange-100 text-orange-700', 'media': 'bg-amber-100 text-amber-700', 'baja': 'bg-blue-100 text-blue-700' };
    return classes[prioridad] || 'bg-gray-100 text-gray-700';
};
const getStatusLabel = (status) => {
    const labels = { 'sugerencias': 'Sugerencias', 'pendiente': 'Por Hacer', 'en_progreso': 'En Progreso', 'completado': 'Completado' };
    return labels[status] || status;
};
const getStatusColor = (status) => {
    const colors = { 'sugerencias': 'bg-purple-500', 'pendiente': 'bg-gray-400', 'en_progreso': 'bg-amber-500', 'completado': 'bg-emerald-500' };
    return colors[status] || 'bg-gray-300';
};

// Currency formatter
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const formatDateShort = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: '2-digit' }).format(date);
};

// PDF Report Generation
const generatePDF = () => {
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    let y = 20;
    const leftMargin = 14;
    const lineHeight = 6;
    
    // Helper to add new page if needed
    const checkAddPage = (needed = 30) => {
        if (y > 270 - needed) {
            doc.addPage();
            y = 20;
        }
    };

    // Title
    doc.setFontSize(18);
    doc.setTextColor(40, 40, 40);
    doc.text(`INFORME DE PROYECTO`, leftMargin, y);
    y += 8;
    doc.setFontSize(14);
    doc.text(props.proyecto.nombre, leftMargin, y);
    y += 8;
    
    // Date
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.text(`Generado: ${new Date().toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' })}`, leftMargin, y);
    y += 12;
    
    // Draw separator line
    doc.setDrawColor(200, 200, 200);
    doc.line(leftMargin, y, pageWidth - leftMargin, y);
    y += 10;

    // Project Summary Section
    doc.setFontSize(12);
    doc.setTextColor(52, 73, 94);
    doc.text('RESUMEN DEL PROYECTO', leftMargin, y);
    y += 8;
    
    doc.setFontSize(10);
    doc.setTextColor(60, 60, 60);
    
    // Count tasks by status
    const completed = (props.columnas?.completado || []).length;
    const inProgress = (props.columnas?.en_progreso || []).length;
    const pending = (props.columnas?.pendiente || []).length;
    const suggestions = (props.columnas?.sugerencias || []).length;
    const totalTasks = completed + inProgress + pending + suggestions;
    const progress = totalTasks > 0 ? Math.round((completed / totalTasks) * 100) : 0;
    
    doc.text(`Total de Tareas: ${totalTasks}`, leftMargin, y); y += lineHeight;
    doc.text(`  - Completadas: ${completed}`, leftMargin, y); y += lineHeight;
    doc.text(`  - En Progreso: ${inProgress}`, leftMargin, y); y += lineHeight;
    doc.text(`  - Pendientes: ${pending}`, leftMargin, y); y += lineHeight;
    doc.text(`  - Sugerencias: ${suggestions}`, leftMargin, y); y += lineHeight;
    doc.text(`Avance General: ${progress}%`, leftMargin, y); y += lineHeight + 2;
    doc.text(`Total en Gastos: ${formatCurrency(props.totalGastos || 0)}`, leftMargin, y); y += lineHeight;
    doc.text(`Total en Productos: ${formatCurrency(props.totalProductos || 0)}`, leftMargin, y); y += 12;

    // Tasks Section
    if (totalTasks > 0) {
        checkAddPage(30);
        doc.setDrawColor(200, 200, 200);
        doc.line(leftMargin, y, pageWidth - leftMargin, y);
        y += 8;
        
        doc.setFontSize(12);
        doc.setTextColor(52, 73, 94);
        doc.text('TAREAS DEL PROYECTO', leftMargin, y);
        y += 10;
        
        doc.setFontSize(9);
        const statusConfig = [
            { key: 'completado', label: 'COMPLETADAS' },
            { key: 'en_progreso', label: 'EN PROGRESO' },
            { key: 'pendiente', label: 'PENDIENTES' },
            { key: 'sugerencias', label: 'SUGERENCIAS' }
        ];
        
        statusConfig.forEach(({ key, label }) => {
            const tasks = props.columnas?.[key] || [];
            if (tasks.length > 0) {
                checkAddPage(15);
                doc.setTextColor(80, 80, 80);
                doc.setFont(undefined, 'bold');
                doc.text(`${label} (${tasks.length})`, leftMargin, y);
                doc.setFont(undefined, 'normal');
                y += 5;
                
                tasks.forEach(task => {
                    checkAddPage(8);
                    doc.setTextColor(60, 60, 60);
                    const taskText = `   - ${task.titulo || 'Sin titulo'}`;
                    doc.text(taskText.substring(0, 85), leftMargin, y);
                    y += 5;
                });
                y += 4;
            }
        });
    }

    // Expenses Table
    if (props.gastos && props.gastos.length > 0) {
        checkAddPage(40);
        y += 5;
        doc.setDrawColor(200, 200, 200);
        doc.line(leftMargin, y, pageWidth - leftMargin, y);
        y += 8;
        
        doc.setFontSize(12);
        doc.setTextColor(52, 73, 94);
        doc.text('GASTOS OPERATIVOS', leftMargin, y);
        y += 10;
        
        // Table header
        doc.setFontSize(8);
        doc.setTextColor(255, 255, 255);
        doc.setFillColor(52, 73, 94);
        doc.rect(leftMargin, y - 4, pageWidth - 28, 7, 'F');
        doc.text('FECHA', leftMargin + 2, y);
        doc.text('CATEGORIA', leftMargin + 28, y);
        doc.text('DESCRIPCION', leftMargin + 65, y);
        doc.text('MONTO', pageWidth - 35, y);
        y += 7;
        
        // Table rows
        doc.setTextColor(60, 60, 60);
        props.gastos.forEach((gasto, idx) => {
            checkAddPage(8);
            if (idx % 2 === 0) {
                doc.setFillColor(248, 248, 248);
                doc.rect(leftMargin, y - 4, pageWidth - 28, 6, 'F');
            }
            doc.text(formatDateShort(gasto.fecha_compra) || '-', leftMargin + 2, y);
            doc.text((gasto.categoria_gasto?.nombre || '-').substring(0, 18), leftMargin + 28, y);
            doc.text((gasto.descripcion || '-').substring(0, 30), leftMargin + 65, y);
            doc.text(formatCurrency(gasto.total || 0), pageWidth - 35, y);
            y += 6;
        });
        
        // Total
        y += 2;
        doc.setFont(undefined, 'bold');
        doc.text(`TOTAL GASTOS: ${formatCurrency(props.totalGastos || 0)}`, pageWidth - 50, y);
        doc.setFont(undefined, 'normal');
        y += 10;
    }

    // Products Table
    if (props.productosProyecto && props.productosProyecto.length > 0) {
        checkAddPage(40);
        y += 5;
        doc.setDrawColor(200, 200, 200);
        doc.line(leftMargin, y, pageWidth - leftMargin, y);
        y += 8;
        
        doc.setFontSize(12);
        doc.setTextColor(52, 73, 94);
        doc.text('PRODUCTOS DEL PROYECTO', leftMargin, y);
        y += 10;
        
        // Table header
        doc.setFontSize(8);
        doc.setTextColor(255, 255, 255);
        doc.setFillColor(52, 73, 94);
        doc.rect(leftMargin, y - 4, pageWidth - 28, 7, 'F');
        doc.text('PRODUCTO', leftMargin + 2, y);
        doc.text('CANT', leftMargin + 85, y);
        doc.text('PRECIO', leftMargin + 105, y);
        doc.text('SUBTOTAL', pageWidth - 35, y);
        y += 7;
        
        // Table rows
        doc.setTextColor(60, 60, 60);
        props.productosProyecto.forEach((prod, idx) => {
            checkAddPage(8);
            if (idx % 2 === 0) {
                doc.setFillColor(248, 248, 248);
                doc.rect(leftMargin, y - 4, pageWidth - 28, 6, 'F');
            }
            doc.text((prod.nombre || 'Producto').substring(0, 45), leftMargin + 2, y);
            doc.text(String(prod.pivot?.cantidad || 0), leftMargin + 85, y);
            doc.text(formatCurrency(prod.pivot?.precio_unitario || 0), leftMargin + 105, y);
            const subtotal = (prod.pivot?.cantidad || 0) * (prod.pivot?.precio_unitario || 0);
            doc.text(formatCurrency(subtotal), pageWidth - 35, y);
            y += 6;
        });
        
        // Total
        y += 2;
        doc.setFont(undefined, 'bold');
        doc.text(`TOTAL PRODUCTOS: ${formatCurrency(props.totalProductos || 0)}`, pageWidth - 55, y);
        doc.setFont(undefined, 'normal');
    }

    // Save PDF
    const fileName = props.proyecto.nombre.replace(/[^a-zA-Z0-9]/g, '_').substring(0, 30);
    doc.save(`Proyecto_${fileName}_${new Date().toISOString().split('T')[0]}.pdf`);
};

</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 8px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
