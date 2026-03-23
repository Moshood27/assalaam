import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const apiTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8000'

export default defineConfig({
    plugins: [vue()],
    // ADD THIS LINE FOR PRODUCTION
    base: '/',
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