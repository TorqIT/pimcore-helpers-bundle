<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Torq\PimcoreHelpersBundle\EventListener\DataGenerationListener;

class TorqPimcoreHelpersExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        // Conditionally load Studio Backend service definitions
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['PimcoreStudioBackendBundle'])) {
            $loader->load('studio_backend_services.yaml');
        }

        if (isset($bundles['PimcoreStudioUiBundle'])) {
            $loader->load('studio_ui_services.yaml');
        }

        $this->configureDataGeneration($container, $config['data_generation'] ?? []);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // Always register the arrayField type mapping with Pimcore
        $loader->load('pimcore.yaml');

        // Conditionally register Studio Backend adapter mapping
        if ($container->hasExtension('pimcore_studio_backend')) {
            $loader->load('pimcore_studio_backend.yaml');
        }
    }

    /**
     * Expose the managed field targets as a parameter and register
     * the generic generation listener at the configured priority.
     *
     * @param array<string, mixed> $config
     */
    private function configureDataGeneration(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('torq_pimcore_helpers.data_generation.managed', $config['managed'] ?? []);

        if (($config['enabled'] ?? true) === false) {
            return;
        }

        if (!$container->hasDefinition(DataGenerationListener::class)) {
            return;
        }

        $priority = (int) ($config['listener_priority'] ?? 10);
        $listenerDefinition = $container->getDefinition(DataGenerationListener::class);

        foreach (['pimcore.dataobject.preAdd', 'pimcore.dataobject.preUpdate'] as $event) {
            $listenerDefinition->addTag('kernel.event_listener', [
                'event' => $event,
                'method' => 'onPreSave',
                'priority' => $priority,
            ]);
        }
    }
}
