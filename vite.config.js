import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import legacy from "@vitejs/plugin-legacy";
import { execSync } from "child_process";

export default defineConfig({
  build: {
    outDir: "public/build",
    manifest: "manifest.json",
    rollupOptions: {
      input: ["resources/js/app.js", "resources/css/app.css"],
    },
  },
  plugins: [
    legacy({
      targets: ["iOS >= 12"],
      additionalLegacyPolyfills: ["regenerator-runtime/runtime"],
    }),
    laravel({
      input: ["resources/js/app.js", "resources/css/app.css"],
      refresh: true,
    }),
    {
      name: "scss-compiler",
      apply: "serve",
      configureServer(server) {
        const scssFiles = ["templates/scss/**/*.scss"];
        server.watcher.add(scssFiles);
      },
      handleHotUpdate({ file, server }) {
        if (file.endsWith(".scss")) {
          try {
            execSync("npm run build:sass");
            server.ws.send({
              type: "full-reload",
            });
            return [];
          } catch (e) {
            console.error("SCSS compilation failed:", e);
          }
        }
      },
    },
  ],
  resolve: {
    alias: {
      "@": "/resources/js",
    },
  },
});
