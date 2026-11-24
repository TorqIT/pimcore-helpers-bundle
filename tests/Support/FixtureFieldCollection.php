<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Support;

use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData;

final class FixtureFieldCollection extends AbstractData
{
    protected string $type = 'fixtureFieldCollection';
}
