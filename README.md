# Pimcore Helpers Bundle

A collection of helpful services, utilities, and extensions for Pimcore.

## Installation

1. Install the composer package:
    ```shell
    composer require torq/pimcore-helpers-bundle
    ```
2. Add the bundle to `Kernel.php` or `bundles.php`:
    ```php
    $collection->addBundle(new TorqPimcoreHelpersBundle());
    ```
3. (Optional) If using the `HashedInput` custom field type, make sure to add the "torq_pimcore_helpers.secret" parameter
   to the Symfony container in `services.yaml`:
    ```yaml
    parameters:
        torq_pimcore_helpers.secret: "%env(MY_SECRET_VAR)%"
    ```

## Repository

The repository pattern wraps fetching, saving, and deleting Pimcore elements (assets, objects, etc.). There are several
benefits to this pattern:

* **Co-location of fetching logic**; complicated querying logic can be encapculated into a single spot, promoting
  reusable queries over random queries sprinkled throughout the service layer.
* **Mocks for testing**; mocking static database read/writes is challenging, mocking a repository method is easy.

## Normalizer

A full set of Symfony normalizers for Pimcore's unique object types including data objects, asset, field collections,
object bricks, blocks, classification stores, etc.

## Startup Commands

Set the `#[AsStartupCommand]` attribute on a Symfony command and add the following to the deploy or startup script:

```shell
bin/console torq:run-startup-commands
```

To automatically run that command on deploy/start-up. Startup commands are tracked in the `startup_command_runs`
database
table and are only run once by default. Set `#[AsStartupCommand(repeatable: true)]` to define a command which should be
run every time.

## Studio Quick Actions

Quick Actions are simple one-click commands – queue a job, clear a cache, trigger a resync. Requires the
`quick_actions` permission to use.

![quick-actions-demo.gif](docs/quick-actions/quick-actions-demo.gif)

### Defining custom actions

To define a quick action, set up the bundle as a Module Federation remote for your Studio extension:

```typescript
// rsbuild.config.ts

import {defineConfig} from "@rsbuild/core";
import {pluginModuleFederation} from "@module-federation/rsbuild-plugin";

export default defineConfig({
    // Set dts for development builds only, production builds do not need types
    dts: {
        consumeTypes: {
            remoteTypeUrls: {
                pimcore_helpers_bundle: {
                    alias: "@torq/pimcore-helpers-bundle",
                    zip: "http://localhost:3033/@mf-types.zip",
                    api: "http://localhost:3033/@mf-types.d.ts",
                },
            },
        },
    },
    plugins: [
        pluginModuleFederation({
            remotes: {
                "@pimcore/studio-ui-bundle": createDynamicRemote("pimcore_studio_ui_bundle"),
                "@torq/pimcore-helpers-bundle": createDynamicRemote("pimcore_helpers_bundle"),
            },
        })
    ]
})
```

Then register the action as a dynamic type using the registry:

```typescript jsx
// CustomQuickActionPlugin.tsx

import {container, IAbstractPlugin} from "@pimcore/studio-ui-bundle";
import {QUICK_ACTION_REGISTRY_SERVICE_ID, type QuickActionRegistry} from "@torq/pimcore-helpers-bundle";

export const CustomQuickActionPlugin: IAbstractPlugin = {
    name: "CustomQuickActionPlugin",

    onInit({container}) {
        const registry = container.get<QuickActionRegistry>(QUICK_ACTION_REGISTRY_SERVICE_ID);
        registry.registerDynamicType({
            id: "custom-quick-action",
            title: "Custom Quick Action",
            permission: "custom_action_permission", // set null for no permission. Users must still have quick_actions permission
            onClick: async (signal?: AbortSignal) => {
                await triggerCustomAction(signal);
            },
        });
    },
};
```

# License

This bundle is licensed under the Pimcore Open Core License (POCL)
and is intended for use with Pimcore Platform 2025.1 and newer.

See LICENSE.md for full license text.

