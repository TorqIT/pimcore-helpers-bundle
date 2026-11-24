<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\Data\QuantityValueRange;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

#[AsAlias('torq.normalizer.quantity_value_range', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class QuantityValueRangeNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    public function __construct(
        protected PimcoreModelDescriber $describer,
    ) {}

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

    public function describe(Model $model, Schema $schema): void
    {
        $this->describer->describeObject([
            'minimum' => 'number',
            'maximum' => 'number',
            'unit' => 'string',
            'unitName' => 'string',
        ], $schema);
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