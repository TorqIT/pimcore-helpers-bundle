<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;


use ArrayObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete as DataObject;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[AutoconfigureTag('serializer.normalizer.torq.data_object')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class DataObjectNormalizer extends AbstractObjectNormalizer
{
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
        $language = $this->utils->get(HelperContextBuilder::LANGUAGE, $context);
        $inheritValues = $this->utils->get(HelperContextBuilder::INHERIT_VALUES, $context, true);
        $includeChildren = $this->utils->get(HelperContextBuilder::INCLUDE_CHILDREN, $context, false);
        $childTypes = $this->utils->get(HelperContextBuilder::CHILD_TYPES, $context, self::DEFAULT_CHILD_TYPES);
        $skipNullValues = $this->utils->get(HelperContextBuilder::SKIP_NULL_VALUES, $context, false);

        // set for just this object
        if (!$inheritValues) {
            AbstractObject::setGetInheritedValues(false);
        }

        $output = new stdClass();
        $fields = $this->getFields($data, $format, $context);
        foreach ($fields as $field) {
            $value = $this->normalizeValue(
                $data->get($field, $language),
                $field,
                $language,
                $data,
                $format,
                $context
            );

            if ($skipNullValues && $value === null) {
                continue;
            } else {
                $output->$field = $value;
            }
        }

        if ($includeChildren) {
            $children = $data->getChildren($childTypes)->getData();
            $output->children = $children && count($children) > 0 ? $children : null;
        }

        // reset for next thing being serialized
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
        return $this->fieldFetcher->getFields($data, excludedFieldTypes: ['reverseObjectRelation']);
    }
}