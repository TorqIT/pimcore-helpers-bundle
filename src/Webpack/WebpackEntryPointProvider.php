<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Webpack;

use Pimcore\Bundle\StudioUiBundle\Webpack\WebpackEntryPointProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 */
#[AutoconfigureTag(name: 'pimcore_studio_ui.webpack_entry_point_provider')]
final class WebpackEntryPointProvider implements WebpackEntryPointProviderInterface
{
    public function getEntryPointsJsonLocations(): array
    {
        return glob(__DIR__ . '/../../public/build/entrypoints.json');
    }

    public function getEntryPoints(): array
    {
        return ['exposeRemote'];
    }

    public function getOptionalEntryPoints(): array
    {
        return [];
    }

}
