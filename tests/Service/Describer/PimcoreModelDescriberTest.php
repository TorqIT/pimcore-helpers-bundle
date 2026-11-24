<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Describer;

use Carbon\Carbon;
use LogicException;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use OpenApi\Generator;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\Consent;
use Pimcore\Model\DataObject\Data\Link;
use stdClass;
use Symfony\Component\TypeInfo\Type;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Tests\Support\DescriberTestTrait;
use Torq\PimcoreHelpersBundle\Tests\Support\FakeDescribingNormalizer;

class PimcoreModelDescriberTest extends TestCase
{
    private const string ASSET_REF = '#/components/schemas/Asset';
    private const string IMAGE_REF = '#/components/schemas/Image';

    use DescriberTestTrait;

    protected function setUp(): void
    {
        $this->createDescriber();
    }

    public function testNullableTypeIsDescribedAsItsWrappedType()
    {
        $schema = $this->newSchema();

        $this->describer->describeType(Type::nullable(Type::string()), $schema, []);

        $this->assertSame('string', $schema->type);
    }

    public function testCollectionIsDescribedAsAnArrayOfItsValueType()
    {
        $schema = $this->newSchema();

        $this->describer->describeType(Type::list(Type::string()), $schema, []);

        $this->assertSame('array', $schema->type);
        $this->assertSame('string', $schema->items->type);
    }

    public function testUnionIsDescribedAsOneOfItsMembers()
    {
        $schema = $this->newSchema();

        $this->describer->describeType(Type::union(Type::string(), Type::int()), $schema, []);

        $memberTypes = array_map(fn(Schema $member) => $member->type, $schema->oneOf);
        $this->assertEqualsCanonicalizing(['string', 'integer'], $memberTypes);
    }

    public function testBuiltinTypesMapToOpenApiTypes()
    {
        $expectedTypes = [
            'string' => Type::string(),
            'integer' => Type::int(),
            'number' => Type::float(),
            'boolean' => Type::bool(),
        ];

        foreach ($expectedTypes as $expected => $type) {
            $schema = $this->newSchema();

            $this->describer->describeType($type, $schema, []);

            $this->assertSame($expected, $schema->type);
        }
    }

    public function testUnknownTypeIsLeftUntyped()
    {
        $schema = $this->newSchema();

        $this->describer->describeType(Type::mixed(), $schema, []);

        $this->assertSame(Generator::UNDEFINED, $schema->type);
    }

    public function testDateIsADateTimeStringByDefault()
    {
        $schema = $this->newSchema();

        $this->describer->describeClass(Carbon::class, $schema, []);

        $this->assertSame('string', $schema->type);
        $this->assertSame('date-time', $schema->format);
    }

    public function testDateWithACustomFormatDescribesThatFormat()
    {
        $schema = $this->newSchema();
        $context = HelperContextBuilder::create()->withDateFormat('Y-m-d')->toArray();

        $this->describer->describeClass(Carbon::class, $schema, $context);

        $this->assertSame('string', $schema->type);
        $this->assertSame(Generator::UNDEFINED, $schema->format);
        $this->assertStringContainsString('Y-m-d', $schema->description);
    }

    public function testClassesNormalizedToScalarsAreInlined()
    {
        $link = $this->newSchema();
        $consent = $this->newSchema();

        $this->describer->describeClass(Link::class, $link, []);
        $this->describer->describeClass(Consent::class, $consent, []);

        $this->assertSame('string', $link->type);
        $this->assertSame('boolean', $consent->type);
    }

    public function testDescribedClassIsReferenced()
    {
        $this->describeClassesWithNormalizers([Asset::class]);
        $schema = $this->newSchema();

        $this->describer->describeClass(Asset::class, $schema, []);

        $this->assertSame(self::ASSET_REF, $schema->ref);
    }

    public function testNullableReferenceIsWrappedInOneOf()
    {
        $this->describeClassesWithNormalizers([Asset::class]);
        $schema = $this->newSchema();
        $schema->nullable = true;

        $this->describer->describeClass(Asset::class, $schema, []);

        $this->assertSame(Generator::UNDEFINED, $schema->ref);
        $this->assertSame(self::ASSET_REF, $schema->oneOf[0]->ref);
    }

    public function testClassWithoutADescribingNormalizerIsAGenericObject()
    {
        $this->describeClassesWithNormalizers([Asset::class]);
        $schema = $this->newSchema();

        $this->describer->describeClass(stdClass::class, $schema, []);

        $this->assertSame('object', $schema->type);
        $this->assertSame(Generator::UNDEFINED, $schema->ref);
    }

