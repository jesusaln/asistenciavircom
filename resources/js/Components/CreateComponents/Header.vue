<template>
  <div class="header-section mb-8">
    <div class="flex items-start justify-between gap-4">
      <!-- Left: title + description -->
      <div class="min-w-0">
        <div class="flex items-center gap-3 flex-wrap">
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white truncate">
            {{ finalTitle }}
          </h1>

          <!-- Status pill (opcional) -->
          <span
            v-if="status"
            :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 transition-colors',
                     statusClasses.bg, statusClasses.text, statusClasses.ring]"
          >
            {{ statusLabel }}
          </span>
        </div>

        <p v-if="finalDescription" class="text-gray-600 dark:text-slate-400 mt-1">
          {{ finalDescription }}
        </p>

        <!-- Breadcrumbs slot opcional -->
        <div v-if="$slots.breadcrumbs" class="mt-2 text-gray-400">
          <slot name="breadcrumbs" />
        </div>
      </div>

      <!-- Right: actions -->
      <div class="flex flex-wrap gap-3 shrink-0">
        <!-- Slot para acciones externas (guardar, aprobar, etc.) -->
        <slot name="actions" />

        <!-- Botón Vista Previa -->
        <button
          v-if="showPreview"
          @click="$emit('preview')"
          :disabled="!canPreview"
          class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
          </svg>
          {{ previewLabel }}
        </button>

        <!-- Botón Volver -->
        <Link
          v-if="showBack"
          :href="backUrl"
          class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-900 border border-gray-300 dark:border-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 hover:border-gray-400 dark:hover:border-slate-700 transition-all duration-200 shadow-sm"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          {{ backLabel }}
        </Link>
      </div>
    </div>

    <!-- Atajos/ayuda (opcional) -->
    <div v-if="showShortcuts && $slots.shortcuts" class="mt-4">
      <slot name="shortcuts" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

type Kind =
  | 'pedido'
  | 'venta'
  | 'compraProveedor'
  | 'ordenCompra'
  | 'renta'
  | 'cotizacion'

type Mode = 'create' | 'edit' | 'show'

const props = defineProps({
  kind: { type: String as () => Kind, default: undefined },
  mode: { type: String as () => Mode, default: 'create' },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  backUrl: { type: String, required: true },
  backLabel: { type: String, default: 'Volver' },
  showBack: { type: Boolean, default: true },
  showPreview: { type: Boolean, default: true },
  canPreview: { type: Boolean, default: true },
  previewLabel: { type: String, default: 'Vista Previa' },
  status: { type: String, default: '' },
  showShortcuts: { type: Boolean, default: false },
})

defineEmits<{
  (e: 'preview'): void
  (e: 'templates'): void
  (e: 'close-shortcuts'): void
}>()

const TEXTS: Record<Kind, Record<Mode, { title: string; description: string }>> = {
  pedido: {
    create: { title: 'Nuevo pedido', description: 'Crea un pedido para tus clientes' },
    edit:   { title: 'Editar pedido', description: 'Actualiza los datos del pedido' },
    show:   { title: 'Detalle del pedido', description: 'Revisa la información del pedido' },
  },
  venta: {
    create: { title: 'Nueva venta', description: 'Registra una venta' },
    edit:   { title: 'Editar venta', description: 'Actualiza los datos de la venta' },
    show:   { title: 'Detalle de la venta', description: 'Revisa la información de la venta' },
  },
  compraProveedor: {
    create: { title: 'Nueva compra', description: 'Registra una compra a proveedor' },
    edit:   { title: 'Editar compra', description: 'Actualiza los datos de la compra' },
    show:   { title: 'Detalle de la compra', description: 'Revisa la información de la compra' },
  },
  ordenCompra: {
    create: { title: 'Nueva orden de compra', description: 'Genera una orden de compra para proveedor' },
    edit:   { title: 'Editar orden de compra', description: 'Actualiza la orden de compra' },
    show:   { title: 'Detalle de la orden de compra', description: 'Revisa la información de la orden' },
  },
  renta: {
    create: { title: 'Nueva renta', description: 'Crea una renta de producto o servicio' },
    edit:   { title: 'Editar renta', description: 'Actualiza los datos de la renta' },
    show:   { title: 'Detalle de la renta', description: 'Revisa la información de la renta' },
  },
  cotizacion: {
    create: { title: 'Nueva cotización', description: 'Crea una cotización para tu cliente' },
    edit:   { title: 'Editar cotización', description: 'Modifica los detalles de la cotización' },
    show:   { title: 'Detalle de la cotización', description: 'Revisa la información de la cotización' },
  },
}

