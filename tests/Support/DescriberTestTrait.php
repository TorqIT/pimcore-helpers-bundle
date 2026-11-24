<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Support;

use ArrayObject;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Schema;
use OpenApi\Context;
use Symfony\Component\TypeInfo\Type;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DescribingNormalizerInterface;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;

/** Builds a real describer and model registry, with no container */
trait DescriberTestTrait
{
    private ArrayObject $normalizers;
    private PimcoreModelDescriber $describer;

    private function createDescriber(): void
    {
        $this->normalizers = new ArrayObject();
        $this->describer = new PimcoreModelDescriber($this->normalizers, new ArrayUtils());
    }

    /** Makes the normalizers known to the describer, so the classes they support are referenced as models */
    private function registerNormalizers(DescribingNormalizerInterface ...$normalizers): void
    {
        foreach ($normalizers as $normalizer) {
            $this->normalizers->append($normalizer);
        }

        $api = new OpenApi(['_context' => new Context()]);
        $registry = new ModelRegistry([$this->describer, ...$this->normalizers], $api);
        $this->describer->setModelRegistry($registry);
    }

    private function modelOf(string $class, array $context = []): Model
    {
        return new Model(Type::object($class), serializationContext: $context);
    }

    private function newSchema(): Schema
    {
        return new Schema(['_context' => new Context()]);
    }

    private function describe(DescribingNormalizerInterface $normalizer, string $class, array $context = []): array
    {
        $schema = $this->newSchema();

        $normalizer->describe($this->modelOf($class, $context), $schema);

        return json_decode($schema->toJson(), true);
    }
}
