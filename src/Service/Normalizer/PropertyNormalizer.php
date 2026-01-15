<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use InvalidArgumentException;
use Pimcore\Model\Property;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer.torq.property')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class PropertyNormalizer implements NormalizerInterface
{
    public function __construct(
        private DataObjectNormalizer $dataObjectNormalizer,
        private AssetNormalizer $assetNormalizer,
    ) {}

    /** @param Property $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|ArrayObject|null {
        return match ($type = $data->getType()) {
            'object' => $this->dataObjectNormalizer->normalize($data->getData(), $format, $context),
            'asset' => $this->assetNormalizer->normalize($data->getData(), $format, $context),
            'document' => throw new InvalidArgumentException("Unsupported property type: $type"),
            default => $data->getData()
        };
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Property;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Property::class => true];
    }
}