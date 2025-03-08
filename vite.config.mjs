import {build, defineConfig} from 'vite';
import react from '@vitejs/plugin-react';
import tsconfigPaths from 'vite-tsconfig-paths';

const isProduction = process.env.NODE_ENV === 'production';

const reactConfig = defineConfig({
    root: 'app', // Specify the root directory as the 'src' folder
    build: {
        outDir: '../public/build', // Build output directory
        emptyOutDir: true, // Ensures old builds are deleted
        minify: isProduction,
        sourcemap: !isProduction, // Generates sourcemaps for development
        rollupOptions: {
            input: 'app/index.tsx', // Entry point for your application
            output: {
                entryFileNames: 'js/[name].mjs',
                assetFileNames: '[ext]/[name].[ext]', // Place assets (e.g., CSS) in a structured folder
            },
        },
    },
    plugins: [
        // Vite's React Plugin (Handles JSX/TSX and React-specific optimizations)
        react(),

        // Use path aliases from tsconfig.json
        tsconfigPaths()
    ],
});

export default reactConfig;
