<?php

namespace Torq\PimcoreHelpersBundle\Tests\Base;

use PHPUnit\Framework\TestCase;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;

abstract class PimcoreTestBootstrapped extends TestCase
{
    protected function setUp(): void
    {
        include __DIR__ . '/../../vendor/autoload.php';

        \Pimcore\Bootstrap::setProjectRoot();
        \Pimcore\Bootstrap::bootstrap();

        $request = Request::createFromGlobals();

        // set current request as property on tool as there's no
        // request stack available yet
        Tool::setCurrentRequest($request);

        /** @var \Pimcore\Kernel $kernel */
        \Pimcore\Bootstrap::kernel();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        restore_error_handler();
        restore_exception_handler();
    }
}