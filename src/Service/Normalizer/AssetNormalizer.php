<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\Asset;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadata;
use Torq\PimcoreHelpersBundle\Repository\AssetMetadataRepository;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.asset')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -2]])]
class AssetNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected AssetMetadataRepository $metadataRepository,
        protected RequestStack $requestStack
    ) {
    }

    /** @param Asset $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $output = new stdClass();
        $output->id = $data->getId();
        $output->fullPath = $this->getFullPath($data, $format, $context);
        $output->fileName = $data->getKey();
        $output->mimeType = $data->getMimeType();
        $output->fileType = $data->getType();
        $output->fileSize = $data->getFileSize(formatted: true);
        $metadata = $this->metadataRepository->getByAssetId($data->getId());
        $output->metadata = count($metadata) > 0 ? $this->convertAssetMetadataToObject($metadata) : null;
        return $this->normalizer->normalize($output, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Asset;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Asset::class => false];
    }

    protected function getFullPath(Asset $data, ?string $format, array $context)
    {
        if ($request = $this->requestStack->getCurrentRequest()) {
            return $request->getSchemeAndHttpHost() . $data->getFullPath();
        } else {
            return $data->getFullPath();
        }
    }

    /** @param AssetMetadata[] $metadata */
    protected function convertAssetMetadataToObject(array $metadata): ?object
    {
        $output = new stdClass();
        foreach ($metadata as $metadatum) {
            $fieldName = $metadatum->getName();
            $output->$fieldName = $metadatum->getData();
        }
        return $output;
    }
}