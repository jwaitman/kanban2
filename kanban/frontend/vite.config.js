import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig({
  root: resolve(__dirname, '.'),
  build: {
    outDir: resolve(__dirname, '../public'),
    emptyOutDir: false, // Changed to false to prevent deleting requirements.html
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html')
      }
    }
  },
  plugins: [vue()]
})
