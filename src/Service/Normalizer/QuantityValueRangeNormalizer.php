<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\QuantityValueRange;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer.torq.quantity_value_range')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class QuantityValueRangeNormalizer implements NormalizerInterface
{

    /* @param QuantityValueRange $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return [
            'minimum' => $data->getMinimum(),
            'maximum' => $data->getMaximum(),
            'unit' => $data->getUnit()?->getAbbreviation(),
            'unitName' => $data->getUnit()?->getId()
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof QuantityValueRange;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [QuantityValueRange::class => true];
    }
}