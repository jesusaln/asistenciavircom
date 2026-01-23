<template>
  <div
   class="reporte-w-full w-full bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-00 p-8
         print:border-0 print:shadow-none print:rounded-none print:p-0 print:mx-0"
  >
    <!-- Encabezado -->
    <div class="flex justify-between items-start mb-8 gap-6">
      <!-- Datos empresa -->
      <div class="flex items-start gap-4">
        <!-- Logo -->
        <div class="w-28 h-28 flex items-center justify-center bg-gray-100 rounded-lg border-2 border-gray-300 shadow-sm overflow-hidden">
          <img
            v-if="empresa.logo"
            :src="empresa.logo"
            alt="Logo Empresa"
            class="w-full h-full object-contain"
          >
          <div
            v-else
            class="w-28 h-28 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-400 text-xs text-center rounded-lg border-2 border-gray-300 shadow-sm"
          >
            <div class="text-center">
              <svg class="w-12 h-12 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                />
              </svg>
              <span class="text-[10px]">Logo Empresa</span>
            </div>
          </div>
        </div>

        <!-- Info empresa -->
        <div class="flex-1">
          <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tight leading-tight mb-2">
            {{ empresa.nombre || 'Nombre de la Empresa' }}
          </h1>
          <div class="space-y-0.5 text-sm text-gray-600 dark:text-gray-300">
            <p class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              {{ empresa.direccion || 'Dirección de la empresa' }}
            </p>
            <p class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
              </svg>
              {{ empresa.telefono || 'Teléfono' }}
            </p>
            <p class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              {{ empresa.email || 'Email' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Datos reporte -->
      <div class="text-right bg-gradient-to-br from-gray-50 to-gray-100 px-6 py-4 rounded-lg border-2 border-gray-200 dark:border-slate-800 shadow-sm">
        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight mb-1">
          Reporte de Asignación
        </h2>
        <h3 class="text-base text-gray-600 dark:text-gray-300 font-semibold mb-3">
          Herramientas y Equipos
        </h3>
        <div class="text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-300">
          <p class="font-medium">Fecha de Emisión:</p>
          <p class="font-mono">
            {{ formatearFecha(new Date()) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Información del técnico y Resumen -->
    <div class="grid grid-cols-2 gap-6 mb-10 print:gap-4 print:mb-8">
      <!-- Datos técnico -->
      <div class="border-2 border-gray-300 rounded-lg p-5 bg-gradient-to-br from-white to-gray-50 shadow-sm">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b-2 border-gray-200 dark:border-slate-800">
          <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
          <h3 class="text-sm font-black text-gray-800 dark:text-gray-100 uppercase tracking-wide">
            Datos del Técnico
          </h3>
        </div>
        <div class="space-y-2.5 text-sm">
          <div class="flex">
            <span class="font-bold text-gray-600 dark:text-gray-300 w-28 flex-shrink-0">Nombre:</span>
            <span class="text-gray-900 dark:text-white font-semibold">
              {{ tecnico.nombre_completo }}
            </span>
          </div>

          <div class="flex">
            <span class="font-bold text-gray-600 dark:text-gray-300 w-28 flex-shrink-0">ID Empleado:</span>
            <span class="text-gray-900 dark:text-white font-mono">
              {{ tecnico.id }}
            </span>
          </div>

          <div class="flex">
            <span class="font-bold text-gray-600 dark:text-gray-300 w-28 flex-shrink-0">Email:</span>
            <span class="text-gray-900 dark:text-white">
              {{ tecnico.email || 'N/A' }}
            </span>
          </div>

          <div class="flex">
            <span class="font-bold text-gray-600 dark:text-gray-300 w-28 flex-shrink-0">Teléfono:</span>
            <span class="text-gray-900 dark:text-white">
              {{ tecnico.telefono || 'N/A' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Resumen inventario -->
      <div class="border-2 border-gray-300 rounded-lg p-5 bg-gradient-to-br from-white to-blue-50 shadow-sm">
        <div class="flex items-center gap-2 mb-4 pb-3 border-b-2 border-blue-200">
          <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
            />
          </svg>
          <h3 class="text-sm font-black text-gray-800 dark:text-gray-100 uppercase tracking-wide">
            Resumen de Inventario
          </h3>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-gray-200 dark:border-slate-800">
            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-bold mb-1">
              Total Herramientas
            </p>
            <p class="text-3xl font-black text-gray-900 dark:text-white">
              {{ estadisticas.total_herramientas }}
            </p>
          </div>

          <div class="bg-white dark:bg-slate-900 rounded-lg p-3 border border-gray-200 dark:border-slate-800">
            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-bold mb-1">
              Valor Total
            </p>
            <p class="text-2xl font-black text-green-700">
              ${{ estadisticas.valor_total?.toLocaleString('es-MX', { minimumFractionDigits: 2 }) || '0.00' }}
            </p>
          </div>
        </div>

        <div class="flex gap-3">
          <div class="flex-1 bg-yellow-50 px-3 py-2 rounded-lg border-2 border-yellow-200 print:border-gray-300">
            <p class="text-xs text-yellow-900 font-bold print:text-black flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
              Mant: {{ estadisticas.herramientas_mantenimiento }}
            </p>
          </div>

          <div class="flex-1 bg-orange-50 px-3 py-2 rounded-lg border-2 border-orange-200 print:border-gray-300">
            <p class="text-xs text-orange-900 font-bold print:text-black flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                  clip-rule="evenodd"
                />
              </svg>
              Vencer: {{ estadisticas.herramientas_por_vencer }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de herramientas -->
    <div class="mb-10">
      <div class="mb-3 flex items-center gap-2">
        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
          />
        </svg>
        <h3 class="text-base font-black text-gray-800 dark:text-gray-100 uppercase tracking-wide">
          Inventario Asignado
        </h3>
      </div>

      <table class="w-full border-collapse border-2 border-gray-400 text-sm shadow-md rounded-lg overflow-hidden">
        <thead>
          <tr class="bg-gradient-to-r from-gray-800 to-gray-700 text-white print:bg-gray-800">
            <th class="border border-gray-400 px-3 py-3 text-left font-bold w-12">ID</th>
            <th class="border border-gray-400 px-3 py-3 text-left font-bold">Herramienta / Serie</th>
            <th class="border border-gray-400 px-3 py-3 text-left font-bold">Categoría</th>
            <th class="border border-gray-400 px-3 py-3 text-center font-bold w-24">Estado</th>
            <th class="border border-gray-400 px-3 py-3 text-right font-bold w-28">Valor</th>
            <th class="border border-gray-400 px-3 py-3 text-center font-bold w-28">Último Mant.</th>
            <th class="border border-gray-400 px-3 py-3 text-left font-bold">Observaciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-slate-900">
          <tr
            v-for="(herramienta, index) in herramientas"
            :key="herramienta.id"
            :class="{'bg-white dark:bg-slate-900 print:bg-white dark:bg-slate-900': index % 2 !== 0}"
            class="hover:bg-blue-50 transition-colors"
          >
            <td class="border border-gray-300 px-3 py-3 text-center text-gray-600 dark:text-gray-300 font-mono font-semibold">
              {{ herramienta.id }}
            </td>
            <td class="border border-gray-300 px-3 py-3">
              <div class="font-bold text-gray-900 dark:text-white leading-tight">
                {{ herramienta.nombre }}
              </div>
              <div
                class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-1 bg-gray-100 px-2 py-0.5 rounded inline-block"
              >
                SN: {{ herramienta.numero_serie }}
              </div>
            </td>
            <td class="border border-gray-300 px-3 py-3 text-gray-700 font-medium">
              {{ herramienta.categoria }}
            </td>
            <td class="border border-gray-300 px-3 py-3 text-center">
              <span
                :class="[
                  'inline-block px-3 py-1 text-xs font-black rounded-full border-2',
                  getEstadoColor(herramienta.estado),
                  'print:bg-white dark:bg-slate-900 print:text-black print:border-gray-500'
                ]"
              >
                {{ getEstadoLabel(herramienta.estado) }}
              </span>
            </td>
            <td class="border border-gray-300 px-3 py-3 text-right text-gray-900 dark:text-white font-bold font-mono">
              ${{ herramienta.costo_reemplazo?.toLocaleString('es-MX', { minimumFractionDigits: 2 }) || '0.00' }}
            </td>
            <td class="border border-gray-300 px-3 py-3 text-center text-gray-700 font-medium">
              {{ formatearFecha(herramienta.fecha_ultimo_mantenimiento) || '-' }}
            </td>
            <td class="border border-gray-300 px-3 py-3 text-xs">
              <div
                v-if="herramienta.necesita_mantenimiento"
                class="flex items-center text-red-700 font-bold mb-1 bg-red-50 px-2 py-1 rounded border border-red-200"
              >
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                  />
                </svg>
                Requiere Mantenimiento
              </div>
              <div
                v-else-if="herramienta.porcentaje_vida_util > 80"
                class="flex items-center text-orange-700 font-bold mb-1 bg-orange-50 px-2 py-1 rounded border border-orange-200"
              >
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                    clip-rule="evenodd"
                  />
                </svg>
                Vida Útil &gt; 80%
              </div>
              <div class="text-gray-600 dark:text-gray-300 leading-tight">
                {{ herramienta.descripcion || 'Sin observaciones adicionales.' }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Sin herramientas -->
      <div
        v-if="herramientas.length === 0"
        class="text-center py-12 border-2 border-t-0 border-gray-300 text-gray-500 dark:text-gray-400 italic bg-white dark:bg-slate-900"
      >
        <svg class="w-16 h-16 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
        <p class="font-semibold">
          No hay herramientas asignadas a este técnico actualmente.
        </p>
      </div>
    </div>

    <!-- Declaración de responsabilidad -->
    <div class="mb-10 text-xs text-gray-700 leading-relaxed bg-white dark:bg-slate-900 border-2 border-gray-300 rounded-lg p-5">
      <div class="flex items-start gap-2 mb-2">
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <p class="font-black uppercase tracking-wide text-gray-800 dark:text-gray-100">
          Términos y Condiciones de Uso
        </p>
      </div>
      <p class="text-justify pl-7">
        Al firmar este documento, el técnico acepta la responsabilidad sobre el uso, cuidado y resguardo de las
        herramientas y equipos detallados anteriormente. El técnico se compromete a notificar inmediatamente cualquier
        daño, pérdida o mal funcionamiento. El equipo debe ser devuelto en las mismas condiciones en que fue recibido,
        salvo el desgaste natural por uso adecuado. En caso de pérdida o daño por negligencia, la empresa se reserva el
        derecho de aplicar las políticas internas correspondientes.
      </p>
    </div>

    <!-- Sección de firmas -->
    <div class="grid grid-cols-2 gap-12 mt-10 mb-8 signature-section">
      <!-- Firma del técnico -->
      <div class="text-center">
        <div class="border-b-2 border-gray-900 h-20 mb-3 relative">
          <span class="absolute -top-2 left-0 text-xs text-gray-400 italic">
            Firma
          </span>
        </div>
        <p class="font-black text-gray-900 dark:text-white text-lg mb-1">
          {{ tecnico.nombre_completo }}
        </p>
        <p class="text-xs text-gray-600 dark:text-gray-300 uppercase font-bold tracking-wide bg-gray-100 inline-block px-4 py-1 rounded">
          Técnico Responsable
        </p>
      </div>

      <!-- Firma del supervisor -->
      <div class="text-center">
        <div class="border-b-2 border-gray-900 h-20 mb-3 relative">
          <span class="absolute -top-2 left-0 text-xs text-gray-400 italic">
            Firma y Sello
          </span>
        </div>
        <p class="font-black text-gray-900 dark:text-white text-lg mb-1">
          AUTORIZADO POR
        </p>
        <p class="text-xs text-gray-600 dark:text-gray-300 uppercase font-bold tracking-wide bg-gray-100 inline-block px-4 py-1 rounded">
          Supervisor / Gerente
        </p>
      </div>
    </div>

    <!-- Pie de página -->
    <div class="mt-8 pt-4 border-t-2 border-gray-300 flex justify-between text-[10px] text-gray-500 dark:text-gray-400">
      <p class="flex items-center gap-1">
        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
            clip-rule="evenodd"
          />
        </svg>
        Generado por Sistema de Gestión CDD
      </p>
      <p class="font-mono">
        Documento Confidencial
      </p>
    </div>

    <!-- Botones de acción (solo visibles en pantalla) -->
    <div
      class="mt-8 flex justify-center gap-4 print:hidden sticky bottom-0 bg-white dark:bg-slate-900/95 backdrop-blur p-4 border-t border-gray-200 dark:border-slate-800 shadow-lg z-40 -mx-8 -mb-8"
    >
      <button
        @click="imprimirReporte"
        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg shadow-md hover:shadow-lg hover:from-blue-700 hover:to-blue-800 font-bold flex items-center gap-2 transition-all transform hover:scale-105"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
          />
        </svg>
        Imprimir Reporte
      </button>

      <Link
        :href="`/herramientas/gestion/${tecnico.id}/descargar`"
        class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg shadow-md hover:shadow-lg hover:from-green-700 hover:to-green-800 font-bold flex items-center gap-2 transition-all transform hover:scale-105"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
          />
        </svg>
        Descargar CSV
      </Link>

      <Link
        :href="`/herramientas/gestion/${tecnico.id}/edit`"
        class="px-6 py-2.5 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg shadow-md hover:shadow-lg hover:from-gray-700 hover:to-gray-800 font-bold flex items-center gap-2 transition-all transform hover:scale-105"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 19l-7-7m0 0l7-7m-7 7h18"
          />
        </svg>
        Volver
      </Link>
    </div>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnico: {
    type: Object,
    required: true,
  },
  herramientas: {
    type: Array,
    default: () => [],
  },
  estadisticas: {
    type: Object,
    required: true,
  },
})

const page = usePage()
const empresaConfig = ref({
  nombre_empresa: 'CDD Sistema',
  direccion: '',
  telefono: '',
  email: '',
  logo_url: null,
})

const empresa = computed(() => {
  // Construir dirección completa
  const direccionParts = []
  if (empresaConfig.value.calle) direccionParts.push(empresaConfig.value.calle)
  if (empresaConfig.value.numero_exterior) direccionParts.push(`No. ${empresaConfig.value.numero_exterior}`)
  if (empresaConfig.value.numero_interior) direccionParts.push(`Int. ${empresaConfig.value.numero_interior}`)
  if (empresaConfig.value.colonia) direccionParts.push(`Col. ${empresaConfig.value.colonia}`)
  if (empresaConfig.value.ciudad) direccionParts.push(empresaConfig.value.ciudad)
  if (empresaConfig.value.estado) direccionParts.push(empresaConfig.value.estado)
  if (empresaConfig.value.pais) direccionParts.push(empresaConfig.value.pais)
  if (empresaConfig.value.codigo_postal) direccionParts.push(`CP ${empresaConfig.value.codigo_postal}`)

  const direccionCompleta = direccionParts.length > 0 ? direccionParts.join(', ') : 'Dirección no configurada'

  return {
    nombre: empresaConfig.value.nombre_empresa || 'Nombre de la Empresa',
    direccion: direccionCompleta,
    telefono: empresaConfig.value.telefono || 'Teléfono',
    email: empresaConfig.value.email || 'Email',
    logo: empresaConfig.value.logo_reportes_url || empresaConfig.value.logo_url,
  }
})

// Cargar configuración de empresa
const cargarConfiguracionEmpresa = async () => {
  try {
    const response = await axios.get('/empresa/configuracion/api')
    empresaConfig.value = response.data.configuracion
  } catch (error) {
    console.error('Error al cargar configuración de empresa:', error)
  }
}

onMounted(() => {
  cargarConfiguracionEmpresa()
})

const getEstadoColor = (estado) => {
  const colors = {
    disponible: 'bg-green-100 text-green-800',
    asignada: 'bg-blue-100 text-blue-800',
    mantenimiento: 'bg-yellow-100 text-yellow-800',
    baja: 'bg-red-100 text-red-800',
    perdida: 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}

const getEstadoLabel = (estado) => {
  const labels = {
    disponible: 'Disponible',
    asignada: 'Asignada',
    mantenimiento: 'En Mant.',
    baja: 'De Baja',
    perdida: 'Perdida',
  }
  return labels[estado] || estado
}

const getCondicionColor = (condicion) => {
  const colors = {
    excelente: 'bg-green-100 text-green-800',
    buena: 'bg-blue-100 text-blue-800',
    regular: 'bg-yellow-100 text-yellow-800',
    mala: 'bg-red-100 text-red-800',
    critica: 'bg-red-100 text-red-800',
  }
  return colors[condicion] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}

const imprimirReporte = () => {
  window.print()
}

const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A'
  const d = new Date(fecha)
  if (Number.isNaN(d.getTime())) return 'N/A'
  return d.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>

<style>
/* Márgenes de la hoja al imprimir (ajústalos a tu gusto) */
@page {
  margin: 0;
}

@media print {
  html,
  body {
    height: auto !important;
    min-height: auto !important;
    max-height: none !important;
  }

  body,
  #app,
  #app > div {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    background-color: white !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    height: auto !important;
    min-height: auto !important;
    max-height: none !important;
    overflow: visible !important;
    position: static !important;
  }

  .w-full,
  .w-full,
  .w-full,
  div[class*='max-w'] {
    max-width: none !important;
    height: auto !important;
    min-height: auto !important;
    max-height: none !important;
    overflow: visible !important;
    position: static !important;
  }

  div,
  section,
  article {
    overflow: visible !important;
    max-height: none !important;
    height: auto !important;
    position: static !important;
  }

  .print\:hidden {
    display: none !important;
  }

  /* Colores de fondo básicos para impresión (por si el navegador los ignora) */
  .bg-gray-100 {
    background-color: #f3f4f6 !important;
  }

  .bg-white dark:bg-slate-900 {
    background-color: #f9fafb !important;
  }

  .bg-blue-100 {
    background-color: #dbeafe !important;
  }

  .bg-green-100 {
    background-color: #dcfce7 !important;
  }

  .bg-yellow-100 {
    background-color: #fef9c3 !important;
  }

  .bg-red-100 {
    background-color: #fee2e2 !important;
  }

  .bg-orange-50 {
    background-color: #fff7ed !important;
  }

  .bg-yellow-50 {
    background-color: #fefce8 !important;
  }

  .bg-red-50 {
    background-color: #fef2f2 !important;
  }

  table {
    page-break-inside: auto !important;
    break-inside: auto !important;
    page-break-after: auto !important;
    width: 100% !important;
  }

  tbody {
    display: table-row-group !important;
  }

  tfoot {
    display: table-footer-group !important;
  }

  tr {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    page-break-after: auto !important;
  }

  .signature-section {
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    page-break-before: auto !important;
  }

  [class*='sticky'],
  [class*='fixed'] {
    position: static !important;
  }

  [class*='overflow-'],
  [class*='scroll'] {
    overflow: visible !important;
  }

  /* Ocultar sidebar en impresión */
  aside {
    display: none !important;
  }

  /* Ocultar barra de navegación superior */
  nav {
    display: none !important;
  }

  /* Ajustar margen del contenido principal */
  main {
    margin-left: 0 !important;
  }
}
</style>

