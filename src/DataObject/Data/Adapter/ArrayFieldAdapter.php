<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\DataObject\Data\Adapter;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\SetterDataInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait\DefaultSetterValueTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

final readonly class ArrayFieldAdapter implements SetterDataInterface
{
    use DefaultSetterValueTrait;

    public function getDataForSetter(
        Concrete $element,
        Data $fieldDefinition,
        string $key,
        array $data,
        UserInterface $user,
        ?FieldContextData $contextData = null,
        bool $isPatch = false
    ): ?array {
        return $this->getDefaultDataForSetter($fieldDefinition, $key, $data);
    }
}
