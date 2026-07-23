<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Tests\Service\DataGeneration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Torq\PimcoreHelpersBundle\Service\DataGeneration\DataGenerationEngine;
use Torq\PimcoreHelpersBundle\Service\DataGeneration\RuleDefinition;

/**
 * Unit tests for the pure decision logic of the field-generation engine:
 * folder scoping, single-winner selection (no competition),
 * and expression evaluation. The Pimcore-coupled rule loading / field writing
 * is exercised by end-to-end verification, not here.
 */
class DataGenerationEngineTest extends TestCase
{
    // --- folderMatches (scoping) -------------------------------------------

    public function testEmptyFolderMatchesEverything(): void
    {
        self::assertTrue(DataGenerationEngine::folderMatches('', '/Products/Anything'));
    }

    public function testFolderMatchesExactPathAndSubtree(): void
    {
        self::assertTrue(DataGenerationEngine::folderMatches('/Products', '/Products'));
        self::assertTrue(DataGenerationEngine::folderMatches('/Products', '/Products/Oil Filters/KL-1'));
        self::assertTrue(DataGenerationEngine::folderMatches('/Products/', '/Products/KL-1'));
    }

    public function testFolderDoesNotMatchSiblingPrefixOrOtherBranch(): void
    {
        self::assertFalse(DataGenerationEngine::folderMatches('/Products', '/ProductsArchive/KL-1'));
        self::assertFalse(DataGenerationEngine::folderMatches('/Products/A', '/Products/B/KL-1'));
    }

    // --- selectWinner (no competition) -------------------------------------

    public function testHighestPriorityWins(): void
    {
        $low = $this->rule(id: 1, priority: 0);
        $high = $this->rule(id: 2, priority: 5);

        self::assertSame($high, DataGenerationEngine::selectWinner([$low, $high]));
    }

    public function testDeeperFolderBreaksAPriorityTie(): void
    {
        $shallow = $this->rule(id: 1, priority: 0, folder: '/Products');
        $deep = $this->rule(id: 2, priority: 0, folder: '/Products/Oil Filters');

        self::assertSame($deep, DataGenerationEngine::selectWinner([$shallow, $deep]));
    }

    public function testLowestIdBreaksARemainingTie(): void
    {
        $first = $this->rule(id: 3, priority: 0);
        $second = $this->rule(id: 7, priority: 0);

        self::assertSame($first, DataGenerationEngine::selectWinner([$second, $first]));
    }

    public function testWinnerIsDeterministicRegardlessOfInputOrder(): void
    {
        $a = $this->rule(id: 1, priority: 0);
        $winner = $this->rule(id: 2, priority: 9);
        $c = $this->rule(id: 3, priority: 5, folder: '/x');

        self::assertSame($winner, DataGenerationEngine::selectWinner([$a, $winner, $c]));
        self::assertSame($winner, DataGenerationEngine::selectWinner([$c, $a, $winner]));
    }

    public function testSelectWinnerThrowsWithoutCandidates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        DataGenerationEngine::selectWinner([]);
    }

    // --- evaluate (expression integration) ---------------------------------

    public function testEvaluateRendersFormulaAgainstObject(): void
    {
        $engine = new DataGenerationEngine(new ExpressionLanguage(), []);
        $object = new class {
            public function getItemNumber(): string
            {
                return 'KL250-021';
            }
        };

        self::assertSame(
            'KL250-021 Keltec Oil Filter',
            $engine->evaluate("object.getItemNumber() ~ ' Keltec Oil Filter'", $object),
        );
    }

    public function testEvaluateSupportsBooleanConditions(): void
    {
        $engine = new DataGenerationEngine(new ExpressionLanguage(), []);
        $object = new class {
            public function getType(): string
            {
                return 'filter';
            }
        };

        self::assertTrue((bool) $engine->evaluate("object.getType() == 'filter'", $object));
        self::assertFalse((bool) $engine->evaluate("object.getType() == 'gasket'", $object));
    }

    public function testEvaluateReturnsNullOnEmptyOrBrokenExpression(): void
    {
        $engine = new DataGenerationEngine(new ExpressionLanguage(), []);
        $object = new class {};

        self::assertNull($engine->evaluate('', $object));
        self::assertNull($engine->evaluate('object.methodThatDoesNotExist()', $object));
    }

    // -----------------------------------------------------------------------

    private function rule(int $id, int $priority, string $folder = ''): RuleDefinition
    {
        return new RuleDefinition(
            id: $id,
            targetClass: 'Product',
            targetField: 'ShortDescription',
            folder: $folder,
            condition: '',
            formula: "object.getItemNumber()",
            priority: $priority,
        );
    }
}
