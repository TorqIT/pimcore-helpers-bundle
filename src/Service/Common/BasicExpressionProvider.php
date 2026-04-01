<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Service\Common;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

#[AutoconfigureTag(name: 'pimcore.calculated_value.expression_language_provider')]
#[AutoconfigureTag(name: 'pimcore.datahub.data_importer.expression_language_provider')]
class BasicExpressionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new ExpressionFunction(
                'lower', function ($str) {
                return sprintf('strtolower(%s)', $str);
            }, function ($variables, $value) {
                return strtolower($value);
            }
            ),

            new ExpressionFunction(
                'upper', function ($str) {
                return sprintf('strtoupper(%s)', $str);
            }, function ($variables, $value) {
                return strtoupper($value);
            }
            ),

            new ExpressionFunction(
                'trim', function ($str) {
                return sprintf('trim(%s)', $str);
            }, function ($variables, $value) {
                return trim($value);
            }
            ),
        ];
    }
}
