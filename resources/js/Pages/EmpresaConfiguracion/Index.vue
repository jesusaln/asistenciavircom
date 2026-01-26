<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { notyf } from '@/Utils/notyf.js'

// Import Partials
import GeneralTab from './Partials/GeneralTab.vue'
import VisualTab from './Partials/VisualTab.vue'
import DocumentosTab from './Partials/DocumentosTab.vue'
import ImpuestosTab from './Partials/ImpuestosTab.vue'
import BancariosTab from './Partials/BancariosTab.vue'
import CorreoTab from './Partials/CorreoTab.vue'
import WhatsAppTab from './Partials/WhatsAppTab.vue'
import SistemaTab from './Partials/SistemaTab.vue'
import SeguridadTab from './Partials/SeguridadTab.vue'
import CobrosTab from './Partials/CobrosTab.vue'
import PagosTab from './Partials/PagosTab.vue'
import CertificadosTab from './Partials/CertificadosTab.vue'
import RedTab from './Partials/RedTab.vue'
import DangerZoneTab from './Partials/DangerZoneTab.vue'
import TiendaOnlineTab from './Partials/TiendaOnlineTab.vue'
import FoliosTab from './Partials/FoliosTab.vue'
import RespaldosTab from './Partials/RespaldosTab.vue'
import RedesSocialesTab from './Partials/RedesSocialesTab.vue'
import ApiKeysTab from './Partials/ApiKeysTab.vue'
import BlogRobotTab from './Partials/BlogRobotTab.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  configuracion: { type: Object, required: true },
  empresa: { type: Object, default: null },
  cuentas_bancarias: { type: Array, default: () => [] },
})

// Inicializar tab desde URL hash o default
const getInitialTab = () => {
  const hash = window.location.hash.replace('#', '');
  const validTabs = ['general', 'visual', 'redes-sociales', 'folios', 'documentos', 'impuestos', 'bancarios', 'correo', 'whatsapp', 'api-keys', 'cobros', 'pagos', 'certificados', 'red', 'tienda', 'respaldos', 'sistema', 'seguridad', 'danger'];
  return validTabs.includes(hash) ? hash : 'general';
};

const activeTab = ref(getInitialTab())

const tabs = [
  { id: 'general', nombre: 'Información General', icono: 'building', component: GeneralTab },
  { id: 'visual', nombre: 'Apariencia', icono: 'palette', component: VisualTab },
  { id: 'redes-sociales', nombre: 'Redes Sociales', icono: 'share-alt', component: RedesSocialesTab },
  { id: 'folios', nombre: 'Folios y Series', icono: 'list-ol', component: FoliosTab }, // NEW TAB
  { id: 'documentos', nombre: 'Documentos', icono: 'file-contract', component: DocumentosTab }, // Changed from document-text to file-contract for better FA match
  { id: 'impuestos', nombre: 'Impuestos', icono: 'money-bill-wave', component: ImpuestosTab }, // Changed icon
  { id: 'bancarios', nombre: 'Datos Bancarios', icono: 'university', component: BancariosTab }, // Changed icon
  { id: 'correo', nombre: 'Configuración Correo', icono: 'envelope', component: CorreoTab },
  { id: 'whatsapp', nombre: 'WhatsApp API', icono: 'comments', component: WhatsAppTab },
  { id: 'api-keys', nombre: 'Inteligencia Artificial', icono: 'robot', component: ApiKeysTab },
  { id: 'cobros', nombre: 'Cobranza', icono: 'file-invoice-dollar', component: CobrosTab },
  { id: 'pagos', nombre: 'Cuentas por Pagar', icono: 'hand-holding-usd', component: PagosTab },
  { id: 'certificados', nombre: 'Certificados SAT', icono: 'key', component: CertificadosTab },
  { id: 'red', nombre: 'Red y Dominio', icono: 'globe', component: RedTab },
  { id: 'tienda', nombre: 'Tienda y Pagos Online', icono: 'shopping-cart', component: TiendaOnlineTab },
  { id: 'respaldos', nombre: 'Respaldos Cloud', icono: 'cloud-upload-alt', component: RespaldosTab },
  { id: 'sistema', nombre: 'Sistema', icono: 'cogs', component: SistemaTab },
  { id: 'robot-blog', nombre: 'Robot de Blog', icono: 'robot', component: BlogRobotTab }, // NEW
  { id: 'seguridad', nombre: 'Seguridad', icono: 'shield-alt', component: SeguridadTab },
  { id: 'danger', nombre: 'Zona de Peligro', icono: 'exclamation-triangle', component: DangerZoneTab },
]

