<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterLoaderInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(DataAdapterLoaderInterface::ADAPTER_TAG)]
final readonly class ArrayFieldAdapter implements SetterDataInterface
{
    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false,
    ): ?array {
        $value = $data[$key] ?? null;
        if (!is_array($value)) {
            return null;
        }

        return $value;
    }
}