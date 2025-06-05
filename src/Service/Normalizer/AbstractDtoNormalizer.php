<?php

namespace Torq\PimcoreHelpersBundle\Service\Normalizer;

use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

abstract class AbstractDtoNormalizer implements NormalizerInterface
{
    public function __construct(protected ObjectNormalizer $objectNormalizer)
    {
    }

    /** @param mixed|array $object */
    public function toDto(mixed $object)
    {
        if (!$object) {
            return null;
        } elseif (is_array($object)) {
            return array_map([$this, 'convertToDto'], $object);
        } else {
            return $this->convertToDto($object);
        }
    }

    abstract protected function convertToDto(mixed $object);

    protected function validateObjectType(mixed $object, ?string $format = null)
    {
        if ($object !== null && !$this->supportsNormalization($object, $format)) {
            throw new InvalidArgumentException(
                'object of type ' .
                    get_debug_type($object) .
                    ' must one of the following types: ' .
                    implode(', ', $this->getSupportedTypes($format))
            );
        }
    }

    public function normalize(mixed $object, ?string $format = null, array $context = [])
    {
        $dto = $this->toDto($object);
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
