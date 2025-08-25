<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use ArrayObject;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

abstract class AbstractDtoNormalizer implements NormalizerInterface
{
    public function __construct(protected ObjectNormalizer $objectNormalizer)
    {
    }

    /** @param mixed|array $object */
    public function toDto(mixed $object, ?string $format = null, array $context = [])
    {
        if (!$object) {
            return null;
        } elseif (is_array($object)) {
            return array_map(fn($o) => $this->convertToDto($o, $format, $context), $object);
        } else {
            return $this->convertToDto($object, $format, $context);
        }
    }

    abstract protected function convertToDto(mixed $object, ?string $format = null, array $context = []);

    protected function validateObjectType(mixed $object, ?string $format = null, array $context = [])
    {
        if ($object !== null && !$this->supportsNormalization($object, $format)) {
            throw new InvalidArgumentException(
                'object of type ' .
                    get_debug_type($object) .
                    ' must one of the following types: ' .
                    implode(', ', array_keys($this->getSupportedTypes($format)))
            );
        }
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): ArrayObject|array|string|int|float|bool|null
    {
        $dto = $this->toDto($object, $format, $context);
        if (is_array($dto)) {
            return array_map(fn(object $o) => $this->objectNormalizer->normalize($o, $format, $context), $dto);
        } elseif (is_scalar($object) || is_object($object)) {
            return $this->objectNormalizer->normalize($dto, $format, $context);
        } else {
            throw new InvalidArgumentException(
                'object of type: ' . get_debug_type($object) . ' must be either scalar, an object, or an array'
            );
        }
    }
}
