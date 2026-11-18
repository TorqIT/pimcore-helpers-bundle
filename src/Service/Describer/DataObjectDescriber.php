<?php

namespace Torq\PimcoreHelpersBundle\Service\Describer;

use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AbstractObjectNormalizer;

/** Describes any model whose class is supported by a schema describing normalizer */
#[AsTaggedItem(priority: 10)]
class DataObjectDescriber implements ModelDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

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

    /** @param iterable<NormalizerInterface> $normalizers */
    public function __construct(
        #[AutowireIterator('torq.object_normalizer')] private iterable $normalizers,
        private FieldFetcher $fieldFetcher,
    ) {}

    public function supports(Model $model): bool
    {
        return $this->getNormalizer($model) !== null;
    }

    public function describe(Model $model, Schema $schema): void
    {
        $context = $this->getNormalizer($model)->getDefaultContext($model->getSerializationContext());
        $class = $this->getClass($model);

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

    private function getFields(string $class, array $context): array
    {
        return $this->fieldFetcher->getFields(
            $class,
            includeId: $context[HelperContextBuilder::INCLUDE_ID],
            includeProperties: $context[HelperContextBuilder::INCLUDE_PROPERTIES],
            excludedFields: $context[HelperContextBuilder::EXCLUDED_FIELDS],
            includedFieldTypes: $context[HelperContextBuilder::INCLUDED_FIELD_TYPES],
            excludedFieldTypes: $context[HelperContextBuilder::EXCLUDED_FIELD_TYPES],
        );
    }

    private function getNormalizer(Model $model): ?AbstractObjectNormalizer
    {
        if (($class = $this->getClass($model)) === null) {
            return null;
        }

        $fallback = null;
        foreach ($this->normalizers as $normalizer) {
            $supportedTypes = array_keys($normalizer->getSupportedTypes('json'));
            if (in_array($class, $supportedTypes, true)) {
                return $normalizer;
            }
            if (array_any($supportedTypes, fn(string $type) => is_a($class, $type, true))) {
                $fallback ??= $normalizer;
            }
        }
        return $fallback;
    }

    private function getClass(Model $model): ?string
    {
        $type = $model->getTypeInfo();
        return $type instanceof ObjectType ? $type->getClassName() : null;
    }

    private function describeDefinition(FieldDef $definition, Schema $property, array $context)
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
}
