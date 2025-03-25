<?php

namespace Torq\PimcoreHelpersBundle\Tests\Service\Utility;

use Torq\PimcoreHelpersBundle\Service\Utility\ArrayUtils;
use Torq\PimcoreHelpersBundle\Tests\Base\PimcoreTestBootstrapped;

class ArrayUtilsTest extends PimcoreTestBootstrapped
{
    private ArrayUtils $arrayUtils;
    
    protected function setUp(): void
    {
        parent::setUp();

        $this->arrayUtils = new ArrayUtils();
    }

    public function testBasicKey()
    {
        $payload = [
            "test" => "My Value"
        ];

        $this->assertEquals("My Value", $this->arrayUtils->get("test", $payload));
    }

    public function testReturnNullOnEmptyArray()
    {
        $this->assertNull($this->arrayUtils->get("test", null));
    }

    public function testNullOnMissingKey()
    {
        $payload = [
            "test" => "My Value"
        ];

        $this->assertNull($this->arrayUtils->get("other", $payload));
    }

    public function testNestedKey()
    {
        $payload = [
            "test" => [
                "inner" => [
                    "bottom" => "My Value"
                ]
            ]
        ];

        $this->assertEquals("My Value", $this->arrayUtils->get(["test", "inner", "bottom"], $payload));
    }

    public function testNullOnMissingNestedKey()
    {
        $payload = [
            "test" => []
        ];

        $this->assertNull($this->arrayUtils->get(["test", "inner", "bottom"], $payload));
    }

    public function testNullOnWrongTypeKey()
    {
        $payload = [
            "test" => "haha"
        ];

        $this->assertNull($this->arrayUtils->get(["test", "inner", "bottom"], $payload));
    }

    public function testThrowExceptionOnEmptyKeyList()
    {
        $payload = [
            "test" => [
                "inner" => [
                    "bottom" => "My Value"
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->arrayUtils->get([], $payload);
    }

    public function testGetNullIntFromEmptyString()
    {
        $payload = [
            "test" => ""
        ];

        $this->assertNull($this->arrayUtils->getInt("test", $payload));
    }

    public function testGetNullFloatFromEmptyString()
    {
        $payload = [
            "test" => ""
        ];

        $this->assertNull($this->arrayUtils->getFloat("test", $payload));
    }

    public function testGetIntFromString()
    {
        $payload = [
            "test" => "12"
        ];

        $this->assertEquals(12, $this->arrayUtils->getInt("test", $payload));
    }

    public function testGetFloatFromString()
    {
        $payload = [
            "test" => "12.34"
        ];

        $this->assertEquals(12.34, $this->arrayUtils->getFloat("test", $payload));
    }

    public function testFindCorrectIndexInArray()
    {
        $payload = [
            ["id" => 1, "name" => "First"],
            ["id" => 2, "name" => "Second"],
            ["id" => 3, "name" => "Third"],
        ];

        $this->assertEquals(1, $this->arrayUtils->findIndex(fn(array $a) => $a["id"] == 2, $payload));
    }

    public function testFindCorrectValueInArray()
    {
        $payload = [
            ["id" => 1, "name" => "First"],
            ["id" => 2, "name" => "Second"],
            ["id" => 3, "name" => "Third"],
        ];

        $this->assertEquals("Second", $this->arrayUtils->findInArray(fn(array $a) => $a["id"] == 2, $payload)["name"]);
    }

    public function testReturnNullWhenFindingOnNullArray()
    {
        $this->assertNull($this->arrayUtils->findInArray(fn(array $a) => $a["id"] == 2, null));
    }

    public function testAllChecksAllItems()
    {
        $this->assertTrue($this->arrayUtils->all([2, 4, 6], fn(int $item) => $item % 2 == 0));
        $this->assertFalse($this->arrayUtils->all([2, 4, 7], fn(int $item) => $item % 2 == 0));
    }

    public function testAnyChecksAnyItems()
    {
        $this->assertTrue($this->arrayUtils->any([1, 3, 6], fn(int $item) => $item % 2 == 0));
        $this->assertFalse($this->arrayUtils->any([1, 3, 5], fn(int $item) => $item % 2 == 0));
    }

    public function testAnyReturnsFalseOnEmpty()
    {
        $this->assertFalse($this->arrayUtils->any([], fn(int $item) => $item % 2 == 0));
    }

    public function testAllReturnsTrueOnEmpty()
    {
        $this->assertTrue($this->arrayUtils->all([], fn(int $item) => $item % 2 == 0));
    }
}