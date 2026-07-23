<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\DataGeneration;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Generic, config-driven field generation.
 *
 * For each config-managed {target_class, target_field} the engine finds the
 * applicable DataGenerationRule objects, picks the single winner (no
 * competition), and evaluates its formula to fill the field — unless the field's
 * override-flag is set, in which case the value is a manual override and is left
 * alone. The object is mutated in memory only, so a save never triggers further
 * saves. Rules are cached per request.
 *
 * Decision logic (folderMatches / selectWinner / evaluate) is pure and unit-tested;
 * loading rules and reading/writing fields is the Pimcore-coupled adapter layer.
 */
class DataGenerationEngine
{
    private const RULE_CLASS = 'DataGenerationRule';

    /** @var array<string, list<RuleDefinition>> rules by target class, cached for the request */
    private array $ruleCache = [];

    /**
     * @param array<int, array{target_class?: string, target_field?: string, override_flag_field?: ?string}> $managed
     */
    public function __construct(
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly array $managed,
    ) {
    }

    /**
     * @return string[] distinct managed target class names
     */
    public function getManagedClasses(): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (array $m): ?string => $m['target_class'] ?? null, $this->managed),
        )));
    }

    /**
     * @return string[] the field names that were changed
     */
    public function apply(Concrete $object): array
    {
        $className = $object->getClassName();

        $managedForClass = array_values(array_filter(
            $this->managed,
            static fn (array $m): bool => ($m['target_class'] ?? null) === $className,
        ));

        if ($managedForClass === []) {
            return [];
        }

        $rules = $this->loadRules($className);
        if ($rules === []) {
            return [];
        }

        $changedFields = [];

        foreach ($managedForClass as $managed) {
            $field = $managed['target_field'] ?? '';
            $overrideFlagField = $managed['override_flag_field'] ?? null;

            if ($field === '') {
                continue;
            }

            if ($overrideFlagField !== null && $overrideFlagField !== '' && (bool) $object->get($overrideFlagField)) {
                continue;
            }

            $candidates = array_values(array_filter(
                $rules,
                fn (RuleDefinition $rule): bool => $rule->targetField === $field && $this->ruleApplies($rule, $object),
            ));

            if ($candidates === []) {
                continue;
            }

            $value = $this->evaluateFormula(self::selectWinner($candidates)->formula, $object);
            if ($value === null) {
                continue;
            }

            $current = $object->get($field);
            if ($value !== ($current === null ? null : (string) $current)) {
                $object->setValue($field, $value);
                $changedFields[] = $field;
            }
        }

        return $changedFields;
    }

    /**
     * Evaluate a Symfony expression against $context (exposed as `object`).
     * Returns null on an empty expression or any evaluation error.
     */
    public function evaluate(string $expression, object $context): mixed
    {
        if (trim($expression) === '') {
            return null;
        }

        try {
            return $this->expressionLanguage->evaluate($expression, ['object' => $context]);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * True when a folder scope (tree path) covers an object's path. An empty
     * folder matches everything; the trailing-slash guard stops "/Products" from
     * matching "/ProductsArchive".
     */
    public static function folderMatches(string $folder, string $path): bool
    {
        if ($folder === '') {
            return true;
        }

        return $path === $folder || str_starts_with($path, rtrim($folder, '/') . '/');
    }

    /**
     * Deterministic single winner (no competition): highest Priority, then
     * deepest Folder, then lowest id.
     *
     * @param list<RuleDefinition> $candidates
     */
    public static function selectWinner(array $candidates): RuleDefinition
    {
        if ($candidates === []) {
            throw new \InvalidArgumentException('selectWinner() requires at least one candidate.');
        }

        usort($candidates, static fn (RuleDefinition $a, RuleDefinition $b): int =>
            ($b->priority <=> $a->priority)
            ?: (strlen($b->folder) <=> strlen($a->folder))
            ?: ($a->id <=> $b->id));

        return $candidates[0];
    }

    /**
     * @return list<RuleDefinition>
     */
    private function loadRules(string $className): array
    {
        if (isset($this->ruleCache[$className])) {
            return $this->ruleCache[$className];
        }

        $rules = [];
        $listingClass = 'Pimcore\\Model\\DataObject\\' . self::RULE_CLASS . '\\Listing';

        if (class_exists($listingClass)) {
            /** @var \Pimcore\Model\Listing\AbstractListing $listing */
            $listing = new $listingClass();
            $listing->setCondition('TargetClass = ? AND Enabled = 1', [$className]);
            $listing->setUnpublished(false);
            foreach ($listing->load() as $rule) {
                $rules[] = self::ruleDefinitionFromObject($rule);
            }
        }

        return $this->ruleCache[$className] = $rules;
    }

    private static function ruleDefinitionFromObject(Concrete $rule): RuleDefinition
    {
        return new RuleDefinition(
            (int) $rule->getId(),
            (string) $rule->get('TargetClass'),
            (string) $rule->get('TargetField'),
            trim((string) $rule->get('Folder')),
            trim((string) $rule->get('Condition')),
            trim((string) $rule->get('Formula')),
            (int) $rule->get('Priority'),
        );
    }

    private function ruleApplies(RuleDefinition $rule, Concrete $object): bool
    {
        if ($rule->folder !== '' && !self::folderMatches($rule->folder, $object->getRealFullPath())) {
            return false;
        }

        if ($rule->condition !== '') {
            return (bool) $this->evaluate($rule->condition, $object);
        }

        return true;
    }

    private function evaluateFormula(string $formula, Concrete $object): ?string
    {
        $value = $this->evaluate($formula, $object);

        return $value === null ? null : (string) $value;
    }
}
