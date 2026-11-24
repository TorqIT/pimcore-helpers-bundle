<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\Common;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pimcore\Model\DataObject\Concrete;
use stdClass;
use Torq\PimcoreHelpersBundle\Service\Common\FieldFetcher;
use Torq\PimcoreHelpersBundle\Tests\Support\FixtureBrick;

class FieldFetcherTest extends TestCase
{
    public function testClassWhoseConstructorNeedsArgumentsIsResolvedFromItsName()
    {
        $object = (new FieldFetcher())->toObject(FixtureBrick::class);

        $this->assertInstanceOf(FixtureBrick::class, $object);
    }

    public function testObjectIsReturnedAsIs()
    {
        $object = $this->createStub(Concrete::class);

        $this->assertSame($object, (new FieldFetcher())->toObject($object));
    }

    public function testClassWhichHasNoFieldsIsRejected()
    {
        $this->expectException(InvalidArgumentException::class);

        (new FieldFetcher())->toObject(stdClass::class);
    }
}
