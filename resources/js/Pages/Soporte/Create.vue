<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';

import SimpleCategoryForm from '@/Components/Soporte/SimpleCategoryForm.vue';

const props = defineProps({
    categorias: Array,
    usuarios: Array,
});

const form = useForm({
    titulo: '',
    descripcion: '',
    prioridad: 'media',
    categoria_id: '',
    cliente_id: '',
    asignado_id: '',
    producto_id: '',
    origen: 'telefono',
    tipo_servicio: 'garantia', // <<< NUEVO
    telefono_contacto: '',
    email_contacto: '',
    nombre_contacto: '',
    folio_manual: '',
    poliza_id: null,
});

// Popup de búsqueda de cliente
const terminoBusqueda = ref('');
const clienteEncontrado = ref(null);
const resultadosBusqueda = ref([]); // Para múltiples resultados
const buscando = ref(false);
const ticketsCliente = ref([]);
const polizaActiva = ref(null);
const showCategoryModal = ref(false);

const listaCategorias = ref([...props.categorias]);

const agregarCategoriaNueva = (nuevaCategoria) => {
    listaCategorias.value.push(nuevaCategoria);
    form.categoria_id = nuevaCategoria.id; // Seleccionar automáticamente
};

const buscarCliente = async () => {
    if (terminoBusqueda.value.length < 3) return;
    
    buscando.value = true;
    resultadosBusqueda.value = [];
    clienteEncontrado.value = null; // Resetear selección al buscar
    
    try {
        const response = await fetch(route('soporte.buscar-cliente') + `?query=${encodeURIComponent(terminoBusqueda.value)}`);
        const data = await response.json();
        
        if (data.found) {
            // Resultado único exacto
            seleccionarClienteEncontrado(data);
        } else if (data.results && data.results.length > 0) {
            // Múltiples resultados
            resultadosBusqueda.value = data.results;
        } else {
            // Nada encontrado
            resultadosBusqueda.value = [];
            // Opcional: mostrar mensaje "no encontrado"
        }
    } catch (error) {
        console.error('Error buscando cliente:', error);
    } finally {
        buscando.value = false;
    }
};

const seleccionarDeLista = async (cliente) => {
    buscando.value = true;
    try {
        const response = await fetch(route('soporte.buscar-cliente') + `?id=${cliente.id}`);
        const data = await response.json();
        if (data.found) {
            seleccionarClienteEncontrado(data);
            resultadosBusqueda.value = []; // Limpiar lista
        }
    } catch (error) {
        console.error('Error seleccionando cliente:', error);
    } finally {
        buscando.value = false;
    }
};

const seleccionarClienteEncontrado = (data) => {
    clienteEncontrado.value = data.cliente;
    ticketsCliente.value = data.tickets_recientes || [];
    
    form.cliente_id = data.cliente.id;
    // Preferir celular si existe, sino telefono
    form.telefono_contacto = data.cliente.celular || data.cliente.telefono; 
    form.email_contacto = data.cliente.email;
    form.nombre_contacto = data.cliente.nombre; // Ahora viene nombre_razon_social del backend
    polizaActiva.value = data.poliza_activa;
    form.poliza_id = data.poliza_activa ? data.poliza_activa.id : null;
};

const limpiarCliente = () => {
    clienteEncontrado.value = null;
    resultadosBusqueda.value = [];
    ticketsCliente.value = [];
    polizaActiva.value = null;
    form.cliente_id = '';
    form.poliza_id = null;
    form.producto_id = '';
    terminoBusqueda.value = '';
};

const estaEquipadoCubierto = computed(() => {
    if (!polizaActiva.value || !form.producto_id) return false;
    return polizaActiva.value.equipos.some(e => e.id === form.producto_id);
});

watch(() => form.producto_id, (newVal) => {
    if (newVal && estaEquipadoCubierto.value) {
        form.tipo_servicio = 'garantia';
    }
});

const submit = () => {
    form.post(route('soporte.store'));
};

const prioridades = [
    { value: 'baja', label: '🟢 Baja', desc: 'Puede esperar' },
    { value: 'media', label: '🟡 Media', desc: 'Normal' },
    { value: 'alta', label: '🟠 Alta', desc: 'Importante' },
    { value: 'urgente', label: '🔴 Urgente', desc: 'Crítico' },
];

const origenes = [
    { value: 'telefono', label: '📞 Teléfono' },
    { value: 'email', label: '📧 Email' },
    { value: 'whatsapp', label: '💬 WhatsApp' },
    { value: 'web', label: '🌐 Web' },
    { value: 'presencial', label: '🏢 Presencial' },
];
</script>

