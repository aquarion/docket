import { execSync } from "node:child_process";
import legacy from "@vitejs/plugin-legacy";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
	server: {
		host: "0.0.0.0",
		port: 5173,
		hmr: {
			host: "localhost",
		},
	},
	build: {
		outDir: "public/build",
		manifest: "manifest.json",
		rollupOptions: {
			input: [
				"resources/js/app.js",
				"resources/css/app.scss",
				"resources/css/manage.scss",
			],
		},
	},
	plugins: [
		legacy({
			targets: ["iOS >= 12"],
			modernTargets: ["iOS >= 12"],
			additionalLegacyPolyfills: ["regenerator-runtime/runtime"],
		}),
		laravel({
			input: [
				"resources/js/app.js",
				"resources/css/app.scss",
				"resources/css/manage.scss",
			],
			refresh: true,
		}),
		{
			// @vitejs/plugin-legacy v8 injects a data: URL import as a feature guard.
			// iOS 12 supports ES modules but not data: URL imports, causing the entire
			// modern bundle to fail silently. Strip it from the output.
			name: "strip-data-url-guard",
			apply: "build",
			generateBundle(_options, bundle) {
				for (const chunk of Object.values(bundle)) {
					if (chunk.type === "chunk" && chunk.code.startsWith("import'data:")) {
						chunk.code = chunk.code.replace(/^import'data:[^']*';/, "");
					}
				}
			},
		},
		{
			name: "scss-compiler",
			apply: "serve",
			configureServer(server) {
				const scssFiles = ["resources/css/festivals/**/*.scss"];
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
