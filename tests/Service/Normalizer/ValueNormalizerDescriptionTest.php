<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Normalizer;

use Carbon\CarbonPeriod;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\Hotspotimage;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\DataObject\Data\InputQuantityValue;
use Pimcore\Model\DataObject\Data\NumericRange;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\Data\QuantityValueRange;
use Pimcore\Model\DataObject\Data\Video;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;
use Torq\PimcoreHelpersBundle\Repository\AssetMetadataRepository;
use Torq\PimcoreHelpersBundle\Service\Normalizer\AssetNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\CarbonNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\CarbonPeriodNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ElementMetadataNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\HotspotImageNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ImageGalleryNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ImageNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ListingNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\NumericRangeNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\ObjectMetadataNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\QuantityValueNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\QuantityValueRangeNormalizer;
use Torq\PimcoreHelpersBundle\Service\Normalizer\VideoNormalizer;
use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Tests\Support\DescriberTestTrait;

class ValueNormalizerDescriptionTest extends TestCase
{
    use DescriberTestTrait;

    private const array NULLABLE_NUMBER = ['type' => 'number', 'nullable' => true];
    private const array NULLABLE_STRING = ['type' => 'string', 'nullable' => true];
    private const array ASSET_PROPERTIES = [
        'id' => ['type' => 'integer'],
        'fullPath' => ['type' => 'string'],
        'fileName' => ['type' => 'string'],
        'mimeType' => ['type' => 'string'],
        'fileType' => ['type' => 'string'],
        'fileSize' => ['type' => 'string'],
        'metadata' => ['type' => 'object', 'nullable' => true, 'additionalProperties' => true],
    ];

    private AssetNormalizer $assetNormalizer;
    private ImageNormalizer $imageNormalizer;

    protected function setUp(): void
    {
        $this->createDescriber();

        $objectNormalizer = $this->createStub(NormalizerInterface::class);
        $metadataRepository = $this->createStub(AssetMetadataRepository::class);
        $this->assetNormalizer = new AssetNormalizer(
            $objectNormalizer,
            new ArrayUtils(),
            $metadataRepository,
            new RequestStack(),
            $this->describer,
        );
        $this->imageNormalizer = new ImageNormalizer(
            $objectNormalizer,
            new ArrayUtils(),
            $metadataRepository,
            new RequestStack(),
            $this->describer,
        );
        $this->registerNormalizers($this->assetNormalizer, $this->imageNormalizer);
    }

    public function testQuantityValueHasANumericValueAndItsUnit()
    {
        $described = $this->describe(new QuantityValueNormalizer($this->describer), QuantityValue::class);

        $this->assertSame('object', $described['type']);
        $this->assertSame(
            ['value' => self::NULLABLE_NUMBER, 'unit' => self::NULLABLE_STRING, 'unitName' => self::NULLABLE_STRING],
            $described['properties'],
        );
    }

    public function testInputQuantityValueHasAStringValue()
    {
        $described = $this->describe(new QuantityValueNormalizer($this->describer), InputQuantityValue::class);

        $this->assertSame(self::NULLABLE_STRING, $described['properties']['value']);
    }

    public function testNumericRangeHasAMinimumAndMaximum()
    {
        $described = $this->describe(new NumericRangeNormalizer($this->describer), NumericRange::class);

        $this->assertSame(
            ['minimum' => self::NULLABLE_NUMBER, 'maximum' => self::NULLABLE_NUMBER],
            $described['properties'],
        );
    }

    public function testQuantityValueRangeHasItsBoundsAndUnit()
    {
        $described = $this->describe(new QuantityValueRangeNormalizer($this->describer), QuantityValueRange::class);

        $this->assertSame(
            [
                'minimum' => self::NULLABLE_NUMBER,
                'maximum' => self::NULLABLE_NUMBER,
                'unit' => self::NULLABLE_STRING,
                'unitName' => self::NULLABLE_STRING,
            ],
            $described['properties'],
        );
    }

