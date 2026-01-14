<template>
  <Head title="Registrar Actividad" />
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Registrar Actividad</h1>
            <p class="mt-2 text-sm text-gray-600">Captura detallada para tu bitácora. Los campos marcados con * son obligatorios.</p>
          </div>
          <Link :href="route('bitacora.index')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-colors shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver
          </Link>
        </div>
      </div>

      <!-- Errores globales -->
      <div v-if="hasAnyError" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 shadow-sm">
        <div class="flex items-center mb-2">
          <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="font-semibold">Revisa los campos marcados:</div>
        </div>
        <ul class="list-disc ml-7 text-sm space-y-0.5">
          <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
        </ul>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="submit('index')" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Título -->
          <div class="md:col-span-2">
            <label for="titulo" class="block text-sm font-medium text-gray-700 mb-1">
              Título <span class="text-red-500">*</span>
            </label>
            <input
              id="titulo"
              v-model.trim="form.titulo"
              type="text"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('titulo')"
              placeholder="Ej. Instalación minisplit sucursal Centro"
              maxlength="150"
              autocomplete="off"
            />
            <div class="flex justify-between mt-1">
              <p v-if="form.errors.titulo" class="text-sm text-red-600">{{ form.errors.titulo }}</p>
              <span class="text-xs text-gray-400">{{ (form.titulo || '').length }} / 150</span>
            </div>
          </div>

          <!-- Empleado -->
          <div>
            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
              Empleado <span class="text-red-500">*</span>
            </label>
            <select
              id="user_id"
              v-model="form.user_id"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('user_id')"
            >
              <option value="" disabled>Selecciona un empleado</option>
              <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
          </div>

          <!-- Cliente -->
          <div>
            <label for="cliente_id" class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
            <select
              id="cliente_id"
              v-model="form.cliente_id"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('cliente_id')"
            >
              <option :value="null">— Sin cliente —</option>
              <option v-for="c in clientes" :key="c.id" :value="c.id">{{ c.nombre_razon_social }}</option>
            </select>
            <p v-if="form.errors.cliente_id" class="mt-1 text-sm text-red-600">{{ form.errors.cliente_id }}</p>
          </div>

          <!-- Asignar a (usuario asignado) -->
          <div>
            <label for="asignado_id" class="block text-sm font-medium text-gray-700 mb-1">
              Asignar a
              <span class="text-xs text-gray-500 ml-1">(opcional)</span>
            </label>
            <select
              id="asignado_id"
              v-model="form.asignado_id"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-colors"
              :class="inputError('asignado_id')"
            >
              <option :value="null">— Auto-asignar (a mí mismo) —</option>
              <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <p v-if="form.errors.asignado_id" class="mt-1 text-sm text-red-600">{{ form.errors.asignado_id }}</p>
            <p class="mt-1 text-xs text-gray-500">
              <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Si asignas a otro usuario, la tarea aparecerá en su panel.
            </p>
          </div>

          <!-- Tipo -->
          <div>
            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">
              Tipo <span class="text-red-500">*</span>
            </label>
            <select
              id="tipo"
              v-model="form.tipo"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent capitalize transition-colors"
              :class="inputError('tipo')"
            >
              <option value="" disabled>Selecciona un tipo</option>
              <option v-for="t in tipos" :key="t" :value="t">{{ formatLabel(t) }}</option>
            </select>
            <p v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">{{ form.errors.tipo }}</p>
          </div>

          <!-- Estado -->
          <div>
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">
              Estado <span class="text-red-500">*</span>
            </label>
            <select
              id="estado"
              v-model="form.estado"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent capitalize transition-colors"
              :class="inputError('estado')"
            >
              <option v-for="e in estados" :key="e" :value="e">
                <span :class="getEstadoClass(e)">{{ formatLabel(e) }}</span>
              </option>
            </select>
            <p v-if="form.errors.estado" class="mt-1 text-sm text-red-600">{{ form.errors.estado }}</p>
            <div class="mt-1 flex items-center">
              <div class="w-2 h-2 rounded-full mr-2" :class="getEstadoIndicator(form.estado)"></div>
              <span class="text-xs text-gray-600">{{ getEstadoDescription(form.estado) }}</span>
            </div>
          </div>

          <!-- Inicio -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="inicio_at" class="block text-sm font-medium text-gray-700">
                Inicio <span class="text-red-500">*</span>
              </label>
              <div class="flex gap-2">
                <button type="button" @click="setNow('inicio_at')" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                  Ahora
                </button>
              </div>
            </div>
            <input
              id="inicio_at"
              v-model="form.inicio_at"
              type="datetime-local"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('inicio_at')"
            />
            <p v-if="form.errors.inicio_at" class="mt-1 text-sm text-red-600">{{ form.errors.inicio_at }}</p>
          </div>

          <!-- Fin -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="fin_at" class="block text-sm font-medium text-gray-700">Fin</label>
              <div class="flex gap-1 flex-wrap">
                <button type="button" @click="setNow('fin_at')" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                  Ahora
                </button>
                <button type="button" @click="sumarMinutos(30)" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                  +30m
                </button>
                <button type="button" @click="sumarMinutos(60)" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                  +1h
                </button>
                <button type="button" @click="sumarMinutos(120)" class="text-xs px-2 py-1 border border-gray-300 rounded hover:bg-gray-50 transition-colors">
                  +2h
                </button>
              </div>
            </div>
            <input
              id="fin_at"
              v-model="form.fin_at"
              type="datetime-local"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('fin_at')"
            />
            <p v-if="form.errors.fin_at" class="mt-1 text-sm text-red-600">{{ form.errors.fin_at }}</p>
            <p v-if="duracionTexto" class="mt-1 text-xs text-gray-500 flex items-center">
              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Duración: <span class="font-medium text-gray-700 ml-1">{{ duracionTexto }}</span>
            </p>
            <p v-if="duracionWarning" class="mt-1 text-xs text-amber-600 flex items-center">
              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.339 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              {{ duracionWarning }}
            </p>
          </div>

          <!-- Ubicación -->
          <div class="md:col-span-2">
            <label for="ubicacion" class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
            <input
              id="ubicacion"
              v-model.trim="form.ubicacion"
              type="text"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
              :class="inputError('ubicacion')"
              placeholder="Ej. Sucursal Centro, Blvd. X, #123"
              maxlength="255"
            />
            <div class="flex justify-between mt-1">
              <p v-if="form.errors.ubicacion" class="text-sm text-red-600">{{ form.errors.ubicacion }}</p>
              <span class="text-xs text-gray-400">{{ (form.ubicacion || '').length }} / 255</span>
            </div>
          </div>

          <!-- Descripción -->
          <div class="md:col-span-2">
            <div class="flex items-center justify-between mb-1">
              <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
              <span class="text-xs text-gray-400">{{ (form.descripcion || '').length }} / 5000</span>
            </div>
            <textarea
              id="descripcion"
              v-model.trim="form.descripcion"
              rows="5"
              class="mt-1 w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors resize-none"
              :class="inputError('descripcion')"
              placeholder="Detalle las actividades realizadas, materiales usados, pendientes, etc."
              maxlength="5000"
              @input="autoResize"
            ></textarea>
            <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <button
                v-for="template in descripcionTemplates"
                :key="template"
                type="button"
                @click="addTemplate(template)"
                class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
              >
                + {{ template }}
              </button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 pt-6 border-t border-gray-200">
          <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <div class="text-xs text-gray-500 flex items-center">
              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Se guardará el usuario que registra y la hora exacta del envío.
            </div>
            <div class="flex gap-3">
              <button
                type="button"
                @click="submit('stay')"
                :disabled="form.processing"
                class="px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
              >
                <span v-if="!form.processing">Guardar y capturar otra</span>
                <span v-else class="inline-flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                  Guardando…
                </span>
              </button>
              <button
                type="submit"
                :disabled="form.processing || !isFormValid"
                class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium shadow-sm"
              >
                <span v-if="!form.processing">
                  <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  Guardar
                </span>
                <span v-else class="inline-flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                  </svg>
                  Guardando…
                </span>
              </button>
            </div>
          </div>
        </div>
      </form>

      <!-- Sugerencias de captura -->
      <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-900 mb-2 flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
          </svg>
          Tips para una mejor captura
        </h3>
        <ul class="text-xs text-blue-800 space-y-1">
          <li>• Si la actividad aún está en curso, deja el <span class="font-medium">Fin</span> vacío y marca el estado como <span class="font-medium">en proceso</span>.</li>
          <li>• Usa los botones de tiempo rápido (+30m, +1h, +2h) para agilizar el registro.</li>
          <li>• Incluye detalles específicos en la descripción para futuras referencias.</li>
          <li>• Las plantillas de descripción te ayudan a estructurar la información.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3'
