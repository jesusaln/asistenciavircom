<template>
  <div class="space-y-6">
    <header class="mb-6">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
        <FontAwesomeIcon icon="globe" class="mr-3 text-blue-600 dark:text-blue-400" />
        Configuración de Red y Dominio
      </h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Configure el dominio, direcciones IP y certificados SSL de su sistema.
      </p>
    </header>

    <!-- Dominio Principal -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
      <h3 class="text-md font-semibold text-gray-700 dark:text-gray-100 mb-4 flex items-center">
        <FontAwesomeIcon icon="server" class="mr-2 text-indigo-500 dark:text-indigo-400" />
        Configuración de Dominio
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Dominio Principal
          </label>
          <input
            v-model="form.dominio_principal"
            type="text"
            placeholder="ej: admin.climasdeldesierto.com"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dominio principal del sistema</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Dominio Secundario
          </label>
          <input
            v-model="form.dominio_secundario"
            type="text"
            placeholder="ej: www.climasdeldesierto.com"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dominio alternativo o secundario</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            URL de la Aplicación
          </label>
          <input
            v-model="form.app_url"
            type="text"
            placeholder="ej: https://admin.climasdeldesierto.com"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">URL base completa (con https://)</p>
        </div>

        <div class="flex items-center">
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.force_https"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">Forzar HTTPS</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Direcciones IP -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
      <h3 class="text-md font-semibold text-gray-700 dark:text-gray-100 mb-4 flex items-center">
        <FontAwesomeIcon icon="network-wired" class="mr-2 text-green-500 dark:text-green-400" />
        Direcciones IP del Servidor
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            IPv4
          </label>
          <input
            v-model="form.servidor_ipv4"
            type="text"
            placeholder="ej: 192.168.1.100"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dirección IPv4 del servidor</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            IPv6
          </label>
          <input
            v-model="form.servidor_ipv6"
            type="text"
            placeholder="ej: 2001:0db8:85a3:0000:0000:8a2e:0370:7334"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dirección IPv6 del servidor (opcional)</p>
        </div>
      </div>
    </div>

    <!-- Configuración SSL -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
      <h3 class="text-md font-semibold text-gray-700 dark:text-gray-100 mb-4 flex items-center">
        <FontAwesomeIcon icon="lock" class="mr-2 text-yellow-500 dark:text-yellow-400" />
        Certificado SSL
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex items-center col-span-2">
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.ssl_enabled"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">SSL Habilitado</span>
          </label>
          <span v-if="form.ssl_enabled" class="ml-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300">
            <FontAwesomeIcon icon="check-circle" class="mr-1" />
            Conexión Segura
          </span>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Proveedor del Certificado
          </label>
          <select
            v-model="form.ssl_proveedor"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          >
            <option value="">Seleccionar...</option>
            <option value="lets_encrypt">Let's Encrypt</option>
            <option value="comodo">Comodo</option>
            <option value="digicert">DigiCert</option>
            <option value="globalsign">GlobalSign</option>
            <option value="godaddy">GoDaddy</option>
            <option value="cloudflare">Cloudflare</option>
            <option value="hostinger">Hostinger</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Fecha de Expiración
          </label>
          <input
            v-model="form.ssl_fecha_expiracion"
            type="date"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p v-if="diasParaExpirar !== null" class="text-xs mt-1" :class="diasParaExpirarClass">
            <FontAwesomeIcon :icon="diasParaExpirar <= 30 ? 'exclamation-triangle' : 'calendar'" class="mr-1" />
            {{ diasParaExpirarTexto }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Ruta del Certificado (.crt)
          </label>
          <input
            v-model="form.ssl_certificado_path"
            type="text"
            placeholder="ej: /etc/ssl/certs/domain.crt"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Ruta de la Llave Privada (.key)
          </label>
          <input
            v-model="form.ssl_key_path"
            type="text"
            placeholder="ej: /etc/ssl/private/domain.key"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
        </div>

        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Ruta del CA Bundle
          </label>
          <input
            v-model="form.ssl_ca_bundle_path"
            type="text"
            placeholder="ej: /etc/ssl/certs/ca-bundle.crt"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Archivo de cadena de certificados (opcional)</p>
        </div>
      </div>
    </div>

    <!-- Configuración ZeroTier VPN -->
    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-700">
      <h3 class="text-md font-semibold text-gray-700 dark:text-gray-100 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-500 dark:text-orange-400" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
        </svg>
        ZeroTier VPN
        <span class="ml-2 text-xs bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 px-2 py-0.5 rounded-full">Acceso sin abrir puertos</span>
      </h3>
      
      <div class="mb-4 p-3 bg-orange-100 dark:bg-orange-900/20 rounded-lg">
        <p class="text-xs text-orange-800 dark:text-orange-300">
          <strong>¿Cuándo usar ZeroTier?</strong> Si no puedes abrir puertos en tu router o tienes IP dinámica, 
          ZeroTier te permite acceder al sistema desde cualquier lugar de forma segura sin configurar DNS público.
        </p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="flex items-center col-span-2">
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              v-model="form.zerotier_enabled"
              type="checkbox"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">Habilitar ZeroTier</span>
          </label>
          <span v-if="form.zerotier_enabled" class="ml-4 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300">
            <FontAwesomeIcon icon="check-circle" class="mr-1" />
            VPN Activa
          </span>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Network ID
          </label>
          <input
            v-model="form.zerotier_network_id"
            type="text"
            placeholder="ej: a1b2c3d4e5f6g7h8"
            maxlength="16"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 font-mono tracking-wider bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ID de 16 caracteres de tu red ZeroTier</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            IP ZeroTier del Servidor
          </label>
          <input
            v-model="form.zerotier_ip"
            type="text"
            placeholder="ej: 10.147.20.100"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 font-mono bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">IP asignada a este servidor en la red ZeroTier</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Node ID (opcional)
          </label>
          <input
            v-model="form.zerotier_node_id"
            type="text"
            placeholder="ej: abc123def4"
            maxlength="10"
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 font-mono bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Identificador del nodo ZeroTier</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            URL de Acceso ZeroTier
          </label>
          <div class="flex items-center">
            <input
              :value="zerotierUrl"
              type="text"
              readonly
              class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 shadow-sm font-mono text-sm text-gray-900 dark:text-gray-200"
            />
            <button 
              v-if="zerotierUrl"
              @click="copyZerotierUrl"
              type="button"
              class="ml-2 px-3 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
              title="Copiar URL"
            >
              <FontAwesomeIcon icon="copy" />
            </button>
          </div>
        </div>

        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Notas de Configuración
          </label>
          <textarea
            v-model="form.zerotier_notas"
            rows="2"
            placeholder="Notas adicionales sobre la configuración de ZeroTier..."
            class="w-full rounded-lg border-gray-300 dark:border-gray-600 shadow-sm focus:border-orange-500 focus:ring-orange-500 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Información de DNS -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
      <div class="flex">
        <FontAwesomeIcon icon="info-circle" class="text-blue-500 dark:text-blue-400 mt-1 mr-3" />
        <div>
          <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">Configuración DNS</h4>
          <p class="text-xs text-blue-700 dark:text-blue-200 mt-1">
            Para que su dominio funcione correctamente, asegúrese de configurar los registros DNS:
          </p>
          <ul class="text-xs text-blue-600 dark:text-blue-300 mt-2 space-y-1 list-disc list-inside">
            <li><strong>Registro A:</strong> Apunte su dominio a la IPv4 del servidor</li>
            <li><strong>Registro AAAA:</strong> Apunte su dominio a la IPv6 (si aplica)</li>
            <li><strong>Registro CNAME:</strong> Para subdominios que apunten al dominio principal</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  form: { type: Object, required: true }
})

