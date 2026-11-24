<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Normalizer;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data\ArrayField;
use Torq\PimcoreHelpersBundle\Repository\AssetMetadataRepository;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AssetNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\DataObjectNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\FieldCollectionDataNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ObjectBrickDataNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\VideoNormalizer;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Tests\Support\DescriberTestTrait;
use Torq\PimcoreHelpersBundle\Tests\Support\FixtureBrick;
use Torq\PimcoreHelpersBundle\Tests\Support\FixtureFieldCollection;

/** Describes objects whose fields come from a stubbed FieldFetcher, so no class definitions are needed */
class ObjectNormalizerDescriptionTest extends TestCase
{
    use DescriberTestTrait;

    private const string BRICK_NAMESPACE = 'Pimcore\\Model\\DataObject\\Objectbrick\\Data\\';
    private const string FIELD_COLLECTION_NAMESPACE = 'Pimcore\\Model\\DataObject\\Fieldcollection\\Data\\';

    private const array NULLABLE_STRING = ['type' => 'string', 'nullable' => true];
    private const array NESTED_FIELDS = ['label'];

    public static function setUpBeforeClass(): void
    {
        // Pimcore generates these classes per project; the describer only ever builds their names
        self::aliasOnce(FixtureBrick::class, self::BRICK_NAMESPACE . 'FixtureBrick');
        self::aliasOnce(FixtureFieldCollection::class, self::FIELD_COLLECTION_NAMESPACE . 'FixtureFieldCollection');
        self::aliasOnce(FixtureFieldCollection::class, self::FIELD_COLLECTION_NAMESPACE . 'OtherFieldCollection');
    }

    protected function setUp(): void
    {
        $this->createDescriber();
    }

    public function testScalarFieldsAreDescribedByTheirType()
    {
        $integer = new FieldDef\Numeric();
        $integer->setInteger(true);

        $properties = $this->describeFields([
            'name' => new FieldDef\Input(),
            'price' => new FieldDef\Numeric(),
            'stock' => $integer,
            'active' => new FieldDef\Checkbox(),
            'tags' => new FieldDef\Multiselect(),
            'aliases' => new ArrayField(),
        ]);

        $strings = ['type' => 'array', 'items' => ['type' => 'string'], 'nullable' => true];
        $this->assertSame(self::NULLABLE_STRING, $properties['name']);
        $this->assertSame(['type' => 'number', 'nullable' => true], $properties['price']);
        $this->assertSame(['type' => 'integer', 'nullable' => true], $properties['stock']);
        $this->assertSame(['type' => 'boolean', 'nullable' => true], $properties['active']);
        $this->assertEquals($strings, $properties['tags']);
        $this->assertEquals($strings, $properties['aliases']);
    }

    public function testDateFieldFollowsTheContextDateFormat()
    {
        $context = HelperContextBuilder::create()->withDateFormat('Y-m-d')->toArray();

        $default = $this->describeFields(['createdOn' => new FieldDef\Date()])['createdOn'];
        $formatted = $this->describeFields(['createdOn' => new FieldDef\Date()], $context)['createdOn'];

        $this->assertSame('date-time', $default['format']);
        $this->assertStringContainsString('Y-m-d', $formatted['description']);
    }

    public function testIdAndPropertiesAreDescribedWithoutAFieldDefinition()
    {
        $properties = $this->describeFields(['id' => null, 'properties' => null]);

        $this->assertSame(['type' => 'integer'], $properties['id']);
        $this->assertSame(['type' => 'object'], $properties['properties']);
    }

    public function testRelationsAreIdsWhenTheContextAsksForThem()
    {
        $context = HelperContextBuilder::create()->relationsAsIds()->toArray();

        $properties = $this->describeFields([
            'owner' => new FieldDef\ManyToOneRelation(),
            'members' => new FieldDef\ManyToManyObjectRelation(),
        ], $context);

        $this->assertSame(['type' => 'integer', 'nullable' => true], $properties['owner']);
        $this->assertEquals(
            ['type' => 'array', 'items' => ['type' => 'integer'], 'nullable' => true],
            $properties['members'],
        );
    }

    public function testVideoIsAnAssetIdOrTheVideoWhenRelationsAreIds()
    {
        $assetNormalizer = new AssetNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createStub(AssetMetadataRepository::class),
            new RequestStack(),
            $this->describer,
        );
        $this->registerNormalizers($assetNormalizer, new VideoNormalizer($assetNormalizer, $this->describer));
        $context = HelperContextBuilder::create()->relationsAsIds()->toArray();

        $properties = $this->describeFields(['trailer' => new FieldDef\Video()], $context);

