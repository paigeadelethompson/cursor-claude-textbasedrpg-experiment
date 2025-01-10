import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import glsl from 'vite-plugin-glsl';
import { resolve } from 'path';
import { dirname, fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    plugins: [
        react(),
        glsl()
    ],
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'index.html'),
                game: resolve(__dirname, 'src/game.tsx')
            },
            output: {
                manualChunks: {
                    vendor: ['react', 'react-dom'],
                    shaders: ['./src/shaders/**/*.glsl'],
                    models: ['./src/models/**/*.ts']
                }
            }
        },
        sourcemap: true,
        minify: 'terser'
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'src')
        }
    }
}); 