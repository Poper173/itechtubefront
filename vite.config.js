import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Backend-Only Configuration
 * Vite is configured for minimal use - only for potential asset serving
 * Frontend has been removed for API-only backend
 */
export default defineConfig({
    plugins: [
        laravel({
            // No frontend inputs - API only backend
            input: [],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
