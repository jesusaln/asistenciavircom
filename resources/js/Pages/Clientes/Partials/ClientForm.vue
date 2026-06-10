<template>
  <div class="space-y-6">
    <!-- Información General -->
    <div v-if="showSection('general')" class="border-b border-slate-200 dark:border-slate-700 pb-6">
      <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Información General</h2>

      <!-- Checkbox para factura -->
      <div class="mb-6">
        <div class="flex items-center">
          <input
            type="checkbox"
            id="requiere_factura"
            v-model="form.requiere_factura"
            @change="$emit('factura-change')"
            :class="[
                  'h-4 w-4 text-blue-600 focus:ring-brand-500 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-700'
                ]"
          />
          <label for="requiere_factura" class="ml-2 block text-sm font-medium text-slate-700 dark:text-slate-200">
            ¿Requiere factura? <span class="text-rose-500">*</span>
          </label>
        </div>
        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Marque esta opción si el cliente necesita facturación electrónica
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
          <div class="mb-4">
            <label for="nombre_razon_social" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Nombre/Razón Social <span class="text-rose-500">*</span>
            </label>
            <input
              type="text"
              id="nombre_razon_social"
              v-model="form.nombre_razon_social"
              @blur="toUpper('nombre_razon_social')"
              autocomplete="new-password"
              :class="[
                    'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                    form.errors.nombre_razon_social ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
                  ]"
              required
            />
            <div
              v-if="form.requiere_factura"
              class="mt-1 text-xs text-blue-600 dark:text-blue-300 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 p-2 rounded-xl border border-blue-100 dark:border-blue-700 italic"
            >
              Tip CFDI 4.0: Ingresa el nombre tal cual aparece en la Constancia de Situación Fiscal (usualmente SIN "S.A. de C.V."). El sistema intentará normalizarlo automáticamente.
            </div>
            <div v-if="form.errors.nombre_razon_social" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
              {{ form.errors.nombre_razon_social }}
            </div>
          </div>
        </div>



        <div class="mb-4">
          <label for="telefono" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Teléfono <span v-if="form.requiere_factura" class="text-rose-500">*</span>
            <span v-if="form.requiere_factura" class="text-slate-400 dark:text-slate-500">(requerido para facturación)</span>
          </label>
          <input
            type="tel"
            id="telefono"
            v-model="form.telefono"
            maxlength="10"
            placeholder="6621234567"
            autocomplete="tel"
            inputmode="numeric"
            pattern="[0-9]{10}"
            title="Ingresa 10 dígitos numéricos"
            @blur="cleanPhone"
            :class="[
                   'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                   form.errors.telefono ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
                 ]"
          />
          <div v-if="form.errors.telefono" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.telefono }}
          </div>
          <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            10 dígitos sin espacios ni guiones.
          </div>

          <!-- Consentimiento de WhatsApp -->
          <div class="mt-3 flex items-center">
            <input
              type="checkbox"
              id="whatsapp_optin"
              v-model="form.whatsapp_optin"
              class="h-4 w-4 text-emerald-600 focus:ring-brand-500 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-700"
            />
            <label for="whatsapp_optin" class="ml-2 block text-sm text-slate-700 dark:text-slate-200">
              ¿El cliente autoriza recibir mensajes por WhatsApp?
            </label>
          </div>

          <div class="mt-3 flex items-center">
            <input
              type="checkbox"
              id="marketing_optin"
              v-model="form.marketing_optin"
              class="h-4 w-4 text-indigo-600 focus:ring-brand-500 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-700"
            />
            <label for="marketing_optin" class="ml-2 block text-sm text-slate-700 dark:text-slate-200">
              ¿El cliente autoriza recibir campañas y promociones?
            </label>
          </div>
        </div>
      </div>

      <!-- Lista de Precios -->
      <div class="mb-6">
        <label for="price_list_id" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Lista de Precios <span class="text-rose-500">*</span>
        </label>
        <select
          id="price_list_id"
          v-model="form.price_list_id"
          @change="form.clearErrors('price_list_id')"
          :class="[
                'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
                form.errors.price_list_id ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
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
        <div v-if="form.errors.price_list_id" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
          {{ form.errors.price_list_id }}
        </div>
        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Determina los precios que se aplicarán a este cliente en las ventas
        </div>
      </div>


    </div>

    <!-- Estado del Cliente (solo en edición) -->
    <div v-if="showSection('status') && isEdit" class="border-b border-slate-200 dark:border-slate-700 pb-6">
      <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Estado del Cliente</h2>
      <div class="grid grid-cols-1 gap-6">
        <div class="mb-4">
          <label class="inline-flex items-center">
            <input
              type="checkbox"
              v-model="form.activo"
              class="rounded-xl border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-700"
            />
            <span class="ml-2 text-sm font-medium text-slate-700 dark:text-slate-200">Cliente Activo</span>
          </label>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Desmarca para inactivar el cliente. Los clientes inactivos no aparecerán en listas por defecto.
          </p>
        </div>
      </div>
    </div>

    <!-- Gestión de Crédito y Portal -->
    <div v-if="showSection('credit')" class="border-b border-slate-200 dark:border-slate-700 pb-6">
      <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Portal del Cliente y Crédito</h2>

      <!-- Acceso al Portal -->
      <div class="mb-6 rounded-xl border border-blue-100 bg-sky-50 dark:bg-sky-900/20/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/20">
        <h3 class="text-sm font-bold text-sky-800 dark:text-sky-200 dark:text-blue-300 mb-3">Acceso al Portal</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Email
              <span v-if="form.requiere_factura" class="text-rose-500">*</span>
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
                'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                form.errors.email ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
              ]"
            />
            <div v-if="form.errors.email" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ form.errors.email }}</div>
          </div>
          <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              {{ isEdit ? 'Reseteo de Contraseña' : 'Contraseña' }}
              <span class="text-slate-400 dark:text-slate-500 text-xs">({{ isEdit ? 'vacío = sin cambio' : 'opcional' }})</span>
            </label>
            <input
              type="password"
              id="password"
              v-model="form.password"
              autocomplete="new-password"
              :placeholder="isEdit ? 'Nueva contraseña' : 'Mínimo 8 caracteres'"
              class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400"
            />
            <div v-if="form.errors.password" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ form.errors.password }}</div>
          </div>
          <div v-if="form.password">
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Confirmar Contraseña
            </label>
            <input
              type="password"
              id="password_confirmation"
              v-model="form.password_confirmation"
              autocomplete="new-password"
              class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
            />
          </div>
        </div>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">El email y contraseña permiten al cliente acceder al portal de clientes.</p>
      </div>

      <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-3">Condiciones de Crédito</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
          <label class="inline-flex items-center">
            <input
              type="checkbox"
              v-model="form.credito_activo"
              class="rounded-xl border-slate-300 dark:border-slate-700 text-blue-600 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:bg-slate-700"
            />
            <span class="ml-2 text-sm font-medium text-slate-700 dark:text-slate-200">Habilitar Crédito</span>
          </label>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Permite realizar ventas a crédito para este cliente.
          </p>
        </div>

        <div class="mb-4">
          <label for="estado_credito" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Estado del Crédito
          </label>
          <select
            id="estado_credito"
            v-model="form.estado_credito"
            :class="[
              'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm border-slate-300 dark:border-slate-700 dark:bg-slate-700 text-slate-900 dark:text-slate-200'
            ]"
          >
            <option value="sin_credito">Sin Crédito</option>
            <option value="en_revision">En Revisión 🔍</option>
            <option value="autorizado">Autorizado ✅</option>
            <option value="suspendido">Suspendido 🚫</option>
          </select>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Define el flujo de aprobación del crédito.
          </p>
        </div>

        <div v-if="form.credito_activo" class="mb-4">
          <label for="limite_credito" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Límite de Crédito
          </label>
          <div class="mt-1 relative rounded-xl shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-slate-500 dark:text-slate-400 sm:text-sm">$</span>
            </div>
            <input
              type="number"
              name="limite_credito"
              id="limite_credito"
              v-model="form.limite_credito"
              class="focus:ring-brand-500 focus:border-brand-500 block w-full pl-7 pr-12 sm:text-sm border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              placeholder="0.00"
              step="0.01"
              min="0"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-slate-500 dark:text-slate-400 sm:text-sm">MXN</span>
            </div>
          </div>
          <div v-if="form.errors.limite_credito" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.limite_credito }}
          </div>
        </div>

        <div v-if="form.credito_activo" class="mb-4">
          <label for="dias_credito" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Días de Crédito
          </label>
          <div class="mt-1 relative rounded-xl shadow-sm">
            <input
              type="number"
              name="dias_credito"
              id="dias_credito"
              v-model="form.dias_credito"
              class="focus:ring-brand-500 focus:border-brand-500 block w-full pr-12 sm:text-sm border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              placeholder="30"
              min="0"
              max="365"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-slate-500 dark:text-slate-400 sm:text-sm">días</span>
            </div>
          </div>
          <div v-if="form.errors.dias_credito" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.dias_credito }}
          </div>
        </div>

        <!-- Días de Gracia (Bloqueo Portal) -->
        <div class="mb-4">
          <label for="dias_gracia" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Días de Gracia (Bloqueo)
          </label>
          <div class="mt-1 relative rounded-xl shadow-sm">
            <input
              type="number"
              name="dias_gracia"
              id="dias_gracia"
              v-model="form.dias_gracia"
              class="focus:ring-brand-500 focus:border-brand-500 block w-full pr-12 sm:text-sm border-slate-300 dark:border-slate-700 rounded-xl dark:bg-slate-700 text-slate-900 dark:text-slate-200"
              placeholder="Automático"
              min="0"
              max="365"
            />
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <span class="text-slate-500 dark:text-slate-400 sm:text-sm">días</span>
            </div>
          </div>
          <p class="mt-1 text-xs text-blue-500 dark:text-blue-400">
            <strong>Bloqueo de Portal:</strong> Días adicionales tras vencimiento antes de bloquear el acceso. Dejar vacío para usar configuración global.
          </p>
          <div v-if="form.errors.dias_gracia" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.dias_gracia }}
          </div>
        </div>
      </div>
    </div>

    <!-- Información Fiscal -->
    <div v-if="showSection('fiscal') && form.requiere_factura" class="border-b border-slate-200 dark:border-slate-700 pb-6">
      <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Información Fiscal</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="mb-4">
          <label for="tipo_persona" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Tipo de Persona <span v-if="form.requiere_factura" class="text-rose-500">*</span>
            <span v-if="!form.requiere_factura" class="text-slate-400 dark:text-slate-500">(opcional)</span>
          </label>
          <select
            id="tipo_persona"
            v-model="form.tipo_persona"
            @change="validateTipoPersona"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
                  form.errors.tipo_persona ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
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
          <div v-if="form.errors.tipo_persona" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.tipo_persona }}
          </div>
        </div>

        <div class="mb-4">
          <label for="rfc" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            RFC <span class="text-rose-500">*</span>
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
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                  form.errors.rfc ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700',
                  !form.tipo_persona ? 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500' : ''
                ]"
            required
          />
          <div v-if="form.errors.rfc" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.rfc }}
          </div>
        </div>

        <div class="mb-4">
          <label for="curp" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
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
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                  form.errors.curp ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700',
                  form.tipo_persona === 'moral' ? 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500' : ''
                ]"
          />
          <div v-if="form.errors.curp" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.curp }}
          </div>
        </div>

        <div class="mb-4">
          <label for="regimen_fiscal" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Régimen Fiscal <span class="text-rose-500">*</span>
          </label>
          <select
            id="regimen_fiscal"
            v-model="form.regimen_fiscal"
            :disabled="!form.tipo_persona"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
                  form.errors.regimen_fiscal ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700',
                  !form.tipo_persona ? 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500' : ''
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
          <div v-if="form.errors.regimen_fiscal" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.regimen_fiscal }}
          </div>
        </div>

        <div class="mb-4">
          <label for="uso_cfdi" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Uso CFDI <span class="text-rose-500">*</span>
          </label>
          <select
            id="uso_cfdi"
            v-model="form.uso_cfdi"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
                  form.errors.uso_cfdi ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
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
          <div v-if="form.errors.uso_cfdi" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.uso_cfdi }}
          </div>
        </div>

         <div class="mb-4">
          <label for="forma_pago_default" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Forma de Pago Preferida <span class="text-slate-400 dark:text-slate-500">(opcional)</span>
          </label>
          <select
            id="forma_pago_default"
            v-model="form.forma_pago_default"
            :class="[
              'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
              form.errors.forma_pago_default ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700',
              !form.requiere_factura ? 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500' : ''
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
          <div v-if="form.errors.forma_pago_default" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.forma_pago_default }}
          </div>
        </div>

        <div class="mb-4">
          <label for="domicilio_fiscal_cp" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            C.P. del Domicilio Fiscal <span v-if="form.requiere_factura" class="text-rose-500">*</span>
          </label>
          <input
            type="text"
            id="domicilio_fiscal_cp"
            v-model="form.domicilio_fiscal_cp"
            maxlength="5"
            placeholder="12345"
            :required="form.requiere_factura"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                  form.errors.domicilio_fiscal_cp ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700',
                  !form.requiere_factura ? 'bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500' : ''
                ]"
          />
          <div v-if="form.errors.domicilio_fiscal_cp" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.domicilio_fiscal_cp }}
          </div>
        </div>
      </div>
    </div>

    <!-- Dirección -->
    <div v-if="showSection('address')" class="border-b border-slate-200 dark:border-slate-700 pb-6 pt-2">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">Dirección</h2>
        <div class="mt-2 sm:mt-0 flex items-center bg-[var(--ui-surface)] dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition" @click="form.mostrar_direccion = !form.mostrar_direccion">
          <input
            type="checkbox"
            id="mostrar_direccion_toggle"
            v-model="form.mostrar_direccion"
            @click.stop
            class="h-4 w-4 text-blue-600 focus:ring-brand-500 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-700 cursor-pointer"
          />
          <label for="mostrar_direccion_toggle" class="ml-2 block text-sm font-medium text-slate-700 dark:text-slate-200 cursor-pointer" @click.stop>
            Agregar información de dirección
          </label>
        </div>
      </div>

      <div v-if="form.mostrar_direccion" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-4 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-xl flex-1">
        <div class="md:col-span-2">
          <div class="mb-4">
            <label for="calle" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
              Calle <span class="text-rose-500">*</span>
          </label>
          <input
            type="text"
            id="calle"
            v-model="form.calle"
            @blur="toUpper('calle')"
            autocomplete="new-password"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                  form.errors.calle ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
                ]"
            required
          />
        </div>
      </div>

      <div class="mb-4">
        <label for="numero_exterior" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Número Exterior <span class="text-rose-500">*</span>
        </label>
        <input
          type="text"
          id="numero_exterior"
          v-model="form.numero_exterior"
          @blur="toUpper('numero_exterior')"
          :class="[
                'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                form.errors.numero_exterior ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
              ]"
          required
        />
      </div>

      <div class="mb-4">
        <label for="numero_interior" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Número Interior <span class="text-slate-400 dark:text-slate-500">(opcional)</span>
        </label>
        <input
          type="text"
          id="numero_interior"
          v-model="form.numero_interior"
          @blur="toUpper('numero_interior')"
          class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
        />
      </div>

      <div class="mb-4">
        <label for="codigo_postal" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Código Postal <span class="text-rose-500">*</span>
        </label>
        <input
          type="text"
          id="codigo_postal"
          maxlength="5"
          v-model="form.codigo_postal"
          @input="$emit('cp-input', $event.target.value)"
          placeholder="12345"
          required
          :class="[
                'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                form.errors.codigo_postal ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
              ]"
        />
         <div v-if="isLoadingCp" class="mt-1 text-xs text-blue-600 dark:text-blue-400 flex items-center">
           <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
             <circle class="opacity-25 dark:opacity-50" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
             <path class="opacity-75 dark:opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
           </svg>
           Buscando...
         </div>
        <div v-if="form.errors.codigo_postal" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
          {{ form.errors.codigo_postal }}
        </div>
      </div>

      <div class="mb-4">
        <label for="colonia" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Colonia <span class="text-rose-500">*</span>
        </label>
        <select
          v-if="availableColonias.length > 0 && !isColoniaManual"
          id="colonia"
          v-model="form.colonia"
          required
          :class="[
                'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200',
                form.errors.colonia ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
              ]"
        >
            <option value="">Selecciona una colonia</option>
            <option
              v-for="colonia in availableColonias"
              :key="colonia"
              :value="colonia"
            >
              {{ colonia }}
            </option>
          </select>
          <input
            v-else
            id="colonia"
            v-model="form.colonia"
            type="text"
            required
            @blur="toUpper('colonia')"
            placeholder="Captura manualmente la colonia"
            :class="[
                  'mt-1 block w-full rounded-xl shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 placeholder-slate-300 dark:placeholder-slate-400',
                  form.errors.colonia ? 'border-rose-300 dark:border-rose-600 bg-rose-50 dark:bg-rose-900/20' : 'border-slate-300 dark:border-slate-700'
                ]"
          />
          <div v-if="form.errors.colonia" class="mt-2 text-sm text-rose-600 dark:text-rose-400">
            {{ form.errors.colonia }}
          </div>
          <!-- Toggle entre manual y dropdown -->
          <div class="mt-1 flex items-center gap-2">
            <button
              v-if="availableColonias.length > 0"
              type="button"
              @click="isColoniaManual = !isColoniaManual; if(isColoniaManual) form.colonia = ''"
              class="text-xs text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 underline"
            >
              {{ isColoniaManual ? 'Seleccionar de la lista' : 'Escribir manualmente' }}
            </button>
            <span v-else class="text-xs text-slate-500 dark:text-slate-400">
              Ingresa el código postal para ver colonias disponibles, o escribe manualmente.
            </span>
          </div>
        </div>

        <div class="mb-4">
          <label for="municipio" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Municipio <span class="text-rose-500">*</span>
          </label>
          <input
            type="text"
            id="municipio"
            v-model="form.municipio"
            required
            class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
          />
        </div>

        <div class="mb-4">
          <label for="estado" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            Estado <span class="text-rose-500">*</span>
          </label>
          <select
            id="estado"
            v-model="form.estado"
            required
            class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
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
          <label for="pais" class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            País
          </label>
          <select
            id="pais"
            v-model="form.pais"
            class="mt-1 block w-full rounded-xl shadow-sm border-slate-300 dark:border-slate-700 focus:border-brand-500 focus:ring-brand-500 sm:text-sm bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200"
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
import { computed, ref, watch } from 'vue'

