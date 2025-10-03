<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Concrete as DataObject;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FCData;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as BrickData;

/** Service which fetches the fields from a given data object, field collection, or object brick */
class FieldFetcher
{
    /**
     * @param string[] $excludedFields
     * @param string[] $includedFieldTypes
     * @param string[] $excludedFieldTypes
     * @return string[]
     */
    public function getFields(
        string|DataObject|FCData|BrickData $object,
        bool $includeId = true,
        array $excludedFields = [],
        array $includedFieldTypes = [],
        array $excludedFieldTypes = []
    ): array {
        if (is_string($object)) {
            $object = new $object;
            if (!$object instanceof DataObject && !$object instanceof FCData && !$object instanceof BrickData) {
                throw new InvalidArgumentException("Unsupported class for object: " . $object::class);
            }
        }
        if ($object instanceof DataObject) {
            $fields = $this->getFieldDefinitionsFromDataObject($object);
        } else {
            $fields = $this->getFieldDefinitionsFromFieldCollection($object);
        }
        $fields = $this->applyFilters($fields, $excludedFields, $includedFieldTypes, $excludedFieldTypes);
        $fields = array_map(fn(FieldDef $f) => $f->getName(), $fields);
        if ($object instanceof DataObject && $includeId) {
            array_unshift($fields, 'id');
        }
        return $fields;
    }

    protected function getFieldDefinitionsFromDataObject(DataObject $object)
    {
        return $object->getClass()->getFieldDefinitions();
    }

    protected function getFieldDefinitionsFromFieldCollection(FCData|BrickData $object)
    {
        return $object->getDefinition()->getFieldDefinitions();
    }

    protected function applyFilters(
        array $fields,
        array $excludedFields,
        array $includedFieldTypes,
        array $excludedFieldTypes
    ) {
        return array_filter(
            $fields,
            fn(FieldDef $f) => !in_array($f->getName(), $excludedFields) && in_array(
                $f->getFieldType(),
                $includedFieldTypes
            ) && !in_array($f->getFieldType(), $excludedFieldTypes)
        );
    }
}
