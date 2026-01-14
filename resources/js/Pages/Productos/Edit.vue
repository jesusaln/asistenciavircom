<template>
    <Head title="Editar Producto" />
    <div class="max-w-4xl mx-auto">
        <div class="bg-white shadow-sm rounded-lg">
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <h1 class="text-2xl font-semibold text-gray-900">Editar Producto</h1>
                <p class="text-sm text-gray-600 mt-1">Actualice la información del producto</p>
                <div class="mt-3 bg-blue-50 border border-blue-200 rounded-md p-3">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                <strong>Nota:</strong> El IVA se calcula automáticamente según la configuración de la empresa. Ingrese los precios SIN IVA.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8 px-6" aria-label="Tabs">
                    <button
                        @click="activeTab = 'general'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'general'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información General
                    </button>
                    <button
                        @click="activeTab = 'pricing'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'pricing'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Precios e Inventario
                    </button>
                    <button
                        @click="activeTab = 'additional'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'additional'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información Adicional
                    </button>
                    <button
                        @click="activeTab = 'sat'"
                        :class="[
                            'py-4 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'sat'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                        type="button"
                    >
                        Información Fiscal (SAT)
                    </button>
                </nav>
            </div>

            <!-- Form Content -->
            <form @submit.prevent="submit" class="p-6">
                <!-- Información General Tab -->
                <div v-show="activeTab === 'general'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nombre -->
                        <div>
                            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                Nombre del Producto <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.nombre"
                                type="text"
                                id="nombre"
                                placeholder="Ingrese el nombre del producto"
                                class="input-field"
                                required
                            />
                            <div v-if="form.errors.nombre" class="error-message">{{ form.errors.nombre }}</div>
                        </div>

                        <!-- Código -->
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.codigo"
                                    type="text"
                                    id="codigo"
                                    placeholder="Código del producto"
                                    class="input-field pr-12"
                                    disabled
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                El código no se puede modificar
                            </p>
                            <div v-if="form.errors.codigo" class="error-message">{{ form.errors.codigo }}</div>
                        </div>

                        <!-- Código de Barras -->
                        <div>
                            <label for="codigo_barras" class="block text-sm font-medium text-gray-700 mb-2">
                                Código de Barras <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.codigo_barras"
                                type="text"
                                id="codigo_barras"
                                placeholder="Código de barras"
                                class="input-field"
                                required
                            />
                            <div v-if="form.errors.codigo_barras" class="error-message">{{ form.errors.codigo_barras }}</div>
                        </div>

<div class="md:col-span-2">
  <div class="flex items-center space-x-3 mt-6">
    <input id="requiere_serie" type="checkbox" v-model="form.requiere_serie" class="h-4 w-4 text-blue-600 border-gray-300 rounded" />
    <label for="requiere_serie" class="text-sm text-gray-700">Este producto requiere capturar número de serie por unidad (en Compras)</label>
  </div>
