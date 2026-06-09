<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

defineOptions({ layout: AppLayout })

const props = defineProps({
  empleado: { type: Object, required: true },
  nominasRecientes: { type: Array, default: () => [] },
  resumenAnual: { type: Object, default: () => ({}) },
})

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })

const page = usePage()
onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
})

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  return isNaN(value) ? '$0.00' : `$${value.toLocaleString('es-MX', { minimumFractionDigits: 2 })}`
}

const formatearFecha = (date) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })
}

const editarEmpleado = () => router.visit(`/empleados/${props.empleado.id}/edit`)
const generarNomina = () => router.visit(`/nominas/create?empleado_id=${props.empleado.id}`)
const volver = () => router.visit('/empleados')

const imprimirContrato = () => {
  window.open(`/empleados/${props.empleado.id}/imprimir-contrato`, '_blank')
}

const descargarContrato = () => {
  window.open(`/empleados/${props.empleado.id}/descargar-contrato`, '_blank')
}
</script>

<template>
  <Head :title="`Empleado - ${empleado.user?.name}`" />

  <div class="min-h-screen bg-white">
    <div class="w-full px-6 py-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <button @click="volver" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ empleado.user?.name || 'Empleado' }}</h1>
            <p class="text-sm text-gray-500">{{ empleado.numero_empleado }} • {{ empleado.puesto || 'Sin puesto' }}</p>
          </div>
        </div>
        <div class="flex space-x-3">
          <button
            v-if="empleado.puede_imprimir_contrato"
            @click="imprimirContrato"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir Contrato
          </button>
          
          <button @click="generarNomina" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            Generar Nómina
          </button>
          <button @click="editarEmpleado" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
            Editar
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Información Principal -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Datos Personales -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Personal</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Email</p>
                <p class="font-medium">{{ empleado.user?.email || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Teléfono</p>
                <p class="font-medium">{{ empleado.user?.telefono || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Fecha de Nacimiento</p>
                <p class="font-medium">{{ formatearFecha(empleado.fecha_nacimiento) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">CURP</p>
                <p class="font-medium font-mono text-sm">{{ empleado.curp || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">RFC</p>
                <p class="font-medium font-mono text-sm">{{ empleado.rfc || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">NSS</p>
                <p class="font-medium font-mono text-sm">{{ empleado.nss || '—' }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-sm text-gray-500">Dirección</p>
                <p class="font-medium">{{ empleado.direccion || '—' }}</p>
              </div>
            </div>
          </div>

          <!-- Datos Laborales -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Laboral</h2>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500">Puesto</p>
                <p class="font-medium">{{ empleado.puesto || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Departamento</p>
                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ empleado.departamento || 'Sin departamento' }}
                </span>
              </div>
              <div>
                <p class="text-sm text-gray-500">Fecha de Contratación</p>
                <p class="font-medium">{{ formatearFecha(empleado.fecha_contratacion) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Antigüedad</p>
                <p class="font-medium">{{ empleado.antiguedad_formateada || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Tipo de Contrato</p>
                <p class="font-medium">{{ empleado.tipo_contrato_formateado || empleado.tipo_contrato }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Frecuencia de Pago</p>
                <p class="font-medium text-purple-600">{{ empleado.frecuencia_pago_formateada || empleado.frecuencia_pago }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Salario Base</p>
                <p class="font-medium text-emerald-600 text-lg">{{ formatearMoneda(empleado.salario_base) }}</p>
              </div>
            </div>
          </div>

          <!-- Datos Bancarios -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Bancaria</h2>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-500">Banco</p>
                <p class="font-medium">{{ empleado.banco || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Número de Cuenta</p>
                <p class="font-medium font-mono text-sm">{{ empleado.numero_cuenta || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">CLABE</p>
                <p class="font-medium font-mono text-sm">{{ empleado.clabe_interbancaria || '—' }}</p>
              </div>
            </div>
          </div>

          <!-- Contacto de Emergencia -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Contacto de Emergencia</h2>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <p class="text-sm text-gray-500">Nombre</p>
                <p class="font-medium">{{ empleado.contacto_emergencia_nombre || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Teléfono</p>
                <p class="font-medium">{{ empleado.contacto_emergencia_telefono || '—' }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-500">Parentesco</p>
                <p class="font-medium">{{ empleado.contacto_emergencia_parentesco || '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Resumen Anual -->
          <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-6 text-white">
            <h3 class="font-semibold mb-4">Resumen {{ new Date().getFullYear() }}</h3>
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-emerald-100">Percepciones</span>
                <span class="font-bold">{{ formatearMoneda(resumenAnual.total_percepciones) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-emerald-100">Deducciones</span>
                <span class="font-bold">{{ formatearMoneda(resumenAnual.total_deducciones) }}</span>
              </div>
              <div class="border-t border-emerald-400 pt-3 flex justify-between">
                <span class="text-emerald-100">Neto Pagado</span>
                <span class="font-bold text-lg">{{ formatearMoneda(resumenAnual.total_neto) }}</span>
              </div>
              <div class="text-sm text-emerald-200">
                {{ resumenAnual.nominas_pagadas || 0 }} nóminas pagadas
              </div>
            </div>
          </div>

          <!-- Nóminas Recientes -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-gray-900">Nóminas Recientes</h3>
              <Link :href="`/nominas?empleado_id=${empleado.id}`" class="text-sm text-emerald-600 hover:underline">
                Ver todas
              </Link>
            </div>
            <div v-if="nominasRecientes.length" class="space-y-3">
              <div v-for="nomina in nominasRecientes.slice(0, 5)" :key="nomina.id" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ nomina.periodo_formateado }}</p>
                  <p class="text-xs text-gray-500">{{ nomina.tipo_periodo_formateado }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium">{{ formatearMoneda(nomina.total_neto) }}</p>
                  <span :class="nomina.estado_info?.color" class="text-xs px-2 py-0.5 rounded-full">
                    {{ nomina.estado_info?.label }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              <p class="text-sm">Sin nóminas registradas</p>
            </div>
          </div>

          <!-- Expediente Digital -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
              <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Expediente Digital
            </h3>
            
            <div class="space-y-4">
              <div class="p-4 bg-white rounded-lg border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-semibold text-gray-500 uppercase">Contrato Laboral</span>
                  <span v-if="empleado.contrato_adjunto" class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] rounded-full font-bold uppercase">Adjunto</span>
                  <span v-else class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] rounded-full font-bold uppercase">Pendiente</span>
                </div>
                
                <div v-if="empleado.contrato_adjunto" class="flex items-center justify-between">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    <span>Contrato_Firmado.pdf</span>
                  </div>
                  <button @click="descargarContrato" class="text-xs text-blue-600 hover:underline font-medium">Ver / Descargar</button>
                </div>
                <div v-else class="text-sm text-gray-500 italic">
                  No se ha adjuntado el contrato físico firmado.
                </div>
              </div>
            </div>
          </div>

          <!-- Observaciones -->
          <div v-if="empleado.observaciones" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-semibold text-gray-900 mb-2">Observaciones</h3>
            <p class="text-sm text-gray-600">{{ empleado.observaciones }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
