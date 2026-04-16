<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Concrete as DataObject;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FCData;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as BrickData;
use ReflectionClass;
use ReflectionClassConstant;

/** Service which fetches the fields from a given data object, field collection, or object brick */
class FieldFetcher
{
    /**
     * @param string[] $excludedFields
     * @param null|string[] $includedFieldTypes
     * @param string[] $excludedFieldTypes
     * @return string[]
     */
    public function getFields(
        DataObject|FCData|BrickData|string $object,
        bool $includeId = true,
        bool $includeProperties = false,
        array $excludedFields = [],
        ?array $includedFieldTypes = null,
        array $excludedFieldTypes = [],
        bool $onlyMandatory = false,
        bool $onlyUnique = false,
        bool $onlyIndexed = false,
    ): array {
        $object = $this->toObject($object);
        $fields = $this->getFieldsFromFieldConsts($object::class);
        $fields = array_filter($fields, fn(string $f) => !in_array($f, $excludedFields));
        if ($includedFieldTypes !== null) {
            $fields = array_filter($fields, function(string $f) use ($object, $includedFieldTypes) {
                $type = $this->getFieldDefinitionType($object, $f);
                return in_array($type, $includedFieldTypes);
            });
        } elseif ($excludedFieldTypes) {
            $fields = array_filter($fields, function(string $f) use ($object, $excludedFieldTypes) {
                $type = $this->getFieldDefinitionType($object, $f);
                return !in_array($type, $excludedFieldTypes);
            });
        }
        if ($object instanceof DataObject && $includeId) {
            array_unshift($fields, 'id');
        }
        if ($object instanceof DataObject && $includeProperties) {
            $fields[] = 'properties';
        }
        if ($onlyMandatory) {
            $fields = array_filter($fields, fn($f) => $this->getFieldDefinition($object, $f)->getMandatory());
        }
        if ($onlyUnique) {
            $fields = array_filter($fields, fn($f) => $this->getFieldDefinition($object, $f)->getUnique());
        }
        if ($onlyIndexed) {
            $fields = array_filter($fields, fn($f) => $this->getFieldDefinition($object, $f)->getIndex());
        }
        return $fields;
    }

    public function hasField(DataObject|FCData|BrickData|string $object, string $field)
    {
        $object = $this->toObject($object);
        $fields = $this->getFieldsFromFieldConsts($object::class);
        return in_array($field, $fields);
    }

    public function getFieldDefinition(DataObject|FCData|BrickData|string $object, string $field)
    {
        $object = $this->toObject($object);
        if ($object instanceof DataObject) {
            return $object->getClass()->getFieldDefinition($field);
        } else {
            return $object->getDefinition()->getFieldDefinition($field);
        }
    }

    public function getFieldDefinitionType(DataObject|FCData|BrickData|string $object, string $field)
    {
        return $this->getFieldDefinition($object, $field)?->getFieldType();
    }

    private function toObject(DataObject|FCData|BrickData|string $object): DataObject|FCData|BrickData
    {
        if (is_string($object)) {
            $object = new $object;
            if (!$object instanceof DataObject && !$object instanceof FCData && !$object instanceof BrickData) {
                throw new InvalidArgumentException("Unsupported class for object: " . $object::class);
            }
        }
        return $object;
    }

    private function getFieldsFromFieldConsts(string $class)
    {
        $constants = (new ReflectionClass($class))->getConstants(ReflectionClassConstant::IS_PUBLIC);
        $fields = array_filter($constants, fn(string $key) => str_starts_with($key, 'FIELD'), ARRAY_FILTER_USE_KEY);
        return array_values($fields);
    }
}
