<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectBrickData;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias('torq.normalizer.object_brick_data', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
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