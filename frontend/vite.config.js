import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    laravel({
      input: ['src/index.css', 'src/main.jsx'],
      refresh: true,
      publicDirectory: '../backend/public',
      buildDirectory: 'build',
      hotFile: '../backend/public/hot',
    }),
    react(),
    tailwindcss(),
  ],
})
