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
}