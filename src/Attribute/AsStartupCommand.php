<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Attribute;

use Attribute;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsStartupCommand extends AutoconfigureTag
{
    public function __construct(
        bool $repeatable = false,
    ) {
        parent::__construct('torq.startup_command', ['repeatable' => $repeatable]);
    }
}