import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            buildDirectory: 'build',
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    define: {
        global: 'globalThis',
    },
    build: {
        outDir: 'backend/public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                app: resolve(__dirname, 'resources/js/app.tsx'),
                css: resolve(__dirname, 'resources/css/app.css'),
            },
        },
    },
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
