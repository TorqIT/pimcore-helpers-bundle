<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Normalizer;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Listing;
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

    public function testNestedNormalizersReceiveThePassedAndRequiredContextButNotTheDefaults()
    {
        $passedDown = null;
        $objectNormalizer = $this->createStub(NormalizerInterface::class);
        $objectNormalizer->method('normalize')->willReturnCallback(
            function (mixed $data, ?string $format, array $context) use (&$passedDown) {
                $passedDown = $context;
                return [];
            },
        );
        $normalizer = new class (
            $objectNormalizer,
            new ArrayUtils(),
            $this->createConfiguredStub(FieldFetcher::class, ['getFields' => []]),
            $this->describer,
        ) extends DataObjectNormalizer {
            protected function addRequiredContext(array $context): array
            {
                return HelperContextBuilder::create()->withContext($context)->relationsAsIds()->toArray();
            }
        };
        $passed = HelperContextBuilder::create()->withLanguage('en')->toArray();

        $normalizer->normalize($this->createStub(Concrete::class), 'json', $passed);

        $this->assertSame(
            [HelperContextBuilder::LANGUAGE => 'en', HelperContextBuilder::RELATIONS_AS_IDS => true],
            $passedDown,
        );
    }

    public function testSubclassCallingToStandardObjectWithItsOwnContextStillGetsTheDefaults()
    {
        $requested = null;
        $fieldFetcher = $this->createStub(FieldFetcher::class);
        $fieldFetcher->method('getFields')->willReturnCallback(
            function (object $object, bool $includeId, bool $includeProperties, array $excludedFields) use (
                &$requested,
            ) {
                $requested = compact('includeId', 'includeProperties', 'excludedFields');
                return [];
            },
        );
        $normalizer = new class (
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $fieldFetcher,
            $this->describer,
        ) extends DataObjectNormalizer {
            public function normalize(mixed $data, ?string $format = null, array $context = []): array
            {
                $ownContext = HelperContextBuilder::create()->excludeFields(['secret'])->toArray();

                return (array) $this->toStandardObject($data, $format, $ownContext);
            }
        };

        $normalizer->normalize($this->createStub(Concrete::class), 'json');

        $this->assertTrue($requested['includeId']);
        $this->assertFalse($requested['includeProperties']);
        $this->assertSame(['secret'], $requested['excludedFields']);
    }

    public function testRequiredContextReachesTheWholeNormalizer()
    {
        $objectNormalizer = $this->createStub(NormalizerInterface::class);
        $objectNormalizer->method('normalize')->willReturnCallback(fn(object $output) => (array) $output);
        $normalizer = new class (
            $objectNormalizer,
            new ArrayUtils(),
            $this->createConfiguredStub(FieldFetcher::class, ['getFields' => []]),
            $this->describer,
        ) extends DataObjectNormalizer {
            protected function addRequiredContext(array $context): array
            {
                return HelperContextBuilder::create()->withContext($context)->includeChildren()->toArray();
            }
        };
        $children = $this->createConfiguredStub(Listing::class, ['getData' => []]);
        $object = $this->createConfiguredStub(Concrete::class, ['getChildren' => $children]);

        $normalized = $normalizer->normalize($object, 'json');

        $this->assertArrayHasKey('children', $normalized);
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
