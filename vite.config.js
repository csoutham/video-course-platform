import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/app.css', 'resources/app.js'],
            refresh: true,
            detectTls: 'video-courses.test',
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            protocol: 'wss',
            host: 'video-courses.test',
        },
        cors: true,
    },
});
