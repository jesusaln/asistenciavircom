<!-- resources/js/Pages/Inventario/Index.vue -->

<template>
    <div>
      <h1>Inventario</h1>
      <div v-if="inventarios.length">
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cantidad</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inventario in inventarios" :key="inventario.id">
              <td>{{ inventario.producto.nombre }}</td>
              <td>{{ inventario.cantidad }}</td>
              <td>
                <!-- Aquí puedes agregar botones para editar o eliminar -->
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else>
        <p>No hay productos en el inventario.</p>
      </div>
    </div>
  </template>

  <script>
  export default {
    props: {
      inventarios: Array,
    },
  };
  </script>




