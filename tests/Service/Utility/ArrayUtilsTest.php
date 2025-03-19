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

        $this->assertEquals("My Value", $this->arrayUtils->safeVal("test", $payload));
    }

    public function testNullOnMissingKey()
    {
        $payload = [
            "test" => "My Value"
        ];

        $this->assertNull($this->arrayUtils->safeVal("other", $payload));
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

        $this->assertEquals("My Value", $this->arrayUtils->safeVal(["test", "inner", "bottom"], $payload));
    }

    public function testNullOnMissingNestedKey()
    {
        $payload = [
            "test" => []
        ];

        $this->assertNull($this->arrayUtils->safeVal(["test", "inner", "bottom"], $payload));
    }

    public function testNullOnWrongTypeKey()
    {
        $payload = [
            "test" => "haha"
        ];

        $this->assertNull($this->arrayUtils->safeVal(["test", "inner", "bottom"], $payload));
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
        $this->arrayUtils->safeVal([], $payload);
    }

    public function testGetNullIntFromEmptyString()
    {
        $payload = [
            "test" => ""
        ];

        $this->assertNull($this->arrayUtils->safeInt("test", $payload));
    }

    public function testGetNullFloatFromEmptyString()
    {
        $payload = [
            "test" => ""
        ];

        $this->assertNull($this->arrayUtils->safeFloat("test", $payload));
    }

    public function testGetIntFromString()
    {
        $payload = [
            "test" => "12"
        ];

        $this->assertEquals(12, $this->arrayUtils->safeInt("test", $payload));
    }

    public function testGetFloatFromString()
    {
        $payload = [
            "test" => "12.34"
        ];

        $this->assertEquals(12.34, $this->arrayUtils->safeFloat("test", $payload));
    }
}