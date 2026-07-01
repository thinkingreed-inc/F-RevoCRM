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
          // Vite 8 (Rollup 4) では assetInfo.name(単数) が廃止され names(配列) になった
          const name = assetInfo.names?.[0] ?? assetInfo.name;
          // CSS は cssCodeSplit:false により単一。読み込み側(Header.tpl)が参照する style.css に固定
          if (name && name.endsWith('.css')) return 'style.css';
          return name || 'asset';
        },
        // 単一ファイルにバンドル
        inlineDynamicImports: true,
      },
    },
    cssCodeSplit: false,
  },
});
