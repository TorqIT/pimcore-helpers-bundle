<?php

namespace Torq\PimcoreHelpersBundle\Repository;

use Pimcore\Model\WebsiteSetting;

class WebsiteSettingRepository
{
    public function getById(?int $id)
    {
        return $id !== null ? WebsiteSetting::getById($id) : null;
    }

    public function getByName(
        ?string $name,
        ?int $siteId = null,
        ?string $language = null,
        ?string $fallbackLanguage = null,
    ) {
        return $name !== null ? WebsiteSetting::getByName($name, $siteId, $language, $fallbackLanguage) : null;
    }

    public function save(WebsiteSetting $setting)
    {
        $setting->save();
        return $setting;
    }

    public function delete(WebsiteSetting $setting)
    {
        $setting->delete();
    }
}