const props = defineProps({
  form: Object,
  catalogs: Object,
  isEdit: { type: Boolean, default: false },
  availableColonias: { type: Array, default: () => [] },
  isLoadingCp: { type: Boolean, default: false },
  visibleSections: { type: Array, default: () => ['general', 'status', 'credit', 'fiscal', 'address'] }
})

const emit = defineEmits(['factura-change', 'tipo-persona-change', 'cp-input'])

const isColoniaManual = ref(false)

watch(() => props.availableColonias, (newColonias) => {
    if (newColonias.length > 0) {
        if (props.form.colonia && !newColonias.includes(props.form.colonia)) {
            isColoniaManual.value = true
        } else {
            isColoniaManual.value = false
        }
    } else {
        isColoniaManual.value = true
    }
}, { immediate: true })

const rfcMaxLength = computed(() => {
  return props.form.tipo_persona === 'fisica' ? 13 : 12
})

function showSection(section) {
  return props.visibleSections.includes(section)
}

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

function cleanPhone() {
  if (props.form.telefono) {
    // Remove non-digits and keep first 10
    props.form.telefono = props.form.telefono.replace(/\D/g, '').slice(0, 10);
  }
}

function validateTipoPersona() {
  emit('tipo-persona-change')
  // Resetear RFC/Regimen si cambia tipo?
  // props.form.regimen_fiscal = ''
}
</script>
