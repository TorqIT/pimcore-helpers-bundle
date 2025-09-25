<?php

namespace Torq\PimcoreHelpersBundle\Model\Asset;

use Carbon\Carbon;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;

class AssetMetadata
{
    private string $name;
    private string $language;
    private AssetMetadataType $type;
    private null|string|bool|AbstractObject|Asset|Carbon $data;

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getLanguage()
    {
        return $this->language;
    }

    public function setLanguage(string $language)
    {
        $this->language = $language;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(AssetMetadataType $type)
    {
        $this->type = $type;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(null|string|bool|AbstractObject|Asset|Carbon $data)
    {
        $this->data = $data;
        return $this;
    }
}