<?php

namespace Torq\PimcoreHelpersBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;

class TorqPimcoreHelpersBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;

    public function getJsPaths(): array
    {
        return [
            '/bundles/torqpimcorehelpers/ClassificationStoreKeyGetter.js',
            '/bundles/torqpimcorehelpers/dynamic-localized-field.js',
            '/bundles/torqpimcorehelpers/symfonyExpression.js',
            '/bundles/torqpimcorehelpers/toClassificationStoreKeyValuePair.js',
        ];
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}