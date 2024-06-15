import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: true,
        hmr: {
          host: 'localhost'
        },
        // ホットリロード設定
        watch: {
            usePolling: true
        }
    },
    resolve: {
        alias: {
          '@': './resources/js',
        },
    },
});
