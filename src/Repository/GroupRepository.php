<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

class GroupRepository
{
    public function __construct(private KeyRepository $keyRepository) {}

    public function save(GroupConfig $group): GroupConfig
    {
        $group->save();
        return $group;
    }

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

    /** @return KeyConfig[] */
    public function getKeysForGroup(null|int|string|GroupConfig $identifier, int $storeId = 1): array {
        $group = $this->get($identifier, $storeId);
        return array_map(
            fn(KeyGroupRelation $r) => $this->keyRepository->get($r->getKeyId(), $storeId),
            $group?->getRelations() ?? [],
        );
    }
}