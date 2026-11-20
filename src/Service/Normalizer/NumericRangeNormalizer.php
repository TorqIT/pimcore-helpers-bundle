<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\Data\NumericRange;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias('torq.normalizer.numeric_range', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class NumericRangeNormalizer implements DescribingNormalizerInterface
{
    use DescribingNormalizerTrait;

    /* @param NumericRange $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return [
            'minimum' => $data->getMinimum(),
            'maximum' => $data->getMaximum()
        ];
    }

    public function describe(Model $model, Schema $schema): void
    {
        $this->describeProperties($schema, ['minimum' => 'number', 'maximum' => 'number']);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof NumericRange;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [NumericRange::class => true];
    }
}