</div>

                        <!-- Categoría -->
                        <div>
                            <label for="categoria_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.categoria_id" id="categoria_id" class="input-field" required>
                                <option value="">Seleccione una categoría</option>
                                <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                                    {{ categoria.nombre }}
                                </option>
                            </select>
                            <button type="button" class="mt-2 text-sm text-blue-600 hover:underline" @click="showCategoriaModal = true">+ Nueva categoría</button>
                            <div v-if="form.errors.categoria_id" class="error-message">{{ form.errors.categoria_id }}</div>
                        </div>

                        <!-- Marca -->
                        <div>
                            <label for="marca_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Marca <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.marca_id" id="marca_id" class="input-field" required>
                                <option value="">Seleccione una marca</option>
                                <option v-for="marca in marcas" :key="marca.id" :value="marca.id">
                                    {{ marca.nombre }}
                                </option>
                            </select>
                            <button type="button" class="mt-2 text-sm text-blue-600 hover:underline" @click="showMarcaModal = true">+ Nueva marca</button>
                            <div v-if="form.errors.marca_id" class="error-message">{{ form.errors.marca_id }}</div>
                        </div>

                        <!-- Proveedor -->
                        <div>
                            <label for="proveedor_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Proveedor
                            </label>
                            <select v-model="form.proveedor_id" id="proveedor_id" class="input-field">
                                <option value="">Seleccione un proveedor</option>
                                <option v-for="proveedor in proveedores" :key="proveedor.id" :value="proveedor.id">
                                    {{ proveedor.nombre_razon_social }}
                                </option>
                            </select>
                            <div v-if="form.errors.proveedor_id" class="error-message">{{ form.errors.proveedor_id }}</div>
                        </div>

                        <!-- Almacén -->
                        <div>
                             <label for="almacen_id" class="block text-sm font-medium text-gray-700 mb-2">
                                 Almacén
                             </label>
                             <select v-model="form.almacen_id" id="almacen_id" class="input-field">
                                 <option value="">Seleccione un almacén</option>
                                 <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                                     {{ almacen.nombre }}
                                 </option>
                             </select>
                             <button type="button" class="mt-2 text-sm text-blue-600 hover:underline" @click="showAlmacenModal = true">+ Nuevo almacén</button>
                             <div v-if="form.errors.almacen_id" class="error-message">{{ form.errors.almacen_id }}</div>
                         </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea
                            v-model="form.descripcion"
                            id="descripcion"
                            rows="4"
                            placeholder="Descripción detallada del producto"
                            class="input-field resize-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Precios e Inventario Tab -->
                <div v-show="activeTab === 'pricing'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Stock Mínimo por Almacén -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Mínimo por Almacén
                            </label>
                            <div class="space-y-3">
                                <div v-for="almacen in almacenes" :key="almacen.id" class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900">{{ almacen.nombre }}</span>
                                    </div>
                                    <div class="w-32">
                                        <input
                                            v-model="stockMinimoPorAlmacen[almacen.id]"
                                            type="number"
                                            :placeholder="'Mínimo para ' + almacen.nombre"
                                            class="input-field text-sm"
                                            min="0"
                                        />
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                Configure el stock mínimo específico para cada almacén. Si no especifica un valor, se mantendrá el actual.
                            </p>
                        </div>

                        <!-- Precio de Compra -->
                        <div>
                            <label for="precio_compra" class="block text-sm font-medium text-gray-700 mb-2">
                                Precio de Compra (SIN IVA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
    <input
        v-model="form.precio_compra"
        type="number"
        step="0.01"
        id="precio_compra"
        placeholder="Precio sin IVA (ej: 80.00)"
        class="input-field"
        min="0"
        required
    />
</div>
                            <div v-if="form.errors.precio_compra" class="error-message">{{ form.errors.precio_compra }}</div>
                        </div>

                        <!-- Precio de Venta -->
                        <div>
                            <label for="precio_venta" class="block text-sm font-medium text-gray-700 mb-2">
                                Precio de Venta (SIN IVA) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
    <input
        v-model="form.precio_venta"
        type="number"
        step="0.01"
        id="precio_venta"
        placeholder="Precio sin IVA (ej: 100.00)"
        class="input-field"
        min="0"
        required
    />
</div>
                            <p class="text-xs text-gray-500 mt-1">
                                El IVA se calculará automáticamente según la configuración de la empresa
                            </p>
                            <div v-if="form.errors.precio_venta" class="error-message">{{ form.errors.precio_venta }}</div>
                        </div>


                        <!-- Unidad de Medida -->
                        <div>
                            <label for="unidad_medida" class="block text-sm font-medium text-gray-700 mb-2">
                                Unidad de Medida <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.unidad_medida"
                                id="unidad_medida"
                                class="input-field"
                                :disabled="cargandoUnidades"
                                required
                            >
                                <option v-for="unidad in unidadesMedida" :key="unidad.id || unidad.nombre" :value="unidad.nombre">
                                    {{ unidad.nombre }}{{ unidad.nombre === unidadMedidaPredeterminada ? ' (Predeterminada)' : '' }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                <span class="px-2 py-0.5 bg-gray-100 rounded text-gray-700">
                                    Predeterminada: {{ unidadMedidaPredeterminada }}
                                </span>
                                <span v-if="cargandoUnidades" class="text-blue-600">Cargando opciones...</span>
                            </p>
                            <button type="button" class="mt-2 text-sm text-blue-600 hover:underline" @click="openUnidadMedidaModal">+ Gestionar unidades</button>
                            <div v-if="form.errors.unidad_medida" class="error-message">{{ form.errors.unidad_medida }}</div>
                        </div>

                        <!-- Comisión Vendedor -->
                        <div>
                            <label for="comision_vendedor" class="block text-sm font-medium text-gray-700 mb-2">
                                Comisión Vendedor (%)
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.comision_vendedor"
                                    type="number"
                                    step="0.01"
                                    id="comision_vendedor"
                                    placeholder="0.00"
                                    class="input-field pr-8"
                                    min="0"
                                    max="100"
                                />
                                <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                Porcentaje adicional que recibe el vendedor por cada venta de este producto
                            </p>
                            <div v-if="form.errors.comision_vendedor" class="error-message">{{ form.errors.comision_vendedor }}</div>
                        </div>
                    </div>

                    <!-- Margen de Ganancia (calculado automáticamente) -->
                    <div v-if="form.precio_compra && form.precio_venta" class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Análisis de Rentabilidad</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Margen:</span>
                                <span class="font-medium text-green-600 ml-2">
                                    ${{ (parseFloat(form.precio_venta) - parseFloat(form.precio_compra)).toFixed(2) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Porcentaje:</span>
                                <span class="font-medium text-blue-600 ml-2">
                                    {{ ((parseFloat(form.precio_venta) - parseFloat(form.precio_compra)) / parseFloat(form.precio_compra) * 100).toFixed(1) }}%
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-600">Precio Final (CON IVA):</span>
                                <span class="font-medium text-gray-900 ml-2">
                                    ${{ (parseFloat(form.precio_venta) * (1 + (props.defaults?.ivaPorcentaje ?? 16) / 100)).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                    </div>


                    <!-- Gestión de Precios por Lista -->
                    <PriceListManager
                        :producto-id="producto.id"
                        :base-price="parseFloat(form.precio_venta) || 0"
                        :price-lists="priceLists"
                    />
                </div>

                <!-- Información Adicional Tab -->
                <div v-show="activeTab === 'additional'" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Producto -->
                        <div>
                            <label for="tipo_producto" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Producto <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.tipo_producto" id="tipo_producto" class="input-field" required>
                                <option value="fisico">Producto Físico</option>
                                <option value="digital">Producto Digital</option>
                            </select>
                            <div v-if="form.errors.tipo_producto" class="error-message">{{ form.errors.tipo_producto }}</div>
                        </div>

                        <!-- Estado -->
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.estado" id="estado" class="input-field" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                            <div v-if="form.errors.estado" class="error-message">{{ form.errors.estado }}</div>
                        </div>
                    </div>

                    <!-- Imagen del Producto -->
                    <div>
                        <label for="imagen" class="block text-sm font-medium text-gray-700 mb-2">
                            Imagen del Producto
                        </label>
                        <div class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-gray-400 transition-colors">
                            <div class="space-y-2 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="text-sm text-gray-600">
                                    <label for="imagen" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Subir imagen</span>
                                        <input @change="handleImageUpload" id="imagen" name="imagen" type="file" accept="image/*" class="sr-only" />
                                    </label>
                                    <p class="pl-1">o arrastrar y soltar</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF hasta 10MB</p>
                                <div v-if="imagePreview" class="mt-4">
                                    <img 
                                        :src="imagePreview" 
                                        alt="Vista previa" 
                                        class="mx-auto h-32 w-32 object-cover rounded-lg border cursor-pointer hover:opacity-80 hover:ring-2 hover:ring-blue-500 transition-all" 
                                        @click="showImageModal = true"
                                        title="Click para ampliar"
                                    />
                                    <p class="text-xs text-gray-400 mt-1">Click en la imagen para ampliar</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="form.errors.imagen" class="error-message">{{ form.errors.imagen }}</div>
                    </div>
                </div>

                <!-- Información Fiscal Tab -->
                <div v-show="activeTab === 'sat'" class="space-y-6">
                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg mb-6 flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm text-blue-800 leading-relaxed">
                            Configure la información necesaria para el timbrado de facturas CFDI 4.0. Estos datos son obligatorios si planea emitir facturas electrónicas para este producto.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Clave SAT -->
                        <div class="md:col-span-2">
                            <SatClaveProdServSearch 
                                v-model="form.sat_clave_prod_serv"
                                :error="form.errors.sat_clave_prod_serv"
                                :initial-description="props.satCatalogos?.claveProdServActual?.descripcion"
                            />
                        </div>

                        <!-- Clave Unidad SAT -->
                        <div>
                            <label for="sat_clave_unidad" class="block text-sm font-medium text-gray-700 mb-2">
                                Unidad SAT <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.sat_clave_unidad" id="sat_clave_unidad" class="input-field" required>
                                <option value="">Seleccione una unidad SAT</option>
                                <option v-for="unidad in satCatalogos.unidades" :key="unidad.clave" :value="unidad.clave">
                                    {{ unidad.nombre }} ({{ unidad.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_clave_unidad" class="error-message">{{ form.errors.sat_clave_unidad }}</div>
                        </div>

                        <!-- Objeto Impuesto -->
                        <div>
                            <label for="sat_objeto_imp" class="block text-sm font-medium text-gray-700 mb-2">
                                Objeto de Impuesto <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.sat_objeto_imp" id="sat_objeto_imp" class="input-field" required>
                                <option v-for="obj in satCatalogos.objetosImp" :key="obj.clave" :value="obj.clave">
                                    {{ obj.nombre }} ({{ obj.clave }})
                                </option>
                            </select>
                            <div v-if="form.errors.sat_objeto_imp" class="error-message">{{ form.errors.sat_objeto_imp }}</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200">
                    <button
                        type="button"
                        @click="$inertia.visit(route('productos.index'))"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="form.processing">Actualizando...</span>
                        <span v-else>Actualizar Producto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
        <!-- Modales rápidos -->
        <div v-if="showCategoriaModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Nueva categoría</h3>
            <input v-model="quickCategoria.nombre" type="text" placeholder="Nombre" class="input-field mb-2" />
            <textarea v-model="quickCategoria.descripcion" placeholder="Descripción (opcional)" class="input-field mb-4"></textarea>
            <div class="flex justify-end space-x-2">
              <button class="px-3 py-2 bg-gray-200 rounded" @click="closeCategoriaModal">Cancelar</button>
              <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="crearCategoriaRapida" :disabled="savingQuick">{{ savingQuick ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </div>
        </div>

        <div v-if="showMarcaModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Nueva marca</h3>
            <input v-model="quickMarca.nombre" type="text" placeholder="Nombre" class="input-field mb-2" />
            <textarea v-model="quickMarca.descripcion" placeholder="Descripción (opcional)" class="input-field mb-4"></textarea>
            <div class="flex justify-end space-x-2">
              <button class="px-3 py-2 bg-gray-200 rounded" @click="closeMarcaModal">Cancelar</button>
              <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="crearMarcaRapida" :disabled="savingQuick">{{ savingQuick ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </div>
        </div>

        <div v-if="showAlmacenModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Nuevo almacén</h3>
            <input v-model="quickAlmacen.nombre" type="text" placeholder="Nombre" class="input-field mb-2" />
            <input v-model="quickAlmacen.ubicacion" type="text" placeholder="Ubicación/Dirección" class="input-field mb-2" />
            <textarea v-model="quickAlmacen.descripcion" placeholder="Descripción (opcional)" class="input-field mb-4"></textarea>
            <div class="flex justify-end space-x-2">
              <button class="px-3 py-2 bg-gray-200 rounded" @click="closeAlmacenModal">Cancelar</button>
              <button class="px-3 py-2 bg-blue-600 text-white rounded" @click="crearAlmacenRapido" :disabled="savingQuick">{{ savingQuick ? 'Guardando...' : 'Guardar' }}</button>
            </div>
          </div>
        </div>

        <!-- Modal de gestión de unidades de medida -->
        <UnidadMedidaModal
            :show="showUnidadMedidaModal"
            :unidades="unidadesMedida"
            :default-name="unidadMedidaPredeterminada"
            @close="showUnidadMedidaModal = false"
            @unidad-created="handleUnidadCreated"
            @unidad-updated="handleUnidadUpdated"
            @unidad-deleted="handleUnidadDeleted"
        />

        <!-- Modal para imagen ampliada -->
        <div v-if="showImageModal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50" @click="showImageModal = false">
            <div class="relative max-w-4xl max-h-[90vh] p-2">
                <button 
                    @click="showImageModal = false" 
                    class="absolute -top-10 right-0 text-white hover:text-gray-300 text-3xl font-bold"
                >
                    &times;
                </button>
                <img 
                    :src="imagePreview" 
                    alt="Imagen del producto" 
                    class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl"
                    @click.stop
                />
            </div>
        </div>

 </template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import UnidadMedidaModal from '@/Components/Modals/UnidadMedidaModal.vue';
import PriceListManager from '@/Components/PriceListManager.vue';
import SatClaveProdServSearch from '@/Components/Sat/SatClaveProdServSearch.vue';

// Define el layout del dashboard
defineOptions({ layout: AppLayout });

// Recibir las relaciones desde el backend
const props = defineProps({
    producto: Object,
    categorias: Array,
    marcas: Array,
    proveedores: Array,
    almacenes: Array,
    unidadesMedida: {
        type: Array,
        default: () => [],
    },
    priceLists: {
        type: Array,
        default: () => [],
    },
    defaults: {
        type: Object,
        default: () => ({ ivaPorcentaje: 16 }),
    },
    satCatalogos: {
        type: Object,
        default: () => ({ unidades: [], objetosImp: [] })
    }
});

// Crear referencias reactivas para poder actualizar las listas
const categorias = ref([...props.categorias]);
const marcas = ref([...props.marcas]);
const almacenes = ref([...props.almacenes]);

const unidadMedidaPredeterminada = 'Pieza';
const unidadesBase = [
    { id: 'base-pieza', nombre: unidadMedidaPredeterminada, abreviatura: 'pz' },
    { id: 'base-metro', nombre: 'Metro(s)', abreviatura: 'm' },
    { id: 'base-kilo', nombre: 'Kilogramos', abreviatura: 'kg' },
    { id: 'base-gramo', nombre: 'Gramos', abreviatura: 'g' },
    { id: 'base-litro', nombre: 'Litros', abreviatura: 'l' },
    { id: 'base-mililitro', nombre: 'Mililitros', abreviatura: 'ml' },
    { id: 'base-centimetro', nombre: 'Centimetros', abreviatura: 'cm' },
    { id: 'base-milimetro', nombre: 'Milimetros', abreviatura: 'mm' },
    { id: 'base-unidad', nombre: 'Unidad', abreviatura: 'u' },
];

// Estado para las pestañas
const activeTab = ref('general');

// Vista previa de imagen
const imagePreview = ref(null);
const showImageModal = ref(false);

// Lista de unidades de medida (se cargarán dinámicamente)
const unidadesMedida = ref([]);
const cargandoUnidades = ref(false);

// Estado para stock mínimo por almacén
const stockMinimoPorAlmacen = ref({});

// Formulario para editar un producto
const form = useForm({
    nombre: props.producto?.nombre || '',
    descripcion: props.producto?.descripcion || '',
    codigo: props.producto?.codigo || '',
    codigo_barras: props.producto?.codigo_barras || '',
    categoria_id: props.producto?.categoria_id || '',
    marca_id: props.producto?.marca_id || '',
    proveedor_id: props.producto?.proveedor_id || '',
    almacen_id: props.producto?.almacen_id || '',
    precio_compra: props.producto?.precio_compra || '',
    precio_venta: props.producto?.precio_venta || '',
    tipo_producto: props.producto?.tipo_producto || 'fisico',
    requiere_serie: props.producto?.requiere_serie ?? false,
    imagen: null,
    estado: props.producto?.estado || 'activo',
    comision_vendedor: props.producto?.comision_vendedor || '',
    unidad_medida: props.producto?.unidad_medida || unidadMedidaPredeterminada,
    sat_clave_prod_serv: props.producto?.sat_clave_prod_serv || '',
    sat_clave_unidad: props.producto?.sat_clave_unidad || 'H87',
    sat_objeto_imp: props.producto?.sat_objeto_imp || '02',
    // Stock mínimo por almacén
    stock_minimo_por_almacen: {},
});

watch(
    () => form.unidad_medida,
    (v) => {
        if (v && typeof form.clearErrors === 'function') {
            form.clearErrors('unidad_medida');
        } else if (v) {
            form.errors.unidad_medida = null;
        }
    }
);

const normalizarUnidades = (lista = []) => {
    const existentes = new Map();

    [...lista, ...unidadesBase].forEach((unidad) => {
        if (!unidad?.nombre) {
            return;
        }
        const key = unidad.nombre.toLowerCase();
        if (!existentes.has(key)) {
            existentes.set(key, {
                id: unidad.id,
                nombre: unidad.nombre,
                abreviatura: unidad.abreviatura || '',
                descripcion: unidad.descripcion || '',
                estado: unidad.estado || 'activo',
            });
        } else if (unidad.nombre === unidadMedidaPredeterminada) {
            const actual = existentes.get(key);
            existentes.set(key, {
                ...unidad,
                ...actual,
                estado: 'activo',
            });
        }
    });

    const ordenadas = Array.from(existentes.values()).sort((a, b) => (a.nombre || '').localeCompare(b.nombre || ''));

    return ordenadas.sort((a, b) => (a.nombre === unidadMedidaPredeterminada ? -1 : b.nombre === unidadMedidaPredeterminada ? 1 : 0));
};

const asegurarUnidadSeleccionada = () => {
    const existe = unidadesMedida.value.some((unidad) => unidad.nombre === form.unidad_medida);
    if (!existe) {
        form.unidad_medida = unidadMedidaPredeterminada;
    }
};

unidadesMedida.value = normalizarUnidades(props.unidadesMedida || []);
asegurarUnidadSeleccionada();

const openUnidadMedidaModal = () => {
    showUnidadMedidaModal.value = true;
    if (!unidadesMedida.value.length) {
        cargarUnidadesMedida();
    }
};

// Inicializar componente
onMounted(() => {
    cargarUnidadesMedida();
    
    // Cargar stock mínimo actual por almacén
    if (props.producto?.inventarios) {
        props.producto.inventarios.forEach(inventario => {
            stockMinimoPorAlmacen.value[inventario.almacen_id] = inventario.stock_minimo || 0;
        });
    }
    
    // Cargar imagen existente del producto si existe
    if (props.producto?.imagen) {
        imagePreview.value = `/storage/${props.producto.imagen}`;
    }
});

// Enviar formulario
const submit = () => {
    if (!form.unidad_medida || String(form.unidad_medida).trim() === '') {
        if (typeof form.setError === 'function') {
            form.setError('unidad_medida', 'Selecciona una unidad de medida.');
        } else {
            form.errors.unidad_medida = 'Selecciona una unidad de medida.';
        }
        return;
    }

    // Preparar stock mínimo por almacén
    form.stock_minimo_por_almacen = {};
    for (const [almacenId, stockMinimo] of Object.entries(stockMinimoPorAlmacen.value)) {
        if (stockMinimo && parseInt(stockMinimo) > 0) {
            form.stock_minimo_por_almacen[almacenId] = parseInt(stockMinimo);
        }
    }

    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('productos.update', props.producto.id), {
        forceFormData: true,
        onSuccess: () => {
            // Producto actualizado correctamente
        },
    });
};

// Manejar carga de imágenes
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.imagen = file;

        // Crear vista previa
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Modales rápidos
const showCategoriaModal = ref(false)
const showMarcaModal = ref(false)
const showAlmacenModal = ref(false)
const showUnidadMedidaModal = ref(false)
const savingQuick = ref(false)

const quickCategoria = ref({ nombre: '', descripcion: '' })
const quickMarca = ref({ nombre: '', descripcion: '' })
const quickAlmacen = ref({ nombre: '', ubicacion: '', descripcion: '' })

const csrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content')

const closeCategoriaModal = () => { showCategoriaModal.value = false; quickCategoria.value = { nombre: '', descripcion: '' } }
const closeMarcaModal = () => { showMarcaModal.value = false; quickMarca.value = { nombre: '', descripcion: '' } }
const closeAlmacenModal = () => { showAlmacenModal.value = false; quickAlmacen.value = { nombre: '', ubicacion: '', descripcion: '' } }

const crearCategoriaRapida = async () => {
  if (!quickCategoria.value.nombre?.trim()) return;
  savingQuick.value = true;

  try {
    const apiUrl = `${window.location.origin}/api/categorias`;
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        nombre: quickCategoria.value.nombre.trim(),
        descripcion: quickCategoria.value.descripcion?.trim() || null
      })
    });

    if (!response.ok) {
      throw new Error(`Error HTTP ${response.status}`);
    }

    const nuevaCategoria = await response.json();
    categorias.value.push(nuevaCategoria);
    form.categoria_id = nuevaCategoria.id;
    closeCategoriaModal();
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Categoría creada exitosamente'
    });

  } catch (error) {
    console.error('Error creando categoría:', error);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al crear la categoría. Por favor, inténtelo de nuevo.'
    });
  } finally {
    savingQuick.value = false;
  }
}

const crearMarcaRapida = async () => {
  if (!quickMarca.value.nombre?.trim()) return;
  savingQuick.value = true;

  try {
    const apiUrl = `${window.location.origin}/api/marcas`;
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        nombre: quickMarca.value.nombre.trim(),
        descripcion: quickMarca.value.descripcion?.trim() || null
      })
    });

    if (!response.ok) {
      throw new Error(`Error HTTP ${response.status}`);
    }

    const nuevaMarca = await response.json();
    marcas.value.push(nuevaMarca);
    form.marca_id = nuevaMarca.id;
    closeMarcaModal();
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Marca creada exitosamente'
    });

  } catch (error) {
    console.error('Error creando marca:', error);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al crear la marca. Por favor, inténtelo de nuevo.'
    });
  } finally {
    savingQuick.value = false;
  }
}

