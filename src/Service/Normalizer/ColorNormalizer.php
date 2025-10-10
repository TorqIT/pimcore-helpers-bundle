<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject\Data\RgbaColor;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.color')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ColorNormalizer implements NormalizerInterface
{
    public function __construct(private ArrayUtils $utils)
    {
    }

    /* @param RgbaColor $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $withAlpha = $this->utils->get(HelperContextBuilder::WITH_ALPHA, $context, false);
        $withHash = $this->utils->get(HelperContextBuilder::WITH_HASH, $context, true);
        return $data->getHex($withAlpha, $withHash);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof RgbaColor;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [RgbaColor::class => true];
    }
}