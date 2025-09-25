<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Carbon\Carbon;
use Exception;
use Pimcore\Model\DataObject\Data\Video;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.carbon')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class CarbonNormalizer implements NormalizerInterface
{
    public function __construct(private ArrayUtils $utils)
    {
    }

    /* @param Carbon $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $dateFormat = $this->utils->get('dateFormat', $context, 'ISO8601');
        return $dateFormat === 'ISO8601' ? $data->toIso8601String() : $data->format($dateFormat);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Carbon;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Carbon::class => true];
    }
}