<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Attribute;

use Attribute;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Attribute(Attribute::TARGET_CLASS)]
#[Autoconfigure(tags: ['torq.startup_command'])]
final class AsStartupCommand
{
    public function __construct(
        public readonly bool $repeatable = false,
    ) {}
}