<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Support;

use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

final class FixtureBrick extends AbstractData
{
    protected string $type = 'fixtureBrick';
}
