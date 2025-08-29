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
                const relativePath = 'Modules/CRMCore/' + relative(__dirname, filePath);
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
        outDir: '../../public/build-crmcore',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-crmcore',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/js/app.js',
                // Company management JS files
                __dirname + '/resources/assets/js/companies-list.js',
                __dirname + '/resources/assets/js/companies-create.js',
                __dirname + '/resources/assets/js/companies-edit.js',
                // Customer management JS files
                __dirname + '/resources/assets/js/customers.js',
                __dirname + '/resources/assets/js/customers-create.js',
                __dirname + '/resources/assets/js/customers-edit.js',
                __dirname + '/resources/assets/js/customers-show.js',
                __dirname + '/resources/assets/js/customer-groups.js',
                ...getFilePaths(join(__dirname, 'resources/assets/js')),
            ],
            refresh: true,
        }),
    ],
});
// Scen all resources for assets file. Return array
//function getFilePaths(dir) {
//    const filePaths = [];
//
//    function walkDirectory(currentPath) {
//        const files = readdirSync(currentPath);
//        for (const file of files) {
//            const filePath = join(currentPath, file);
//            const stats = statSync(filePath);
//            if (stats.isFile() && !file.startsWith('.')) {
//                const relativePath = 'Modules/CRMCore/'+relative(__dirname, filePath);
//                filePaths.push(relativePath);
//            } else if (stats.isDirectory()) {
//                walkDirectory(filePath);
//            }
//        }
//    }
//
//    walkDirectory(dir);
//    return filePaths;
//}

//const __filename = fileURLToPath(import.meta.url);
//const __dirname = dirname(__filename);

//const assetsDir = join(__dirname, 'resources/assets');
//export const paths = getFilePaths(assetsDir);


//export const paths = [
//    'Modules/CRMCore/resources/assets/sass/app.scss',
//    'Modules/CRMCore/resources/assets/js/app.js',
//];
