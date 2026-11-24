<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

abstract class AbstractObjectNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;
    use RelationsAsIdsTrait;

    private const string DATA_OBJECT_NAMESPACE = 'Pimcore\\Model\\DataObject\\';
    private const string BRICK_NAMESPACE = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\';
    private const string FIELD_COLLECTION_NAMESPACE = 'Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\';

    private const array SINGLE_RELATION_FIELDS = ['manyToOneRelation', 'image', 'hotspotimage', 'video'];

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected FieldFetcher $fieldFetcher,
        protected PimcoreModelDescriber $describer,
    ) {}

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|ArrayObject|null {
        $context = $this->getDefaultContext($context);
        $object = $this->toStandardObject($data, $format, $context);
        return $this->normalizer->normalize($object, $format, $context);
    }

    public function describe(Model $model, Schema $schema): void
    {
        $context = $this->getDefaultContext($model->getSerializationContext());
        $class = $model->getTypeInfo()->getClassName();

        $schema->type = 'object';
        foreach ($this->getFields($class, $context) as $field) {
            $property = Util::getProperty($schema, $field);
            $definition = $this->fieldFetcher->getFieldDefinition($class, $field);

            if ($definition) {
                $this->describeDefinition($definition, $property, $context);
            } elseif ($field === 'id') {
                $property->type = 'integer';
            } elseif ($field === 'properties') {
                $property->type = 'object';
            }
        }
    }

    public function getDefaultContext(array $context): array
    {
        return [
            HelperContextBuilder::INCLUDE_ID => true,
            HelperContextBuilder::INCLUDE_PROPERTIES => false,
            HelperContextBuilder::EXCLUDED_FIELDS => [],
            HelperContextBuilder::INCLUDED_FIELD_TYPES => null,
            HelperContextBuilder::EXCLUDED_FIELD_TYPES => [],
            HelperContextBuilder::EMPTY_ARRAYS_AS_NULL => false,
            HelperContextBuilder::SKIP_NULL_VALUES => false,
            ...$this->addRequiredContext($context),
        ];
    }

    protected function addRequiredContext(array $context): array
    {
        return $context;
    }

    protected function toStandardObject(mixed $data, ?string $format = null, array $context = []): stdClass
    {
        $castEmptyArrayToNull = $this->utils->get(HelperContextBuilder::EMPTY_ARRAYS_AS_NULL, $context, false);
        $skipNullValues = $this->utils->get(HelperContextBuilder::SKIP_NULL_VALUES, $context, false);

        $output = new stdClass();
        $fields = $this->getFields($data, $context);
        foreach ($fields as $field) {
            $value = $this->getValue($data, $field, $format, $context);
            if ($castEmptyArrayToNull && is_array($value) && count($value) === 0) {
                $value = null;
            }
            if ($skipNullValues && $value === null) {
                continue;
            } else {
                $output->$field = $value;
            }
        }
        return $output;
    }

    protected function getValue(mixed $data, string $field, ?string $format = null, array $context = [])
    {
        $language = $this->utils->get(HelperContextBuilder::LANGUAGE, $context);
        $value = $data->get($field, $language);
        if (!$this->utils->get(HelperContextBuilder::RELATIONS_AS_IDS, $context)) {
            return $value;
        }

        return $this->relationsToIds($this->fieldFetcher->getFieldDefinitionType($data, $field), $value);
    }

    /* @return string[] */
    private function getFields(object|string $data, array $context = []): array
    {
        return $this->fieldFetcher->getFields(
            $data,
            includeId: $context[HelperContextBuilder::INCLUDE_ID],
            includeProperties: $context[HelperContextBuilder::INCLUDE_PROPERTIES],
            excludedFields: $context[HelperContextBuilder::EXCLUDED_FIELDS],
            includedFieldTypes: $context[HelperContextBuilder::INCLUDED_FIELD_TYPES],
            excludedFieldTypes: $context[HelperContextBuilder::EXCLUDED_FIELD_TYPES],
        );
    }

    private function describeDefinition(FieldDef $definition, Schema $schema, array $context): void
    {
        $schema->nullable = true;
        $fieldType = $definition->getFieldType();
        $relationsAsIds = $context[HelperContextBuilder::RELATIONS_AS_IDS] ?? false;

        if ($relationsAsIds && in_array($fieldType, self::RELATION_FIELDS)) {
            $this->describeRelationIds($fieldType, $schema);
        } elseif ($fieldType === 'objectbricks') {
            /** @var FieldDef\Objectbricks $definition */
            $this->describeBricks($definition, $schema, $context);
        } elseif ($fieldType === 'fieldcollections') {
            /** @var FieldDef\Fieldcollections $definition */
            $this->describeFieldCollections($definition, $schema, $context);
        } elseif ($fieldType === 'block') {
            /** @var FieldDef\Block $definition */
            $this->describeBlock($definition, $schema, $context);
        } elseif ($fieldType === 'advancedManyToManyObjectRelation') {
            /** @var FieldDef\AdvancedManyToManyObjectRelation $definition */
            $this->describeAdvancedObjectRelation($definition, $schema, $context);
        } elseif ($fieldType === 'advancedManyToManyRelation') {
            /** @var FieldDef\AdvancedManyToManyRelation $definition */
            $this->describeAdvancedRelation($definition, $schema, $context);
        } elseif ($fieldType === 'encryptedField') {
            /** @var FieldDef\EncryptedField $definition */
            $this->describeDefinition($definition->getDelegate(), $schema, $context);
        } else {
            $this->describePhpdocType($definition->getPhpdocReturnType(), $schema, $context);
        }
    }

    private function describeRelationIds(string $fieldType, Schema $schema): void
    {
        if (in_array($fieldType, self::SINGLE_RELATION_FIELDS)) {
            $schema->type = 'integer';
            return;
        }
        $schema->type = 'array';
        $schema->items = new Items(['type' => 'integer', '_context' => Util::createWeakContext($schema->_context)]);
    }

    private function describeBricks(FieldDef\Objectbricks $definition, Schema $schema, array $context): void
    {
        $schema->type = 'object';
        foreach ($definition->getAllowedTypes() as $type) {
            $brick = Util::getProperty($schema, $type);
            $this->describer->describeClass(self::BRICK_NAMESPACE . $type, $brick, $context);
        }
    }

    private function describeFieldCollections(
        FieldDef\Fieldcollections $definition,
        Schema $schema,
        array $context,
    ): void {
        $classes = array_map(
            fn(string $type) => self::FIELD_COLLECTION_NAMESPACE . ucfirst($type),
            $definition->getAllowedTypes(),
        );

        $schema->type = 'array';
        $schema->items = new Items(['_context' => Util::createWeakContext($schema->_context)]);
        $this->describer->describeClasses($classes, $schema->items, $context);
    }

    /** Each block row maps a child field name to `{data: value}`, as BlockElementNormalizer outputs it */
    private function describeBlock(FieldDef\Block $definition, Schema $schema, array $context): void
    {
        $schema->type = 'array';
        $schema->items = new Items(['type' => 'object', '_context' => Util::createWeakContext($schema->_context)]);
        foreach ($definition->getFieldDefinitions() as $name => $child) {
            $element = Util::getProperty($schema->items, $name);
            $element->type = 'object';
            $this->describeDefinition($child, Util::getProperty($element, 'data'), $context);
        }
    }

    private function describeAdvancedObjectRelation(
        FieldDef\AdvancedManyToManyObjectRelation $definition,
        Schema $schema,
        array $context,
    ): void {
        $className = ClassDefinition::getById($definition->getAllowedClassId())->getName();
        $classes = [self::DATA_OBJECT_NAMESPACE . ucfirst($className)];

        $this->describeMetadataRows($schema, 'object', $classes, $definition->getColumns(), $context);
    }

    private function describeAdvancedRelation(
        FieldDef\AdvancedManyToManyRelation $definition,
        Schema $schema,
        array $context,
    ): void {
        $classes = $this->getAllowedElementClasses($definition);

        $this->describeMetadataRows($schema, 'element', $classes, $definition->getColumns(), $context);
    }

    /** @return string[] empty when the allowed elements can't be narrowed to classes a normalizer describes */
    private function getAllowedElementClasses(FieldDef\AdvancedManyToManyRelation $definition): array
    {
        $anyObjectAllowed = $definition->getObjectsAllowed() && $definition->getClasses() === [];
        if ($definition->getDocumentsAllowed() || $anyObjectAllowed) {
            return [];
        }

        $classes = [];
        if ($definition->getObjectsAllowed()) {
            $classes = array_map(
                fn(array $class) => self::DATA_OBJECT_NAMESPACE . ucfirst($class['classes']),
                $definition->getClasses(),
            );
        }
        if ($definition->getAssetsAllowed()) {
            $classes[] = Asset::class;
        }
        return $classes;
    }

    /**
     * Each row is the related element plus its metadata columns, as the metadata normalizers output it.
     * Pimcore stores every column value as text, whatever the column's type.
     *
     * @param string[] $classes
     * @param array<array{key: string}> $columns
     */
    private function describeMetadataRows(
        Schema $schema,
        string $elementKey,
        array $classes,
        array $columns,
        array $context,
    ): void {
        $schema->type = 'array';
        $schema->items = new Items(['type' => 'object', '_context' => Util::createWeakContext($schema->_context)]);
        $this->describer->describeClasses($classes, Util::getProperty($schema->items, $elementKey), $context);
        foreach ($columns as $column) {
            $value = Util::getProperty($schema->items, $column['key']);
            $value->type = 'string';
            $value->nullable = true;
        }
    }

    private function describePhpdocType(?string $phpdocType, Schema $schema, array $context): void
    {
        if ($phpdocType !== null) {
            $this->describer->describeType(TypeResolver::create()->resolve($phpdocType), $schema, $context);
        }
    }

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;
}
