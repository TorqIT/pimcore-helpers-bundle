<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Encoder\MD5Encoder;

#[Autoconfigure(tags: [['name' => 'serializer.normalizer', 'priority' => -1]])]
class DataObjectMD5Normalizer implements NormalizerInterface
{
    public function __construct(protected DataObjectNormalizer $dataObjectNormalizer) {}

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null {
        if (!$data instanceof Concrete) {
            return null;
        }

        $excludedFields = $context[HelperContextBuilder::EXCLUDED_FIELDS] ?? [];

        $context = HelperContextBuilder::create()
            ->withContext($context)
            ->includeProperties()
            ->excludeFields($excludedFields)
            ->toArray();

        $output = $this->dataObjectNormalizer->normalize($data, $format, $context);

        if (is_array($output)) {
            $output = $this->transformFields($data, $output, $excludedFields);
        }

        return $output;
    }

    protected function transformFields(Concrete $data, array $output, array $excludedFields): array
    {
        $objectIdGetter = fn(?DataObject $o) => $o?->getId();
        $classDefinition = $data->getClass();
        $fieldDefinitions = $classDefinition->getFieldDefinitions();

        foreach ($fieldDefinitions as $fieldName => $fieldDef) {
            if (in_array($fieldName, $excludedFields)) {
                continue;
            }

            $fieldType = $fieldDef->getFieldtype();

            if (!in_array($fieldType, ['date', 'image', 'asset', 'manyToManyObjectRelation', 'reverseObjectRelation', 'manyToOneRelation'])) {
                continue;
            }

            $getter = 'get' . ucfirst($fieldName);
            if (!method_exists($data, $getter)) {
                continue;
            }

            $value = $data->$getter();

            $output[$fieldName] = match ($fieldType) {
                'date' => $value?->toDateString(),
                'image', 'asset' => $value?->getId(),
                'manyToManyObjectRelation', 'reverseObjectRelation' => is_array($value) ? array_map($objectIdGetter, $value) : [],
                'manyToOneRelation' => $objectIdGetter($value),
            };
        }

        return $output;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Concrete && $format === MD5Encoder::FORMAT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return $format === MD5Encoder::FORMAT ? [Concrete::class => false] : [];
    }
}