const crearAlmacenRapido = async () => {
  if (!quickAlmacen.value.nombre?.trim()) return;
  savingQuick.value = true;

  try {
    const apiUrl = `${window.location.origin}/api/almacenes`;
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        nombre: quickAlmacen.value.nombre.trim(),
        descripcion: quickAlmacen.value.descripcion?.trim() || null,
        ubicacion: quickAlmacen.value.ubicacion?.trim() || ''
      })
    });

    if (!response.ok) {
      throw new Error(`Error HTTP ${response.status}`);
    }

    const nuevoAlmacen = await response.json();
    almacenes.value.push(nuevoAlmacen);
    form.almacen_id = nuevoAlmacen.id;
    closeAlmacenModal();
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: 'Almacén creado exitosamente'
    });

  } catch (error) {
    console.error('Error creando almacén:', error);
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al crear el almacén. Por favor, inténtelo de nuevo.'
    });
  } finally {
    savingQuick.value = false;
  }
}

// Cargar unidades de medida
const cargarUnidadesMedida = async () => {
   cargandoUnidades.value = true;
   try {
       const apiUrl = `${window.location.origin}/api/unidades-medida/activas`;
       const response = await fetch(apiUrl, {
           method: 'GET',
           headers: {
               'X-Requested-With': 'XMLHttpRequest',
               'Accept': 'application/json'
           }
       });

       if (!response.ok) {
           throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
       }

         const data = await response.json();
         unidadesMedida.value = normalizarUnidades(data.data || []);
         asegurarUnidadSeleccionada();

   } catch (error) {
       console.error('Error cargando unidades de medida:', error);
       // Fallback a unidades básicas si falla la carga
       unidadesMedida.value = normalizarUnidades([]);
       asegurarUnidadSeleccionada();
   } finally {
       cargandoUnidades.value = false;
   }
};

