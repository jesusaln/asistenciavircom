<template>
  <AppLayout title="Crear Nuevo Kit">
    <div class="min-h-screen bg-[var(--ui-surface)] text-slate-100 font-sans selection:bg-brand-500 selection:text-white">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h1 class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-400 to-brand-500 tracking-tight">
              Crear Nuevo Kit
            </h1>
            <p class="mt-2 text-slate-400 text-lg">Define la composición y precio del nuevo paquete</p>
          </div>
          <Link href="/kits" 
            class="inline-flex items-center px-5 py-2.5 border border-slate-600 rounded-2xl shadow-sm text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 transition-all duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Volver a Kits
          </Link>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 overflow-hidden">
          
          <!-- Información Básica -->
          <div class="px-8 py-6 border-b border-slate-700 bg-slate-800/50">
            <div class="flex items-center">
              <span class="p-2 bg-brand-500/10 rounded-xl mr-3">
                 <svg class="w-10 h-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                 </svg>
              </span>
              <div>
                <h3 class="text-xl font-bold text-white">Información General</h3>
                <p class="mt-1 text-sm text-slate-400">Detalles principales del kit</p>
              </div>
            </div>
          </div>

          <div class="px-8 py-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Nombre -->
              <div>
                <label for="nombre" class="block text-sm font-medium text-slate-300 mb-2">Nombre del Kit <span class="text-brand-500">*</span></label>
                <input v-model="form.nombre" type="text" id="nombre"
                       class="block w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200"
                       placeholder="Ej. Kit de Instalación Básico"
                       required>
                <div v-if="errors.nombre" class="mt-2 text-sm text-rose-400 font-medium">{{ errors.nombre[0] }}</div>
              </div>

              <!-- Código -->
              <div>
                <label for="codigo" class="block text-sm font-medium text-slate-300 mb-2">Código <span class="text-slate-500 text-xs">(Opcional)</span></label>
                <input v-model="form.codigo" type="text" id="codigo"
                       class="block w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200"
                       placeholder="Generar automáticamente">
                <div v-if="errors.codigo" class="mt-2 text-sm text-rose-400 font-medium">{{ errors.codigo[0] }}</div>
              </div>
            </div>

            <!-- Descripción -->
            <div>
              <label for="descripcion" class="block text-sm font-medium text-slate-300 mb-2">Descripción</label>
              <textarea v-model="form.descripcion" id="descripcion" rows="3"
                        class="block w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200"
                        placeholder="Detalles sobre qué incluye este kit y para qué sirve..."></textarea>
              <div v-if="errors.descripcion" class="mt-2 text-sm text-rose-400 font-medium">{{ errors.descripcion[0] }}</div>
            </div>

            <!-- Foto y Destacado -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Imagen -->
              <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Imagen del Kit</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-700 border-dashed rounded-2xl bg-black/50 hover:bg-slate-900 transition-all duration-200 group">
                  <div class="space-y-2 text-center">
                    <div v-if="imagePreview" class="relative inline-block group/preview">
                      <img :src="imagePreview" class="h-32 w-32 object-cover rounded-xl border-2 border-brand-500/50 shadow-xl shadow-brand-500/10" />
                      <button @click="removeImage" type="button" class="absolute -top-2 -right-2 bg-brand-500 text-white rounded-full p-1 opacity-0 group-hover/preview:opacity-100 transition-opacity duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                      </button>
                    </div>
                    <div v-else class="flex flex-col items-center">
                      <svg class="mx-auto h-12 w-12 text-slate-500 group-hover:text-brand-500 transition-colors duration-200" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      </svg>
                      <div class="flex text-sm text-slate-400 mt-2">
                        <label for="imagen" class="relative cursor-pointer bg-transparent rounded-xl font-bold text-brand-500 hover:text-brand-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-brand-500 focus-within:ring-offset-slate-900">
                          <span>Subir un archivo</span>
                          <input id="imagen" name="imagen" type="file" class="sr-only" @change="handleImageUpload" accept="image/*">
                        </label>
                        <p class="pl-1">o arrastrar y soltar</p>
                      </div>
                      <p class="text-xs text-slate-500 mt-1">PNG, JPG, WEBP hasta 10MB</p>
                    </div>
                  </div>
                </div>
                <div v-if="errors.imagen" class="mt-2 text-sm text-rose-400 font-medium">{{ errors.imagen[0] }}</div>
              </div>

              <!-- Destacado -->
              <div class="flex flex-col justify-center">
                <label class="block text-sm font-medium text-slate-300 mb-4">Visibilidad en Tienda</label>
                <div class="bg-black/50 border border-slate-700 rounded-2xl p-6 hover:bg-slate-900 transition-all duration-200">
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="text-white font-bold">Producto Destacado</h4>
                      <p class="text-sm text-slate-400 mt-1">Aparecerá en la sección "Top Ventas" e Inicio</p>
                    </div>
                    <button type="button" 
                            @click="form.destacado = !form.destacado"
                            :class="[form.destacado ? 'bg-brand-500' : 'bg-slate-700']"
                            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                      <span :class="[form.destacado ? 'translate-x-5' : 'translate-x-0']"
                            class="inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Precio y Categoría -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <div>
                <label for="precio_venta" class="block text-sm font-medium text-slate-300 mb-2">Precio de Venta <span class="text-brand-500">*</span></label>
                <div class="relative rounded-2xl shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="text-slate-500 font-bold">$</span>
                  </div>
                  <input v-model.number="form.precio_venta" type="number" step="0.01" min="0" id="precio_venta"
                         class="pl-8 block w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 font-mono text-lg"
                         placeholder="0.00"
                         required>
                </div>
                <div v-if="errors.precio_venta" class="mt-2 text-sm text-rose-400 font-medium">{{ errors.precio_venta[0] }}</div>
              </div>

              <div>
                <label for="categoria_id" class="block text-sm font-medium text-slate-300 mb-2">Categoría</label>
                <div class="relative">
                   <select v-model="form.categoria_id" id="categoria_id"
                          class="block w-full bg-slate-900 border border-slate-700 rounded-xl py-3 px-4 text-white focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 appearance-none">
                    <option value="" class="text-slate-500">Seleccionar categoría (opcional)</option>
                    <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                      {{ categoria.nombre }}
                    </option>
                  </select>
                  <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Componentes del Kit -->
          <div class="px-8 py-6 border-t border-slate-700 bg-slate-800/50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div class="flex items-center">
                 <span class="p-2 bg-brand-500/10 rounded-xl mr-3">
                    <svg class="w-10 h-10 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                 </span>
                 <div>
                   <h3 class="text-xl font-bold text-white">Componentes</h3>
                   <p class="mt-1 text-sm text-slate-400">Productos y servicios incluidos</p>
                 </div>
              </div>
              <button type="button" @click="addComponent"
                      class="inline-flex items-center px-4 py-2 border border-transparent rounded-2xl shadow-xl shadow-emerald-900/20 text-sm font-bold text-white bg-gradient-to-r from-brand-500 to-brand-600 hover:from-emerald-400 hover:to-teal-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 transition-all duration-200 transform hover:scale-105 active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Agregar Componente
              </button>
            </div>
          </div>

          <div class="px-8 py-8 bg-slate-800">
            <!-- Lista de Componentes -->
            <div class="space-y-6">
              <div v-for="(componente, index) in form.componentes" :key="index"
                   class="bg-slate-700/30 border border-slate-600/50 rounded-2xl p-6 transition-all duration-200 hover:border-brand-500 hover:shadow-xl relative group">
                
                <div class="absolute -top-3 -left-3 bg-slate-800 text-slate-300 text-xs font-bold px-3 py-1 rounded-full border border-slate-600 shadow-sm">
                   #{{ index + 1 }}
                </div>

                <div class="flex justify-end absolute top-4 right-4 opacity-70 group-hover:opacity-100 transition-opacity">
                  <button type="button" @click="removeComponent(index)"
                          class="p-2 bg-brand-500/10 text-rose-400 rounded-xl hover:bg-slate-500 hover:text-white transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-2">
                  <!-- Tipo -->
                  <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tipo</label>
                    <div class="relative">
                       <select v-model="componente.item_type"
                              @change="clearItemSelection(index)"
                              class="block w-full bg-slate-900 border border-slate-600 rounded-xl py-2.5 px-3 text-white focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 appearance-none text-sm"
                              required>
                        <option value="" class="text-slate-500">Seleccionar</option>
                        <option value="producto">Producto</option>
                        <option value="servicio">Servicio</option>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                         <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                       </div>
                    </div>
                  </div>

                  <!-- Item (Producto o Servicio) -->
                  <div class="md:col-span-1">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                      {{ componente.item_type === 'servicio' ? 'Servicio' : 'Producto' }}
                    </label>
                    <div class="relative">
                       <select v-model="componente.item_id"
                              @change="updateItemInfo(index)"
                              class="block w-full bg-slate-900 border border-slate-600 rounded-xl py-2.5 px-3 text-white focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 appearance-none text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                              :disabled="!componente.item_type"
                              required>
                        <option value="" class="text-slate-500">Seleccionar...</option>
                        <template v-if="componente.item_type === 'producto'">
                          <option v-for="producto in productosDisponibles" :key="producto.id" :value="producto.id">
                            {{ producto.codigo }} - {{ producto.nombre }}
                          </option>
                        </template>
                        <template v-else-if="componente.item_type === 'servicio'">
                          <option v-for="servicio in serviciosDisponibles" :key="servicio.id" :value="servicio.id">
                            {{ servicio.codigo }} - {{ servicio.nombre }}
                          </option>
                        </template>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                         <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                       </div>
                    </div>
                  </div>

                  <!-- Cantidad -->
                  <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Cantidad</label>
                    <input v-model.number="componente.cantidad" type="number" min="1" step="1"
                           @input="calculateCosts"
                           class="block w-full bg-slate-900 border border-slate-600 rounded-xl py-2.5 px-3 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 text-sm font-mono text-center"
                           required>
                  </div>

                  <!-- Precio Unitario -->
                  <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">P. Unitario</label>
                    <div class="relative rounded-2xl shadow-sm">
                      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-slate-500 font-bold text-xs">$</span>
                      </div>
                      <input v-model.number="componente.precio_unitario" type="number" step="0.01" min="0"
                             @input="calculateCosts"
                             class="pl-6 block w-full bg-slate-900 border border-slate-600 rounded-xl py-2.5 px-3 text-white placeholder-slate-600 focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200 text-sm font-mono text-right"
                             :placeholder="getItemPrecio(componente)">
                    </div>
                  </div>
                </div>

                <!-- Info adicional -->
                <div v-if="componente.item_type === 'producto' && componente.requiereSeries" class="mt-4 flex items-center">
                  <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium text-blue-400 bg-brand-500/10 rounded-xl border border-blue-500/20">
                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Producto seriado (series se asignan al vender)
                  </span>
                </div>
              </div>
            </div>

            <div v-if="errors.componentes" class="mt-4 text-sm text-rose-400 font-medium px-4 py-2 bg-brand-500/10 rounded-xl border border-rose-500/20 inline-block">
               {{ errors.componentes[0] }}
            </div>

            <!-- Resumen de Costos y Precios -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
               <!-- Caja 1: Suma de Componentes (lo que el usuario teclea) -->
               <div class="bg-gradient-to-r from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl p-6 shadow-md">
                 <h4 class="text-slate-300 font-bold mb-1">Total Precios de Componentes</h4>
                 <p class="text-xs text-slate-500 mb-4">Suma de la columna "P. Unitario" configurada</p>
                 <div class="flex justify-between items-end">
                   <div class="text-left">
                     <p class="text-sm text-slate-400">Subtotal (Suma Exacta)</p>
                     <div class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(sumaPreciosUnitarios) }}</div>
                   </div>
                    <div class="text-right">
                      <p class="text-[10px] text-brand-500 font-black uppercase tracking-wide mb-1.5 opacity-80">Precio Final Sugerido</p>
                      <div class="text-2xl font-black text-brand-400 tracking-tighter">{{ formatCurrency(sumaConIva) }}</div>
                      <p class="text-[9px] text-slate-500 mt-1 font-bold">Sumando 16% IVA al subtotal</p>
                    </div>
                 </div>
               </div>
               
               <!-- Caja 2: Costo de Inventario API -->
               <div class="bg-gradient-to-r from-blue-900/40 to-indigo-900/40 border border-blue-500/20 rounded-2xl p-6 shadow-md relative">
                 <div class="absolute top-4 right-4">
                    <svg v-if="loadingCosto" class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                 </div>
                 <h4 class="text-blue-200 font-bold mb-1">Costo Base de Producción</h4>
                 <p class="text-xs text-blue-400/80 mb-4">Costo Histórico / FIFO (Extraído de la API)</p>
                 <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
                   <div class="text-left">
                     <p class="text-sm text-blue-300">Inversión Interna</p>
                     <div class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(costoTotal) }}</div>
                   </div>
                   <div class="text-left sm:text-right">
                     <p class="text-xs text-blue-400 font-bold uppercase tracking-wider mb-1">Margen Real</p>
                     <div :class="['text-xl font-bold', parseFloat(margen) > 30 ? 'text-emerald-400' : 'text-amber-400']">{{ margen }}%</div>
                   </div>
                 </div>
               </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="px-8 py-6 bg-black/50 border-t border-slate-700 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <Link href="/kits" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-slate-600 rounded-2xl shadow-sm text-base font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 transition-all duration-200">
              Cancelar
            </Link>
            <button type="submit" :disabled="loading"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-2xl shadow-xl shadow-brand-900/20 text-base font-bold text-white bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 disabled:opacity-50 disabled:transform-none transition-all duration-200 transform hover:scale-105 active:scale-95">
              <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              {{ loading ? 'Procesando...' : 'Crear Kit' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import axios from 'axios'

const props = defineProps({
  productosDisponibles: Array,
  serviciosDisponibles: Array,
  categorias: Array,
  almacenPrincipal: Object
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'info', background: '#3b82f6', icon: false }
  ]
})