<template>
    <AppLayout title="Nuevo Ticket">
        <Head title="Nuevo Ticket de Soporte" />

        <div class="py-6">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('soporte.index')" class="text-orange-600 hover:text-orange-800 text-sm mb-2 inline-block">
                        ← Volver a tickets
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nuevo Ticket de Soporte</h1>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Formulario principal -->
                    <div class="lg:col-span-2">
                        <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 space-y-6">
                            <!-- Búsqueda de cliente -->
                            <div class="bg-gradient-to-r from-orange-50 to-yellow-50 rounded-xl p-4 border border-orange-200">
                                <label class="block text-sm font-semibold text-orange-800 mb-2">
                                    🔍 Buscar Cliente
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        v-model="terminoBusqueda"
                                        type="text"
                                        placeholder="Nombre, Empresa o Teléfono..."
                                        class="flex-1 px-4 py-3 border-2 border-orange-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-lg"
                                        @keyup.enter="buscarCliente"
                                    />
                                    <button 
                                        type="button"
                                        @click="buscarCliente"
                                        :disabled="buscando"
                                        class="px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 font-semibold"
                                    >
                                        {{ buscando ? '...' : 'Buscar' }}
                                    </button>
                                </div>

                                <!-- Lista de Resultados (Si hay múltiples) -->
                                <div v-if="resultadosBusqueda.length > 0 && !clienteEncontrado" class="mt-4 bg-white dark:bg-slate-900 rounded-lg border border-orange-200 overflow-hidden shadow-sm">
                                    <div class="px-4 py-2 bg-orange-100 text-orange-800 text-xs font-bold uppercase">
                                        Resultados encontrados ({{ resultadosBusqueda.length }})
                                    </div>
                                    <ul class="divide-y divide-gray-100">
                                        <li v-for="res in resultadosBusqueda" :key="res.id" 
                                            @click="seleccionarDeLista(res)"
                                            class="px-4 py-3 hover:bg-orange-50 cursor-pointer flex justify-between items-center transition-colors"
                                        >
                                            <div>
                                                <div class="font-bold text-gray-800 dark:text-gray-100">{{ res.nombre }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ res.email }} • {{ res.telefono }}</div>
                                            </div>
                                            <span class="text-orange-500">Seleccionar →</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Cliente seleccionado -->
                                <div v-if="clienteEncontrado" class="mt-4 bg-white dark:bg-slate-900 rounded-lg p-4 border border-green-300">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ clienteEncontrado.nombre }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ clienteEncontrado.email }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-300">{{ clienteEncontrado.telefono || clienteEncontrado.celular }}</div>
                                            <!-- Info de Póliza -->
                                            <div v-if="polizaActiva" class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                <font-awesome-icon icon="shield-halved" class="mr-1" />
                                                Póliza Activa: {{ polizaActiva.nombre }} (Folio: {{ polizaActiva.folio }})
                                            </div>
                                            <div v-else class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-slate-800">
                                                Sin Póliza Activa
                                            </div>
                                        </div>
                                        <button type="button" @click="limpiarCliente" class="text-gray-400 hover:text-gray-600 dark:text-gray-300">✕</button>
                                    </div>
                                    
                                    <!-- Tickets recientes del cliente -->
                                    <div v-if="ticketsCliente.length > 0" class="mt-3 pt-3 border-t">
                                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">Tickets recientes:</div>
                                        <div class="max-h-40 overflow-y-auto pr-1">
                                            <div v-for="t in ticketsCliente" :key="t.id" class="text-xs py-1 flex justify-between items-center hover:bg-white dark:bg-slate-900 rounded px-1">
                                                <Link :href="route('soporte.show', t.id)" target="_blank" class="font-mono text-orange-600 hover:text-orange-800 hover:underline">
                                                    {{ t.numero }}
                                                </Link>
                                                <span class="text-gray-600 dark:text-gray-300 truncate max-w-[150px]" :title="t.titulo">{{ t.titulo }}</span>
                                                <span :class="t.estado === 'abierto' ? 'text-blue-600' : t.estado === 'resuelto' ? 'text-green-600' : 'text-gray-500 dark:text-gray-400'">
                                                    {{ t.estado }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Título -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Título del problema *</label>
                                <input
                                    v-model="form.titulo"
                                    type="text"
                                    required
                                    placeholder="Describe brevemente el problema..."
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                                />
                                <p v-if="form.errors.titulo" class="text-red-500 text-sm mt-1">{{ form.errors.titulo }}</p>
                            </div>

                            <!-- Selección de Equipo (Producto) -->
                            <div v-if="clienteEncontrado">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Equipo / Producto Relacionado</label>
                                <div class="space-y-2">
                                    <select 
                                        v-model="form.producto_id" 
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                                        :class="{'border-green-500 bg-green-50': estaEquipadoCubierto}"
                                    >
                                        <option value="">Seleccionar equipo (Opcional)</option>
                                        <option v-for="equipo in polizaActiva?.equipos" :key="equipo.id" :value="equipo.id">
                                            🛡️ {{ equipo.nombre }} (S/N: {{ equipo.serie }}) - CUBIERTO POR PÓLIZA
                                        </option>
                                        <!-- En un sistema real aquí cargaríamos todos los equipos del cliente, pero por ahora mostramos los de póliza para demostrar -->
                                        <option v-if="polizaActiva?.equipos.length === 0" disabled>No hay equipos listados en la póliza</option>
                                    </select>
                                    
                                    <div v-if="estaEquipadoCubierto" class="flex items-center gap-2 text-xs font-bold text-green-700 bg-green-100 p-2 rounded-lg border border-green-200">
                                        <font-awesome-icon icon="check-circle" />
                                        Equipo identificado dentro de la Póliza de Servicio. Aplicando cobertura automática.
                                    </div>
                                </div>
                            </div>



                            <!-- Descripción -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción detallada *</label>
                                <textarea
                                    v-model="form.descripcion"
                                    rows="5"
                                    required
                                    placeholder="Describe el problema con detalle..."
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                                ></textarea>
                                <p v-if="form.errors.descripcion" class="text-red-500 text-sm mt-1">{{ form.errors.descripcion }}</p>
                            </div>

                            <!-- Tipo de Servicio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Servicio</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer border p-3 rounded-lg w-full hover:bg-orange-50" :class="{'ring-2 ring-orange-500 bg-orange-50': form.tipo_servicio === 'garantia'}">
                                        <input type="radio" v-model="form.tipo_servicio" value="garantia" class="text-orange-500 focus:ring-orange-500">
                                        <div>
                                            <span class="block font-bold text-gray-800 dark:text-gray-100">🛡️ Garantía / Póliza</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Sin costo para el cliente</span>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer border p-3 rounded-lg w-full hover:bg-orange-50" :class="{'ring-2 ring-orange-500 bg-orange-50': form.tipo_servicio === 'costo'}">
                                        <input type="radio" v-model="form.tipo_servicio" value="costo" class="text-orange-500 focus:ring-orange-500">
                                        <div>
                                            <span class="block font-bold text-gray-800 dark:text-gray-100">💰 Con Costo</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Genera venta y facturación</span>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="form.errors.tipo_servicio" class="text-red-500 text-sm mt-1">{{ form.errors.tipo_servicio }}</p>
                            </div>

                            <!-- Folio Manual -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Folio Físico / Externo (Opcional)</label>
                                <input
                                    v-model="form.folio_manual"
                                    type="text"
                                    placeholder="Ej. Nota 1234"
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500"
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Si tienes un folio impreso o de otro sistema.</p>
                            </div>

                            <!-- Prioridad y Categoría -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                                    <select v-model="form.prioridad" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                                        <option v-for="p in prioridades" :key="p.value" :value="p.value">
                                            {{ p.label }} - {{ p.desc }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                    <div class="flex gap-2">
                                        <select v-model="form.categoria_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                                            <option value="">Sin categoría</option>
                                            <option v-for="c in listaCategorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                        </select>
                                        <button 
                                            type="button" 
                                            @click="showCategoryModal = true"
                                            class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 text-gray-600 dark:text-gray-300"
                                            title="Nueva Categoría"
                                        >
                                            <font-awesome-icon icon="plus" />
                                        </button>
                                    </div>
                                </div>
                            </div>


                            <!-- Origen y Asignado -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Origen del ticket</label>
                                    <select v-model="form.origen" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                                        <option v-for="o in origenes" :key="o.value" :value="o.value">{{ o.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a</label>
                                    <select v-model="form.asignado_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-orange-500">
                                        <option value="">Sin asignar</option>
                                        <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Contacto (si no hay cliente) -->
                            <div v-if="!clienteEncontrado" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de contacto</label>
                                    <input v-model="form.nombre_contacto" type="text" class="w-full px-4 py-2 border rounded-lg" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email de contacto</label>
                                    <input v-model="form.email_contacto" type="email" class="w-full px-4 py-2 border rounded-lg" />
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <Link :href="route('soporte.index')" class="px-6 py-2 border rounded-lg text-gray-700 hover:bg-white dark:bg-slate-900">
                                    Cancelar
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50 font-semibold"
                                >
                                    {{ form.processing ? 'Creando...' : 'Crear Ticket' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sidebar de ayuda -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-4">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">💡 Tips</h3>
                            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                                <li>• Usa el buscador de teléfono para identificar al cliente rápidamente</li>
                                <li>• Selecciona la prioridad correcta para un mejor SLA</li>
                                <li>• Incluye todos los detalles relevantes en la descripción</li>
                            </ul>
                        </div>

                        <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
                            <h3 class="font-semibold text-yellow-800 mb-2">⚡ SLA por Categoría</h3>
                            <div v-for="c in categorias" :key="c.id" class="text-sm flex justify-between py-1">
                                <span>{{ c.nombre }}</span>
                                <span class="text-yellow-700 font-mono">{{ c.sla_horas }}h</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Modal de Gestión de Categorías Simplificado -->
    <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
        <SimpleCategoryForm 
            @close="showCategoryModal = false" 
            @created="agregarCategoriaNueva"
        />
    </Modal>
</template>
