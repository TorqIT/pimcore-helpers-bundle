<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsAlias('torq.normalizer.hotspot_image', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class HotspotImageNormalizer implements NormalizerInterface
{
    public function __construct(#[Autowire(service: 'torq.normalizer.image')] private ImageNormalizer $imageNormalizer)
    {
    }

    /* @param Hotspotimage $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $image = $data?->getImage();
        return $image !== null ? $this->imageNormalizer->normalize($image, $format, $context) : null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Hotspotimage;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Hotspotimage::class => true];
    }
}