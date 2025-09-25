<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\Classificationstore\GroupConfig;

class GroupRepository
{
    public function get(null|int|string|GroupConfig $identifier, int $storeId = 1): ?GroupConfig
    {
        if (is_int($identifier)) {
            $identifier = $this->getById($identifier);
        } elseif (is_string($identifier)) {
            $identifier = $this->getByName($identifier, $storeId);
        }
        return $identifier;
    }

    public function getById(?int $id): ?GroupConfig
    {
        return $id !== null ? GroupConfig::getById($id) : null;
    }

    public function getByName(?string $name, int $storeId = 1): ?GroupConfig
    {
        return $name !== null ? GroupConfig::getByName($name, $storeId) : null;
    }
}