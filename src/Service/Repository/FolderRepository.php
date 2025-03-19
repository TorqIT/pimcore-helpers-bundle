<?php

namespace Torq\PimcoreHelpersBundle\Service\Repository;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;

class FolderRepository
{
    public function getAll()
    {
        return DataObject\Folder::getList()->load();
    }

    public function getDataObjectFolder(string $path, array $options = []): ?DataObject\Folder
    {
        return DataObject\Folder::getByPath($path, $options);
    }

    public function createDataObjectFolder(string $path, array $options = []): DataObject\Folder
    {
        return DataObject\Service::createFolderByPath($path, $options);
    }

    public function getOrCreateDataObjectFolder(string $path)
    {
        return DataObject\Folder::getByPath($path) ?? DataObject\Service::createFolderByPath($path);
    }

    public function getOrCreateAssetFolder(string $path): Asset\Folder
    {
        return $this->getAssetFolder($path) ?? $this->createAssetFolder($path);
    }

    public function getAssetFolder(string $path): ?Asset\Folder
    {
        return Asset\Folder::getByPath($path);
    }

    public function createAssetFolder(string $path, array $options = []): Asset\Folder
    {
        return Asset\Service::createFolderByPath($path, $options);
    }

    public function getDocumentFolder(string $path, array $options = []): ?Document\Folder
    {
        return Document\Folder::getByPath($path, $options);
    }

    public function createDocumentFolder(string $path, array $options = []): Document\Folder
    {
        return Document\Service::createFolderByPath($path, $options);
    }
}