const tabRoutes = {
  general: 'empresa-configuracion.general.update',
  visual: 'empresa-configuracion.visual.update',
  'redes-sociales': 'empresa-configuracion.redes-sociales.update',
  folios: null, // Self-contained
  documentos: 'empresa-configuracion.documentos.update',
  impuestos: 'empresa-configuracion.impuestos.update',
  bancarios: 'empresa-configuracion.bancarios.update',
  correo: 'empresa-configuracion.correo.update',
  whatsapp: 'empresa-configuracion.whatsapp.update',
  'api-keys': 'empresa-configuracion.api-keys.update',
  cobros: 'empresa-configuracion.cobros.update',
  pagos: 'empresa-configuracion.pagos.update',
  certificados: null,
  red: 'empresa-configuracion.red.update',
  tienda: 'empresa-configuracion.tienda.update',
  respaldos: 'empresa-configuracion.respaldos.update',
  sistema: 'empresa-configuracion.sistema.update',
  'robot-blog': 'empresa-configuracion.robot-blog.update', 
  seguridad: 'empresa-configuracion.seguridad.update',
  danger: null,
}

const form = useForm({
  // General
  nombre_empresa: props.configuracion.nombre_empresa,
  rfc: props.configuracion.rfc,
  razon_social: props.configuracion.razon_social,
  regimen_fiscal: props.configuracion.regimen_fiscal,
  calle: props.configuracion.calle,
  numero_exterior: props.configuracion.numero_exterior,
  numero_interior: props.configuracion.numero_interior,
  telefono: props.configuracion.telefono,
  email: props.configuracion.email,
  sitio_web: props.configuracion.sitio_web,
  codigo_postal: props.configuracion.codigo_postal,
  colonia: props.configuracion.colonia,
  ciudad: props.configuracion.ciudad,
  estado: props.configuracion.estado,
  pais: props.configuracion.pais,
  descripcion_empresa: props.configuracion.descripcion_empresa,
  
  // Visual
  color_principal: props.configuracion.color_principal,
  color_secundario: props.configuracion.color_secundario,
  color_terciario: props.configuracion.color_terciario,
  logo_url: props.configuracion.logo_url, // For preview
  favicon_url: props.configuracion.favicon_url, // For preview
  logo_reportes_url: props.configuracion.logo_reportes_url, // For preview

  // Documentos
  pie_pagina_facturas: props.configuracion.pie_pagina_facturas,
  pie_pagina_cotizaciones: props.configuracion.pie_pagina_cotizaciones,
  pie_pagina_ventas: props.configuracion.pie_pagina_ventas,
  terminos_condiciones: props.configuracion.terminos_condiciones,
  politica_privacidad: props.configuracion.politica_privacidad,

  // Impuestos
  iva_porcentaje: props.configuracion.iva_porcentaje,
  isr_porcentaje: props.configuracion.isr_porcentaje || 1.25,
  moneda: props.configuracion.moneda,

  // Bancarios
  banco: props.configuracion.banco,
  sucursal: props.configuracion.sucursal,
  cuenta: props.configuracion.cuenta,
  clabe: props.configuracion.clabe,
  titular: props.configuracion.titular,
  numero_cuenta: props.configuracion.numero_cuenta,
  numero_tarjeta: props.configuracion.numero_tarjeta,
  nombre_titular: props.configuracion.nombre_titular,
  informacion_adicional_bancaria: props.configuracion.informacion_adicional_bancaria,

  // Correo
  smtp_host: props.configuracion.smtp_host,
  smtp_port: props.configuracion.smtp_port,
  smtp_username: props.configuracion.smtp_username,
  smtp_password: props.configuracion.smtp_password,
  smtp_encryption: props.configuracion.smtp_encryption,
  email_from_address: props.configuracion.email_from_address,
  email_from_name: props.configuracion.email_from_name,
  email_reply_to: props.configuracion.email_reply_to,

  // WhatsApp
  whatsapp_enabled: props.empresa?.whatsapp_enabled || false,
  whatsapp_business_account_id: props.empresa?.whatsapp_business_account_id || '',
  whatsapp_phone_number_id: props.empresa?.whatsapp_phone_number_id || '',
  whatsapp_sender_phone: props.empresa?.whatsapp_sender_phone || '',
  whatsapp_access_token: '', // Sensitive
  whatsapp_app_secret: '', // Sensitive
  whatsapp_webhook_verify_token: props.empresa?.whatsapp_webhook_verify_token || '',
  whatsapp_default_language: props.empresa?.whatsapp_default_language || 'es_MX',
  whatsapp_template_payment_reminder: props.empresa?.whatsapp_template_payment_reminder || '',

  // Sistema
  mantenimiento: props.configuracion.mantenimiento,
  mensaje_mantenimiento: props.configuracion.mensaje_mantenimiento,
  registro_usuarios: props.configuracion.registro_usuarios,
  notificaciones_email: props.configuracion.notificaciones_email,
  backup_automatico: props.configuracion.backup_automatico,
  frecuencia_backup: props.configuracion.frecuencia_backup,
  retencion_backups: props.configuracion.retencion_backups,
  backup_cloud_enabled: props.configuracion.backup_cloud_enabled || false,
  backup_tipo: props.configuracion.backup_tipo || 'sql',
  backup_hora_completo: props.configuracion.backup_hora_completo || '03:00',

  // MEGA Cloud
  mega_enabled: props.configuracion.mega_enabled || false,
  mega_email: props.configuracion.mega_email || '',
  mega_password: '', // Sensitive - no precargar
  mega_folder: props.configuracion.mega_folder || '/Backups',
  mega_auto_backup: props.configuracion.mega_auto_backup || false,
  mega_retention_days: props.configuracion.mega_retention_days || 30,

  // Google Drive
  cloud_provider: props.configuracion.cloud_provider || 'none',
  gdrive_enabled: props.configuracion.gdrive_enabled || false,
  gdrive_client_id: props.configuracion.gdrive_client_id || '',
  gdrive_client_secret: '', // Sensitive - no precargar
  gdrive_folder_name: props.configuracion.gdrive_folder_name || 'CDD_Backups',
  gdrive_auto_backup: props.configuracion.gdrive_auto_backup || false,
  gdrive_access_token: props.configuracion.gdrive_access_token ? '***' : '',
  gdrive_refresh_token: props.configuracion.gdrive_refresh_token ? '***' : '',

  // Cobros
  email_cobros: props.configuracion.email_cobros || '',
  cobros_hora_reporte: props.configuracion.cobros_hora_reporte || '08:00',
  cobros_reporte_automatico: props.configuracion.cobros_reporte_automatico || false,
  cobros_dias_anticipacion: props.configuracion.cobros_dias_anticipacion || 0,

  // Pagos (Cuentas por Pagar)
  email_pagos: props.configuracion.email_pagos || '',
  pagos_hora_reporte: props.configuracion.pagos_hora_reporte || '08:00',
  pagos_reporte_automatico: props.configuracion.pagos_reporte_automatico || false,
  pagos_dias_anticipacion: props.configuracion.pagos_dias_anticipacion || 0,

  // Seguridad
  intentos_login: props.configuracion.intentos_login,
  tiempo_bloqueo: props.configuracion.tiempo_bloqueo,
  requerir_2fa: props.configuracion.requerir_2fa,
  dkim_selector: props.configuracion.dkim_selector,
  dkim_domain: props.configuracion.dkim_domain,
  dkim_public_key: props.configuracion.dkim_public_key,
  dkim_enabled: props.configuracion.dkim_enabled,

  // Red y Dominio
  dominio_principal: props.configuracion.dominio_principal || '',
  dominio_secundario: props.configuracion.dominio_secundario || '',
  servidor_ipv4: props.configuracion.servidor_ipv4 || '',
  servidor_ipv6: props.configuracion.servidor_ipv6 || '',
  ssl_enabled: props.configuracion.ssl_enabled || false,
  ssl_certificado_path: props.configuracion.ssl_certificado_path || '',
  ssl_key_path: props.configuracion.ssl_key_path || '',
  ssl_ca_bundle_path: props.configuracion.ssl_ca_bundle_path || '',
  ssl_fecha_expiracion: props.configuracion.ssl_fecha_expiracion || '',
  ssl_proveedor: props.configuracion.ssl_proveedor || '',
  app_url: props.configuracion.app_url || '',
  force_https: props.configuracion.force_https ?? true,

  // ZeroTier VPN
  zerotier_enabled: props.configuracion.zerotier_enabled || false,
  zerotier_network_id: props.configuracion.zerotier_network_id || '',
  zerotier_ip: props.configuracion.zerotier_ip || '',
  zerotier_node_id: props.configuracion.zerotier_node_id || '',
  zerotier_notas: props.configuracion.zerotier_notas || '',

  // Tienda en Línea y Pagos
  tienda_online_activa: props.configuracion.tienda_online_activa || false,
  google_client_id: props.configuracion.google_client_id || '',
  google_client_secret: props.configuracion.google_client_secret || '',
  microsoft_client_id: props.configuracion.microsoft_client_id || '',
  microsoft_client_secret: props.configuracion.microsoft_client_secret || '',
  microsoft_tenant_id: props.configuracion.microsoft_tenant_id || 'common',
  
  // MercadoPago
  mercadopago_access_token: props.configuracion.mercadopago_access_token || '',
  mercadopago_public_key: props.configuracion.mercadopago_public_key || '',
  mercadopago_sandbox: props.configuracion.mercadopago_sandbox ?? true,
  
  // PayPal
  paypal_client_id: props.configuracion.paypal_client_id || '',
  paypal_client_secret: props.configuracion.paypal_client_secret || '',
  paypal_sandbox: props.configuracion.paypal_sandbox ?? true,

  // Stripe
  stripe_public_key: props.configuracion.stripe_public_key || '',
  stripe_secret_key: props.configuracion.stripe_secret_key || '',
  stripe_webhook_secret: props.configuracion.stripe_webhook_secret || '',
  stripe_sandbox: props.configuracion.stripe_sandbox ?? true,

  // Bancos automáticos
  cuenta_id_paypal: props.configuracion.cuenta_id_paypal || '',
  cuenta_id_mercadopago: props.configuracion.cuenta_id_mercadopago || '',
  cuenta_id_stripe: props.configuracion.cuenta_id_stripe || '',

  // Redes Sociales
  facebook_url: props.configuracion.facebook_url || '',
  instagram_url: props.configuracion.instagram_url || '',
  twitter_url: props.configuracion.twitter_url || '',
  tiktok_url: props.configuracion.tiktok_url || '',
  youtube_url: props.configuracion.youtube_url || '',
  linkedin_url: props.configuracion.linkedin_url || '',
  n8n_webhook_blog: props.configuracion.n8n_webhook_blog || '',

  // CVA
  cva_active: props.configuracion.cva_active ?? false,
  cva_user: props.configuracion.cva_user || '',
  cva_password: '', // Sensible, no precargar
  cva_utility_percentage: props.configuracion.cva_utility_percentage || 15.00,
  cva_codigo_sucursal: props.configuracion.cva_codigo_sucursal || 1,
  cva_paqueteria_envio: props.configuracion.cva_paqueteria_envio || 4,

  // AI & API Keys
  ai_provider: props.configuracion.ai_provider || 'groq',
  groq_api_key: '', // Sensitive
  groq_model: props.configuracion.groq_model || 'llama-3.3-70b-versatile',
  groq_temperature: props.configuracion.groq_temperature || 0.7,
  ollama_base_url: props.configuracion.ollama_base_url || 'http://localhost:11434',
  ollama_model: props.configuracion.ollama_model || 'llama3.1',
  chatbot_enabled: props.configuracion.chatbot_enabled ?? true,
  chatbot_system_prompt: props.configuracion.chatbot_system_prompt || '',
  chatbot_system_prompt: props.configuracion.chatbot_system_prompt || '',
  chatbot_name: props.configuracion.chatbot_name || 'VircomBot',

  // Robot Blog
  blog_robot_token: props.configuracion.blog_robot_token || '',
  blog_robot_enabled: props.configuracion.blog_robot_enabled ?? false,
})

