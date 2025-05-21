import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css"],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: "localhost",
        },
        watch: {
            usePolling: true,
        },
    },
    resolve: {
        alias: {
            "@": "./resources/js", // 必要なら残す（TS/JSベースで開発していれば）
        },
    },
});
