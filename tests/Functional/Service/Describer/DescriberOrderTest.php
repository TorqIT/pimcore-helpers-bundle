<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Functional\Service\Describer;

use Nelmio\ApiDocBundle\ModelDescriber\ObjectModelDescriber;
use Pimcore\Test\KernelTestCase;
use ReflectionProperty;
use Torq\PimcoreHelpersBundle\Service\Describer\PimcoreModelDescriber;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DescribingNormalizerInterface;

/**
 * Nelmio takes the first describer which supports a model, so everything here depends on tag priority.
 * A describing normalizer carries two Nelmio tags (ours with a priority, Nelmio's without) and which one Symfony
 * honours isn't documented.
 */
class DescriberOrderTest extends KernelTestCase
{
    /** @var object[] */
    private array $describers;

    protected function setUp(): void
    {
        static::bootKernel();

        $generator = static::getContainer()->get('nelmio_api_doc.generator.default');
        $describers = (new ReflectionProperty($generator, 'modelDescribers'))->getValue($generator);
        $this->describers = [...$describers];
    }

    public function testDescriberComesBeforeTheNormalizersSoItHasTheModelRegistryWhenTheyDescribe()
    {
        $describerPosition = $this->positionOf(PimcoreModelDescriber::class)[0];
        $normalizerPositions = $this->positionOf(DescribingNormalizerInterface::class);

        $this->assertLessThan(min($normalizerPositions), $describerPosition);
    }

    public function testDescribingNormalizersComeBeforeTheGenericObjectDescriber()
    {
        $genericPosition = $this->positionOf(ObjectModelDescriber::class)[0];
        $normalizerPositions = $this->positionOf(DescribingNormalizerInterface::class);

        $this->assertNotEmpty($normalizerPositions);
        $this->assertLessThan($genericPosition, max($normalizerPositions));
    }

    public function testEachDescribingNormalizerIsListedOnce()
    {
        $normalizers = array_filter(
            $this->describers,
            fn(object $describer) => $describer instanceof DescribingNormalizerInterface,
        );
        $classes = array_map(fn(object $normalizer) => $normalizer::class, $normalizers);

        $this->assertSame(array_unique($classes), $classes);
    }

    /** @return int[] */
    private function positionOf(string $class): array
    {
        return array_keys(array_filter($this->describers, fn(object $describer) => $describer instanceof $class));
    }
}
