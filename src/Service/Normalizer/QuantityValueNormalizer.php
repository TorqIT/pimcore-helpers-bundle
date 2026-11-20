<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[AsAlias('torq.normalizer.quantity_value', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class QuantityValueNormalizer implements DescribingNormalizerInterface
{
    use DescribingNormalizerTrait;

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

    public function describe(Model $model, Schema $schema): void
    {
        $isInput = is_a($model->getTypeInfo()->getClassName(), InputQuantityValue::class, true);

        $this->describeProperties($schema, [
            'value' => $isInput ? 'string' : 'number',
            'unit' => 'string',
            'unitName' => 'string',
        ]);
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