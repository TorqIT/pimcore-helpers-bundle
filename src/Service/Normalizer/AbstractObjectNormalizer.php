<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use stdClass;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

abstract class AbstractObjectNormalizer implements NormalizerInterface
{
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
        $language = $this->utils->get(HelperContextBuilder::LANGUAGE, $context);
        $castEmptyArrayToNull = $this->utils->get(HelperContextBuilder::EMPTY_ARRAYS_AS_NULL, $context, false);
        $skipNullValues = $this->utils->get(HelperContextBuilder::SKIP_NULL_VALUES, $context, false);

        $output = new stdClass();
        $fields = $this->getFields($data, $format, $context);
        foreach ($fields as $field) {
            $value = $data->get($field, $language);
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