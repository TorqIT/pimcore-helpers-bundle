<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class TorqPimcoreHelpersExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');

        // Conditionally load Studio Backend service definitions
        $bundles = $container->getParameter('kernel.bundles');
        if (isset($bundles['PimcoreStudioBackendBundle'])) {
            $loader->load('studio_backend_services.yaml');
        }
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
}