// Funciones para manejar eventos del modal de unidades
const handleUnidadCreated = (unidad) => {
   unidadesMedida.value = normalizarUnidades([...unidadesMedida.value, unidad]);
   form.unidad_medida = unidad?.nombre || unidadMedidaPredeterminada;
   showUnidadMedidaModal.value = false;
   asegurarUnidadSeleccionada();
};

const handleUnidadUpdated = (unidad) => {
   const index = unidadesMedida.value.findIndex(u => u.id === unidad.id);
   if (index !== -1) {
       unidadesMedida.value[index] = unidad;
   }
   unidadesMedida.value = normalizarUnidades(unidadesMedida.value);
   asegurarUnidadSeleccionada();
};

const handleUnidadDeleted = (unidad) => {
   unidadesMedida.value = normalizarUnidades(unidadesMedida.value.filter(u => u.id !== unidad.id));
   if (form.unidad_medida === unidad?.nombre) {
       form.unidad_medida = unidadMedidaPredeterminada;
   }
   asegurarUnidadSeleccionada();
};
</script>

<style scoped>
@import "tailwindcss" reference;
.input-field {
    width: 100%;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    border-width: 1px;
    border-style: solid;
    border-color: #D1D5DB; /* gray-300 */
    border-radius: 0.375rem; /* rounded-md */
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm approximation */
    color: #111827; /* text-gray-900 */
    outline: none;
    background-color: #fff;
    -webkit-appearance: none;
    appearance: none;
}

.input-field::placeholder {
    color: #9CA3AF; /* placeholder-gray-400 */
}

.input-field:focus {
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12); /* subtle ring approximation */
    border-color: #3B82F6; /* focus:border-blue-500 */
}

.input-field option {
    color: #111827; /* text-gray-900 */
    background-color: #ffffff;
}

.error-message {
    margin-top: 0.25rem; /* mt-1 */
    font-size: 0.875rem; /* text-sm */
    color: #DC2626; /* text-red-600 */
}
</style>



