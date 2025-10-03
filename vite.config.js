import { defineConfig } from 'vite';
import { resolve } from 'path';
import fs from 'fs';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  root: '.',
  publicDir: false,
  resolve: {
    alias: {
      vue: 'vue/dist/vue.esm-bundler.js',
    },
  },
  build: {
    outDir: 'public/build',
    manifest: true,
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
      },
    },
  },
  server: {
    cors: true,
    strictPort: true,
    port: 5173,
    host: '0.0.0.0', // Слушать на всех интерфейсах
    hmr: {
      // HMR будет работать на том же хосте, что и основное приложение
      host: 'localhost',
    },
  },
  plugins: [
    vue(),
    {
      name: 'vite-plugin-hot-file',
      configureServer(server) {
        const hotFile = resolve(__dirname, 'public/hot');
        
        // Создаем hot файл при старте dev сервера
        server.httpServer?.once('listening', () => {
          fs.writeFileSync(hotFile, '');
          console.log(`✓ Hot file created: ${hotFile}`);
        });

        // Удаляем hot файл при остановке
        process.on('SIGINT', () => {
          if (fs.existsSync(hotFile)) {
            fs.unlinkSync(hotFile);
            console.log(`✓ Hot file removed: ${hotFile}`);
          }
          process.exit();
        });

        process.on('SIGTERM', () => {
          if (fs.existsSync(hotFile)) {
            fs.unlinkSync(hotFile);
          }
          process.exit();
        });
      },
    },
  ],
});

