<!-- resources/js/Pages/Producto/Inventario.vue -->

<template>
    <div>
      <h1>Inventario de {{ producto.nombre }}</h1>
      <div v-if="inventarios.length">
        <table>
          <thead>
            <tr>
              <th>Cantidad</th>
              <th>Fecha de Registro</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inventario in inventarios" :key="inventario.id">
              <td>{{ inventario.cantidad }}</td>
              <td>{{ inventario.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else>
        <p>No hay registros de inventario para este producto.</p>
      </div>
    </div>
  </template>

  <script>
  export default {
    props: {
      producto: Object,
      inventarios: Array,
    },
  };
  </script>