const inferredKind = computed<Kind | undefined>(() => {
  const routeFn = typeof globalThis !== 'undefined' ? (globalThis as any).route : undefined
  const current = typeof routeFn === 'function' ? (routeFn().current?.() || routeFn().current || '') : ''
  if (typeof current === 'string' && current) {
    if (current.startsWith('cotizaciones.')) return 'cotizacion'
    if (current.startsWith('pedidos.'))      return 'pedido'
    if (current.startsWith('ventas.'))       return 'venta'
    if (current.startsWith('compras.'))      return 'compraProveedor'
    if (current.startsWith('ordenes.'))      return 'ordenCompra'
    if (current.startsWith('rentas.'))       return 'renta'
  }
  const p = (typeof window !== 'undefined' ? window.location.pathname : '').toLowerCase()
  if (p.includes('/cotiz'))  return 'cotizacion'
  if (p.includes('/pedido')) return 'pedido'
  if (p.includes('/venta'))  return 'venta'
  if (p.includes('/compra')) return 'compraProveedor'
  if (p.includes('/orden'))  return 'ordenCompra'
  if (p.includes('/renta'))  return 'renta'
  return undefined
})

const resolvedKind = computed<Kind>(() => props.kind ?? inferredKind.value ?? 'venta')

const finalTitle = computed(() =>
  props.title || TEXTS[resolvedKind.value][props.mode].title
)

const finalDescription = computed(() =>
  props.description || TEXTS[resolvedKind.value][props.mode].description
)

const STATUS_STYLES: Record<string, { bg: string; text: string; ring: string; label?: string }> = {
  borrador:       { bg: 'bg-gray-50 dark:bg-slate-800',    text: 'text-gray-700 dark:text-slate-300',    ring: 'ring-gray-200 dark:ring-slate-700',    label: 'Borrador' },
  pendiente:      { bg: 'bg-amber-50 dark:bg-amber-900/20',   text: 'text-amber-700 dark:text-amber-400',   ring: 'ring-amber-200 dark:ring-amber-800/30',   label: 'Pendiente' },
  aprobada:       { bg: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-400', ring: 'ring-emerald-200 dark:ring-emerald-800/30', label: 'Aprobada' },
  confirmada:     { bg: 'bg-emerald-50 dark:bg-emerald-900/20', text: 'text-emerald-700 dark:text-emerald-400', ring: 'ring-emerald-200 dark:ring-emerald-800/30', label: 'Confirmada' },
  en_preparacion: { bg: 'bg-sky-50 dark:bg-sky-900/20',     text: 'text-sky-700 dark:text-sky-400',     ring: 'ring-sky-200 dark:ring-sky-800/30',     label: 'En preparación' },
  listo_entrega:  { bg: 'bg-indigo-50 dark:bg-indigo-900/20',  text: 'text-indigo-700 dark:text-indigo-400',  ring: 'ring-indigo-200 dark:ring-indigo-800/30',  label: 'Listo para entrega' },
  entregado:      { bg: 'bg-blue-50 dark:bg-blue-900/20',    text: 'text-blue-700 dark:text-blue-400',    ring: 'ring-blue-200 dark:ring-blue-800/30',    label: 'Entregado' },
  pagado:         { bg: 'bg-green-50 dark:bg-green-900/20',   text: 'text-green-700 dark:text-green-400',   ring: 'ring-green-200 dark:ring-green-800/30',   label: 'Pagado' },
  cancelado:      { bg: 'bg-rose-50 dark:bg-rose-900/20',    text: 'text-rose-700 dark:text-rose-400',    ring: 'ring-rose-200 dark:ring-rose-800/30',    label: 'Cancelado' },
  pendiente_pago: { bg: 'bg-amber-50 dark:bg-amber-900/20',   text: 'text-amber-700 dark:text-amber-400',   ring: 'ring-amber-200 dark:ring-amber-800/30',   label: 'Pendiente de pago' },
}

const statusClasses = computed(() => {
  const key = (props.status || '').toLowerCase().replace(/\s+/g, '_')
  return STATUS_STYLES[key] ?? { bg: 'bg-gray-50 dark:bg-slate-800', text: 'text-gray-700 dark:text-slate-300', ring: 'ring-gray-200 dark:ring-slate-700' }
})

const statusLabel = computed(() => {
  const key = (props.status || '').toLowerCase().replace(/\s+/g, '_')
  return STATUS_STYLES[key]?.label ?? props.status
})
</script>
