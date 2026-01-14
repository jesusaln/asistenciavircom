<template>
    <div class="space-y-8">
        <div>
            <h2 class="text-xl font-semibold text-red-600 mb-2 flex items-center gap-2">
                <FontAwesomeIcon icon="exclamation-triangle" />
                Zona de Peligro
            </h2>
            <p class="text-sm text-gray-500 mb-6">
                ⚠️ Las acciones en esta sección son <strong>irreversibles</strong>. Los datos eliminados no se pueden recuperar.
            </p>

            <!-- Advertencia Principal -->
            <div class="bg-red-50 border-2 border-red-300 rounded-xl p-6 mb-8">
                <div class="flex items-start gap-3">
                    <FontAwesomeIcon icon="skull-crossbones" class="text-red-600 text-2xl mt-1" />
                    <div>
                        <h3 class="font-bold text-red-800">¡CUIDADO!</h3>
                        <p class="text-sm text-red-700">
                            Estas acciones eliminarán permanentemente los datos seleccionados de la base de datos.
                            Asegúrate de tener un respaldo antes de continuar.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Grid de Botones de Eliminación -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                
                <!-- Eliminar Productos -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="boxes" class="text-orange-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Productos</h4>
                                <p class="text-xs text-gray-500">Todos los productos del catálogo</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('productos', 'todos los productos')"
                            :disabled="isDeleting.productos"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.productos" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Compras -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="shopping-cart" class="text-blue-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Compras</h4>
                                <p class="text-xs text-gray-500">Todas las compras e items</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('compras', 'todas las compras')"
                            :disabled="isDeleting.compras"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.compras" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Ventas -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="cash-register" class="text-emerald-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Ventas</h4>
                                <p class="text-xs text-gray-500">Todas las ventas y facturas</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('ventas', 'todas las ventas')"
                            :disabled="isDeleting.ventas"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.ventas" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Bancos -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="university" class="text-purple-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Bancos</h4>
                                <p class="text-xs text-gray-500">Cuentas bancarias y movimientos</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('bancos', 'todos los bancos y movimientos')"
                            :disabled="isDeleting.bancos"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.bancos" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Inventario -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="warehouse" class="text-amber-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Inventario</h4>
                                <p class="text-xs text-gray-500">Stock, series y movimientos</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('inventario', 'todo el inventario')"
                            :disabled="isDeleting.inventario"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.inventario" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Clientes -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="users" class="text-cyan-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Clientes</h4>
                                <p class="text-xs text-gray-500">Todos los clientes</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('clientes', 'todos los clientes')"
                            :disabled="isDeleting.clientes"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.clientes" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar Proveedores -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="truck" class="text-indigo-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar Proveedores</h4>
                                <p class="text-xs text-gray-500">Todos los proveedores</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('proveedores', 'todos los proveedores')"
                            :disabled="isDeleting.proveedores"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.proveedores" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>

                <!-- Eliminar CFDIs -->
                <div class="bg-white border border-gray-200 rounded-xl p-5 hover:border-red-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-rose-100 rounded-lg flex items-center justify-center">
                                <FontAwesomeIcon icon="file-invoice" class="text-rose-600" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Eliminar CFDIs</h4>
                                <p class="text-xs text-gray-500">Todos los documentos fiscales</p>
                            </div>
                        </div>
                        <button 
                            @click="confirmarEliminacion('cfdis', 'todos los CFDIs')"
                            :disabled="isDeleting.cfdis"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold hover:bg-red-700 disabled:opacity-50 flex items-center gap-2"
                        >
                            <FontAwesomeIcon v-if="isDeleting.cfdis" icon="spinner" class="animate-spin" />
                            <FontAwesomeIcon v-else icon="trash" />
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sección Crítica: Eliminar Todo -->
            <div class="border-t-4 border-red-500 pt-8">
                <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center gap-2">
                    <FontAwesomeIcon icon="bomb" />
                    Acciones Críticas
                </h3>

                <div class="space-y-4">
                    <!-- Eliminar TODO -->
                    <div class="bg-red-100 border-2 border-red-400 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center">
                                    <FontAwesomeIcon icon="skull" class="text-white text-xl" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-red-900 text-lg">🔥 ELIMINAR TODO</h4>
                                    <p class="text-sm text-red-700">Elimina TODOS los datos del sistema (excepto usuarios)</p>
                                </div>
                            </div>
                            <button 
                                @click="confirmarEliminacionTotal()"
                                :disabled="isDeleting.todo"
                                class="px-6 py-3 bg-red-700 text-white rounded-xl font-bold hover:bg-red-800 disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-red-500/30"
                            >
                                <FontAwesomeIcon v-if="isDeleting.todo" icon="spinner" class="animate-spin" />
                                <FontAwesomeIcon v-else icon="trash-alt" />
                                ELIMINAR TODO
                            </button>
                        </div>
                    </div>

                    <!-- Eliminar TODO + Usuarios (excepto SuperAdmin) -->
                    <div class="bg-gray-900 border-2 border-gray-700 rounded-xl p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-700 rounded-xl flex items-center justify-center">
                                    <FontAwesomeIcon icon="user-slash" class="text-red-400 text-xl" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-white text-lg">☠️ REINICIAR SISTEMA</h4>
                                    <p class="text-sm text-gray-400">Elimina TODO incluyendo usuarios (excepto SuperAdmin)</p>
                                </div>
                            </div>
                            <button 
                                @click="confirmarReinicio()"
                                :disabled="isDeleting.reinicio"
                                class="px-6 py-3 bg-gray-700 text-red-400 border-2 border-red-500 rounded-xl font-bold hover:bg-red-900 hover:text-white disabled:opacity-50 flex items-center gap-2"
                            >
                                <FontAwesomeIcon v-if="isDeleting.reinicio" icon="spinner" class="animate-spin" />
                                <FontAwesomeIcon v-else icon="power-off" />
                                REINICIAR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import Swal from 'sweetalert2';

