<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Carbon\CarbonPeriod;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[AsAlias('torq.normalizer.carbon_period', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class CarbonPeriodNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'torq.normalizer.carbon')] private CarbonNormalizer $carbonNormalizer,
    )
    {
    }

    /* @param CarbonPeriod $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $output = [];
        foreach ($data as $datum) {
            $output[] = $this->carbonNormalizer->normalize($datum, $format, $context);
        }
        return $output;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof CarbonPeriod;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [CarbonPeriod::class => true];
    }
}