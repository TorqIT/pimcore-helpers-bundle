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

    /** @param mixed|array $asset */
    public function toDto(mixed $asset)
    {
        if (!$asset) {
            return null;
        } elseif (is_array($asset)) {
            return array_map([$this, '_toDto'], $asset);
        } else {
            return $this->_toDto($asset);
        }
    }

    abstract protected function _toDto(mixed $object);

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