const diasParaExpirar = computed(() => {
  if (!props.form.ssl_fecha_expiracion) return null
  const hoy = new Date()
  const expiracion = new Date(props.form.ssl_fecha_expiracion)
  const diff = expiracion - hoy
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
})

const diasParaExpirarClass = computed(() => {
  if (diasParaExpirar.value === null) return 'text-gray-500'
  if (diasParaExpirar.value <= 0) return 'text-red-600 font-semibold'
  if (diasParaExpirar.value <= 30) return 'text-yellow-600'
  return 'text-green-600'
})

const diasParaExpirarTexto = computed(() => {
  if (diasParaExpirar.value === null) return ''
  if (diasParaExpirar.value <= 0) return '¡Certificado expirado!'
  if (diasParaExpirar.value === 1) return 'Expira mañana'
  if (diasParaExpirar.value <= 30) return `Expira en ${diasParaExpirar.value} días`
  return `Válido por ${diasParaExpirar.value} días`
})

// ZeroTier computeds
const zerotierUrl = computed(() => {
  if (!props.form.zerotier_ip) return ''
  return `http://${props.form.zerotier_ip}`
})

const copyZerotierUrl = async () => {
  if (zerotierUrl.value) {
    try {
      await navigator.clipboard.writeText(zerotierUrl.value)
      Swal.fire({
        icon: 'success',
        title: '¡Copiado!',
        text: 'URL copiada al portapapeles'
      })
    } catch (e) {
      console.error('Error al copiar:', e)
    }
  }
}
</script>
