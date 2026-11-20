<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Schema;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Contracts\Service\Attribute\Required;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

/** Derives which models a describing normalizer supports from the types it normalizes, plus shared schema helpers */
trait DescribingNormalizerTrait
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

    /** @var iterable<DescribingNormalizerInterface> */
    private iterable $otherDescribingNormalizers;

    #[Required]
    public function setOtherDescribingNormalizers(
        #[AutowireIterator(DescribingNormalizerInterface::TAG)] iterable $otherDescribingNormalizers,
    ): void {
        $this->otherDescribingNormalizers = $otherDescribingNormalizers;
    }

    /** A normalizer for the exact class describes it ahead of one which only supports a parent class */
    public function supports(Model $model): bool
    {
        $type = $model->getTypeInfo();
        if (!$type instanceof ObjectType) {
            return false;
        }

        $class = $type->getClassName();
        $supportedClasses = self::getSupportedClasses($this);
        if (in_array($class, $supportedClasses, true)) {
            return true;
        }

        return array_any($supportedClasses, fn(string $supported) => is_a($class, $supported, true))
            && !$this->anotherNormalizerSupportsExactly($class);
    }

    /** @param array<string, string> $types OpenAPI types by property name */
    private function describeProperties(Schema $schema, array $types, bool $nullable = true): void
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

    /** References the model of a class, or falls back to a generic object when no normalizer describes it */
    private function describeModel(string $class, Schema $property, array $context): void
    {
        $shapeContext = array_intersect_key($context, array_flip(self::SHAPE_KEYS));
        $model = new Model(Type::object($class), serializationContext: $shapeContext);
        if (!$this->isDescribable($model)) {
            $property->type = 'object';
            return;
        }

        $ref = $this->modelRegistry->register($model);
        if ($property->nullable !== true) {
            $property->ref = $ref;
            return;
        }
        // OpenAPI 3.0 ignores siblings of $ref, so a nullable reference has to be wrapped
        $property->oneOf = [new Schema(['ref' => $ref, '_context' => Util::createWeakContext($property->_context)])];
    }

    private function isDescribable(Model $model): bool
    {
        return array_any(
            [$this, ...$this->otherDescribingNormalizers],
            fn(DescribingNormalizerInterface $normalizer) => $normalizer->supports($model),
        );
    }

    private function anotherNormalizerSupportsExactly(string $class): bool
    {
        return array_any(
            [...$this->otherDescribingNormalizers],
            fn(DescribingNormalizerInterface $other) => in_array($class, self::getSupportedClasses($other), true),
        );
    }

    /** @return string[] */
    private static function getSupportedClasses(DescribingNormalizerInterface $normalizer): array
    {
        return array_keys($normalizer->getSupportedTypes('json'));
    }
}
