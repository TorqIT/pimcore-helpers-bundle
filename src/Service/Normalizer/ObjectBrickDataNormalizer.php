<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectBrickData;

class ObjectBrickDataNormalizer extends FieldCollectionDataNormalizer
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ObjectBrickData;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ObjectBrickData::class => false];
    }
}