<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionData;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickData;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Logger;

class ArrayField extends Data implements
    Data\ResourcePersistenceAwareInterface,
    Data\QueryResourcePersistenceAwareInterface,
    Data\CustomResourcePersistingInterface,
    Data\TypeDeclarationSupportInterface,
    Data\VarExporterInterface,
    NormalizerInterface,
    Data\PreGetDataInterface,
    Data\PreSetDataInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\SimpleNormalizerTrait;
    use DataObject\Traits\ClassSavedTrait;

    /**
     * @internal
     */
    public string $elementType = 'input';

    /**
     * @internal
     */
    public bool $removeDuplicates = false;

    /**
     * @internal
     */
    public bool $filterEmptyValues = false;

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function setElementType(string $elementType): static
    {
        $this->elementType = $elementType;
        return $this;
    }

    public function getRemoveDuplicates(): bool
    {
        return $this->removeDuplicates;
    }

    public function setRemoveDuplicates(bool $removeDuplicates): static
    {
        $this->removeDuplicates = $removeDuplicates;
        return $this;
    }

    public function getFilterEmptyValues(): bool
    {
        return $this->filterEmptyValues;
    }

    public function setFilterEmptyValues(bool $filterEmptyValues): static
    {
        $this->filterEmptyValues = $filterEmptyValues;
        return $this;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (!is_array($data) || empty($data)) {
            return null;
        }

        if ($this->filterEmptyValues) {
            $data = array_values(array_filter($data, function ($value) {
                return $value !== null && $value !== '' && $value !== false;
            }));
        }

        if ($this->removeDuplicates) {
            // Preserve original order while removing duplicates
            $seen = [];
            $data = array_values(array_filter($data, function ($value) use (&$seen) {
                $key = serialize($value);
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            }));
        }

        return json_encode($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (empty($data)) {
            return null;
        }

        return json_decode($data, true) ?: null;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     */
    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (!is_array($data)) {
            return [];
        }

        return array_map(fn($item, $index) => ['index' => $index, 'value' => $item], $data, array_keys($data));
    }

    /**
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        if (!is_array($data)) {
            return null;
        }

        return array_map(fn($item) => $item['value'] ?? $item, $data);
    }

    /**
     * @see Data::getDataForGrid
     */
    public function getDataForGrid(mixed $data, ?Concrete $object = null, array $params = []): string
    {
        if (!is_array($data) || empty($data)) {
            return '';
        }

        return implode(', ', array_map(fn($v) => $v === null ? '' : (string)$v, $data));
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && $this->isEmpty($data)) {
            throw new ValidationException('Empty mandatory field [ ' . $this->getName() . ' ]');
        }
    }

    public function isEmpty(mixed $data): bool
    {
        return $data === null || (is_array($data) && count($data) === 0);
    }

    public function getForCsvExport(
        DataObject\Localizedfield|FieldcollectionData|ObjectbrickData|DataObject\Concrete $object,
        array $params = []
    ): string {
        $data = $this->getDataFromObjectParam($object, $params);
        return is_array($data) ? implode(', ', $data) : '';
    }

    public function getForWebserviceExport(mixed $object, array $params = []): mixed
    {
        return $this->getDataFromObjectParam($object, $params);
    }

    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        return $this->getDataForGrid($data, $object, $params);
    }

    public function getDiffVersionPreview(?array $data, ?Concrete $object = null, array $params = []): array
    {
        $display = is_array($data) && !empty($data)
            ? implode(', ', $data) . ' (' . count($data) . ' items)'
            : '(empty)';

        return [
            'html' => '<div>' . htmlspecialchars($display) . '</div>',
            'type' => 'html'
        ];
    }

    public function normalize(mixed $value, array $params = []): ?array
    {
        return is_array($value) ? $value : null;
    }

    public function denormalize(mixed $value, array $params = []): ?array
    {
        return is_array($value) ? $value : null;
    }

    public function save(Localizedfield|FieldcollectionData|ObjectbrickData|Concrete $object, array $params = []): void
    {
        $data = $this->getDataFromObjectParam($object, $params);
        $db = \Pimcore\Db::get();

        if ($object instanceof Concrete) {
            $table = 'object_store_' . $object->getClassId();
            $db->update($table, [
                $this->getName() => $this->getDataForResource($data, $object, $params)
            ], ['oo_id' => $object->getId()]);
        }
    }

    public function load(Localizedfield|FieldcollectionData|ObjectbrickData|Concrete $object, array $params = []): mixed
    {
        if ($object instanceof Concrete) {
            $db = \Pimcore\Db::get();
            $table = 'object_store_' . $object->getClassId();

            $result = $db->fetchOne(
                'SELECT ' . $this->getName() . ' FROM ' . $table . ' WHERE oo_id = ?',
                [$object->getId()]
            );

            return $this->getDataFromResource($result, $object, $params);
        }

        return null;
    }

    public function delete(Localizedfield|FieldcollectionData|ObjectbrickData|Concrete $object, array $params = []): void
    {
        // No custom delete logic needed
    }

    public function preGetData(mixed $container, array $params = []): mixed
    {
        $data = $container instanceof DataObject\Concrete
            ? $container->getObjectVar($this->getName())
            : ($params['data'] ?? null);

        return is_array($data) ? $data : [];
    }

    public function preSetData(mixed $container, mixed $data, array $params = []): mixed
    {
        $this->markLazyloadedFieldAsLoaded($container);
        return $data;
    }

    public function getColumnType(): string
    {
        return 'longtext';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?array';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'array|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'array|null';
    }

    public function getFieldType(): string
    {
        return 'arrayField';
    }

    public function synchronizeWithMainDefinition(DataObject\ClassDefinition\Data $mainDefinition): void
    {
        if ($mainDefinition instanceof self) {
            $this->elementType = $mainDefinition->elementType;
            $this->removeDuplicates = $mainDefinition->removeDuplicates;
            $this->filterEmptyValues = $mainDefinition->filterEmptyValues;
        }
    }

    public function isFilterable(): bool
    {
        return true;
    }

    public function isSortable(): bool
    {
        return false;
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);
        return $obj;
    }
}
