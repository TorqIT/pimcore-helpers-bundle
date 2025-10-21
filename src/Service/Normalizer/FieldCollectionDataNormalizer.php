<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldData;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('serializer.normalizer.torq.field_collection_data')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class FieldCollectionDataNormalizer extends AbstractObjectNormalizer
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FieldData;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [FieldData::class => false];
    }
}