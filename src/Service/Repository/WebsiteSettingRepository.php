<?php

namespace Torq\PimcoreHelpersBundle\Service\Repository;

use Pimcore\Model\WebsiteSetting;

class WebsiteSettingRepository
{
    public function create(string $name, string $type, mixed $data, bool $save = true)
    {
        $setting = new WebsiteSetting();
        $setting->setName($name);
        $setting->setType($type);
        $setting->setData($data);
        if ($save) {
            $this->save($setting);
        }
        return $setting;
    }

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
