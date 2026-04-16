<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ArrayFieldTypeRegistrationPass implements CompilerPassInterface
{
    private const DATA_IMPORTER_SERVICE = 'Pimcore\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService';
    private const GENERIC_DATA_INDEX_SERVICE = 'Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter\TextKeywordAdapter';

    public function process(ContainerBuilder $container): void
    {
        // Register arrayField for Data Importer
        if ($container->hasDefinition(self::DATA_IMPORTER_SERVICE)) {
            $definition = $container->getDefinition(self::DATA_IMPORTER_SERVICE);
            $definition->addMethodCall('appendTypeMapping', [
                'arrayField',
                'array',
            ]);
        }

        // Register arrayField for OpenSearch indexing
        if ($container->hasDefinition(self::GENERIC_DATA_INDEX_SERVICE)) {
            $definition = $container->getDefinition(self::GENERIC_DATA_INDEX_SERVICE);
            $definition->addTag('pimcore.generic_data_index.data-object.search_index_field_definition', [
                'type' => 'arrayField',
            ]);
        }
    }
}
