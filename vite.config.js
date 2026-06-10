import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'path';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
  plugins: [
    tailwindcss(),
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    VitePWA({
      injectRegister: false, // 🔥 Desactivado: Registro manual en app.js
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'images/*.png', 'images/*.svg'],
      manifestFilename: 'manifest.webmanifest', // 🔥 Restaurado a webmanifest
      manifest: {
        name: 'Climas del Desierto POS',
        short_name: 'CDD POS',
        description: 'Punto de Venta Profesional - Climas del Desierto',
        theme_color: '#0f172a',
        background_color: '#0f172a',
        display: 'standalone',
        scope: '/',
        start_url: '/pos',
        icons: [
          {
            src: '/images/icon-192x192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/images/icon-512x512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2,json}'],
        maximumFileSizeToCacheInBytes: 7 * 1024 * 1024, // 7MB (Aumentado por seguridad)
        navigateFallback: null, // Evita el error de precache
        modifyURLPrefix: {
          'assets/': '/build/assets/',
          'manifest.webmanifest': '/manifest.webmanifest'
        },
        runtimeCaching: [
          {
            // Cachear TODAS las navegaciones (HTML) para que funcione offline
            urlPattern: ({ request }) => request.mode === 'navigate',
            handler: 'NetworkFirst',
            options: {
              cacheName: 'pages-cache',
              networkTimeoutSeconds: 3, // Si en 3s no hay red, usa cache
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 días
              }
            }
          },
          {
            urlPattern: /\/build\/assets\/.*/,
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'assets-cache',
              expiration: {
                maxEntries: 50,
                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
              }
            }
          },
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'google-fonts-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          },
          {
            urlPattern: /^https:\/\/fonts\.gstatic\.com\/.*/i,
            handler: 'CacheFirst',
            options: {
              cacheName: 'gstatic-fonts-cache',
              expiration: {
                maxEntries: 10,
                maxAgeSeconds: 60 * 60 * 24 * 365
              },
              cacheableResponse: {
                statuses: [0, 200]
              }
            }
          }
        ]
      },
      devOptions: {
        enabled: true,
        type: 'module',
      },
    })
  ],
  resolve: {
    alias: {
      '@': resolve('resources/js'),
      'vue': 'vue/dist/vue.esm-bundler.js'
    },
  },
  server: {
    host: '0.0.0.0',
    cors: true,
    hmr: {
      host: '192.168.1.55.nip.io'
    }
  },
  build: {
    manifest: true,
    outDir: 'public/build',
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('pinia') || id.includes('@inertiajs')) {
              return 'vue-core';
            }
            if (id.includes('axios') || id.includes('lodash')) {
              return 'utils';
            }
            if (id.includes('chart.js') || id.includes('vue-chartjs')) {
              return 'charts';
            }
            if (id.includes('@fortawesome')) {
              return 'icons';
            }
            if (id.includes('jspdf') || id.includes('exceljs')) {
              return 'reports';
            }
            // El resto a vendor general
            return 'vendor';
          }
        }
      }
    }
  }
});
