<template>
  <div class="space-y-8">
    <!-- Información General -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Información General</h2>

      <!-- Checkbox para factura -->
      <div class="mb-6">
        <div class="flex items-center">
          <input
            type="checkbox"
            id="requiere_factura"
            v-model="form.requiere_factura"
            @change="$emit('factura-change')"
            :class="[
                  'h-4 w-4 text-blue-600 focus:ring-blue-500 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700'
                ]"
          />
          <label for="requiere_factura" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            ¿Requiere factura? <span class="text-red-500">*</span>
          </label>
        </div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Marque esta opción si el cliente necesita facturación electrónica
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <div class="mb-4">
            <label for="nombre_razon_social" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Nombre/Razón Social <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="nombre_razon_social"
              v-model="form.nombre_razon_social"
              @blur="toUpper('nombre_razon_social')"
              autocomplete="new-password"
              :class="[
                    'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                    form.errors.nombre_razon_social ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                  ]"
              required
            />
            <div
              v-if="form.requiere_factura"
              class="mt-1 text-xs text-blue-600 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 p-2 rounded border border-blue-100 dark:border-blue-700 italic"
            >
              Tip CFDI 4.0: Ingresa el nombre tal cual aparece en la Constancia de Situación Fiscal (usualmente SIN "S.A. de C.V."). El sistema intentará normalizarlo automáticamente.
            </div>
            <div v-if="form.errors.nombre_razon_social" class="mt-2 text-sm text-red-600 dark:text-red-400">
              {{ form.errors.nombre_razon_social }}
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Email <span v-if="form.requiere_factura" class="text-red-500">*</span>
            <span v-if="form.requiere_factura" class="text-gray-400 dark:text-gray-500">(requerido para facturación)</span>
          </label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            @blur="normalizeEmail"
            placeholder="correo@ejemplo.com"
            autocomplete="off"
            readonly
            onfocus="this.removeAttribute('readonly');"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.email ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
            :required="form.requiere_factura"
          />
          <div v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.email }}
          </div>
        </div>

        <!-- Contraseña (Solo Create o si usuario decide cambiar) -->
        <div class="mb-4">
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ isEdit ? 'Reseteo de Contraseña' : 'Contraseña' }}
            <span class="text-gray-400 dark:text-gray-500">({{ isEdit ? 'dejar vacío para mantener actual' : 'opcional' }})</span>
          </label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            autocomplete="new-password"
            :placeholder="isEdit ? 'Nueva contraseña' : 'Mínimo 8 caracteres'"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500"
          />
          <div v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.password }}
          </div>
        </div>

        <div class="mb-4">
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Confirmar {{ isEdit ? 'Nueva ' : '' }}Contraseña
          </label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
            autocomplete="new-password"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
        </div>

        <div class="mb-4">
          <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Teléfono <span v-if="form.requiere_factura" class="text-red-500">*</span>
            <span v-if="form.requiere_factura" class="text-gray-400 dark:text-gray-500">(requerido para facturación)</span>
          </label>
          <input
            type="tel"
            id="telefono"
            v-model="form.telefono"
            maxlength="10"
            placeholder="10 dígitos"
            autocomplete="new-password"
            :class="[
                   'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                   form.errors.telefono ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                 ]"
            :required="form.requiere_factura"
          />
          <div v-if="form.errors.telefono" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.telefono }}
          </div>

          <!-- Consentimiento de WhatsApp -->
          <div class="mt-3 flex items-center">
            <input
              type="checkbox"
              id="whatsapp_optin"
              v-model="form.whatsapp_optin"
              class="h-4 w-4 text-green-600 focus:ring-green-500 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
            />
            <label for="whatsapp_optin" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
              ¿El cliente autoriza recibir mensajes por WhatsApp?
            </label>
          </div>
        </div>
      </div>

      <!-- Lista de Precios -->
      <div class="mb-6">
        <label for="price_list_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Lista de Precios <span class="text-red-500">*</span>
        </label>
        <select
          id="price_list_id"
          v-model="form.price_list_id"
          @change="form.clearErrors('price_list_id')"
          :class="[
                'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
                form.errors.price_list_id ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
              ]"
          required
        >
          <option value="">Selecciona una lista de precios</option>
          <option
            v-for="lista in catalogs.priceLists"
            :key="lista.value"
            :value="lista.value"
          >
            {{ lista.text }}
          </option>
        </select>
        <div v-if="form.errors.price_list_id" class="mt-2 text-sm text-red-600 dark:text-red-400">
          {{ form.errors.price_list_id }}
        </div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Determina los precios que se aplicarán a este cliente en las ventas
        </div>
      </div>

      <!-- Checkbox para mostrar dirección -->
      <div class="mb-6">
        <div class="flex items-center">
          <input
            type="checkbox"
            id="mostrar_direccion"
            v-model="form.mostrar_direccion"
            :class="[
                  'h-4 w-4 text-blue-600 focus:ring-blue-500 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700'
                ]"
          />
          <label for="mostrar_direccion" class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Agregar información de dirección
          </label>
        </div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Marque esta opción si desea agregar la dirección del cliente
        </div>
      </div>
    </div>

    <!-- Estado del Cliente -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Estado del Cliente</h2>
      <div class="grid grid-cols-1 gap-6">
        <div class="mb-4">
          <label class="inline-flex items-center">
            <input
              type="checkbox"
              v-model="form.activo"
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700"
            />
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Cliente Activo</span>
          </label>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Desmarca para inactivar el cliente. Los clientes inactivos no aparecerán en listas por defecto.
          </p>
        </div>
      </div>
    </div>

    <!-- Gestión de Crédito -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Gestión de Crédito</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
          <label class="inline-flex items-center">
            <input
              type="checkbox"
              v-model="form.credito_activo"
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700"
            />
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">Habilitar Crédito</span>
          </label>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Permite realizar ventas a crédito para este cliente.
          </p>
        </div>

        <div class="mb-4">
          <label for="estado_credito" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Estado del Crédito
          </label>
          <select
            id="estado_credito"
            v-model="form.estado_credito"
            :class="[
              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-gray-200'
            ]"
          >
            <option value="sin_credito">Sin Crédito</option>
            <option value="en_revision">En Revisión 🔍</option>
            <option value="autorizado">Autorizado ✅</option>
            <option value="suspendido">Suspendido 🚫</option>
          </select>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Define el flujo de aprobación del crédito.
          </p>
        </div>

        <div v-if="form.credito_activo" class="mb-4">
          <label for="limite_credito" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Límite de Crédito
          </label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
            </div>
            <input
              type="number"
              name="limite_credito"
              id="limite_credito"
              v-model="form.limite_credito"
              class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 text-gray-900 dark:text-gray-200"
              placeholder="0.00"
              step="0.01"
              min="0"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-gray-500 dark:text-gray-400 sm:text-sm">MXN</span>
            </div>
          </div>
          <div v-if="form.errors.limite_credito" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.limite_credito }}
          </div>
        </div>

        <div v-if="form.credito_activo" class="mb-4">
          <label for="dias_credito" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Días de Crédito
          </label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <input
              type="number"
              name="dias_credito"
              id="dias_credito"
              v-model="form.dias_credito"
              class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 text-gray-900 dark:text-gray-200"
              placeholder="30"
              min="0"
              max="365"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-gray-500 dark:text-gray-400 sm:text-sm">días</span>
            </div>
          </div>
          <div v-if="form.errors.dias_credito" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.dias_credito }}
          </div>
        </div>

        <!-- Días de Gracia (Bloqueo Portal) -->
        <div class="mb-4">
          <label for="dias_gracia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Días de Gracia (Bloqueo)
          </label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <input
              type="number"
              name="dias_gracia"
              id="dias_gracia"
              v-model="form.dias_gracia"
              class="focus:ring-blue-500 focus:border-blue-500 block w-full pr-12 sm:text-sm border-gray-300 dark:border-gray-600 rounded-md dark:bg-gray-700 text-gray-900 dark:text-gray-200"
              placeholder="Automático"
              min="0"
              max="365"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-gray-500 dark:text-gray-400 sm:text-sm">días</span>
            </div>
          </div>
          <p class="mt-1 text-xs text-blue-500 dark:text-blue-400">
            <strong>Bloqueo de Portal:</strong> Días adicionales tras vencimiento antes de bloquear el acceso. Dejar vacío para usar configuración global.
          </p>
          <div v-if="form.errors.dias_gracia" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.dias_gracia }}
          </div>
        </div>
      </div>
    </div>

    <!-- Información Fiscal -->
    <div v-if="form.requiere_factura" class="border-b border-gray-200 dark:border-gray-700 pb-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Información Fiscal</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
          <label for="tipo_persona" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Tipo de Persona <span v-if="form.requiere_factura" class="text-red-500">*</span>
            <span v-if="!form.requiere_factura" class="text-gray-400 dark:text-gray-500">(opcional)</span>
          </label>
          <select
            id="tipo_persona"
            v-model="form.tipo_persona"
            @change="validateTipoPersona"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
                  form.errors.tipo_persona ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
            :required="form.requiere_factura"
          >
            <option value="">Selecciona una opción</option>
            <option
              v-for="op in catalogs.tiposPersona"
              :key="op.value"
              :value="op.value"
            >
              {{ op.label }}
            </option>
          </select>
          <div v-if="form.errors.tipo_persona" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.tipo_persona }}
          </div>
        </div>

        <div class="mb-4">
          <label for="rfc" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            RFC <span class="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="rfc"
            v-model="form.rfc"
            :maxlength="rfcMaxLength"
            placeholder="XXXX010101XXX"
            @blur="toUpper('rfc')"
            :disabled="!form.tipo_persona"
            autocomplete="new-password"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.rfc ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600',
                  !form.tipo_persona ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : ''
                ]"
            required
          />
          <div v-if="form.errors.rfc" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.rfc }}
          </div>
        </div>

        <div class="mb-4">
          <label for="curp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            CURP
          </label>
          <input
            type="text"
            id="curp"
            v-model="form.curp"
            @blur="toUpper('curp')"
            :disabled="form.tipo_persona === 'moral'"
            maxlength="18"
            :placeholder="form.tipo_persona === 'fisica' ? 'ABCD123456HMEFGH99' : 'Opcional'"
            autocomplete="new-password"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.curp ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600',
                  form.tipo_persona === 'moral' ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : ''
                ]"
          />
          <div v-if="form.errors.curp" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.curp }}
          </div>
        </div>

        <div class="mb-4">
          <label for="regimen_fiscal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Régimen Fiscal <span class="text-red-500">*</span>
          </label>
          <select
            id="regimen_fiscal"
            v-model="form.regimen_fiscal"
            :disabled="!form.tipo_persona"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
                  form.errors.regimen_fiscal ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600',
                  !form.tipo_persona ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : ''
                ]"
            required
          >
            <option value="">Selecciona una opción</option>
            <option
              v-for="regimen in regimenesFiltrados"
              :key="regimen.value"
              :value="regimen.value"
            >
              {{ regimen.label }}
            </option>
          </select>
          <div v-if="form.errors.regimen_fiscal" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.regimen_fiscal }}
          </div>
        </div>

        <div class="mb-4">
          <label for="uso_cfdi" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Uso CFDI <span class="text-red-500">*</span>
          </label>
          <select
            id="uso_cfdi"
            v-model="form.uso_cfdi"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
                  form.errors.uso_cfdi ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
            required
          >
            <option value="">Selecciona una opción</option>
            <option
              v-for="uso in catalogs.usosCFDI"
              :key="uso.value"
              :value="uso.value"
            >
              {{ uso.label }}
            </option>
          </select>
          <div v-if="form.errors.uso_cfdi" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.uso_cfdi }}
          </div>
        </div>

         <div class="mb-4">
          <label for="forma_pago_default" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Forma de Pago Preferida <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
          </label>
          <select
            id="forma_pago_default"
            v-model="form.forma_pago_default"
            :class="[
              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
              form.errors.forma_pago_default ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600',
              !form.requiere_factura ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : ''
            ]"
          >
            <option value="">Sin preferencia</option>
            <option
              v-for="fp in catalogs.formasPago"
              :key="fp.value"
              :value="fp.value"
            >
              {{ fp.label }}
            </option>
          </select>
          <div v-if="form.errors.forma_pago_default" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.forma_pago_default }}
          </div>
        </div>

        <div class="mb-4">
          <label for="domicilio_fiscal_cp" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            C.P. del Domicilio Fiscal <span v-if="form.requiere_factura" class="text-red-500">*</span>
          </label>
          <input
            type="text"
            id="domicilio_fiscal_cp"
            v-model="form.domicilio_fiscal_cp"
            maxlength="5"
            placeholder="12345"
            :required="form.requiere_factura"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.domicilio_fiscal_cp ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600',
                  !form.requiere_factura ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : ''
                ]"
          />
          <div v-if="form.errors.domicilio_fiscal_cp" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.domicilio_fiscal_cp }}
          </div>
        </div>
      </div>
    </div>

    <!-- Dirección -->
    <div v-if="form.mostrar_direccion">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Dirección</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="md:col-span-2">
          <div class="mb-4">
            <label for="calle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Calle <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
            </label>
            <input
              type="text"
              id="calle"
              v-model="form.calle"
              @blur="toUpper('calle')"
              autocomplete="new-password"
              :class="[
                    'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                    form.errors.calle ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                  ]"
            />
          </div>
        </div>

        <div class="mb-4">
          <label for="numero_exterior" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Número Exterior <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
          </label>
          <input
            type="text"
            id="numero_exterior"
            v-model="form.numero_exterior"
            @blur="toUpper('numero_exterior')"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.numero_exterior ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
          />
        </div>

        <div class="mb-4">
          <label for="numero_interior" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Número Interior
          </label>
          <input
            type="text"
            id="numero_interior"
            v-model="form.numero_interior"
            @blur="toUpper('numero_interior')"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
        </div>

        <div class="mb-4">
          <label for="codigo_postal" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Código Postal <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
          </label>
          <input
            type="text"
            id="codigo_postal"
            maxlength="5"
            v-model="form.codigo_postal"
            @input="$emit('cp-input', $event.target.value)"
            placeholder="12345"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-500',
                  form.errors.codigo_postal ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
          />
           <div v-if="isLoadingCp" class="mt-1 text-xs text-blue-600 dark:text-blue-400 flex items-center">
             <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
               <circle class="opacity-25 dark:opacity-50" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
               <path class="opacity-75 dark:opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
             </svg>
             Buscando...
           </div>
          <div v-if="form.errors.codigo_postal" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.codigo_postal }}
          </div>
        </div>

        <div class="mb-4">
          <label for="colonia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Colonia <span class="text-gray-400 dark:text-gray-500">(opcional)</span>
          </label>
          <select
            id="colonia"
            v-model="form.colonia"
            :disabled="availableColonias.length === 0"
            :class="[
                  'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200',
                  form.errors.colonia ? 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' : 'border-gray-300 dark:border-gray-600'
                ]"
          >
            <option value="">
              {{ availableColonias.length === 0 ? 'Ingresa CP primero' : 'Selecciona una colonia' }}
            </option>
            <option
              v-for="colonia in availableColonias"
              :key="colonia"
              :value="colonia"
            >
              {{ colonia }}
            </option>
          </select>
          <div v-if="form.errors.colonia" class="mt-2 text-sm text-red-600 dark:text-red-400">
            {{ form.errors.colonia }}
          </div>
        </div>

        <div class="mb-4">
          <label for="municipio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Municipio
          </label>
          <input
            type="text"
            id="municipio"
            v-model="form.municipio"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
        </div>

        <div class="mb-4">
          <label for="estado" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Estado
          </label>
          <select
            id="estado"
            v-model="form.estado"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          >
            <option value="">Selecciona una opción</option>
            <option
              v-for="estado in catalogs.estados"
              :key="estado.value"
              :value="estado.value"
            >
              {{ estado.label }}
            </option>
          </select>
        </div>

        <div class="mb-4">
          <label for="pais" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            País
          </label>
          <select
            id="pais"
            v-model="form.pais"
            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 dark:border-gray-600 focus:border-blue-500 focus:ring-blue-500 sm:text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          >
             <option value="MX">México</option>
             <option value="USA">Estados Unidos</option>
             <option value="CAN">Canadá</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  form: Object,
  catalogs: Object,
  isEdit: { type: Boolean, default: false },
  availableColonias: { type: Array, default: () => [] },
  isLoadingCp: { type: Boolean, default: false }
})

const emit = defineEmits(['factura-change', 'tipo-persona-change', 'cp-input'])

const rfcMaxLength = computed(() => {
  return props.form.tipo_persona === 'fisica' ? 13 : 12
})

const regimenesFiltrados = computed(() => {
  if (!props.form.tipo_persona) return []
  
  return props.catalogs.regimenesFiscales.filter(regimen => {
    if (props.form.tipo_persona === 'fisica') {
        // Verificar metadata persona_fisica (puede venir como booleano true/false o 1/0)
        return regimen.persona_fisica
    } else if (props.form.tipo_persona === 'moral') {
        return regimen.persona_moral
    }
    return true
  })
})

function toUpper(field) {
  if (props.form[field]) {
    props.form[field] = props.form[field].toUpperCase()
  }
}

function normalizeEmail() {
  if (props.form.email) {
    props.form.email = props.form.email.toLowerCase().trim()
  }
}

function validateTipoPersona() {
  emit('tipo-persona-change')
  // Resetear RFC/Regimen si cambia tipo?
  // props.form.regimen_fiscal = ''
}
</script>
