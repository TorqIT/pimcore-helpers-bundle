<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Functional\Service\Normalizer;

use Pimcore\Cache\RuntimeCache;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data as FieldDef;
use Pimcore\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Repository\AssetMetadataRepository;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AssetNormalizer;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Tests\Support\FixtureObject;
use Torq\PimcoreHelpersBundle\Tests\Support\ObjectDescriptionTestTrait;

/** Needs a kernel: Pimcore resolves the classes a relation allows through the container and class definitions */
class RelationDescriptionTest extends KernelTestCase
{
    use ObjectDescriptionTestTrait;

    private const string FIXTURE_CLASS_ID = 'torq_helpers_fixture';
    private const string FIXTURE_CACHE_KEY = 'class_' . self::FIXTURE_CLASS_ID;
    private const array FIXTURE_REF = ['$ref' => '#/components/schemas/FixtureObject'];
    private const array COLUMNS = [['key' => 'role', 'type' => 'text'], ['key' => 'weight', 'type' => 'number']];
    private const array NULLABLE_STRING = ['type' => 'string', 'nullable' => true];

    public static function setUpBeforeClass(): void
    {
        self::aliasOnce(FixtureObject::class, 'Pimcore\\Model\\DataObject\\FixtureObject');
    }

    protected function setUp(): void
    {
        static::bootKernel();
        $this->createDescriber();

        $classDefinition = new ClassDefinition();
        $classDefinition->setId(self::FIXTURE_CLASS_ID);
        $classDefinition->setName('FixtureObject');
        RuntimeCache::set(self::FIXTURE_CACHE_KEY, $classDefinition);
    }

    protected function tearDown(): void
    {
        RuntimeCache::set(self::FIXTURE_CACHE_KEY, null);

        parent::tearDown();
    }

    public function testRelationToOneClassReferencesThatClass()
    {
        $relation = $this->relationToFixture(new FieldDef\ManyToOneRelation());
        $relation->setObjectsAllowed(true);

        $properties = $this->describeFields(['owner' => $relation]);

        $this->assertTrue($properties['owner']['nullable']);
        $this->assertSame([self::FIXTURE_REF], $properties['owner']['oneOf']);
    }

    public function testRelationToManyOfOneClassIsAnArrayOfThatClass()
    {
        $relation = $this->relationToFixture(new FieldDef\ManyToManyObjectRelation());

        $properties = $this->describeFields(['members' => $relation]);

        $this->assertSame('array', $properties['members']['type']);
        $this->assertSame(self::FIXTURE_REF, $properties['members']['items']);
    }

    public function testRelationToAnyObjectIsAGenericObject()
    {
        $relation = new FieldDef\ManyToOneRelation();
        $relation->setObjectsAllowed(true);

        $properties = $this->describeFields(['owner' => $relation]);

        $this->assertSame(['type' => 'object', 'nullable' => true], $properties['owner']);
    }

    /** The stubbed fields are the same for every class, so the related class relates back to itself */
    public function testClassWhichRelatesToItselfIsDescribedOnce()
    {
        $relation = $this->relationToFixture(new FieldDef\ManyToManyObjectRelation());

        $this->describeFields(['related' => $relation]);

        $schemas = $this->getReferencedSchemas();
        $this->assertSame(['FixtureObject'], array_keys($schemas));
        $this->assertSame(self::FIXTURE_REF, $schemas['FixtureObject']['properties']['related']['items']);
    }

    public function testAdvancedObjectRelationRowsHaveTheObjectAndAStringPerColumn()
    {
        $relation = new FieldDef\AdvancedManyToManyObjectRelation();
        $relation->setAllowedClassId(self::FIXTURE_CLASS_ID);
        $relation->setColumns(self::COLUMNS);

        $rows = $this->describeFields(['members' => $relation])['members'];

        $this->assertSame('array', $rows['type']);
        $this->assertEquals(
            ['object' => self::FIXTURE_REF, 'role' => self::NULLABLE_STRING, 'weight' => self::NULLABLE_STRING],
            $rows['items']['properties'],
        );
    }

    public function testAdvancedObjectRelationWithoutAnAllowedClassHasAGenericObject()
    {
        $relation = new FieldDef\AdvancedManyToManyObjectRelation();
        $relation->setColumns(self::COLUMNS);

        $rows = $this->describeFields(['members' => $relation])['members'];

        $this->assertSame(['type' => 'object'], $rows['items']['properties']['object']);
    }

    public function testAdvancedRelationElementIsAUnionOfTheAllowedClasses()
    {
        $this->registerNormalizers(new AssetNormalizer(
            $this->createStub(NormalizerInterface::class),
            new ArrayUtils(),
            $this->createStub(AssetMetadataRepository::class),
            new RequestStack(),
            $this->describer,
        ));
        $relation = $this->relationToFixture(new FieldDef\AdvancedManyToManyRelation());
        $relation->setObjectsAllowed(true);
        $relation->setAssetsAllowed(true);
        $relation->setColumns(self::COLUMNS);

        $rows = $this->describeFields(['related' => $relation])['related'];

        $this->assertSame(
            [self::FIXTURE_REF, ['$ref' => '#/components/schemas/Asset']],
            $rows['items']['properties']['element']['oneOf'],
        );
        $this->assertSame(self::NULLABLE_STRING, $rows['items']['properties']['role']);
    }

    public function testAdvancedRelationToAnyObjectHasAGenericElement()
    {
        $relation = new FieldDef\AdvancedManyToManyRelation();
        $relation->setObjectsAllowed(true);
        $relation->setAssetsAllowed(true);
        $relation->setColumns(self::COLUMNS);

        $rows = $this->describeFields(['related' => $relation])['related'];

        $this->assertSame(['type' => 'object'], $rows['items']['properties']['element']);
    }

    /**
     * @template T of FieldDef\Relations\AbstractRelations
     * @param T $relation
     * @return T
     */
    private function relationToFixture(FieldDef\Relations\AbstractRelations $relation): FieldDef
    {
        $relation->setClasses([['classes' => 'FixtureObject']]);

        return $relation;
    }
}
