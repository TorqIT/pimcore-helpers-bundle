<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\ClassDefinition;

class ClassDefinitionRepository
{
    public function getById(?string $id, bool $force = false)
    {
        return $id !== null ? ClassDefinition::getById($id, $force) : null;
    }

    public function getByName(?string $name)
    {
        return $name !== null ? ClassDefinition::getByName($name) : null;
    }

    public function save(ClassDefinition $definition, bool $saveDefinitionFile = true)
    {
        $definition->save($saveDefinitionFile);
        return $definition;
    }
}