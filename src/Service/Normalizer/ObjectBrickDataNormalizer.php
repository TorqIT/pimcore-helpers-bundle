<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectBrickData;
use stdClass;

class ObjectBrickDataNormalizer extends AbstractObjectNormalizer
{
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $language = $this->utils->get(self::LANGUAGE, $context);

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
        return $this->normalizeOutput($output, $data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ObjectBrickData;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ObjectBrickData::class => false];
    }

    protected function getFields(object $data, ?string $format = null, array $context = []): array
    {
        return $this->fieldFetcher->getFieldsFromObject($data::class, includeId: false);
    }
}