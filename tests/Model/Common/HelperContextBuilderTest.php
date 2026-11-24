<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Model\Common;

use PHPUnit\Framework\TestCase;
use Torq\PimcoreHelpersBundle\Model\Common\HelperContextBuilder;

class HelperContextBuilderTest extends TestCase
{
    public function testAddingAnExcludedFieldTwiceKeepsItOnce()
    {
        $context = HelperContextBuilder::create()
            ->addExcludedField('name')
            ->addExcludedField('email')
            ->addExcludedField('name')
            ->toArray();

        $this->assertSame(['name', 'email'], $context[HelperContextBuilder::EXCLUDED_FIELDS]);
    }

    public function testAddingAnIncludedFieldTypeTwiceKeepsItOnce()
    {
        $context = HelperContextBuilder::create()
            ->addIncludedFieldType('input')
            ->addIncludedFieldType('input')
            ->toArray();

        $this->assertSame(['input'], $context[HelperContextBuilder::INCLUDED_FIELD_TYPES]);
    }

    public function testAddingAnExcludedFieldTypeTwiceKeepsItOnce()
    {
        $context = HelperContextBuilder::create()
            ->addExcludedFieldType('block')
            ->addExcludedFieldType('block')
            ->toArray();

        $this->assertSame(['block'], $context[HelperContextBuilder::EXCLUDED_FIELD_TYPES]);
    }
}
