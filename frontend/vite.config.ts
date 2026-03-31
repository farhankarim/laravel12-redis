import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/admin/',
  build: {
    outDir: '../public/admin',
    emptyOutDir: true,
  },
  server: {
    proxy: {
      '/auth': 'http://localhost:3000',
      '/users': 'http://localhost:3000',
      '/transactions': 'http://localhost:3000',
      '/queue': 'http://localhost:3000',
      '/api': 'http://localhost:3000',
    },
  },
})
