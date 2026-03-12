<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

class KeyRepository
{
    public function save(KeyConfig $key): KeyConfig
    {
        $key->save();
        return $key;
    }

    public function get(null|int|string|KeyConfig $identifier, int $storeId = 1): ?KeyConfig
    {
        if (is_int($identifier)) {
            $identifier = $this->getById($identifier);
        } elseif (is_string($identifier)) {
            $identifier = $this->getByName($identifier, $storeId);
        }
        return $identifier;
    }

    public function getById(?int $id): ?KeyConfig
    {
        return $id !== null ? KeyConfig::getById($id) : null;
    }

    public function getByName(?string $name, int $storeId = 1): ?KeyConfig
    {
        return $name !== null ? KeyConfig::getByName($name, $storeId) : null;
    }
}