defineProps({
    form: { type: Object, required: true },
});

const isDeleting = ref({
    productos: false,
    compras: false,
    ventas: false,
    bancos: false,
    inventario: false,
    clientes: false,
    proveedores: false,
    cfdis: false,
    todo: false,
    reinicio: false,
});

const confirmarEliminacion = async (modulo, descripcion) => {
    const result = await Swal.fire({
        title: `¿Eliminar ${descripcion}?`,
        html: `
            <div class="text-left">
                <p class="text-red-600 font-bold mb-2">⚠️ Esta acción es IRREVERSIBLE</p>
                <p class="text-gray-600 text-sm">Se eliminarán permanentemente ${descripcion} de la base de datos.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: `Escribe "${modulo.toUpperCase()}" para confirmar`,
        inputValidator: (value) => {
            if (value !== modulo.toUpperCase()) {
                return `Debes escribir "${modulo.toUpperCase()}" para confirmar`;
            }
        }
    });

    if (result.isConfirmed) {
        await ejecutarEliminacion(modulo);
    }
};

const confirmarEliminacionTotal = async () => {
    const result = await Swal.fire({
        title: '🔥 ¿ELIMINAR TODO?',
        html: `
            <div class="text-left">
                <p class="text-red-600 font-bold mb-3">⚠️ ADVERTENCIA CRÍTICA</p>
                <p class="text-gray-700 mb-2">Esto eliminará:</p>
                <ul class="text-sm text-gray-600 list-disc pl-5 space-y-1">
                    <li>Productos y categorías</li>
                    <li>Compras y proveedores</li>
                    <li>Ventas y clientes</li>
                    <li>Inventario y series</li>
                    <li>Bancos y movimientos</li>
                    <li>CFDIs y documentos</li>
                </ul>
                <p class="text-gray-500 text-xs mt-3">Los usuarios NO serán eliminados.</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ELIMINAR TODO',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: 'Escribe "ELIMINAR TODO" para confirmar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR TODO') {
                return 'Debes escribir "ELIMINAR TODO" para confirmar';
            }
        }
    });

    if (result.isConfirmed) {
        await ejecutarEliminacion('todo');
    }
};

const confirmarReinicio = async () => {
    const result = await Swal.fire({
        title: '☠️ ¿REINICIAR SISTEMA?',
        html: `
            <div class="text-left">
                <p class="text-red-600 font-bold mb-3">⚠️ PELIGRO EXTREMO</p>
                <p class="text-gray-700 mb-2">Esto eliminará ABSOLUTAMENTE TODO:</p>
                <ul class="text-sm text-gray-600 list-disc pl-5 space-y-1">
                    <li>Todos los datos del sistema</li>
                    <li>Todos los usuarios (excepto SuperAdmin)</li>
                </ul>
                <p class="text-red-600 text-sm mt-3 font-bold">Esta acción dejará el sistema como recién instalado.</p>
            </div>
        `,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#1f2937',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'REINICIAR SISTEMA',
        cancelButtonText: 'Cancelar',
        input: 'text',
        inputPlaceholder: 'Escribe "REINICIAR" para confirmar',
        inputValidator: (value) => {
            if (value !== 'REINICIAR') {
                return 'Debes escribir "REINICIAR" para confirmar';
            }
        }
    });

    if (result.isConfirmed) {
        await ejecutarEliminacion('reinicio');
    }
};

const ejecutarEliminacion = async (modulo) => {
    isDeleting.value[modulo] = true;
    
    try {
        const response = await axios.delete(route('empresa-configuracion.danger-zone.eliminar', { modulo }));
        
        if (response.data.success) {
            Swal.fire({
                icon: 'success',
                title: '✅ Eliminación Completada',
                html: `
                    <p class="text-gray-700">${response.data.message}</p>
                    <p class="text-sm text-gray-500 mt-2">Registros eliminados: ${response.data.count || 0}</p>
                `,
                confirmButtonColor: '#10b981'
            });
        } else {
            throw new Error(response.data.message || 'Error desconocido');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || error.message || 'Error al eliminar',
            confirmButtonColor: '#dc2626'
        });
    } finally {
        isDeleting.value[modulo] = false;
    }
};
</script>
