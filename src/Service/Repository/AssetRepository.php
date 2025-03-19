<?php

namespace Torq\PimcoreHelpersBundle\Service\Repository;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AssetRepository
{
    public function getById(int $id): ?Asset
    {
        return Asset::getById($id);
    }

    public function getByPath(string $path)
    {
        return Asset::getByPath($path);
    }

    public function save(Asset $asset): Asset
    {
        return $asset->save();
    }

    public function delete(Asset $asset)
    {
        $asset->delete();
    }

    public function deleteIfExists(string $path)
    {
        $target = Asset::getByPath($path);

        if ($target) {
            $target->delete();
        }
    }

    public function createAsset(UploadedFile $upload, ElementInterface $parent, bool $overwrite = false)
    {
        $asset = new Asset();
        $asset->setKey($upload->getClientOriginalName());
        $asset->setParent($parent);
        $asset->setData(file_get_contents($upload->getPathname()));

        if ($overwrite) {
            $this->deleteIfExists($parent->getFullPath() . "/" . $asset->getKey());
        } else {
            $asset->setKey(Service::getUniqueKey($asset));
        }

        $asset->save();

        return $asset;
    }
}
