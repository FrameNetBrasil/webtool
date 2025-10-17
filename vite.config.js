import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { minimatch } from 'minimatch';
export default defineConfig({
    plugins: [
        {
            handleHotUpdate(ctx) {
                if (minimatch(ctx.file, '**/storage/framework/views/**/*.php')) {
                    return [];
                }
            }
        },
        laravel({
            input: [
                'resources/js/app.js',
            ],
            refresh: ['app/UI/**'],
        }),
    ],
    css: {
        preprocessorOptions: {
            less: {
                math: "always",
                relativeUrls: true,
                javascriptEnabled: true,
            },
        },
    },
    // server: {
    //     hmr: {
    //         host: 'localhost',
    //     },
    //     watch: {
    //         usePolling: true
    //     }
    // },
});
