<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Describer;

use DateTimeInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\ExternalImage;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DescribingNormalizerInterface;

/**
 * What describing normalizers delegate to when describing a type which isn't their own.
 *
 * It never describes a model itself. Nelmio only hands its model registry to the describers it iterates, so this is
 * registered as one, ahead of the describing normalizers, purely to receive that registry on their behalf.
 */
#[AsTaggedItem(priority: 20)]
class PimcoreModelDescriber implements ModelDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    /** The context keys which change the shape of normalized output; all are safe to serialize */
    private const array SHAPE_KEYS = [
        HelperContextBuilder::DATE_FORMAT,
        HelperContextBuilder::INCLUDE_CHILDREN,
        HelperContextBuilder::CHILD_TYPES,
        HelperContextBuilder::INCLUDE_ID,
        HelperContextBuilder::INCLUDE_PROPERTIES,
        HelperContextBuilder::EXCLUDED_FIELDS,
        HelperContextBuilder::INCLUDED_FIELD_TYPES,
        HelperContextBuilder::EXCLUDED_FIELD_TYPES,
        HelperContextBuilder::RELATIONS_AS_IDS,
    ];

    /** Value classes whose normalizers output a scalar */
    private const array SCALAR_CLASSES = [
        Link::class => 'string',
        UrlSlug::class => 'string',
        ExternalImage::class => 'string',
        RgbaColor::class => 'string',
        Consent::class => 'boolean',
    ];

    /** @param iterable<DescribingNormalizerInterface> $describingNormalizers */
    public function __construct(
        #[AutowireIterator(DescribingNormalizerInterface::TAG)] private iterable $describingNormalizers,
    ) {}

    public function supports(Model $model): bool
    {
        return false;
    }

    public function describe(Model $model, Schema $schema): void {}

    /** A normalizer for the exact class describes it ahead of one which only supports a parent class */
    public function isSupportedBy(DescribingNormalizerInterface $normalizer, Model $model): bool
    {
        $type = $model->getTypeInfo();
        if (!($type instanceof ObjectType)) {
            return false;
        }

        $class = $type->getClassName();
        $supportedClasses = $this->getSupportedClasses($normalizer);
        if (in_array($class, $supportedClasses, true)) {
            return true;
        }

        return array_any($supportedClasses, fn(string $supported) => is_a($class, $supported, true)) &&
            !$this->isSupportedExactly($class);
    }

    public function describeType(Type $type, Schema $schema, array $context): void
    {
        if ($type instanceof NullableType) {
            $this->describeType($type->getWrappedType(), $schema, $context);
        } elseif ($type instanceof CollectionType) {
            $this->describeCollection($type, $schema, $context);
        } elseif ($type instanceof UnionType) {
            $this->describeUnion($type, $schema, $context);
        } elseif ($type instanceof ObjectType) {
            $this->describeClass(ltrim($type->getClassName(), '\\'), $schema, $context);
        } elseif ($type instanceof BuiltinType) {
            $this->describeBuiltin($type, $schema);
        }
    }

    /** Inlines dates and scalar classes, references a described model, and otherwise leaves a generic object */
    public function describeClass(string $class, Schema $schema, array $context): void
    {
        if (is_a($class, DateTimeInterface::class, true)) {
            $this->describeDate($schema, $context);
            return;
        }
        if (isset(self::SCALAR_CLASSES[$class])) {
            $schema->type = self::SCALAR_CLASSES[$class];
            return;
        }

        $shapeContext = array_intersect_key($context, array_flip(self::SHAPE_KEYS));
        $model = new Model(Type::object($class), serializationContext: $shapeContext);
        if (!$this->isDescribable($model)) {
            $schema->type = 'object';
            return;
        }

        $ref = $this->modelRegistry->register($model);
        if ($schema->nullable !== true) {
            $schema->ref = $ref;
            return;
        }
        // OpenAPI 3.0 ignores siblings of $ref, so a nullable reference has to be wrapped
        $schema->oneOf = [new Schema(['ref' => $ref, '_context' => Util::createWeakContext($schema->_context)])];
    }

    /** @param string[] $classes */
    public function describeClasses(array $classes, Schema $schema, array $context): void
    {
        if ($classes === []) {
            $schema->type = 'object';
            return;
        }
        if (count($classes) === 1) {
            $this->describeClass($classes[0], $schema, $context);
            return;
        }

        $schema->oneOf = array_map(function (string $class) use ($schema, $context) {
            $classSchema = new Schema(['_context' => Util::createWeakContext($schema->_context)]);
            $this->describeClass($class, $classSchema, $context);
            return $classSchema;
        }, $classes);
    }

    /** @param array<string, string> $types OpenAPI types by property name */
    public function describeObject(array $types, Schema $schema, bool $nullable = true): void
    {
        $schema->type = 'object';
        foreach ($types as $name => $type) {
            $property = Util::getProperty($schema, $name);
            $property->type = $type;
            if ($nullable) {
                $property->nullable = true;
            }
        }
    }

    public function describeDate(Schema $schema, array $context): void
    {
        $format = $context[HelperContextBuilder::DATE_FORMAT] ?? 'ISO8601';
        $schema->type = 'string';
        if ($format === 'ISO8601') {
            $schema->format = 'date-time';
            return;
        }
        $schema->description = "Date format: $format";
    }

    private function describeCollection(CollectionType $type, Schema $schema, array $context): void
    {
        $schema->type = 'array';
        $schema->items = new Items(['_context' => Util::createWeakContext($schema->_context)]);
        $this->describeType($type->getCollectionValueType(), $schema->items, $context);
    }

    private function describeUnion(UnionType $type, Schema $schema, array $context): void
    {
        $schema->oneOf = array_map(function (Type $member) use ($schema, $context) {
            $memberSchema = new Schema(['_context' => Util::createWeakContext($schema->_context)]);
            $this->describeType($member, $memberSchema, $context);
            return $memberSchema;
        }, $type->getTypes());
    }

    private function describeBuiltin(BuiltinType $type, Schema $schema): void
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
            $schema->type = $openApiType;
        }
    }

    private function isDescribable(Model $model): bool
    {
        return array_any(
            [...$this->describingNormalizers],
            fn(DescribingNormalizerInterface $normalizer) => $normalizer->supports($model),
        );
    }

    private function isSupportedExactly(string $class): bool
    {
        return array_any(
            [...$this->describingNormalizers],
            fn(DescribingNormalizerInterface $normalizer) => in_array(
                $class,
                $this->getSupportedClasses($normalizer),
                true,
            ),
        );
    }

    /** @return string[] */
    private function getSupportedClasses(DescribingNormalizerInterface $normalizer): array
    {
        return array_keys($normalizer->getSupportedTypes('json'));
    }
}
