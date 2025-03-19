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

    public function getOrCreateDataObjectFolder(string $path, array $options = [])
    {
        return DataObject\Service::createFolderByPath($path, $options);
    }

    public function getAssetFolder(string $path, array $options = []): ?Asset\Folder
    {
        return Asset\Folder::getByPath($path, $options);
    }

    public function getOrCreateAssetFolder(string $path, array $options = []): Asset\Folder
    {
        return Asset\Service::createFolderByPath($path, $options);
    }

    public function getDocumentFolder(string $path, array $options = []): ?Document\Folder
    {
        return Document\Folder::getByPath($path, $options);
    }

    public function getOrCreateDocumentFolder(string $path, array $options = []): Document\Folder
    {
        return Document\Service::createFolderByPath($path, $options);
    }
}