    public function testContextWhichCannotBeSerializedDoesNotBreakAReference()
    {
        $this->describeClassesWithNormalizers([Asset::class]);
        $schema = $this->newSchema();
        $context = HelperContextBuilder::create()->withGroupFilter(fn() => true)->relationsAsIds()->toArray();

        $this->describer->describeClass(Asset::class, $schema, $context);

        $this->assertSame(self::ASSET_REF, $schema->ref);
    }

    public function testNoClassesAreAGenericObject()
    {
        $schema = $this->newSchema();

        $this->describer->describeClasses([], $schema, []);

        $this->assertSame('object', $schema->type);
    }

    public function testOneClassIsReferencedDirectly()
    {
        $this->describeClassesWithNormalizers([Asset::class]);
        $schema = $this->newSchema();

        $this->describer->describeClasses([Asset::class], $schema, []);

        $this->assertSame(self::ASSET_REF, $schema->ref);
    }

    public function testSeveralClassesAreAUnionOfReferences()
    {
        $this->describeClassesWithNormalizers([Asset::class], [Image::class]);
        $schema = $this->newSchema();

        $this->describer->describeClasses([Asset::class, Image::class], $schema, []);

        $refs = array_map(fn(Schema $member) => $member->ref, $schema->oneOf);
        $this->assertSame([self::ASSET_REF, self::IMAGE_REF], $refs);
    }

    public function testObjectPropertiesAreNullableByDefault()
    {
        $schema = $this->newSchema();

        $this->describer->describeObject(['minimum' => 'number'], $schema);

        $this->assertSame('object', $schema->type);
        $this->assertSame('number', $schema->properties[0]->type);
        $this->assertTrue($schema->properties[0]->nullable);
    }

    public function testObjectPropertiesCanBeNonNullable()
    {
        $schema = $this->newSchema();

        $this->describer->describeObject(['id' => 'integer'], $schema, nullable: false);

        $this->assertSame(Generator::UNDEFINED, $schema->properties[0]->nullable);
    }

    public function testNormalizerSupportsTheExactClassItNormalizes()
    {
        $normalizer = new FakeDescribingNormalizer($this->describer, [Asset::class]);
        $this->normalizers->append($normalizer);

        $this->assertTrue($this->describer->isSupportedBy($normalizer, $this->modelOf(Asset::class)));
    }

    public function testNormalizerSupportsAChildClassNoOtherNormalizerClaims()
    {
        $normalizer = new FakeDescribingNormalizer($this->describer, [Asset::class]);
        $this->normalizers->append($normalizer);

        $this->assertTrue($this->describer->isSupportedBy($normalizer, $this->modelOf(Image::class)));
    }

    public function testNormalizerYieldsAChildClassToTheNormalizerWhichClaimsItExactly()
    {
        $assetNormalizer = new FakeDescribingNormalizer($this->describer, [Asset::class]);
        $imageNormalizer = new FakeDescribingNormalizer($this->describer, [Image::class]);
        $this->normalizers->append($assetNormalizer);
        $this->normalizers->append($imageNormalizer);

        $this->assertFalse($this->describer->isSupportedBy($assetNormalizer, $this->modelOf(Image::class)));
        $this->assertTrue($this->describer->isSupportedBy($imageNormalizer, $this->modelOf(Image::class)));
    }

    public function testNormalizerDoesNotSupportAnUnrelatedClassOrANonObjectType()
    {
        $normalizer = new FakeDescribingNormalizer($this->describer, [Asset::class]);
        $this->normalizers->append($normalizer);

        $this->assertFalse($this->describer->isSupportedBy($normalizer, $this->modelOf(stdClass::class)));
        $this->assertFalse($this->describer->isSupportedBy($normalizer, new Model(Type::string())));
    }

    public function testDescriberNeverDescribesAModelItself()
    {
        $this->assertFalse($this->describer->supports($this->modelOf(Asset::class)));

        $this->expectException(LogicException::class);

        $this->describer->describe($this->modelOf(Asset::class), $this->newSchema());
    }

    /** @param class-string[] ...$supportedClasses the classes claimed by each registered normalizer */
    private function describeClassesWithNormalizers(array ...$supportedClasses): void
    {
        $this->registerNormalizers(...array_map(
            fn(array $classes) => new FakeDescribingNormalizer($this->describer, $classes),
            $supportedClasses,
        ));
    }
}
