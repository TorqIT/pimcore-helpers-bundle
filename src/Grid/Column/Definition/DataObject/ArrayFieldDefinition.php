<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Grid\Column\Definition\DataObject;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Definition\DataObject\AbstractDefinition;

final readonly class ArrayFieldDefinition extends AbstractDefinition
{
    public function getType(): string
    {
        return 'data-object.arrayField';
    }

    public function getFrontendType(): string
    {
        return 'arrayField';
    }

    public function getConfig(mixed $config): array
    {
        return [
            'elementType' => $config['elementType'] ?? 'input',
            'maxItems' => $config['maxItems'] ?? null,
            'removeDuplicates' => $config['removeDuplicates'] ?? false,
        ];
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function isExportable(): bool
    {
        return true;
    }

    public function isEditable(): bool
    {
        return false;
    }
}
