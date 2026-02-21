import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const appUrl = env.APP_URL || 'http://localhost:8001'
  const devHost = env.VITE_DEV_HOST || '0.0.0.0'
  const hmrHost = env.VITE_HMR_HOST || 'localhost'
  const devPort = Number(env.VITE_DEV_PORT || 5174)

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
              return 'vendor'
            }
          },
        },
      },
    },
  }
});
