<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadata;
use Torq\PimcoreHelpersBundle\Repository\AssetMetadataRepository;
use ArrayObject;
use Pimcore\Model\Asset;
use stdClass;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.asset')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class AssetNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] private NormalizerInterface $normalizer,
        private ArrayUtils $utils,
        private AssetMetadataRepository $metadataRepository,
        private RequestStack $requestStack
    ) {
    }

    /** @param Asset $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        $language = $this->utils->get(AbstractObjectNormalizer::LANGUAGE, $context, '');
        $request = $this->requestStack->getCurrentRequest();

        $output = new stdClass();
        $output->id = $data->getId();
        if ($request !== null) {
            $output->fullPath = $request->getSchemeAndHttpHost() . $data->getFullPath();
        } else {
            $output->fullPath = $data->getFullPath();
        }
        $output->fileName = $data->getKey();
        $output->mimeType = $data->getMimeType();
        $output->fileType = $data->getType();
        $output->fileSize = $data->getFileSize(formatted: true);
        $metadata = $this->metadataRepository->getByAssetId($data->getId(), $language);
        $output->metadata = count($metadata) > 0 ? $this->convertAssetMetadataToObject($metadata) : null;
        return $this->normalizer->normalize($output, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Asset;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Asset::class => true];
    }

    /** @param AssetMetadata[] $metadata */
    protected function convertAssetMetadataToObject(array $metadata): object
    {
        $output = new stdClass();
        foreach ($metadata as $metadatum) {
            $fieldName = $metadatum->getName();
            $output->$fieldName = $metadatum->getData();
        }
        return $output;
    }
}