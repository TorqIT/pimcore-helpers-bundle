<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Normalizer;

use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\BlockElement;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\Video;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Service\Normalizer\BlockElementNormalizer;

class BlockElementNormalizerTest extends TestCase
{
    private BlockElementNormalizer $normalizer;

    protected function setUp(): void
    {
        $objectNormalizer = $this->createStub(NormalizerInterface::class);
        $objectNormalizer->method('normalize')->willReturnCallback(fn(object $output) => (array) $output);

        $this->normalizer = new BlockElementNormalizer($objectNormalizer);
    }

    public function testRelationIsNormalizedInFullByDefault()
    {
        $object = $this->createObject(5);

        $normalized = $this->normalizer->normalize(new BlockElement('owner', 'manyToOneRelation', $object));

        $this->assertSame(['data' => $object], $normalized);
    }

    public function testSingleRelationBecomesItsId()
    {
        $this->assertSame(5, $this->normalizeAsIds('manyToOneRelation', $this->createObject(5)));
        $this->assertNull($this->normalizeAsIds('manyToOneRelation', null));
    }

    public function testManyRelationBecomesItsIds()
    {
        $objects = [$this->createObject(5), $this->createObject(6)];

        $this->assertSame([5, 6], $this->normalizeAsIds('manyToManyObjectRelation', $objects));
        $this->assertSame([], $this->normalizeAsIds('manyToManyObjectRelation', null));
    }

    public function testAdvancedObjectRelationBecomesTheIdsOfItsObjects()
    {
        $metadata = $this->createStub(ObjectMetadata::class);
        $metadata->method('getObject')->willReturn($this->createObject(5));

        $this->assertSame([5], $this->normalizeAsIds('advancedManyToManyObjectRelation', [$metadata]));
    }

    public function testAdvancedRelationBecomesTheIdsOfItsElements()
    {
        $metadata = $this->createStub(ElementMetadata::class);
        $metadata->method('getElement')->willReturn($this->createObject(5));

        $this->assertSame([5], $this->normalizeAsIds('advancedManyToManyRelation', [$metadata]));
    }

    public function testImagesBecomeTheirIds()
    {
        $image = $this->createStub(Image::class);
        $image->method('getId')->willReturn(7);
        $hotspotImage = $this->createStub(Hotspotimage::class);
        $hotspotImage->method('getImage')->willReturn($image);
        $gallery = $this->createStub(ImageGallery::class);
        $gallery->method('getItems')->willReturn([$hotspotImage]);

        $this->assertSame(7, $this->normalizeAsIds('image', $image));
        $this->assertSame(7, $this->normalizeAsIds('hotspotimage', $hotspotImage));
        $this->assertSame([7], $this->normalizeAsIds('gallery', $gallery));
    }

    public function testOnlyAVideoBackedByAnAssetBecomesAnId()
    {
        $asset = $this->createStub(Asset::class);
        $asset->method('getId')->willReturn(9);
        $assetVideo = $this->createStub(Video::class);
        $assetVideo->method('getData')->willReturn($asset);
        $youtubeVideo = $this->createStub(Video::class);
        $youtubeVideo->method('getData')->willReturn('dQw4w9WgXcQ');

        $this->assertSame(9, $this->normalizeAsIds('video', $assetVideo));
        $this->assertSame($youtubeVideo, $this->normalizeAsIds('video', $youtubeVideo));
    }

    public function testFieldWhichIsNotARelationIsLeftAlone()
    {
        $this->assertSame('text', $this->normalizeAsIds('input', 'text'));
    }

    private function normalizeAsIds(string $fieldType, mixed $data): mixed
    {
        $context = HelperContextBuilder::create()->relationsAsIds()->toArray();

        return $this->normalizer->normalize(new BlockElement('field', $fieldType, $data), 'json', $context)['data'];
    }

    private function createObject(int $id): Concrete
    {
        $object = $this->createStub(Concrete::class);
        $object->method('getId')->willReturn($id);

        return $object;
    }
}
