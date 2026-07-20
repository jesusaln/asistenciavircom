import './bootstrap';
import '../css/app.css';

// Echo/Reverb se inicializa UNA sola vez en ./echo.js (no duplicar aquí).
// Antes había una copia inline que causaba que se crearan 2 instancias de
// window.Echo y se triplicaran las notificaciones en el frontend.
import './echo.js';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { applyThemeCSSVariables } from './Utils/themeDefaults';

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

    // Tema global: aplica colores de empresa_config en TODAS las páginas
    const applyGlobalTheme = () => {
      const cfg = props.initialPage.props?.empresa_config || props.initialPage.props?.empresa;
      const isDark = document.documentElement.classList.contains('dark');
      applyThemeCSSVariables(cfg, isDark);
    };
    applyGlobalTheme();

    // Re-aplicar en cada navegación SPA (por si cambia empresa_config)
    router.on('finish', () => {
      applyGlobalTheme();
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

    // Desregistrar cualquier SW anterior para romper ciclos de cache
    navigator.serviceWorker.getRegistrations().then(registrations => {
        for (let r of registrations) r.unregister();
    });

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
