<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

abstract class AbstractObjectNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')] protected NormalizerInterface $normalizer,
        protected ArrayUtils $utils,
        protected FieldFetcher $fieldFetcher
    ) {
    }

    abstract public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null;

    abstract public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;

    abstract public function getSupportedTypes(?string $format): array;

    /* @return string[] */
    abstract protected function getFields(object $data, ?string $format = null, array $context = []): array;

    protected function normalizeValue(
        mixed $value,
        string $field,
        ?string $language,
        object $data,
        ?string $format = null,
        array $context = []
    ) {
        if (is_array($value) && count($value) === 0) {
            return null;
        } else {
            return $value;
        }
    }

    protected function normalizeOutput(
        object $output,
        object $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|null
    {
        return $this->normalizer->normalize($output, $format, $context);
    }
}