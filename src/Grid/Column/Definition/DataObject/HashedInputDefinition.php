<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Grid\Column\Definition\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\AbstractDefinition;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: 'pimcore.studio_backend.grid_column_definition')]
final readonly class HashedInputDefinition extends AbstractDefinition
{
    public function getType(): string
    {
        return 'data-object.hashedInput';
    }

    public function getFrontendType(): string
    {
        return 'hashedInput';
    }

    public function getConfig(mixed $config): array
    {
        return [
            'elementType' => $config['elementType'] ?? 'input',
        ];
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function isFilterable(): bool
    {
        return false;
    }

    public function isExportable(): bool
    {
        return false;
    }

    public function isEditable(): bool
    {
        return false;
    }
}
