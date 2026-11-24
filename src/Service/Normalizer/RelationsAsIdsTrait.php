<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;

trait RelationsAsIdsTrait
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

    private function relationsToIds(?string $fieldType, mixed $value): mixed
    {
        $getId = fn(?ElementInterface $element) => $element?->getId();

        return match ($fieldType) {
            'manyToOneRelation', 'image' => $getId($value),
            'manyToManyRelation', 'manyToManyObjectRelation', 'reverseObjectRelation' => array_map(
                $getId,
                $value ?? [],
            ),
            'hotspotimage' => $value?->getImage()?->getId(),
            'gallery' => array_map(fn(Hotspotimage $i) => $i->getImage()?->getId(), $value?->getItems() ?? []),
            'video' => $value?->getData() instanceof Asset ? $value->getData()->getId() : $value,
            'advancedManyToManyRelation' => array_map(
                fn(ElementMetadata $metadata) => $metadata->getElement()?->getId(),
                $value ?? [],
            ),
            'advancedManyToManyObjectRelation' => array_map(
                fn(ObjectMetadata $metadata) => $metadata->getObject()?->getId(),
                $value ?? [],
            ),
            default => $value,
        };
    }
}