// Reactive data
const form = ref({
  nombre: '',
  descripcion: '',
  codigo: '',
  precio_venta: null,
  categoria_id: '',
  destacado: false,
  imagen: null,
  componentes: []
})

const errors = ref({})
const loading = ref(false)
const loadingCosto = ref(false)
const costoTotal = ref(0)
const margen = ref(0)
const imagePreview = ref(null)

const sumaPreciosUnitarios = computed(() => {
  return form.value.componentes.reduce((acc, c) => {
    return acc + (Number(c.precio_unitario || 0) * Number(c.cantidad || 0));
  }, 0);
});

const sumaConIva = computed(() => {
  return sumaPreciosUnitarios.value * 1.16;
});

// Methods
const handleImageUpload = (e) => {
  const file = e.target.files[0]
  if (!file) return
  
  form.value.imagen = file
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const removeImage = () => {
  form.value.imagen = null
  imagePreview.value = null
  const input = document.getElementById('imagen')
  if (input) input.value = ''
}

const addComponent = () => {
  form.value.componentes.push({
    item_type: '',
    item_id: '',
    cantidad: 1,
    precio_unitario: null
  })
}

const removeComponent = (index) => {
  form.value.componentes.splice(index, 1)
  calculateCosts()
}

const clearItemSelection = (index) => {
  form.value.componentes[index].item_id = ''
  form.value.componentes[index].precio_unitario = null
  calculateCosts()
}

const updateItemInfo = (index) => {
  const componente = form.value.componentes[index]
  if (!componente.item_type || !componente.item_id) return

  if (componente.item_type === 'producto') {
    const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
    const requiereSeries = producto && (producto.requiere_serie || producto.maneja_series || producto.expires)
    
    componente.requiereSeries = requiereSeries
    componente.productoNombre = producto?.nombre || 'Producto'
  } else if (componente.item_type === 'servicio') {
    componente.requiereSeries = false
    componente.productoNombre = props.serviciosDisponibles.find(s => s.id == componente.item_id)?.nombre || 'Servicio'
  }

  if (!componente.precio_unitario) {
    if (componente.item_type === 'producto') {
      const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
      if (producto) {
        componente.precio_unitario = producto.precio_venta
      }
    } else if (componente.item_type === 'servicio') {
      const servicio = props.serviciosDisponibles.find(s => s.id == componente.item_id)
      if (servicio) {
        componente.precio_unitario = servicio.precio
      }
    }
  }
  calculateCosts()
}

const getItemPrecio = (componente) => {
  if (componente.item_type === 'producto') {
    const producto = props.productosDisponibles.find(p => p.id == componente.item_id)
    return producto ? `$${producto.precio_venta}` : 'Precio del producto'
  } else if (componente.item_type === 'servicio') {
    const servicio = props.serviciosDisponibles.find(s => s.id == componente.item_id)
    return servicio ? `$${servicio.precio}` : 'Precio del servicio'
  }
  return 'Seleccione un item'
}

const calculateCosts = async () => {
  const componentes = form.value.componentes
    .filter(c => c.item_type && c.item_id && c.cantidad > 0)
    .map(c => ({
      item_type: c.item_type,
      item_id: Number(c.item_id),
      cantidad: Number(c.cantidad),
      precio_unitario: c.precio_unitario
    }))

  if (componentes.length === 0) {
    costoTotal.value = 0
    margen.value = 0
    return
  }

  loadingCosto.value = true;
  try {
    const response = await axios.post('/kits/api/calcular-costo', {
      componentes: componentes,
      almacen_id: props.almacenPrincipal?.id || 1
    });

    const data = response.data;

    if (data.success) {
      costoTotal.value = data.costo_total
      updateMargen()
    } else {
      console.error('Error calculando costo:', data.error)
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingCosto.value = false;
  }
}

const updateMargen = () => {
  const precioVenta = form.value.precio_venta || 0
  if (costoTotal.value > 0 && precioVenta > 0) {
    // Quitar IVA del precio de venta (16%)
    const precioVentaSinIVA = precioVenta / 1.16
    margen.value = (((precioVentaSinIVA - costoTotal.value) / costoTotal.value) * 100).toFixed(1)
  } else if (costoTotal.value === 0 && precioVenta > 0) {
    margen.value = '100.0' // Solo servicios
  } else {
    margen.value = 0
  }
}

const submitForm = async () => {
  if (form.value.componentes.length === 0) {
    notyf.error('Debes agregar al menos un componente al kit.')
    return
  }

  // Validar componentes
  const componentesInvalidos = form.value.componentes.filter(c =>
    !c.item_type || !c.item_id || !c.cantidad || c.cantidad <= 0
  )

  if (componentesInvalidos.length > 0) {
    notyf.error('Todos los componentes deben tener tipo, item y cantidad válida.')
    return
  }

  const productosCount = form.value.componentes.filter(c => c.item_type === 'producto').length
  if (productosCount === 0) {
    notyf.error('El kit debe incluir al menos un producto.')
    return
  }

  loading.value = true
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('nombre', form.value.nombre)
    formData.append('descripcion', form.value.descripcion || '')
    formData.append('codigo', form.value.codigo || '')
    formData.append('precio_venta', form.value.precio_venta || 0)
    formData.append('categoria_id', form.value.categoria_id || '')
    formData.append('destacado', form.value.destacado ? '1' : '0')
    if (form.value.imagen) {
      formData.append('imagen', form.value.imagen)
    }

    form.value.componentes.forEach((c, index) => {
      formData.append(`componentes[${index}][item_type]`, c.item_type)
      formData.append(`componentes[${index}][item_id]`, c.item_id)
      formData.append(`componentes[${index}][cantidad]`, c.cantidad)
      if (c.precio_unitario) {
        formData.append(`componentes[${index}][precio_unitario]`, c.precio_unitario)
      }
    })

    const response = await axios.post('/kits', formData, {
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.status === 200 || response.status === 201) {
      notyf.success('Kit creado exitosamente');
      router.visit('/kits');
    }
  } catch (error) {
    console.error('Error:', error);
    if (error.response && error.response.data) {
      if (error.response.data.errors) {
        errors.value = error.response.data.errors;
        notyf.error('Por favor corrige los errores en el formulario');
      } else {
        notyf.error(error.response.data.message || 'Error al crear el kit');
      }
    } else {
      notyf.error('Error al crear el kit');
    }
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

// Lifecycle
onMounted(() => {
  addComponent() // Agregar primer componente vacío
})

// Watchers
watch(() => form.value.precio_venta, updateMargen)
</script>

<style scoped>
/* Transiciones suaves */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
