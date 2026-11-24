<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use OpenApi\Annotations\Items;
use OpenApi\Annotations\Schema;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;

#[AsAlias('torq.normalizer.image_gallery', public: true)]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ImageGalleryNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    public function __construct(
        #[Autowire(service: 'torq.normalizer.hotspot_image')] private HotspotImageNormalizer $imageNormalizer,
        protected PimcoreModelDescriber $describer,
    )
    {
    }

    /* @param ImageGallery $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $images = $data->getItems();
        return count($images) > 0 ?
            array_map(fn($i) => $this->imageNormalizer->normalize($i, $format, $context), $images) :
            null;
    }

    public function describe(Model $model, Schema $schema): void
    {
        $schema->type = 'array';
        $schema->items = new Items(['_context' => Util::createWeakContext($schema->_context)]);
        $this->describer->describeClass(Image::class, $schema->items, $model->getSerializationContext());
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