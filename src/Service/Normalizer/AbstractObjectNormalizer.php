<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\Element\ElementInterface;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('torq.object_normalizer')]
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
        'reverseObjectRelation',
    ];

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

    public function getDefaultContext(array $context): array
    {
        $defaults = [
            HelperContextBuilder::INCLUDE_ID => true,
            HelperContextBuilder::INCLUDE_PROPERTIES => false,
            HelperContextBuilder::EXCLUDED_FIELDS => [],
            HelperContextBuilder::INCLUDED_FIELD_TYPES => null,
            HelperContextBuilder::EXCLUDED_FIELD_TYPES => [],
            HelperContextBuilder::EMPTY_ARRAYS_AS_NULL => false,
            HelperContextBuilder::SKIP_NULL_VALUES => false,
        ];

        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $context)) {
                $context[$key] = $default;
            }
        }

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
                'advancedManyToManyRelation' => $value?->getElement()?->getId(),
                'advancedManyToManyObjectRelation' => $value?->getObject()?->getId(),
                default => $value,
            };
        }
        return $value;
    }

    /* @return string[] */
    private function getFields(object $data, array $context = []): array
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

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;
}
