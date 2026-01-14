<template>
    <Head title="Ver Equipo" />
    <div class="equipos-show max-w-4xl mx-auto p-6 bg-gray-50 rounded-lg shadow-md">
      <h1 class="text-2xl font-semibold mb-6 text-center">Detalles del Equipo #{{ props.equipo.id }}</h1>

      <div class="space-y-6">
        <!-- Nombre del Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Nombre</label>
          <p class="text-gray-700">{{ props.equipo.nombre || 'Sin nombre' }}</p>
        </div>

        <!-- Marca -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Marca</label>
          <p class="text-gray-700">{{ props.equipo.marca || 'Sin marca' }}</p>
        </div>

        <!-- Modelo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Modelo</label>
          <p class="text-gray-700">{{ props.equipo.modelo || 'Sin modelo' }}</p>
        </div>

        <!-- Tipo de Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Tipo</label>
          <p class="text-gray-700">{{ formatearTipoEquipo(props.equipo.tipo) }}</p>
        </div>

        <!-- Descripción -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Descripción</label>
          <p class="text-gray-700">{{ props.equipo.descripcion || 'Sin descripción' }}</p>
        </div>

        <!-- Serie -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Número de Serie</label>
          <p class="text-gray-700">{{ props.equipo.serie || 'Sin serie' }}</p>
        </div>

        <!-- Estado -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Estado</label>
          <p class="text-gray-700">{{ formatearEstado(props.equipo.estado) }}</p>
        </div>

        <!-- Foto del Equipo -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Foto del Equipo</label>
          <img v-if="props.equipo.foto"
               :src="props.equipo.foto"
               alt="Foto del Equipo"
               class="max-w-full h-auto rounded-md shadow-sm"
               @error="handleImageError">
          <p v-else class="text-gray-500 italic">No hay foto del equipo disponible</p>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { Head } from '@inertiajs/vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';

  defineOptions({ layout: AppLayout });

  const props = defineProps({
    equipo: Object,
  });

  const formatearTipoEquipo = (tipo) => {
    const tipos = {
      minisplit: 'Minisplit',
      boiler: 'Boiler',
      refrigerador: 'Refrigerador',
      // Agregar más tipos según el modelo de BD
      otro: 'Otro Equipo',
    };
    return tipos[tipo] || 'Desconocido';
  };

  const formatearEstado = (estado) => {
    const estados = {
      activo: 'Activo',
      inactivo: 'Inactivo',
      mantenimiento: 'En Mantenimiento',
    };
    return estados[estado] || 'Desconocido';
  };

  // Función para manejar errores de carga de imagen
  const handleImageError = (event) => {
    console.warn('Error al cargar imagen de equipo:', event.target.src);
    // Establecer una imagen de placeholder local
    event.target.src = '/images/placeholder-product.svg';
    event.target.alt = 'Imagen no disponible';
  };
  </script>

  <style scoped>
@import "tailwindcss" reference;
  .equipos-show {
    margin-top: 1rem;
  }

  img {
    max-width: 300px;
  }
  </style>

