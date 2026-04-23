import { defineConfig } from "@rsbuild/core";
import { pluginReact } from "@rsbuild/plugin-react";
import { pluginModuleFederation } from "@module-federation/rsbuild-plugin";
import { pluginGenerateEntrypoints } from "@pimcore/studio-ui-bundle/rsbuild/plugins";
import path from "path";
import fs from "fs";
import packages from "./package.json";

const buildPath = path.resolve(__dirname, "..", "public", "build");

const devBuildPath = path.resolve(buildPath, "development");
if (!fs.existsSync(buildPath)) {
    fs.mkdirSync(buildPath, { recursive: true });
}

const prodBuildPath = path.resolve(buildPath, "production");
if (!fs.existsSync(buildPath)) {
    fs.mkdirSync(buildPath, { recursive: true });
}

const isDevServer = process.env.NODE_ENV === "development";
export default defineConfig({
    mode: process.env.NODE_ENV === "production" ? "production" : "development",
    server: {
        port: 3033,
    },
    dev: {
        client: {
            host: "localhost",
            port: 3033,
            protocol: "ws",
        },
    },
    source: {
        entry: {
            main: "./src/main.ts",
        },
        decorators: {
            version: "legacy",
        },
    },
    output: {
        manifest: true,
        assetPrefix: "/bundles/torqpimcorehelpers/build/" + (isDevServer ? "development" : "production"),
        cleanDistPath: true,
        distPath: isDevServer ? devBuildPath : prodBuildPath,
    },
    tools: {
        bundlerChain: (chain, { env }) => {
            chain.output.uniqueName("pimcore_helpers_bundle");
        },
    },
    plugins: [
        pluginGenerateEntrypoints(),
        pluginReact(),
        pluginModuleFederation({
            name: "pimcore_helpers_bundle",
            filename: "static/js/remoteEntry.js",
            exposes: {
                ".": "./src/main.ts",
            },
            dts: false,
            remotes: {
                "@pimcore/studio-ui-bundle": `promise new Promise(resolve => {
          const studioUIBundleRemoteUrl = window.StudioUIBundleRemoteUrl
          const script = document.createElement('script')

          let hasScript = false;

          document.querySelectorAll('script').forEach((el) => {
            const elPathname = el.src.replace(/https?:\\/\\/[^/]+/, '')
            const studioUIBundleRemoteUrlPathname = studioUIBundleRemoteUrl.replace(/https?:\\/\\/[^/]+/, '')

            if (elPathname === studioUIBundleRemoteUrlPathname) {
              hasScript = true;
              return;
            }
          })

          if (hasScript) {
            resolve({
              get: (request) => window['pimcore_studio_ui_bundle'].get(request),
              init: (...arg) => {
                try {
                  return window['pimcore_studio_ui_bundle'].init(...arg)
                } catch(e) {
                  console.log('remote container already initialized')
                }
              }
            })
            return
          }

          script.src = studioUIBundleRemoteUrl
          script.onload = () => {
            const proxy = {
              get: (request) => window['pimcore_studio_ui_bundle'].get(request),
              init: (...arg) => {
                try {
                  return window['pimcore_studio_ui_bundle'].init(...arg)
                } catch(e) {
                  console.log('remote container already initialized')
                }
              }
            }
            resolve(proxy)
          }
          document.head.appendChild(script);
        })
        `,
            },
            shared: {
                ...packages.dependencies,
                react: {
                    singleton: true,
                    eager: true,
                    requiredVersion: packages.dependencies.react,
                },
                "react-dom": {
                    singleton: true,
                    eager: true,
                    requiredVersion: packages.dependencies["react-dom"],
                },
            },
        }),
    ],
});
