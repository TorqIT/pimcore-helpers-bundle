<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Torq\PimcoreHelpersBundle\DependencyInjection\Compiler\ArrayFieldTypeRegistrationPass;

class TorqPimcoreHelpersBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ArrayFieldTypeRegistrationPass());
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/torqpimcorehelpers/js/pimcore/object/classes/data/arrayField.js',
            '/bundles/torqpimcorehelpers/js/pimcore/object/tags/arrayField.js',
        ];
    }
}