const currentTabComponent = computed(() => {
    return tabs.find(t => t.id === activeTab.value)?.component || GeneralTab
})

const guardarConfiguracion = () => {
    const routeName = tabRoutes[activeTab.value] || null
    if (!routeName) {
        notyf.warning('Este tab no requiere guardado.')
        return
    }

    form.put(route(routeName), {
        preserveScroll: true,
        onSuccess: () => notyf.success('Configuración guardada correctamente'),
        onError: () => notyf.error('Error al guardar la configuración')
    })
}

// Sincronizar tab con URL hash y scrollear al inicio
watch(activeTab, (newTab) => {
    window.history.replaceState(null, '', `#${newTab}`);
    
    // Scroll suave al inicio del contenedor de info en móviles
    if (window.innerWidth < 1024) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
})
</script>

<template>
  <Head title="Configuración de Empresa" />

  <div class="min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 pb-20">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header con Botón de Guardado Sticky -->
      <div class="sticky top-4 z-30 mb-6 transition-all duration-300">
          <div class="bg-white dark:bg-slate-900/90 dark:bg-gray-800/90 backdrop-blur-md border border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-xl shadow-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                    <FontAwesomeIcon icon="building" size="lg" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white dark:text-gray-100">Configuración</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 hidden md:block">Personaliza tu sistema</p>
                </div>
            </div>
            
            <button
                @click="guardarConfiguracion"
                :disabled="form.processing"
                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium flex items-center gap-2 shadow-sm hover:shadow-md"
            >
                <FontAwesomeIcon v-if="form.processing" icon="spinner" spin />
                <FontAwesomeIcon v-else icon="save" />
                <span>{{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}</span>
            </button>
          </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Sidebar de Navegación -->
        <div class="lg:col-span-3">
          <nav class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-2 sticky top-24 max-h-[calc(100vh-140px)] overflow-y-auto custom-scrollbar overscroll-contain scroll-smooth flex flex-col pb-10">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'w-full flex items-center gap-3 px-4 py-3 text-left text-sm font-medium rounded-lg transition-all duration-200 mb-1',
                  activeTab === tab.id
                    ? 'bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300'
                    : 'text-gray-600 dark:text-gray-300 dark:text-gray-300 hover:bg-white dark:bg-slate-900 dark:hover:bg-gray-700 hover:text-gray-900 dark:text-white dark:hover:text-gray-100'
                ]"
              >
                  <div class="w-6 text-center">
                     <FontAwesomeIcon :icon="tab.icono" :class="activeTab === tab.id ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500 dark:text-gray-400'" />
                  </div>
                  {{ tab.nombre }}
                  
                  <FontAwesomeIcon 
                    v-if="activeTab === tab.id" 
                    icon="chevron-right" 
                    class="ml-auto text-xs opacity-50" 
                  />
              </button>
          </nav>
        </div>

        <!-- Contenido Principal -->
        <div class="lg:col-span-9">
             <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-8 min-h-[600px] transition-all duration-300">
                <component :is="currentTabComponent" :form="form" :cuentas_bancarias="cuentas_bancarias" />
             </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #e2e8f0 transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

/* Evitar que el scroll del menú afecte al scroll de la página */
nav {
  scrollbar-gutter: stable;
}

/* Transición suave para el contenido al cambiar de tab */
.lg:col-span-9 {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>



