<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Carbon\CarbonPeriod;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Schema;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

#[AsAlias('torq.normalizer.carbon_period', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class CarbonPeriodNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    public function __construct(
        #[Autowire(service: 'torq.normalizer.carbon')] private CarbonNormalizer $carbonNormalizer,
        protected PimcoreModelDescriber $describer,
    )
    {
    }

    /* @param CarbonPeriod $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return [
            'startDate' => $this->carbonNormalizer->normalize($data->getStartDate(), $format, $context),
            'endDate' => $this->carbonNormalizer->normalize($data->getEndDate(), $format, $context),
        ];
    } 

    public function describe(Model $model, Schema $schema): void
    {
        $schema->type = 'object';
        foreach (['startDate', 'endDate'] as $name) {
            $this->describer->describeDate(Util::getProperty($schema, $name), $model->getSerializationContext());
        }
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