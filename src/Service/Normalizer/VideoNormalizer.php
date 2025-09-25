<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\Video;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer.torq.video')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class VideoNormalizer implements NormalizerInterface
{
    public function __construct(private AssetNormalizer $assetNormalizer)
    {
    }

    /* @param Video $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {

        $title = $data->getTitle();
        $description = $data->getDescription();
        $type = $data->getType();
        $value = $data->getData();
        $value = match ($type) {
            'asset' => $this->assetNormalizer->normalize($value, $format, $context),
            'youtube' => "https://youtu.be/$value",
            default => $value,
        };

        return [
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'data' => $value
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Video;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Video::class => true];
    }
}