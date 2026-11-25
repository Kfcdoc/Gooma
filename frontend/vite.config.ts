import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// El navegador habla con Vite y Vite reenvía /api al contenedor de Laravel.
// Así evitamos CORS en desarrollo y el frontend usa rutas relativas.
export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    proxy: {
      '/api': {
        target: process.env.VITE_API_PROXY_TARGET ?? 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
