import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Decide where to proxy API calls during development
// - Inside Docker (Sail): defaults to http://laravel.test
// - On host machine: set VITE_PROXY_TARGET=http://localhost:8000 (php artisan serve default)
const apiTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8000'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': {
        target: apiTarget,
        changeOrigin: true,
        secure: false,
      },
      // Proxy publicly served files from Laravel (e.g., legacy uploads and storage)
      '/upload': {
        target: apiTarget,
        changeOrigin: true,
        secure: false,
      },
      '/storage': {
        target: apiTarget,
        changeOrigin: true,
        secure: false,
      },
    },
  },
})
