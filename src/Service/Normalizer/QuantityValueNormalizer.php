<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer.torq.quantity_value')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class QuantityValueNormalizer implements NormalizerInterface
{
    /* @param QuantityValue|InputQuantityValue $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return [
            'value' => $data->getValue(),
            'unit' => $data->getUnit()?->getAbbreviation(),
            'unitName' => $data->getUnit()?->getId()
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof QuantityValue || $data instanceof InputQuantityValue;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [QuantityValue::class => true, InputQuantityValue::class => true];
    }
}