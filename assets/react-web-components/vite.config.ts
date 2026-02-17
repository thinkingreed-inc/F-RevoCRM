import { defineConfig } from 'vite';
import path from "path"
import tailwindcss from "@tailwindcss/vite"
import react from "@vitejs/plugin-react"

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env': {},
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  server: {
    host: 'localhost',
    port: 5173,
    cors: true,
    strictPort: true,
  },
  build: {
    outDir: '../../public/resources/web-components',
    lib: {
      entry: 'src/main.ts',
      formats: ['iife'],
      name: 'WebComponents',
      fileName: () => 'web-components.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.name === 'style.css') return 'style.css';
          return assetInfo.name || 'asset';
        },
        // 単一ファイルにバンドル
        inlineDynamicImports: true,
      },
    },
    cssCodeSplit: false,
  },
});
