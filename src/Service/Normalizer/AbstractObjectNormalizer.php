<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use DateTimeInterface;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\UrlSlug;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

abstract class AbstractObjectNormalizer implements DescribingNormalizerInterface
{
    use DescribingNormalizerTrait;
    use RelationsAsIdsTrait;

    private const string BRICK_NAMESPACE = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\';
    private const string FIELD_COLLECTION_NAMESPACE = 'Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\';

    /** Value classes whose normalizers output a scalar */
    private const array SCALAR_CLASSES = [
        Link::class => 'string',
        UrlSlug::class => 'string',
        ExternalImage::class => 'string',
        RgbaColor::class => 'string',
        Consent::class => 'boolean',
    ];

    private const array SINGLE_RELATION_FIELDS = ['manyToOneRelation', 'image', 'hotspotimage', 'video'];

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected FieldFetcher $fieldFetcher,
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

    private function describeDefinition(FieldDef $definition, Schema $property, array $context): void
    {
        $property->nullable = true;
        $fieldType = $definition->getFieldType();
        $relationsAsIds = $context[HelperContextBuilder::RELATIONS_AS_IDS] ?? false;

        if ($relationsAsIds && in_array($fieldType, self::RELATION_FIELDS)) {
            $this->describeRelationIds($fieldType, $property);
        } elseif ($fieldType === 'objectbricks') {
            /** @var FieldDef\Objectbricks $definition */
            $this->describeBricks($definition, $property, $context);
        } elseif ($fieldType === 'fieldcollections') {
            /** @var FieldDef\Fieldcollections $definition */
            $this->describeFieldCollections($definition, $property, $context);
        } elseif ($fieldType === 'block') {
            /** @var FieldDef\Block $definition */
            $this->describeBlock($definition, $property, $context);
        } elseif ($fieldType === 'encryptedField') {
            /** @var FieldDef\EncryptedField $definition */
            $this->describeDefinition($definition->getDelegate(), $property, $context);
        } else {
            $this->describePhpdocType($definition->getPhpdocReturnType(), $property, $context);
        }
    }

    private function describeRelationIds(string $fieldType, Schema $property): void
    {
        if (in_array($fieldType, self::SINGLE_RELATION_FIELDS)) {
            $property->type = 'integer';
            return;
        }
        $property->type = 'array';
        $property->items = new Items(['type' => 'integer', '_context' => Util::createWeakContext($property->_context)]);
    }

    private function describeBricks(FieldDef\Objectbricks $definition, Schema $property, array $context): void
    {
        $property->type = 'object';
        foreach ($definition->getAllowedTypes() as $type) {
            $this->describeClass(self::BRICK_NAMESPACE . $type, Util::getProperty($property, $type), $context);
        }
    }

    private function describeFieldCollections(
        FieldDef\Fieldcollections $definition,
        Schema $property,
        array $context,
    ): void {
        $property->type = 'array';
        $property->items = new Items(['_context' => Util::createWeakContext($property->_context)]);
        $property->items->oneOf = array_map(function (string $type) use ($property, $context) {
            $schema = new Schema(['_context' => Util::createWeakContext($property->_context)]);
            $this->describeClass(self::FIELD_COLLECTION_NAMESPACE . ucfirst($type), $schema, $context);
            return $schema;
        }, $definition->getAllowedTypes());
    }

    /** Each block row maps a child field name to `{data: value}`, as BlockElementNormalizer outputs it */
    private function describeBlock(FieldDef\Block $definition, Schema $property, array $context): void
    {
        $property->type = 'array';
        $property->items = new Items(['type' => 'object', '_context' => Util::createWeakContext($property->_context)]);
        foreach ($definition->getFieldDefinitions() as $name => $child) {
            $element = Util::getProperty($property->items, $name);
            $element->type = 'object';
            $this->describeDefinition($child, Util::getProperty($element, 'data'), $context);
        }
    }

    private function describePhpdocType(?string $phpdocType, Schema $property, array $context): void
    {
        if ($phpdocType !== null) {
            $this->describeType(TypeResolver::create()->resolve($phpdocType), $property, $context);
        }
    }

    private function describeType(Type $type, Schema $property, array $context): void
    {
        match (true) {
            $type instanceof NullableType => $this->describeType($type->getWrappedType(), $property, $context),
            $type instanceof CollectionType => $this->describeCollection($type, $property, $context),
            $type instanceof UnionType => $this->describeUnion($type, $property, $context),
            $type instanceof ObjectType => $this->describeClass(
                ltrim($type->getClassName(), '\\'),
                $property,
                $context,
            ),
            $type instanceof BuiltinType => $this->describeBuiltin($type, $property),
            default => null,
        };
    }

    private function describeCollection(CollectionType $type, Schema $property, array $context): void
    {
        $property->type = 'array';
        $property->items = new Items(['_context' => Util::createWeakContext($property->_context)]);
        $this->describeType($type->getCollectionValueType(), $property->items, $context);
    }

    private function describeUnion(UnionType $type, Schema $property, array $context): void
    {
        $property->oneOf = array_map(function (Type $member) use ($property, $context) {
            $schema = new Schema(['_context' => Util::createWeakContext($property->_context)]);
            $this->describeType($member, $schema, $context);
            return $schema;
        }, $type->getTypes());
    }

    private function describeBuiltin(BuiltinType $type, Schema $property): void
    {
        $openApiType = match ($type->getTypeIdentifier()) {
            TypeIdentifier::STRING => 'string',
            TypeIdentifier::INT => 'integer',
            TypeIdentifier::FLOAT => 'number',
            TypeIdentifier::BOOL => 'boolean',
            TypeIdentifier::ARRAY => 'array',
            default => null,
        };
        if ($openApiType !== null) {
            $property->type = $openApiType;
        }
    }

    private function describeClass(string $class, Schema $property, array $context): void
    {
        if (is_a($class, DateTimeInterface::class, true)) {
            $this->describeDate($property, $context);
            return;
        }
        if (isset(self::SCALAR_CLASSES[$class])) {
            $property->type = self::SCALAR_CLASSES[$class];
            return;
        }

        $this->describeModel($class, $property, $context);
    }

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;
}
