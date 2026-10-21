<?php

namespace Torq\PimcoreHelpersBundle\Service\Common;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Concrete as DataObject;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FCData;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as BrickData;
use Torq\PimcoreHelpersBundle\Model\DataObject\IndexType;
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
        IndexType $indexType = IndexType::QUERY,
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
            $fields = array_filter($fields, fn($f) => $this->isFieldIndexed($object, $f, $indexType));
        }
        return $fields;
    }

    public function hasField(DataObject|FCData|BrickData|string $object, string $field)
    {
        $object = $this->toObject($object);
        $fields = $this->getFieldsFromFieldConsts($object::class);
        return in_array($field, $fields);
    }

    /**
     * Whether the field has an index on the table `$indexType` points at: its own flag (Pimcore puts `index` on
     * query tables and `unique` on store tables) or membership in a class composite index of that type.
     * Composite and localized indices only exist on classes, so field collections and bricks only check the flag.
     */
    public function isFieldIndexed(
        DataObject|FCData|BrickData|string $object,
        string $field,
        IndexType $indexType = IndexType::QUERY,
    ): bool {
        $object = $this->toObject($object);
        $definition = $indexType->isLocalized()
            ? $this->getLocalizedFieldDefinition($object, $field)
            : $this->getFieldDefinition($object, $field);
        if ($definition === null) {
            return false;
        }
        if ($indexType->isQuery() ? $definition->getIndex() : $definition->getUnique()) {
            return true;
        }
        if (!$object instanceof DataObject) {
            return false;
        }

        $columns = $this->getColumnNames($definition, $indexType->isQuery());
        foreach ($object->getClass()->getCompositeIndices() as $index) {
            if ($index['index_type'] === $indexType->value && array_intersect($columns, $index['index_columns']) !== []) {
                return true;
            }
        }
        return false;
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

    public function getFieldColumnName(DataObject|FCData|BrickData|string $object, string $field)
    {
        $def = $this->getFieldDefinition($object, $field);
        if ($def instanceof FieldDef\ResourcePersistenceAwareInterface) {
            if (is_array($type = $def->getColumnType())) {
                $suffixes = array_keys($type);
                return array_map(fn($s) => $def->getName() . "__$s", $suffixes);
            } else {
                return $def->getName();
            }
        }
        return null;
    }

    public function getFieldQueryColumnName(DataObject|FCData|BrickData|string $object, string $field)
    {
        $def = $this->getFieldDefinition($object, $field);
        if ($def instanceof FieldDef\QueryResourcePersistenceAwareInterface) {
            if (is_array($type = $def->getQueryColumnType())) {
                $suffixes = array_keys($type);
                return array_map(fn($s) => $def->getName() . "__$s", $suffixes);
            } else {
                return $def->getName();
            }
        }
        return null;
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

    private function getLocalizedFieldDefinition(DataObject|FCData|BrickData $object, string $field): ?FieldDef
    {
        if (!$object instanceof DataObject) {
            return null;
        }
        $localizedFields = $object->getClass()->getFieldDefinition('localizedfields');
        return $localizedFields instanceof FieldDef\Localizedfields ? $localizedFields->getFieldDefinition($field) : null;
    }

    /** @return string[] */
    private function getColumnNames(FieldDef $definition, bool $query): array
    {
        $type = match (true) {
            $query && $definition instanceof FieldDef\QueryResourcePersistenceAwareInterface => $definition->getQueryColumnType(),
            !$query && $definition instanceof FieldDef\ResourcePersistenceAwareInterface => $definition->getColumnType(),
            default => null,
        };
        if ($type === null) {
            return [];
        }
        return is_array($type)
            ? array_map(fn($suffix) => $definition->getName() . "__$suffix", array_keys($type))
            : [$definition->getName()];
    }

    private function getFieldsFromFieldConsts(string $class)
    {
        $constants = (new ReflectionClass($class))->getConstants(ReflectionClassConstant::IS_PUBLIC);
        $fields = array_filter($constants, fn(string $key) => str_starts_with($key, 'FIELD'), ARRAY_FILTER_USE_KEY);
        return array_values($fields);
    }
}
