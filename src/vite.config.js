import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";

export default defineConfig({
    // Poper.jsのエラー解消  https://github.com/mui/material-ui/issues/32727#issuecomment-1767646455
    optimizeDeps: {
        include: ["@emotion/react", "@emotion/styled", "@mui/material/Tooltip"],
    },
    plugins: [
        laravel({
            input: ["resources/sass/app.scss", "resources/js/app.js"],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: true,
        hmr: {
            host: "localhost",
        },
    },
});
