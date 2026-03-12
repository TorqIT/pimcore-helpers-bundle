<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\ClassDefinition;

class ClassDefinitionRepository
{
    public function getById(?string $id, bool $force = false)
    {
        return $id !== null ? ClassDefinition::getById($id, $force) : null;
    }
}