import { computed, nextTick } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  usuarios: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  tipos: { type: Array, default: () => [] },
  estados: { type: Array, default: () => ['pendiente', 'en_proceso', 'completado', 'cancelado'] },
})

const page = usePage()
const currentUserId = page?.props?.auth?.user?.id ?? ''

const form = useForm({
  titulo: '',
  user_id: currentUserId || '',
  asignado_id: null,
  cliente_id: null,
  tipo: props.tipos[0] || '',
  estado: props.estados.includes('en_proceso') ? 'en_proceso' : (props.estados[0] || ''),
  inicio_at: '',
  fin_at: '',
  ubicacion: '',
  descripcion: '',
})

// Plantillas para descripción
const descripcionTemplates = [
  'Materiales utilizados:',
  'Tiempo estimado:',
  'Pendientes:',
  'Observaciones:',
  'Estado del equipo:',
  'Próximos pasos:'
]

const hasAnyError = computed(() => Object.keys(form.errors || {}).length > 0)

const isFormValid = computed(() => {
  return form.titulo && form.user_id && form.tipo && form.estado && form.inicio_at
})

const duracionWarning = computed(() => {
  if (!form.inicio_at || !form.fin_at) return ''
  const i = new Date(form.inicio_at)
  const f = new Date(form.fin_at)
  const ms = f - i
  if (isNaN(ms)) return ''
  if (ms < 0) return 'La fecha de fin no puede ser anterior al inicio'
  const hours = ms / (1000 * 60 * 60)
  if (hours > 12) return 'Duración muy larga, verifica las fechas'
  return ''
})

