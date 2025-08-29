import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync, statSync } from 'fs';
import { join,relative,dirname } from 'path';
import { fileURLToPath } from 'url';

//Include all files in /resources/assets/js/
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
// Function to get all file paths in a directory recursively
function getFilePaths(dir) {
    const filePaths = [];
    function walkDirectory(currentPath) {
        const files = readdirSync(currentPath);
        for (const file of files) {
            const filePath = join(currentPath, file);
            const stats = statSync(filePath);
            if (stats.isFile() && !file.startsWith('.')) {
                const relativePath = 'Modules/AICore/' + relative(__dirname, filePath);
                filePaths.push(relativePath);
            } else if (stats.isDirectory()) {
                walkDirectory(filePath);
            }
        }
    }
    walkDirectory(dir);
    return filePaths;
}

export default defineConfig({
    build: {
        outDir: '../../public/build-aicore',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-aicore',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/css/ai-request-logs.css',
                __dirname + '/resources/assets/js/app.js',
                __dirname + '/resources/assets/js/aicore-providers.js',
                __dirname + '/resources/assets/js/aicore-provider-details.js',
                __dirname + '/resources/assets/js/aicore-provider-form.js',
                __dirname + '/resources/assets/js/aicore-models.js',
                __dirname + '/resources/assets/js/aicore-model-details.js',
                __dirname + '/resources/assets/js/aicore-model-form.js',
                __dirname + '/resources/assets/js/aicore-usage-analytics.js',
                __dirname + '/resources/assets/js/aicore-configurations.js',
                __dirname + '/resources/assets/js/aicore-module-configuration.js',
                __dirname + '/resources/assets/js/aicore-dashboard.js',
                __dirname + '/resources/assets/js/ai-request-logs.js',
            ],
            refresh: true,
        }),
    ],
});
