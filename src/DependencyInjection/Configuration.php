<?php

namespace Torq\PimcoreHelpersBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('torq_pimcore_helpers');

        $treeBuilder->getRootNode()
            ->children()
                // Declares which (class, field) combos the generic
                // DataGenerationRule engine manages. The rules themselves are
                // editable DataObjects; this config only sets up what is manageable.
                ->arrayNode('data_generation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->integerNode('listener_priority')
                            ->defaultValue(10)
                            ->info('kernel.event_listener priority for the pre-save generation listener')
                        ->end()
                        ->arrayNode('managed')
                            ->info('Field targets the engine fills from DataGenerationRule objects')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('target_class')->isRequired()->cannotBeEmpty()
                                        ->info('Pimcore DataObject class name, e.g. Product')->end()
                                    ->scalarNode('target_field')->isRequired()->cannotBeEmpty()
                                        ->info('Field name to generate, e.g. ShortDescription')->end()
                                    ->scalarNode('override_flag_field')->defaultNull()
                                        ->info('Boolean field that, when true, marks the value a manual override')->end()
                                ->end()
                            ->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