function inputError(field) {
  return form.errors?.[field] ? 'border-red-300 focus:ring-red-500 bg-red-50' : 'border-gray-300'
}

function formatLabel(text) {
  return text.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, l => l.toUpperCase())
}

function getEstadoClass(estado) {
  const classes = {
    'pendiente': 'text-yellow-700',
    'en_proceso': 'text-blue-700',
    'completado': 'text-green-700',
    'cancelado': 'text-red-700'
  }
  return classes[estado] || 'text-gray-700'
}

function getEstadoIndicator(estado) {
  const classes = {
    'pendiente': 'bg-yellow-400',
    'en_proceso': 'bg-blue-400',
    'completado': 'bg-green-400',
    'cancelado': 'bg-red-400'
  }
  return classes[estado] || 'bg-gray-400'
}

function getEstadoDescription(estado) {
  const descriptions = {
    'pendiente': 'Por iniciar',
    'en_proceso': 'En progreso',
    'completado': 'Finalizada',
    'cancelado': 'No realizada'
  }
  return descriptions[estado] || ''
}

function setNow(field) {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset()) // para datetime-local
  const val = now.toISOString().slice(0,16)
  form[field] = val
}

function sumarMinutos(mins) {
  if (!form.inicio_at) return
  const base = new Date(form.inicio_at)
  const local = new Date(base.getTime() - base.getTimezoneOffset() * 60000)
  local.setMinutes(local.getMinutes() + mins)
  const val = new Date(local.getTime() + local.getTimezoneOffset() * 60000) // revert hack
  val.setMinutes(val.getMinutes() - val.getTimezoneOffset())
  form.fin_at = val.toISOString().slice(0,16)
}

const duracionTexto = computed(() => {
  if (!form.inicio_at || !form.fin_at) return ''
  const i = new Date(form.inicio_at)
  const f = new Date(form.fin_at)
  const ms = f - i
  if (isNaN(ms) || ms <= 0) return ''
  const m = Math.round(ms / 60000)
  const h = Math.floor(m / 60)
  const mm = m % 60
  return h ? `${h}h ${mm}m` : `${mm}m`
})

function addTemplate(template) {
  const currentText = form.descripcion || ''
  const separator = currentText ? '\n\n' : ''
  form.descripcion = currentText + separator + template + ' '

  // Enfocar el textarea después de agregar la plantilla
  nextTick(() => {
    const textarea = document.getElementById('descripcion')
    if (textarea) {
      textarea.focus()
      textarea.setSelectionRange(textarea.value.length, textarea.value.length)
    }
  })
}

function autoResize(event) {
  const textarea = event.target
  textarea.style.height = 'auto'
  textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px'
}

function normalizePayload() {
  // Enviamos como strings datetime-local; Laravel Carbon los parsea sin problema.
  return { ...form.data() }
}

function submit(mode = 'index') {
  const payload = normalizePayload()
  form.post(route('bitacora.store'), {
    preserveScroll: true,
    data: payload,
    onSuccess: () => {
      if (mode === 'stay') {
        const keepUser = form.user_id
        const keepTipo = form.tipo
        const keepEstado = form.estado
        form.reset('titulo', 'cliente_id', 'inicio_at', 'fin_at', 'ubicacion', 'descripcion')
        form.user_id = keepUser
        form.tipo = keepTipo
        form.estado = keepEstado
        // Enfocar en título
        nextTick(() => {
          const el = document.getElementById('titulo')
          el?.focus?.()
        })
      } else {
        router.visit(route('bitacora.index'))
      }
    },
  })
}
</script>

<style scoped>
@import "tailwindcss" reference;
/* Mejores focos y estados */
input:focus, select:focus, textarea:focus {
  outline: none;
}

/* Animación suave para transiciones */
.transition-colors {
  transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, color 0.15s ease-in-out;
}

/* Mejorar el estilo de los selects */
select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
  background-size: 1.25em 1.25em;
  padding-right: 2.5rem;
}

/* Estilo para textarea con resize automático */
textarea {
  min-height: 120px;
  max-height: 200px;
}
</style>

