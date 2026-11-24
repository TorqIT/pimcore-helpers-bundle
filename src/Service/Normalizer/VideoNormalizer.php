<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Schema;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Video;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

#[AsAlias('torq.normalizer.video', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class VideoNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    public function __construct(
        #[Autowire(service: 'torq.normalizer.asset')] private AssetNormalizer $assetNormalizer,
        protected PimcoreModelDescriber $describer,
    ) {
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

    public function describe(Model $model, Schema $schema): void
    {
        $this->describer->describeObject([
            'title' => 'string',
            'description' => 'string',
            'type' => 'string',
        ], $schema);

        $weakContext = Util::createWeakContext($schema->_context);
        $asset = new Schema(['_context' => $weakContext]);
        $this->describer->describeClass(Asset::class, $asset, $model->getSerializationContext());

        $data = Util::getProperty($schema, 'data');
        $data->nullable = true;
        $data->oneOf = [new Schema(['type' => 'string', '_context' => $weakContext]), $asset];
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