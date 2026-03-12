<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Element\ElementInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

abstract class AbstractObjectNormalizer implements NormalizerInterface
{
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
        'reverseObjectRelation'
    ];


    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected FieldFetcher $fieldFetcher
    ) {
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        $object = $this->toStandardObject($data, $format, $context);
        return $this->normalizer->normalize($object, $format, $context);
    }

    protected function toStandardObject(mixed $data, ?string $format = null, array $context = []): stdClass
    {
        $castEmptyArrayToNull = $this->utils->get(HelperContextBuilder::EMPTY_ARRAYS_AS_NULL, $context, false);
        $skipNullValues = $this->utils->get(HelperContextBuilder::SKIP_NULL_VALUES, $context, false);

        $output = new stdClass();
        $fields = $this->getFields($data, $format, $context);
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
                'manyToManyRelation', 'manyToManyObjectRelation', 'reverseObjectRelation' => array_map($getId, $value ?? []),
                'hotspotimage' => $value?->getImage()?->getId(),
                'gallery' => array_map(fn(Hotspotimage $i) => $i->getImage()?->getId(), $value?->getItems() ?? []),
                'video' => $value?->getData() instanceof Asset ? $value->getData()->getId() : $value,
                'advancedManyToManyRelation' => $value?->getElement()?->getId(),
                'advancedManyToManyObjectRelation' => $value?->getObject()?->getId(),
                default => $value
            };
        }
        return $value;
    }

    /* @return string[] */
    protected function getFields(object $data, ?string $format = null, array $context = []): array
    {
        $includeId = $this->utils->get(HelperContextBuilder::INCLUDE_ID, $context, true);
        $includeProperties = $this->utils->get(HelperContextBuilder::INCLUDE_PROPERTIES, $context, false);
        $excludedFields = $this->utils->get(HelperContextBuilder::EXCLUDED_FIELDS, $context, []);
        $includedFieldTypes = $this->utils->get(HelperContextBuilder::INCLUDED_FIELD_TYPES, $context);
        $excludedFieldTypes = $this->utils->get(HelperContextBuilder::EXCLUDED_FIELD_TYPES, $context, []);

        return $this->fieldFetcher->getFields(
            $data,
            includeId: $includeId,
            includeProperties: $includeProperties,
            excludedFields: $excludedFields,
            includedFieldTypes: $includedFieldTypes,
            excludedFieldTypes: $excludedFieldTypes,
        );
    }

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;
}