<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldData;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[AutoconfigureTag('serializer.normalizer.torq.field_collection_data')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class FieldCollectionDataNormalizer extends AbstractObjectNormalizer
{
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $language = $this->utils->get(HelperContextBuilder::LANGUAGE, $context);

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
        return $data instanceof FieldData;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [FieldData::class => false];
    }

    protected function getFields(object $data, ?string $format = null, array $context = []): array
    {
        return $this->fieldFetcher->getFields($data);
    }
}