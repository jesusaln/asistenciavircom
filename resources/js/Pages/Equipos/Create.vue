<script setup>
import { reactive } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue';


defineOptions({ layout: AppLayout });

const props = defineProps({
  estados: { type: Array, default: () => [] },
  condiciones: { type: Array, default: () => [] },
})

const form = useForm({
  codigo: '',
  nombre: '',
  marca: '',
  modelo: '',
  numero_serie: '',
  descripcion: '',
  especificaciones: {},     // JSON
  imagen: null,
  precio_renta_mensual: '',
  precio_compra: '',
  fecha_adquisicion: '',
  estado: 'disponible',
  condicion: 'bueno',
  ubicacion_fisica: '',
  notas: '',
  accesorios: [],           // JSON
  fecha_garantia: '',
  proveedor: '',
})

const submit = () => {
  form.post(route('equipos.store'))
}
</script>

<template>
  <Head title="Crear Equipo" />
  <div class="max-w-5xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">Nuevo Equipo</h1>
      <Link :href="route('equipos.index')" class="text-amber-600 hover:underline">Volver</Link>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-xl border p-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium">Código *</label>
          <input v-model="form.codigo" type="text" class="mt-1 input" />
          <div v-if="form.errors.codigo" class="text-red-600 text-sm">{{ form.errors.codigo }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Nombre *</label>
          <input v-model="form.nombre" type="text" class="mt-1 input" />
          <div v-if="form.errors.nombre" class="text-red-600 text-sm">{{ form.errors.nombre }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Marca</label>
          <input v-model="form.marca" type="text" class="mt-1 input" />
        </div>

        <div>
          <label class="block text-sm font-medium">Modelo</label>
          <input v-model="form.modelo" type="text" class="mt-1 input" />
        </div>

        <div>
          <label class="block text-sm font-medium">Número de serie</label>
          <input v-model="form.numero_serie" type="text" class="mt-1 input" />
          <div v-if="form.errors.numero_serie" class="text-red-600 text-sm">{{ form.errors.numero_serie }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Imagen</label>
          <input @input="form.imagen = $event.target.files[0]" type="file" accept="image/*" class="mt-1 input" />
          <div v-if="form.errors.imagen" class="text-red-600 text-sm">{{ form.errors.imagen }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Precio renta mensual *</label>
          <input v-model="form.precio_renta_mensual" type="number" step="0.01" class="mt-1 input" />
          <div v-if="form.errors.precio_renta_mensual" class="text-red-600 text-sm">{{ form.errors.precio_renta_mensual }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Precio compra</label>
          <input v-model="form.precio_compra" type="number" step="0.01" class="mt-1 input" />
        </div>

        <div>
          <label class="block text-sm font-medium">Fecha adquisición</label>
          <input v-model="form.fecha_adquisicion" type="date" class="mt-1 input" />
        </div>

        <div>
          <label class="block text-sm font-medium">Estado *</label>
          <select v-model="form.estado" class="mt-1 input">
            <option v-for="e in props.estados" :key="e" :value="e">{{ e }}</option>
          </select>
          <div v-if="form.errors.estado" class="text-red-600 text-sm">{{ form.errors.estado }}</div>
        </div>

        <div>
          <label class="block text-sm font-medium">Condición *</label>
          <select v-model="form.condicion" class="mt-1 input">
            <option v-for="c in props.condiciones" :key="c" :value="c">{{ c }}</option>
          </select>
          <div v-if="form.errors.condicion" class="text-red-600 text-sm">{{ form.errors.condicion }}</div>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Descripción</label>
          <textarea v-model="form.descripcion" rows="3" class="mt-1 input"></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Ubicación física</label>
          <input v-model="form.ubicacion_fisica" type="text" class="mt-1 input" />
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium">Notas</label>
          <textarea v-model="form.notas" rows="3" class="mt-1 input"></textarea>
        </div>
      </div>

      <div class="pt-4 flex gap-3">
        <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded bg-amber-500 text-white hover:bg-amber-600">
          Guardar
        </button>
        <button type="button" @click="form.reset()" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
          Limpiar
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.input { @apply w-full rounded-md border-gray-300 focus:border-amber-500 focus:ring-amber-500; }
</style>

