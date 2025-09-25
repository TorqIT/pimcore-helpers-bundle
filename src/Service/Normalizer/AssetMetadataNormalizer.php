<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadata;
use Torq\PimcoreHelpersBundle\Model\Asset\AssetMetadataType;
use Torq\PimcoreHelpersBundle\Repository\DataObjectRepository;
use ArrayObject;
use Carbon\Carbon;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Torq\PimcoreHelpersBundle\Repository\AssetRepository;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

#[AutoconfigureTag('serializer.normalizer.torq.asset_metadata')]
#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class AssetMetadataNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const IS_VALUE_SERIALIZED = 'isValueSerialized';

    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] private ObjectNormalizer $normalizer,
        private DataObjectRepository $objectRepository,
        private AssetRepository $assetRepository,
        private ArrayUtils $utils,
    ) {
    }

    /** @param AssetMetadata $data */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        return $this->normalizer->normalize($data, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $get = fn(string $key) => $this->utils->get($key, $data);
        $isValueSerialized = $this->utils->get(static::IS_VALUE_SERIALIZED, $context, false);

        $metadata = new AssetMetadata();
        $metadata->setName($get('name'));
        $metadata->setLanguage($get('language'));
        $assetType = AssetMetadataType::tryFromNullable($get('type')) ?? AssetMetadataType::INPUT;
        $metadata->setType($assetType);

        $value = $get('data');
        if ($isValueSerialized) {
            $value = $this->deserializeValue($value, $assetType);
        }
        $metadata->setData($value);
        return $metadata;
    }

    protected function deserializeValue(mixed $value, AssetMetadataType $type)
    {
        return match ($type) {
            AssetMetadataType::ASSET => $this->assetRepository->getById((int)$value),
            AssetMetadataType::OBJECT => $this->objectRepository->getById((int)$value),
            AssetMetadataType::DATE => $value ? Carbon::parse($value) : null,
            AssetMetadataType::CHECKBOX => (bool)$value,
            AssetMetadataType::DOCUMENT => throw new Exception('Asset metadata type `document` not yet supported.'),
            default => $value,
        };
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AssetMetadata;
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool {
        return is_array($data) && $type === AssetMetadata::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [AssetMetadata::class => true];
    }
}