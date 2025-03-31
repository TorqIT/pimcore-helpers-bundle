<?php

namespace Torq\PimcoreHelpersBundle\Service\Repository;

use Pimcore\Model\WebsiteSetting;

abstract class WebsiteSettingRepository
{
    public function getById(int $id)
    {
        return WebsiteSetting::getById($id);
    }

    public function getByName(string $name)
    {
        return WebsiteSetting::getByName($name);
    }

    public function save(WebsiteSetting $setting)
    {
        $setting->save();
    }

    public function delete(WebsiteSetting $setting)
    {
        $setting->delete();
    }
}
