<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Support;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DescribingNormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Normalizer\PimcoreModelSupportTrait;

/** A describing normalizer which claims whichever classes a test gives it */
final class FakeDescribingNormalizer implements DescribingNormalizerInterface
{
    use PimcoreModelSupportTrait;

    /** @param class-string[] $supportedClasses */
    public function __construct(
        protected PimcoreModelDescriber $describer,
        private array $supportedClasses,
    ) {}

    public function describe(Model $model, Schema $schema): void
    {
        $schema->type = 'object';
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array|string|int|float|bool|ArrayObject|null {
        return null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function getSupportedTypes(?string $format): array
    {
        return array_fill_keys($this->supportedClasses, true);
    }
}
