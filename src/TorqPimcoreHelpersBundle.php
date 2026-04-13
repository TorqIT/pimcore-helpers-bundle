<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Torq\PimcoreHelpersBundle\DependencyInjection\Compiler\ArrayFieldTypeRegistrationPass;
use Torq\PimcoreHelpersBundle\Service\Common\BundleAssetResolverTrait;

class TorqPimcoreHelpersBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use BundleAssetResolverTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArrayFieldTypeRegistrationPass());
    }

    public function getCssPaths(): array
    {
        $paths = $this->getBundleAssetPaths($this->getPath() . '/public/css', 'css');
        return array_map(fn($p) => "/bundles/torqpimcorehelpers/$p", $paths);
    }

    public function getJsPaths(): array
    {
        $paths = $this->getBundleAssetPaths($this->getPath() . '/public/js', 'js');
        return array_map(fn($p) => "/bundles/torqpimcorehelpers/$p", $paths);
    }
}