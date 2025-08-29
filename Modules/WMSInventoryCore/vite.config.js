import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync, statSync } from 'fs';
import { join,relative,dirname } from 'path';
import { fileURLToPath } from 'url';

export default defineConfig({
    build: {
        outDir: '../../public/build-wmsinventorycore',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-wmsinventorycore',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/js/app.js',
                __dirname + '/resources/assets/js/wms-inventory-dashboard.js',
                __dirname + '/resources/assets/js/wms-inventory-products.js',
                __dirname + '/resources/assets/js/wms-inventory-warehouses.js',
                __dirname + '/resources/assets/js/wms-inventory-categories.js',
                __dirname + '/resources/assets/js/wms-inventory-units.js',
                __dirname + '/resources/assets/js/wms-inventory-adjustment-types.js',
                __dirname + '/resources/assets/js/wms-inventory-adjustments.js',
                __dirname + '/resources/assets/js/wms-inventory-transfers.js',
                __dirname + '/resources/assets/js/wms-inventory-purchases.js',
                __dirname + '/resources/assets/js/wms-inventory-sales.js',
                __dirname + '/resources/assets/js/wms-inventory-vendors.js'
            ],
            refresh: true,
        }),
    ],
});
