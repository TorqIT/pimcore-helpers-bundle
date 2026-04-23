<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\ColumnDataTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\FieldDefinitionTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data\ArrayField;
use Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data\HashedInput;

/** @internal */
#[AutoconfigureTag(name: 'pimcore.studio_backend.grid_column_resolver')]
final class HashedInputResolver implements ColumnResolverInterface, CoreElementColumnResolverInterface,
                                           ExportResolverInterface
{
    use ColumnDataTrait;
    use LocalizedValueTrait;
    use FieldDefinitionTrait;

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $fieldDefinition = $this->getFieldDefinition($column->getKey(), $element->getClass());

        if (!$fieldDefinition instanceof HashedInput) {
            throw new InvalidArgumentException('Field definition must be a HashedInput instance');
        }

        $formattedValue = $fieldDefinition->getForCsvExport($element);

        return $this->getColumnData($column, $formattedValue, $fieldDefinition->getFieldType(), null);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        $fieldDefinition = $this->getFieldDefinition($column->getKey(), $element->getClass());

        if (!$fieldDefinition instanceof HashedInput) {
            throw new InvalidArgumentException('Field definition must be an HashedInput instance');
        }

        // Get raw array value for Studio UI
        $rawValue = $this->getLocalizedValue($column, $element);

        return $this->getColumnData($column, $rawValue, $fieldDefinition->getFieldType(), null);
    }

    public function getType(): string
    {
        return 'data-object.hashedInput';
    }

    public function supportedElementTypes(): array
    {
        return [ElementTypes::TYPE_OBJECT];
    }
}
