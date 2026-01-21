<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    cita: Object,
    timeline: Array,
    cargos: Object, // Added cargos prop
});

const cssVars = computed(() => ({
    '--color-primary': props.empresa?.color_principal || '#FF6B35',
}));

// Estado actual formateado
const estadoInfo = computed(() => {
    const estados = {
        'pendiente_asignacion': { label: 'Pendiente de Asignación', color: 'yellow', icon: '⏳' },
        'pendiente': { label: 'Pendiente', color: 'yellow', icon: '⏳' },
        'programado': { label: 'Programada', color: 'blue', icon: '📅' },
        'en_proceso': { label: 'En Proceso', color: 'indigo', icon: '🔧' },
        'completado': { label: 'Completado', color: 'green', icon: '✅' },
        'cancelado': { label: 'Cancelada', color: 'red', icon: '❌' },
    };
    return estados[props.cita?.estado] || { label: 'Desconocido', color: 'gray', icon: '❓' };
});

// Formatear días preferidos
const diasFormateados = computed(() => {
    return props.cita?.dias_preferidos?.map(fecha => {
        const d = new Date(fecha + 'T12:00:00');
        return d.toLocaleDateString('es-MX', { 
            weekday: 'short', 
            day: 'numeric', 
            month: 'short' 
        });
    }) || [];
});
</script>

