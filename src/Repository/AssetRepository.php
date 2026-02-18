<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AssetRepository
{
    public function __construct(private FolderRepository $folderRepository) {}

    public function getById(int $id): ?Asset
    {
        return Asset::getById($id);
    }

    public function getByFilename(string $filename)
    {
        $list = new Asset\Listing();
        $list->addConditionParam('filename = ?', $filename);
        return $list->getData() ?? [];
    }

    public function getByPath(string $path)
    {
        return Asset::getByPath($path);
    }

    public function save(Asset $asset, array $parameters = []): Asset
    {
        return $asset->save($parameters);
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

    public function createOrUpdateAsset(mixed $data, string $filename, string|ElementInterface $parent) {
        if (is_string($parent)) {
            $parent = $this->folderRepository->getOrCreateAssetFolder($parent);
        }

        $asset = $this->getByPath($filename . $parent->getPath());
        if ($asset === null) {
            $asset = new Asset();
            $asset->setKey($filename);
            $asset->setParent($parent);
        }

        $asset->setData($data);
        return $this->save($asset);
    }

    public function createAssetFromUploadedFile(UploadedFile $upload, ElementInterface $parent, bool $overwrite = false)
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
