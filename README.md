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

# License

This bundle is licensed under the Pimcore Open Core License (POCL)
and is intended for use with Pimcore Platform 2025.1 and newer.

See LICENSE.md for full license text.