    public function testCarbonPeriodHasAStartAndEndDate()
    {
        $described = $this->describe($this->createCarbonPeriodNormalizer(), CarbonPeriod::class);

        $date = ['type' => 'string', 'format' => 'date-time'];
        $this->assertEquals(['startDate' => $date, 'endDate' => $date], $described['properties']);
    }

    public function testCarbonPeriodDatesFollowTheContextDateFormat()
    {
        $context = HelperContextBuilder::create()->withDateFormat('Y-m-d')->toArray();

        $described = $this->describe($this->createCarbonPeriodNormalizer(), CarbonPeriod::class, $context);

        $this->assertStringContainsString('Y-m-d', $described['properties']['startDate']['description']);
        $this->assertArrayNotHasKey('format', $described['properties']['startDate']);
    }

    public function testAssetHasItsFileDetailsAndMetadata()
    {
        $described = $this->describe($this->assetNormalizer, Asset::class);

        $this->assertSame('object', $described['type']);
        $this->assertEquals(self::ASSET_PROPERTIES, $described['properties']);
    }

    public function testHotspotImageIsDescribedAsItsImage()
    {
        $normalizer = new HotspotImageNormalizer($this->imageNormalizer, $this->describer);

        $described = $this->describe($normalizer, Hotspotimage::class);

        $this->assertEquals(self::ASSET_PROPERTIES, $described['properties']);
    }

    public function testImageGalleryIsAnArrayOfImages()
    {
        $hotspotImageNormalizer = new HotspotImageNormalizer($this->imageNormalizer, $this->describer);
        $normalizer = new ImageGalleryNormalizer($hotspotImageNormalizer, $this->describer);

        $described = $this->describe($normalizer, ImageGallery::class);

        $this->assertSame('array', $described['type']);
        $this->assertSame(['$ref' => '#/components/schemas/Image'], $described['items']);
    }

    public function testVideoDataIsAStringOrAnAsset()
    {
        $described = $this->describe(new VideoNormalizer($this->assetNormalizer, $this->describer), Video::class);

        $properties = $described['properties'];
        $this->assertSame(self::NULLABLE_STRING, $properties['title']);
        $this->assertSame(self::NULLABLE_STRING, $properties['description']);
        $this->assertSame(self::NULLABLE_STRING, $properties['type']);
        $this->assertTrue($properties['data']['nullable']);
        $this->assertSame(
            [['type' => 'string'], ['$ref' => '#/components/schemas/Asset']],
            $properties['data']['oneOf'],
        );
    }

    public function testObjectMetadataHasItsObjectAndAnyExtraColumns()
    {
        $normalizer = new ObjectMetadataNormalizer($this->createStub(NormalizerInterface::class), $this->describer);

        $described = $this->describe($normalizer, ObjectMetadata::class);

        $this->assertSame(['object' => ['type' => 'object']], $described['properties']);
        $this->assertTrue($described['additionalProperties']);
    }

    public function testElementMetadataHasItsElementAndAnyExtraColumns()
    {
        $normalizer = new ElementMetadataNormalizer($this->createStub(NormalizerInterface::class), $this->describer);

        $described = $this->describe($normalizer, ElementMetadata::class);

        $this->assertSame(['element' => ['type' => 'object']], $described['properties']);
        $this->assertTrue($described['additionalProperties']);
    }

    public function testListingDataReferencesTheClassItLists()
    {
        $normalizer = new ListingNormalizer($this->createStub(NormalizerInterface::class), $this->describer);

        $described = $this->describe($normalizer, Asset\Listing::class);

        $properties = $described['properties'];
        $this->assertSame(['type' => 'integer'], $properties['pageTotal']);
        $this->assertSame(['type' => 'integer'], $properties['total']);
        $this->assertSame('array', $properties['data']['type']);
        $this->assertSame(['$ref' => '#/components/schemas/Asset'], $properties['data']['items']);
    }

    private function createCarbonPeriodNormalizer(): CarbonPeriodNormalizer
    {
        return new CarbonPeriodNormalizer(new CarbonNormalizer(new ArrayUtils()), $this->describer);
    }
}
