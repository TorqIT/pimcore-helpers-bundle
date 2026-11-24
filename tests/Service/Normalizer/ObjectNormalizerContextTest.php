<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DataObjectNormalizer;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Tests\Support\DescriberTestTrait;

class ObjectNormalizerContextTest extends TestCase
{
    use DescriberTestTrait;

    protected function setUp(): void
    {
        $this->createDescriber();
    }

    public function testDefaultsApplyWhenNothingIsPassed()
    {
        $context = $this->createNormalizer()->getDefaultContext([]);

        $this->assertTrue($context[HelperContextBuilder::INCLUDE_ID]);
        $this->assertFalse($context[HelperContextBuilder::INCLUDE_PROPERTIES]);
        $this->assertFalse($context[HelperContextBuilder::INCLUDE_CHILDREN]);
        $this->assertTrue($context[HelperContextBuilder::INHERIT_VALUES]);
    }

    public function testPassedContextWinsOverDefaults()
    {
        $passed = HelperContextBuilder::create()->includeId(false)->includeProperties()->toArray();

        $context = $this->createNormalizer()->getDefaultContext($passed);

        $this->assertFalse($context[HelperContextBuilder::INCLUDE_ID]);
        $this->assertTrue($context[HelperContextBuilder::INCLUDE_PROPERTIES]);
    }

    public function testRequiredContextOfTheNormalizerWinsOverPassedContext()
    {
        $normalizer = new class (
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createStub(FieldFetcher::class),
            $this->describer,
        ) extends DataObjectNormalizer {
            protected function addRequiredContext(array $context): array
            {
                return HelperContextBuilder::create()->withContext($context)->includeId(false)->toArray();
            }
        };
        $passed = HelperContextBuilder::create()->includeId()->includeProperties()->toArray();

        $context = $normalizer->getDefaultContext($passed);

        $this->assertFalse($context[HelperContextBuilder::INCLUDE_ID]);
        $this->assertTrue($context[HelperContextBuilder::INCLUDE_PROPERTIES]);
    }

    public function testReverseRelationsAreExcludedUnlessRelationsAreIds()
    {
        $normalizer = $this->createNormalizer();
        $asIds = HelperContextBuilder::create()->relationsAsIds()->toArray();

        $this->assertSame(
            ['reverseObjectRelation'],
            $normalizer->getDefaultContext([])[HelperContextBuilder::EXCLUDED_FIELD_TYPES],
        );
        $this->assertSame([], $normalizer->getDefaultContext($asIds)[HelperContextBuilder::EXCLUDED_FIELD_TYPES]);
    }

    public function testResolvingAnAlreadyResolvedContextChangesNothing()
    {
        $normalizer = $this->createNormalizer();
        $resolved = $normalizer->getDefaultContext([]);

        $this->assertSame($resolved, $normalizer->getDefaultContext($resolved));
    }

    private function createNormalizer(): DataObjectNormalizer
    {
        return new DataObjectNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createStub(FieldFetcher::class),
            $this->describer,
        );
    }
}