<template>
    <Head :title="`Seguimiento - ${cita?.folio || 'Cita'}`" />
    
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100" :style="cssVars">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="w-full px-4 py-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[var(--color-primary)] flex items-center justify-center text-white font-bold">
                        {{ empresa?.nombre?.charAt(0) || 'C' }}
                    </div>
                    <div>
                        <h1 class="font-bold text-gray-900">{{ empresa?.nombre || 'Asistencia Vircom' }}</h1>
                        <p class="text-xs text-gray-500">Seguimiento de tu cita</p>
                    </div>
                </div>
            </div>
        </header>

        <main class="w-full px-4 py-6 space-y-6">
            <!-- Estado Principal -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div 
                    :class="`bg-${estadoInfo.color}-500 p-6 text-center text-white`"
                    :style="{ backgroundColor: estadoInfo.color === 'yellow' ? '#EAB308' : 
                              estadoInfo.color === 'blue' ? '#3B82F6' : 
                              estadoInfo.color === 'green' ? '#22C55E' : 
                              estadoInfo.color === 'red' ? '#EF4444' : 
                              estadoInfo.color === 'indigo' ? '#6366F1' : '#6B7280' }"
                >
                    <span class="text-5xl mb-3 block">{{ estadoInfo.icon }}</span>
                    <h2 class="text-xl font-bold mb-1">{{ estadoInfo.label }}</h2>
                    <p class="text-sm opacity-90">Folio: {{ cita?.folio }}</p>
                </div>
                
                <!-- Info de la cita -->
                <div class="p-6 space-y-4">
                    <!-- Si está confirmada -->
                    <div v-if="cita?.esta_confirmada" class="p-4 bg-green-50 border border-green-200 rounded-xl">
                        <p class="font-bold text-green-800 mb-2">📅 Cita Confirmada</p>
                        <div class="text-green-700 space-y-1">
                            <p class="text-lg font-bold">
                                {{ new Date(cita.fecha_confirmada + 'T12:00:00').toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' }) }}
                            </p>
                            <p class="text-lg">⏰ {{ cita.hora_confirmada_rango || cita.hora_confirmada }}</p>
                        </div>
                    </div>
                    
                    <!-- Si NO está confirmada -->
                    <div v-else class="p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
                        <p class="font-bold text-yellow-800 mb-2">⏳ Esperando Confirmación</p>
                        <p class="text-sm text-yellow-700">
                            Estamos revisando tu solicitud. Te confirmaremos la fecha y hora exacta por WhatsApp.
                        </p>
                        <div class="mt-3 text-sm text-yellow-700">
                            <p class="font-medium">Días solicitados:</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <span 
                                    v-for="dia in diasFormateados" 
                                    :key="dia"
                                    class="px-2 py-0.5 bg-yellow-200 rounded text-xs capitalize"
                                >
                                    {{ dia }}
                                </span>
                            </div>
                            <p class="mt-2" v-if="cita?.horario_info">
                                Horario: {{ cita.horario_info.emoji }} {{ cita.horario_info.nombre }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Técnico asignado -->
                    <div v-if="cita?.tecnico" class="flex items-center gap-3 p-4 bg-white rounded-xl">
                        <div class="w-12 h-12 bg-[var(--color-primary)] rounded-full flex items-center justify-center text-white font-bold text-lg">
                            {{ cita.tecnico.nombre?.charAt(0) || '?' }}
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Técnico asignado</p>
                            <p class="font-bold text-gray-900">{{ cita.tecnico.nombre }}</p>
                            <p v-if="cita.tecnico.telefono" class="text-sm text-gray-500">📱 {{ cita.tecnico.telefono }}</p>
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="p-4 bg-white rounded-xl">
                        <p class="text-sm text-gray-500 mb-1">📍 Dirección del servicio</p>
                        <p class="font-medium text-gray-900">{{ cita?.direccion_completa }}</p>
                        <p v-if="cita?.direccion_referencias" class="text-sm text-gray-500 italic mt-1">
                            "{{ cita.direccion_referencias }}"
                        </p>
                    </div>
                    
                    <!-- Servicio -->
                    <div class="p-4 bg-white rounded-xl">
                        <p class="text-sm text-gray-500 mb-1">🔧 Servicio solicitado</p>
                        <p class="font-medium text-gray-900 capitalize">{{ cita?.tipo_servicio }} - {{ cita?.tipo_equipo }}</p>
                        <p v-if="cita?.nombre_tienda" class="text-sm text-gray-500">
                            🏪 Comprado en {{ cita.nombre_tienda }}
                        </p>
                        <p v-if="cita?.descripcion" class="text-sm text-gray-600 mt-2">
                            {{ cita.descripcion }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Estado de Cuenta (Cargos) -->
            <div v-if="cargos && cargos.items.length > 0" class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">💰 Estado de Cuenta</h3>
                    <span 
                        :class="[
                            'px-3 py-1 rounded-full text-xs font-bold uppercase',
                            cargos.estado_pago === 'pagado' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                        ]"
                    >
                        {{ cargos.estado_pago === 'pagado' ? 'PAGADO' : 'PENDIENTE DE PAGO' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-white text-gray-600 font-medium">
                            <tr>
                                <th class="px-3 py-2 text-left">Concepto</th>
                                <th class="px-3 py-2 text-center">Cant.</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(item, index) in cargos.items" :key="index">
                                <td class="px-3 py-2 text-gray-900">{{ item.nombre }}</td>
                                <td class="px-3 py-2 text-center text-gray-500">{{ item.cantidad }}</td>
                                <td class="px-3 py-2 text-right text-gray-900 font-medium">
                                    ${{ Number(item.subtotal).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-t border-gray-200">
                            <tr>
                                <td colspan="2" class="px-3 py-3 text-right font-bold text-gray-900">Total a Pagar:</td>
                                <td class="px-3 py-3 text-right font-bold text-blue-600 text-lg">
                                    ${{ Number(cargos.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-if="cargos.estado_pago !== 'pagado'" class="mt-4">
                    <button disabled class="w-full py-3 bg-gray-200 text-gray-500 font-bold rounded-xl cursor-not-allowed">
                        💳 Pagar Ahora (Próximamente)
                    </button>
                    <p class="text-xs text-center text-gray-500 mt-2">
                        Fecha límite de pago: {{ cargos.fecha_vencimiento || 'Inmediato' }}
                    </p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="font-bold text-gray-900 mb-4">📋 Estado de tu solicitud</h3>
                
                <div class="relative">
                    <!-- Línea vertical -->
                    <div class="absolute left-4 top-6 bottom-6 w-0.5 bg-gray-200"></div>
                    
                    <div class="space-y-6">
                        <div 
                            v-for="(step, index) in timeline" 
                            :key="step.estado"
                            class="relative flex items-start gap-4"
                        >
                            <!-- Icono -->
                            <div 
                                :class="[
                                    'relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-lg',
                                    step.completado ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'
                                ]"
                            >
                                {{ step.completado ? '✓' : step.icono }}
                            </div>
                            
                            <!-- Contenido -->
                            <div class="flex-1 pb-4">
                                <p :class="['font-medium', step.completado ? 'text-gray-900' : 'text-gray-400']">
                                    {{ step.nombre }}
                                </p>
                                <p v-if="step.fecha" class="text-sm text-gray-500">
                                    {{ step.fecha }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="space-y-3">
                <a 
                    v-if="empresa?.whatsapp"
                    :href="`https://wa.me/${empresa.whatsapp.replace(/\D/g, '')}?text=Hola, tengo una pregunta sobre mi cita ${cita?.folio}`"
                    target="_blank"
                    class="flex items-center justify-center gap-2 w-full py-3 bg-green-500 text-white font-bold rounded-xl hover:bg-green-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    </svg>
                    ¿Tienes dudas? Escríbenos
                </a>
                
                <a 
                    v-if="empresa?.telefono"
                    :href="`tel:${empresa.telefono}`"
                    class="flex items-center justify-center gap-2 w-full py-3 border-2 border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-white transition-colors"
                >
                    📞 Llamar: {{ empresa.telefono }}
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center text-gray-400 text-sm py-4">
                <p>{{ empresa?.nombre || 'Asistencia Vircom' }}</p>
                <p class="text-xs mt-1">Solicitud recibida: {{ cita?.created_at }}</p>
            </div>
        </main>
    </div>
</template>
