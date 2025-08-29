import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: '../../public/build-announcement',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-announcement',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/css/announcement.css',
                __dirname + '/resources/assets/js/app.js',
                __dirname + '/resources/assets/js/announcement-index.js',
                __dirname + '/resources/assets/js/announcement-create.js',
                __dirname + '/resources/assets/js/announcement-edit.js',
                __dirname + '/resources/assets/js/announcement-show.js'
            ],
            refresh: true,
        }),
    ],
});

// Export paths for main vite config
export const paths = [
    'Modules/Announcement/resources/assets/sass/app.scss',
    'Modules/Announcement/resources/assets/js/app.js',
    'Modules/Announcement/resources/assets/js/announcement-index.js',
    'Modules/Announcement/resources/assets/js/announcement-create.js',
    'Modules/Announcement/resources/assets/js/announcement-edit.js',
    'Modules/Announcement/resources/assets/js/announcement-show.js'
];