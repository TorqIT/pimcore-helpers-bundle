<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Contracts\Service\Attribute\Required;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag(self::TAG)]
#[AutoconfigureTag('nelmio_api_doc.model_describer', ['priority' => 10])]
abstract class AbstractObjectNormalizer implements NormalizerInterface, ModelDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    private const string TAG = 'torq.object_normalizer';

    private const string DATA_OBJECT_NAMESPACE = 'Pimcore\\Model\\DataObject\\';
    private const string BRICK_NAMESPACE = self::DATA_OBJECT_NAMESPACE . 'Objectbrick\\Data\\';

    private const array STRING_TYPES = [
        'input',
        'textarea',
        'wysiwyg',
        'select',
        'hashedInput',
        'email',
        'firstname',
        'lastname',
        'gender',
        'country',
        'language',
        'time',
    ];

    private const array RELATION_FIELDS = [
        'manyToOneRelation',
        'image',
        'video',
        'hotspotimage',
        'gallery',
        'manyToManyRelation',
        'manyToManyObjectRelation',
        'advancedManyToManyRelation',
        'advancedManyToManyObjectRelation',
        'reverseObjectRelation',
    ];

    /** @var iterable<self> */
    private iterable $otherObjectNormalizers;

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected FieldFetcher $fieldFetcher,
    ) {}

    #[Required]
    public function setOtherObjectNormalizers(#[AutowireIterator(self::TAG)] iterable $otherObjectNormalizers): void
    {
        $this->otherObjectNormalizers = $otherObjectNormalizers;
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|ArrayObject|null {
        $context = $this->getDefaultContext($context);
        $object = $this->toStandardObject($data, $format, $context);
        return $this->normalizer->normalize($object, $format, $context);
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

    /** A normalizer for the exact class describes it ahead of one which only supports a parent class */
    public function supports(Model $model): bool
    {
        $type = $model->getTypeInfo();
        if (!$type instanceof ObjectType) {
            return false;
        }

        $class = $type->getClassName();
        if ($this->supportsExactly($class)) {
            return true;
        }

        return array_any($this->getSupportedClasses(), fn(string $supported) => is_a($class, $supported, true))
            && !$this->anotherNormalizerSupportsExactly($class);
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
        $relationsAsIds = $this->utils->get(HelperContextBuilder::RELATIONS_AS_IDS, $context);
        $value = $data->get($field, $language);
        if ($relationsAsIds) {
            $fieldType = $this->fieldFetcher->getFieldDefinitionType($data, $field);
            if (!in_array($fieldType, self::RELATION_FIELDS)) {
                return $value;
            }

            $getId = fn(?ElementInterface $o) => $o?->getId();
            $value = match ($fieldType) {
                'manyToOneRelation', 'image' => $getId($value),
                'manyToManyRelation', 'manyToManyObjectRelation', 'reverseObjectRelation' => array_map(
                    $getId,
                    $value ?? [],
                ),
                'hotspotimage' => $value?->getImage()?->getId(),
                'gallery' => array_map(fn(Hotspotimage $i) => $i->getImage()?->getId(), $value?->getItems() ?? []),
                'video' => $value?->getData() instanceof Asset ? $value->getData()->getId() : $value,
                'advancedManyToManyRelation' => array_map(
                    fn(ElementMetadata $metadata) => $metadata->getElement()?->getId(),
                    $value ?? [],
                ),
                'advancedManyToManyObjectRelation' => array_map(
                    fn(ObjectMetadata $metadata) => $metadata->getObject()?->getId(),
                    $value ?? [],
                ),
                default => $value,
            };
        }
        return $value;
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

    private function anotherNormalizerSupportsExactly(string $class): bool
    {
        return array_any($this->otherObjectNormalizers, fn($other) => $other->supportsExactly($class));
    }

    private function supportsExactly(string $class): bool
    {
        return in_array($class, $this->getSupportedClasses(), true);
    }

    /** @return string[] */
    private function getSupportedClasses(): array
    {
        return array_keys($this->getSupportedTypes('json'));
    }

    private function describeDefinition(FieldDef $definition, Schema $property, array $context): void
    {
        $property->nullable = true;
        $relationsAsIds = $context[HelperContextBuilder::RELATIONS_AS_IDS] ?? false;

        // FIXME: Likely need to pull out to separate types
        $type = $definition->getFieldType();
        if (in_array($type, self::STRING_TYPES)) {
            $property->type = 'string';
        } elseif (in_array($type, ['numeric', 'slider'])) {
            $this->describeNumber($definition, $property);
        } elseif (in_array($type, ['checkbox', 'booleanSelect'])) {
            $property->type = 'boolean';
        } elseif (in_array($type, ['multiselect', 'arrayField', 'countrymultiselect', 'languagemultiselect'])) {
            $this->describeArray($property, ['type' => 'string']);
        } elseif (in_array($type, ['date', 'datetime'])) {
            $this->describeDate($property, $context);
        } elseif ($type === 'objectbricks') {
            $this->describeBricks($definition, $property, $context);
        } elseif ($type === 'manyToOneRelation') {
            if ($relationsAsIds) {
                $property->type = 'integer';
            } else {
                $this->describeRelation($definition, $property, $context);
            }
        } elseif (in_array($type, ['manyToManyObjectRelation', 'manyToManyRelation', 'reverseObjectRelation'])) {
            $this->describeArray(
                $property,
                $relationsAsIds ? ['type' => 'integer'] : $this->getRelationSchema($definition, $property, $context),
            );
        } elseif (
            in_array($type, [
                'image',
                'hotspotimage',
                'video',
                'advancedManyToManyRelation',
                'advancedManyToManyObjectRelation',
            ])
        ) {
            // FIXME: implement nested object describing
            if ($relationsAsIds) {
                $property->type = 'integer';
            }
        } elseif ($type === 'gallery') {
            // FIXME: implement nested object describing
            if ($relationsAsIds) {
                $this->describeArray($property, ['type' => 'integer']);
            }
        }
    }

    private function describeNumber(FieldDef $definition, Schema $property)
    {
        $isInt = $definition instanceof FieldDef\Numeric && $definition->getInteger();
        if ($isInt) {
            $property->type = 'integer';
        } else {
            $property->type = 'number';
        }
    }

    private function describeArray(Schema $property, array $items): void
    {
        $property->type = 'array';
        $property->items = new Items([...$items, '_context' => Util::createWeakContext($property->_context)]);
    }

    private function describeDate(Schema $property, array $context): void
    {
        $format = $context[HelperContextBuilder::DATE_FORMAT] ?? 'ISO8601';
        $property->type = 'string';
        if ($format === 'ISO8601') {
            $property->format = 'date-time';
            return;
        }
        $property->description = "Date format: $format";
    }

    /** @param FieldDef\Objectbricks $definition */
    private function describeBricks(FieldDef $definition, Schema $property, array $context): void
    {
        $property->type = 'object';
        foreach ($definition->getAllowedTypes() as $type) {
            $className = self::BRICK_NAMESPACE . $type;
            Util::getProperty($property, $type)->ref = $this->register($className, $context);
        }
    }

    private function describeRelation(FieldDef $definition, Schema $property, array $context): void
    {
        foreach ($this->getRelationSchema($definition, $property, $context) as $key => $value) {
            $property->$key = $value;
        }
    }

    /** Relations restricted to data object classes reference those models, anything else is a generic object */
    private function getRelationSchema(FieldDef $definition, Schema $property, array $context): array
    {
        $classes = match (true) {
            $definition instanceof FieldDef\ReverseObjectRelation => [$definition->getOwnerClassName()],
            $definition instanceof FieldDef\ManyToManyObjectRelation => array_column(
                $definition->getClasses(),
                'classes',
            ),
            $definition instanceof FieldDef\ManyToOneRelation &&
                !$definition->getAssetsAllowed() &&
                !$definition->getDocumentsAllowed()
                => array_column($definition->getClasses(), 'classes'),
            default => [],
        };
        if ($classes === []) {
            return ['type' => 'object'];
        }

        return [
            'oneOf' => array_map(
                fn(string $class) => new Schema([
                    'ref' => $this->register(self::DATA_OBJECT_NAMESPACE . $class, $context),
                    '_context' => Util::createWeakContext($property->_context),
                ]),
                $classes,
            ),
        ];
    }

    private function register(string $class, array $context): string
    {
        return $this->modelRegistry->register(new Model(Type::object($class), serializationContext: $context));
    }

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;
}
