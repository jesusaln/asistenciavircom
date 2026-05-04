import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appUrl = env.APP_URL || 'http://localhost:8001'
  const devHost = env.VITE_DEV_HOST || '0.0.0.0'
  const hmrHost = env.VITE_HMR_HOST || 'localhost'
  const devPort = Number(env.VITE_DEV_PORT || 5174)
  const business = env.APP_BUSINESS || 'vircom'
  const appName = business === 'climas' ? 'Climas del Desierto POS' : 'Asistencia Vircom ERP'
  const shortName = business === 'climas' ? 'CDD POS' : 'Vircom ERP'

  return {
    plugins: [
      laravel({
        input: ['resources/css/app.css', 'resources/js/app.js'],
        refresh: false,
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
        injectRegister: false,
        registerType: 'autoUpdate',
        includeAssets: ['favicon.ico', 'images/*.png', 'images/*.svg'],
        manifestFilename: 'manifest.webmanifest',
        manifest: {
          name: appName,
          short_name: shortName,
          description: appName,
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
          maximumFileSizeToCacheInBytes: 7 * 1024 * 1024,
          navigateFallback: null,
          modifyURLPrefix: {
            'assets/': '/build/assets/',
            'manifest.webmanifest': '/manifest.webmanifest'
          },
          runtimeCaching: [
            {
              urlPattern: ({ request }) => request.mode === 'navigate',
              handler: 'NetworkFirst',
              options: {
                cacheName: 'pages-cache',
                networkTimeoutSeconds: 3,
                expiration: {
                  maxEntries: 10,
                  maxAgeSeconds: 60 * 60 * 24 * 30
                }
              }
            }
          ]
        }
      })
    ],
    resolve: {
      alias: {
        '@': resolve('resources/js'),
        'vue': 'vue/dist/vue.esm-bundler.js',
      },
    },
    server: {
      host: devHost,
      port: devPort,
      strictPort: true,
      cors: {
        origin: [appUrl, /^https?:\/\/(?:.+\.)?nip\.io(?::\d+)?$/, /^https?:\/\/192\.168\.\d+\.\d+(?::\d+)?$/],
      },
      hmr: {
        host: hmrHost,
        protocol: 'ws',
        port: devPort,
        clientPort: devPort,
      },
    },
    build: {
      manifest: 'manifest.json',
      outDir: 'public/build',
      chunkSizeWarningLimit: 1600,
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
              return 'vendor';
            }
          },
        },
      },
    },
  }
});
