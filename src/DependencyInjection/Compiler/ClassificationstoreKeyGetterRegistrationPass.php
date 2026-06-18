<?php

namespace Torq\PimcoreHelpersBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassificationstoreKeyGetterRegistrationPass implements CompilerPassInterface
{
    private const string ADMIN_OPERATOR_FACTORY = 'Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\Factory\DefaultOperatorFactory';

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::ADMIN_OPERATOR_FACTORY)) {
            $def = $container->getDefinition(self::ADMIN_OPERATOR_FACTORY);
            $def->setArgument(
                '$className',
                'Torq\PimcoreHelpersBundle\GridColumnConfig\Operator\ClassificationStoreKeyGetter',
            );
            $def->addTag(
                'pimcore.data_object.grid_column_config.operator_factory',
                ['id' => 'ClassificationStoreKeyGetter'],
            );
        }
    }
}