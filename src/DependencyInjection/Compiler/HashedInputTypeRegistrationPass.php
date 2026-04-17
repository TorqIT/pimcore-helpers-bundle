<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HashedInputTypeRegistrationPass implements CompilerPassInterface
{
    private const string DATA_IMPORTER_SERVICE = 'Pimcore\Bundle\DataImporterBundle\Mapping\Type\TransformationDataTypeService';
    private const string GENERIC_DATA_INDEX_SERVICE = 'Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\DefaultSearch\DataObject\FieldDefinitionAdapter\TextKeywordAdapter';

    public function process(ContainerBuilder $container): void
    {
        // Register hashedInput for Data Importer
        if ($container->hasDefinition(self::DATA_IMPORTER_SERVICE)) {
            $definition = $container->getDefinition(self::DATA_IMPORTER_SERVICE);
            $definition->addMethodCall('appendTypeMapping', [
                'hashedInput',
                'default',
            ]);
        }

        // Register hashedInput for OpenSearch indexing
        if ($container->hasDefinition(self::GENERIC_DATA_INDEX_SERVICE)) {
            $definition = $container->getDefinition(self::GENERIC_DATA_INDEX_SERVICE);
            $definition->addTag('pimcore.generic_data_index.data-object.search_index_field_definition', [
                'type' => 'hashedInput',
            ]);
        }
    }
}