        $this->assertSame(
            [['type' => 'integer'], ['$ref' => '#/components/schemas/Video']],
            $properties['trailer']['oneOf'],
        );
    }

    public function testEncryptedFieldIsDescribedAsTheFieldItEncrypts()
    {
        $encrypted = new FieldDef\EncryptedField();
        $encrypted->setDelegate(new FieldDef\Input());

        $properties = $this->describeFields(['secret' => $encrypted]);

        $this->assertSame(self::NULLABLE_STRING, $properties['secret']);
    }

    public function testBlockIsAnArrayOfRowsWithADataPropertyPerChildField()
    {
        $block = new FieldDef\Block();
        $block->setChildren([$this->named(new FieldDef\Input(), 'label'), $this->named(new FieldDef\Checkbox(), 'on')]);

        $rows = $this->describeFields(['options' => $block])['options'];

        $this->assertSame('array', $rows['type']);
        $this->assertEquals(
            ['type' => 'object', 'properties' => ['data' => self::NULLABLE_STRING]],
            $rows['items']['properties']['label'],
        );
        $this->assertSame('boolean', $rows['items']['properties']['on']['properties']['data']['type']);
    }

    public function testBlockNestedInABlockIsDescribed()
    {
        $inner = new FieldDef\Block();
        $inner->setChildren([$this->named(new FieldDef\Input(), 'label')]);
        $outer = new FieldDef\Block();
        $outer->setChildren([$this->named($inner, 'inner')]);

        $rows = $this->describeFields(['outer' => $outer])['outer'];

        $innerRows = $rows['items']['properties']['inner']['properties']['data'];
        $this->assertSame('array', $innerRows['type']);
        $this->assertSame(self::NULLABLE_STRING, $innerRows['items']['properties']['label']['properties']['data']);
    }

    public function testBricksAreAnObjectWithAReferencePerAllowedType()
    {
        $bricks = new FieldDef\Objectbricks();
        $bricks->allowedTypes = ['fixtureBrick'];
        $brickNormalizer = new ObjectBrickDataNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createFieldFetcher(array_fill_keys(self::NESTED_FIELDS, new FieldDef\Input())),
            $this->describer,
        );
        $this->registerNormalizers($brickNormalizer);

        $described = $this->describeFields(['details' => $bricks])['details'];

        $this->assertSame('object', $described['type']);
        $this->assertSame(
            ['fixtureBrick' => ['$ref' => '#/components/schemas/FixtureBrick']],
            $described['properties'],
        );
    }

    public function testFieldCollectionWithOneTypeIsAnArrayOfThatType()
    {
        $this->registerFieldCollectionNormalizer();
        $collection = new FieldDef\Fieldcollections();
        $collection->allowedTypes = ['fixtureFieldCollection'];

        $described = $this->describeFields(['items' => $collection])['items'];

        $this->assertSame('array', $described['type']);
        $this->assertSame(['$ref' => '#/components/schemas/FixtureFieldCollection'], $described['items']);
    }

    public function testFieldCollectionWithSeveralTypesIsAnArrayOfTheirUnion()
    {
        $this->registerFieldCollectionNormalizer();
        $collection = new FieldDef\Fieldcollections();
        $collection->allowedTypes = ['fixtureFieldCollection', 'otherFieldCollection'];

        $described = $this->describeFields(['items' => $collection])['items'];

        $this->assertSame(
            [
                ['$ref' => '#/components/schemas/FixtureFieldCollection'],
                ['$ref' => '#/components/schemas/OtherFieldCollection'],
            ],
            $described['items']['oneOf'],
        );
    }

    public function testChildrenAreOnlyDescribedWhenIncluded()
    {
        $properties = $this->describeFields(['name' => new FieldDef\Input()]);

        $this->assertArrayNotHasKey('children', $properties);
    }

    public function testChildrenOfOnlyVariantsReferenceTheSameModel()
    {
        $context = HelperContextBuilder::create()
            ->includeChildren()
            ->withChildTypes([AbstractObject::OBJECT_TYPE_VARIANT])
            ->toArray();

        $children = $this->describeFields(['name' => new FieldDef\Input()], $context)['children'];

        $this->assertSame('array', $children['type']);
        $this->assertTrue($children['nullable']);
        $this->assertSame(['$ref' => '#/components/schemas/Concrete'], $children['items']);
    }

    public function testChildrenOfOtherTypesAreGenericObjects()
    {
        $context = HelperContextBuilder::create()->includeChildren()->toArray();

        $children = $this->describeFields(['name' => new FieldDef\Input()], $context)['children'];

        $this->assertSame(['type' => 'object'], $children['items']);
    }

    /**
     * @param array<string, FieldDef|null> $definitions field definitions by field name, null for a field without one
     * @return array<string, array> the described properties by field name
     */
    private function describeFields(array $definitions, array $context = []): array
    {
        $normalizer = new DataObjectNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createFieldFetcher($definitions),
            $this->describer,
        );
        $this->registerNormalizers($normalizer);

        return $this->describe($normalizer, Concrete::class, $context)['properties'];
    }

    /** @param array<string, FieldDef|null> $definitions */
    private function createFieldFetcher(array $definitions): FieldFetcher
    {
        $fieldFetcher = $this->createStub(FieldFetcher::class);
        $fieldFetcher->method('toObject')->willReturn($this->createStub(Concrete::class));
        $fieldFetcher->method('getFields')->willReturn(array_keys($definitions));
        $fieldFetcher->method('getFieldDefinition')->willReturnCallback(
            fn(object $object, string $field) => $definitions[$field],
        );

        return $fieldFetcher;
    }

    private function registerFieldCollectionNormalizer(): void
    {
        $this->registerNormalizers(new FieldCollectionDataNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createFieldFetcher(array_fill_keys(self::NESTED_FIELDS, new FieldDef\Input())),
            $this->describer,
        ));
    }

    private function named(FieldDef $definition, string $name): FieldDef
    {
        $definition->setName($name);

        return $definition;
    }

    private static function aliasOnce(string $class, string $alias): void
    {
        if (!class_exists($alias, false)) {
            class_alias($class, $alias);
        }
    }
}
