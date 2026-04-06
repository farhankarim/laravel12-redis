import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

const isCodespaces = Boolean(process.env.CODESPACE_NAME && process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN);
const codespacesHost = isCodespaces
    ? `${process.env.CODESPACE_NAME}-5173.${process.env.GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}`
    : null;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/university/main.jsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: isCodespaces ? `https://${codespacesHost}` : 'http://127.0.0.1:5173',
        hmr: {
            host: isCodespaces ? codespacesHost : '127.0.0.1',
            protocol: isCodespaces ? 'wss' : 'ws',
            port: 5173,
            clientPort: isCodespaces ? 443 : 5173,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
        proxy: {
            // Forward all non-Vite requests to the Laravel dev server
            '^(?!/@vite|/resources|/@id|/node_modules)': {
                target: 'http://127.0.0.1:8000',
                changeOrigin: true,
            },
        },
    },
});
