<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\DataGeneration;

/**
 * Immutable snapshot of a DataGenerationRule object, decoupled from Pimcore so
 * the engine's decision logic (scoping + winner selection) is unit-testable.
 */
final class RuleDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly string $targetClass,
        public readonly string $targetField,
        public readonly string $folder,
        public readonly string $condition,
        public readonly string $formula,
        public readonly int $priority,
    ) {
    }
}
