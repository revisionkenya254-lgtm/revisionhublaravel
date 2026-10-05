import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/note-reader.css',
                'resources/js/app.js',
                'resources/js/register-phone.js',
                'resources/js/note-reader.js',
            ],
            refresh: true,
        }),
    ],
});
