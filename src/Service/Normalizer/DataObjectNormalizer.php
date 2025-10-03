<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;


use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use ArrayObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete as DataObject;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.data_object')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class DataObjectNormalizer extends AbstractObjectNormalizer
{
    public const INCLUDE_CHILDREN = 'includeChildren';
    public const CHILD_TYPES = 'childTypes';
    public const INHERIT_VALUES = 'inheritValues';

    private const DEFAULT_CHILD_TYPES = [
        AbstractObject::OBJECT_TYPE_OBJECT,
        AbstractObject::OBJECT_TYPE_VARIANT,
        AbstractObject::OBJECT_TYPE_FOLDER,
    ];

    /** @param DataObject $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $language = $this->utils->get(AbstractObjectNormalizer::LANGUAGE, $context);
        $inheritValues = $this->utils->get(self::INHERIT_VALUES, $context, true);
        $includeChildren = $this->utils->get(self::INCLUDE_CHILDREN, $context, false);
        $childTypes = $this->utils->get(self::CHILD_TYPES, $context, self::DEFAULT_CHILD_TYPES);

        // set for just this object
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(false);
        }

        $output = new stdClass();
        $fields = $this->getFields($data, $format, $context);
        foreach ($fields as $field) {
            $output->$field = $this->normalizeValue(
                $data->get($field, $language),
                $field,
                $language,
                $data,
                $format,
                $context
            );
        }

        if ($includeChildren) {
            $children = $data->getChildren($childTypes)->getData();
            $output->children = $children && count($children) > 0 ? $children : null;
        }

        // reset for next object being serialized
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(true);
        }

        return $this->normalizeOutput($output, $data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DataObject;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [DataObject::class => false];
    }

    protected function getFields(object $data, ?string $format = null, array $context = []): array
    {
        $fields = $this->fieldFetcher->getFields($data);
        return array_filter($fields, fn($field) => $this->includeField($data, $field));
    }

    private function includeField(object $data, string $field)
    {
        /** @var DataObject $data */
        $fieldDefinition = ClassDefinition::getByName($data->getClassName())?->getFieldDefinition($field);
        return $fieldDefinition?->getFieldType() !== 'reverseObjectRelation';
    }
}