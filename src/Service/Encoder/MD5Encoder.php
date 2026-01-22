<?php

namespace Torq\PimcoreHelpersBundle\Service\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class MD5Encoder extends JsonEncoder implements EncoderInterface {
    public const FORMAT = 'md5';

    public function encode(mixed $data, string $format, array $context = []): string
    {
        $json = parent::encode($data, $format, $context);
        return md5($json);
    }

    public function supportsEncoding(string $format): bool
    {
        return $format === self::FORMAT;
    }
}
