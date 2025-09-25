<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AutoconfigureTag('serializer.normalizer.torq.image_gallery')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ImageGalleryNormalizer implements NormalizerInterface
{
    public function __construct(private HotspotImageNormalizer $imageNormalizer)
    {
    }

    /* @param ImageGallery $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $images = $data->getItems();
        return array_map(fn($i) => $this->imageNormalizer->normalize($i, $format, $context), $images);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ImageGallery;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ImageGallery::class => true];
    }
}