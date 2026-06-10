import './bootstrap';
import './echo';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import('./icons.js');

const appName = import.meta.env.VITE_APP_NAME || 'App';



createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .component('FontAwesomeIcon', FontAwesomeIcon);

    // Seguimiento de Facebook Pixel en cambios de ruta SPA
    router.on('finish', () => {
      if (window.fbq) {
        window.fbq('track', 'PageView');
      }
    });

    // Directiva para click fuera
    app.directive('click-outside', {
      mounted(el, binding) {
        el.clickOutsideEvent = function (event) {
          if (!(el === event.target || el.contains(event.target))) {
            binding.value(event, el);
          }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
      },
      unmounted(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
      },
    });

    app.config.globalProperties.$can = (permissionOrRole) => {
      const auth = app.config.globalProperties.$page.props.auth;
      if (!auth || !auth.user) return false;

      // Check if user is admin (from is_admin flag)
      if (auth.user.is_admin) return true;

      const permissions = auth.user.permissions || [];
      const roles = auth.user.roles || [];

      // Also check if user has admin or super-admin in roles array
      const roleNames = roles.map(r => typeof r === 'string' ? r : r.name);
      if (roleNames.includes('admin') || roleNames.includes('super-admin')) return true;

      // Coincidencia exacta
      if (permissions.includes(permissionOrRole) || roleNames.includes(permissionOrRole)) return true;
      if (permissions.includes('*')) return true;

      // Normalizar 'acción modulo' (ej. 'view clientes') a 'clientes.view'
      let permToMatch = permissionOrRole;
      if (permissionOrRole.includes(' ')) {
        const parts = permissionOrRole.split(' ');
        if (parts.length === 2) {
          permToMatch = `${parts[1]}.${parts[0]}`;
        }
      }

      // Comprobar patrones de comodines (ej. 'clientes.*')
      const permParts = permToMatch.split('.');
      let currentPath = '';
      for (let i = 0; i < permParts.length - 1; i++) {
        currentPath += (i === 0 ? permParts[i] : '.' + permParts[i]);
        if (permissions.includes(`${currentPath}.*`)) return true;
      }

      // Coincidencia inversa para comodines espaciados (ej. '* clientes' o 'clientes *')
      if (permissionOrRole.includes(' ')) {
        const parts = permissionOrRole.split(' ');
        if (permissions.includes(`* ${parts[1]}`) || permissions.includes(`${parts[1]} *`)) return true;
      }

      return false;
    };

    app.mount(el);
  },
  progress: {
    color: '#FF6B35',
  },
});

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // 🔥 AUTOMACIÓN: Desregistrar SW en desarrollo para evitar conflictos de puertos/caché
    if (import.meta.env.DEV) {
      navigator.serviceWorker.getRegistrations().then(registrations => {
        for (let registration of registrations) {
          registration.unregister();
          console.log('PWA: ServiceWorker desregistrado automáticamente en DESARROLLO');
        }
      });
      return; 
    }

    // 💣 BOMBA DE CACHÉ DE EMERGENCIA (Fuerza limpieza tras despliegue de IA)
    const APP_VERSION = 'v1.3-reset-sw';
    if (localStorage.getItem('app_pwa_version') !== APP_VERSION) {
        console.warn('PWA: Detectada versión antigua. Ejecutando limpieza profunda...');
        navigator.serviceWorker.getRegistrations().then(registrations => {
            for (let registration of registrations) registration.unregister();
            if ('caches' in window) {
                caches.keys().then(names => {
                    for (let name of names) caches.delete(name);
                });
            }
            localStorage.setItem('app_pwa_version', APP_VERSION);
            console.log('PWA: Caché borrada. Reiniciando sistema...');
            window.location.reload();
        });
        return; // Detener ejecución hasta el reload
    }

    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .then(registration => {
        console.log('PWA: ServiceWorker registered with scope:', registration.scope);

        registration.onupdatefound = () => {
          const installingWorker = registration.installing;
          if (installingWorker == null) return;
          installingWorker.onstatechange = () => {
            if (installingWorker.state === 'installed') {
              if (navigator.serviceWorker.controller) {
                console.log('PWA: New content available, refreshing...');
                if (window.$toast) window.$toast.info('Nueva versión disponible. Actualizando...');
              } else {
                console.log('PWA: Content is cached for offline use.');
                if (window.$toast) window.$toast.success('🚀 POS: Listo para trabajar offline');
              }
            }
          };
        };
      })
      .catch(error => {
        console.error('PWA: ServiceWorker registration failed:', error);
      });
  });
}
