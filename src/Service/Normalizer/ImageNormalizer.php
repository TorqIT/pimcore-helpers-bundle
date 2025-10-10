<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class ImageNormalizer extends AssetNormalizer
{
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Image;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Image::class => true];
    }

    protected function getFullPath(Asset $data, ?string $format, array $context)
    {
        if (!$data instanceof Image) {
            return parent::getFullPath($data, $format, $context);
        }
        $thumbnail = $this->utils->get(HelperContextBuilder::THUMBNAIL, $context);
        $path = $thumbnail ? $data->getThumbnail($thumbnail)?->getFrontendPath() : $data->getFullPath();
        if ($request = $this->requestStack->getCurrentRequest()) {
            return $request->getSchemeAndHttpHost() . $path;
        } else {
            return $path;
        }

    }
}